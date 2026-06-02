<?php
/**
 * Realtime Importer
 * Imports products as soon as they are AI-enhanced (real-time import)
 */

namespace App\Importers;

use App\Importers\Parsers\PakoworldParser;
use App\Importers\Parsers\B2BMarktParser;
use App\Importers\Parsers\LibertaParser;
use App\Importers\Parsers\EstiahParser;

class RealtimeImporter {
    private $downloader;
    private $sync;
    private $processed_skus = [];

    public function __construct() {
        $this->downloader = new XMLDownloader();

        // Enable auto-tracking for enhanced XMLs
        $this->sync = new ProductSync(true);
    }

    /**
     * Seed the processed-SKUs list so the next watchAndImport() run skips
     * those products. Used by `resume` mode to avoid re-importing items that
     * a previous run already finished.
     */
    public function seedProcessedSkus(array $skus): void {
        $this->processed_skus = array_values(array_unique(array_merge($this->processed_skus, $skus)));
    }

    /**
     * Cap the number of images sideloaded per product. Proxies to ProductSync.
     * Pass null to remove the cap (default behaviour: import all images).
     */
    public function setMaxImages(?int $max): void {
        $this->sync->setMaxImages($max);
    }

    /**
     * Find SKUs for a supplier whose `_last_synced` meta is at or after a given
     * timestamp. Used by `resume` mode to skip recently-imported products.
     *
     * @param string $supplier  Supplier slug, e.g. "B2BMarkt"
     * @param int    $since_ts  Unix timestamp; SKUs synced ≥ this are returned
     * @return string[]         SKU strings
     */
    public function findRecentlySyncedSkus(string $supplier, int $since_ts): array {
        global $wpdb;
        $since_mysql = date('Y-m-d H:i:s', $since_ts);
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT pm_sku.meta_value
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm_sku   ON p.ID = pm_sku.post_id   AND pm_sku.meta_key   = '_sku'
             INNER JOIN {$wpdb->postmeta} pm_sup   ON p.ID = pm_sup.post_id   AND pm_sup.meta_key   = '_supplier' AND pm_sup.meta_value = %s
             INNER JOIN {$wpdb->postmeta} pm_sync  ON p.ID = pm_sync.post_id  AND pm_sync.meta_key  = '_last_synced' AND pm_sync.meta_value >= %s
             WHERE p.post_type = 'product'",
            $supplier,
            $since_mysql
        ));
        return array_values(array_filter((array) $rows, fn($s) => $s !== ''));
    }

    /**
     * Watch for AI-enhanced products and import them in real-time
     *
     * @param string $supplier Supplier name
     * @param int $poll_interval Seconds between checks (default 5)
     * @param int $max_wait Maximum seconds to wait for completion (default 86400 = 24 hours)
     * @return array Processing statistics
     */
    public function watchAndImport($supplier, $poll_interval = 5, $max_wait = 86400) {
        $xml_dir = dirname(__DIR__) . '/data/xml_files/';
        $ready_file = $xml_dir . $supplier . '-ready.json';
        $progress_file = $xml_dir . $supplier . '-progress.json';
        $enhanced_xml = $xml_dir . 'enhanced/' . $supplier . '-enhanced.xml';

        error_log("RealtimeImporter: Starting watch for {$supplier}");
        error_log("RealtimeImporter: Ready file: {$ready_file}");

        $start_time = time();
        $stats = [
            'created'        => 0,
            'updated'        => 0,
            'errors'         => 0,
            'total_imported' => 0,
            'trashed'        => 0,
        ];

        // Track active SKUs for missing product cleanup
        $active_skus = [];

        while (true) {
            // Check timeout
            if (time() - $start_time > $max_wait) {
                error_log("RealtimeImporter: Max wait time reached, stopping watch");
                break;
            }

            // Check if AI processing is complete
            $ai_complete = false;
            if (file_exists($progress_file)) {
                $progress_data = json_decode(file_get_contents($progress_file), true);
                if ($progress_data && isset($progress_data['status'])) {
                    if ($progress_data['status'] === 'complete') {
                        $ai_complete = true;
                        error_log("RealtimeImporter: AI processing marked as complete");
                    }
                }
            }

            // Check for ready products
            if (file_exists($ready_file)) {
                $ready_data = json_decode(file_get_contents($ready_file), true);

                if ($ready_data && isset($ready_data['ready_skus'])) {
                    $ready_skus = $ready_data['ready_skus'];

                    // Find new SKUs (not yet processed)
                    $new_skus = array_diff($ready_skus, $this->processed_skus);

                    if (count($new_skus) > 0) {
                        error_log("RealtimeImporter: Found " . count($new_skus) . " new products ready for import");

                        // Import new products
                        try {
                            $import_stats = $this->importProducts($supplier, $enhanced_xml, $new_skus);

                            // Update stats
                            $stats['created'] += $import_stats['created'];
                            $stats['updated'] += $import_stats['updated'];
                            $stats['errors'] += $import_stats['errors'];
                            $stats['total_imported'] += count($new_skus);

                            // Mark as processed
                            $this->processed_skus = array_merge($this->processed_skus, $new_skus);

                            // Add to active SKUs list
                            $active_skus = array_merge($active_skus, $new_skus);
                            $active_skus = array_unique($active_skus);

                            error_log("RealtimeImporter: Imported " . count($new_skus) . " products " .
                                     "(created={$import_stats['created']}, updated={$import_stats['updated']}, errors={$import_stats['errors']})");
                        } catch (\Exception $e) {
                            error_log("RealtimeImporter: Error importing products: " . $e->getMessage());
                            $stats['errors'] += count($new_skus);
                        }
                    }
                }
            }

            // If AI is complete and no more products to process, we're done
            if ($ai_complete) {
                // Check one more time for any remaining products
                if (file_exists($ready_file)) {
                    $ready_data = json_decode(file_get_contents($ready_file), true);
                    if ($ready_data && isset($ready_data['ready_skus'])) {
                        $ready_skus = $ready_data['ready_skus'];
                        $remaining_skus = array_diff($ready_skus, $this->processed_skus);

                        if (count($remaining_skus) === 0) {
                            error_log("RealtimeImporter: All products imported, processing complete");

                            // Trash missing products
                            $trashed = $this->sync->trashMissingProducts($supplier, $active_skus);
                            $stats['trashed'] = $trashed;

                            error_log("RealtimeImporter: Trashed {$trashed} missing products");
                            break;
                        } else {
                            error_log("RealtimeImporter: Waiting for " . count($remaining_skus) . " remaining products...");
                        }
                    }
                }
            }

            // Wait before next check
            sleep($poll_interval);
        }

        error_log("RealtimeImporter: Completed - imported {$stats['total_imported']} products " .
                 "(created={$stats['created']}, updated={$stats['updated']}, errors={$stats['errors']}, trashed={$stats['trashed']})");

        return $stats;
    }

    /**
     * Import specific products by SKU from enhanced XML
     */
    private function importProducts($supplier, $xml_file, $skus) {
        if (!file_exists($xml_file)) {
            throw new \Exception("Enhanced XML not found: {$xml_file}");
        }

        error_log("RealtimeImporter: Importing " . count($skus) . " products from {$xml_file}");

        // Parse XML and get products
        $parser = $this->getParser($supplier, $xml_file);
        $all_products = $parser->parseProducts();

        // Filter products by SKU
        $products_to_import = [];
        foreach ($all_products as $product) {
            if (in_array($product->sku, $skus)) {
                $products_to_import[] = $product;
            }
        }

        if (count($products_to_import) === 0) {
            error_log("RealtimeImporter: No products found with specified SKUs");
            return [
                'created' => 0,
                'updated' => 0,
                'errors' => 0
            ];
        }

        // Sync in chunks to avoid memory/timeout issues
        $batch_size = 25;
        $chunks = array_chunk($products_to_import, $batch_size);
        $stats = ['created' => 0, 'updated' => 0, 'errors' => 0];

        foreach ($chunks as $chunk) {
            $chunk_stats = $this->sync->syncProducts($chunk, $supplier);
            $stats['created'] += $chunk_stats['created'] ?? 0;
            $stats['updated'] += $chunk_stats['updated'] ?? 0;
            $stats['errors']  += $chunk_stats['errors'] ?? 0;
        }

        return $stats;
    }

    /**
     * Get parser for supplier
     */
    private function getParser($supplier, $xml_file) {
        switch (strtolower($supplier)) {
            case 'pakoworld':
                return new PakoworldParser($xml_file);

            case 'b2bmarkt':
                return new B2BMarktParser($xml_file);

            case 'libertab2b':
                return new LibertaParser($xml_file);

            case 'estiahomeart':
                return new EstiahParser($xml_file);

            default:
                throw new \Exception("Unknown supplier: {$supplier}");
        }
    }
}

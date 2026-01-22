<?php
/**
 * Batch Importer
 * Processes imports in smaller batches to avoid timeouts
 */

namespace App\Importers;

use App\Importers\Parsers\PakoworldParser;
use App\Importers\Parsers\B2BMarktParser;
use App\Importers\Parsers\LibertaParser;
use App\Importers\Parsers\EstiahParser;

class BatchImporter {
    private $downloader;
    private $sync;
    private $batch_size = 25; // Process 25 products at a time (reduced for memory)

    public function __construct() {
        $this->downloader = new XMLDownloader();
        $this->sync = new ProductSync();

        // Get batch size from settings if available
        $saved_batch_size = get_option('xml_importer_batch_size', 25);
        if ($saved_batch_size > 0) {
            $this->batch_size = (int)$saved_batch_size;
        }
    }

    /**
     * Download XMLs (step 1)
     */
    public function downloadXMLs() {
        set_time_limit(300); // 5 minutes for download

        $results = $this->downloader->downloadAll();

        // Store download results
        update_option('xml_importer_download_results', $results);

        return $results;
    }

    /**
     * Get suppliers list
     */
    public function getSuppliers() {
        return ['pakoworld', 'b2bmarkt', 'libertab2b', 'estiahomeart'];
    }

    /**
     * Get total products count for a supplier
     */
    public function getSupplierProductCount($supplier) {
        $xml_file = $this->downloader->getLocalFile($supplier);

        if (!$xml_file) {
            return 0;
        }

        try {
            $parser = $this->getParser($supplier, $xml_file);
            return $parser->getProductCount();
        } catch (\Exception $e) {
            error_log("Error counting products for {$supplier}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Process a batch of products for a supplier
     */
    public function processBatch($supplier, $offset = 0, $limit = null) {
        if ($limit === null) {
            $limit = $this->batch_size;
        }

        set_time_limit(120); // 2 minutes per batch
        ini_set('memory_limit', '1024M'); // Increase memory for this request

        $xml_file = $this->downloader->getLocalFile($supplier);

        if (!$xml_file) {
            throw new \Exception("XML file not found for supplier: {$supplier}");
        }

        // Use cached products (stored in transient) if available
        // Disable caching for low-memory environments (development)
        $use_cache = wp_get_environment_type() !== 'development';
        $cache_key = 'xml_import_parsed_' . $supplier;
        $all_products = $use_cache ? get_transient($cache_key) : false;

        if ($all_products === false) {
            if ($offset === 0) {
                error_log("BatchImporter: Parsing {$supplier} XML (cache=" . ($use_cache ? 'enabled' : 'disabled') . ")");
            }

            try {
                $parser = $this->getParser($supplier, $xml_file);
                $all_products = $parser->parseProducts();

                // Only cache in production to save memory
                if ($use_cache) {
                    set_transient($cache_key, $all_products, HOUR_IN_SECONDS);
                }

                if ($offset === 0) {
                    error_log("BatchImporter: Parsed " . count($all_products) . " products from {$supplier}");
                }

                // In development, free memory aggressively after parsing
                if (!$use_cache) {
                    unset($parser);
                    gc_collect_cycles();
                }
            } catch (\Exception $e) {
                error_log("BatchImporter: Error parsing {$supplier}: " . $e->getMessage());
                throw $e;
            }
        } else {
            if ($offset === 0) {
                error_log("BatchImporter: Using cached products for {$supplier}");
            }
        }

        $total = count($all_products);

        // Get the batch
        $batch_products = array_slice($all_products, $offset, $limit);
        $batch_count = count($batch_products);

        // In low-memory mode, free the full product array ASAP
        if (!$use_cache) {
            unset($all_products);
            gc_collect_cycles();
        }

        error_log("BatchImporter: Processing batch for {$supplier} - offset={$offset}, batch_size={$batch_count}, total={$total}");

        if ($batch_count === 0) {
            // Clear cache when done
            if ($use_cache) {
                delete_transient($cache_key);
            }

            return [
                'success' => true,
                'processed' => 0,
                'total' => $total,
                'offset' => $offset,
                'complete' => true
            ];
        }

        try {
            // Track active SKUs across batches
            $active_skus_key = 'xml_import_active_skus_' . $supplier;

            // Initialize active SKUs list on first batch
            if ($offset === 0) {
                delete_transient($active_skus_key);
                error_log("BatchImporter: Starting new import session for {$supplier}");
            }

            // Get existing active SKUs
            $active_skus = get_transient($active_skus_key);
            if ($active_skus === false) {
                $active_skus = [];
            }

            // Sync batch
            $stats = $this->sync->syncProducts($batch_products, $supplier);

            // Free batch products from memory immediately
            unset($batch_products);

            // Add synced SKUs to active list
            $batch_skus = $this->sync->getSyncedSkus();
            $active_skus = array_merge($active_skus, $batch_skus);
            $active_skus = array_unique($active_skus); // Remove duplicates

            // Save updated active SKUs list
            set_transient($active_skus_key, $active_skus, HOUR_IN_SECONDS);

            error_log("BatchImporter: Batch synced - created={$stats['created']}, updated={$stats['updated']}, errors={$stats['errors']}");
            error_log("BatchImporter: Total active SKUs so far: " . count($active_skus));

            $new_offset = $offset + $batch_count;
            $is_complete = $new_offset >= $total;

            // When complete, trash missing products
            if ($is_complete) {
                error_log("BatchImporter: Import complete for {$supplier}, checking for missing products");

                // Trash products not in XML
                $trashed = $this->sync->trashMissingProducts($supplier, $active_skus);
                $stats['trashed'] = $trashed;

                // Clear caches (only if using cache)
                if ($use_cache) {
                    delete_transient($cache_key);
                }
                delete_transient($active_skus_key);

                error_log("BatchImporter: Completed {$supplier}, cleared cache, trashed {$trashed} products");
            }

            // Force garbage collection after each batch
            if (function_exists('gc_mem_caches')) {
                gc_mem_caches(); // PHP 7.0+
            }
            gc_collect_cycles();

            return [
                'success' => true,
                'processed' => $batch_count,
                'total' => $total,
                'offset' => $new_offset,
                'complete' => $is_complete,
                'stats' => $stats
            ];
        } catch (\Exception $e) {
            error_log("BatchImporter: Error syncing batch for {$supplier}: " . $e->getMessage());
            throw $e;
        }
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

    /**
     * Get batch size
     */
    public function getBatchSize() {
        return $this->batch_size;
    }

    /**
     * Set batch size
     */
    public function setBatchSize($size) {
        $this->batch_size = (int)$size;
    }
}

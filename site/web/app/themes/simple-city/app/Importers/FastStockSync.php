<?php
/**
 * Fast Stock & Price Sync
 * Lightweight sync that ONLY updates stock quantities and prices
 * No AI processing, no images, no categories - just stock & price
 */

namespace App\Importers;

class FastStockSync {
    private $downloader;
    private $stats;

    public function __construct() {
        $this->downloader = new XMLDownloader();
        $this->stats = [
            'updated' => 0,
            'not_found' => 0,
            'errors' => 0
        ];
    }

    /**
     * Sync stock and prices for a supplier
     * FAST: No AI, no images, no complex processing
     */
    public function syncSupplier($supplier) {
        $this->log("Starting fast stock sync for {$supplier}...");

        // Get original XML (NOT enhanced)
        $xml_file = $this->downloader->getLocalFile($supplier, false);

        if (!$xml_file) {
            throw new \Exception("XML file not found for supplier: {$supplier}");
        }

        // Parse XML
        $parser = $this->getParser($supplier, $xml_file);
        $products = $parser->parseProducts();

        $this->log("Found {count($products)} products in XML");

        // Batch size for commits
        $batch_size = 50;
        $batch_count = 0;

        foreach ($products as $product) {
            try {
                $this->updateProductStockAndPrice($product);
                $batch_count++;

                // Commit every 50 products to avoid memory issues
                if ($batch_count >= $batch_size) {
                    wp_cache_flush();
                    $batch_count = 0;
                }

            } catch (\Exception $e) {
                $this->stats['errors']++;
                error_log("FastStockSync: Error updating SKU {$product->sku}: " . $e->getMessage());
            }
        }

        $this->log("Fast sync completed for {$supplier}");
        $this->log("Updated: {$this->stats['updated']}, Not found: {$this->stats['not_found']}, Errors: {$this->stats['errors']}");

        return $this->stats;
    }

    /**
     * Sync all suppliers
     */
    public function syncAll() {
        $suppliers = ['pakoworld', 'b2bmarkt', 'libertab2b', 'estiahomeart'];
        $all_stats = [];

        foreach ($suppliers as $supplier) {
            try {
                $this->stats = ['updated' => 0, 'not_found' => 0, 'errors' => 0];
                $stats = $this->syncSupplier($supplier);
                $all_stats[$supplier] = $stats;
            } catch (\Exception $e) {
                $this->log("Error syncing {$supplier}: " . $e->getMessage(), 'error');
                $all_stats[$supplier] = [
                    'error' => $e->getMessage()
                ];
            }
        }

        return $all_stats;
    }

    /**
     * Update ONLY stock and price for a single product
     */
    private function updateProductStockAndPrice($product) {
        global $wpdb;

        // Find product by SKU
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1",
            $product->sku
        ));

        if (!$product_id) {
            $this->stats['not_found']++;
            return;
        }

        // Get WooCommerce product
        $wc_product = wc_get_product($product_id);

        if (!$wc_product) {
            $this->stats['not_found']++;
            return;
        }

        // Update ONLY stock
        if ($product->stock_quantity > 0) {
            $wc_product->set_manage_stock(true);
            $wc_product->set_stock_quantity($product->stock_quantity);
            $wc_product->set_stock_status($product->stock_status);
        } else {
            $wc_product->set_manage_stock(false);
            $wc_product->set_stock_status($product->stock_status);
        }

        // Update ONLY price (with markup)
        $markup_settings = get_option('xml_importer_markup', []);
        $markup = isset($markup_settings[$product->supplier])
            ? (float)$markup_settings[$product->supplier]
            : (float)($markup_settings['default'] ?? 0);

        $final_price = $product->calculateFinalPrice($markup);
        $wc_product->set_regular_price($final_price);

        if (!empty($product->sale_price) && $product->sale_price < $final_price) {
            $wc_product->set_sale_price($product->sale_price);
        }

        // Update last synced timestamp
        $wc_product->update_meta_data('_last_stock_sync', current_time('mysql'));

        // Save product
        $wc_product->save();

        $this->stats['updated']++;
    }

    /**
     * Get parser for supplier
     */
    private function getParser($supplier, $xml_file) {
        switch (strtolower($supplier)) {
            case 'pakoworld':
                return new Parsers\PakoworldParser($xml_file);

            case 'b2bmarkt':
                return new Parsers\B2BMarktParser($xml_file);

            case 'libertab2b':
                return new Parsers\LibertaParser($xml_file);

            case 'estiahomeart':
                return new Parsers\EstiahParser($xml_file);

            default:
                throw new \Exception("Unknown supplier: {$supplier}");
        }
    }

    /**
     * Log message
     */
    private function log($message, $level = 'info') {
        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] [FastStockSync] [{$level}] {$message}";

        error_log($log_entry);

        // Store in option for admin display
        $logs = get_option('xml_importer_logs', []);
        $logs[] = $log_entry;

        // Keep only last 100 entries
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }

        update_option('xml_importer_logs', $logs);
    }

    /**
     * Get sync statistics
     */
    public function getStats() {
        return $this->stats;
    }
}

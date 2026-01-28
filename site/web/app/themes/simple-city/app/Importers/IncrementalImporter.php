<?php
/**
 * Incremental Importer
 * Detects new/changed/deleted products and processes only those
 */

namespace App\Importers;

class IncrementalImporter {
    private $downloader;
    private $sync;
    private $sku_tracker;
    private $stats;

    public function __construct() {
        $this->downloader = new XMLDownloader();
        $this->sync = new ProductSync();
        $this->sku_tracker = new SKUTracker();
        $this->stats = [
            'new_products' => 0,
            'deleted_products' => 0,
            'enhanced_skus' => []
        ];
    }

    /**
     * Detect new products for a supplier
     * Returns array of new SKUs that need AI enhancement
     */
    public function detectNewProducts($supplier) {
        $this->log("Detecting new products for {$supplier}...");

        // Get original XML
        $xml_file = $this->downloader->getLocalFile($supplier, false);

        if (!$xml_file) {
            throw new \Exception("XML file not found for supplier: {$supplier}");
        }

        // Extract all SKUs from current XML
        $current_skus = $this->sku_tracker->extractSKUsFromXML($xml_file);
        $this->log("Found {count($current_skus)} SKUs in current XML");

        // Compare with enhanced SKUs
        $diff = $this->sku_tracker->compareWithXML($current_skus, $supplier);

        $this->log("New products: " . count($diff['new']));
        $this->log("Existing products: " . count($diff['existing']));
        $this->log("Deleted products: " . count($diff['deleted']));

        return $diff;
    }

    /**
     * Process only new products (AI enhancement required)
     * This creates an enhanced XML with ONLY new products
     */
    public function processNewProducts($supplier) {
        $this->log("Processing new products for {$supplier}...");

        // Detect new SKUs
        $diff = $this->detectNewProducts($supplier);

        if (empty($diff['new'])) {
            $this->log("No new products found for {$supplier}");
            return [
                'new_count' => 0,
                'enhanced_xml' => null
            ];
        }

        $this->log("Found " . count($diff['new']) . " new products that need AI enhancement");

        // Get original XML file
        $xml_file = $this->downloader->getLocalFile($supplier, false);

        // Create enhanced XML with ONLY new products
        $enhanced_xml = $this->createEnhancedXMLForSKUs($xml_file, $diff['new'], $supplier);

        // Mark these SKUs as enhanced
        $this->sku_tracker->markEnhancedBatch($diff['new'], $supplier);

        $this->stats['new_products'] = count($diff['new']);
        $this->stats['enhanced_skus'] = $diff['new'];

        return [
            'new_count' => count($diff['new']),
            'new_skus' => $diff['new'],
            'enhanced_xml' => $enhanced_xml
        ];
    }

    /**
     * Import new products from enhanced XML
     */
    public function importNewProducts($supplier) {
        $enhanced_xml = $this->downloader->getLocalFile($supplier, true);

        if (!$enhanced_xml) {
            throw new \Exception("Enhanced XML not found for {$supplier}. Please run AI enhancement first.");
        }

        // Parse and import
        $parser = $this->getParser($supplier, $enhanced_xml);
        $products = $parser->parseProducts();

        $this->log("Importing " . count($products) . " new products for {$supplier}");

        $stats = $this->sync->syncProducts($products, $supplier);

        return $stats;
    }

    /**
     * Trash deleted products (no longer in XML)
     */
    public function trashDeletedProducts($supplier) {
        $diff = $this->detectNewProducts($supplier);

        if (empty($diff['deleted'])) {
            $this->log("No deleted products for {$supplier}");
            return 0;
        }

        $this->log("Trashing " . count($diff['deleted']) . " deleted products for {$supplier}");

        $trashed_count = 0;

        foreach ($diff['deleted'] as $sku) {
            $product_id = $this->findProductBySKU($sku);

            if ($product_id) {
                wp_trash_post($product_id);
                $trashed_count++;
                $this->log("Trashed product SKU: {$sku} (ID: {$product_id})");
            }
        }

        $this->stats['deleted_products'] = $trashed_count;

        return $trashed_count;
    }

    /**
     * Full incremental workflow for a supplier
     */
    public function runIncrementalImport($supplier) {
        $this->log("Starting incremental import for {$supplier}...");

        // Step 1: Detect changes
        $diff = $this->detectNewProducts($supplier);

        $results = [
            'supplier' => $supplier,
            'new_count' => count($diff['new']),
            'deleted_count' => count($diff['deleted']),
            'existing_count' => count($diff['existing'])
        ];

        // Step 2: Trash deleted products
        if (!empty($diff['deleted'])) {
            $results['trashed'] = $this->trashDeletedProducts($supplier);
        }

        // Step 3: Return info about new products (AI enhancement needed separately)
        $results['new_skus'] = $diff['new'];

        $this->log("Incremental import completed for {$supplier}");

        return $results;
    }

    /**
     * Create enhanced XML file with only specific SKUs
     * (Used for Python AI processor to know which products to enhance)
     */
    private function createEnhancedXMLForSKUs($xml_file, $skus, $supplier) {
        // This will be called AFTER Python AI enhancement
        // For now, just return the filename where it should be created
        $xml_dir = dirname($xml_file);
        return $xml_dir . '/' . $supplier . '-enhanced.xml';
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
     * Find product by SKU
     */
    private function findProductBySKU($sku) {
        global $wpdb;

        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1",
            $sku
        ));

        return $product_id;
    }

    /**
     * Log message
     */
    private function log($message, $level = 'info') {
        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] [IncrementalImporter] [{$level}] {$message}";

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
     * Get statistics
     */
    public function getStats() {
        return $this->stats;
    }
}

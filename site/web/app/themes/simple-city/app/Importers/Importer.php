<?php
/**
 * Main Product Importer
 * Orchestrates XML download, parsing, and WooCommerce sync
 */

namespace App\Importers;

use App\Importers\Parsers\PakoworldParser;
use App\Importers\Parsers\B2BMarktParser;
use App\Importers\Parsers\LibertaParser;
use App\Importers\Parsers\EstiahParser;

class Importer {
    private $downloader;
    private $sync;
    private $results;

    public function __construct() {
        $this->downloader = new XMLDownloader();
        $this->sync = new ProductSync();
        $this->results = [];
    }

    /**
     * Run full import (download + parse + sync)
     */
    public function runFullImport() {
        $this->log("Starting full import...");

        // Download XMLs
        $this->log("Downloading XML feeds...");
        $download_results = $this->downloader->downloadAll();

        // Process each supplier
        $suppliers = ['pakoworld', 'b2bmarkt', 'libertab2b', 'estiahomeart'];

        foreach ($suppliers as $supplier) {
            try {
                $this->log("Processing {$supplier}...");
                $this->processSupplier($supplier);
            } catch (\Exception $e) {
                $this->log("Error processing {$supplier}: " . $e->getMessage(), 'error');
                $this->results[$supplier] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        $this->log("Import completed!");

        return $this->results;
    }

    /**
     * Process single supplier
     */
    public function processSupplier($supplier) {
        $xml_file = $this->downloader->getLocalFile($supplier);

        if (!$xml_file) {
            throw new \Exception("XML file not found for supplier: {$supplier}");
        }

        // Get appropriate parser
        $parser = $this->getParser($supplier, $xml_file);

        // Parse products
        $this->log("Parsing {$supplier} XML...");
        $products = $parser->parseProducts();
        $product_count = count($products);
        $this->log("Parsed {$product_count} products from {$supplier}");

        // Sync to WooCommerce
        $this->log("Syncing {$product_count} products to WooCommerce...");
        $stats = $this->sync->syncProducts($products, $supplier);

        $this->results[$supplier] = [
            'success' => true,
            'total_products' => $product_count,
            'created' => $stats['created'],
            'updated' => $stats['updated'],
            'skipped' => $stats['skipped'],
            'errors' => $stats['errors']
        ];

        $this->log("Completed {$supplier}: Created={$stats['created']}, Updated={$stats['updated']}, Errors={$stats['errors']}");

        return $this->results[$supplier];
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
     * Download XMLs only
     */
    public function downloadXMLs() {
        return $this->downloader->downloadAll();
    }

    /**
     * Process local XMLs only (skip download)
     */
    public function processLocalXMLs() {
        if (!$this->downloader->hasLocalFiles()) {
            throw new \Exception("No local XML files found. Please download first.");
        }

        $suppliers = ['pakoworld', 'b2bmarkt', 'libertab2b', 'estiahomeart'];

        foreach ($suppliers as $supplier) {
            if ($this->downloader->getLocalFile($supplier)) {
                try {
                    $this->processSupplier($supplier);
                } catch (\Exception $e) {
                    $this->log("Error processing {$supplier}: " . $e->getMessage(), 'error');
                }
            }
        }

        return $this->results;
    }

    /**
     * Get import results
     */
    public function getResults() {
        return $this->results;
    }

    /**
     * Log message
     */
    private function log($message, $level = 'info') {
        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] [{$level}] {$message}";

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
     * Get recent logs
     */
    public static function getLogs($count = 50) {
        $logs = get_option('xml_importer_logs', []);
        return array_slice($logs, -$count);
    }

    /**
     * Clear logs
     */
    public static function clearLogs() {
        delete_option('xml_importer_logs');
    }
}

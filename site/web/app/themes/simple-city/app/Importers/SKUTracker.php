<?php
/**
 * SKU Tracker
 * Tracks which products have been AI-enhanced
 */

namespace App\Importers;

class SKUTracker {
    private $option_key = 'xml_importer_enhanced_skus';

    /**
     * Check if a SKU has been AI-enhanced
     */
    public function hasEnhancement($sku, $supplier = null) {
        $enhanced_skus = $this->getAllEnhanced();

        if (!isset($enhanced_skus[$sku])) {
            return false;
        }

        if ($supplier && $enhanced_skus[$sku]['supplier'] !== $supplier) {
            return false;
        }

        return true;
    }

    /**
     * Mark a SKU as AI-enhanced
     */
    public function markEnhanced($sku, $supplier, $metadata = []) {
        $enhanced_skus = $this->getAllEnhanced();

        $enhanced_skus[$sku] = [
            'supplier' => $supplier,
            'enhanced_at' => current_time('mysql'),
            'metadata' => $metadata
        ];

        update_option($this->option_key, $enhanced_skus);
    }

    /**
     * Mark multiple SKUs as enhanced (batch)
     */
    public function markEnhancedBatch($skus, $supplier) {
        $enhanced_skus = $this->getAllEnhanced();

        foreach ($skus as $sku) {
            $enhanced_skus[$sku] = [
                'supplier' => $supplier,
                'enhanced_at' => current_time('mysql'),
                'metadata' => []
            ];
        }

        update_option($this->option_key, $enhanced_skus);
    }

    /**
     * Get all enhanced SKUs
     */
    public function getAllEnhanced() {
        return get_option($this->option_key, []);
    }

    /**
     * Get enhanced SKUs for a specific supplier
     */
    public function getEnhancedBySupplier($supplier) {
        $all_enhanced = $this->getAllEnhanced();

        return array_filter($all_enhanced, function($data) use ($supplier) {
            return $data['supplier'] === $supplier;
        });
    }

    /**
     * Get SKUs that exist in XML but not in enhanced list
     * Returns: ['new' => [...], 'existing' => [...]]
     */
    public function compareWithXML($xml_skus, $supplier) {
        $enhanced_skus = array_keys($this->getEnhancedBySupplier($supplier));

        $new_skus = array_diff($xml_skus, $enhanced_skus);
        $existing_skus = array_intersect($xml_skus, $enhanced_skus);

        return [
            'new' => array_values($new_skus),
            'existing' => array_values($existing_skus),
            'deleted' => array_values(array_diff($enhanced_skus, $xml_skus))
        ];
    }

    /**
     * Get SKUs from XML file
     */
    public function extractSKUsFromXML($xml_file) {
        if (!file_exists($xml_file)) {
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($xml_file);

        if ($xml === false) {
            error_log("SKUTracker: Failed to parse XML: {$xml_file}");
            return [];
        }

        $skus = [];

        // Find all products
        $products = $xml->xpath('//product');

        foreach ($products as $product) {
            // Try different SKU field names
            $sku = null;

            if (isset($product->model)) {
                $sku = (string)$product->model;
            } elseif (isset($product->sku)) {
                $sku = (string)$product->sku;
            } elseif (isset($product->code)) {
                $sku = (string)$product->code;
            }

            if ($sku) {
                $skus[] = $sku;
            }
        }

        return $skus;
    }

    /**
     * Clear all tracking data
     */
    public function clearAll() {
        delete_option($this->option_key);
    }

    /**
     * Get statistics
     */
    public function getStats() {
        $all_enhanced = $this->getAllEnhanced();

        $by_supplier = [];
        foreach ($all_enhanced as $sku => $data) {
            $supplier = $data['supplier'];
            if (!isset($by_supplier[$supplier])) {
                $by_supplier[$supplier] = 0;
            }
            $by_supplier[$supplier]++;
        }

        return [
            'total' => count($all_enhanced),
            'by_supplier' => $by_supplier
        ];
    }
}

<?php
/**
 * Abstract XML Parser
 * Base class for all supplier-specific XML parsers
 */

namespace App\Importers\Parsers;

use App\Importers\Models\NormalizedProduct;

abstract class AbstractParser {
    protected $xml;
    protected $supplier_name;
    protected $file_path;

    public function __construct($file_path) {
        $this->file_path = $file_path;
    }

    /**
     * Load and parse XML file
     */
    public function loadXML() {
        if (!file_exists($this->file_path)) {
            throw new \Exception("XML file not found: {$this->file_path}");
        }

        libxml_use_internal_errors(true);

        // For large files, use XMLReader or chunks
        $filesize = filesize($this->file_path);
        if ($filesize > 10 * 1024 * 1024) { // 10MB
            // Use streaming parser for large files
            $this->xml = $this->loadLargeXML();
        } else {
            $this->xml = simplexml_load_file($this->file_path, 'SimpleXMLElement', LIBXML_NOCDATA);
        }

        if ($this->xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $error_messages = array_map(function($error) {
                return $error->message;
            }, $errors);
            throw new \Exception("Failed to parse XML: " . implode(', ', $error_messages));
        }

        return true;
    }

    /**
     * Load large XML files with streaming
     */
    protected function loadLargeXML() {
        // For now, still use simplexml but with memory optimization
        return simplexml_load_file($this->file_path, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_COMPACT);
    }

    /**
     * Parse all products from XML
     * Must be implemented by child classes
     *
     * @return NormalizedProduct[]
     */
    abstract public function parseProducts();

    /**
     * Parse single product node
     * Must be implemented by child classes
     *
     * @param \SimpleXMLElement $productNode
     * @return NormalizedProduct
     */
    abstract protected function parseProduct($productNode);

    /**
     * Get supplier name
     */
    public function getSupplierName() {
        return $this->supplier_name;
    }

    /**
     * Safely get XML node value
     */
    protected function getNodeValue($node, $default = '') {
        return isset($node) && !empty((string)$node) ? trim((string)$node) : $default;
    }

    /**
     * Safely get XML node attribute
     */
    protected function getNodeAttribute($node, $attribute, $default = '') {
        return isset($node[$attribute]) ? trim((string)$node[$attribute]) : $default;
    }

    /**
     * Parse price - handle comma/dot decimal separators
     */
    protected function parsePrice($priceString) {
        if (empty($priceString)) {
            return 0;
        }

        // Remove any currency symbols and whitespace
        $priceString = preg_replace('/[^\d.,]/', '', (string)$priceString);

        // Convert comma to dot
        $priceString = str_replace(',', '.', $priceString);

        return (float)$priceString;
    }

    /**
     * Parse stock status from various formats
     */
    protected function parseStockStatus($stock, $availabilityText = '') {
        $stock = (int)$stock;

        if ($stock > 0) {
            return 'instock';
        }

        // Check availability text
        $availabilityText = strtolower($availabilityText);
        if (strpos($availabilityText, 'διαθέσιμο') !== false ||
            strpos($availabilityText, 'available') !== false) {
            return 'instock';
        }

        if (strpos($availabilityText, 'παραγγελία') !== false ||
            strpos($availabilityText, 'backorder') !== false ||
            strpos($availabilityText, 'κατόπιν') !== false) {
            return 'onbackorder';
        }

        return 'outofstock';
    }

    /**
     * Clean HTML description
     */
    protected function cleanDescription($html) {
        if (empty($html)) {
            return '';
        }

        // Decode HTML entities
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove excessive whitespace
        $html = preg_replace('/\s+/', ' ', $html);

        return trim($html);
    }

    /**
     * Extract dimensions from text
     */
    protected function parseDimensions($dimensionsText) {
        $dimensions = [
            'length' => 0,
            'width' => 0,
            'height' => 0
        ];

        if (empty($dimensionsText)) {
            return $dimensions;
        }

        // Try to extract numbers from formats like "265x188x99cm" or "265x188x99"
        if (preg_match('/(\d+\.?\d*)\s*[xX×]\s*(\d+\.?\d*)\s*[xX×]\s*(\d+\.?\d*)/', $dimensionsText, $matches)) {
            $dimensions['length'] = (float)$matches[1];
            $dimensions['width'] = (float)$matches[2];
            $dimensions['height'] = (float)$matches[3];
        }

        return $dimensions;
    }

    /**
     * Get total product count
     */
    public function getProductCount() {
        if (!$this->xml) {
            $this->loadXML();
        }

        return count($this->getProductNodes());
    }

    /**
     * Get product nodes from XML
     * Must be implemented by child classes
     */
    abstract protected function getProductNodes();
}

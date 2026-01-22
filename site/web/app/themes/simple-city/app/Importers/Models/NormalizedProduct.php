<?php
/**
 * Normalized Product Model
 * Common data structure for all supplier products
 */

namespace App\Importers\Models;

class NormalizedProduct {
    public $sku;
    public $name;
    public $name_en;
    public $description;
    public $description_en;
    public $barcode;
    public $supplier;

    // Prices
    public $wholesale_price;
    public $retail_price;
    public $sale_price;

    // Stock
    public $stock_quantity;
    public $stock_status; // 'instock', 'outofstock', 'onbackorder'

    // Images
    public $main_image_url;
    public $gallery_image_urls = [];

    // Categories
    public $categories = [];

    // Attributes
    public $attributes = [];

    // Dimensions & Weight
    public $weight;
    public $length;
    public $width;
    public $height;
    public $dimensions_text;

    // Additional
    public $manufacturer;
    public $material;
    public $color;
    public $assembly_manual_url;
    public $related_skus = [];
    public $specifications = [];

    // Metadata
    public $supplier_product_id;
    public $supplier_url;

    public function __construct() {
        $this->categories = [];
        $this->attributes = [];
        $this->gallery_image_urls = [];
        $this->related_skus = [];
        $this->specifications = [];
    }

    /**
     * Validate required fields
     */
    public function validate() {
        $errors = [];

        if (empty($this->sku)) {
            $errors[] = 'SKU is required';
        }

        if (empty($this->name)) {
            $errors[] = 'Product name is required';
        }

        if (empty($this->wholesale_price) && empty($this->retail_price)) {
            $errors[] = 'At least one price is required';
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Convert to array
     */
    public function toArray() {
        return get_object_vars($this);
    }

    /**
     * Get final price after markup
     */
    public function calculateFinalPrice($markup_percentage = 0, $add_vat = false, $vat_rate = 24) {
        $base_price = $this->wholesale_price ?: $this->retail_price;

        if ($markup_percentage > 0) {
            $base_price = $base_price * (1 + ($markup_percentage / 100));
        }

        if ($add_vat) {
            $base_price = $base_price * (1 + ($vat_rate / 100));
        }

        return round($base_price, 2);
    }
}

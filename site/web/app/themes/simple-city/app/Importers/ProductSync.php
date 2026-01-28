<?php
/**
 * WooCommerce Product Sync
 * Syncs normalized products to WooCommerce
 */

namespace App\Importers;

use App\Importers\Models\NormalizedProduct;

class ProductSync {
    private $markup_settings;
    private $stats;
    private $synced_skus = []; // Track SKUs that were synced
    private $sku_tracker = null;
    private $auto_track_enhancements = false;

    public function __construct($auto_track_enhancements = false) {
        $this->markup_settings = get_option('xml_importer_markup', []);
        $this->auto_track_enhancements = $auto_track_enhancements;
        $this->stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'trashed' => 0
        ];

        // Initialize SKU tracker if auto-tracking enabled
        if ($this->auto_track_enhancements) {
            require_once(__DIR__ . '/SKUTracker.php');
            $this->sku_tracker = new SKUTracker();
        }
    }

    /**
     * Sync multiple products
     */
    public function syncProducts($products, $supplier = null) {
        // Track which SKUs we've seen in this batch
        foreach ($products as $product) {
            try {
                $this->syncProduct($product);
                // Track this SKU as synced
                if (!empty($product->sku)) {
                    $this->synced_skus[] = $product->sku;
                }
            } catch (\Exception $e) {
                $this->stats['errors']++;
                error_log("Product sync error for SKU {$product->sku}: " . $e->getMessage());
            }
        }

        return $this->stats;
    }

    /**
     * Get synced SKUs for this session
     */
    public function getSyncedSkus() {
        return $this->synced_skus;
    }

    /**
     * Trash products that are no longer in XML feed
     */
    public function trashMissingProducts($supplier, $active_skus) {
        global $wpdb;

        error_log("ProductSync: Checking for missing products from supplier: {$supplier}");
        error_log("ProductSync: Active SKUs count: " . count($active_skus));

        if (empty($active_skus)) {
            error_log("ProductSync: No active SKUs provided, skipping trash");
            return 0;
        }

        // Get all products for this supplier
        $supplier_products = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, pm.meta_value as sku
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_supplier' AND pm2.meta_value = %s
            WHERE p.post_type = 'product'
            AND p.post_status = 'publish'",
            $supplier
        ));

        $trashed_count = 0;

        foreach ($supplier_products as $product_row) {
            $sku = $product_row->sku;
            $product_id = $product_row->ID;

            // If this SKU is not in the active list, trash it
            if (!in_array($sku, $active_skus)) {
                wp_trash_post($product_id);
                $trashed_count++;
                error_log("ProductSync: Trashed product ID {$product_id} (SKU: {$sku}) - not in XML");
            }
        }

        error_log("ProductSync: Trashed {$trashed_count} missing products from {$supplier}");

        return $trashed_count;
    }

    /**
     * Sync single product
     */
    public function syncProduct(NormalizedProduct $product) {
        // Validate product
        $validation = $product->validate();
        if ($validation !== true) {
            $this->stats['skipped']++;
            throw new \Exception("Validation failed: " . implode(', ', $validation));
        }

        // Check if product exists by SKU
        $existing_id = $this->findProductBySKU($product->sku);

        if ($existing_id) {
            $this->updateProduct($existing_id, $product);
            $this->stats['updated']++;
        } else {
            $this->createProduct($product);
            $this->stats['created']++;
        }

        // Auto-track if this product has AI enhancements
        if ($this->auto_track_enhancements && $this->hasAIEnhancements($product)) {
            $this->sku_tracker->markEnhanced($product->sku, $product->supplier, [
                'import_date' => current_time('mysql'),
                'has_ai_title' => !empty($product->name),
                'has_ai_description' => !empty($product->description),
                'has_ai_tags' => !empty($product->tags),
                'has_ai_category' => !empty($product->woo_category)
            ]);
        }
    }

    /**
     * Check if product has AI enhancements
     */
    private function hasAIEnhancements($product) {
        // Check if product has AI-enhanced fields
        return !empty($product->woo_category) || !empty($product->tags);
    }

    /**
     * Create new WooCommerce product
     */
    private function createProduct(NormalizedProduct $product) {
        $wc_product = new \WC_Product_Simple();

        $this->setProductData($wc_product, $product);

        $product_id = $wc_product->save();

        // Set images
        $this->setProductImages($product_id, $product);

        // Set categories
        $this->setProductCategories($product_id, $product);

        // Set attributes
        $this->setProductAttributes($product_id, $product);

        return $product_id;
    }

    /**
     * Update existing WooCommerce product
     */
    private function updateProduct($product_id, NormalizedProduct $product) {
        $wc_product = wc_get_product($product_id);

        if (!$wc_product) {
            throw new \Exception("Product not found: {$product_id}");
        }

        // If product is trashed, restore it (untrash)
        $post_status = get_post_status($product_id);
        if ($post_status === 'trash') {
            wp_untrash_post($product_id);
            error_log("ProductSync: Restored trashed product {$product->sku} (ID: {$product_id})");
        }

        $this->setProductData($wc_product, $product);

        $wc_product->save();

        // Update images
        $this->setProductImages($product_id, $product);

        // Update categories
        $this->setProductCategories($product_id, $product);

        // Update attributes
        $this->setProductAttributes($product_id, $product);

        return $product_id;
    }

    /**
     * Set common product data
     */
    private function setProductData(\WC_Product $wc_product, NormalizedProduct $product) {
        // Basic info
        $wc_product->set_name($product->name);
        $wc_product->set_sku($product->sku);
        $wc_product->set_description($product->description);

        // Prices
        $markup = $this->getMarkupForSupplier($product->supplier);

        // If retail_price exists, use it directly (already has VAT from supplier)
        // Otherwise calculate from wholesale_price with markup
        $final_price = $product->calculateFinalPrice($markup);
        $wc_product->set_regular_price($final_price);

        // Set sale price if exists and is lower than regular price
        if (!empty($product->sale_price) && $product->sale_price > 0 && $product->sale_price < $final_price) {
            $wc_product->set_sale_price($product->sale_price);
        }

        // Stock
        $wc_product->set_stock_status($product->stock_status);
        if ($product->stock_quantity > 0) {
            $wc_product->set_manage_stock(true);
            $wc_product->set_stock_quantity($product->stock_quantity);
        } else {
            $wc_product->set_manage_stock(false);
        }

        // Dimensions & Weight
        if (!empty($product->weight)) {
            $wc_product->set_weight($product->weight);
        }
        if (!empty($product->length)) {
            $wc_product->set_length($product->length);
        }
        if (!empty($product->width)) {
            $wc_product->set_width($product->width);
        }
        if (!empty($product->height)) {
            $wc_product->set_height($product->height);
        }

        // Meta data
        $wc_product->update_meta_data('_supplier', $product->supplier);
        $wc_product->update_meta_data('_supplier_product_id', $product->supplier_product_id);
        $wc_product->update_meta_data('_barcode', $product->barcode);
        $wc_product->update_meta_data('_last_synced', current_time('mysql'));

        if (!empty($product->manufacturer)) {
            $wc_product->update_meta_data('_manufacturer', $product->manufacturer);
        }
    }

    /**
     * Set product images
     */
    private function setProductImages($product_id, NormalizedProduct $product) {
        if (empty($product->main_image_url)) {
            return;
        }

        // Download and attach main image
        $main_image_id = $this->downloadAndAttachImage($product->main_image_url, $product_id);
        if ($main_image_id) {
            set_post_thumbnail($product_id, $main_image_id);
        }

        // Download and attach gallery images
        if (!empty($product->gallery_image_urls)) {
            $gallery_ids = [];
            foreach ($product->gallery_image_urls as $image_url) {
                $image_id = $this->downloadAndAttachImage($image_url, $product_id);
                if ($image_id) {
                    $gallery_ids[] = $image_id;
                }
            }
            if (!empty($gallery_ids)) {
                update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
            }
        }
    }

    /**
     * Download and attach image
     */
    private function downloadAndAttachImage($image_url, $product_id) {
        // Check if image already exists
        $existing_id = $this->findImageByURL($image_url);
        if ($existing_id) {
            return $existing_id;
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $temp_file = download_url($image_url);

        if (is_wp_error($temp_file)) {
            error_log("Failed to download image {$image_url}: " . $temp_file->get_error_message());
            return false;
        }

        $file = [
            'name' => basename($image_url),
            'tmp_name' => $temp_file
        ];

        $image_id = media_handle_sideload($file, $product_id);

        if (is_wp_error($image_id)) {
            @unlink($temp_file);
            error_log("Failed to sideload image {$image_url}: " . $image_id->get_error_message());
            return false;
        }

        // Store URL for future reference
        update_post_meta($image_id, '_source_url', $image_url);

        return $image_id;
    }

    /**
     * Find image by source URL
     */
    private function findImageByURL($url) {
        global $wpdb;

        $image_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_source_url' AND meta_value = %s LIMIT 1",
            $url
        ));

        return $image_id;
    }

    /**
     * Set product categories
     */
    private function setProductCategories($product_id, NormalizedProduct $product) {
        $category_ids = [];

        // Priority 1: Use AI-enhanced WooCommerce category if available
        if (!empty($product->woo_category)) {
            $term = get_term_by('name', $product->woo_category, 'product_cat');

            if (!$term) {
                // Create AI category
                $new_term = wp_insert_term($product->woo_category, 'product_cat');
                if (!is_wp_error($new_term)) {
                    $category_ids[] = $new_term['term_id'];
                }
            } else {
                $category_ids[] = $term->term_id;
            }
        }
        // Priority 2: Fallback to supplier categories
        elseif (!empty($product->categories)) {
            foreach ($product->categories as $cat) {
                $cat_name = $cat['name'];
                $term = get_term_by('name', $cat_name, 'product_cat');

                if (!$term) {
                    // Create category
                    $new_term = wp_insert_term($cat_name, 'product_cat');
                    if (!is_wp_error($new_term)) {
                        $category_ids[] = $new_term['term_id'];
                    }
                } else {
                    $category_ids[] = $term->term_id;
                }
            }
        }

        if (!empty($category_ids)) {
            wp_set_object_terms($product_id, $category_ids, 'product_cat');
        }

        // Set AI-generated tags
        if (!empty($product->tags)) {
            wp_set_object_terms($product_id, $product->tags, 'product_tag');
        }
    }

    /**
     * Set product attributes
     */
    private function setProductAttributes($product_id, NormalizedProduct $product) {
        if (empty($product->attributes)) {
            return;
        }

        $wc_product = wc_get_product($product_id);
        $attributes = [];

        foreach ($product->attributes as $attr) {
            $attr_name = isset($attr['name']) ? $attr['name'] : (isset($attr['id']) ? 'Attribute ' . $attr['id'] : 'Attribute');
            $attr_value = $attr['value'];

            $attribute = new \WC_Product_Attribute();
            $attribute->set_name($attr_name);
            $attribute->set_options([$attr_value]);
            $attribute->set_visible(true);
            $attribute->set_variation(false);

            $attributes[] = $attribute;
        }

        $wc_product->set_attributes($attributes);
        $wc_product->save();
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
     * Get markup percentage for supplier
     */
    private function getMarkupForSupplier($supplier) {
        if (isset($this->markup_settings[$supplier])) {
            return (float)$this->markup_settings[$supplier];
        }

        // Default markup
        return isset($this->markup_settings['default']) ? (float)$this->markup_settings['default'] : 0;
    }

    /**
     * Get sync statistics
     */
    public function getStats() {
        return $this->stats;
    }
}

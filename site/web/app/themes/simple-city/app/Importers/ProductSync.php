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

    // Attributes that should be global WooCommerce taxonomies (filterable via layered nav).
    // Key = mb_strtolower'd input name, value = [canonical Greek label, taxonomy slug].
    private $global_attribute_names = [
        'χρώμα'    => ['χρώμα', 'xroma'],
        'color'    => ['χρώμα', 'xroma'],
        'υλικό'    => ['υλικό', 'yliko'],
        'material' => ['υλικό', 'yliko'],
    ];

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

        // Set brand taxonomy
        $this->setProductBrand($product_id, $product);

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

        // Update brand taxonomy
        $this->setProductBrand($product_id, $product);

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
     * Set product images.
     * Downloads all images, batch-removes backgrounds via rembg, then sideloads.
     */
    private function setProductImages($product_id, NormalizedProduct $product) {
        if (empty($product->main_image_url)) {
            return;
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $gallery_urls  = $product->gallery_image_urls ?? [];
        $featured_url  = $product->main_image_url;
        $final_gallery = $gallery_urls;

        // Swap featured ↔ gallery[0] if main is a room/lifestyle photo and gallery[0]
        // is the clean white-background product shot.
        $has_image_lib = function_exists('imagecreatefromjpeg') || class_exists('Imagick');
        if (!empty($gallery_urls) && $has_image_lib) {
            $main_tmp = download_url($product->main_image_url);
            if (!is_wp_error($main_tmp)) {
                $main_is_white = $this->hasWhiteBackground($main_tmp);
                @unlink($main_tmp);

                if (!$main_is_white) {
                    $gallery0_tmp = download_url($gallery_urls[0]);
                    if (!is_wp_error($gallery0_tmp)) {
                        $gallery0_is_white = $this->hasWhiteBackground($gallery0_tmp);
                        @unlink($gallery0_tmp);

                        if ($gallery0_is_white) {
                            $featured_url  = $gallery_urls[0];
                            $final_gallery = array_slice($gallery_urls, 1);
                            array_unshift($final_gallery, $product->main_image_url);
                            error_log("ProductSync: Swapped images for product {$product_id}: gallery[0] → featured");
                        }
                    }
                }
            }
        }

        // Download all new images (skip already-imported ones)
        $all_urls    = array_unique(array_merge([$featured_url], $final_gallery));
        $url_to_temp = []; // url => temp_path

        foreach ($all_urls as $url) {
            if ($this->findImageByURL($url)) {
                continue;
            }
            $tmp = download_url($url);
            if (!is_wp_error($tmp)) {
                $url_to_temp[$url] = $tmp;
            } else {
                error_log("ProductSync: Failed to download {$url}: " . $tmp->get_error_message());
            }
        }

        // Batch AI background removal (rembg — model loads once per product)
        $url_to_processed = $this->batchRemoveBackgrounds($url_to_temp);

        // Sideload featured image
        $featured_id = $this->sideloadProductImage($featured_url, $url_to_processed, $url_to_temp, $product_id);
        if ($featured_id) {
            set_post_thumbnail($product_id, $featured_id);
        }

        // Sideload gallery images
        if (!empty($final_gallery)) {
            $gallery_ids = [];
            foreach ($final_gallery as $url) {
                $id = $this->sideloadProductImage($url, $url_to_processed, $url_to_temp, $product_id);
                if ($id) {
                    $gallery_ids[] = $id;
                }
            }
            if (!empty($gallery_ids)) {
                update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
            }
        }

        // Cleanup: original temp files (media_handle_sideload moves them, so @unlink is a no-op on success)
        foreach ($url_to_temp as $tmp) {
            @unlink($tmp);
        }
        // Cleanup any rembg outputs not consumed (e.g. sideload failed)
        foreach ($url_to_processed as $out) {
            @unlink($out);
        }
    }

    /**
     * Batch remove backgrounds using rembg (Python, U2Net model).
     * Calls remove_bg_batch.py once for all images; model loads a single time.
     *
     * @param  array $url_to_temp  url => temp_file_path
     * @return array               url => rembg_webp_path (only for successful results)
     */
    private function batchRemoveBackgrounds(array $url_to_temp): array
    {
        $dbg = '/tmp/rembg_php.log';
        $log = function(string $msg) use ($dbg) {
            file_put_contents($dbg, date('H:i:s') . " {$msg}\n", FILE_APPEND);
        };

        if (empty($url_to_temp)) {
            return [];
        }

        $script_dir = get_template_directory() . '/scripts/product-ai-processor';
        $wrapper    = $script_dir . '/rembg_run.sh';

        // rembg_run.sh is inside the virtiofs mount so file_exists() works even
        // with PHP-FPM open_basedir. The wrapper itself locates the correct Python
        // at runtime (exec() is not subject to open_basedir).
        $log("wrapper=" . (file_exists($wrapper) ? 'OK' : 'MISSING') . " script_dir={$script_dir}");

        if (!file_exists($wrapper)) {
            error_log("ProductSync: rembg_run.sh not found at {$wrapper}");
            return [];
        }

        // Build input/output pairs
        $output_map = []; // url => output_webp_path
        $all_args   = [];
        foreach ($url_to_temp as $url => $temp_path) {
            $out = sys_get_temp_dir() . '/rembg_' . uniqid() . '.webp';
            $output_map[$url] = $out;
            $all_args[] = escapeshellarg($temp_path);
            $all_args[] = escapeshellarg($out);
        }

        $command = sprintf(
            'sh %s %s',
            escapeshellarg($wrapper),
            implode(' ', $all_args)
        );

        $log("CMD: {$command}");
        exec($command, $output_lines, $return_code);
        $log("exit={$return_code} output=" . implode(' | ', $output_lines));

        if ($return_code !== 0) {
            error_log("ProductSync: batchRemoveBackgrounds failed (exit {$return_code}): " . implode(' ', $output_lines));
            return [];
        }

        // Return only URLs where a non-empty WebP was produced
        $result = [];
        foreach ($output_map as $url => $out) {
            if (file_exists($out) && filesize($out) > 0) {
                $result[$url] = $out;
            }
        }

        return $result;
    }

    /**
     * Sideload a single product image (rembg WebP if available, else original).
     * Returns the attachment ID or false on failure.
     */
    private function sideloadProductImage(string $url, array $url_to_processed, array $url_to_temp, int $product_id)
    {
        $existing_id = $this->findImageByURL($url);
        if ($existing_id) {
            return $existing_id;
        }

        // Prefer rembg-processed WebP; fall back to original temp
        if (isset($url_to_processed[$url]) && file_exists($url_to_processed[$url])) {
            $file_path = $url_to_processed[$url];
            $filename  = preg_replace('/\.[^.]+$/', '.webp', basename($url));
        } elseif (isset($url_to_temp[$url]) && file_exists($url_to_temp[$url])) {
            $file_path = $url_to_temp[$url];
            $filename  = basename($url);
        } else {
            return false;
        }

        $file     = ['name' => $filename, 'tmp_name' => $file_path];
        $image_id = media_handle_sideload($file, $product_id);

        if (is_wp_error($image_id)) {
            error_log("ProductSync: Failed to sideload {$url}: " . $image_id->get_error_message());
            return false;
        }

        update_post_meta($image_id, '_source_url', $url);
        return $image_id;
    }

    /**
     * Check if an image has a white background by sampling corner pixels.
     * Returns true if >80% of sampled corner pixels are near-white (R,G,B > 235).
     */
    private function hasWhiteBackground($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }

        // Try Imagick first (more widely available in this environment)
        if (class_exists('Imagick')) {
            return $this->hasWhiteBackgroundImagick($file_path);
        }

        // Fallback to GD
        if (function_exists('imagecreatefromjpeg')) {
            return $this->hasWhiteBackgroundGD($file_path);
        }

        return false;
    }

    private function hasWhiteBackgroundImagick($file_path) {
        try {
            $imagick = new \Imagick($file_path);
            $width   = $imagick->getImageWidth();
            $height  = $imagick->getImageHeight();
            $inset   = 5;

            $sample_points = [
                [$inset,          $inset],
                [$width - $inset, $inset],
                [$inset,          $height - $inset],
                [$width - $inset, $height - $inset],
                [(int)($width / 2), $inset],
                [(int)($width / 2), $height - $inset],
                [$inset,            (int)($height / 2)],
                [$width - $inset,   (int)($height / 2)],
            ];

            $white_count = 0;
            $threshold   = 235;
            $pixel_log   = [];

            foreach ($sample_points as [$x, $y]) {
                $pixel     = $imagick->getImagePixelColor($x, $y);
                $color     = $pixel->getColor();
                $r = $color['r'];
                $g = $color['g'];
                $b = $color['b'];
                $is_white  = $r >= $threshold && $g >= $threshold && $b >= $threshold;
                if ($is_white) $white_count++;
                $pixel_log[] = "({$x},{$y})=rgb({$r},{$g},{$b})" . ($is_white ? '✓' : '✗');
            }

            $imagick->destroy();

            $result = $white_count >= 6;
            error_log("hasWhiteBackground(imagick) [{$file_path}]: {$white_count}/8 → " . ($result ? 'WHITE' : 'NOT WHITE') . " | " . implode(' ', $pixel_log));
            return $result;

        } catch (\Exception $e) {
            error_log("hasWhiteBackgroundImagick error: " . $e->getMessage());
            return false;
        }
    }

    private function hasWhiteBackgroundGD($file_path) {
        $mime  = mime_content_type($file_path);
        $image = null;

        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $image = @imagecreatefromjpeg($file_path);
        } elseif ($mime === 'image/png') {
            $image = @imagecreatefrompng($file_path);
        } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $image = @imagecreatefromwebp($file_path);
        }

        if (!$image) return false;

        $width  = imagesx($image);
        $height = imagesy($image);
        $inset  = 5;

        $sample_points = [
            [$inset,          $inset],
            [$width - $inset, $inset],
            [$inset,          $height - $inset],
            [$width - $inset, $height - $inset],
            [(int)($width / 2), $inset],
            [(int)($width / 2), $height - $inset],
            [$inset,            (int)($height / 2)],
            [$width - $inset,   (int)($height / 2)],
        ];

        $white_count = 0;
        $threshold   = 235;
        $pixel_log   = [];

        foreach ($sample_points as [$x, $y]) {
            $rgb      = imagecolorat($image, $x, $y);
            $r        = ($rgb >> 16) & 0xFF;
            $g        = ($rgb >>  8) & 0xFF;
            $b        = ($rgb      ) & 0xFF;
            $is_white = $r >= $threshold && $g >= $threshold && $b >= $threshold;
            if ($is_white) $white_count++;
            $pixel_log[] = "({$x},{$y})=rgb({$r},{$g},{$b})" . ($is_white ? '✓' : '✗');
        }

        imagedestroy($image);

        $result = $white_count >= 6;
        error_log("hasWhiteBackground(gd) [{$file_path}]: {$white_count}/8 → " . ($result ? 'WHITE' : 'NOT WHITE') . " | " . implode(' ', $pixel_log));
        return $result;
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
    /**
     * Set product brand via product_brand taxonomy
     */
    private function setProductBrand($product_id, NormalizedProduct $product) {
        if (empty($product->manufacturer)) {
            return;
        }

        if (!taxonomy_exists('product_brand')) {
            return;
        }

        // Get or create the brand term
        $term = term_exists($product->manufacturer, 'product_brand');
        if (!$term) {
            $term = wp_insert_term($product->manufacturer, 'product_brand');
        }

        if (!is_wp_error($term)) {
            $term_id = is_array($term) ? $term['term_id'] : $term;
            wp_set_object_terms($product_id, (int) $term_id, 'product_brand');
        }
    }

    private function setProductAttributes($product_id, NormalizedProduct $product) {
        $attrs_to_set = $product->attributes ?? [];

        if (empty($attrs_to_set)) {
            return;
        }

        $wc_product = wc_get_product($product_id);
        $attributes = [];

        foreach ($attrs_to_set as $attr) {
            $attr_name  = isset($attr['name']) ? $attr['name'] : (isset($attr['id']) ? 'Attribute ' . $attr['id'] : 'Attribute');
            $attr_value = $attr['value'];

            $attr_name_key = mb_strtolower($attr_name, 'UTF-8');
            if (isset($this->global_attribute_names[$attr_name_key])) {
                [$canonical_label, $slug] = $this->global_attribute_names[$attr_name_key];
                $wc_attr = $this->getOrCreateGlobalAttribute($canonical_label, $slug);

                if ($wc_attr) {
                    [$attribute_id, $taxonomy] = $wc_attr;

                    if (!taxonomy_exists($taxonomy)) {
                        // Taxonomy not yet registered in this request — re-register
                        register_taxonomy($taxonomy, ['product']);
                    }

                    // Normalize raw supplier strings to canonical terms
                    $values = match($slug) {
                        'yliko' => $this->normalizeMaterials($attr_value),
                        'xroma' => $this->normalizeColors($attr_value),
                        default => [$attr_value],
                    };

                    if (empty($values)) {
                        continue;
                    }

                    $term_ids = [];
                    foreach ($values as $value) {
                        $term = term_exists($value, $taxonomy);
                        if (!$term) {
                            $opts = ($slug === 'xroma') ? ['slug' => $this->getColorCssSlug($value)] : [];
                            $term = wp_insert_term($value, $taxonomy, $opts);
                        }
                        if (!is_wp_error($term)) {
                            $term_ids[] = is_array($term) ? (int) $term['term_id'] : (int) $term;
                        } else {
                            error_log("ProductSync: Failed to insert term '{$value}' into {$taxonomy}: " . $term->get_error_message());
                        }
                    }

                    if (!empty($term_ids)) {
                        wp_set_object_terms($product_id, $term_ids, $taxonomy, false);

                        $attribute = new \WC_Product_Attribute();
                        $attribute->set_id($attribute_id);
                        $attribute->set_name($taxonomy);
                        $attribute->set_options($term_ids);
                        $attribute->set_visible(true);
                        $attribute->set_variation(false);
                        $attributes[] = $attribute;
                    }

                    continue;
                }
                // Fall through to local attribute if global creation failed
            }

            // Local (non-filterable) attribute
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
     * Maps raw supplier material strings to canonical Greek terms.
     * Splits combinations (e.g. "MDF - METAL") into multiple terms.
     */
    private function normalizeMaterials(string $raw): array {
        // Keywords that identify each canonical material (checked on uppercased string)
        $map = [
            'Βελούδο'       => ['VELVET'],
            'MDF'           => ['MDF', 'CLIPBOARD', 'CHIPBOARD', 'MELAMINE', 'ΜΕΛΑΜΙΝ', 'ΜΟΡΙΟΣΑΝΙΔ', 'PAPER WOOD', '3D PAPER', 'PAPER MELAMINE', 'LPL'],
            'Κόντρα πλακέ'  => ['PLYWOOD'],
            'Ξύλο'          => ['SOLID WOOD', 'PINE WOOD', 'RUBBERWOOD', 'BEECHWOOD', 'BEECH WOOD', 'HARDWOOD', 'MANGO', 'FINGER JOINTED', 'ΞΥΛΟ', 'ΑΚΑΚΙΑ', 'ΠΑΥΛΩΝΙΑ'],
            'Μέταλλο'       => ['METAL', 'ΜΕΤΑΛΛΟ', 'STEEL', 'IRON'],
            'Inox'          => ['INOX'],
            'Αλουμίνιο'     => ['ALUMIN'],
            'Μπαμπού'       => ['BAMBOO', 'BAMBOU', 'ΜΠΑΜΠΟΥ'],
            'Ύφασμα'        => ['FABRIC', 'CANVAS', 'ΥΦΑΣΜΑ', 'TEXTILENE', 'TEXTILE', 'ROPE', 'MESH', 'OXFORD'],
            'Δερματίνη'     => ['PU LEATHER', ' PU ', ' PU-', '-PU ', '.PU', 'PU.'],
            'Γυαλί'         => ['GLASS', 'ΓΥΑΛ', 'TEMPERED'],
            'Ρατάν'         => ['RATTAN'],
            'Πολυπροπυλένιο'=> ['HDPE', ' PP ', ' PP-', '-PP '],
            'PVC'           => ['PVC'],
            'Πολυεστέρας'   => ['POLYESTER', '420D', '600D', '100D'],
            'Σφουγγάρι'     => ['FOAM', 'EPS BEADS', ' EPS ', 'SPRING MATTRESS', 'POCKET SPRING', 'MEMORY FOAM', 'LATEX'],
        ];

        $upper = mb_strtoupper(' ' . $raw . ' ', 'UTF-8');
        $found = [];

        foreach ($map as $canonical => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($upper, $kw, 0, 'UTF-8') !== false) {
                    $found[] = $canonical;
                    break;
                }
            }
        }

        // Fallback: keep the raw value trimmed if nothing matched
        return $found ?: [trim($raw)];
    }

    private function normalizeColors(string $raw): array {
        $map = [
            'Μαύρο'      => ['BLACK', 'ΜΑΥΡΟ', ' BACK '],
            'Λευκό'      => ['WHITE', 'ΛΕΥΚΟ', 'IVORY', 'CREAM'],
            'Γκρι'       => ['GREY', 'GRAY', 'ΓΚΡΙ', 'ELEPHANT', 'RUSTIC GREY'],
            'Ανθρακί'    => ['ANTHRACITE', 'ΑΝΘΡΑΚΙ', 'CHARCOAL'],
            'Μπεζ'       => ['BEIGE', 'ECRU'],
            'Καφέ'       => ['BROWN', 'ΚΑΦΕ', 'TABAC', 'MOCHA'],
            'Χρυσό'      => ['GOLD', 'ΧΡΥΣΟ', 'COPPER', 'BRONZE'],
            'Ασημί'      => ['SILVER', 'CHROME', 'ΑΣΗΜΙ', 'INOX', 'PIPE'],
            'Κόκκινο'    => ['RED', 'ROTTEN APPLE', 'CASTILLO-TORO'],
            'Μπλε'       => ['BLUE', 'CIEL'],
            'Πράσινο'    => ['GREEN'],
            'Ροζ'        => ['PINK'],
            'Πορτοκαλί'  => ['ORANGE'],
            'Κίτρινο'    => ['YELLOW'],
            'Μωβ'        => ['PURPLE', 'VIOLET'],
            'Τυρκουάζ'   => ['WATER GREEN', 'TURQUOISE'],
            'Πολύχρωμο'  => ['MULTICOLOR'],
            'Διάφανο'    => ['TRANSPARENT'],
            'Σονόμα'     => ['SONOMA'],
            'Καρυδί'     => ['WALNUT', 'ΚΑΡΥΔΙ'],
            'Βέγκε'      => ['WENGE'],
            'Φυσικό'     => ['NATURAL', 'ΦΥΣΙΚΟ', 'OAK'],
            'Σφενδάμι'   => ['MAPLE'],
            'Μαρμάρινο'  => ['MARBLE'],
            'Τσιμέντο'   => ['CEMENT'],
        ];

        $upper = mb_strtoupper(' ' . $raw . ' ', 'UTF-8');
        $found = [];

        foreach ($map as $canonical => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($upper, $kw, 0, 'UTF-8') !== false) {
                    $found[] = $canonical;
                    break;
                }
            }
        }

        return $found ?: [trim($raw)];
    }

    private function getColorCssSlug(string $canonical): string {
        static $css = [
            'Μαύρο'     => 'black',
            'Λευκό'     => 'white',
            'Γκρι'      => 'grey',
            'Ανθρακί'   => 'anthracite',
            'Μπεζ'      => 'beige',
            'Καφέ'      => 'brown',
            'Χρυσό'     => 'gold',
            'Ασημί'     => 'silver',
            'Κόκκινο'   => 'red',
            'Μπλε'      => 'blue',
            'Πράσινο'   => 'green',
            'Ροζ'       => 'pink',
            'Πορτοκαλί' => 'orange',
            'Κίτρινο'   => 'yellow',
            'Μωβ'       => 'purple',
            'Τυρκουάζ'  => 'teal',
            'Πολύχρωμο' => 'multicolor',
            'Διάφανο'   => 'transparent',
            'Σονόμα'    => 'sonoma',
            'Καρυδί'    => 'walnut',
            'Βέγκε'     => 'wenge',
            'Οκ'        => 'oak',
            'Φυσικό'    => 'natural',
            'Σφενδάμι'  => 'maple',
            'Μαρμάρινο' => 'marble',
            'Τσιμέντο'  => 'cement',
        ];

        return $css[$canonical] ?? sanitize_title($canonical);
    }

    /**
     * Get or create a global WooCommerce attribute taxonomy.
     *
     * @param string $label Human-readable label (e.g. "χρώμα")
     * @param string $slug  Latin taxonomy slug (e.g. "xroma") — used only on creation
     * @return array|null   [attribute_id, taxonomy_name] or null on failure
     */
    private function getOrCreateGlobalAttribute(string $label, string $slug): ?array {
        foreach (wc_get_attribute_taxonomies() as $tax) {
            if ($tax->attribute_label === $label) {
                return [(int) $tax->attribute_id, wc_attribute_taxonomy_name($tax->attribute_name)];
            }
        }

        $result = wc_create_attribute([
            'name'         => $label,
            'slug'         => $slug,
            'type'         => 'select',
            'order_by'     => 'menu_order',
            'has_archives' => false,
        ]);

        if (is_wp_error($result)) {
            error_log("ProductSync: Failed to create global attribute '{$label}': " . $result->get_error_message());
            return null;
        }

        // Re-register taxonomies so the new one is available within this request
        do_action('woocommerce_after_register_taxonomy');

        return [(int) $result, wc_attribute_taxonomy_name($slug)];
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

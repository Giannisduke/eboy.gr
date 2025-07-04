<?php

/**
 * Class XmlImportWooCommerceService
 */
final class XmlImportWooCommerceService {

    /**
     * Singletone instance
     * @var XmlImportWooCommerceService
     */
    protected static $instance;

    /**
     *  Product custom field name to keep information about is product
     *  was created or updated by import.
     *
     */
    const FLAG_IS_NEW_PRODUCT = '__is_newly_created_product';

    /**
     *  Store all originally parsed data in product meta.
     */
    const PARSED_DATA_KEY = '__originally_parsed_data';

    /**
     *  Store ID of variation created from parent row in product meta.
     */
    const FIRST_VARIATION = '__first_variation_id';

    /**
     * @var XmlImportWooTaxonomyService
     */
    public $taxonomiesService;

    /**
     * @var XmlImportWooPriceService
     */
    public $priceService;

    /**
     * @var \PMXI_Image_Record
     */
    public $import;

    /**
     * @var array
     */
    public $product_taxonomies;

    /**
     * Return singletone instance
     * @return XmlImportWooCommerceService
     */
    static public function getInstance() {
        if (self::$instance == NULL) {
            self::$instance = new self();
        }
        self::$instance->setImport();
        return self::$instance;
    }

    /**
     * XmlImportWooCommerceService constructor.
     */
    protected function __construct() {
        try {
            // Init current import instance.
            $this->setImport();
            $this->taxonomiesService = new XmlImportWooTaxonomyService($this->import);
            $this->priceService = new XmlImportWooPriceService($this->import);
            $taxonomies = array('post_format', 'product_type', 'product_shipping_class', 'product_visibility');
            $taxonomies = apply_filters('wp_all_import_variation_taxonomies', $taxonomies);
            $this->product_taxonomies = array_diff_key(get_taxonomies_by_object_type(array('product'), 'object'), array_flip($taxonomies));
        } catch(\Exception $e) {
            self::getLogger() && call_user_func(self::getLogger(), '<b>ERROR:</b> ' . $e->getMessage());
        }
    }

    /**
     * Init import object form request data.
     */
    public function setImport() {
        // Init current import instance.
        $this->import = new PMXI_Import_Record();
        $input = new PMXI_Input();
        $importID = $input->get('id');
        if (empty($importID)) {
            $importID = $input->get('import_id');
        }
        if (empty($importID) && !empty(\PMXI_Plugin::$session)) {
            $importID = \PMXI_Plugin::$session->import_id;
        }
        if (empty($importID) && php_sapi_name() === 'cli') {
            global $argv;
            foreach ($argv as $key => $arg) {
                if ($arg === 'run' && !empty($argv[$key + 1])) {
                    $importID = $argv[$key + 1];
                }
            }
        }
        if ($importID && ($this->import->isEmpty() || $this->import->id != $importID)) {
            $this->import->getById($importID);
        }
    }

    /**
     * @return \XmlImportWooTaxonomyService
     */
    public function getTaxonomiesService() {
        return $this->taxonomiesService;
    }

    /**
     * @return \XmlImportWooPriceService
     */
    public function getPriceService() {
        return $this->priceService;
    }

    /**
     * @return \PMXI_Image_Record
     */
    public function getImport() {
        return $this->import;
    }

    /**
     * @return array
     */
    public function getProductTaxonomies() {
        return $this->product_taxonomies;
    }

    /**
     * @param $productID
     *
     * @return mixed
     */
    public function getAllOriginallyParsedData($productID) {
        $data = get_post_meta($productID, self::PARSED_DATA_KEY, true);
        return $data;
    }

    /**
     * @param $productID
     * @param $key
     *
     * @return mixed
     */
    public function getOriginallyParsedData($productID, $key) {
        $data = $this->getAllOriginallyParsedData($productID);
        return isset($data[$key]) ? $data[$key] : NULL;
    }

    /**
     * Sync parent product prices & attributes with variations.
     *
     * @param $parentID
     */
    public function syncVariableProductData($parentID) {
        $product = new \WC_Product_Variable($parentID);

		do_action('wp_all_import_before_variable_product_import', $product->get_id());

        $variations = array();
        $variationIDs = $product->get_children();
        // Collect product variations.
        foreach ($variationIDs as $key => $variationID) {
            $variations[] = new \WC_Product_Variation($variationID);
        }
        $parentAttributes = get_post_meta($product->get_id(), '_product_attributes', TRUE);
        // Sync attribute terms with parent product.
        if (!empty($parentAttributes)) {
            $variation_attributes = [];
            foreach ($variations as $variation) {
                $attributes = $variation->get_attributes();
                if (!empty($attributes)) {
                    foreach ($attributes as $attribute_name => $attribute_value) {
                        if (!isset($variation_attributes[$attribute_name])) {
                            $variation_attributes[$attribute_name] = [];
                        }
                        if (!in_array($attribute_value, $variation_attributes[$attribute_name])) {
                            $variation_attributes[$attribute_name][] = $attribute_value;
                        }
                    }
                }
            }
            foreach ($parentAttributes as $name => $parentAttribute) {
                // Only in case if attribute marked to import as taxonomy terms.
                if ($parentAttribute['is_taxonomy']) {
                    $taxonomy_name = strpos($name, "%") !== FALSE ? urldecode($name) : $name;
                    $terms = [];
                    if (isset($variation_attributes[$name]) && is_array($variation_attributes[$name])) {
	                    $variation_attributes[$name] = array_filter($variation_attributes[$name]);
                    }
                    if (!empty($variation_attributes[$name])) {
                        foreach ($variation_attributes[$name] as $attribute_term_slug) {
                            $term = get_term_by('slug', $attribute_term_slug, $taxonomy_name);
                            if ($term && !is_wp_error($term)) {
                                $terms[] = $term->term_taxonomy_id;
                            }
                        }
						if (!empty($parentAttribute['value'])) {
							$parent_terms = explode("|", $parentAttribute['value']);
							$parent_terms = array_filter($parent_terms);
							if (!empty($parent_terms)) {
								foreach ($parent_terms as $parent_term) {
									if ( ! in_array($parent_term, $terms) ) {
										$terms[] = $parent_term;
									}
								}
							}
						}
                    } else {
                        $terms = explode("|", $parentAttribute['value']);
                        $terms = array_filter($terms);
                    }
                    if (!empty($terms)) {
                        $this->getTaxonomiesService()->associateTerms($parentID, $terms, $taxonomy_name);
                    }
                }
            }
        }
        $isNewProduct = get_post_meta($product->get_id(), self::FLAG_IS_NEW_PRODUCT, true);
        // Make product simple it has less than minimum number of variations.
        $minimumVariations = apply_filters('wp_all_import_minimum_number_of_variations', 2, $product->get_id(), $this->getImport()->id);
        // Sync parent product with variation if at least one variation exist.
        if (!empty($variations)) {
            /** @var WC_Product_Variable_Data_Store_CPT $data_store */
            if (!$this->getImport()->options['link_all_variations'] && (count($variationIDs) >= $minimumVariations || !$this->getImport()->options['make_simple_product'])) {
                $data_store = WC_Data_Store::load( 'product-' . $product->get_type() );
                $data_store->sync_price( $product );
                $data_store->sync_stock_status( $product );
            }
            // Set product default attributes.
            if ($isNewProduct || $this->isUpdateCustomField('_default_attributes')) {
                $defaultAttributes = [];
                if ($this->getImport()->options['is_default_attributes']) {
                    $defaultVariation = FALSE;
                    // Set first variation as the default selection.
                    if ($this->getImport()->options['default_attributes_type'] == 'first') {
                        $defaultVariation = array_shift($variations);
                    }
                    // Set first in stock variation as the default selection.
                    if ($this->getImport()->options['default_attributes_type'] == 'instock') {
                        /** @var \WC_Product_Variation $variation */
                        foreach ($variations as $variation) {
                            if ($variation->get_stock_status() == 'instock') {
                                $defaultVariation = $variation;
                                break;
                            }
                        }
                    }
                    if ($defaultVariation) {
                        foreach ($defaultVariation->get_attributes() as $key => $value) {
                            if (!empty($value)) {
                                $defaultAttributes[$key] = $value;
                            } else {
                                // Variation can be applied to any value of this attribute.
                                if (isset($parentAttributes[$key])) {
                                    // Get first value from parent product.
                                    if ($parentAttributes[$key]['is_taxonomy']) {
                                        $terms = explode("|", $parentAttributes[$key]['value']);
                                        $terms = array_filter($terms);
                                        if (!empty($terms)) {
                                            $term = WP_Term::get_instance($terms[0]);
                                            if ($term) {
                                                $defaultAttributes[$key] = $term->slug;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $default_attributes = $this->getOriginallyParsedData($product->get_id(), '_default_attributes');
                        if (!empty($default_attributes)) {
                            $defaultAttributes = maybe_unserialize($default_attributes);
                        }
                    }
                }
                $product->set_default_attributes($defaultAttributes);
            }
            $product->save();
        }
        // Sync custom fields for variation created from parent row.
        $firstVariationID = get_post_meta($product->get_id(), self::FIRST_VARIATION, TRUE);
        if ($firstVariationID && in_array($this->getImport()->options['matching_parent'], array('first_is_parent_id', 'first_is_variation')) ) {
            $parentMeta = get_post_meta($product->get_id(), '');
            if ("manual" !== $this->getImport()->options['duplicate_matching']) {
                foreach ($this->getImport()->options['custom_name'] as $customFieldName) {
                    if ($isNewProduct || $this->isUpdateCustomField($customFieldName)) {
                        update_post_meta($firstVariationID, $customFieldName, maybe_unserialize($parentMeta[$customFieldName][0]));
                    }
                }

				// Sync specific fields even if not configured in the import explicitly.
	            $specific_fields = ['_global_unique_id'];

				foreach ($specific_fields as $specific_field) {
					!empty(($parentMeta[$specific_field][0] ?? null)) && update_post_meta($firstVariationID, $specific_field, maybe_unserialize($parentMeta[$specific_field][0]));
				}
            }
            $sync_parent_acf_with_first_variation = apply_filters('wp_all_import_sync_parent_acf_with_first_variation', true);
            if ($sync_parent_acf_with_first_variation) {
	            // Sync all ACF fields.
	            foreach ($parentMeta as $parentMetaKey => $parentMetaValue) {
		            if (strpos($parentMetaValue[0], 'field_') === 0) {
			            update_post_meta($firstVariationID, $parentMetaKey, $parentMetaValue[0]);
			            $acfFieldKey = preg_replace("%^_(.*)%", "$1", $parentMetaKey);
			            foreach ($parentMeta as $key => $value) {
				            if (strpos($key, $acfFieldKey) === 0) {
					            update_post_meta($firstVariationID, $key, $value[0]);
				            }
			            }
		            }
	            }
            }
	        delete_post_meta($firstVariationID, '_variation_updated');
        }

        update_post_meta($product->get_id(), '_product_attributes', $parentAttributes);

        if (count($variationIDs) < $minimumVariations) {
            $this->maybeMakeProductSimple($product, $variationIDs);
        }
        if ($this->isUpdateDataAllowed('is_update_attributes', $isNewProduct)) {
            $this->recountAttributes($product);
        }
        do_action('wp_all_import_variable_product_imported', $product->get_id());
        // Delete originally parsed data, which was temporary stored in
        // product meta.
        delete_post_meta($product->get_id(), self::PARSED_DATA_KEY);
    }

    /**
     * Convert variable product into simple.
     *
     * @param $product \WC_Product_Variable
     * @param $variationIDs
     */
    public function maybeMakeProductSimple($product, $variationIDs) {
        $isNewProduct = get_post_meta($product->get_id(), self::FLAG_IS_NEW_PRODUCT, true);
        if (empty($isNewProduct)) {
	        $isNewProduct = FALSE;
        }
        if ($this->isUpdateDataAllowed('is_update_product_type', $isNewProduct) && $this->getImport()->options['make_simple_product']) {
            do_action('wp_all_import_before_make_product_simple', $product->get_id(), $this->getImport()->id);
            $product_type_term = is_exists_term('simple', 'product_type', 0);
            if (!empty($product_type_term) && !is_wp_error($product_type_term)) {
                $this->getTaxonomiesService()->associateTerms($product->get_id(), array( (int) $product_type_term['term_taxonomy_id'] ), 'product_type');
            }
        }
        // Sync prices after conversion to simple product or if product has less than 2 variations.
        $getPricesFromFirstVariation = apply_filters('wp_all_import_get_prices_from_first_variation', FALSE, $product->get_id(), $this->getImport()->id);
        $parsedData = $this->getAllOriginallyParsedData($product->get_id());
        if ($getPricesFromFirstVariation && !empty($variationIDs)) {
            $firstVariationID = get_post_meta($product->get_id(), self::FIRST_VARIATION, TRUE);
            $parsedData['regular_price'] = get_post_meta($firstVariationID, '_regular_price', TRUE);
            $parsedData['sale_price'] = get_post_meta($firstVariationID, '_sale_price', TRUE);
            $price = get_post_meta($firstVariationID, '_price', TRUE);
        }
        if (!empty($parsedData)) {
            if (empty($variationIDs) && $this->getImport()->options['make_simple_product']) {
                // Sync product data in case variations weren't created for this product.
                $simpleProduct = new \WC_Product_Simple($product->get_id());
                $simpleProduct->set_stock_status($parsedData['stock_status']);
	            if (isset($parsedData['downloadable']) && ($isNewProduct || $this->isUpdateCustomField('_downloadable'))) {
		            $simpleProduct->set_downloadable($parsedData['downloadable']);
	            }
	            if (isset($parsedData['virtual']) && ($isNewProduct || $this->isUpdateCustomField('_virtual'))) {
		            $simpleProduct->set_virtual($parsedData['virtual']);
	            }
                $simpleProduct->save();
            }
            if (empty($price)) {
                $price = isset($parsedData['sale_price']) ? $parsedData['sale_price'] : '';
                if ($price == '' && isset($parsedData['regular_price'])) {
                    $price = $parsedData['regular_price'];
                }
            }
            if (isset($parsedData['regular_price'])) {
                XmlImportWooCommerceService::getInstance()->pushMeta($product->get_id(), '_regular_price', $parsedData['regular_price'], $isNewProduct);
            }
            if (isset($parsedData['sale_price'])) {
                XmlImportWooCommerceService::getInstance()->pushMeta($product->get_id(), '_sale_price', $parsedData['sale_price'], $isNewProduct);
            }
            XmlImportWooCommerceService::getInstance()->pushMeta($product->get_id(), '_price', $price, $isNewProduct);
            // Recover original SKU.
            if (isset($parsedData['original_sku']) && $this->getImport()->options['make_simple_product']) {
                XmlImportWooCommerceService::getInstance()->pushMeta($product->get_id(), '_sku', $parsedData['original_sku'], $isNewProduct);
            }
        }
        if ($this->isUpdateDataAllowed('is_update_product_type', $isNewProduct) && $this->getImport()->options['make_simple_product']) {
            try {
                $stock_quantity = get_post_meta($product->get_id(), '_stock', TRUE);
                $data_store = WC_Data_Store::load( 'product' );
                $data_store->update_product_stock( $product->get_id(), $stock_quantity, 'set' );
            } catch(\Exception $e) {
                self::getLogger() && call_user_func(self::getLogger(), '<b>ERROR:</b> ' . $e->getMessage());
            }
            // Delete all variations.
            $children = get_posts(array(
                'post_parent' => $product->get_id(),
                'posts_per_page' => -1,
                'post_type' => 'product_variation',
                'fields' => 'ids',
                'post_status' => 'any'
            ));
            if (!empty($children)) {
                foreach ($children as $child) {
                    wp_delete_post($child, TRUE);
                }
            }
            do_action('wp_all_import_make_product_simple', $product->get_id(), $this->getImport()->id);
        }
    }

    /**
     * Get All orders IDs for a given product ID.
     *
     * @param  integer  $product_id (required)
     * @return array
     */
    public function getOrdersIdsByProductId( $product_id ){
        global $wpdb;
        $results = $wpdb->get_col("
            SELECT order_items.order_id
            FROM {$wpdb->prefix}woocommerce_order_items as order_items
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
            LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
            WHERE posts.post_type = 'shop_order'            
            AND order_items.order_item_type = 'line_item'
            AND order_item_meta.meta_key = '_product_id'
            AND order_item_meta.meta_value = '$product_id'
        ");
        return $results;
    }

    /**
     * Re-count product attributes.
     *
     * @param WC_Product $product
     */
    public function recountAttributes(\WC_Product $product) {
        $attributes = $product->get_attributes();
        /** @var \WC_Product_Attribute $attribute */
        foreach ($attributes as $attributeName => $attribute) {
            if ( ! empty( $attribute ) ) {
                if ($attribute->is_taxonomy()) {
                    $attribute_values = $attribute->get_terms();
                    if (!empty($attribute_values)) {
	                    $terms = [];
	                    foreach ($attribute_values as $key => $object) {
	                        $terms[] = $object->term_id;
	                    }
	                    wp_update_term_count_now($terms, $attributeName);
                    }
                }
            }
        }
    }

    /**
     * @param string $option
     * @param bool $isNewProduct
     * @return bool
     */
    public function isUpdateDataAllowed($option = '', $isNewProduct = TRUE) {
        // Allow update data for newly created products.
        if ($isNewProduct) {
            return TRUE;
        }
        // `Update existing posts with changed data in your file` option disabled.
        if ($this->getImport()->options['is_keep_former_posts'] == 'yes') {
            return FALSE;
        }
        // `Update all data` option enabled
        if ($this->getImport()->options['update_all_data'] == 'yes') {
            return TRUE;
        }
        if (in_array($option, array('is_update_catalog_visibility', 'is_update_featured_status')) && empty($this->getImport()->options['is_update_advanced_options'])) {
            return FALSE;
        }

        return empty($this->getImport()->options[$option]) ? FALSE : TRUE;
    }

    /**
     * @param $tx_name
     * @param bool $isNewProduct
     * @return bool
     */
    public function isUpdateTaxonomy($tx_name, $isNewProduct = TRUE) {

        if (!$isNewProduct) {
            if ($this->getImport()->options['update_all_data'] == 'yes'){
                return TRUE;
            }
            if ( ! $this->getImport()->options['is_update_categories'] ) {
                return FALSE;
            }
            if ($this->getImport()->options['update_all_data'] == "no" && $this->getImport()->options['update_categories_logic'] == "all_except" && !empty($this->getImport()->options['taxonomies_list'])
                && is_array($this->getImport()->options['taxonomies_list']) && in_array($tx_name, $this->getImport()->options['taxonomies_list'])) {
                return FALSE;
            }
            if ($this->getImport()->options['update_all_data'] == "no" && $this->getImport()->options['update_categories_logic'] == "only" && ((!empty($this->getImport()->options['taxonomies_list'])
				&& is_array($this->getImport()->options['taxonomies_list']) && ! in_array($tx_name, $this->getImport()->options['taxonomies_list'])) || empty($this->getImport()->options['taxonomies_list']))) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * @param $attributeName
     * @param bool $isNewProduct
     *
     * @return bool
     */
    public function isUpdateAttribute($attributeName, $isNewProduct = TRUE) {
        $is_update_attributes = TRUE;
        // Update only these Attributes, leave the rest alone.
        if ( ! $isNewProduct && $this->getImport()->options['update_all_data'] == "no" && $this->getImport()->options['is_update_attributes'] && $this->getImport()->options['update_attributes_logic'] == 'only') {
            if ( ! empty($this->getImport()->options['attributes_list']) && is_array($this->getImport()->options['attributes_list'])) {
                if ( ! in_array( $attributeName , array_filter($this->getImport()->options['attributes_list'], 'trim'))) {
                    $is_update_attributes = FALSE;
                }
            }
        }
        // Leave these attributes alone, update all other Attributes.
        if ( ! $isNewProduct && $this->getImport()->options['update_all_data'] == "no" && $this->getImport()->options['is_update_attributes'] && $this->getImport()->options['update_attributes_logic'] == 'all_except') {
            if ( ! empty($this->getImport()->options['attributes_list']) && is_array($this->getImport()->options['attributes_list'])) {
                if ( in_array( $attributeName , array_filter($this->getImport()->options['attributes_list'], 'trim'))) {
                    $is_update_attributes = FALSE;
                }
            }
        }
        return $is_update_attributes;
    }

    /**
     * @param $meta_key
     * @return bool
     */
    public function isUpdateCustomField($meta_key) {
        $options = $this->getImport()->options;
        if ($options['update_all_data'] == 'yes') {
            return TRUE;
        }
        if (!$options['is_update_custom_fields']) {
            return FALSE;
        }
        if ($options['update_custom_fields_logic'] == "full_update") {
            return TRUE;
        }
        if ($options['update_custom_fields_logic'] == "only"
            && !empty($options['custom_fields_list'])
            && is_array($options['custom_fields_list'])
            && in_array($meta_key, $options['custom_fields_list'])
        ) {
            return TRUE;
        }
        if ($options['update_custom_fields_logic'] == "all_except"
            && (empty($options['custom_fields_list']) || !in_array($meta_key, $options['custom_fields_list']))
        ) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param $pid
     * @param $meta_key
     * @param $meta_value
     * @param bool $isNewPost
     * @return mixed
     */
    public function pushMeta($pid, $meta_key, $meta_value, $isNewPost = TRUE) {
        if (!empty($meta_key) && ($isNewPost || $this->isUpdateCustomField($meta_key))) {
            update_post_meta($pid, $meta_key, $meta_value);
        } elseif (in_array($meta_key, ['_product_image_gallery']) && $this->isUpdateDataAllowed('is_update_images', $isNewPost)) {
	        // Update gallery custom field if images is set to be updated.
	        update_post_meta($pid, $meta_key, $meta_value);
        }
    }

    /**
     * @param $input
     * @return array
     */
    public static function arrayCartesian($input) {
        $result = array();
        foreach ($input as $key => $values) {
	        // If a sub-array is empty, it doesn't affect the cartesian product
	        if ( empty( $values ) ) {
		        continue;
	        }
	        // Special case: seeding the product array with the values from the first sub-array
	        if ( empty( $result ) ) {
		        foreach ( $values as $value ) {
			        $result[] = array( $key => $value );
		        }
	        } else {
		        // Second and subsequent input sub-arrays work like this:
		        //   1. In each existing array inside $product, add an item with
		        //      key == $key and value == first item in input sub-array
		        //   2. Then, for each remaining item in current input sub-array,
		        //      add a copy of each existing array inside $product with
		        //      key == $key and value == first item in current input sub-array

		        // Store all items to be added to $product here; adding them on the spot
		        // inside the foreach will result in an infinite loop
		        $append = array();
		        foreach( $result as &$product ) {
			        // Do step 1 above. array_shift is not the most efficient, but it
			        // allows us to iterate over the rest of the items with a simple
			        // foreach, making the code short and familiar.
			        $product[ $key ] = array_shift( $values );
			        // $product is by reference (that's why the key we added above
			        // will appear in the end result), so make a copy of it here
			        $copy = $product;
			        // Do step 2 above.
			        foreach( $values as $item ) {
				        $copy[ $key ] = $item;
				        $append[] = $copy;
			        }
			        // Undo the side effecst of array_shift
			        array_unshift( $values, $product[ $key ] );
		        }
		        // Out of the foreach, we can add to $results now
		        $result = array_merge( $result, $append );
	        }
        }
        return $result;
    }

    /**
     * @return bool|\Closure
     */
    public static function getLogger() {
        $logger = FALSE;
        if (PMXI_Plugin::is_ajax()) {
            $logger = function($m) {echo "<div class='progress-msg'>[". date("H:i:s") ."] ".wp_all_import_filter_html_kses($m)."</div>\n";flush();};
        }
        return $logger;
    }
}

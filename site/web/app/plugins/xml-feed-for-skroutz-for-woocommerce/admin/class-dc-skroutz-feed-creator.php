<?php

/**
 * The class that generates the XML feed.
 */
class Dicha_Skroutz_Feed_Creator {

	/**
	 * The plugin options (user settings).
	 * @var array $options
	 */
	private array $options;

	/**
	 * The feed type.
	 * @var string $feed_type
	 */
	private string $feed_type;

	/**
	 * A helper class to get product data.
	 * @var Dicha_Skroutz_Feed_Data_Helper $data_helper
	 */
	private Dicha_Skroutz_Feed_Data_Helper $data_helper;

	/**
	 * An array with mapping between sanitized and unsanitized attribute slugs.
	 * @var array $wc_product_attributes_sanitized
	 */
	private array $wc_product_attributes_sanitized = [];

	/**
	 * The array with product data that will be exported to XML.
	 * @var array
	 */
	private array $products_for_export = [];

	/**
	 * The array with problematic products that will be skipped from XML.
	 * @var array
	 */
	private array $products_with_errors = [];

	/**
	 * The array with errors that occurred during the XML file creation process.
	 * @var string[]
	 */
	private array $xml_creation_errors = [];

	/**
	 * Script execution start time.
	 *
	 * @var float
	 */
	private float $start_full_time = 0.0;

	/**
	 * XML generation completed time.
	 *
	 * @var float
	 */
	private float $xml_generation_time = 0.0;

	/**
	 * The max memory limit of the server.
	 *
	 * @var string The memory limit in bytes.
	 */
	private string $memory_limit;

	/**
	 * The fields to wrap in CDATA when writing the XML.
	 *
	 * @var array
	 */
	private array $fields_in_cdata = [];

	/**
	 * If a language switch is active.
	 *
	 * @var bool
	 */
	private bool $wpml_do_lang_switch = false;

	/**
	 * The original language of the site.
	 *
	 * @var string
	 */
	private string $original_site_lang = '';


	/**
	 * Constructor.
	 *
	 * @param string $feed_type
	 */
	public function __construct( string $feed_type = '' ) {

		$this->feed_type = empty( $feed_type ) ? 'skroutz' : trim( $feed_type );
	}


	/**
	 * The main function that generates the XML feed.
	 *
	 * @return bool True if XML creation was successful. False otherwise.
	 */
	public function create_feed(): bool {

		$this->start_full_time = microtime( true );

		// echo '<pre>';

		$this->init_options();
		$this->init_data_helper();
		$this->setup_wc_attributes_list();
		$this->find_max_memory_limit();

		// echo '<pre>'; print_r( $this->options ); echo '</pre>';

		// Add compatibility with RightPress - Pricepep - WooCommerce Dynamic Pricing, Discounts & Fees
		add_filter( 'rp_wcdpd_request_is_product_feed', '__return_true' );

		// Build product data for export
		$this->build_product_export_data();

		// Add compatibility with RightPress - Pricepep - WooCommerce Dynamic Pricing, Discounts & Fees
		remove_filter( 'rp_wcdpd_request_is_product_feed', '__return_true' );

		// Create XML and save
		$this->saveXML();
		$this->xml_generation_time = microtime( true );

		// write log data
		$this->write_logs();

		return empty( $this->xml_creation_errors );
	}


	/**
	 * Gets all products for export and fills an array with their export data.
	 *
	 * @return void
	 */
	private function build_product_export_data(): void {

		$skroutz_products = $this->collect_products_for_export();
		// var_dump( $skroutz_products );

		$product_counter = 0;

		// todo move to separate class and add hooks for modifications
		if ( class_exists( 'SitePress', false ) ) {
			global $sitepress;

			$switch_language_enabled = apply_filters( 'dicha_skroutz_feed_switch_language_enabled', true );

			if ( $switch_language_enabled && isset( $sitepress ) ) {

				$this->original_site_lang = $sitepress->get_default_language();

				if ( $this->original_site_lang != 'el' ) {
					$sitepress->switch_lang( 'el', false );
					$this->wpml_do_lang_switch = true;
				}
			}
		}

		foreach ( $skroutz_products as $product_id ) {

			if ( $this->wpml_do_lang_switch ) {
				$original_lang_product_id = $product_id;
				$product_id = apply_filters( 'wpml_object_id', $original_lang_product_id, 'product', true, 'el' );
			}

			$product_counter ++;

			if ( $product_counter >= 20 ) {
				$product_counter = 0;

				$this->maybe_flush_runtime_cache();
			}

			// if ( $product_id != 1186 ) continue; // Skroutz variable with Size + Color
			// if ( $product_id != 1170 ) continue; // Skroutz Simple
			// if ( $product_id != 1170 && $product_id != 1186 ) continue;
			// if ( $product_id != 1175 ) continue; // Skroutz Variable with Size
			// if ( $product_id != 1180 ) continue; // Skroutz Variable with Dimension
			// if ( $product_id != 1230 ) continue; // Skroutz Variable with "Any" option
			// if ( $product_id != 1264 && $product_id != 1268 ) continue; // Skroutz products with greek slug
			// if ( $product_id != 1220 && $product_id != 1287 ) continue; // Skroutz variable with 2 attrs (no size var) + Skroutz Variable with Color (only) + Size attr (no var)

			$product = wc_get_product( $product_id );

			if ( ! $product instanceof WC_Product ) continue;

			// Skip products from manual filter
			if ( $this->data_helper->skroutz_exclude_product_from_xml( $product ) ) continue;

			$product_type = $product->get_type();

			if ( 'simple' === $product_type ) {
				/** @var WC_Product_Simple $product */

				$unique_id = $product->get_id();

				if ( $this->wpml_do_lang_switch ) {
					// maybe add with filter
					$unique_id = $original_lang_product_id;
				}

				// gather simple product data
				$node_data = array_merge(
					[ 'id' => apply_filters( 'dicha_skroutz_feed_custom_product_id', $unique_id, $product, $this->feed_type ) ],
					$this->get_simple_product_data( $product )
				);

				$data_contain_no_errors = $this->detect_data_errors( $node_data );

				$node_data = apply_filters( 'dicha_skroutz_feed_custom_node_data', $node_data, $product, [], $this->feed_type );

				if ( $data_contain_no_errors ) {
					$this->products_for_export[ $unique_id ] = $node_data;
				}
			}
			elseif ( 'variable' === $product_type ) {
				/** @var WC_Product_Variable $product */

				$variations_groups = [];

				// 1. find variation atts and available variations
				$cat_supports_vars_nodes   = empty( array_intersect( $product->get_category_ids(), $this->options['cats_with_no_vars_support'] ) );
				$size_var_atts_for_product = $cat_supports_vars_nodes ? $this->options['size'] : [];
				$size_var_atts_for_product = (array) apply_filters( 'dicha_skroutz_feed_size_vars_atts_for_product', $size_var_atts_for_product, $product, $this->options['size'], $this->feed_type );
				$available_variations      = $product->get_available_variations( 'objects' );
				$variation_attributes      = $product->get_variation_attributes();
				$has_size_variations       = $this->has_size_options( $variation_attributes, $size_var_atts_for_product );
				$has_non_size_variations   = $this->has_non_size_options( $variation_attributes, $size_var_atts_for_product );
				$has_color_variations      = $this->has_color_options( $variation_attributes );

				// var_dump( $variation_attributes );
				// var_dump( $available_variations );
				// var_dump($has_size_variations);
				// var_dump($has_non_size_variations);
				// var_dump($has_color_variations);

				$parent_id   = $product->get_id();
				$parent_name = $product->get_name();

				// Calculate parent level data once for all variation groups
				$parent_level_data = $this->get_variable_parent_level_data( $product );

				// if color not used for variations, calculate it now and NOT overwrite later
				if ( ! $has_color_variations ) {
					$parent_level_data['color'] = $this->data_helper->skroutz_get_color( $product );
				}

				// if size not used for variations, calculate it now and NOT overwrite later
				if ( ! $has_size_variations ) {
					$parent_level_data['size'] = $this->data_helper->skroutz_get_size( $product );
				}

				/** @var WC_Product_Variation[] $available_variations */

				// split variations to groups, based on variation attributes
				foreach ( $available_variations as $variation ) {

					// $variation_attributes = $variation->get_attributes();
					$variation_attributes = $variation->get_variation_attributes( false );
					$unique_key           = $parent_id;
					$group_name           = $parent_name;

					if ( $this->wpml_do_lang_switch ) {
						// maybe add with filter
						$unique_key = $original_lang_product_id;
					}

					// var_dump( $variation->get_id() );
					// var_dump( $variation_attributes );


					// Detect if a variation with "Any" size or "Any" color exists ("Any" === empty string)
					// If "Any" attribute is color or size, we must skip the variation because of skroutz requirements
					// If "Any" attribute is something else, it doesn't bother us, and the script will continue as usual
					foreach ( $variation_attributes as $attribute_slug => $attribute_value ) {
						if ( empty( $attribute_value ) ) {

							// in this point, the $attribute_slug is already sanitized,
							// so we need to fetch the original (unsanitized) slug with non english chars,
							// to check inside $this->options which contains the unsanitized slugs
							// Checked for greek slugs
							if ( isset( $this->wc_product_attributes_sanitized[ $attribute_slug ] ) ) {
								$attribute_slug = $this->wc_product_attributes_sanitized[ $attribute_slug ];
							}

							if ( in_array( $attribute_slug, $size_var_atts_for_product ) || in_array( $attribute_slug, $this->options['color'] ) ) {
								continue 2;
							}
						}
					}

					// if exist variations with attributes that are not "size", then split to "variations groups"
					if ( $has_non_size_variations ) {

						$unique_key_parts = [ $unique_key ];
						$group_name_parts = [ $group_name ];

						foreach ( $variation_attributes as $attribute_slug => $attribute_value ) {

							// in this point, the $attribute_slug is already sanitized,
							// so we need to fetch the original (unsanitized) slug with non english chars,
							// in order to use the functions `taxonomy_exists` and `get_term_by`
							// Checked for greek slugs
							if ( isset( $this->wc_product_attributes_sanitized[ $attribute_slug ] ) ) {
								$attribute_slug = $this->wc_product_attributes_sanitized[ $attribute_slug ];
							}

							// if "size" attribute or attribute with "Any" value, then no grouping happens
							if ( in_array( $attribute_slug, $size_var_atts_for_product ) || empty( $attribute_value ) ) continue;

							$attribute_term = taxonomy_exists( $attribute_slug ) ? get_term_by( 'slug', $attribute_value, $attribute_slug ) : false;
							$attribute_term = ! is_wp_error( $attribute_term ) && $attribute_term ? $attribute_term : false;

							// use term_id if taxonomy exists - use value if custom (no taxonomy) attribute
							// maybe hash $attribute_value for non taxonomies?
							$term_id = ! is_wp_error( $attribute_term ) && $attribute_term ? $attribute_term->term_id : $attribute_value;

							if ( $this->wpml_do_lang_switch ) {
								// maybe add with filter
								$term_id = apply_filters( 'wpml_object_id', $term_id, $attribute_slug, true, 'el' );
							}

							// Use attribute term_id to create a unique id for the variation group
							$unique_key_parts[] = $term_id;

							$term_name          = ! is_wp_error( $attribute_term ) && $attribute_term ? $attribute_term->name : $attribute_value;
							$group_name_parts[] = $term_name;
						}

						$unique_key = implode( '-', $unique_key_parts );
						$group_name = implode( ' ', $group_name_parts );
					}

					// add variation to the correct variation group
					if ( ! isset( $variations_groups[ $unique_key ] ) ) {
						$variations_groups[ $unique_key ] = [
							'unique_id'        => $unique_key,
							'group_name'       => $this->data_helper->skroutz_get_name( $product, $group_name, $variation ),
							'group_variations' => [ $variation ]
						];
					}
					else {
						$variations_groups[ $unique_key ]['group_variations'][] = $variation;
					}
				}

				// in case of all variations are "Any" size or "Any" color variations -> Skip product but add to array for logging purposes
				if ( empty( $variations_groups ) ) {
					$this->products_with_errors[ $parent_id ] = [
						'id'     => $parent_id,
						'name'   => $parent_name,
						'errors' => [
							'variations' => new WP_Error( '80-1', 'Product skipped due to "Any" variations' )
						]
					];
				}
				else {
					// a list of used skus, to prevent "duplicate sku" errors when variations have no own sku, only on parent level
					$groups_skus_list = [];
					$groups_count     = count( $variations_groups );

					foreach ( $variations_groups as $variations_group ) {

						// gather variable (parent) product data
						$node_data = array_merge(
							[
								'id'   => apply_filters( 'dicha_skroutz_feed_custom_product_id', $variations_group['unique_id'], $product, $this->feed_type ), // if no size variation grouping, this will be replaced later with variation ID
								'name' => $variations_group['group_name']
							],
							$parent_level_data,
							$this->get_variations_group_data( $product, $variations_group['group_variations'], $has_size_variations, $groups_skus_list, $groups_count ),
						);

						// check if errors exist
						$data_contain_no_errors = $this->detect_data_errors( $node_data );

						$node_data = apply_filters( 'dicha_skroutz_feed_custom_node_data', $node_data, $product, $variations_group, $this->feed_type );

						if ( $data_contain_no_errors ) {
							$this->products_for_export[ $variations_group['unique_id'] ] = $node_data;
						}
					}
				}
			}
		}

		// Clear runtime cache in the end to free resources
		$this->flush_runtime_cache();
	}


	/**
	 * Wrapper function for XML generation when triggered manually from the button inside Tools section.
	 * Redirects automatically to XML settings page after XML generation is completed.
	 *
	 * @return void
	 */
	public function create_feed_manual_mode() {

		$feed_generation_result = $this->create_feed();
		$result_param_value     = $feed_generation_result ? 1 : 0;

		// enable this after testing
		wp_redirect( admin_url( 'admin.php?page=' . DICHA_SKROUTZ_FEED_SLUG . '&feed_success=' . $result_param_value ) );
		exit;
	}


	/**
	 * Initializes options.
	 *
	 * @return void
	 */
	private function init_options(): void {

		$options = [
			'manufacturer'              => $this->prefix_attributes( get_option( 'dicha_skroutz_feed_manufacturer', [] ) ),
			'color'                     => $this->prefix_attributes( get_option( 'dicha_skroutz_feed_color', [] ) ),
			'size'                      => $this->prefix_attributes( get_option( 'dicha_skroutz_feed_size', [] ) ),
			'title_attributes'          => $this->prefix_attributes( get_option( 'dicha_skroutz_feed_title_attributes', [] ) ),
			'xml_availability'          => get_option( 'dicha_skroutz_feed_availability' ),
			'include_backorders'        => get_option( 'dicha_skroutz_feed_include_backorders' ),
			'description'               => get_option( 'dicha_skroutz_feed_description', 'short' ),
			'global_markup'             => get_option( 'dicha_skroutz_feed_global_markup', '' ),
			'flat_rate'                 => get_option( 'dicha_skroutz_feed_shipping_cost' ),
			'flat_rate_free'            => get_option( 'dicha_skroutz_feed_free_shipping' ),
			'selected_cats'             => get_option( 'dicha_skroutz_feed_filter_categories', [] ),
			'selected_tags'             => get_option( 'dicha_skroutz_feed_filter_tags', [] ),
			'cats_incl_mode'            => get_option( 'dicha_skroutz_incl_excl_mode_categories' ),
			'tags_incl_mode'            => get_option( 'dicha_skroutz_incl_excl_mode_tags' ),
			'cats_with_no_vars_support' => (array) apply_filters( 'dicha_skroutz_feed_cats_with_no_variations_support', [], $this->feed_type ),
		];

		$this->options = apply_filters( 'dicha_skroutz_feed_custom_options', $options, $this->feed_type );
	}


	/**
	 * Initializes data helper class.
	 *
	 * @return void
	 */
	private function init_data_helper(): void {

		require_once( 'class-dc-skroutz-feed-data-helper.php' );

		$this->data_helper = new Dicha_Skroutz_Feed_Data_Helper( $this->options, $this->feed_type );
	}


	/**
	 * Fetches WooCommerce products for export, depending on options and filters.
	 *
	 * @return array Array of WooCommerce products.
	 */
	private function collect_products_for_export(): array {

		$query_args = [
			'return'       => 'ids',
			'limit'        => -1,
			'type'         => [ 'simple', 'variable' ],
			'visibility'   => 'catalog',
			'status'       => 'publish',
			'virtual'      => false,
			'downloadable' => false,
			'stock_status' => wc_string_to_bool( $this->options['include_backorders'] ) ? [ 'instock', 'onbackorder' ] : 'instock',
		];

		// include/exclude products by category
		if ( ! empty( $selected_product_cat_terms = $this->options['selected_cats'] ) ) {

			if ( wc_string_to_bool( $this->options['cats_incl_mode'] ) ) {
				$query_args['product_category_id'] = $selected_product_cat_terms;
			}
			else {
				$query_args['dicha_exclude_product_category_id'] = $selected_product_cat_terms;
			}
		}

		// include/exclude products by tag
		if ( ! empty( $selected_product_tag_terms = $this->options['selected_tags'] ) ) {

			if ( wc_string_to_bool( $this->options['tags_incl_mode'] ) ) {
				$query_args['product_tag_id'] = $selected_product_tag_terms;
			}
			else {
				$query_args['dicha_exclude_product_tag_id'] = $selected_product_tag_terms;
			}
		}

		$query_args = apply_filters( 'dicha_skroutz_feed_product_query_args', $query_args, $this->options, $this->feed_type );

		return wc_get_products( $query_args );
	}


	/**
	 * Builds node data for simple products.
	 *
	 * @param $product WC_Product_Simple
	 *
	 * @return array
	 */
	private function get_simple_product_data( WC_Product_Simple $product ): array {
		return [
			'name'              => $this->data_helper->skroutz_get_name( $product ),
			'link'              => $this->data_helper->skroutz_get_url( $product ),
			'image'             => $this->data_helper->skroutz_get_main_image_url( $product ),
			'additional_imageurl' => $this->data_helper->skroutz_get_additional_images( $product ),
			'category'          => $this->data_helper->skroutz_get_category( $product ),
			'price_with_vat'    => $this->data_helper->skroutz_get_price( $product ),
			'vat'               => $this->data_helper->skroutz_get_vat( $product ),
			'availability'      => $this->data_helper->skroutz_get_availability( $product ),
			'manufacturer'      => $this->data_helper->skroutz_get_manufacturer( $product ),
			'mpn'               => $this->data_helper->skroutz_get_mpn( $product ),
			'ean'               => $this->data_helper->skroutz_get_ean( $product ),
			'size'              => $this->data_helper->skroutz_get_size( $product ),
			'weight'            => $this->data_helper->skroutz_get_weight( $product ),
			'shipping_costs'    => $this->data_helper->skroutz_get_shipping( $product ),
			'color'             => $this->data_helper->skroutz_get_color( $product ),
			'description'       => $this->data_helper->skroutz_get_description( $product ),
			'quantity'          => $this->data_helper->skroutz_get_quantity( $product ),
		];
	}


	/**
	 * Builds node data for variable products (Only parent-related data).
	 *
	 * @param $parent_product WC_Product_Variable
	 *
	 * @return array
	 */
	private function get_variable_parent_level_data( WC_Product_Variable $parent_product ): array {
		return [
			'link'           => $this->data_helper->skroutz_get_url( $parent_product ),
			'category'       => $this->data_helper->skroutz_get_category( $parent_product ),
			'price_with_vat' => $this->data_helper->skroutz_get_price( $parent_product ),
			'vat'            => $this->data_helper->skroutz_get_vat( $parent_product ),
			'availability'   => $this->data_helper->skroutz_get_availability( $parent_product ),
			'manufacturer'   => $this->data_helper->skroutz_get_manufacturer( $parent_product ),
			'mpn'            => $this->data_helper->skroutz_get_mpn( $parent_product ), // todo check if same mpn in diff groups cause validation error (ID:1186)
			'ean'            => $this->data_helper->skroutz_get_ean( $parent_product ),
			'shipping_costs' => $this->data_helper->skroutz_get_shipping( $parent_product ),
			'description'    => $this->data_helper->skroutz_get_description( $parent_product ),
		];
	}


	/**
	 * Builds node data for variable products (Variation groups data).
	 *
	 * @param WC_Product_Variable    $parent_product      The parent product
	 * @param WC_Product_Variation[] $group_variations    An array with this group's variations
	 * @param bool                   $has_size_variations True if parent product has "size" variation attributes
	 * @param array                  $groups_skus_list    A list with unique group SKUs
	 * @param int                    $groups_count        The total number of variation groups
	 *
	 * @return array
	 */
	private function get_variations_group_data( WC_Product_Variable $parent_product, array $group_variations, bool $has_size_variations, array &$groups_skus_list, int $groups_count ): array {

		// Protect against product data error - All groups with no size vars, should have exactly 1 group variation
		// Usually with variations not showing in product page because their variations attributes were removed
		if ( ! $has_size_variations && count( $group_variations ) > 1 ) {
			$variable_group_data['variations'] = new WP_Error( '80-3', 'Product variation data has critical errors. Check your product.' );
			return $variable_group_data;
		}

		$group_color         = $group_image = $group_link = $group_sku = '';
		$variable_group_data = $group_sizes = $group_additional_images = $variation_nodes = [];

		// Get parent stock if manage stock happens on parent level
		// if this happens, stock status field (instock/outofstock/backorder) disappears from variations tabs
		// To set stock for a single variation, you should enable manage stock and add stock quantity
		$parent_manages_stock = $parent_product->managing_stock();
		$parent_stock         = $parent_manages_stock ? max( $parent_product->get_stock_quantity(), 0 ) : false;
		$group_quantity       = $parent_stock !== false ? $parent_stock : 0;

		// Get parent weight - If it's empty, try to get weight if exists on any variation
		$group_weight = $this->data_helper->skroutz_get_weight( $parent_product );

		// parent main product image
		$parent_main_image = $this->data_helper->skroutz_get_main_image_url( $parent_product );

		// if parent product has only one group (usually just size variations, without non-size attrs), then keep parent mpn for whole group
		// if multiple groups exist, leave empty so that later gets filled with a variation's sku (avoid duplicate sku in different groups)
		if ( $groups_count === 1 ) {
			$group_sku = $this->data_helper->skroutz_get_mpn( $parent_product );
		}

		// if variations have "skroutz price" (triggers parent price recalculation in the end)
		$variations_have_skroutz_price = false;

		foreach ( $group_variations as $variation ) {

			// Skip variation from manual filter
			$exclude_variation = $this->data_helper->skroutz_exclude_variation_from_xml( $variation, $parent_product );

			if ( $exclude_variation ) {

				$exclude_error_data = new WP_Error( '10-4', 'Η παραλλαγή έχει εξαιρεθεί λόγω του φίλτρου `dicha_skroutz_feed_exclude_variation_from_xml`' );

				if ( ! $has_size_variations ) {
					$variable_group_data['exclude_xml'] = $exclude_error_data;
				}
				else {
					$variation_nodes[] = [
						'variationid' => $variation->get_id(),
						'exclude_xml' => $exclude_error_data
					];
				}

				// continue foreach loop to next group variation
				continue;
			}


			// get variation manage stock - Returns true/false or 'parent' if managing stock happens on parent level
			$variation_manages_stock = $variation->get_manage_stock();

			// if variation not managing stock, but parent does, then variation quantity equals parent quantity
			// in this rare edge case, the total parent stock will not match with the variations' stock sum, but it is  more correct in this way
			if ( $parent_manages_stock && 'parent' === $variation_manages_stock ) {
				$variation_quantity = $parent_stock;
			}
			else {
				$variation_quantity = $this->data_helper->skroutz_get_quantity( $variation );

				// Create an error if variation is out of stock
				if ( ! is_wp_error( $variation_quantity ) && $variation_quantity == 0 ) {
					$variation_quantity = new WP_Error( '10-1', 'Η κατάσταση αποθέματος της παραλλαγής είναι εξαντλημένη' );
				}

				// If error exists, add it to the <quantity> node in order to skip product from XML and continue to next variation
				// Skip if variation is out of stock
				// Skip if variation is on backorder and backorders are not allowed based on settings
				if ( is_wp_error( $variation_quantity ) ) {

					if ( ! $has_size_variations ) {
						$group_quantity = $variation_quantity;
					}
					else {
						$variation_nodes[] = [
							'variationid' => $variation->get_id(),
							'quantity'    => $variation_quantity
						];
					}

					// continue foreach loop to next group variation
					continue;
				}
				else {
					$group_quantity += $variation_quantity;
				}
			}


			if ( empty( $group_sku ) ) {
				// variation sku (not inheriting parent's)
				$group_sku = $this->data_helper->skroutz_get_mpn( $variation, 'not_inherit_from_parent' );
			}

			if ( empty( $group_link ) ) {
				$group_link = $this->data_helper->skroutz_get_url( $variation, $has_size_variations );
			}

			// Get variation weight if not set on parent level
			// (tip: skroutz supports weight on parent level only, not variation level)
			if ( empty( $group_weight ) ) {
				$variation_weight = $this->data_helper->skroutz_get_weight( $variation );
				$group_weight     = $variation_weight;
			}

			// calculate variation size and add it to group sizes
			$variation_size = $this->data_helper->skroutz_get_size( $variation );

			if ( ! empty( $variation_size ) ) {
				$group_sizes[]  = $variation_size;
			}

			// calculate variation color and set group color
			if ( empty( $group_color ) ) {
				$group_color = $this->data_helper->skroutz_get_color( $variation );
			}

			// get variation image, if not exists returns parent main image
			$variation_image = $this->data_helper->skroutz_get_main_image_url( $variation );

			// If variation image is different from main product image, then keep the more specific variation image
			if ( empty( $group_image ) || $variation_image !== $parent_main_image ) {
				$group_image = $variation_image;
			}

			/*
			 * If variations have extra gallery images in some custom field, you can use this filter
			 * to add these images in this array.
			 * If custom images are found, they are shown in the <additional_imageurl> nodes.
			 * If no custom images found, then the parent's (variable) gallery images will be shown in this field.
			 */
			$variation_additional_images = apply_filters( 'dicha_skroutz_feed_custom_variation_additional_images', [], $variation, $parent_product, $this->feed_type );

			if ( ! empty( $variation_additional_images ) ) {
				$group_additional_images = array_merge( $group_additional_images, $variation_additional_images );
			}

			$variation_id = $variation->get_id();

			if ( $this->wpml_do_lang_switch ) {
				// maybe add with filter
				$variation_id = apply_filters( 'wpml_object_id', $variation_id, 'product_variation', true, $this->original_site_lang );
			}

			// if no size variations, then this group has only one variation because no size grouping happens
			// if no size groups, then don't add size_variations node, but add these data as main nodes
			if ( ! $has_size_variations ) {
				$variable_group_data = [
					'id'             => apply_filters( 'dicha_skroutz_feed_custom_variation_id', $variation_id, $variation, $parent_product, $this->feed_type ), // We replace group unique_id with variation ID
					'availability'   => $this->data_helper->skroutz_get_availability( $variation ),
					'price_with_vat' => $this->data_helper->skroutz_get_price( $variation ),
					'vat'            => $this->data_helper->skroutz_get_vat( $variation ),
					'ean'            => $this->data_helper->skroutz_get_ean( $variation ),
				];
			}
			else {
				// detect if "skroutz price" exists at least on one group variation (sufficient to trigger parent price recalculation)
				if (  ! $variations_have_skroutz_price && max( 0.0, $variation->get_meta( 'dicha_skroutz_feed_price' ) ) > 0 ) {
					$variations_have_skroutz_price = true;
				}

				// if size variations exist, then add size_variations nodes
				$variation_nodes[] = [
					'variationid'    => apply_filters( 'dicha_skroutz_feed_custom_variation_id', $variation_id, $variation, $parent_product, $this->feed_type ),
					'availability'   => $this->data_helper->skroutz_get_availability( $variation ),
					'size'           => $variation_size,
					'quantity'       => apply_filters( 'dicha_skroutz_feed_custom_variation_quantity', $variation_quantity, $variation, $parent_product, $this->feed_type ),
					'price_with_vat' => $this->data_helper->skroutz_get_price( $variation ),
					'link'           => $this->data_helper->skroutz_get_url( $variation ),
					'mpn'            => $this->data_helper->skroutz_get_mpn( $variation, 'not_inherit_from_parent' ), // if empty, then empty, don't inherit
					'ean'            => $this->data_helper->skroutz_get_ean( $variation ),
				];
			}
		}


		// if group sku empty, then all variations have no sku in their own tab, so get from parent
		if ( empty( $group_sku ) ) {
			$group_sku = $this->data_helper->skroutz_get_mpn( $parent_product );
		}

		// if this sku already exists in another variation group, add a suffix to make it unique
		if ( ! empty( $group_sku ) && in_array( $group_sku, $groups_skus_list ) ) {
			$current_groups_count = count( $groups_skus_list );
			$group_sku            .= '-' . $current_groups_count;
		}

		// add unique sku to variation group data, and in the unique skus list
		if ( ! empty( $group_sku ) ) {
			$variable_group_data['mpn'] = $group_sku;
			$groups_skus_list[]         = $group_sku;
		}

		$variable_group_data['link']     = $group_link;
		$variable_group_data['weight']   = $group_weight;
		$variable_group_data['quantity'] = $group_quantity;
		$variable_group_data['image']    = $group_image;

		// if not additional images found for variations, then use parents gallery images
		$group_additional_images = array_unique( array_filter( $group_additional_images ) );

		if ( empty( $group_additional_images ) ) {
			$group_additional_images = $this->data_helper->skroutz_get_additional_images( $parent_product );
		}

		if ( ! empty( $group_additional_images ) ) {
			$variable_group_data['additional_imageurl'] = $group_additional_images;
		}

		// if color empty, then don't add in array, to keep original parent data
		if ( ! empty( $group_color ) ) {
			$variable_group_data['color'] = $group_color;
		}

		// if size empty, then don't add in array, to keep original parent data
		if ( ! empty( $group_sizes ) ) {
			$variable_group_data['size'] = implode( ',', $group_sizes );
		}

		// Add variation nested nodes if size variations exist and have no errors
		if ( ! empty( $variation_nodes ) ) {

			// clean variation nodes with errors
			foreach ( $variation_nodes as $node_key => $variation_node_data ) {

				$nodes_with_errors = array_filter( $variation_node_data, 'is_wp_error' );

				if ( ! empty( $nodes_with_errors ) ) {

					$this->products_with_errors[ $variation_node_data['variationid'] ] = [
						'name'   => $parent_product->get_name() . ' - Variation #' . $variation_node_data['variationid'],
						'errors' => $nodes_with_errors
					];

					unset( $variation_nodes[ $node_key ] );
				}
			}

			// if variation nodes still exist, add them to XML
			if ( ! empty( $variation_nodes ) ) {
				$variable_group_data['variations'] = $variation_nodes;

				// also recalculate group price if variations have "skroutz price"
				if ( $variations_have_skroutz_price ) {
					$variable_group_data['price_with_vat'] = min( array_column( $variation_nodes, 'price_with_vat' ) );
				}
			}
			else {
				// if empty, then all variations have errors -> add a WP_Error to force skipping for the parent product
				$variable_group_data['variations'] = new WP_Error( '80-2', 'All "size" variations nodes have errors or are hidden from XML' );
			}
		}

		return $variable_group_data;
	}


	/**
	 * Checks if a "size" attribute exists.
	 *
	 * @param $variation_attributes array of variation attributes slugs and values
	 *
	 * @return bool True if a "size" attribute exists.
	 */
	private function has_size_options( array $variation_attributes, array $size_var_atts_for_product ): bool {
		return ! empty( array_intersect( array_keys( $variation_attributes ), $size_var_atts_for_product ) );
	}


	/**
	 * Checks if any non "size" attribute exists.
	 *
	 * @param $variation_attributes array of variation attributes slugs and values
	 *
	 * @return bool True if any non "size" attribute exists.
	 */
	private function has_non_size_options( array $variation_attributes, array $size_var_atts_for_product ): bool {
		return ! empty( array_diff( array_keys( $variation_attributes ), $size_var_atts_for_product ) );
	}


	/**
	 * Checks if a "color" attribute exists.
	 *
	 * @param $variation_attributes array of variation attributes slugs and values
	 *
	 * @return bool True if a "color" attribute exists.
	 */
	private function has_color_options( array $variation_attributes ): bool {
		return ! empty( array_intersect( array_keys( $variation_attributes ), $this->options['color'] ) );
	}


	/**
	 * Adds the 'pa_' prefix to attributes slugs, only if missing.
	 *
	 * @param $atts_array string[] Attributes slugs.
	 *
	 * @return string[]
	 */
	private function prefix_attributes( array $atts_array ): array {
		return array_map( function( $v ) {
			if ( empty( $v ) ) return $v;
			return strpos( $v, 'pa_' ) === 0 ? $v : 'pa_' . $v;
			}, $atts_array );
	}


	/**
	 * Detect if WP_Errors exist in node data and also adds the problematic node to the errors array.
	 *
	 * @param $node_data array The node data.
	 *
	 * @return bool True if no errors found. False otherwise.
	 */
	private function detect_data_errors( array $node_data ): bool {

		$data_contain_no_errors = true;
		$nodes_with_errors      = array_filter( $node_data, 'is_wp_error' );

		if ( ! empty( $nodes_with_errors ) ) {

			$this->products_with_errors[ $node_data['id'] ] = [
				'name'   => $node_data['name'],
				'errors' => $nodes_with_errors
			];

			$data_contain_no_errors = false;
		}


		return $data_contain_no_errors;
	}


	/**
	 * Prepares error data to be printed in log files.
	 *
	 * @param $error_data array Original error data containing WP_Errors.
	 *
	 * @return array|false Error data ready for printing in log file, or false if no errors remain after removing unwanted errors.
	 */
	private function prepare_error_for_printing( array $error_data ) {

		$errors_for_print = [];

		/** @var WP_Error $wp_error */
		foreach ( $error_data['errors'] as $wp_error ) {

			if ( ! is_wp_error( $wp_error ) ) continue;

			$error_code = $wp_error->get_error_code();

			// Exclude errors which are about stock
			// Not really errors and not so important to log them
			if ( in_array( $error_code, [ '10-1', '10-2' ] ) ) continue;

			$errors_for_print[] = [
				'code'    => $error_code,
				'message' => $wp_error->get_error_message()
			];
		}

		if ( ! empty( $errors_for_print ) ) {
			$error_data['errors'] = $errors_for_print;
			return $error_data;
		}

		return false;
	}


	/**
	 * Creates a new WC log file and prints script info and products with errors.
	 *
	 * @return void
	 */
	private function write_logs(): void {

		$log_level = get_option( 'dicha_skroutz_feed_log_level', 'minimal' );

		if ( 'disabled' === $log_level ) return;

		if ( 'full' === $log_level ) {
			$errors_in_readable_form = array_filter( array_map( [ $this, 'prepare_error_for_printing' ], $this->products_with_errors ) );
		}

		$start_time              = gmdate( 'Y-m-d H:i:s', floor( $this->start_full_time ) );
		$xml_generation_duration = intval( $this->xml_generation_time - $this->start_full_time );
		$full_script_duration    = intval( microtime( true ) - $this->start_full_time );

		$logger  = wc_get_logger();
		$context = [ 'source' => DICHA_SKROUTZ_FEED_SLUG . '-' . get_date_from_gmt( $start_time, 'Hi' ) ];

		if ( ! empty( $this->xml_creation_errors ) ) {
			$logger->critical( 'XML file generation failed. Errors: ' . implode( ', ', $this->xml_creation_errors ), $context );
		}

		$logger->info( sprintf( 'XML generation started for feed type: %s', $this->feed_type ), $context );
		$logger->info( sprintf( 'XML creation start time: %s', get_date_from_gmt( $start_time, 'd-m-Y H:i:s' ) ), $context );
		$logger->info( sprintf( 'XML generation duration: %d minutes and %s seconds', (int) floor( $xml_generation_duration / 60 ), round( $xml_generation_duration % 60 ) ), $context );
		$logger->info( sprintf( 'Full script execution duration: %d minutes and %s seconds', (int) floor( $full_script_duration / 60 ), round( $full_script_duration % 60 ) ), $context );
		$logger->info( sprintf( 'Peak memory usage: %s MB', round( memory_get_peak_usage( true ) / ( 1024 * 1024 ), 2 ) ), $context );
		$logger->info( sprintf( 'Max memory limit: %s MB', round( $this->memory_limit / ( 1024 * 1024 ), 2 ) ), $context );

		if ( 'full' === $log_level ) {
			$logger->notice( 'Product nodes with errors:', $context );
			$logger->notice( wc_print_r( $errors_in_readable_form, true ), $context );
		}
	}


	/**
	 * Checks if currently used memory is close to max memory limit and in that case flushes the object cache.
	 * Except for object cache, it should be left free memory for PHP internal workings and also a memory size equal to the XML filesize for the file_put_contents() to succeed.
	 *
	 * After testing, it seems that 75% of RAM is a nice limit for cleaning the OB cache, for max memory limit larger than 512MB.
	 * Tested OK for 15k products with variations, with 75% limit on max memory size of 512MB.
	 *
	 * If the memory limit is very low like 256MB, then use 80% of memory as limit, but there is a limit here on the total exported products because of low memory.
	 * Tested OK for 5k products with variations, with 80% limit on max memory size of 256MB.
	 *
	 * @return void
	 */
	private function maybe_flush_runtime_cache(): void {

		// 80% if under 500MB, 75% if over 500MB
		$limit_ram_usage_percent = $this->memory_limit < 524288000 ? 0.8 : 0.75;

		$max_memory_to_use = min( $limit_ram_usage_percent * $this->memory_limit, $this->memory_limit - 52428800 ); // always leave a minimum of 50MB free

		if ( memory_get_usage() > $max_memory_to_use ) {

			$this->flush_runtime_cache();
		}
	}


	/**
	 * Clears the runtime object cache to free memory.
	 * After each iteration, the object cache is increasing, which makes the used memory really huge for eshops with thousands of products.
	 *
	 * @return void
	 */
	private function flush_runtime_cache(): void {

		/*
		 * Calling wp_cache_flush_runtime() lets us clear the runtime cache without invalidating the external object
		 * cache, so we will always prefer this when it is available (works for WordPress v6.1+).
		 */
		if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_runtime' ) ) {
			wp_cache_flush_runtime();
		}
		else {
			wp_cache_flush();
		}

		$GLOBALS['wpdb']->flush();
	}


	/**
	 * Find the max memory limit for this PHP script.
	 *
	 * @return void
	 * @noinspection PhpMissingBreakStatementInspection
	 */
	private function find_max_memory_limit(): void {

		$memory_limit = ini_get( 'memory_limit' );

		if ( empty( $memory_limit ) ) {
			$memory_limit = '256M';
		}
		elseif ( $memory_limit == -1 ) {
			$memory_limit = '1G';
		}

		$this->memory_limit = preg_replace_callback('/^\s*([\d.]+)\s*(?:([kmgt]?)b?)?\s*$/i', function($matches) {
			switch ( strtolower( $matches[2] ) ) {
				case 't': $matches[1] *= 1024;
				case 'g': $matches[1] *= 1024;
				case 'm': $matches[1] *= 1024;
				case 'k': $matches[1] *= 1024;
			}
			return $matches[1];
		}, $memory_limit );
	}


	/**
	 * Creates a mapping between unserialized and serialized slugs for WC attributes.
	 *
	 * @return void
	 */
	private function setup_wc_attributes_list(): void {

		global $wc_product_attributes;

		foreach ( $wc_product_attributes as $wc_product_attribute_slug => $wc_product_attribute_obj ) {

			if ( ! isset( $wc_product_attribute_obj->attribute_id ) || $wc_product_attribute_obj->attribute_id < 1 ) continue;

			$this->wc_product_attributes_sanitized[ 'pa_' . sanitize_title( $wc_product_attribute_obj->attribute_name ) ] = $wc_product_attribute_slug;
		}
	}



	/**
	 *********************************
	 ***** PRODUCT QUERY FILTERS *****
	 *********************************
	 */

	/**
	 * Handle custom params in wc_get_products query.
	 *
	 * @param array $query      Args for WP_Query.
	 * @param array $query_vars Query vars from WC_Product_Query.
	 *
	 * @return array modified $query
	 */
	function handle_skroutz_products_query_vars( array $query, array $query_vars ): array {

		if ( ! empty( $query_vars['dicha_exclude_product_category_id'] ) ) {
			$query['tax_query'][] = [
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => $query_vars['dicha_exclude_product_category_id'],
				'include_children' => true,
				'operator'         => 'NOT IN',
			];
		}

		if ( ! empty( $query_vars['dicha_exclude_product_tag_id'] ) ) {
			$query['tax_query'][] = [
				'taxonomy'         => 'product_tag',
				'field'            => 'term_id',
				'terms'            => $query_vars['dicha_exclude_product_tag_id'],
				'include_children' => true,
				'operator'         => 'NOT IN',
			];
		}

		return $query;
	}



	/**
	 *****************************
	 ***** EXPORT & SAVE XML *****
	 *****************************
	 */

	/**
	 * Saves the data for export into an XML file.
	 *
	 * @return void
	 */
	private function saveXML(): void {

		require_once( 'mod_simplexml.php' );

		echo "#========================================================================#\n";
		echo "-> Saving Products XML...\n";

		$data_for_export = [];

		try {
			$dt = new DateTime( "now", new DateTimeZone( 'Europe/Athens' ) );
			$data_for_export['created_at'] = $dt->format( 'Y-m-d H:i:s' );
		}
		catch ( Exception $e ) {}

		$data_for_export['products'] = $this->products_for_export;

		$this->decide_nodes_for_cdata();

		// creating object of SimpleXMLElement
		$xml_data = new Dicha_SimpleXMLElement_Extension( '<?xml version="1.0" encoding="UTF-8"?><mywebstore></mywebstore>' );

		// function call to convert array to xml
		$this->xmlProcess( $data_for_export, $xml_data );

		$dom                     = new DOMDocument( "1.0" );
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput       = apply_filters( 'dicha_skroutz_feed_format_output', true, $this->feed_type );
		$dom->loadXML( $xml_data->asXML(), LIBXML_PARSEHUGE );

		// Include the required files for WP_Filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		// Initialize the WP_Filesystem
		global $wp_filesystem;
		WP_Filesystem();

		$xml_location_path = Dicha_Skroutz_Feed_Admin::get_default_feed_filepath();

		// create folder inside /uploads/ if not exist
		if ( ! $wp_filesystem->exists( $xml_location_path ) ) {
			$folder_creation_result = $wp_filesystem->mkdir( $xml_location_path, 0755 );

			if ( ! $folder_creation_result ) {
				$this->xml_creation_errors[] = 'Could not create folder ' . $xml_location_path . ' inside /uploads/';
				return;
			}
		}

		$feed_filename = Dicha_Skroutz_Feed_Admin::get_feed_filename( $this->feed_type ) . '.xml';
		$filename_with_path = $xml_location_path . $feed_filename;

		// save XML
		$xml_datas = $dom->saveXML();

		$file_creation_result = $wp_filesystem->put_contents( $filename_with_path, $xml_datas );

		if ( ! $file_creation_result ) {
			$this->xml_creation_errors[] = 'Could not create the file: ' . $filename_with_path;
			return;
		}

		// Zip XML
		// todo always create zip
		if ( apply_filters( 'dicha_skroutz_feed_zip_xml', false, $this->feed_type ) && class_exists( 'ZipArchive' ) ) {

			$zip = new ZipArchive();
			$success_open = $zip->open( $filename_with_path . '.zip', ZipArchive::CREATE );

			if ( $success_open === true ) {
				$zip->addFile( $filename_with_path, $feed_filename );
				$zip->close();
			}
		}

		echo "-> Saving: DONE!\n";
		echo "#========================================================================#\n";

		// save last successful run time in UTC timestamp
		update_option( 'dicha_skroutz_feed_last_run', current_time( 'timestamp', true ), false );
	}


	/**
	 * Recursive function to create the XML nodes.
	 *
	 * Skroutz validator rules (Jun 2024):
	 * Manufacturer: Can be missing 100% *** can NOT be all empty *** can NOT be only one node *** can be some filled, some missing, some empty
	 * Color: Can be missing 100% *** can NOT be all empty *** can NOT be only one node, at least 2 even if one of them empty *** can be some filled, some missing, some empty
	 * Size: Can be missing 100% *** can be all empty *** can be only one node *** can be some filled, some missing, some empty
	 * Ean: Can be missing 100% *** can NOT be all empty *** can NOT be only one node, at least 2 even if one of them empty *** can be some filled, some missing, some empty
	 * Weight: Can be missing 100% *** can NOT be all empty *** can NOT be only one node, at least 2 even if one of them empty *** can be some filled, some missing, some empty
	 *
	 *
	 * @param $data        array
	 * @param $xml_data Dicha_SimpleXMLElement_Extension | SimpleXMLElement
	 * @param $parent_node string
	 *
	 * @return void
	 */
	private function xmlProcess( array $data, &$xml_data, string $parent_node = 'root' ): void {

		foreach ( $data as $key => $value ) {

			if ( 'products' === $parent_node ) {
				$node_name = 'product';
			}
			elseif ( 'variations' === $parent_node ) {
				$node_name = 'variation';
			}
			else {
				$node_name = (string) $key;
			}


			if ( is_array( $value ) ) {

				if ( 'additional_imageurl' === $node_name ) {

					// Exception: if additional images, then don't create sub-nodes, but add same-level nodes
					if ( in_array( $node_name, $this->fields_in_cdata ) ) {
						foreach ( $value as $additional_img_url ) {
							$xml_data->addChildCData( $node_name, (string) $additional_img_url );
						}
					}
					else {
						foreach ( $value as $additional_img_url ) {
							$xml_data->addChild( $node_name, (string) $additional_img_url );
						}
					}
				}
				else {
					$sub_node = $xml_data->addChild( $node_name );
					$this->xmlProcess( $value, $sub_node, $node_name );
				}
			}
			else {

				// don't print empty nodes
				if ( $value === '' || $value === NULL ) continue;

				if ( in_array( $node_name, $this->fields_in_cdata ) ) {
					$xml_data->addChildCData( $node_name, (string) $value );
				}
				else {
					$xml_data->addChild( $node_name, (string) $value );
				}
			}
		}
	}


	/**
	 * Set which fields will be wrapped in CDATA.
	 *
	 * @return void
	 */
	private function decide_nodes_for_cdata(): void {

		$this->fields_in_cdata = apply_filters( 'dicha_skroutz_feed_fields_in_cdata', [
			'name',
			'link',
			'category',
			'description',
			'image',
			'additional_imageurl'
		], $this->feed_type );
	}

}
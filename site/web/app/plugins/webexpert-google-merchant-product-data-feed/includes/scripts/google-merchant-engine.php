<?php
//if ( ! defined( 'ABSPATH' ) ) {
//    exit; // Exit if accessed directly
//}

class webexpert_google_merchant_Egnine {
	private $xml;
	private $filename;
	private $tmp_filename;
	private $options;
	private $webexpert_google_merchant_product_data_feed_stats_simple_products;
	private $webexpert_google_merchant_product_data_feed_stats_variations;

	public function __construct() {
		global $blog_id;
		if (defined('WP_ALLOW_MULTISITE') && isset($blog_id)) {
			$this->filename = "google_merchant_$blog_id.xml";
			$this->tmp_filename = "google_merchant_".current_time('timestamp')."$blog_id.xml";
		}else {
			$this->filename = "google_merchant.xml";
			$this->tmp_filename="google_merchant_".current_time('timestamp').".xml";
		}

		$this->options = array(
			'brand' => get_option('we_google_merchant_product_data_feed_brand',[]) ?? [],
			'colour' => get_option('we_google_merchant_product_data_feed_colour',[]) ?? [],
            'size' => get_option('we_google_merchant_product_data_feed_size',[]) ?? [],
            'description' => get_option('we_google_merchant_product_data_feed_desc_field','short'),
			'custom_gtin' => get_option('we_google_merchant_product_data_feed_custom_gtin',null),
			'custom_id' => get_option('we_google_merchant_product_data_feed_custom_id',null),
			'custom_mpn' => get_option('we_google_merchant_product_data_feed_custom_mpn',null),
			'custom_weight'=>get_option('we_google_merchant_product_data_feed_custom_weight',null),
			'max_page_size'=>get_option('we_google_merchant_product_data_feed_max_page_size',100),
			'hide_out_of_stock'=>get_option('we_google_merchant_product_data_feed_hide_out_of_stock',100),
		);
	}

	public function prepare_args() {
		$args = array('post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish','fields'=>'ids');
		$excluded_terms_categories = get_option('we_google_merchant_product_data_feed_categories_not_to_list');
		$excluded_terms_tags = get_option('we_google_merchant_product_data_feed_tags_not_to_list');
		$we_google_merchant_product_data_feed_attributes = get_option('we_google_merchant_product_data_feed_attributes_not_to_list');
		$args['meta_query']=[
			[
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS'
			]
		];

		if ((is_array($excluded_terms_categories) && sizeof($excluded_terms_categories) > 0) || (is_array($excluded_terms_tags) && sizeof($excluded_terms_tags) > 0) || is_array($we_google_merchant_product_data_feed_attributes) && sizeof($we_google_merchant_product_data_feed_attributes) > 0) {
			$args['tax_query'] = array("relation" => "AND");
		}

		if ($excluded_terms_categories > 0) {
			$args['tax_query'][] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $excluded_terms_categories,
				'operator' => (get_option('we_google_merchant_product_data_feed_categories_not_to_list_invert',0) ? 'IN' : 'NOT IN')
			);
		}

		if ($excluded_terms_tags > 0) {
			$args['tax_query'][] = array(
				'taxonomy' => 'product_tag',
				'field'    => 'term_id',
				'terms'    => $excluded_terms_tags,
				'operator' => (get_option('we_google_merchant_product_data_feed_tags_not_to_list_invert',0) ? 'IN' : 'NOT IN')
			);
		}

		if ($we_google_merchant_product_data_feed_attributes) {
			$temp_attributes=[];
			if (sizeof($we_google_merchant_product_data_feed_attributes)>1) {
				$temp_attributes['relation']=(get_option('we_google_merchant_product_data_feed_attributes_not_to_list_invert',0) ? 'OR' : 'AND');
			}
			foreach ($we_google_merchant_product_data_feed_attributes as $attribute_filter) {
				$expl = explode("__", $attribute_filter);
				$temp_attributes[] = array(
					'taxonomy' => $expl[1],
					'field'    => 'term_id',
					'terms'    => $expl[0],
					'operator' => (get_option('we_google_merchant_product_data_feed_attributes_not_to_list_invert',0) ? 'IN' : 'NOT IN')
				);
			}
			$args['tax_query'][]=$temp_attributes;
		}

		if (!empty(apply_filters('webexpert_google_merchant_product_data_feed_hide_certain_product_ids',[]))) {
			$args['post__not_in'][]=apply_filters('webexpert_google_merchant_product_data_feed_hide_certain_product_ids',[]);
		}

		return apply_filters( 'webexpert_google_merchant_product_data_feed_custom_args', $args);
	}

	public function run() {
		$upload_dir = wp_upload_dir();
		$google_merchant_xml_dir = $upload_dir['basedir'] . '/webexpert-google-merchant-product-data-feed';
		$google_merchant_xml_url = $upload_dir['baseurl'] . '/webexpert-google-merchant-product-data-feed';
		wp_mkdir_p($google_merchant_xml_dir);

		$this->xml = new XMLWriter();
		$this->xml->openMemory();
		$this->xml->setIndent(1);
		$this->xml->startDocument('1.0', 'UTF-8');
		$this->xml->startElement('rss');
		$this->xml->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
		$this->xml->writeAttribute('version', '2.0');

		$this->xml->startElement('channel');
		$this->xml->writeElement('title',get_bloginfo('name'));
		$this->xml->writeElement('link',get_bloginfo('url'));
		$loop = new WP_Query($this->prepare_args());
		if ($loop->last_error) :
			print_r($loop->last_error);
			exit;
		endif;

		$count=0;

		$this->webexpert_google_merchant_product_data_feed_stats_simple_products=0;
		$this->webexpert_google_merchant_product_data_feed_stats_variations=0;
		$this->webexpert_google_merchant_product_data_feed_stats_percentage=0;
		update_option('webexpert_google_merchant_product_data_feed_stats_simple_products',$this->webexpert_google_merchant_product_data_feed_stats_simple_products);
		update_option('webexpert_google_merchant_product_data_feed_stats_variations',$this->webexpert_google_merchant_product_data_feed_stats_variations);
		update_option('webexpert_google_merchant_product_data_feed_stats_percentage',$this->webexpert_google_merchant_product_data_feed_stats_percentage);

		while ($loop->have_posts()) : $loop->the_post();
			$this->generate_data(get_the_ID());
			if (0 == $count % $this->options['max_page_size']) {
				file_put_contents($google_merchant_xml_dir.'/'.$this->tmp_filename, $this->xml->flush(true), FILE_APPEND);
				update_option('webexpert_google_merchant_product_data_feed_stats_simple_products',$this->webexpert_google_merchant_product_data_feed_stats_simple_products);
				update_option('webexpert_google_merchant_product_data_feed_stats_variations',$this->webexpert_google_merchant_product_data_feed_stats_variations);
				update_option('webexpert_google_merchant_product_data_feed_stats_percentage',round($this->options['max_page_size'] / $loop->post_count,2) * 100);
			}
			$count++;
		endwhile;
		$this->xml->endElement();
		$this->xml->endElement();
		$this->xml->endDocument();

		file_put_contents($google_merchant_xml_dir.'/'.$this->tmp_filename, $this->xml->flush(true), FILE_APPEND);
		update_option('webexpert_google_merchant_product_data_feed_stats_simple_products',$this->webexpert_google_merchant_product_data_feed_stats_simple_products);
		update_option('webexpert_google_merchant_product_data_feed_stats_variations',$this->webexpert_google_merchant_product_data_feed_stats_variations);
		update_option('webexpert_google_merchant_product_data_feed_stats_percentage', 100);

		$doc = new DOMDocument;
		if (@$doc->load($google_merchant_xml_dir.'/'.$this->tmp_filename) === false) {
			set_transient('webexpert-google-merchant-product-data-feed-errors', __('XML could not be generated or copied.', 'webexpert-google-merchant-product-data-feed') . '. <a target="_blank" href="' . $google_merchant_xml_url.'/'.$this->tmp_filename . '">' . __('View XML', 'webexpert-google-merchant-product-data-feed') . '</a>');
		}else {
			copy($google_merchant_xml_dir.'/'.$this->tmp_filename,$google_merchant_xml_dir.'/'.$this->filename);
			set_transient('webexpert-google-merchant-product-data-feed-success', __('XML Data Feed was generated successfully.', 'webexpert-google-merchant-product-data-feed') . '. <a target="_blank" href="' . $google_merchant_xml_url.'/'.$this->filename . '">' . __('View XML', 'webexpert-google-merchant-product-data-feed') . '</a>');
			unlink($google_merchant_xml_dir.'/'.$this->tmp_filename);
		}

		update_option('we_google_merchant_product_data_feed_lastrun', date_i18n('d-m-Y H:i:s'));
		set_transient('webexpert-google-merchant-product-data-feed-success', __('XML Data Feed was generated successfully.', 'webexpert-google-merchant-product-data-feed') . '. <a target="_blank" href="' . $google_merchant_xml_url.'/'.$this->filename . '">' . __('View XML', 'webexpert-google-merchant-product-data-feed') . '</a>');

		wp_reset_query();
	}

	public function generate_data($product_id) {
		$product=wc_get_product($product_id);
		if ($product && floatval($product->get_price()) > 0) {
			if ($product->is_type('simple')) {
				$exported = $this->generate_product($product);
				if ($exported)
					$this->webexpert_google_merchant_product_data_feed_stats_simple_products++;
			} elseif ($product->is_type('variable')) {
				$variations = $product->get_available_variations('objects');
				foreach ($variations as $variation) {
					$exported = $this->generate_product($variation);
					if ($exported)
						$this->webexpert_google_merchant_product_data_feed_stats_variations++;
				}
			}
		}
	}

	public function generate_product($product) {
		if ( ! $this->check_visibility( $product ) ) {
			return false;
		}
		if ($this->options['hide_out_of_stock']==1 && !$product->is_in_stock()) {
			return false;
		}

		$this->xml->startElement('item');
		$this->get_id($product);
		$this->get_name($product);
		$this->get_description($product);
		$this->get_link($product);
		$this->get_image($product);
		$this->get_gallery($product);
		$this->get_availability($product);
		$this->get_prices($product);

		$this->get_categories($product);
		$this->get_brand($product);
		$this->get_gtin($product);
		$this->get_mpn($product);
		$this->get_condition($product);

		$this->get_product_detail($product);
		$this->get_weight($product);
		$this->get_colours($product);
		$this->get_sizes($product);

		do_action('we_google_merchant_product_data_feed_additional_fields',$product, $this->xml, $this->options);
		$this->xml->endElement();
		return true;
	}

	public function get_sizes($product,$overrides=[]) {
		if ($product->is_type('simple')) {
			$size = apply_filters('we_google_merchant_product_data_feed_custom_size',$this->get_tax_or_attribute($product,'size'),$product);
			if (!empty($size)) {
				$this->xml->startElement('size');
				$this->xml->writeCData($size);
				$this->xml->endElement();
			}
		}elseif ($product->is_type('variable')) {
			foreach ($this->options['size'] as $size_attribute) {
				$available_variations = $product->get_available_variations('objects');
				foreach ($available_variations as $variation) {
					if ($this->check_visibility($variation) !== false) {
						$variation_attributes = $variation->get_variation_attributes();
						if (array_key_exists("attribute_pa_$size_attribute", $variation_attributes)) {
							$attr_slug = $variation_attributes["attribute_pa_$size_attribute"];
							$term_obj = get_term_by('slug', $attr_slug, "pa_" . $size_attribute);
							if ($term_obj && !is_wp_error($term_obj))
								$attr_name[] = $term_obj->name;
						}
					}
				}
			}
			if (!empty($attr_name)) {
				$this->xml->startElement('g:size');
				$this->xml->writeElement(implode(', ', array_unique($attr_name)));
				$this->xml->endElement();
			}
		}elseif ($product->is_type('variation')) {
			$sizes = array();
			foreach ($this->options['size'] as $size) {
				$term = get_term_by('slug', $product->get_attribute($size), 'pa_' . $size);
				if (!is_wp_error($term) && $term !== false) {
					$sizes[] = $term->name;
				}
			}
			if (!empty($sizes)) {
				$this->xml->writeElement('g:size',implode(",", array_unique($sizes)));
			}
		}
	}

	public function get_colours($product) {
		if ($product->is_type('variation')) {
			$colours = array();
			foreach ($this->options['colour'] as $colour) {
				$term = get_term_by('slug', $product->get_attribute($colour), 'pa_' . $colour);
				if (!is_wp_error($term) && $term !== false) {
					$colours[] = $term->name;
				}
			}
			if (!empty($colours)) {
				$this->xml->writeElement('g:color',implode(",", array_unique($colours)));
			}
		}else {
			$color = apply_filters('we_google_merchant_product_data_feed_custom_color',$this->get_tax_or_attribute($product,'colour'),$product);
			if (!empty($color)) {
				$this->xml->writeElement('g:color',$color);
			}
		}
	}

	public function get_brand($product) {
		$brand = esc_html(apply_filters('we_google_merchant_product_data_feed_custom_brand',$this->get_tax_or_attribute($product, 'brand'),$product));
		if (!empty($brand)) {
			$this->xml->writeElement('g:brand',$brand);
		}
	}

	public function get_product_detail($product ) {
		$product_attributes = $product->get_attributes();
		if ($product->is_type('variation')) {
			foreach ( $product_attributes as $attribute_key=>$attribute_value ) {
				if (in_array(str_replace("pa_","",$attribute_key),$this->options['colour']) || in_array(str_replace("pa_","",$attribute_key),$this->options['size'] )) {
					continue;
				}

				$attribute_name_slug = wc_attribute_label( $attribute_key, $product );
				if ( taxonomy_exists( $attribute_name_slug ) ) {
					$taxonomy_obj = get_taxonomy( $attribute_name_slug );
					if ( $taxonomy_obj ) {
						$attribute_name = $taxonomy_obj->labels->singular_name;
						$term           = get_term_by( 'slug', $attribute_value, $attribute_name_slug );
						if (!is_wp_error($term) && $term !== false) {
							$attribute_value = $term->name;
						}
					}else {
						$attribute_name = wc_attribute_label( $attribute_key, $product );
					}
				} else {
					$attribute_name = wc_attribute_label( $attribute_key, $product );
					$attribute_value = ucfirst( $attribute_value );
				}
				$this->xml->startElement('g:product_detail');
				$this->xml->writeElement("g:attribute_name",$attribute_name);
				$this->xml->writeElement("g:attribute_value",$attribute_value);
				$this->xml->endElement();
			}
		}else {
			foreach ( $product_attributes as $attribute ) {
				if ($attribute->is_taxonomy()) {
					$attribute_taxonomy = $attribute->get_taxonomy_object();
					$attribute_values = wc_get_product_terms($product->get_id(), $attribute->get_name(), array('fields' => 'all'));

					foreach ($attribute_values as $attribute_value) {
						$value_name = esc_html($attribute_value->name);
						if ($attribute_taxonomy->attribute_public) {
							$this->xml->startElement('g:product_detail');
							$this->xml->writeElement("g:attribute_name", $attribute_taxonomy->attribute_label);
							$this->xml->writeElement("g:attribute_value", $value_name);
							$this->xml->endElement();
						}
					}
				}
			}
		}
	}

	public function get_tax_or_attribute($product,$attribute) {
		$attributes = array();
        if (!empty($this->options[$attribute])) {
			foreach ($this->options[$attribute] as $attr) {
				$pa_terms = get_the_terms(($product->is_type('variation') ? $product->get_parent_id() : $product->get_id()), taxonomy_exists($attr) ? $attr : 'pa_' . $attr);
				if ($pa_terms && !is_wp_error($pa_terms)) {
					$array_terms = array_map(function ($e) {
						return is_object($e) ? $e->name : $e['name'];
					}, $pa_terms);
					$attributes = array_merge($attributes, $array_terms);
				}
			}
		}
		return implode(",", array_unique($attributes));
	}

	public function get_weight($product) {
		$weight = apply_filters('we_google_merchant_product_data_feed_custom_weight', $product->get_weight(), $product);
		if ($weight) {
			$this->xml->writeElement('g:product_weight', $weight ." ".get_option('woocommerce_weight_unit'));
			$this->xml->writeElement('g:shipping_weight', $weight ." ".get_option('woocommerce_weight_unit'));
		}
		$length = apply_filters('we_google_merchant_product_data_feed_custom_length', $product->get_weight(), $product);
		if ($length) {
			$this->xml->writeElement('g:product_length', $length ." ".get_option('woocommerce_dimension_unit'));
		}
		$width = apply_filters('we_google_merchant_product_data_feed_custom_width', $product->get_weight(), $product);
		if ($width) {
			$this->xml->writeElement('g:product_width', $width ." ".get_option('woocommerce_dimension_unit'));
		}
		$height = apply_filters('we_google_merchant_product_data_feed_custom_height', $product->get_weight(), $product);
		if ($height) {
			$this->xml->writeElement('g:product_height', $height ." ".get_option('woocommerce_dimension_unit'));
		}
	}

	public function is_on_backorder($product) {
		if ($product->get_manage_stock()==false) {
			return $product->get_stock_status()!=='instock' && ($product->is_on_backorder() || $product->backorders_allowed() || ($product->is_type('variable') && $product->child_is_on_backorder()));
		}else {
			return $product->get_stock_quantity()<=0 && ($product->is_on_backorder() || $product->backorders_allowed() || ($product->is_type('variable') && $product->child_is_on_backorder()));
		}
	}

	public function get_availability($product) {
		$is_on_backorder = $this->is_on_backorder($product);
		if ($product->is_in_stock() && !$is_on_backorder) {
			$availability = "in_stock";
		} else {
			if ($is_on_backorder) {
				$availability = "backorder";
			}else {
				$availability = "out_of_stock";
			}
		}
		$this->xml->writeElement('g:availability',apply_filters('we_google_merchant_product_data_feed_custom_availability',$availability,$product,$is_on_backorder));
		if ($availability === "backorder") {
			$daysToAdd = apply_filters('we_google_merchant_product_data_feed_custom_availability_date',10,$product,$is_on_backorder);
			$currentTimestamp = current_time('timestamp');
			$futureTimestamp = $currentTimestamp + ($daysToAdd * DAY_IN_SECONDS);
			$this->xml->writeElement('g:availability_date',date_i18n('c', $futureTimestamp));
		}
	}

	public function get_description($product) {
		if ($product->is_type('variation')) {
			$_parent_product = wc_get_product($product->get_parent_id());
			$description=$this->options['description']=='short' ? $_parent_product->get_short_description() : $_parent_product->get_description();
		}else {
			$description=$this->options['description']=='short' ? $product->get_short_description() : $product->get_description();
		}

		if (apply_filters('we_google_merchant_product_data_feed_custom_description',$description,$product)) {
			$this->xml->startElement('g:description');
			$this->xml->writeCData(wp_filter_nohtml_kses(apply_filters('we_google_merchant_product_data_feed_custom_description',$description,$product)));
			$this->xml->endElement();
		}
	}

	public function get_quantity($product) {
		$quantity = apply_filters('webexpert_google_merchant_product_data_feed_custom_quantity',$product->get_stock_quantity(),$product);
		$this->xml->writeElement('quantity', $quantity);

		if (!empty(apply_filters('webexpert_google_merchant_product_data_feed_custom_offer_quantity',null,$product))) {
			$this->xml->writeElement('offer_quantity', apply_filters('webexpert_google_merchant_product_data_feed_custom_offer_quantity',null,$product));
		}
	}

	public function get_gtin($product) {
		$gtin = $product->get_meta('we_google_merchant_product_data_feed_ean_barcode') ?? null;
		if (!empty($this->options['custom_gtin'])) {
			if ($this->options['custom_gtin']=='_sku') {
				$gtin = $product->get_sku();
			}else {
				$gtin=$product->get_meta($this->options['custom_gtin']);
			}

			if ($product->is_type('variation') && empty($gtin)) {
				$gtin=get_post_meta($product->get_parent_id(),$this->options['custom_gtin'],true);
			}
		}
		$gtin =apply_filters('webexpert_google_merchant_product_data_feed_custom_gtin', $gtin, $product);
		if (!empty($gtin))
			$this->xml->writeElement('g:gtin',$gtin);
	}

	public function get_id($product) {
		$id = $product->get_id();
		if (!empty($this->options['custom_id'])) {
			if ($this->options['custom_id']=='_sku') {
				$id = $product->get_sku();
			}else {
				$id=$product->get_meta($this->options['custom_id']);
			}
		}

		$id = esc_html(apply_filters('webexpert_google_merchant_product_data_feed_custom_id', $id,$product));
		if (!empty($id))
			$this->xml->writeElement('g:id',$id);

		if ($product->is_type('variation')) {
			$group_id = esc_html(apply_filters('webexpert_google_merchant_product_data_feed_custom_group_id', $product->get_parent_id(),$product));
			$this->xml->writeElement('g:item_group_id',$group_id);
		}
	}

	public function get_mpn($product) {
		$mpn = $product->get_meta('we_google_merchant_product_data_feed_mpn');
		if (!empty($this->options['custom_mpn'])) {
			if ($this->options['custom_mpn']=='_sku') {
				$mpn = $product->get_sku();
			}else {
				$mpn=$product->get_meta($this->options['custom_mpn']);
			}

			if ($product->is_type('variation') && empty($mpn)) {
				$mpn=get_post_meta($product->get_parent_id(),$this->options['custom_mpn'],true);
			}
		}
		$mpn=esc_html(apply_filters('webexpert_google_merchant_product_data_feed_custom_mpn',$mpn));
		if (!empty($mpn))
			$this->xml->writeElement('g:mpn', $mpn);
	}

	public function get_condition($product) {
		$condition=esc_html(apply_filters('webexpert_google_merchant_product_data_feed_custom_condition','new',$product));
		if (!empty($condition))
			$this->xml->writeElement('g:condition', $condition);
	}

	public function get_prices($product) {
		$regular_price = esc_html(apply_filters('webexpert_google_merchant_product_data_feed_custom_list_price',wc_get_price_including_tax($product,['qty'=>1,'price'=>$product->get_regular_price()]),$product));

		$sale_price=null;
		if (class_exists('RP_WCDPD_Settings')) {
			foreach (RP_WCDPD_Rules::get('product_pricing', array('methods' => array('simple'))) as $rule_key => $rule) {
				$matched = RP_WCDPD_Controller_Conditions::object_conditions_are_matched($rule, array(
					'item_id'               => (!$product->is_type('variation') ? $product->get_id() : $product->get_parent_id()),
					'child_id'              => ($product->is_type('variation') ? $product->get_id() : null),
					'variation_attributes'  => null,
				));
				if (!$matched) {
					continue;
				}
				if ($rule['pricing_method'] === 'discount__percentage') {
					if (RP_WCDPD_Settings::get('product_pricing_sale_price_handling') === 'exclude' && RP_WCDPD_Product_Pricing::product_is_on_sale($product)) {
						continue;
					}
					if (RP_WCDPD_Settings::get('product_pricing_sale_price_handling') === 'regular') {
						$base_price = (float) $product->get_regular_price('edit');
					}else {
						$base_price = (float) $product->get_price('edit');
					}
					$sale_price = $base_price - ($base_price * $rule['pricing_value'] / 100);
				}
			}
		}

		$sale_price = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', $sale_price, $product,1,0,'discounted_price',true,false);

		if (empty($sale_price)) {
			$sale_price = esc_html(apply_filters('webexpert_google_merchant_product_data_feed_custom_price',wc_get_price_including_tax($product,['qty'=>1,'price'=>$product->get_sale_price()]),$product));
		}

		if ($regular_price) {
			$this->xml->writeElement('g:price',round($regular_price,2) . " ".get_woocommerce_currency());
		}
		if ($sale_price && $sale_price!=$regular_price) {
			$this->xml->writeElement('g:sale_price',round($sale_price,2). " ".get_woocommerce_currency());
		}
	}

	public function get_image($product) {
		$this->xml->writeElement('g:image_link',esc_html(apply_filters('webexpert_google_merchant_product_data_feed_custom_image',wp_get_attachment_url($product->get_image_id()),$product)));
	}

	public function get_gallery($product) {
		$attachment_ids = [];
		if ($product->is_type('variation')) {
			$parent = wc_get_product($product->get_parent_id());
			if ($parent)
				$attachment_ids = apply_filters('webexpert_google_merchant_product_data_feed_custom_gallery', $parent->get_gallery_image_ids(), $product);
		}else {
			$attachment_ids = apply_filters('webexpert_google_merchant_product_data_feed_custom_gallery', $product->get_gallery_image_ids(), $product);
		}

		if (sizeof($attachment_ids)>0) {
			$count=1;
			foreach ($attachment_ids as $attachment_id) {
				$this->xml->startElement('g:additional_image_link');
				$this->xml->writeCData(esc_html(apply_filters('webexpert_google_merchant_product_data_feed_custom_additional_gallery_image',wp_get_attachment_url($attachment_id),$product,$count)));
				$this->xml->endElement();
				$count++;
			}
		}
	}

	public function get_link($product) {
		$this->xml->writeElement('g:link',esc_html(apply_filters('webexpert_google_merchant_product_data_feed_custom_link',trim($product->get_permalink()),$product)));
	}

	public function get_name($product) {
		$this->xml->writeElement('g:title',esc_html(apply_filters('webexpert_google_merchant_product_data_feed_custom_product_title', $product->get_title(), $product)));
	}

	public function get_categories($product) {
		$category_ids = get_the_terms(($product->is_type('variation') || $product->is_type('variable') ? $product->get_parent_id() : $product->get_id()), 'product_cat');
		if ($category_ids) {
			$parent = null;
			if ($product->is_type('variation')) {
				$parent=wc_get_product($product->get_parent_id());
			}
			$last_category = get_term(end($category_ids), 'product_cat', 'taxonomy');
			if ( class_exists( 'WPSEO_Primary_Term' ) ) {
				if ($product->is_type('variation')) {
					$primary_term_object = new WPSEO_Primary_Term('product_cat', $product->get_parent_id());
				}else {
					$primary_term_object = new WPSEO_Primary_Term('product_cat', $product->get_id());
				}
				$last_category_id=$primary_term_object->get_primary_term();
				if ($last_category_id)
					$last_category = get_term( $last_category_id, 'product_cat' );
			}

			$rank_math_primary_product_cat=$parent ? $parent->get_meta('rank_math_primary_product_cat') : $product->get_meta('rank_math_primary_product_cat');
			if (!empty($rank_math_primary_product_cat) && in_array($rank_math_primary_product_cat,$product->get_category_ids())) {
				$possible_term = get_term( $rank_math_primary_product_cat, 'product_cat' );
				if ($possible_term && !is_wp_error($possible_term))
					$last_category = get_term( $possible_term->term_id, 'product_cat' );
			}

			// The SEO Framework
			$tsf_primary_product_cat=$parent ? $parent->get_meta('_primary_term_product_cat') : $product->get_meta('_primary_term_product_cat');
			if (!empty($tsf_primary_product_cat)) {
				$possible_term = get_term( $tsf_primary_product_cat, 'product_cat' );
				if ($possible_term && !is_wp_error($possible_term))
					$last_category = get_term( $possible_term->term_id, 'product_cat' );
			}

			if (!is_wp_error($last_category)) {
				$this->xml->writeElement('g:product_type',esc_html(apply_filters('webexpert_google_merchant_product_data_feed_custom_category',$last_category->name)));
			}

			$google_category_mapping = get_term_meta($last_category->term_id, 'google_categories_map', true);
			if (!empty($google_category_mapping)) {
				$this->xml->writeElement('g:google_product_category',esc_html(apply_filters('webexpert_google_merchant_product_data_feed_custom_product_category',$google_category_mapping)));
			}elseif (!empty(get_option('we_google_merchant_product_data_feed_default_category',''))) {
				$this->xml->writeElement('g:google_product_category',esc_html(get_option('we_google_merchant_product_data_feed_default_category','')));
			}
		}
	}

	public function check_visibility($product) {
		if (apply_filters('webexpert_google_merchant_product_data_feed_product_visibility_control',true,$product)===false) {
			return false;
		}
		return true;
	}
}

function run_webexpert_google_merchant_engine() {

	$plugin = new webexpert_google_merchant_Egnine();
	$plugin->run();

}
run_webexpert_google_merchant_engine();

if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
	return true;
}else {
	wp_redirect(admin_url('admin.php?page=webexpert-google-merchant-product-data-feed'));
}
die();
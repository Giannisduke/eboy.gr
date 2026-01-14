<?php
use DragonBe\Vies\Vies;

class Webexpert_Timologio_For_Woocommerce_Public {

	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_register_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/webexpert-timologio-for-woocommerce-public.css', array(), $this->version, 'all');
		if (is_checkout() || is_account_page())
			wp_enqueue_style($this->plugin_name);
	}

	public function enqueue_scripts() {
		if (is_checkout() || is_account_page()) {
			wp_register_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/webexpert-timologio-for-woocommerce-public.js', array('jquery'), $this->version, false);
			wp_enqueue_script($this->plugin_name);
			wp_localize_script( $this->plugin_name, 'webexpert_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		}

		if(is_checkout() && get_option('webexpert_timologio_for_woocommerce_uppercase_checkout') == 'yes') {
			wp_enqueue_script( $this->plugin_name  ."2", plugin_dir_url( __FILE__ ) . 'js/webexpert-timologio-extra.js', array( 'jquery' ), $this->version, false );
		}
	}

	public function webexpert_timologio_for_wc_aade_fill() {
		$vat_id=sanitize_text_field($_POST['vat_id']);
		if (!empty($vat_id) && get_option('webexpert_timologio_for_woocommerce_validate_aade')=="yes" && get_option('webexpert_timologio_for_woocommerce_validate_aade_autocomplete')=="yes") {
			$check=$this->check_for_valid_vat_aade($vat_id);
			wp_send_json($check);
			die();
		}
		wp_send_json([]);
	}

	public function webexpert_timologio_for_wc_invoice_value() {
		$invoice_option=sanitize_text_field($_POST['invoice_option']);
		if (!empty($invoice_option)) {
			WC()->session->set('chosen_billing_invoice', $invoice_option);
		}
		wp_die(); // this is required to terminate immediately and return a proper response
	}

    public function get_tax_offices_from_json() {
		$json_path = plugin_dir_path(__DIR__) . 'includes/tax-offices.json';

		if (file_exists($json_path)) {
			$json_content = file_get_contents($json_path);
			return json_decode($json_content, true);
		}
		return false;
	}

	public function webexpert_timologio_for_woocommerce_billing_fields($billing_fields) {
		$billing_fields['billing_company']['priority'] = 24;
		$billing_fields['billing_company']['class'] = ['form-row-wide','','invoice-only'];
		$billing_fields['billing_company']['label'] = __('Company Name', $this->plugin_name);
		$billing_fields['billing_company']['placeholder'] = _x('Company Name', 'placeholder', $this->plugin_name);
		$repositioning_fix_class=get_option('webexpert_timologio_for_woocommerce_repositioning_fix','no')=='yes' ? 'repositioning_fix' : '';
		$chosen_billing_invoice = (isset(WC()->session) && !empty(WC()->session->get('chosen_billing_invoice'))) ? WC()->session->get('chosen_billing_invoice') : 'n';

		if (get_option('webexpert_timologio_for_woocommerce_display_as','select')=='select') {
			$billing_fields['billing_invoice'] = array(
				'priority'    => 21,
				'type'        => 'select',
				'label'       => apply_filters('webexpert_timologio_for_wc__label_select',__('I want to issue an invoice', $this->plugin_name)),
				'placeholder' => _x('Invoice', 'placeholder', $this->plugin_name),
				'required'    => false,
				'class'       => array('form-row-wide', 'invoice-select',$repositioning_fix_class),
				'clear'       => true,
				'options'     => array(
					'n' => apply_filters('webexpert_timologio_for_wc__label_no',__('No', $this->plugin_name)),
					'y' => apply_filters('webexpert_timologio_for_wc__label_yes',__('Yes', $this->plugin_name)),
				),
				'default'     => $chosen_billing_invoice
			);
		}else {
			$billing_fields['billing_invoice'] = array(
				'priority'    => 21,
				'type'        => 'radio',
				'label'       => "",
				'required'    => false,
				'class'       => array('form-row-wide'),
				'options'     => array(
					'n' => __('Receipt', $this->plugin_name),
					'y' => __('Invoice', $this->plugin_name),
				),
				'default'     => $chosen_billing_invoice
			);
		}

		$billing_fields['billing_activity'] = array(
			'priority'    => 25,
			'type'        => 'text',
			'label'       => apply_filters('webexpert_timologio_for_wc__label_activity',__('Business activity', $this->plugin_name)),
			'placeholder' => _x('Business activity', 'placeholder', $this->plugin_name),
			'class'       => array('form-row-wide', 'invoice-only', 'validate-required'),
			'required'    => false,
			'clear'       => true,
		);

        if (get_option("webexpert_timologio_for_woocommerce_vat_exempt_auto_if_valid") == "yes") {
            $page_with_terms=get_option('webexpert_timologio_for_woocommerce_39a_page',wc_terms_and_conditions_page_id());
	        $billing_fields['billing_39a'] = array(
		        'label'       => apply_filters('webexpert_timologio_for_wc__label_39a',__('VAT exemption (article 39a / POL 1150/29.9.2017)', $this->plugin_name)),
		        'type'        => 'checkbox',
		        'required'    => false,
		        'priority'    => 26,
		        'class'       => array('form-row-wide invoice-only'),
		        'clear'       => true,
                'custom_attributes' => [
                    'data-custom-description'=>apply_filters('webexpert_timologio_for_wc__label_39a_description',sprintf(__('<span style="display:block!important;color: #666;font-style: italic;font-size: 12px;">**The VAT exemption applies to mobile phones, game consoles, tablets, and laptops.
<a style="color: #333;" target="_blank" href="%s">Read the conditions and documents</a> you will need to send us after completing your order. 
If necessary, we will contact you for further verification of your details and the completion of the process.</span>',$this->plugin_name),get_permalink($page_with_terms)))
                ]
	        );
        }

		$billing_fields['billing_vat_id'] = array(
			'priority'    => 22,
			'type'        => 'text',
			'label'       => apply_filters('webexpert_timologio_for_wc__label_vat_no',__('VAT number', $this->plugin_name)),
			'placeholder' => _x('VAT number', 'placeholder', $this->plugin_name),
			'class'       => array('form-row-first', 'invoice-only', 'validate-required'),
			'required'    => false
		);
		if (get_option('webexpert_timologio_for_woocommerce_tax_office_dropdown','')=="yes") {
			$tax_offices = $this->get_tax_offices_from_json();

			$options = array('' => __('Select Tax Office', $this->plugin_name));
			if ($tax_offices) {
				foreach ($tax_offices as $office_key => $office_name) {
					$options[$office_key] = $office_name;
				}
			}

			$billing_fields['billing_tax_office'] = array(
				'priority' => 23,
				'type' => 'select',
				'label' => apply_filters( 'webexpert_timologio_for_wc__label_tax_office', __( 'Tax office', $this->plugin_name ) ),
				'placeholder' => _x( 'Tax office', 'placeholder', $this->plugin_name ),
				'class' => array(
					'form-row-last',
					'invoice-only',
					'validate-required'
				),
				'options'     => $options,
				'required' => false,
				'clear' => true
			);
		}else {
			$billing_fields['billing_tax_office'] = array(
				'priority' => 23,
				'type' => 'text',
				'label' => apply_filters( 'webexpert_timologio_for_wc__label_tax_office', __( 'Tax office', $this->plugin_name ) ),
				'placeholder' => _x( 'Tax office', 'placeholder', $this->plugin_name ),
				'class' => array(
					'form-row-last',
					'invoice-only',
					'validate-required'
				),
				'required' => false,
				'clear' => true
			);
        }
		return $billing_fields;
	}


	public function webexpert_timologio_for_woocommerce_customer_meta_fields($billing_fields) {
		if (isset($billing_fields['billing']['fields'])) {
			$billing_fields['billing']['fields']['billing_activity'] = array(
				'label'       => __('Business activity', $this->plugin_name),
				'description' => ''
			);
			$billing_fields['billing']['fields']['billing_vat_id'] = array(
				'label'       => __('VAT number', $this->plugin_name),
				'description' => ''
			);
			$billing_fields['billing']['fields']['billing_tax_office'] = array(
				'label'       => __('Tax office', $this->plugin_name),
				'description' => ''
			);
		}
		return $billing_fields;
	}

	public function webexpert_timologio_for_woocommerce_checkout_field_process($data, $errors) {
		$billing_invoice = $data['billing_invoice'];
		$billing_vat_id = $data['billing_vat_id'];
		$billing_tax_office = $data['billing_tax_office'];
		$billing_activity = $data['billing_activity'];
		$country = $data['billing_country'];
		if ( function_exists( 'WC' ) ) {
			$owner_country = WC()->countries->get_base_country();
		}else {
			$owner_country = get_option('woocommerce_default_country');
		}

		if (isset($billing_invoice) && $billing_invoice == "y") {
			if (!$billing_tax_office && mb_strtolower($country) == 'gr' ) {
				$errors->add('required-field', apply_filters('woocommerce_checkout_required_field_notice', sprintf(__('%s is a required field.', $this->plugin_name), '<strong>' . esc_html(sprintf(__('Billing %s', $this->plugin_name), __('Tax office', $this->plugin_name))) . '</strong>'), __('Tax office', $this->plugin_name)));
			}
			if (!$billing_vat_id) {
				$errors->add('required-field', apply_filters('woocommerce_checkout_required_field_notice', sprintf(__('%s is a required field.', $this->plugin_name), '<strong>' . esc_html(sprintf(__('Billing %s', $this->plugin_name), __('VAT number', $this->plugin_name))) . '</strong>'), __('VAT number', $this->plugin_name)));
			}
			if (!$billing_activity) {
				$errors->add('required-field', apply_filters('woocommerce_checkout_required_field_notice', sprintf(__('%s is a required field.', $this->plugin_name), '<strong>' . esc_html(sprintf(__('Billing %s', $this->plugin_name), __('Business activity', $this->plugin_name))) . '</strong>'), __('Business activity', $this->plugin_name)));
			}
			if (!empty($billing_vat_id) && mb_strtolower($country) == 'gr' && get_option('webexpert_timologio_for_woocommerce_validate_aade')=="yes" && (empty($this->check_for_valid_vat_aade($billing_vat_id)) || $this->check_for_valid_vat_aade($billing_vat_id)=='false')) {
				$errors->add('invalid-field', apply_filters('woocommerce_checkout_required_field_notice', sprintf(__('%s is not valid VAT number.', $this->plugin_name), '<strong>' . esc_html(sprintf(__('Billing %s', $this->plugin_name), __('VAT number', $this->plugin_name))) . '</strong>'), __('VAT number', $this->plugin_name)));
			}

			if (!empty($billing_vat_id) && mb_strtolower($country) != mb_strtolower($owner_country) && get_option('webexpert_timologio_for_woocommerce_validate_vies')=="yes" && !$this->check_for_valid_vat($billing_vat_id,$this->filter_country($country))) {
				$errors->add('invalid-field', apply_filters('woocommerce_checkout_required_field_notice', sprintf(__('%s is not valid VAT number.', $this->plugin_name), '<strong>' . esc_html(sprintf(__('Billing %s', $this->plugin_name), __('VAT number', $this->plugin_name))) . '</strong>'), __('VAT number', $this->plugin_name)));
			}
		}
	}

	function woocommerce_checkout_order_processed( $order_id ) {
		$order = wc_get_order( $order_id );
		if ($order) {
			$order_billing_vat_id = $order->get_meta( '_billing_vat_id');
			if (!empty($order_billing_vat_id) && get_option('webexpert_timologio_for_woocommerce_validate_aade')=="yes" && apply_filters('webexpert_timologio_for_woocommerce_show_order_note',true)) {
				$check=$this->check_for_valid_vat_aade($order_billing_vat_id);
				if ($check!==false) {
					$order->add_order_note($check,0,false);
				}
			}
		}
	}

	function webexpert_timologio_for_woocommerce_add_woocommerce_found_customer_details($customer_data, $user_id, $type_to_load) {
		if ($type_to_load == 'billing') {
			$customer_data[$type_to_load . '_vat_id'] = get_user_meta($user_id, $type_to_load . '_vat_id', true);
			$customer_data[$type_to_load . '_tax_office'] = get_user_meta($user_id, $type_to_load . '_tax_office', true);
			$customer_data[$type_to_load . '_activity'] = get_user_meta($user_id, $type_to_load . '_activity', true);
		}
		return $customer_data;
	}

	function woo_add_cart_fee() {
		if (!$_POST || (is_admin() && !is_ajax())) {
			return;
		}
		if ( is_admin() && ! defined( 'DOING_AJAX' ) )
			return;

		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
			return;

		if (isset($_POST['post_data'])) {
			parse_str($_POST['post_data'], $post_data);
		} else {
			$post_data = $_POST;
		}

		$option_vies_exempt = get_option('webexpert_timologio_for_woocommerce_exempt_valid_vies');
		$post_data_billing_country=$this->filter_country($post_data['billing_country'] ?? '');

		//Πρώτη περίπτωση αποφορολόγησης (1) (VIES ENTOS EUROPIS EKTOS ELLADAS)
		if(isset($post_data['billing_vat_id']) && isset($post_data['billing_invoice']) && $post_data['billing_invoice'] == 'y' && in_array(mb_strtolower($post_data_billing_country), ['at','be','bg', 'cy', 'cz', 'de', 'dk', 'ee', 'es', 'fi', 'fr', 'hr','hu', 'ie',
				'it', 'ie', 'lt','lu', 'lv', 'mt', 'nl', 'pl', 'pt', 'ro', 'se', 'si', 'sk', 'xi'
			]) ) {
			if( $option_vies_exempt == "yes" && $this->check_for_valid_vat($post_data['billing_vat_id'], $post_data_billing_country)) {
				WC()->customer->set_is_vat_exempt(true);
				return;
			}

			WC()->customer->set_is_vat_exempt(false);
		}

		//Τέλος Πρώτης περίπτωσης αποφορολόγησης
		//Δεύτερη περίπτωση αποφορολόγησης (2) (TRITES XORES)

		if(isset($post_data['billing_vat_id']) && isset($post_data['billing_invoice']) && $post_data['billing_invoice'] == 'y' && !in_array(mb_strtolower($post_data_billing_country), ['at','be','bg', 'cy', 'cz', 'de', 'dk', 'ee', 'es', 'fi', 'fr', 'hr','hu', 'ie',
				'it', 'ie', 'lt','lu', 'lv', 'mt', 'nl', 'pl', 'pt', 'ro', 'se', 'si', 'sk', 'xi', 'gr', 'el'
			]) ) {
			if( get_option('webexpert_timologio_for_woocommerce_exempt_non_europe_country') == "yes") {
				WC()->customer->set_is_vat_exempt(true);
				return;
			}

			WC()->customer->set_is_vat_exempt(false);
		}
		//Τέλος δεύτερης περίπτωσης αποφορολόγησης (2)

		//Τρίτη περίπτωση αποφορολόγησης (3) (ΠΟΛ ΜΕ ΤΙΣ ΚΑΤΗΓΟΡΙΕΣ ΕΛΛΑΔΑ ΠΡΟΣ ΕΛΛΑΔΑ ΑΝ ΕΙΝΑΙ ΑΝΟΙΧΤΟ & ΕΧΕΙ ΒΑΛΕΙ ΚΑΤΗΓΟΡΙΕΣ ΚΑΙ ΕΧΕΙ ΕΠΙΛΕΞΕΙ CLASS TAX)
		$taxExemptFromCategories = false;
		if(isset($post_data['billing_vat_id']) && isset($post_data['billing_invoice']) && $post_data['billing_invoice'] == 'y' && isset($post_data['billing_39a']) && $post_data['billing_39a'] == '1' && in_array(mb_strtolower($post_data_billing_country),['gr', 'el'])) {
			if(get_option("webexpert_timologio_for_woocommerce_vat_exempt_auto_if_valid") == "yes" && is_array(get_option('webexpert_timologio_for_woocommerce_vat_exempt_categories')) && strlen(get_option("webexpert_timologio_for_woocommerce_vat_exempt_tax_class")) > 0 ) {
				$aadeResponse = $this->check_for_valid_vat_aade($post_data['billing_vat_id']);

				if(isset($aadeResponse['afm'])) {

					foreach ( WC()->cart->get_cart() as $cart_item ) {
						$product = wc_get_product($cart_item['data']->get_id());
						if(!$product) {
							continue;
						}

						$terms = [];
						if($product->is_type( 'variable' ) || $product->is_type('simple') ) {
							$terms = $product->get_category_ids();
							$newPrice =  wc_get_price_excluding_tax($product);
						} elseif($product->is_type('variation')) {
							$parentProduct = wc_get_product($product->get_parent_id());
							if(!$parentProduct) continue;
							$terms = $parentProduct->get_category_ids();
							$newPrice =  wc_get_price_excluding_tax($product);
						}

						$same = array_intersect($terms , get_option('webexpert_timologio_for_woocommerce_vat_exempt_categories'));
						if(count($same) > 0) {
							$cart_item['data']->set_tax_class(get_option('webexpert_timologio_for_woocommerce_vat_exempt_tax_class'));
							$taxExemptFromCategories = true;
							if(wc_prices_include_tax() == "yes" && isset($newPrice)) {
								$cart_item['data']->set_price($newPrice);
							}
						}
					}
				}
				if($taxExemptFromCategories) {
					WC()->cart->calculate_totals();
					return;
				}
			}

			WC()->customer->set_is_vat_exempt(false);
		}
		//Τέλος τρίτης περίπτωσης αποφορολόγησης

		//Τέταρτη περίπτωση αποφορολόγησης
		$taxExemptFromIslands = false;
		if(isset($post_data['billing_vat_id']) && isset($post_data['billing_invoice']) && $post_data['billing_invoice'] == 'y' && in_array(mb_strtolower($post_data_billing_country),['gr', 'el'
			]) && get_option("webexpert_timologio_for_woocommerce_enable_island_reduced_tax") == "yes" ) {
			$islandPostalCodes = [81110,81111,81101,83200,85302,83104,81300,81103,82100,82300,82102,81104,82103,83100,81107,85400,81108,81200,81100,81105,85300,81113,81109,82200,81106,83103,85301,81112,83101,83102,81102,8210,85300, 82150, 82132, 82131, 82104, 82101, 83400, 83302, 83301, 83300, 81500, 81401, 81400,81150, 81132, 81131, 85401, 85600];
			if(isset($post_data['billing_postcode'])) {
				if(in_array($post_data['billing_postcode'] , $islandPostalCodes)) {
					$aadeResponse = $this->check_for_valid_vat_aade($post_data['billing_vat_id']);

					if(isset($aadeResponse['afm'])) {
						foreach ( WC()->cart->get_cart() as $cart_item ) {
							$product = wc_get_product($cart_item['data']->get_id()); //$cart_item['data']->get_id());
							if(!$product) {
								continue;
							}
							$pid = !empty($cart_item['variation_id']) ? $cart_item['variation_id'] : $cart_item['product_id'];

							$product = wc_get_product($pid);
							$currentTaxPercentage = productTaxPercentage($product);

							if($currentTaxPercentage == 24 ){
								$newTax = get_option('webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_24');
							}elseif($currentTaxPercentage == 13) {
								$newTax = get_option('webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_13');
							}else if($currentTaxPercentage == 6){
								$newTax = get_option('webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_6');
							}

							if(isset($newTax)) {
								if(get_option("woocommerce_prices_include_tax") == "yes") {
									$cart_item['data']->set_price( wc_get_price_excluding_tax( $product ) + ( wc_get_price_excluding_tax( $product ) * getTaxPercentage($newTax) / 100 ) );
								}
								else{
									$cart_item['data']->set_tax_class($newTax);
								}
								$taxExemptFromIslands = true;
							}

						}
					}
				}
				if($taxExemptFromIslands)
				{
					WC()->session->set('_reduced_vat_for_island', true);
					WC()->cart->calculate_totals();
					return;
				}else {
					WC()->session->set('_reduced_vat_for_island', false);
                }
			}
		}
		WC()->customer->set_is_vat_exempt(false);

		//Τέλος τέταρτης περίπτωσης αποφορολόγησης
	}

	function filter_country($country) {
		if (strpos($country, ':') !== false) {
			$expl = explode(":", $country);
			$country = $expl[0];
		}

		if ($country == "GR") {
			$country = "EL";
		}
		return $country;
	}

	function check_for_valid_vat($vat_id, $country) {
		$vies = new Vies();
		$owner_country = get_option('woocommerce_default_country');
		$owner_country = $this->filter_country($owner_country);
		$country = $this->filter_country($country);

		$owner_vat_id=str_replace($owner_country,"",get_option('woocommerce_store_vat_id'));
		$vat_id=str_replace($country,"",$vat_id);

		if (false === $vies->getHeartBeat()->isAlive()) {
			wp_mail(get_option('admin_email'), __('Timologio for WooCommerce alert', $this->plugin_name), __('VIES is not available at the moment', $this->plugin_name));
			return -1;
		}
		try {
			$vatResult = $vies->validateVat(
				strtoupper($country),
				strtoupper($vat_id),
				strtoupper($owner_country),
				strtoupper($owner_vat_id)
			);
		} catch (Exception $e) {
			return false;
		}

		return $vatResult->isValid();
	}

	function check_if_aade_isalive($timeout = 3) {

		$response = wp_remote_get("https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2?WSDL");
		$status = wp_remote_retrieve_response_code($response);
		return $status === 200;
	}


	function check_for_valid_vat_aade($vat_id, $country=null) {
		$result = get_transient( "{$vat_id}_aade_check");
		if ( empty($result) || get_option('webexpert_timologio_for_woocommerce_debug_mode',null)=='yes' ) {
			if ($this->check_if_aade_isalive()===false) {
				if (get_option('webexpert_timologio_for_woocommerce_aade_down_mode','')=="yes") {
					return true;
				}
				wp_mail(get_option('admin_email'),__("AADE service is down",$this->plugin_name),__("AADE service is down! Please de-activate the VAT validation and VAT auto complete on Web Expert Timologio for WooCommerce",$this->plugin_name));
				return ['error'];
			}

			$envelope = '<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:ns1="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:ns2="http://rgwspublic2/RgWsPublic2Service" xmlns:ns3="http://rgwspublic2/RgWsPublic2">
	   <env:Header>
	      <ns1:Security>
	         <ns1:UsernameToken>
	            <ns1:Username>'. get_option('webexpert_timologio_for_woocommerce_aade_username') . '</ns1:Username>
	            <ns1:Password>' . get_option('webexpert_timologio_for_woocommerce_aade_password') .'</ns1:Password>
	         </ns1:UsernameToken>
	      </ns1:Security>
	   </env:Header>
	   <env:Body>
	      <ns2:rgWsPublic2AfmMethod>
	         <ns2:INPUT_REC>
	            <ns3:afm_called_by/>
	            <ns3:afm_called_for>'. $vat_id .'</ns3:afm_called_for>
	         </ns2:INPUT_REC>
	      </ns2:rgWsPublic2AfmMethod>
	   </env:Body>
	</env:Envelope>';
			$url = 'https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2?WSDL';

			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL, $url);
			curl_setopt($ch,CURLOPT_POST, true);
			curl_setopt($ch,CURLOPT_POSTFIELDS, $envelope);
			curl_setopt($ch,CURLOPT_TIMEOUT, 5);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: ',
					'Content-Length: ' . strlen($envelope))
			);

			$result = curl_exec($ch);
			$result = preg_replace('/(<\s*)\w+:/','$1',$result);
			$result = preg_replace('/(<\/\s*)\w+:/','$1',$result);

			if (get_option('webexpert_timologio_for_woocommerce_debug_mode',null)=='yes') {
				error_log($result);
			}

			if (!empty($result)) {
				try {
					$xml = new SimpleXMLElement($result);
					$returns=[];
					if (empty($xml->Body->rgWsPublic2AfmMethodResponse->result->rg_ws_public2_result_rtType->error_rec->error_code)) {
						foreach ($xml->Body->rgWsPublic2AfmMethodResponse->result->rg_ws_public2_result_rtType->basic_rec->children() as $k=>$v) {
							if (!empty($v))
								$returns[$k]=(string)$v;
						}

						if (!empty($xml->Body->rgWsPublic2AfmMethodResponse->result->rg_ws_public2_result_rtType->firm_act_tab)) {
							foreach ($xml->Body->rgWsPublic2AfmMethodResponse->result->rg_ws_public2_result_rtType->firm_act_tab->children() as $k=>$v) {
								if (!empty($v->firm_act_descr)) {
									if (!array_key_exists('activities',$returns)){
										$returns['activities']=[];
									}
									array_push($returns['activities'],(string)$v->firm_act_descr);
								}
							}
						}
						$result=$returns;
					}else {
						$result=false;
					}
					set_transient( "{$vat_id}_aade_check", is_array($result) ? $result : 'false', DAY_IN_SECONDS );
				}
				catch(Exception $e) {
					error_log($e->getMessage());
					$result=false;
				}
			}else {
				if (get_option('webexpert_timologio_for_woocommerce_aade_down_mode','')=="yes") {
					return true;
				}
				return false;
			}

		}
		return $result;
	}



	function add_my_account_my_orders_timologio_for_woocommerce_custom_action( $actions , $order )
	{
		$action_slug = 'specific_name';
		if($order->get_meta('webexpert_timologio_for_wc_invoice_finalized') == 'yes'){
			$link = null;
			if( strlen(strval($order->get_meta('webexpert_timologio_invoice_type_receipt'))) > 0 ) {
				$link = $order->get_meta('webexpert_timologio_invoice_type_receipt');
			}else if( strlen( strval($order->get_meta('webexpert_timologio_invoice_type_invoice'))) > 0 ) {
				$link = $order->get_meta('webexpert_timologio_invoice_type_invoice');
			}

			if(strlen(strval($order->get_meta('webexpert_timologio_for_wc_invoice_uploaded_file')) ) > 0 ) {
				$link = $order->get_meta( 'webexpert_timologio_for_wc_invoice_uploaded_file');
			}

			if($link) {
				$actions[$action_slug] = array(
					'url'  => $link,
					'name' => __('Document',$this->plugin_name),
				);
			}
		}
		return $actions;
	}

	function find_tax_class($rate) {
		$all_tax_rates = [];
		$tax_classes = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
		if ( !in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
			array_unshift( $tax_classes, '' );
		}
		foreach ( $tax_classes as $tax_class ) { // For each tax class, get all rates.
			$taxes = WC_Tax::get_rates_for_tax_class( $tax_class );
			$all_tax_rates = array_merge( $all_tax_rates, $taxes );
		}
		foreach ($all_tax_rates as $tax_rate) {
			if (floatval($tax_rate->tax_rate)==floatval(str_replace(",",'.',$rate)))
				return $tax_rate->tax_rate_class;
		}
		return '';
	}

	function set_invoice_option_on_checkout() {
		if (is_checkout()) {
			?>
			<script type="text/javascript">
                jQuery(document).ready(function($) {
                    var invoice_select_field = $('#billing_invoice');
                    var savedInvoiceOption = '<?php echo WC()->session->get('chosen_billing_invoice') ?? ''; ?>';
                    if (savedInvoiceOption) {
                        if (invoice_select_field.length>0) {
                            $('#billing_invoice').val(savedInvoiceOption).trigger('change');
                        }else {
                            $('input[type=radio][name=billing_invoice][value="' + savedInvoiceOption + '"]').prop('checked', true).change();
                        }
                    }
                });
			</script>
			<?php
		}
	}

	function get_tax_office_label_by_value($value) {
		$tax_offices = $this->get_tax_offices_from_json(); // Function from the previous example
		if (isset($tax_offices[$value])) {
			return $tax_offices[$value];
		}
		return $value;
	}

	function add_custom_field_to_formatted_billing_address($address, $order) {
		if (!empty($address['billing_tax_office'])) {
			$tax_office_label = $this->get_tax_office_label_by_value($address['billing_tax_office']);
            if (!empty($tax_office_label))
			    $address['billing_tax_office'] = $tax_office_label;
		}

		return $address;
	}
	function add_custom_field_to_order($order, $data) {
		if (WC()->session->get('_reduced_vat_for_island')) {
			$order->update_meta_data('_reduced_vat_for_island', 'true');
			$order->save();
		}
	}
	function add_custom_billing_fields_to_rest_api() {
		register_rest_field('customer', 'billing_vat_id', array(
			'get_callback' => [$this,'get_custom_billing_field'],
			'update_callback' => null,
			'schema' => null,
		));
		register_rest_field('customer', 'billing_tax_office', array(
			'get_callback' => [$this,'get_custom_billing_field'],
			'update_callback' => null,
			'schema' => null,
		));
		register_rest_field('customer', 'billing_activity', array(
			'get_callback' => [$this,'get_custom_billing_field'],
			'update_callback' => null,
			'schema' => null,
		));
	}

	function get_custom_billing_field($user, $field_name, $request) {
		return get_user_meta($user['id'], $field_name, true);
	}
}
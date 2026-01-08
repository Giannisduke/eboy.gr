<?php
add_action( 'woocommerce_shipping_init','webexpert_acs_courier_shipping' );
function webexpert_acs_courier_shipping() {

    if (!class_exists('WebExpert_ACS_Courier')) {
        class WebExpert_ACS_Courier extends WC_Shipping_Method {
            public function __construct($instance_id = 0) {
                $this->instance_id = absint($instance_id);
                $this->id = 'webexpert_acs_courier';
                $this->method_title = __('ACS Courier','class-acs-voucher-for-woocommerce');
                $this->method_description = __('Allows you to calculate shipping based on ACS web service','class-acs-voucher-for-woocommerce');
                $this->supports = array(
                    'shipping-zones',
                    'instance-settings',
                    'instance-settings-modal',
                );
                $this->title = __('ACS Courier','class-acs-voucher-for-woocommerce');
                $this->enabled = 'yes';
                $this->init();
            }

            function init() {
                $this->init_form_fields();
                $this->init_settings();

                $this->title      = $this->get_option( 'title' );
                $this->tax_status = $this->get_option( 'tax_status' );
                $this->cost       = $this->get_option( 'cost' );

                add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
            }

            function init_form_fields() {
                $this->instance_form_fields = array(
                    'title' => array(
                        'title'       => __('Title','acs-voucher-for-woocommerce'),
                        'type'        => 'text',
                        'description' => __('The display title on the page','acs-voucher-for-woocommerce'),
                        'default'     => __('Shipping by ACS','acs-voucher-for-woocommerce'),
                        'desc_tip' => true
                    ),
                    'tax_status' => array(
                        'title'   => __( 'Tax status', 'woocommerce' ),
                        'type'    => 'select',
                        'class'   => 'wc-enhanced-select',
                        'default' => 'taxable',
                        'options' => array(
                            'taxable' => __( 'Taxable', 'woocommerce' ),
                            'none'    => _x( 'None', 'Tax status', 'woocommerce' ),
                        ),
                    ),
                );
            }

            public function calculate_shipping($package = array()) {
	            global $woocommerce;

	            $postal_code = str_replace( " ", "", $package['destination']['postcode'] );
	            $address     = str_replace( " ", "", $package['destination']['address_1'] );
	            $city        = str_replace( " ", "", $package['destination']['city'] );

	            if ( empty( $postal_code ) && empty( $address ) && empty( $city ) ) {
		            return false;
	            }

	            $total_weight   = 0.0;
	            $weight_unit    = get_option( 'woocommerce_weight_unit' );
	            $dimension_unit = get_option( 'woocommerce_dimension_unit' );


	            foreach ( WC()->cart->get_cart() as $cart_item ) {
		            $product = $cart_item['data'];
		            $qty     = $cart_item['quantity'];

		            $volumetric_weight = 0.0;
		            if ( get_option( 'webexpert_acs_disable_dimensions_volumetric', '0' ) != "1" && $product->get_length() && $product->get_width() && $product->get_height() ) {
			            $length            = ( wc_get_dimension( str_replace( ",", ".", $product->get_length() ), 'cm', $dimension_unit ) );
			            $width             = ( wc_get_dimension( str_replace( ",", ".", $product->get_width() ), 'cm', $dimension_unit ) );
			            $height            = ( wc_get_dimension( str_replace( ",", ".", $product->get_height() ), 'cm', $dimension_unit ) );
			            $volumetric_weight = ( $length * $width * $height ) / 5000 * $qty;
		            }

		            $weight = 0.0;
		            if ( $product->get_weight() ) {
			            $weight = wc_get_weight( str_replace( ",", ".", $product->get_weight() ), 'kg', $weight_unit ) * $qty;
		            }

		            if ( $volumetric_weight > $weight ) {
			            $total_weight += $volumetric_weight;
		            } else {
			            $total_weight += $weight;
		            }
	            }

	            $total_weight = apply_filters( 'webexpert_acs_custom_cart_total_weight', $total_weight );

	            if ( $total_weight == 0 ) {
		            $total_weight = get_option( 'webexpert_acs_default_weight' );
	            }

	            $acs       = new ACS_Voucher_For_Woocommerce();
	            $acs_admin = new ACS_Voucher_For_Woocommerce_Admin( $acs->get_plugin_name(), $acs->get_version() );

	            $inaccessible = false;
	            if ( get_option( 'webexpert_acs_shipping_validate_by', 'address' ) == 'address' ) {
		            $result             = $acs_admin->webexpert_acs_validate_address( get_option( 'woocommerce_store_address' ), get_option( 'woocommerce_store_postcode' ), get_option( 'woocommerce_store_city' ) );
		            $Acs_Station_Origin = ! empty( $result ) && isset($result->Resolved_Station_ID) ? $result->Resolved_Station_ID : null;
		            if ( empty( $Acs_Station_Origin ) ) {
			            $result             = $acs_admin->webexpert_acs_validate_zip( get_option( 'woocommerce_store_postcode' ) );
			            $Acs_Station_Origin = $result->Station_ID;

		            }
	            } else {
		            $result             = $acs_admin->webexpert_acs_validate_zip( get_option( 'woocommerce_store_postcode' ) );
		            $Acs_Station_Origin = $result->Station_ID;

	            }

	            $result                  = $acs_admin->webexpert_acs_validate_zip( $postal_code );
	            $Acs_Station_Destination = $result->Station_ID;
	            if ( $result->Inaccessible_Area_Kind == "ΔΠ" ) {
		            $inaccessible = true;
	            }

	            $accounts = sizeof( get_option( 'webexpert_acs_company_id' ) );
				$final_cost=null;
	            for ( $i = 0; $i < $accounts; $i ++ ) {
		            $acsAccount = $i;
		            $service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';

		            $body = array(
			            'ACSAlias'           => 'ACS_Price_Calculation',
			            'ACSInputParameters' => [
				            'Company_ID'              => get_option( 'webexpert_acs_company_id' )[ $acsAccount ],
				            'Company_Password'        => get_option( 'webexpert_acs_company_password' )[ $acsAccount ],
				            'User_ID'                 => get_option( 'webexpert_acs_user_id' )[ $acsAccount ],
				            'User_Password'           => get_option( 'webexpert_acs_user_password' )[ $acsAccount ],
				            'Pickup_Date'             => date_i18n( 'Y-m-d' ),
				            'Billing_Code'            => get_option( 'webexpert_acs_billing_code' )[ $acsAccount ],
				            "Billing_Category"        => '2',
				            "Acs_Station_Origin"      => $Acs_Station_Origin,
				            "Acs_Station_Destination" => $Acs_Station_Destination,
				            "Weight"                  => floatval( str_replace( ",", ".", $total_weight ) ),
				            "Charge_Type"             => 2,
			            ]
		            );

                    $delivery_products = [];
                    if ( $inaccessible ) {
                        $delivery_products[] = 'REM';
                    }
                    $payment_method = WC()->session ? WC()->session->get('chosen_payment_method') : null;
                    if ( $payment_method === 'cod' ) {
                        $delivery_products[] = 'COD';
                    }
                    if ( ! empty( $delivery_products ) ) {
                        $body['ACSInputParameters']['Acs_Delivery_Products'] = implode(',', $delivery_products);
                    }

		            if ( get_option( 'webexpert_acs_debug', null ) == '1' ) {
			            file_put_contents( wp_upload_dir()['basedir'] . '/wc-logs/acs-voucher-for-woocommerce.log', PHP_EOL . "=== ACS Voucher for WooCommerce ===" . PHP_EOL, FILE_APPEND );
			            file_put_contents( wp_upload_dir()['basedir'] . '/wc-logs/acs-voucher-for-woocommerce.log', "=== Shipping Calculator Request ===" . PHP_EOL, FILE_APPEND );
			            file_put_contents( wp_upload_dir()['basedir'] . '/wc-logs/acs-voucher-for-woocommerce.log', json_encode( $body, JSON_UNESCAPED_UNICODE ) . PHP_EOL, FILE_APPEND );
		            }

		            $request = wp_remote_post( $service_url, [
			            'data_format' => 'body',
			            'method'      => 'POST',
			            'body'        => json_encode( $body ),
			            'headers'     => array(
				            "Content-Type" => "application/json",
				            "ACSApiKey"    => get_option( 'webexpert_acs_apikey' )[ $acsAccount ]
			            ),
		            ] );

		            if ( wp_remote_retrieve_response_code( $request ) != 200 ) {
			            return wp_remote_retrieve_response_code( $request ) . " - " . wp_remote_retrieve_response_message( $request );
		            }

		            if ( is_wp_error( $request ) ) {
			            return $request->get_error_message();
		            }

		            $response = wp_remote_retrieve_body( $request );

		            if ( get_option( 'webexpert_acs_debug', null ) == '1' ) {
			            file_put_contents( wp_upload_dir()['basedir'] . '/wc-logs/acs-voucher-for-woocommerce.log', PHP_EOL . "=== ACS Voucher for WooCommerce ===" . PHP_EOL, FILE_APPEND );
			            file_put_contents( wp_upload_dir()['basedir'] . '/wc-logs/acs-voucher-for-woocommerce.log', "=== Shipping Calculator Response ===" . PHP_EOL, FILE_APPEND );
			            file_put_contents( wp_upload_dir()['basedir'] . '/wc-logs/acs-voucher-for-woocommerce.log', $response . PHP_EOL, FILE_APPEND );
		            }

		            $obj = json_decode( $response );
		            if ( $obj->ACSExecution_HasError == false ) {
			            $cost = $obj->ACSOutputResponce->ACSValueOutput[0]->Total_Ammount;
			            if ( mb_strtolower( $this->get_option( 'tax_status' ) ) !== 'taxable' ) {
				            $cost += $obj->ACSOutputResponce->ACSValueOutput[0]->Total_Vat_Ammount;
			            }

						if ($cost>0 && empty($final_cost)) {
							$final_cost=$cost;
						}

						if ($final_cost>$cost) {
							$final_cost=$cost;
						}
		            }
	            }

	            $final_cost = apply_filters( "webexpert_acs_custom_cost_calculate", $final_cost, $total_weight, $inaccessible );

	            if ( $final_cost > 0 ) {
		            $this->add_rate(
			            array(
				            'id'      => $this->id,
				            'label'   => $this->title,
				            'cost'    => $final_cost,
				            'package' => $package,
				            'taxes'   => mb_strtolower( $this->get_option( 'tax_status' ) ) == 'taxable',
			            )
		            );
	            }
            }

            public function is_available($package) {
                if ($package['destination']['country'] == "GR") {
                    $is_available = true;
                } else {
                    $is_available = false;
                }

                $postal_code = str_replace(" ","",$package[ 'destination' ][ 'postcode' ]);
                $address = str_replace(" ","",$package[ 'destination' ][ 'address_1' ]);
                $city = str_replace(" ","",$package[ 'destination' ][ 'city' ]);

                if (empty($postal_code) && empty($address) && empty($city)) {
                    $is_available=false;
                }

                return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this);
            }
        }
    }

    add_filter( 'woocommerce_shipping_methods','add_acs_shipping_method' );
    if (!function_exists('add_acs_shipping_method')) {
        function add_acs_shipping_method( $methods ) {
            $methods['webexpert_acs_courier'] = 'WebExpert_ACS_Courier';
            return $methods;
        }
    }
}

<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WC_Piraeusbank_Gateway extends WC_Payment_Gateway {
	public $installments_range;
	public function __construct() {
		$this->id = 'piraeusbank_gateway';
		$this->order_button_text = __( 'Proceed for payment', 'webexpert-woocommerce-piraeus-payment-gateway' );
		$this->icon = apply_filters('piraeusbank_icon', WebExpert_Epay_Payments::plugin_url().'/assets/img/PB_blue_GR.png');
		$this->has_fields = ($this->get_option('pb_installments') > 1 ? true : false);
		$this->supports           = array('products','subscriptions');
		$this->method_description = __('Piraeus Bank Gateway allows you to accept payment through various channels such as Maestro, Mastercard and Visa cards.', 'webexpert-woocommerce-piraeus-payment-gateway');
		$this->method_title = __('Piraeus Bank Gateway','webexpert-woocommerce-piraeus-payment-gateway');
		$this->init_form_fields();
		$this->init_settings();
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->installments_range = array();
		add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_api_wc_' . $this->id, array($this, 'check_piraeusbank_response'));
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_order_payment_type_meta_data' ), 10, 2 );
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'display_transaction_type_order_item_totals'), 10, 3 );
		add_action( 'woocommerce_admin_order_data_after_billing_address',  array( $this, 'display_payment_type_order_edit_pages'), 10, 1 );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	public function admin_options() {
		echo '<h3>' . __('Piraeus Bank Gateway', 'webexpert-woocommerce-piraeus-payment-gateway') . '</h3>';
		echo '<p>' . __('Piraeus Bank Gateway allows you to accept payment through various channels such as Maestro, Mastercard and Visa cards.', 'webexpert-woocommerce-piraeus-payment-gateway') . '</p>';
		echo '<table class="form-table">';
		$this->generate_settings_html();
		echo '</table>';
	}

	function check_transaction($merchantReference) {
		$soapUrl = "https://paycenter.piraeusbank.gr/services/paymentgateway.asmx";
		$xml_post_string = '<?xml version="1.0" encoding="utf-8"?>
	    <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
	      <soap12:Body>
	        <ProcessTransaction xmlns="http://piraeusbank.gr/paycenter">
	          <TransactionRequest xmlns="http://piraeusbank.gr/paycenter/1.0">
	            <Header>
	              <RequestType>FOLLOW_UP</RequestType>
	              <MerchantInfo>
	                <AcquirerID>GR014</AcquirerID>
	                <MerchantID>'.$this->get_option('pb_PayMerchantId').'</MerchantID>
	                <PosID>'.$this->get_option('pb_PosId').'</PosID>
	                <ChannelType>3DSecure</ChannelType>
	                <User>'.$this->get_option('pb_Username').'</User>
	                <Password>'.hash('md5', $this->get_option('pb_Password')).'</Password>
	              </MerchantInfo>
	            </Header>
	            <Body>
	                <TransactionInfo>
	                    <MerchantReference>'.$merchantReference.'</MerchantReference>
	                    <TransactionReferenceID xsi:nil="true" />
	                    <EntryType xsi:nil="true" />
	                    <CurrencyCode xsi:nil="true" />
	                    <Amount xsi:nil="true" />
	                    <Installments xsi:nil="true" />
	                    <ExpirePreauth xsi:nil="true" />
	                    <TipAmount xsi:nil="true" />
	                    <Bnpl xsi:nil="true" />
	                    <SessionKey xsi:nil="true" /><AuthInfo xsi:nil="true" />
	                </TransactionInfo>
	            </Body>=
	          </TransactionRequest>
	        </ProcessTransaction>
	      </soap12:Body>
	    </soap12:Envelope>';

		$headers = array(
			"POST /services/paymentgateway.asmx HTTP/1.1",
			"Host: ".$_SERVER['SERVER_NAME']."",
			"Content-Type: application/soap+xml; charset=utf-8",
			"Content-Length: ".strlen($xml_post_string)
		);

		$url = $soapUrl;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$response = curl_exec($ch);

		$xml = simplexml_load_string($response);
		$xml->registerXPathNamespace("soap", "http://www.w3.org/2003/05/soap-envelope");
		return $xml->xpath('//soap:Body')[0]->ProcessTransactionResponse->TransactionResponse->Body->TransactionInfo;
	}

	function payment_fields() {
		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}

		if (is_wc_endpoint_url('order-pay')) {
			$order_id = get_query_var('order-pay');
			$order = wc_get_order($order_id);
			$cart_total = $order->get_total();
		} else {
			$cart_total = WC()->cart->get_total('');
		}

		$this->calculate_installments($cart_total);

		if ($this->get_option('pb_installments')>1) {

			if (is_array($this->installments_range) && sizeof($this->installments_range)>1) {
				echo "<div class='form-row form-row-wide'><label>".__('Installments', 'webexpert-woocommerce-piraeus-payment-gateway')."<span class='required'>*</span></label>
                	<select name='installments' id='installments'>";
				if (class_exists("Webexpert_Interest_Bearing_Installments")) {
					$engine=new Webexpert_Interest_Bearing_Installments();
					$engine_public = new Webexpert_Interest_Bearing_Installments_Public($engine->get_plugin_name(), $engine->get_version());
					$rows = get_field('we_installments', 'option');
					foreach ($rows as $row) :
						$installments = $row['installment_no'];
						if (is_array($this->installments_range) && sizeof($this->installments_range) > 0) {
							if (!in_array($installments, $this->installments_range)) {
								continue;
							}
						}
						$calculate_price = $engine_public::calculate_rates($cart_total, $installments);
						if ($installments >= 1) {
							echo "<option value='$installments' data-installment='$installments' data-fee='" . $calculate_price['fees'] . "' data-total='" . round($calculate_price['total_price'],2). "'>".
							     sprintf( esc_html( _n( 'No installments', '%d installments from %s per month', $installments, 'webexpert-woocommerce-piraeus-payment-gateway'  ) ), $installments, wc_price($calculate_price['monthly_installment']))."</option>";
						}
					endforeach;
				}else {
					foreach ($this->installments_range as $num) {
						echo "<option value='$num'>".sprintf( esc_html( _n( 'No installments', '%d installments from %s per month', $num, 'webexpert-woocommerce-piraeus-payment-gateway'  ) ), $num, wc_price(round($cart_total/$num,2)))."</option>";
					}
				}
				echo "</select>
                	</div>
                	<div class='clear'></div>";
			}else {
				echo "<input type='hidden' name='installments' id='installments' value='".$this->installments_range[0]."'>";
			}
		}
	}

	function save_order_payment_type_meta_data( $order, $data ) {
		if ( $data['payment_method'] === $this->id && isset($_POST['installments']) && $_POST['installments']>1 )
			$order->update_meta_data('_installments', esc_attr($_POST['installments']) );
	}

	public function display_payment_type_order_edit_pages( $order ){
		if( $this->id === $order->get_payment_method() && $order->get_meta('_installments') ) {
			echo '<p><strong>'.__('Installments','webexpert-woocommerce-piraeus-payment-gateway').':</strong> ' . $order->get_meta('_installments') . '</p>';
		}
	}

	public function display_transaction_type_order_item_totals( $total_rows, $order, $tax_display ){
		if( is_a( $order, 'WC_Order' ) && $order->get_meta('_installments') ) {
			$new_rows = []; // Initializing
			foreach( $total_rows as $total_key => $total_values ) {
				$new_rows[$total_key] = $total_values;
				if( $total_key === 'payment_method' ) {
					$new_rows['payment_type'] = [
						'label' => __("Installments", 'webexpert-woocommerce-piraeus-payment-gateway') . ':',
						'value' => $order->get_meta('_installments'),
					];
				}
			}
			$total_rows = $new_rows;
		}
		return $total_rows;
	}

	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->get_option( 'instructions' ) && ! $sent_to_admin && $this->id === $order->get_payment_method() && $order->has_status( 'processing' ) ) {
			echo wp_kses_post( wpautop( wptexturize( $this->get_option( 'instructions' ) ) ) . PHP_EOL );
		}
	}

	function calculate_installments($cart_total) {
		if (is_admin()) {
			return;
		}

		$json_array = json_decode("{".$this->get_option('pb_installments_options_array')."}",true);
		$flip_json_array=array_flip($json_array);

		$possible_installments=null;
		foreach ($flip_json_array as $k => $arr) {
			$expl_keys = explode("-", $arr);
			if (is_array($expl_keys)) {
				if ($cart_total >= floatval(str_replace(",",".",$expl_keys[0])) && $cart_total <= floatval(str_replace(",",".",$expl_keys[1]))) {
					$possible_installments = $k;
					break;
				}
			}
		}

		if (!$possible_installments) {
			if ($this->get_option('pb_installments')>1) {
				$possible_installments="1-{$this->get_option('pb_installments')}";
			}
		}

		$expl_installments = explode("-", $possible_installments);

		if (is_array($expl_installments) && sizeof($expl_installments) > 1) {
			for ($i = $expl_installments[0]; $i <= $expl_installments[1]; $i++) {
				if (!in_array($i,$this->installments_range))
					array_push($this->installments_range, $i);
			}
		} else {
			if ($possible_installments>1) {
				for ($i = 1; $i <= $possible_installments; $i++) {
					if (!in_array($i,$this->installments_range))
						array_push($this->installments_range, $i);
				}
			}else {
				array_push($this->installments_range, $possible_installments);
			}
		}

	}

	function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'type' => 'checkbox',
				'label' => __('Enable Piraeus Bank Gateway', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'description' => __('Enable or disable the gateway.', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'desc_tip' => true,
				'default' => 'yes'
			),
			'environment' => array(
				'title' => __('Test Environment', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'type' => 'checkbox',
				'description' => __('Enables test environment for Piraeus Bank Gateway for test cases.', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'desc_tip' => true,
				'default' => 'yes'
			),
			'title' => array(
				'title' => __('Title', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'default' => __('Piraeus Bank Gateway', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'desc_tip' => true
			),
			'description' => array(
				'title' => __('Description', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'type' => 'textarea',
				'description' => __('This controls the description which the user sees during checkout.', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'default' => __('Pay Via Piraeus Bank: Accepts  Mastercard, Visa cards and etc.', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'desc_tip' => true
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'webexpert-woocommerce-piraeus-payment-gateway' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'webexpert-woocommerce-piraeus-payment-gateway' ),
				'default'     => '', // Empty by default
				'desc_tip'    => true,
			),
			'pb_PayMerchantId' => array(
				'title' => __('Merchant ID', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'type' => 'text',
				'description' => __('Enter Your Piraeus Bank Merchant ID', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'default' => '',
				'desc_tip' => true
			),
			'pb_AcquirerId' => array(
				'title' => __('Acquirer ID', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'type' => 'text',
				'description' => __('Enter Your Piraeus Bank Acquirer ID', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'default' => '',
				'desc_tip' => true
			),
			'pb_PosId' => array(
				'title' => __('POS ID', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'type' => 'text',
				'description' => __('Enter your Piraeus Bank POS ID', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'default' => '',
				'desc_tip' => true
			), 'pb_Username' => array(
				'title' => __('Username', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'type' => 'text',
				'description' => __('Enter your Piraeus Bank Username', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'default' => '',
				'desc_tip' => true
			), 'pb_Password' => array(
				'title' => __('Password', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'type' => 'text',
				'description' => __('Enter your Piraeus Bank Password', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'default' => '',
				'desc_tip' => true
			), 'pb_authorize' => array(
				'title' => __('Pre-Authorize', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'type' => 'checkbox',
				'label' => __('Enable to capture preauthorized payments', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'default' => 'yes',
				'description' => __('Default payment method is Purchase, enable for Pre-Authorized payments. You will then need to accept them from Peiraeus Bank AdminTool', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'desc_tip' => true
			),
			'pb_installments' => array(
				'title' => __('Max Installments', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'type' => 'number',
				'description' => __('Number of installments, 1 for one time payment', 'webexpert-woocommerce-piraeus-payment-gateway')
			),
			'pb_installments_options_array' => array(
				'title' => __('Installments', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'type' => 'textarea',
				'description' => '<a id="installments_validator_piraeus" href="#validate">'.__('Validate', 'webexpert-woocommerce-piraeus-payment-gateway').'</a><br><br>'.__("Set rules of installments. Please put one rule per line.<br><strong>Rule format</strong><br>\"{amount}-{amount}\":\"{min_installments} - {max_installments}\",<br>\"{amount}-{amount}\":\"{installments}\"<br><br><strong>Example (with range)</strong><br>\"0-100\":\"2-4\", (From 0 to 100 cart total, 2 to 4 installments)<br>\"101-300\":\"5-8\" (From 101 to 300 cart total, 5 to 8 installments)<br><br><strong>Example (without range)</strong><br>\"0-100\":\"4\", (From 0 to 100 cart total, 4 installments)<br>\"101-300\":\"8\" (From 101 to 300 cart total, 8 installments)<br><br><strong>Important:</strong> If cart total is not in range, max installments will be used<br><strong>Important:</strong> Please do not leave any extra spaces.", 'webexpert-woocommerce-piraeus-payment-gateway')
			),
			'pb_transactions_log' => array(
				'title' => __('Log', 'webexpert-woocommerce-piraeus-payment-gateway'),
				'type' => 'textarea',
				'description' => __("Showing 20 latest transactions", 'webexpert-woocommerce-piraeus-payment-gateway')
			)
		);
	}

	function itu_e164($phone,$country = null) {
		$country_codes = array('AC' => '247', 'AD' => '376', 'AE' => '971', 'AF' => '93', 'AG' => '1268', 'AI' => '1264', 'AL' => '355', 'AM' => '374', 'AO' => '244', 'AQ' => '672', 'AR' => '54', 'AS' => '1684', 'AT' => '43', 'AU' => '61', 'AW' => '297', 'AX' => '358', 'AZ' => '994', 'BA' => '387', 'BB' => '1246', 'BD' => '880', 'BE' => '32', 'BF' => '226', 'BG' => '359', 'BH' => '973', 'BI' => '257', 'BJ' => '229', 'BL' => '590', 'BM' => '1441', 'BN' => '673', 'BO' => '591', 'BQ' => '599', 'BR' => '55', 'BS' => '1242', 'BT' => '975', 'BW' => '267', 'BY' => '375', 'BZ' => '501', 'CA' => '1', 'CC' => '61', 'CD' => '243', 'CF' => '236', 'CG' => '242', 'CH' => '41', 'CI' => '225', 'CK' => '682', 'CL' => '56', 'CM' => '237', 'CN' => '86', 'CO' => '57', 'CR' => '506', 'CU' => '53', 'CV' => '238', 'CW' => '599', 'CX' => '61', 'CY' => '357', 'CZ' => '420', 'DE' => '49', 'DJ' => '253', 'DK' => '45', 'DM' => '1767', 'DO' => '1809', 'DO' => '1829', 'DO' => '1849', 'DZ' => '213', 'EC' => '593', 'EE' => '372', 'EG' => '20', 'EH' => '212', 'ER' => '291', 'ES' => '34', 'ET' => '251', 'EU' => '388', 'FI' => '358', 'FJ' => '679', 'FK' => '500', 'FM' => '691', 'FO' => '298', 'FR' => '33', 'GA' => '241', 'GB' => '44', 'GD' => '1473', 'GE' => '995', 'GF' => '594', 'GG' => '44', 'GH' => '233', 'GI' => '350', 'GL' => '299', 'GM' => '220', 'GN' => '224', 'GP' => '590', 'GQ' => '240', 'GR' => '30', 'GT' => '502', 'GU' => '1671', 'GW' => '245', 'GY' => '592', 'HK' => '852', 'HN' => '504', 'HR' => '385', 'HT' => '509', 'HU' => '36', 'ID' => '62', 'IE' => '353', 'IL' => '972', 'IM' => '44', 'IN' => '91', 'IO' => '246', 'IQ' => '964', 'IR' => '98', 'IS' => '354', 'IT' => '39', 'JE' => '44', 'JM' => '1876', 'JO' => '962', 'JP' => '81', 'KE' => '254', 'KG' => '996', 'KH' => '855', 'KI' => '686', 'KM' => '269', 'KN' => '1869', 'KP' => '850', 'KR' => '82', 'KW' => '965', 'KY' => '1345', 'KZ' => '7', 'LA' => '856', 'LB' => '961', 'LC' => '1758', 'LI' => '423', 'LK' => '94', 'LR' => '231', 'LS' => '266', 'LT' => '370', 'LU' => '352', 'LV' => '371', 'LY' => '218', 'MA' => '212', 'MC' => '377', 'MD' => '373', 'ME' => '382', 'MF' => '590', 'MG' => '261', 'MH' => '692', 'MK' => '389', 'ML' => '223', 'MM' => '95', 'MN' => '976', 'MO' => '853', 'MP' => '1670', 'MQ' => '596', 'MR' => '222', 'MS' => '1664', 'MT' => '356', 'MU' => '230', 'MV' => '960', 'MW' => '265', 'MX' => '52', 'MY' => '60', 'MZ' => '258', 'NA' => '264', 'NC' => '687', 'NE' => '227', 'NF' => '672', 'NG' => '234', 'NI' => '505', 'NL' => '31', 'NO' => '47', 'NP' => '977', 'NR' => '674', 'NU' => '683', 'NZ' => '64', 'OM' => '968', 'PA' => '507', 'PE' => '51', 'PF' => '689', 'PG' => '675', 'PH' => '63', 'PK' => '92', 'PL' => '48', 'PM' => '508', 'PR' => '1787', 'PR' => '1939', 'PS' => '970', 'PT' => '351', 'PW' => '680', 'PY' => '595', 'QA' => '974', 'QN' => '374', 'QS' => '252', 'QY' => '90', 'RE' => '262', 'RO' => '40', 'RS' => '381', 'RU' => '7', 'RW' => '250', 'SA' => '966', 'SB' => '677', 'SC' => '248', 'SD' => '249', 'SE' => '46', 'SG' => '65', 'SH' => '290', 'SI' => '386', 'SJ' => '47', 'SK' => '421', 'SL' => '232', 'SM' => '378', 'SN' => '221', 'SO' => '252', 'SR' => '597', 'SS' => '211', 'ST' => '239', 'SV' => '503', 'SX' => '1721', 'SY' => '963', 'SZ' => '268', 'TA' => '290', 'TC' => '1649', 'TD' => '235', 'TG' => '228', 'TH' => '66', 'TJ' => '992', 'TK' => '690', 'TL' => '670', 'TM' => '993', 'TN' => '216', 'TO' => '676', 'TR' => '90', 'TT' => '1868', 'TV' => '688', 'TW' => '886', 'TZ' => '255', 'UA' => '380', 'UG' => '256', 'UK' => '44', 'US' => '1', 'UY' => '598', 'UZ' => '998', 'VA' => '379', 'VA' => '39', 'VC' => '1784', 'VE' => '58', 'VG' => '1284', 'VI' => '1340', 'VN' => '84', 'VU' => '678', 'WF' => '681', 'WS' => '685', 'XC' => '991', 'XD' => '888', 'XG' => '881', 'XL' => '883', 'XN' => '857', 'XN' => '858', 'XN' => '870', 'XP' => '878', 'XR' => '979', 'XS' => '808', 'XT' => '800', 'XV' => '882', 'YE' => '967', 'YT' => '262', 'ZA' => '27', 'ZM' => '260', 'ZW' => '263');
		$default_country = get_option('woocommerce_default_country');
		if (strpos($default_country, ':') !== false) {
			$expl=explode(":",$default_country);
			$default_country=$expl[0];
		}
		$default_country_code = $country_codes[$default_country];
		$numbers = [$phone => $country];
		foreach ($numbers as $n => $c) {
			$n = preg_replace("/\([0-9]+?\)/", "", $n);
			$n = preg_replace("/[^0-9]/", "", $n);
			$n = ltrim($n, '0');
			if (array_key_exists($c, $country_codes)) {
				$pfx = $country_codes[$c];
			} else {
				$pfx = $default_country_code;
			}
			if (!preg_match('/^' . $pfx . '/', $n)) {
				$n = $pfx .'-'. $n;
			}

			if (strpos($n,'-')===false) {
				$n=substr_replace( $n, "-", 2, 0 );
			}
			return $n;
		}
	}

	function generate_piraeusbank_form($order_id) {
		global $wpdb;

		if ($wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . "piraeusbank_transactions'") !== $wpdb->prefix . 'piraeusbank_transactions') {
			$query = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'piraeusbank_transactions (id int(11) unsigned NOT NULL AUTO_INCREMENT, merch_ref varchar(50) not null, trans_ticket varchar(32) not null , timestamp datetime default null, PRIMARY KEY (id))';
			$wpdb->query($query);
		}

		$order = wc_get_order($order_id);

		if ($this->get_option('pb_authorize') == "yes") {
			$requestType = '00';
			$ExpirePreauth = '30';
		} else {
			$requestType = '02';
			$ExpirePreauth = '0';
		}
		// installments
		if ($this->get_option('pb_installments')>1) {
			$installments=intval($_GET['installments']);
			$max_installments=$this->get_option('pb_installments');

			if ($installments>$max_installments) {
				$installments=$max_installments;
			}
		}else {
			$installments=1;
		}

		$currency=get_woocommerce_currency();
		$currencyCode=array('ALL'=>"008",'AUD'=>"036",'CAD'=>"124",'CNY'=>"156",'HRK'=>"191",'CZK'=>"203",'DKK'=>"208",'INR'=>"356",'ILS'=>"376",'NOK'=>"578",'RUB'=>"643",'SEK'=>"752",'CHF'=>"756",'EGP'=>"818",'GBP'=>"826",'USD'=>"840",'RSD'=>"941",'RON'=>"946",'TRY'=>"949",'BGN'=>"975",'EUR'=>"978",'UAH'=>"980",'PLN'=>"985",'BRL'=>"986",'AED'=>"784",'ARS'=>"032",'BYR'=>"974",'CLP'=>"152",'COP'=>"170");
		$MerchantReference=uniqid("$order_id-");
		try {
			$opts = [
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true,
				],
			];

			$context = stream_context_create($opts);
			$soap = new SoapClient("https://paycenter.piraeusbank.gr/services/tickets/issuer.asmx?WSDL", [
				'stream_context' => $context,
				'cache_wsdl' => WSDL_CACHE_NONE,
				'trace' => 1,
				'exceptions' => true
			]);

			$billing_address=self::split(preg_replace("/[^\p{L}\p{N}\p{Z}_]/u", '', $order->get_billing_address_1()),50);
			$shipping_address=self::split(preg_replace("/[^\p{L}\p{N}\p{Z}_]/u", '',($order->get_shipping_address_1() ? $order->get_shipping_address_1() : $order->get_billing_address_1())),50);

			$ticketRequest = array(
				'Username' => $this->get_option('pb_Username'),
				'Password' => hash('md5', $this->get_option('pb_Password')),
				'MerchantId' => $this->get_option('pb_PayMerchantId'),
				'PosId' => apply_filters('webexpert_custom_pos_id',$this->get_option('pb_PosId')),
				'AcquirerId' => $this->get_option('pb_AcquirerId'),
				'MerchantReference' => $MerchantReference,
				'RequestType' => $requestType,
				'ExpirePreauth' => $ExpirePreauth,
				'Amount' => $order->get_total(),
				'CurrencyCode' => $currencyCode[$currency],
				'Installments' => $installments,
				'Bnpl' => 0,
				'Parameters' => '',
				'BillAddrCity'=>mb_substr($order->get_billing_city(),0,50),
				'BillAddrCountry'=>self::convert_alpha_2_to_code($order->get_billing_country()),
				'BillAddrLine1'=> $billing_address[0] ?? '',
				'BillAddrLine2'=> $billing_address[1] ?? '',
				'BillAddrLine3'=> $billing_address[2] ?? '',
				'BillAddrPostCode'=>$order->get_billing_postcode(),
				'BillAddrState'=>$order->get_billing_country()=='GR' ? $order->get_billing_state() : '',
				'ShipAddrCity'=>mb_substr(!empty($order->get_shipping_city()) ? $order->get_shipping_city() : $order->get_billing_city() ,0,50),
				'ShipAddrCountry'=>self::convert_alpha_2_to_code($order->get_shipping_country()),
				'ShipAddrLine1'=> $shipping_address[0] ?? '',
				'ShipAddrLine2'=> $shipping_address[1] ?? '',
				'ShipAddrLine3'=> $shipping_address[2] ?? '',
				'ShipAddrPostCode'=>$order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode(),
				'ShipAddrState'=>$order->get_billing_country()=='GR' ? ($order->get_shipping_state() ? $order->get_shipping_state() : $order->get_billing_state()) : '',
				'CardholderName'=>apply_filters('webexpert_piraeusbank_custom_cardholder',self::attempt_transliteration("{$order->get_billing_first_name()} {$order->get_billing_last_name()}")),
				'Email'=>$order->get_billing_email(),
				'HomePhone'=>$this->itu_e164(apply_filters('webexpert_piraeusbank_custom_home_phone',$order->get_billing_phone()),$order->get_billing_country()),
				'MobilePhone'=>$this->itu_e164(apply_filters('webexpert_piraeusbank_custom_mobile_phone',$order->get_billing_phone()),$order->get_billing_country()),
				'WorkPhone'=> $this->itu_e164(apply_filters('webexpert_piraeusbank_custom_work_phone',$order->get_billing_phone()),$order->get_billing_country()),
			);

			if ( in_array( 'webexpert-greek-states-and-cities/webexpert-greek-states-and-cities.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || in_array( 'web-expert-greek-states-based-shipping/webexpert-greek-states-and-cities.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$ticketRequest['BillAddrState']=self::prefecture_to_state($order->get_billing_state());
				$ticketRequest['ShipAddrState']=self::prefecture_to_state($order->get_shipping_state() ? $order->get_shipping_state() : $order->get_billing_state());
			}

			if ($order->get_billing_country()!="GR") {
				unset($ticketRequest['BillAddrState']);
				unset($ticketRequest['ShipAddrState']);
			}

			$xml = array(
				'Request' => $ticketRequest
			);
			$oResult = $soap->IssueNewTicket($xml);
			if ($oResult->IssueNewTicketResult->ResultCode == 0) {
				$wpdb->insert($wpdb->prefix . 'piraeusbank_transactions', array('trans_ticket' => $oResult->IssueNewTicketResult->TranTicket, 'merch_ref' => $MerchantReference, 'timestamp' => current_time('mysql', 1)));
				wc_enqueue_js('
				jQuery.blockUI({
						message: "' . esc_js(apply_filters('webexpert_piraeusbank_custom_javascript_message',__('Thank you for your order. We are now redirecting you to Piraeus Bank to make payment.', 'webexpert-woocommerce-piraeus-payment-gateway'))) . '",
						baseZ: 99999,
						overlayCSS:
						{
							background: "#fff",
							opacity: 0.6
						},
						css: {
                            width:          "100%", 
                            "max-width":        "600px",
                            padding:        "20px",
                            zindex:         "9999999",
                            left:         "50%",
                            transform: "translate(-50%, 0)",
                            textAlign:      "center",
                            color:          "#555",
                            border:         "3px solid #aaa",
                            backgroundColor:"#fff",
                            cursor:         "wait",
                            lineHeight:        "24px",
                        }
					});
				jQuery("#submit_pb_payment_form").click();
			');
				$langcodes_allowed=array("el-GR","en-US","ru-RU","de-DE");
				$LanCode = get_locale();
				if ($LanCode=="el")
					$LanCode="el-GR";
				if (!in_array($LanCode,$langcodes_allowed)) {
					$LanCode = "en-US";
				}
				return '<form action="' . esc_url("https://paycenter.piraeusbank.gr/redirection/pay.aspx") . '" method="post" id="pb_payment_form" target="_top">
						<input type="hidden" id="AcquirerId" name="AcquirerId" value="' . esc_attr($this->get_option('pb_AcquirerId')) . '"/>
						<input type="hidden" id="MerchantId" name="MerchantId" value="' . esc_attr($this->get_option('pb_PayMerchantId')) . '"/>
						<input type="hidden" id="PosID" name="PosID" value="' . esc_attr(apply_filters('webexpert_custom_pos_id',$this->get_option('pb_PosId'))). '"/>
						<input type="hidden" id="User" name="User" value="' . esc_attr($this->get_option('pb_Username')) . '"/>
						<input type="hidden" id="LanguageCode"  name="LanguageCode" value="' . $LanCode . '"/>
						<input type="hidden" id="MerchantReference" name="MerchantReference"  value="' . esc_attr($MerchantReference) . '"/>
					<div class="payment_buttons">
						<input type="submit" class="button alt" id="submit_pb_payment_form" value="' . __('Pay via Pireaus Bank', 'webexpert-woocommerce-piraeus-payment-gateway') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url_raw()) . '">' . __('Cancel order &amp; restore cart', 'webexpert-woocommerce-piraeus-payment-gateway') . '</a>

					</div>
					<script type="text/javascript">
					jQuery(".payment_buttons").hide();
					</script>
				</form>';
			} else {
				echo __('An error occurred, please contact system administrator', 'webexpert-woocommerce-piraeus-payment-gateway');
				$resultCode=$oResult->IssueNewTicketResult->ResultCode;
				echo __('Result code','webexpert-woocommerce-piraeus-payment-gateway').'): '.$resultCode;
				if ($resultCode==1040 || $resultCode==1041) {
					echo __('Error validating IP address', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($resultCode==100) {
					echo __('Authentication Error. Wrong username or/and password', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($resultCode==1003) {
					echo __('Wrong merchant ID', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($resultCode==1019) {
					echo __('Too many installments asked.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($resultCode==1045) {
					echo __('Duplicate transaction references are not allowed.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($resultCode==1048) {
					echo __('Transaction already processed and completed', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($resultCode==1802) {
					echo __('Wrong amount value', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($resultCode==500 || $resultCode==501) {
					echo __('Communication problem with the transaction processing system', 'webexpert-woocommerce-piraeus-payment-gateway');
				}
			}
		} catch (SoapFault $fault) {
			$order->add_order_note(__('Error' . $fault, ''));
			echo __('Error' . $fault, '');
		}
	}

	protected function check_settings() {
		return !empty($this->get_option('pb_PayMerchantId')) && !empty($this->get_option('pb_PosId')) && !empty($this->get_option('pb_Username')) && !empty($this->get_option('pb_Password'));
	}
	function process_payment($order_id) {
		$is_store_api_request = method_exists(WC(), 'is_store_api_request') && WC()->is_store_api_request();

		if(!$this->check_settings()) {
			$message = sprintf(__('%1$s is not properly configured.', 'webexpert-woocommerce-piraeus-payment-gateway'), $this->method_title);

			if($is_store_api_request) {
				throw new Exception($message);
			}

			wc_add_notice($message, 'error');

			return array(
				'result'   => 'failure',
				'messages' => $message
			);
		}

		if($is_store_api_request || is_ajax()) {
			$installments=0;
			if (isset($_POST['installments'])) {
				$installments=intval($_POST['installments']);
			}

			$order = wc_get_order($order_id);
			if ($installments>1) {
				return array(
					'result' => 'success',
					'redirect' => add_query_arg('installments',$installments,$order->get_checkout_payment_url(true))
				);
			}else {
				return array(
					'result' => 'success',
					'redirect' => $order->get_checkout_payment_url(true)
				);
			}
		}
		$this->receipt_page($order_id);
	}

	function receipt_page($order) {
		echo $this->generate_piraeusbank_form($order);
	}

	function check_piraeusbank_response() {
		global $woocommerce;
		global $wpdb;
		$order=null;

		if (isset($_GET['peiraeus']) && ($_GET['peiraeus'] == 'success')) {
			$ResultCode = $_REQUEST['ResultCode'];
			$ResponseCode = $_REQUEST['ResponseCode'];
			$StatusFlag = $_REQUEST['StatusFlag'];
			$HashKey = $_REQUEST['HashKey'];
			$SupportReferenceID = $_REQUEST['SupportReferenceID'];
			$TransactionID = $_REQUEST['TransactionId'];
			$ApprovalCode = $_REQUEST['ApprovalCode'];
			$Parameters = $_REQUEST['Parameters'];
			$AuthStatus = $_REQUEST['AuthStatus'];
			$PackageNo = $_REQUEST['PackageNo'];
			$MerchantReference=$_REQUEST['MerchantReference'];
			$cleanUniqString=explode("-",$_REQUEST['MerchantReference']);
			if (sizeof($cleanUniqString)>1) {
				$order_id = $cleanUniqString[0];
			}else {
				$order_id = $MerchantReference;
			}
			$order = wc_get_order($order_id);

			if ($order===false) {
				return;
			}

			if ($ResultCode != 0) {
				if ($ResultCode=='1048') {
					$message = __('The transaction wasn\'t successful because of a recharge attempt.<br />The requested transaction was already processed and completed. Re-send the transaction using a different «MerchantReference» value.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($ResultCode=='500') {
					$message = __('The transaction wasn\'t successful as a technical problem occurred.<br />Communication problem with the transaction processing system. Try again later when the problem has been rectified.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($ResultCode=='981') {
					$message = __('The transaction wasn\'t successful as no valid values were used in card details or unsupported card was used. Please resend the transaction using correct card details.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($ResultCode=='1045') {
					$message = __('The transaction wasn\'t successful as duplicate transaction references are not allowed.<br />The request was sent with the same «MerchantReference» as that of a transaction currently processed by ePOS Paycenter. Try again later in order for the initial transaction to be completed.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($ResultCode=='1072') {
					$message = __('The transaction wasn\'t successful as pack is still closing.<br />The batch settlement process is in progress. Try again later after the batch has been closed.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($ResultCode=='1') {
					$message = __('The transaction wasn\'t successful as an error has occurred. Please check your data or else contact Winbank PayCenter administrator.<br />Try again later when the problem has been rectified.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}else {
					$message = __('The transaction wasn\'t successful as a technical problem occurred. Payment wasn\'t received.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}

				$message_type = 'error';
				wc_add_notice($message, $message_type);
				$order->update_status('failed', '');
				$redirect_url=$order->get_cancel_order_url_raw();
				wp_redirect($redirect_url);
				exit;
			}
			$ttquery = 'SELECT trans_ticket
			FROM `' . $wpdb->prefix . 'piraeusbank_transactions`
			WHERE `merch_ref` = "' . $MerchantReference  . '"	;';
			$tt = $wpdb->get_results($ttquery);
			$transticket = $tt['0']->trans_ticket;
			$stcon = $transticket . apply_filters('webexpert_custom_pos_id',$this->get_option('pb_PosId')) . $this->get_option('pb_AcquirerId') . $MerchantReference . $ApprovalCode . $Parameters . $ResponseCode . $SupportReferenceID . $AuthStatus . $PackageNo . $StatusFlag;
			$conhash = strtoupper(hash('sha256', $stcon));

			//hmac
			$stcon_hmac = $transticket .";". apply_filters('webexpert_custom_pos_id',$this->get_option('pb_PosId')) .";". $this->get_option('pb_AcquirerId') .";". $MerchantReference .";". $ApprovalCode .";". $Parameters .";". $ResponseCode .";". $SupportReferenceID .";". $AuthStatus .";". $PackageNo .";". $StatusFlag;
			$conhash_hmac = strtoupper(hash_hmac('sha256', $stcon_hmac, $transticket));

			if ($conhash != $HashKey && $conhash_hmac != $HashKey) {
				$message = __('Thank you for choosing us for your online shopping. <br />However, the transaction wasn\'t successful, payment wasn\'t received.', 'webexpert-woocommerce-piraeus-payment-gateway');
				$message_type = 'error';
				wc_add_notice( $message, $message_type);
				$order->update_status('failed', '');
				echo "Wrong hash";exit;
			} else {
				if ($ResponseCode == 0 || $ResponseCode == 8 || $ResponseCode == 10 || $ResponseCode == 16) {
					$order->add_order_note(__('Payment Via Peiraeus Bank.','webexpert-woocommerce-piraeus-payment-gateway').'<br />MerchantReference: '.$MerchantReference.', SupportReferenceID: '.$SupportReferenceID.', Transaction ID: '.$TransactionID);
					$message = __('Thank you for choosing us for your online shopping.<br />Your transaction was successful, payment was received.<br />Your order is currently being processed.', 'webexpert-woocommerce-piraeus-payment-gateway');
					$message_type = 'success';
					$order->payment_complete($TransactionID);
					wc_add_notice( $message, $message_type );
					do_action( 'webexpert_woocommerce_piraeus_bank_success', $order->get_id());
				} else if ($ResponseCode == '11') {
					$message = __('Thank you for choosing us for your online shopping.<br />Your transaction was previously received.', 'webexpert-woocommerce-piraeus-payment-gateway');
					$message_type = 'success';
					wc_add_notice($message,$message_type);
					do_action( 'webexpert_woocommerce_piraeus_bank_success', $order->get_id());
				} else {
					$message = __('Thank you for choosing us for your online shopping. <br />However, the transaction was declined by the Issuer, payment wasn\'t received. Please contact your bank or use another card.', 'webexpert-woocommerce-piraeus-payment-gateway');
					$message_type = 'error';
					wc_add_notice($message,$message_type);
					do_action( 'webexpert_woocommerce_piraeus_bank_failed', $order->get_id());
					$order->update_status('failed', '');
					$redirect_url=$order->get_cancel_order_url_raw();
					wp_redirect($redirect_url);
					exit;
				}
			}
		}
		if (isset($_GET['peiraeus']) && ($_GET['peiraeus'] == 'fail')) {
			$ResultCode = $_REQUEST['ResultCode'];
			if (isset($_REQUEST['MerchantReference'])) {
				$MerchantReference=$_REQUEST['MerchantReference'];
				$cleanUniqString=explode("-",$_REQUEST['MerchantReference']);
				$SupportReferenceID = $_REQUEST['SupportReferenceID'];
				$TransactionID = $_REQUEST['TransactionId'];
				if (sizeof($cleanUniqString)>1) {
					$order_id = $cleanUniqString[0];
				}else {
					$order_id = $MerchantReference;
				}
				$order = wc_get_order($order_id);

				if ($order===false) {
					$checkout_url = wc_get_checkout_url();
					wp_redirect($checkout_url);
					return;
				}

				if ($ResultCode=='1048') {
					$message = __('The transaction wasn\'t successful because of a recharge attempt.<br />The requested transaction was already processed and completed. Re-send the transaction using a different «MerchantReference» value.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($ResultCode=='500') {
					$message = __('The transaction wasn\'t successful as a technical problem occurred.<br />Communication problem with the transaction processing system. Try again later when the problem has been rectified.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($ResultCode=='981') {
					$message = __('The transaction wasn\'t successful as no valid values were used in card details or unsupported card was used. Please resend the transaction using correct card details.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($ResultCode=='1045') {
					$message = __('The transaction wasn\'t successful as duplicate transaction references are not allowed.<br />The request was sent with the same «MerchantReference» as that of a transaction currently processed by ePOS Paycenter. Try again later in order for the initial transaction to be completed.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($ResultCode=='1072') {
					$message = __('The transaction wasn\'t successful as pack is still closing.<br />The batch settlement process is in progress. Try again later after the batch has been closed.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}elseif ($ResultCode=='1') {
					$message = __('The transaction wasn\'t successful as an error has occurred. Please check your data or else contact Winbank PayCenter administrator.<br />Try again later when the problem has been rectified.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}else {
					$message = __('The transaction wasn\'t successful as a technical problem occurred. Payment wasn\'t received.', 'webexpert-woocommerce-piraeus-payment-gateway');
				}

				$order->add_order_note($message . __('Payment Via Peiraeus Bank','webexpert-woocommerce-piraeus-payment-gateway').'<br />MerchantReference: '.$MerchantReference.', SupportReferenceID: '.$SupportReferenceID.', Transaction ID: '.$TransactionID);
				$order->update_status('failed', '');
				$message_type='error';
				wc_add_notice($message, $message_type);
				$redirect_url=$order->get_cancel_order_url_raw();
				wp_redirect($redirect_url);
				exit;
			}else {
				$checkout_url = wc_get_checkout_url();
				wp_redirect($checkout_url);
				exit;
			}
		}
		if (isset($_GET['peiraeus']) && ($_GET['peiraeus'] == 'cancel')) {
			$checkout_url = wc_get_checkout_url();
			wp_redirect($checkout_url);
			exit;
		}
		$redirect_url = $this->get_return_url($order);
		wp_redirect($redirect_url);
		exit;
	}

	public static function split($str, $len = 1) {
		$arr		= [];
		$length 	= mb_strlen($str, 'UTF-8');
		for ($i = 0; $i < $length; $i += $len) {
			$arr[] = mb_substr($str, $i, $len, 'UTF-8');
		}
		return $arr;
	}

	public static function convert_alpha_2_to_code($alpha_2) {
		$iso=["AF"=>"4","AX"=>"248","AL"=>"8","DZ"=>"12","AS"=>"16","AD"=>"20","AO"=>"24","AI"=>"660","AQ"=>"10","AG"=>"28","AR"=>"32","AM"=>"51","AW"=>"533","AU"=>"36","AT"=>"40","AZ"=>"31","BS"=>"44","BH"=>"48","BD"=>"50","BB"=>"52","BY"=>"112","BE"=>"56","BZ"=>"84","BJ"=>"204","BM"=>"60","BT"=>"64","BO"=>"68","BQ"=>"535","BA"=>"70","BW"=>"72","BV"=>"74","BR"=>"76","IO"=>"86","BN"=>"96","BG"=>"100","BF"=>"854","BI"=>"108","CV"=>"132","KH"=>"116","CM"=>"120","CA"=>"124","KY"=>"136","CF"=>"140","TD"=>"148","CL"=>"152","CN"=>"156","CX"=>"162","CC"=>"166","CO"=>"170","KM"=>"174","CG"=>"178","CD"=>"180","CK"=>"184","CR"=>"188","CI"=>"384","HR"=>"191","CU"=>"192","CW"=>"531","CY"=>"196","CZ"=>"203","DK"=>"208","DJ"=>"262","DM"=>"212","DO"=>"214","EC"=>"218","EG"=>"818","SV"=>"222","GQ"=>"226","ER"=>"232","EE"=>"233","SZ"=>"748","ET"=>"231","FK"=>"238","FO"=>"234","FJ"=>"242","FI"=>"246","FR"=>"250","GF"=>"254","PF"=>"258","TF"=>"260","GA"=>"266","GM"=>"270","GE"=>"268","DE"=>"276","GH"=>"288","GI"=>"292","GR"=>"300","GL"=>"304","GD"=>"308","GP"=>"312","GU"=>"316","GT"=>"320","GG"=>"831","GN"=>"324","GW"=>"624","GY"=>"328","HT"=>"332","HM"=>"334","VA"=>"336","HN"=>"340","HK"=>"344","HU"=>"348","IS"=>"352","IN"=>"356","ID"=>"360","IR"=>"364","IQ"=>"368","IE"=>"372","IM"=>"833","IL"=>"376","IT"=>"380","JM"=>"388","JP"=>"392","JE"=>"832","JO"=>"400","KZ"=>"398","KE"=>"404","KI"=>"296","KP"=>"408","KR"=>"410","KW"=>"414","KG"=>"417","LA"=>"418","LV"=>"428","LB"=>"422","LS"=>"426","LR"=>"430","LY"=>"434","LI"=>"438","LT"=>"440","LU"=>"442","MO"=>"446","MG"=>"450","MW"=>"454","MY"=>"458","MV"=>"462","ML"=>"466","MT"=>"470","MH"=>"584","MQ"=>"474","MR"=>"478","MU"=>"480","YT"=>"175","MX"=>"484","FM"=>"583","MD"=>"498","MC"=>"492","MN"=>"496","ME"=>"499","MS"=>"500","MA"=>"504","MZ"=>"508","MM"=>"104","NA"=>"516","NR"=>"520","NP"=>"524","NL"=>"528","NC"=>"540","NZ"=>"554","NI"=>"558","NE"=>"562","NG"=>"566","NU"=>"570","NF"=>"574","MK"=>"807","MP"=>"580","NO"=>"578","OM"=>"512","PK"=>"586","PW"=>"585","PS"=>"275","PA"=>"591","PG"=>"598","PY"=>"600","PE"=>"604","PH"=>"608","PN"=>"612","PL"=>"616","PT"=>"620","PR"=>"630","QA"=>"634","RE"=>"638","RO"=>"642","RU"=>"643","RW"=>"646","BL"=>"652","SH"=>"654","KN"=>"659","LC"=>"662","MF"=>"663","PM"=>"666","VC"=>"670","WS"=>"882","SM"=>"674","ST"=>"678","SA"=>"682","SN"=>"686","RS"=>"688","SC"=>"690","SL"=>"694","SG"=>"702","SX"=>"534","SK"=>"703","SI"=>"705","SB"=>"90","SO"=>"706","ZA"=>"710","GS"=>"239","SS"=>"728","ES"=>"724","LK"=>"144","SD"=>"729","SR"=>"740","SJ"=>"744","SE"=>"752","CH"=>"756","SY"=>"760","TW"=>"158","TJ"=>"762","TZ"=>"834","TH"=>"764","TL"=>"626","TG"=>"768","TK"=>"772","TO"=>"776","TT"=>"780","TN"=>"788","TR"=>"792","TM"=>"795","TC"=>"796","TV"=>"798","UG"=>"800","UA"=>"804","AE"=>"784","GB"=>"826","US"=>"840","UM"=>"581","UY"=>"858","UZ"=>"860","VU"=>"548","VE"=>"862","VN"=>"704","VG"=>"92","VI"=>"850","WF"=>"876","EH"=>"732","YE"=>"887","ZM"=>"894","ZW"=>"716"];
		$iso = array_map(function ($item) {
			return str_pad($item, 3, "0", \STR_PAD_LEFT);
		},
			$iso
		);
		return isset($iso[$alpha_2]) ? $iso[$alpha_2] : null;
	}

	public static function prefecture_to_state($prefecture) {
		$prefecture=wc_strtoupper($prefecture);
		switch ($prefecture) {
			case wc_strtoupper('Αττικής'):
				return 'I';
				break;
			case wc_strtoupper('Αγίου Όρους'):
			case wc_strtoupper('Ημαθίας'):
			case wc_strtoupper('Θεσσαλονίκης'):
			case wc_strtoupper('Κιλκίς'):
			case wc_strtoupper('Πέλλας'):
			case wc_strtoupper('Πιερίας'):
			case wc_strtoupper('Σερρών'):
			case wc_strtoupper('Χαλκιδικής'):
				return 'B';
				break;
			case wc_strtoupper('Αιτωλοακαρνανίας'):
			case wc_strtoupper('Αχαΐας'):
			case wc_strtoupper('Ηλείας'):
				return 'G';
				break;
			case wc_strtoupper('Αργολίδος'):
			case wc_strtoupper('Αρκαδίας'):
			case wc_strtoupper('Κορινθίας'):
			case wc_strtoupper('Λακωνίας'):
			case wc_strtoupper('Μεσσηνίας'):
				return 'J';
				break;
			case wc_strtoupper('Άρτης'):
			case wc_strtoupper('Θεσπρωτίας'):
			case wc_strtoupper('Ιωαννίνων'):
			case wc_strtoupper('Πρεβέζης'):
				return 'D';
				break;
			case wc_strtoupper('Βοιωτίας'):
			case wc_strtoupper('Ευβοίας'):
			case wc_strtoupper('Ευρυτανίας'):
			case wc_strtoupper('Φθιώτιδας'):
			case wc_strtoupper('Φωκίδας'):
				return 'H';
				break;
			case wc_strtoupper('Γρεβενών'):
			case wc_strtoupper('Καστοριάς'):
			case wc_strtoupper('Κοζάνης'):
			case wc_strtoupper('Φλωρίνης'):
				return 'C';
				break;
			case wc_strtoupper('Δράμας'):
			case wc_strtoupper('Έβρου'):
			case wc_strtoupper('Καβάλας'):
			case wc_strtoupper('Ξάνθης'):
			case wc_strtoupper('Ροδόπης'):
				return 'A';
				break;
			case wc_strtoupper('Δωδεκανήσου'):
			case wc_strtoupper('Κυκλάδων'):
				return 'L';
				break;
			case wc_strtoupper('Ζακύνθου'):
			case wc_strtoupper('Κερκύρας'):
			case wc_strtoupper('Κεφαλληνίας'):
			case wc_strtoupper('Λευκάδος'):
				return 'F';
				break;
			case wc_strtoupper('Ηρακλείου'):
			case wc_strtoupper('Ρεθύμνου'):
			case wc_strtoupper('Χανίων'):
			case wc_strtoupper('Λασιθίου'):
				return 'M';
				break;
			case wc_strtoupper('Καρδίτσας'):
			case wc_strtoupper('Λαρίσης'):
			case wc_strtoupper('Μαγνησίας'):
			case wc_strtoupper('Τρικάλων'):
				return 'E';
				break;
			case wc_strtoupper('Λέσβου'):
			case wc_strtoupper('Σάμου'):
			case wc_strtoupper('Χίου'):
				return 'K';
				break;
			default:
				return 'I';
		}
	}

	public  static function attempt_transliteration($field) {
		$encode = mb_detect_encoding($field);
		if ($encode !== 'ASCII') {
			if (function_exists('transliterator_transliterate')) {
				$field = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove', $field);
				$field = preg_replace("/[^a-zA-Z0-9\\/_|+ -]/", '', $field);
				$field = preg_replace("/[\\/_|+ -]+/", " ", $field);
				$field = trim($field, " ");
			} else {
				$field = remove_accents($field);
				$field = iconv($encode, 'ASCII//TRANSLIT//IGNORE', $field);
				$field = str_ireplace('?', '', $field);
				$field = trim($field);
			}
		}
		return $field;
	}
}
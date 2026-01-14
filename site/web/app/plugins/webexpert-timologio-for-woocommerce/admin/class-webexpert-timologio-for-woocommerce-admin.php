<?php

class Webexpert_Timologio_For_Woocommerce_Admin {

	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles() {
		if( ! wp_style_is( 'select2', 'registered' ) ) {
			wp_register_style( 'select2', WC()->plugin_url() . '/assets/css/select2.css', null, $this->version );
		}
		wp_enqueue_style( 'select2' );
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/webexpert-timologio-for-woocommerce-admin.css', array(), $this->version, 'all');
	}

	public function enqueue_scripts() {
		wp_enqueue_script('jquery-ui-datepicker');
		if( ! wp_script_is( 'select2', 'registered' ) ) {
			wp_register_script( 'select2', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', array( 'jquery' ), $this->version );
		}
		wp_enqueue_script('select2');
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/webexpert-timologio-for-woocommerce-admin.js', array('jquery'), time(), false);
		wp_localize_script($this->plugin_name, 'webexpert_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
	}

	function webexpert_timologio_for_woocommerce_settings_callback() {
		echo '<div class="wrap">
        <h1>Τιμολόγια - Ρυθμίσεις</h1>
        </div>';
	}

	public function get_tax_offices_from_json() {
		$json_path = plugin_dir_path(__DIR__) . 'includes/tax-offices.json';

		if (file_exists($json_path)) {
			$json_content = file_get_contents($json_path);
			return json_decode($json_content, true);
		}
		return false;
	}

	function pre_user_search($user_search) {
		global $wpdb;
		if ( empty( $_REQUEST['s'] ) ) {
			return;
		}

		// Sanitize the search term
		$search = trim(esc_sql( $wpdb->esc_like( $_GET['s'] ) ));

		//Enter Your Meta Fields To Query
		$search_array = array("billing_vat_id", "billing_phone", "billing_company");

		$user_search->query_from .= " LEFT JOIN {$wpdb->usermeta} ON {$wpdb->users}.ID={$wpdb->usermeta}.user_id AND (";
		for($i=0;$i<count($search_array);$i++) {
			if ($i > 0) $user_search->query_from .= " OR ";
			$user_search->query_from .= "{$wpdb->usermeta}.meta_key='" . $search_array[$i] . "'";
		}
		$user_search->query_from .= ")";
		$custom_where = $wpdb->prepare("{$wpdb->usermeta}.meta_value LIKE '%s'", "%{$search}%");
		$user_search->query_where = str_replace('WHERE 1=1 AND (', "WHERE 1=1 AND ({$custom_where} OR ",$user_search->query_where);
	}

	public function webexpert_timologio_for_woocommerce_admin_billing_fields($billing_fields) {
		$billing_fields['invoice'] = array(
			'type'          => 'select',
			'label'         => apply_filters('webexpert_timologio_for_wc__label_select',__('I want to issue an invoice', 'webexpert-timologio-for-woocommerce')),
			'placeholder'   => _x('Invoice', 'placeholder', 'webexpert-timologio-for-woocommerce'),
			'required'      => false,
			'clear'         => true,
			'options'       => array(
				'n' => apply_filters('webexpert_timologio_for_wc__label_no',__('No', 'webexpert-timologio-for-woocommerce')),
				'y' => apply_filters('webexpert_timologio_for_wc__label_yes',__('Yes', 'webexpert-timologio-for-woocommerce')),
			),
			'default'       => 'n',
			'wrapper_class' => 'form-field-wide',
			'show'          => false
		);

		$billing_fields['activity'] = array(
			'type'          => 'text',
			'label'       => apply_filters('webexpert_timologio_for_wc__label_activity',__('Business activity', 'webexpert-timologio-for-woocommerce')),
			'placeholder'   => _x('Business activity', 'placeholder', 'webexpert-timologio-for-woocommerce'),
			'required'      => false,
			'clear'         => true,
			'show'          => false,
			'wrapper_class' => 'form-field-wide invoice-only',
		);

		$billing_fields['vat_id'] = array(
			'type'          => 'text',
			'label'       => apply_filters('webexpert_timologio_for_wc__label_vat_no',__('VAT number', 'webexpert-timologio-for-woocommerce')),
			'placeholder'   => _x('VAT number', 'placeholder', 'webexpert-timologio-for-woocommerce'),
			'required'      => false,
			'wrapper_class' => 'form-field-left invoice-only',
			'show'          => false
		);

		if (get_option('webexpert_timologio_for_woocommerce_tax_office_dropdown','')=="yes") {
			$tax_offices = $this->get_tax_offices_from_json();

			$options = array('' => __('Select Tax Office', $this->plugin_name));
			if ($tax_offices) {
				foreach ($tax_offices as $office_key => $office_name) {
					$options[$office_key] = $office_name;
				}
			}

			$billing_fields['tax_office'] = array(
				'priority' => 23,
				'type' => 'select',
				'label' => apply_filters( 'webexpert_timologio_for_wc__label_tax_office', __( 'Tax office', 'webexpert-timologio-for-woocommerce' ) ),
				'placeholder' => _x( 'Tax office', 'placeholder', 'webexpert-timologio-for-woocommerce' ),
				'required'      => false,
				'wrapper_class' => 'form-field-right invoice-only',
				'clear'         => true,
				'show'          => false,
				'options'     => $options,
			);
		}else {
			$billing_fields['tax_office'] = array(
				'type'          => 'text',
				'label'         => apply_filters( 'webexpert_timologio_for_wc__label_tax_office', __( 'Tax office', 'webexpert-timologio-for-woocommerce' ) ),
				'placeholder'   => _x( 'Tax office', 'placeholder', 'webexpert-timologio-for-woocommerce' ),
				'required'      => false,
				'wrapper_class' => 'form-field-right invoice-only',
				'clear'         => true,
				'show'          => false
			);
        }
		return $billing_fields;
	}

	function webexpert_timologio_for_woocommerce_add_woocommerce_order_fields($address, $order) {
		$address['billing_activity'] = $order->get_meta('_billing_activity');
		$address['billing_vat_id'] = $order->get_meta('_billing_vat_id');
		$address['billing_tax_office'] = $order->get_meta('_billing_tax_office');
		return $address;
	}

	function webexpert_timologio_for_woocommerce_add_woocommerce_formatted_address_replacements($replace, $args) {
		$replace['{billing_activity}'] = !empty($args['billing_activity']) ? __('Business activity', 'webexpert-timologio-for-woocommerce') . ': ' . $args['billing_activity'] : '';
		$replace['{billing_vat_id}'] = !empty($args['billing_vat_id']) ? __('VAT number', 'webexpert-timologio-for-woocommerce') . ': ' . $args['billing_vat_id'] : '';
		$replace['{billing_tax_office}'] = !empty($args['billing_tax_office']) ? __('Tax office', 'webexpert-timologio-for-woocommerce') . ': ' . $args['billing_tax_office'] : '';
		return $replace;
	}

	function webexpert_timologio_for_woocommerce_add_woocommerce_localisation_address_formats($formats) {
		$formats['default'] = $formats['default'] . "\n{billing_vat_id}\n{billing_tax_office}\n{billing_activity}";
		return $formats;
	}

	function woocommerce_process_shop_order() {
		if (empty($_POST['_billing_vat_id'])) {
			return;
		}
	}

    function webexpert_timologio_for_woocommerce_add_metaboxes()
    {
	    $screen = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		    ? wc_get_page_screen_id( 'shop-order' )
		    : 'shop_order';

        add_meta_box('webexpert_timologio_for_woocommerce_meta_box', __('Invoice Generator','webexpert-timologio-for-woocommerce'), array($this, 'webexpert_timologio_for_woocommerce_box_html'), $screen, 'side');
        add_meta_box('webexpert_timologio_for_woocommerce_meta_box_2', __('Invoice Upload'), array($this, 'webexpert_timologio_for_woocommerce_box_2_html'), $screen, 'side');
    }

    function webexpert_timologio_for_woocommerce_box_2_html($post)
    {
	    $order = ( $post instanceof WP_Post ) ? wc_get_order( $post->ID ) : $post;
        if ($order) { ?>
            <div class="webexpert-timologio-for-woocommerce-generating-box-2">
                  <?php if(strlen(strval($order->get_meta('webexpert_timologio_for_wc_invoice_uploaded_file' )) ) > 0 ) { ?>
                        <h4><?php _e('You have attached an invoice for this order', 'webexpert-timologio-for-woocommerce'); ?></h4>
                        <div style='margin-top:5px'>
                                 <div> <a href="<?php echo $order->get_meta('webexpert_timologio_for_wc_invoice_uploaded_file') ?>" target="_blank" class="button"> <?php _e('See Invoice' , 'webexpert-timologio-for-woocommerce') ?> </a> </div>
                                 <div style='margin-top:5px'> <button type="button" class="button" data-order-id="<?php echo $order->get_id() ?>" id="webexpert_timologio_for_wc_delete_uploaded_file"><?php _e('Delete Invoice' , 'webexpert-timologio-for-woocommerce') ?></button> </div>
                        </div>
                    <?php } else { ?>
                        <h4><?php _e('Want to upload your own invoice? This one will replace the generated invoices.' , 'webexpert-timologio-for-woocommerce') ?></h4>
                        <div style='margin-top:5px'>
                                <input data-order-id="<?php echo $order->get_id(); ?>" style='display:none;' type="file" id="webexpert-timologio-for-woocommerce-custom-invoice-file"  />
                                <button type="button" id="webexpert-timologio-for-woocommerce-custom-invoice-button" class="button">Upload Invoice</button>
                        </div>
                 <?php } ?>
            </div>
            <?php
        }
    }

    function webexpert_timologio_for_wc_finalize_order_invoice()
    {
         $postId = isset($_POST['orderId']) ? sanitize_text_field($_POST['orderId']) : null;
         $result = isset($_POST['result']) ? sanitize_text_field($_POST['result']) : null;

         if( ! $postId ) {
            wp_send_json(['error' => 'order_not_setted'] , 422);
         }

         if( ! $result ) {
            wp_send_json(['error' => 'result_not_setted'] , 422);
         }

         $order = wc_get_order($postId);

         if( ! $order ) {
            wp_send_json(['error' => 'order_not_found'], 404);
         }

	    $order->update_meta_data('webexpert_timologio_for_wc_invoice_finalized' , $result);
	    $order->save();
        wp_send_json(['success' => true]);
    }

    function webexpert_timologio_for_woocommerce_generate_invoice() {
        $action = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : null;
        $order_id  = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : null;

        if( ! $order_id || ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json(['error' => __("Wrong action or not authorized",'webexpert-timologio-for-woocommerce') ] , 422);
        }

        if(! ($order = wc_get_order( $order_id )) ) {
            wp_send_json(['error' => __("Order could not be found",'webexpert-timologio-for-woocommerce') ] , 422);
        }

        $pdfName = $order->get_id()."_".base64_encode(microtime()) . ".pdf";
        $upload = wp_upload_dir();
	    wp_mkdir_p($upload['basedir'] . '/invoices/');

        if($action == 'cancel') {
            if(strlen(strval($order->get_meta('webexpert_timologio_invoice_type_receipt'))) <= 0 &&
                strlen(strval($order->get_meta('webexpert_timologio_invoice_type_invoice'))) <= 0) {
                wp_redirect('/');
                exit();
            }

            if(strlen(strval($order->get_meta('webexpert_timologio_invoice_type_cancel'))) > 0) {
                wp_redirect('/');
                exit();
            }

            $serial = get_option("webexpert_timologio_for_woocommerce_cancel_serial") ??  'AT';
            $newNumber = get_option('webexpert_timologio_for_woocommerce_cancel_starting') ?? 1;

            $invoiceId = get_option('webexpert_timologio_for_woocommerce_cancel_serial') . sprintf('%08d', $newNumber);
            $type = 'cancel';
            $pdfUrl = $upload['baseurl'] . '/invoices/' . $pdfName;
            $order->update_meta_data( 'webexpert_timologio_invoice_type_cancel' , $pdfUrl );
            $order->save();
            update_option('webexpert_timologio_for_woocommerce_cancel_starting' , $newNumber + 1);
            update_option('webexpert_timologio_for_woocommerce_cancel_invoice_id' , $invoiceId);

            $qrString = $this->webexpert_timologio_for_woocommerce_generate_qr_string($order , $type, $serial, $newNumber);

            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
            $mpdf->writeHTML($this->webexpert_timologio_for_woocommerce_invoice_html($order , $invoiceId , $type , $qrString));
            $mpdf->Output($upload['basedir'] . '/invoices/' . $pdfName , 'F');
            wp_send_json(['url' => $pdfUrl]);
            die();
        }

        if($action == 'return_receipt') {
            if(strlen(strval($order->get_meta('webexpert_timologio_invoice_type_receipt'))) <= 0 &&
                strlen(strval($order->get_meta('webexpert_timologio_invoice_type_invoice'))) <= 0) {
                wp_redirect('/');
                exit();
            }

            if(strlen(strval($order->get_meta('webexpert_timologio_invoice_type_return_receipt'))) > 0) {
                wp_redirect('/');
                exit();
            }

            $serial = get_option("webexpert_timologio_for_woocommerce_return_receipt_serial") ??  'AΕ';
            $newNumber = get_option('webexpert_timologio_for_woocommerce_receipt_return_starting') ?? 1;

            $invoiceId = get_option('webexpert_timologio_for_woocommerce_return_receipt_serial') . sprintf('%08d', $newNumber);
            $type = 'return_receipt';
            $pdfUrl = $upload['baseurl'] . '/invoices/' . $pdfName;
            $order->update_meta_data( 'webexpert_timologio_invoice_type_return_receipt' , $pdfUrl );
            $order->save();
            update_option('webexpert_timologio_for_woocommerce_return_receipt_starting' , $newNumber + 1);
            update_option('webexpert_timologio_for_woocommerce_return_receipt_invoice_id' , $invoiceId);

            $qrString = $this->webexpert_timologio_for_woocommerce_generate_qr_string($order , $type, $serial, $newNumber);

            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
            $mpdf->writeHTML($this->webexpert_timologio_for_woocommerce_invoice_html($order , $invoiceId , $type , $qrString));
            $mpdf->Output($upload['basedir'] . '/invoices/' . $pdfName , 'F');
            wp_send_json(['url' => $pdfUrl]);
            die();
        }

        if($action == 'cancel_receipt') {
            if(strlen(strval($order->get_meta('webexpert_timologio_invoice_type_receipt'))) <= 0 &&
                strlen(strval($order->get_meta('webexpert_timologio_invoice_type_invoice'))) <= 0) {
                wp_redirect('/');
                exit();
            }

            if(strlen(strval($order->get_meta('webexpert_timologio_invoice_type_cancel_receipt'))) > 0) {
                wp_redirect('/');
                exit();
            }

            $serial = get_option("webexpert_timologio_for_woocommerce_cancel_receipt_serial") ??  'AA';
            $newNumber = get_option('webexpert_timologio_for_woocommerce_cancel_receipt_starting') ?? 1;

            $invoiceId = get_option('webexpert_timologio_for_woocommerce_cancel_receipt_serial') . sprintf('%08d', $newNumber);
            $type = 'cancel_receipt';
	        $qrString = $this->webexpert_timologio_for_woocommerce_generate_qr_string($order , $type, $serial, $newNumber);
            $pdfUrl = $upload['baseurl'] . '/invoices/' . $pdfName;
            $order->update_meta_data( 'webexpert_timologio_invoice_type_cancel_receipt' , $pdfUrl );
            $order->save();
            update_option('webexpert_timologio_for_woocommerce_cancel_receipt_starting' , $newNumber + 1);
            update_option('webexpert_timologio_for_woocommerce_cancel_receipt_invoice_id' , $invoiceId);

            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
            $mpdf->writeHTML($this->webexpert_timologio_for_woocommerce_invoice_html($order , $invoiceId , $type , $qrString));
            $mpdf->Output($upload['basedir'] . '/invoices/' . $pdfName , 'F');
            wp_send_json(['url' => $pdfUrl]);
            die();
        }

        if($action == 'credit') {
            if(strlen(strval($order->get_meta('webexpert_timologio_invoice_type_invoice'))) <= 0) {
                wp_redirect('/');
                exit();
            }

            if(strlen(strval($order->get_meta( 'webexpert_timologio_invoice_type_credit'))) > 0) {
                wp_redirect('/');
                exit();
            }

            $serial = get_option("webexpert_timologio_for_woocommerce_credit_serial") ??  'AT';
            $newNumber = get_option('webexpert_timologio_for_woocommerce_credit_starting') ?? 1;
            $invoiceId = get_option('webexpert_timologio_for_woocommerce_credit_serial') . sprintf('%08d', $newNumber);
            $type = 'credit';
            $qrString = $this->webexpert_timologio_for_woocommerce_generate_qr_string($order , $type, $serial, $newNumber);
            $pdfUrl = $upload['baseurl'] . '/invoices/' . $pdfName;
            $order->update_meta_data( 'webexpert_timologio_invoice_type_credit' , $pdfUrl );
            $order->save();
            update_option('webexpert_timologio_for_woocommerce_credit_starting' , $newNumber + 1);
            update_option('webexpert_timologio_for_woocommerce_credit_invoice_id' , $invoiceId);

            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
            $mpdf->writeHTML($this->webexpert_timologio_for_woocommerce_invoice_html($order , $invoiceId , $type, $qrString));
            $mpdf->Output($upload['basedir'] . '/invoices/' . $pdfName , 'F');
            wp_send_json(['url' => $pdfUrl]);
            die();
        }

        if($action == 'delivery_note') {

            if(strlen(strval($order->get_meta('webexpert_timologio_invoice_type_delivery_note'))) > 0) {
                wp_redirect('/');
                exit();
            }

            $serial = get_option("webexpert_timologio_for_woocommerce_delivery_note_serial") ??  'ΔΠ';
            $newNumber = get_option('webexpert_timologio_for_woocommerce_delivery_note_starting') ?? 1;
            if(!intval($newNumber)){
                $newNumber = 1;
            }

            $invoiceId = get_option('webexpert_timologio_for_woocommerce_delivery_note_serial') . sprintf('%08d', $newNumber);
            $type = 'delivery_note';
            $qrString = "";

            $pdfUrl = $upload['baseurl'] . '/invoices/' . $pdfName;
            $order->update_meta_data( 'webexpert_timologio_invoice_type_delivery_note' , $pdfUrl );
            $order->save();
            update_option('webexpert_timologio_for_woocommerce_delivery_note_starting' , $newNumber + 1);
            update_option('webexpert_timologio_for_woocommerce_delivery_note_invoice_id' , $invoiceId);

            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);

            $mpdf->writeHTML($this->webexpert_timologio_for_woocommerce_invoice_html($order , $invoiceId , $type, $qrString));
            $mpdf->Output($upload['basedir'] . '/invoices/' . $pdfName , 'F');
            wp_send_json(['url' => $pdfUrl]);
            die();
        }

        $type = '';
        if( $order->get_meta('_billing_invoice',true) == "y" )
        {
            $serial = get_option("webexpert_timologio_for_woocommerce_invoice_serial") ?? 'T';
            $newNumber = get_option('webexpert_timologio_for_woocommerce_invoice_starting') ?? 1;
	        $type  = 'invoice';
            $qrString = $this->webexpert_timologio_for_woocommerce_generate_qr_string($order , $type, $serial, $newNumber);
            $invoiceId = get_option('webexpert_timologio_for_woocommerce_invoice_serial') . sprintf('%08d', $newNumber);
            $pdfUrl = $upload['baseurl'] . '/invoices/' . $pdfName;
            $order->update_meta_data( 'webexpert_timologio_invoice_type_invoice' , $pdfUrl );
            $order->save();
            update_option('webexpert_timologio_for_woocommerce_invoice_starting' , $newNumber + 1);
            update_option('webexpert_timologio_for_woocommerce_invoice_invoice_id' , $invoiceId);
        }
        else
        {
            $serial = get_option("webexpert_timologio_for_woocommerce_receipt_serial") ?? 'A';
            $newNumber = get_option('webexpert_timologio_for_woocommerce_receipt_starting') ?? 1;
            $invoiceId = get_option('webexpert_timologio_for_woocommerce_receipt_serial') . sprintf('%08d', $newNumber);
	        $type = 'receipt';
            $qrString = $this->webexpert_timologio_for_woocommerce_generate_qr_string($order , $type, $serial, $newNumber);
            $pdfUrl = $upload['baseurl'] . '/invoices/' . $pdfName;
            $order->update_meta_data( 'webexpert_timologio_invoice_type_receipt' , $pdfUrl );
            $order->save();
            update_option('webexpert_timologio_for_woocommerce_receipt_starting' , $newNumber + 1);
            update_option('webexpert_timologio_for_woocommerce_receipt_invoice_id' , $invoiceId);
        }

        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
        $mpdf->writeHTML($this->webexpert_timologio_for_woocommerce_invoice_html($order , $invoiceId , $type , $qrString ));
        ob_clean();

        $order->update_meta_data( 'webexpert_timlogio_invoice_path' , wp_normalize_path($upload['path'] . '/' . $pdfName) );
        $order->save();
        $mpdf->Output($upload['basedir'] . '/invoices/' . $pdfName , 'F');
        wp_send_json(['url' => $pdfUrl]);
        die();
    }

    function webexpert_timologio_for_woocommerce_box_html($post)
    {
	    $order = ( $post instanceof WP_Post ) ? wc_get_order( $post->ID ) : $post;
        if ($order) {
	        $hasReceipt = strlen(strval($order->get_meta('webexpert_timologio_invoice_type_receipt' ))) > 0;
	        $hasCancel = strlen(strval($order->get_meta(  'webexpert_timologio_invoice_type_cancel' ))) > 0;
	        $hasCancelReceipt= strlen(strval($order->get_meta( 'webexpert_timologio_invoice_type_cancel_receipt' ))) > 0;
	        $hasReturnReceipt= strlen(strval($order->get_meta( 'webexpert_timologio_invoice_type_return_receipt' ))) > 0;
	        $hasInvoice = strlen( strval($order->get_meta( 'webexpert_timologio_invoice_type_invoice'))) > 0;
	        $hasCredit = strlen(strval($order->get_meta( 'webexpert_timologio_invoice_type_credit' ))) > 0;
	        $hasDeliveryNote = strlen(strval($order->get_meta( 'webexpert_timologio_invoice_type_delivery_note'  ))) >0;
	        ?>
            <div class="webexpert-timologio-for-woocommerce-generating-box">

                <input type="hidden" id="webexpert-timologio-for-woocommerce-ajax-loader"  value="<?php echo ajaxLoaderWebexpertTimologio() ?>" />

                <h4><?php _e('Generate an invoice for this order', 'webexpert-timologio-for-woocommerce') ?></h4>
		        <?php if($hasReceipt) { ?>
                    <div style='margin-bottom:5px;'><i><?php echo $order->get_meta( 'webexpert_timologio_for_woocommerce_receipt_invoice_id' ); ?></i></div>
                    <a target="_blank" href="<?php echo $order->get_meta( 'webexpert_timologio_invoice_type_receipt' ) ?>" class="button"> <?php _e("Download Order Receipt" , "webexpert-timologio-for-woocommerce") ?></a>
		        <?php } ?>
		        <?php if($hasInvoice) { ?>
                    <div style='margin-bottom:5px;'><i><?php echo $order->get_meta( 'webexpert_timologio_for_woocommerce_invoice_invoice_id' ); ?></i></div>
                    <a class="button" target="_blank" href="<?php echo $order->get_meta( 'webexpert_timologio_invoice_type_invoice' ) ?>"> <?php _e("Download Order Invoice" , "webexpert-timologio-for-woocommerce") ?> </a>
		        <?php } ?>
		        <?php if($hasReceipt) { ?>
			        <?php if($hasCancelReceipt) { ?>
                        <div style="margin-top:10px">
                            <div style='margin-bottom:5px;'><i><?php echo $order->get_meta( 'webexpert_timologio_for_woocommerce_cancel_receipt_invoice_id' ); ?></i></div>
                            <a class="button" target="_blank" href="<?php echo $order->get_meta( 'webexpert_timologio_invoice_type_cancel_receipt' ) ?>"><?php _e("Download Cancellation Receipt" , "webexpert-timologio-for-woocommerce") ?></a>
                        </div>
			        <?php } else { ?>
                        <div style="margin-top:10px">
                            <a class="button webexpert-timologio-for-woocommerce-generate-invoice" target="_blank" href="<?php echo site_url() ?>/webexpert-timologio-for-woocommerce-box-generate?o=<?php echo $order->get_id() ?>&action=cancel_receipt"><?php _e("Generate Cancellation Receipt" , "webexpert-timologio-for-woocommerce") ?></a>
                        </div>
			        <?php } ?>

			        <?php if($hasReturnReceipt) { ?>
                        <div style="margin-top:10px">
                            <div style='margin-bottom:5px;'><i><?php echo $order->get_meta( 'webexpert_timologio_for_woocommerce_return_receipt_invoice_id' ); ?></i></div>
                            <a class="button" target="_blank" href="<?php echo $order->get_meta( 'webexpert_timologio_invoice_type_return_receipt' ) ?>"><?php _e("Download Return Receipt" , "webexpert-timologio-for-woocommerce") ?></a>
                        </div>
			        <?php } else { ?>
                        <div style="margin-top:10px">
                            <a class="button webexpert-timologio-for-woocommerce-generate-invoice" target="_blank" href="<?php echo site_url() ?>/webexpert-timologio-for-woocommerce-box-generate?o=<?php echo $order->get_id() ?>&action=return_receipt"><?php _e("Generate Return Receipt" , "webexpert-timologio-for-woocommerce") ?></a>
                        </div>
			        <?php } ?>

			        <?php if($hasCancel) { ?>
                        <div style="margin-top:10px">
                            <div style='margin-bottom:5px;'><i><?php echo $order->get_meta( 'webexpert_timologio_for_woocommerce_cancel_invoice_id' ); ?></i></div>
                            <a class="button" target="_blank" href="<?php echo $order->get_meta( 'webexpert_timologio_invoice_type_cancel' ) ?>"><?php _e("Download Cancellation Invoice" , "webexpert-timologio-for-woocommerce") ?></a>
                        </div>
			        <?php } else { ?>
                        <div style="margin-top:10px">
                            <a class="button webexpert-timologio-for-woocommerce-generate-invoice" target="_blank" href="<?php echo site_url() ?>/webexpert-timologio-for-woocommerce-box-generate?o=<?php echo $order->get_id() ?>&action=cancel"><?php _e("Generate Cancellation Invoice" , "webexpert-timologio-for-woocommerce") ?></a>
                        </div>
			        <?php } ?>
		        <?php } else if($hasInvoice) { ?>
			        <?php if($hasCredit) { ?>
                        <div style="margin-top:10px">
                            <div style='margin-bottom:5px;'><i><?php echo $order->get_meta( 'webexpert_timologio_for_woocommerce_credit_invoice_id' ); ?></i></div>
                            <a class="button" target="_blank" href="<?php echo $order->get_meta( 'webexpert_timologio_invoice_type_credit' ) ?>"><?php _e("Download Credit Note" , "webexpert-timologio-for-woocommerce") ?></a>
                        </div>
			        <?php } else { ?>
                        <div style="margin-top:10px">
                            <a class="button webexpert-timologio-for-woocommerce-generate-invoice" target="_blank" href="<?php echo site_url() ?>/webexpert-timologio-for-woocommerce-box-generate?o=<?php echo $order->get_id(); ?>&action=credit"><?php _e("Generate Credit Note" , "webexpert-timologio-for-woocommerce") ?></a>
                        </div>
			        <?php } ?>
			        <?php if($hasCancel) { ?>
                        <div style="margin-top:10px">
                            <div style='margin-bottom:5px;'><i><?php echo $order->get_meta( 'webexpert_timologio_for_woocommerce_cancel_invoice_id' ); ?></i></div>
                            <a class="button" target="_blank" href="<?php echo $order->get_meta( 'webexpert_timologio_invoice_type_cancel' ) ?>"><?php _e("Download Cancellation Invoice" , "webexpert-timologio-for-woocommerce") ?></a>
                        </div>
			        <?PHP } else { ?>
                        <div style="margin-top:10px">
                            <a class="button webexpert-timologio-for-woocommerce-generate-invoice" target="_blank" href="<?php echo site_url() ?>/webexpert-timologio-for-woocommerce-box-generate?o=<?php echo $order->get_id(); ?>&action=cancel"><?php _e("Generate Cancellation Invoice" , "webexpert-timologio-for-woocommerce") ?></a>
                        </div>
			        <?php } ?>
		        <?php } else { ?>
                    <a class="button webexpert-timologio-for-woocommerce-generate-invoice" target="_blank" href="<?php echo site_url() ?>/webexpert-timologio-for-woocommerce-box-generate?o=<?php echo $order->get_id(); ?>"><?php _e("Generate Order Invoice" , "webexpert-timologio-for-woocommerce") ?></a>
		        <?php } ?>
		        <?php if($hasDeliveryNote) { ?>
                    <div style="margin-top:10px">
                        <div style='margin-bottom:5px;'><i><?php echo $order->get_meta( 'webexpert_timologio_for_woocommerce_delivery_note_invoice_id' ); ?></i></div>
                        <a class="button" target="_blank" href="<?php echo $order->get_meta( 'webexpert_timologio_invoice_type_delivery_note' ) ?>"><?php _e("Download Delivery Note" , "webexpert-timologio-for-woocommerce") ?></a>
                    </div>
		        <?php } else { ?>
                    <div style="margin-top:10px">
                        <a class="button webexpert-timologio-for-woocommerce-generate-invoice" target="_blank" href="<?php echo site_url() ?>/webexpert-timologio-for-woocommerce-box-generate?o=<?php echo $order->get_id(); ?>&action=delivery_note"><?php _e("Generate Delivery Note" , "webexpert-timologio-for-woocommerce") ?></a>
                    </div>
		        <?php } ?>

                <div style="margin-top:15px;">
                    <fieldset>
                        <label for="webexpert_timologio_for_woocommerce_finalization_invoice">
                            <input <?php echo $order->get_meta( 'webexpert_timologio_for_wc_invoice_finalized' )  == 'yes' ? 'checked="checked"' : ''; ?> data-order-id="<?php echo $order->get_id(); ?>" id="webexpert_timologio_for_woocommerce_finalization_invoice" type="checkbox"  />
					        <?php _e('Finalization', 'webexpert-timologio-for-woocommerce'); ?>
                        </label>
                    </fieldset>
                    <p><?php
				        _e('Finalizing means that the customer will be able to see the invoice on "My Orders" page.', 'webexpert-timologio-for-woocommerce');
				        ?></p>
                </div>
            </div>
	        <?php
        }
    }

    private function getTotalTaxAndNetOfOrder($order , $number , $digitsSeperation , $thousandsSeperation)
    {
          $total_net = 0;
          $total_tax = 0;

            foreach ( $order->get_items() as  $item ) {
                $with_tax = $item->get_total_tax() + $item->get_total();
                $without_tax = $item->get_total();
                $tax_amount = $with_tax - $without_tax;
                $percent = $without_tax>0 ? round(($tax_amount / $without_tax) * 100) : 0;

                if($percent == $number) {
                    $total_net += $without_tax;
                    $total_tax += $tax_amount;
                }
            }

            foreach ( $order->get_items('fee') as $item_id => $item_fee) {
                $with_tax = $item_fee->get_total_tax() + $item_fee->get_total();
                $without_tax = $item_fee->get_total();
                $tax_amount = $with_tax - $without_tax;
                $percent = $without_tax>0 ? round(($tax_amount / $without_tax) * 100) : 0;

                if($percent == $number) {
                     $total_net += $without_tax;
                     $total_tax += $tax_amount;
                }
            }

            foreach( $order->get_items( 'shipping' ) as $item_id => $item_fee ) {
                $with_tax = $item_fee->get_total_tax() + $item_fee->get_total();
                $without_tax = $item_fee->get_total();
                $tax_amount = $with_tax - $without_tax;
                $percent = $without_tax>0 ? round(($tax_amount / $without_tax) * 100) : 0;

                if($percent == $number) {
                    $total_net += $without_tax ;
                    $total_tax += $tax_amount ;
                }
            }

            return ['total_net' => number_format($total_net , 2 , $digitsSeperation , $thousandsSeperation ) , 'total_tax' => number_format($total_tax , 2 , $digitsSeperation , $thousandsSeperation ) ];
    }
    function webexpert_timologio_for_woocommerce_generate_qr_string($order , $type , $serial, $number) {
        $delimiter = get_option("webexpert_timologio_for_woocommerce_invoice_qr_delimiter",";" );
        $digitsSeperation = get_option("webexpert_timologio_for_woocommerce_invoice_qr_digit_seperation",",");
        $thousandsSeperation = get_option("webexpert_timologio_for_woocommerce_invoice_qr_thousands_seperation",".");
        $typeNumber = "162";
        switch($type) {
            case "cancel":
                $typeNumber = get_option("webexpert_timologio_for_woocommerce_qr_cancel_code","215");
                break;
            case "cancel_receipt":
                $typeNumber = get_option("webexpert_timologio_for_woocommerce_qr_cancel_receipt_code","215");
                break;
            case "return_receipt":
                $typeNumber = get_option("webexpert_timologio_for_woocommerce_qr_return_receipt_code","215");
                break;
            case "receipt":
            $typeNumber = get_option("webexpert_timologio_for_woocommerce_qr_receipt_code","231");
                break;
            case "invoice":
              $typeNumber = get_option("webexpert_timologio_for_woocommerce_qr_invoice_code","162");
                break;
            case "credit":
              $typeNumber = get_option("webexpert_timologio_for_woocommerce_qr_credit_code","169");
                break;
        }

        $result = "";
        $stats_0 = $this->getTotalTaxAndNetOfOrder($order , 0, $digitsSeperation , $thousandsSeperation);
        $stats_6 = $this->getTotalTaxAndNetOfOrder($order , 6 , $digitsSeperation , $thousandsSeperation);
        $stats_13 = $this->getTotalTaxAndNetOfOrder($order , 13 , $digitsSeperation , $thousandsSeperation);
        $stats_24 = $this->getTotalTaxAndNetOfOrder($order , 24 , $digitsSeperation , $thousandsSeperation);
        $stats_36 = $this->getTotalTaxAndNetOfOrder($order , 36 , $digitsSeperation , $thousandsSeperation);
        $customerVat = !empty($order->get_meta('_billing_vat_id')) ? $order->get_meta('_billing_vat_id') : "";

        if (get_option('webexpert_timologio_hide_symboloseira',null)!='yes') {
            $result .= "<%SL";
            $result .= get_option('woocommerce_store_vat_id') . $delimiter; //AFM SHOP
            $result .= $customerVat . $delimiter; //AFM PARALIPTI
            $result .= str_repeat($delimiter, 6);
            $result .= $typeNumber . $delimiter;
            $result .= $serial . $delimiter; //Serial Number
            $result .= $number . $delimiter; //Invoice Number

            $result .=  $stats_6['total_net'] . $delimiter;
            $result .= $stats_13['total_net'] . $delimiter;
            $result .= $stats_24['total_net'] . $delimiter;
            $result .= $stats_36['total_net'] . $delimiter;
            $result .= $stats_0['total_net'] . $delimiter;

            $result .= $stats_6['total_tax'] . $delimiter;
            $result .= $stats_13['total_tax'] . $delimiter;
            $result .= $stats_24['total_tax'] . $delimiter;
            $result .= $stats_36['total_tax'] . $delimiter;

            $result .= number_format( $order->get_total() , 2 , $digitsSeperation , $thousandsSeperation) . $delimiter;
            $result .= trim(get_option("woocommerce_currency","EUR"));
            $result .= ">";
        }
	    return apply_filters('webexpert_timologio_qr_string', $result, $order, $type, $serial, $number, $typeNumber, $delimiter, $stats_6, $stats_13, $stats_24, $stats_36, $stats_0, $digitsSeperation , $thousandsSeperation, $customerVat );
    }

    function webexpert_timologio_for_woocommerce_invoice_html($order , $invoiceId , $type , $qrString = "")
    {
            $itemsHtml = "";
            $counter = 1;
            foreach ( $order->get_items() as  $item )
            {
                $product = wc_get_product($item->get_variation_id()>0 ? $item->get_variation_id() : $item->get_product_id());
                 //A/A ΠΡΟΙΟΝ ΠΟΣΟΤΗΤΑ ΚΑΘΑΡΗ ΑΞΙΑ ΦΠΑ ΣΥΝΟΛΟ
	            $itemVat = $item->get_total_tax();
	            $itemRegularUnitPrice = wc_prices_include_tax() ? wc_get_price_excluding_tax($product) : $product->get_price();
	            $itemSubTotalExcludingVat = $item->get_total();
	            $itemDiscount = $item->get_subtotal()>0 ? ((($item->get_subtotal() - $item->get_total())*100) /$item->get_subtotal()) : 0;

	            $itemsHtml .= apply_filters('webexpert_timologio_for_wc_custom_html_table',"
                  <tr class='left-row'>
                        <td>" . $counter . "</td>
                        <td> " . $item->get_name() . "  </td>
                        <td style='text-align:center'> " . wc_price($itemRegularUnitPrice).  " </td>
                        <td style='text-align:center'> x" . $item->get_quantity() ." </td>
                        <td style='text-align:center'> " . ($itemDiscount > 0 ? wc_price($item->get_subtotal() - $item->get_total()) : '')." </td>
                        <td style='text-align:center'> " . wc_price($itemSubTotalExcludingVat)  . "</td>
                        <td style='text-align:center'> " . wc_price( $itemVat ). " </td>
                    </tr>",$item, $product, $counter);
                $counter++;
            }
            $itemsHtml .= apply_filters('webexpert_timologio_for_wc_custom_html_table_sep',"
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td style='text-align:right'></td>
                <td></td>
            </tr>");

            foreach( $order->get_items( 'shipping' ) as $item_id => $item ){
                  $item_data = $item->get_data();
                  $itemsHtml .= apply_filters('webexpert_timologio_for_wc_custom_html_table_shipping',"
                  <tr class='left-row'>
                        <td>" . $counter . "</td>
                        <td> " . $item_data['name'] . "  </td>
                        <td style='text-align:center'> " . wc_price($item_data['total']).  " </td>
                        <td style='text-align:center'>  </td>
                        <td>  </td>
                        <td style='text-align:center'> " . wc_price($item_data['total'] + $item_data['total_tax'])  . "</td>
                        <td style='text-align:center'> " . wc_price($item_data['total_tax']). " </td>
                    </tr>",$item,$counter);
                  $counter++;
          }

            foreach( $order->get_items( 'fee' ) as $item_id => $item ){
                  $item_data = $item->get_data();
                $itemsHtml .= apply_filters('webexpert_timologio_for_wc_custom_html_table_shipping',"
                   <tr class='left-row'>
                        <td>" . $counter . "</td>
                        <td> " . $item_data['name'] . "  </td>
                        <td style='text-align:center'> " . wc_price($item_data['total']).  " </td>
                        <td>  </td>
                        <td>  </td>
                        <td style='text-align:center'> " . wc_price($item_data['total'] + $item_data['total_tax'])  . "</td>
                        <td style='text-align:center'> " . wc_price($item_data['total_tax']). " </td>
                    </tr>",$item,$counter);
                  $counter++;
          }

            $invoiceType = 'invoice';
            switch($type) {
                case 'receipt':
                  $invoiceType = __('Receipt', 'webexpert-timologio-for-woocommerce');
                    break;
                case 'cancel':
                    $invoiceType = __('Cancellation', 'webexpert-timologio-for-woocommerce');
                    break;
                case 'cancel_receipt':
                    $invoiceType = __("Receipt Cancellation", 'webexpert-timologio-for-woocommerce');
                    break;
                case 'credit':
                  $invoiceType = __('Credit', 'webexpert-timologio-for-woocommerce');
                    break;
                case 'return_receipt':
                    $invoiceType = __("Return Receipt", "webexpert-timologio-for-woocommerce");
                    break;
                case 'delivery_note':
                    $invoiceType = __("Delivery Note" , 'webexpert-timologio-for-woocommerce');
                    break;
                case 'invoice':
                    $invoiceType = __('Document Invoice', 'webexpert-timologio-for-woocommerce');
                    break;
            }


            $date =  date_i18n('d/m/Y');
            $time = date_i18n('H:i');
            $data = [
                'company_name' => $order->get_billing_company(),
                'vat_number' => !empty($order->get_meta('_billing_vat_id')) ? $order->get_meta('_billing_vat_id') : '',
                'activity' => !empty($order->get_meta('_billing_activity')) ? $order->get_meta('_billing_activity') : '',
                'tax_office' => !empty($order->get_meta('_billing_tax_office')) ? $order->get_meta('_billing_tax_office') : ''
            ];

            $html = getInvoiceTemplate($data,$order);
            $html = str_replace('%payment_method%', $order->get_payment_method_title() , $html);
            $html = str_replace("%order_id%" , $order->get_id()  , $html);
            $html = str_replace("%invoice_id%" , $invoiceId , $html);
            $html = str_replace("%invoice_type%", $invoiceType , $html);
            $html = str_replace('%total_net_price%', wc_price($order->get_total() - $order->get_total_tax())  ,$html);
            $html = str_replace('%total_tax_price%', wc_price($order->get_total_tax()) , $html);
            $html = str_replace("%total_price%" , wc_price($order->get_total()), $html);
            $html = str_replace("%order_date%" , $date , $html);
            $html = str_replace("%order_time%" , $time , $html);
            $html = str_replace("%qr_string%" , $qrString , $html);
            $html = str_replace("%vat_number%", $order->get_meta('vat_number'), $html);
            $html = str_replace("%billing_email%" , $order->get_billing_email() , $html);
            $html = str_replace("%billing_address%" , $order->get_billing_address_1(),  $html);
            $html = str_replace("%billing_city%" , $order->get_billing_city() ,$html);
            $html = str_replace("%shipping_country%" , $order->get_shipping_country() , $html);
            $html = str_replace("%billing_country%", $order->get_billing_country() , $html);
            $html = str_replace("%shipping_address%" , $order->get_shipping_address_1() ?? $order->get_billing_address_1(), $html);
            $html = str_replace('%shipping_postcode%' , $order->get_shipping_postcode() , $html);
            $html = str_replace("%billing_postcode%" , $order->get_billing_postcode() , $html);
            $html = str_replace('%billing_phone%' , $order->get_billing_phone()  , $html);
            $html = str_replace("%billing_first_name%" , $order->get_billing_first_name() , $html );
            $html = str_replace("%billing_last_name%" , $order->get_billing_last_name() , $html );
            $html = str_replace("%order_table%" , $itemsHtml , $html);
            $logo = strlen(strval(get_option('webexpert_timologio_for_woocommerce_invoice_logo'))) > 0 ? "<img src='" . get_option('webexpert_timologio_for_woocommerce_invoice_logo') .  "' />" : "<h2>" . get_bloginfo( 'name' ) . "</h2>";
            $html = str_replace('%logo%' , $logo , $html);
            return $html;
    }

    function webexpert_timologio_for_wc_finalize_order_invoice_upload()
    {
        $orderId = isset($_POST['orderId']) ? sanitize_text_field($_POST['orderId']) : null;
        $order = wc_get_order($orderId);

        if(! $orderId || ! $order ) {
            wp_send_json(['error' => 'order_not_found'] , 404);
        }

        if(empty($_FILES['invoice_file']['name'])) {
            wp_send_json(['error' => 'not_file_uplaoded'] , 422);
        }

        $upload = wp_upload_bits($_FILES['invoice_file']['name'], null, file_get_contents($_FILES['invoice_file']['tmp_name']));
        if(isset($upload['error']) && $upload['error'] != 0) {
            wp_send_json(['error' => 'could_not_uploaded'] , 422);
        } else {
            $order->update_meta_data('webexpert_timlogio_invoice_path2' , wp_normalize_path($upload['file']) );
            $order->update_meta_data('webexpert_timologio_for_wc_invoice_uploaded_file', $upload['url']);
            $order->save();
            wp_send_json(['success' => true]);
        }
        wp_send_json(['error' => 'not_uploaded'] , 400);
    }

    function webexpert_timologio_for_wc_order_invoice_upload_delete()
    {
        $orderId = isset($_POST['orderId']) ? sanitize_text_field($_POST['orderId']) : null;
        $order = wc_get_order($orderId);
        if(! $orderId || ! $order ) {
            wp_send_json(['error' => 'order_not_found'] , 404);
        }

        if (!empty($order->get_meta('webexpert_timlogio_invoice_path2')) && file_exists($order->get_meta('webexpert_timlogio_invoice_path2'))) {
            unlink($order->get_meta('webexpert_timlogio_invoice_path2'));
        }

	    $order->delete_meta_data('webexpert_timologio_for_wc_invoice_uploaded_file');
	    $order->delete_meta_data('webexpert_timlogio_invoice_path2');
        $order->save();

        wp_send_json(['success' => true]);
    }

    function webexpert_timologio_for_wc_add_order_custom_actions($actions)
    {
        global $theorder;
        $actions['wc_webexpert_send_invoice_to_email_action'] = __( 'Send invoice to customer\'s email', 'webexpert-timologio-for-woocommerce' );
        return $actions;
    }

    function webexpert_attach_disclaimer_pdf_to_email($attachments, $email_id, $order)
    {
         if($email_id == 'wc_webexpert_send_invoice') {
            $link = null;
            if( strlen(strval($order->get_meta('webexpert_timlogio_invoice_path' , true))) > 0 ) {
                    $link = $order->get_meta('webexpert_timlogio_invoice_path', true);
            }

             if( strlen(strval( $order->get_meta('webexpert_timlogio_invoice_path') )) > 0 ) {
                 $link = $order->get_meta('webexpert_timlogio_invoice_path');
             }

            if( strlen(strval($order->get_meta('webexpert_timlogio_invoice_path2' , true))) > 0 ) {
                    $link = $order->get_meta('webexpert_timlogio_invoice_path2', true);
            }

             if( strlen(strval( $order->get_meta('webexpert_timlogio_invoice_path2' , true) )) > 0 ) {
                 $link = $order->get_meta('webexpert_timlogio_invoice_path2' , true);
             }

            $attachments[] = $link;
        }
        return $attachments;
    }

    function webexpert_timologio_for_wc_process_send_invoice_to_email_action($order)
    {
        $orderid = $order->get_id();
        $link = null;

        if( strlen(strval($order->get_meta( 'webexpert_timlogio_invoice_path' , true))) > 0 ) {
                $link = $order->get_meta( 'webexpert_timlogio_invoice_path', true);
        }

        if( strlen(strval($order->get_meta( 'webexpert_timlogio_invoice_path2' , true))) > 0 ) {
                $link = $order->get_meta('webexpert_timlogio_invoice_path2', true);
        }

        if(!$link) {
            return;
        }

        $mailer = WC()->mailer();
        $mails = $mailer->get_emails();
        if (!empty($mails))
        {
            foreach ($mails as $mail)
            {
                if ($mail->id == 'wc_webexpert_send_invoice')
                {
                    $mail->trigger($orderid);
                }
            }
        }
    }

    function webexpert_timologio_for_wc_check_aade_connection()
    {
         $vat = isset($_POST['aade_vat']) ?  sanitize_text_field($_POST['aade_vat']) : '';
         $username = isset($_POST['aade_username']) ?  sanitize_text_field($_POST['aade_username']) : '';
         $password = isset($_POST['aade_password']) ?  sanitize_text_field($_POST['aade_password']) : '';

         $result = $this->check_for_valid_vat_aade_with_message($vat , $username , $password);
         wp_send_json(['msg' => $result]);
         die();
    }

    function webexpert_timologio_for_wc__order_status_completed($order_id)
    {

        $order = wc_get_order($order_id);
        if(!$order || is_wp_error($order)) {
            return;
        }

        if(get_option('webexpert_timologio_auto_generate_invoice') == "yes") {
            $post_id  = $order_id;
            $order = wc_get_order($post_id);
            $type = '';

	        $pdfName = $order->get_id()."_".base64_encode(microtime()) . ".pdf";
            $upload = wp_upload_dir();
	        wp_mkdir_p($upload['basedir'] . '/invoices/');

            if( $order->get_meta('_billing_invoice',true) == "y" ) {
                $serial = get_option("webexpert_timologio_for_woocommerce_invoice_serial") ?? 'T';
                $newNumber = get_option('webexpert_timologio_for_woocommerce_invoice_starting') ?? 1;
	            $type  = 'invoice';
                $qrString = $this->webexpert_timologio_for_woocommerce_generate_qr_string($order , $type, $serial, $newNumber);
                $invoiceId = get_option('webexpert_timologio_for_woocommerce_invoice_serial') . sprintf('%08d', $newNumber);
                $pdfUrl = $upload['baseurl'] . '/invoices/' . $pdfName;
                $order->update_meta_data('webexpert_timologio_invoice_type_invoice' , $pdfUrl);
                $order->save();
                update_option('webexpert_timologio_for_woocommerce_invoice_starting' , $newNumber + 1);
                update_option('webexpert_timologio_for_woocommerce_invoice_invoice_id' , $invoiceId);
            } else {
                $serial = get_option("webexpert_timologio_for_woocommerce_receipt_serial") ?? 'A';
                $newNumber = get_option('webexpert_timologio_for_woocommerce_receipt_starting') ?? 1;
                $invoiceId = get_option('webexpert_timologio_for_woocommerce_receipt_serial') . sprintf('%08d', $newNumber);
	            $type = 'receipt';
                $qrString = $this->webexpert_timologio_for_woocommerce_generate_qr_string($order , $type, $serial, $newNumber);
                $pdfUrl = $upload['baseurl'] . '/invoices/' . $pdfName;
                $order->update_meta_data('webexpert_timologio_invoice_type_receipt' , $pdfUrl);
                $order->save();
                update_option('webexpert_timologio_for_woocommerce_receipt_starting' , $newNumber + 1);
                update_option('webexpert_timologio_for_woocommerce_receipt_invoice_id' , $invoiceId);
            }

            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
            $mpdf->writeHTML($this->webexpert_timologio_for_woocommerce_invoice_html($order , $invoiceId , $type , $qrString ));
	        $order->update_meta_data('webexpert_timlogio_invoice_path' , wp_normalize_path($upload['basedir'] . '/invoices/' . $pdfName));
            $order->save();
            $mpdf->Output($upload['basedir'] . '/invoices/' . $pdfName , 'F');
        }


        if(get_option('webexpert_timologio_auto_finalize_and_send_invoice') == "yes") {
             if(!empty( $order->get_meta('webexpert_timlogio_invoice_path') )   || !empty( $order->get_meta('webexpert_timlogio_invoice_path2') ) ) {
                 //Finalize the order
	             $order->update_meta_data('webexpert_timologio_for_wc_invoice_finalized' , "yes");
	             $order->save();

                 //Send the email
                 $mailer = WC()->mailer();
                 $mails = $mailer->get_emails();
                 if (!empty($mails))
                 {
                    foreach ($mails as $mail)
                    {
                        if ($mail->id == 'wc_webexpert_send_invoice')
                        {

                            $mail->trigger($order_id);
                        }
                    }
                 }

             }
        }

    }

    function webexpert_timologio_for_woocommerce_init() {
        if(empty(get_option("webexpert_timologio_for_woocommerce_cancel_receipt_serial")))
            update_option("webexpert_timologio_for_woocommerce_cancel_receipt_serial" , "ΑΑ");

        if(empty(get_option("webexpert_timologio_for_woocommerce_return_receipt_serial")))
            update_option("webexpert_timologio_for_woocommerce_return_receipt_serial" , "ΑΕ");

        if(empty(get_option("webexpert_timologio_for_woocommerce_cancel_receipt_starting")))
            update_option("webexpert_timologio_for_woocommerce_cancel_receipt_starting" , 1);

        if(empty(get_option("webexpert_timologio_for_woocommerce_receipt_return_starting")))
            update_option("webexpert_timologio_for_woocommerce_receipt_return_starting" , 1);

        $oldPage = get_page_by_path( 'webexpert-timologio-for-woocommerce-box-generate' );
        if(!is_wp_error($oldPage) && $oldPage) {
            wp_delete_post($oldPage->ID, true);
        }

    }
	function check_if_aade_isalive($timeout = 3) {
		$response = wp_remote_get("https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2?WSDL");
		$status = wp_remote_retrieve_response_code($response);
		return $status === 200;
	}
    function check_for_valid_vat_aade_with_message($vat_id, $username , $password) {
        if ($this->check_if_aade_isalive()===false) {
            return __("AADE service is down",'webexpert-timologio-for-woocommerce');
        }

        $envelope = '<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:ns1="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:ns2="http://rgwspublic2/RgWsPublic2Service" xmlns:ns3="http://rgwspublic2/RgWsPublic2">
         <env:Header>
                <ns1:Security>
                     <ns1:UsernameToken>
                            <ns1:Username>'. $username . '</ns1:Username>
                            <ns1:Password>' . $password .'</ns1:Password>
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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
                return __("Success" , "webexpert-timologio-for-woocommerce");
            }else {
                return strval($xml->Body->rgWsPublic2AfmMethodResponse->result->rg_ws_public2_result_rtType->error_rec->error_descr[0]);
            }
        }
        catch(Exception $e) {
            return __("AADE Unknown Error" , "webexpert-timologio-for-woocommerce");
        }
	}

	function webexpert_timologio_register_email($emails) {
		require plugin_dir_path(__DIR__).'admin/emails/class_wc_webexpert_send_invoice.php';
		$emails['WC_Webexpert_Send_Invoice'] = new WC_Webexpert_Send_Invoice();
		return $emails;
	}

	function webexpert_timologio_for_wc_admin_formatted($order) {
        echo "<p><strong>".__('Finance document', 'webexpert-timologio-for-woocommerce').":</strong><br>".($order->get_meta('_billing_invoice' )=='y' ? __('Invoice','webexpert-timologio-for-woocommerce') : __('Receipt','webexpert-timologio-for-woocommerce'))."</p>";
	}

	function webexpert_timologio_for_wc_search_for_billing_vat_id($search_fields) {
		$search_fields[] = '_billing_vat_id';
		return $search_fields;
	}
}

if (!function_exists('webexpert_check_for_valid_vat_aade_with_message')) {
    function webexpert_check_for_valid_vat_aade_with_message($vat_id) {
	    $pluign=new Webexpert_Timologio_For_Woocommerce();
	    $pluign_admin=new Webexpert_Timologio_For_Woocommerce_Admin($pluign->get_plugin_name(),$pluign->get_version());
	    $username=get_option('webexpert_timologio_for_woocommerce_aade_username');
	    $password=get_option('webexpert_timologio_for_woocommerce_aade_password');

        return $pluign_admin->check_for_valid_vat_aade_with_message($vat_id,$username,$password);
    }
}

if (!function_exists('webexpert_check_for_valid_vat_aade')) {
	function webexpert_check_for_valid_vat_aade($vat_id) {
		$pluign=new Webexpert_Timologio_For_Woocommerce();
		$plugin_public=new Webexpert_Timologio_For_Woocommerce_Public($pluign->get_plugin_name(),$pluign->get_version());
		return $plugin_public->check_for_valid_vat_aade($vat_id);
	}
}
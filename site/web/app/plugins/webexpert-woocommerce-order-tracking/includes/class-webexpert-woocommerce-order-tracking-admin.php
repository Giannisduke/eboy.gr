<?php
class Webexpert_woocommerce_order_tracking_Admin {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_scripts() {
        add_thickbox();
        wp_enqueue_script('jquery-ui-datepicker');
        wp_register_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/webexpert-woocommerce-order-tracking-admin.css', array(), $this->version, false);
        wp_enqueue_style($this->plugin_name);
        wp_register_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/webexpert-woocommerce-order-tracking-admin.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->plugin_name);
        wp_localize_script($this->plugin_name, 'webexpert_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_style( 'jquery-ui-datepicker-style' , '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css');
    }

    public function webexpert_woocommerce_order_tracking_admin_billing_fields($billing_fields) {
        $billing_fields['tracking_number'] = array(
            'type' => 'text',
            'label' => __('Tracking number', 'webexpert-woocommerce-order-tracking'),
            'placeholder' => _x('Tracking number', 'placeholder', 'webexpert-woocommerce-order-tracking'),
            'required' => false,
            'clear' => true,
            'show'  => true,
            'wrapper_class' => 'form-field-wide',

        );
        return $billing_fields;
    }

    public function webexpert_woocommerce_order_tracking_register_email( $emails ) {
        require_once plugin_dir_path(__FILE__) . '/emails/class-webexpert-woocommerce-order-tracking-email.php';
        $emails['WC_Email_Customer_Tracking_Number'] = new Webexpert_woocommerce_order_tracking_email();
        return $emails;
    }

    function webexpert_woocommerce_order_tracking_custom_wc_order_action( $actions ) {
        if ( is_array( $actions ) ) {
            $actions['send_tracking_number'] = __( 'Send tracking number','webexpert-woocommerce-order-tracking' );
        }
        return $actions;
    }

    function webexpert_woocommerce_order_tracking_fired_send_tracking_number( $order ) {
        $mailer = WC()->mailer();
        $mails = $mailer->get_emails();
        if (!empty($mails))
        {
            foreach ($mails as $mail)
            {
                if ($mail->id == 'customer_tracking_number')
                {
                    $mail->trigger($order->get_id());
                }
            }
        }
    }

    function webexpert_woocommerce_order_tracking_meta_box() {
	    $screen = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		    ? wc_get_page_screen_id( 'shop-order' )
		    : 'shop_order';

        add_meta_box(
            'multi_carrier_webeexpert',
            __('Multi-Carriers', 'webexpert-woocommerce-order-tracking'),
            [$this, 'multi_carrier_metabox_content'],
	        $screen, 'side', 'core'
        );
    }

	function webexpert_woocommerce_order_tracking_meta_box_save($order) {
		$transient_key = 'webexpert_order_tracking_save_lock_' . $order->get_id();
		if (get_transient($transient_key)) {
			return;
		}

		if (!empty($_POST['webexpert_woocommerce_order_tracking_order_carrier'])) {
			set_transient($transient_key, true, 0.5); // Lock expires in 0.5 second
			$order->update_meta_data('_webexpert_order_tracking_carrier', $_POST['webexpert_woocommerce_order_tracking_order_carrier']);
			$order->save();
			delete_transient($transient_key);
		}
	}

    function woocommerce_shop_order_search_voucher($search_fields) {
        $search_fields[] = '_shipping_tracking_number';
        return $search_fields;
    }

    function webexpert_order_tracking_add_tracking_column_header($columns) {
        $new_columns = array();

        foreach ( $columns as $column_name => $column_info ) {
            $new_columns[ $column_name ] = $column_info;
            if ( 'order_total' === $column_name ) {
                $new_columns['tracking_number'] = __( 'Tracking number', 'webexpert-woocommerce-order-tracking' );
            }
        }

        return $new_columns;
    }

	function webexpert_order_tracking_add_tracking_column_content($column, $post) {
		$order = is_int($post) ? wc_get_order( $post ) : (( $post instanceof WP_Post ) ? wc_get_order( $post->ID ) : $post);
		if ( 'tracking_number' === $column ) {
			if (!empty($order->get_meta('_shipping_tracking_number'))) {
				$job_id = !empty($order->get_meta('we_voucher_job_id')) ? $order->get_meta('we_voucher_job_id') : null;
				$vendor= get_post_meta( (int) $job_id, 'we_voucher_job_provider', true) ?? null;
				echo '<a data-title="Order #' . $order->get_id() . ' - Track & Trace" class="webexpert-voucher-tracking" data-voucher="' . $order->get_meta('_shipping_tracking_number') . '" data-order="' . $order->get_id() . '" data-vendor="' . $vendor . '" class="thickbox"><mark class="order-status status-completed"><span>' . $order->get_meta('_shipping_tracking_number') . '</span></mark></a> ';
				if (!empty($order->get_meta('voucher_delivery_status'))) {
					if (is_string($order->get_meta('voucher_delivery_status'))) {
						echo wc_help_tip("<small>".( $order->get_meta('voucher_delivery_status') ?? '-') . "</small>",true);
					}else if (is_object($order->get_meta('voucher_delivery_status'))) {
						echo wc_help_tip("<small>".( $order->get_meta('voucher_delivery_status')->web_status_title ?? '-') . "</small>",true);
					}
				}
				?>
                <div id="webexpert-voucher-tracking-<?php echo $order->get_id();?>" style="display:none;"></div>

				<?php
				$carrier=$order->get_meta('_webexpert_order_tracking_carrier') ? $order->get_meta('_webexpert_order_tracking_carrier') : null;
				if (!empty($carrier)) {
					$url = str_replace("{tracking_number}",$order->get_meta('_shipping_tracking_number'),$this->multi_carrier_add_tracking_url('',$order->get_id()));
					echo '<span onclick=\'event.stopPropagation();WebExpertopenInNewTab("'.$url.'"); return false;\' style="font-size: 14px;margin-top: 4px;" class="dashicons dashicons-external"></span>';
				}
			}
		}
	}

    function multi_carrier_metabox_content($post) {
	    $order = ( $post instanceof WP_Post ) ? wc_get_order( $post->ID ) : $post;
        if ($order) {
            $carriers=apply_filters('webexpert_order_tracking_carriers',[
	            'acs' => __('ACS Courier', 'webexpert-woocommerce-order-tracking'),
	            'boxnow' => __('BoxNow', 'webexpert-woocommerce-order-tracking'),
                'courier_center' => __('Courier Center', 'webexpert-woocommerce-order-tracking'),
                'taxydema' => __('Taxydema', 'webexpert-woocommerce-order-tracking'),
                'elta' => __('ELTA Courier', 'webexpert-woocommerce-order-tracking'),
                'geniki_taxydromiki' => __('Geniki Taxydromiki', 'webexpert-woocommerce-order-tracking'),
                'speedex' => __('Speedex', 'webexpert-woocommerce-order-tracking'),
            ]);
            $selected=!empty($order->get_meta('_webexpert_order_tracking_carrier')) ? $order->get_meta('_webexpert_order_tracking_carrier') : null;
            ?>
            <div class="webexpert-field-group">
                <div class="webexpert-field">
                    <p><small><em><?php echo __('Pick the preferred carrier before completing the order, so the automated email will include it\'s tracking names and URLs.', 'webexpert-woocommerce-order-tracking') ?></small></em></p>
                    <label for="webexpert_woocommerce_order_tracking_order_carrier" class="screen-reader-text"><?php echo __('Multi-Carriers', 'webexpert-woocommerce-order-tracking') ?></label>
                    <select name="webexpert_woocommerce_order_tracking_order_carrier" id="webexpert_woocommerce_order_tracking_order_carrier" placeholder="<?php echo __('Multi-Carriers', 'webexpert-woocommerce-order-tracking') ?>" style="display:block;width: 100%">
                        <option value=''><?php echo __('Pick the preferred carrier','webexpert-woocommerce-order-tracking') ?></option>
                        <?php
                        foreach ($carriers as $k=>$v) {
                            echo "<option value='$k'".($selected==$k ? 'selected' : '') .">$v</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <?php
        }
    }

    function multi_carrier_add_company_name($company_name,$order_id) {
        $order = wc_get_order($order_id);
        if ($order && get_option('webexpert_woocommerce_order_trackin_multiple_carriers',false)) {
            $carrier=!empty($order->get_meta('_webexpert_order_tracking_carrier')) ? $order->get_meta('_webexpert_order_tracking_carrier') : null;
            switch ($carrier) {
                case 'acs':
                    return __('ACS Courier','webexpert-woocommerce-order-tracking');
                    break;
	            case 'boxnow':
		            return __('BoxNow','webexpert-woocommerce-order-tracking');
		            break;
	            case 'courier_center':
		            return __('Courier Center','webexpert-woocommerce-order-tracking');
		            break;
	            case 'taxydema':
		            return __('Taxydema','webexpert-woocommerce-order-tracking');
		            break;
                case 'elta':
                    return __('ELTA Courier','webexpert-woocommerce-order-tracking');
                    break;
                case 'geniki_taxydromiki':
                    return __('Geniki Taxydromiki','webexpert-woocommerce-order-tracking');
                    break;
	            case 'speedex':
		            return __('Speedex','webexpert-woocommerce-order-tracking');
		            break;
                default:
                    return apply_filters('webexpert_order_tracking_custom_company_name',$carrier,$company_name);;
            }
        }
        return $company_name;
    }

    function multi_carrier_add_tracking_url($tracking_url, $order_id) {
        $order = wc_get_order($order_id);
        if ($order && get_option('webexpert_woocommerce_order_trackin_multiple_carriers',false)) {
            $carrier=!empty($order->get_meta('_webexpert_order_tracking_carrier')) ? $order->get_meta('_webexpert_order_tracking_carrier') : null;
            switch ($carrier) {
                case 'acs':
	                $tracking_url = 'https://www.acscourier.net/el/myacs/anafores-apostolwn/anazitisi-apostolwn/?trackingNumber={tracking_number}';
                    break;
                case 'boxnow':
	                $tracking_url = 'https://boxnow.gr/?track={tracking_number}';
	                break;
	            case 'courier_center':
		            $tracking_url = 'https://platform.courier.gr/tracking/pages/?awb={tracking_number}';
		            break;
	            case 'taxydema':
		            $tracking_url = 'https://www.taxydema.gr/entopismos/{tracking_number}';
		            break;
                case 'elta':
	                $tracking_url = 'https://www.elta-courier.gr/search?br={tracking_number}';
                    break;
                case 'geniki_taxydromiki':
	                $tracking_url = 'https://www.taxydromiki.com/track/{tracking_number}';
                    break;
                case 'speedex':
	                $tracking_url = "http://www.speedex.gr/isapohi.asp?voucher_code={tracking_number}";
                    break;
                default:
	                $tracking_url = '';
            }
        }
	    return  apply_filters('webexpert_order_tracking_custom_tracking_url',$tracking_url,$order);
    }

    function webexpert_order_tracking_license_admin_notices() {
        if (empty(get_option('webexpert_woocommerce_order_trackin_license_key')) || empty(get_option('webexpert_woocommerce_order_trackin_email'))) {
            ?>
            <div class="notice notice-error">
                <p><?php _e('Please activate <strong>Web Expert Order Tracking</strong> to enable all it\'s features and automatic updates.', 'webexpert-woocommerce-order-tracking'); ?></p>
            </div>
            <?php
        }
        if (get_option('woocommerce_order_trackin_valid_license',false)===false) {
            ?>
            <div class="notice notice-error">
                <p><?php _e('The license for <strong>Web Expert Order Tracking</strong> is invalid. Please fill in a valid license key.', 'webexpert-woocommerce-order-tracking'); ?></p>
            </div>
            <?php
        }
    }

    function order_tracking_plugin_action_links($links, $file)
    {
        static $this_plugin;
        if (!$this_plugin) {
            $this_plugin = ( dirname(plugin_basename(__FILE__), 2) . '/' . $this->plugin_name . '.php' );
        }
        if ($file == $this_plugin) {
            $settings_link = '<a href="' . admin_url("admin.php?page=webexpert-woocommerce-order-tracking").'">'.__('Settings').'</a>';
            $support_link = '<a target="_blank" href="https://support.webexpert.gr">' .__('Support').'</a>';
            array_unshift($links, $settings_link, $support_link);
        }
        return $links;
    }

    function webexpert_order_tracking_thickbox() {
        $order_id=sanitize_text_field($_POST['order_id']);
        $vendor=sanitize_text_field($_POST['vendor']);
        $voucher=sanitize_text_field($_POST['voucher']);
        $title=sanitize_text_field($_POST['title']);
        $response=[];

        $response['order_id']=$order_id;
        $response['html']='';
        $response['success']=true;

        if (!empty($vendor)) {
            switch ($vendor) {
                case "acs":
                    $response['html'] = "<div class='track-results-acs'><p><strong>".$title." (ACS)</strong></p>";
                    $response['html'] .= do_shortcode ('[webexpert_acs_track_checkpoints order_id="'.$order_id.'" voucher_no="'.$voucher.'"]');
                    $response['html'] .= "</div>";
                    break;
	            case "courier-center":
		            $response['html'] = "<div class='track-results-courier-center'><p><strong>".$title." (Courier Center)</strong></p>";
		            $response['html'] .= do_shortcode ('[webexpert_courier_center_track_checkpoints order_id="'.$order_id.'" voucher_no="'.$voucher.'"]');
		            $response['html'] .= "</div>";
		            break;
	            case "taxydema":
		            $response['html'] = "<div class='track-results-courier-center'><p><strong>".$title." (Taxydema)</strong></p>";
		            $response['html'] .= do_shortcode ('[webexpert_taxydema_courier_track_checkpoints order_id="'.$order_id.'" voucher_no="'.$voucher.'"]');
		            $response['html'] .= "</div>";
		            break;
                case "elta-courier":
                    $response['html'] = "<div class='track-results-elta'><p><strong>".$title." (ELTA)</strong></p>";
                    $response['html'] .= do_shortcode ('[webexpert_elta_courier_track_checkpoints order_id="'.$order_id.'" voucher_no="'.$voucher.'"]');
                    $response['html'] .= "</div>";
                    break;
                case "geniki-taxydromiki":
                    $response['html'] = "<div class='track-results-geniki-taxydromiki'><p><strong>".$title." (Geniki Taxydromiki)</strong></p>";
                    $response['html'] .= do_shortcode ('[webexpert_geniki_taxydromiki_track_checkpoints order_id="'.$order_id.'" voucher_no="'.$voucher.'"]');
                    $response['html'] .= "</div>";
                    break;
                case "speedex":
                    $response['html'] = "<div class='track-results-speedex'><p><strong>".$title." (Speedex)</strong></p>";
                    $response['html'] .= do_shortcode ('[webexpert_speedex_track_checkpoints order_id="'.$order_id.'" voucher_no="'.$voucher.'"]');
                    $response['html'] .= "</div>";
                    break;
                case "mycouriernow-courier":
                    $response['html'] = "<div class='track-results-mycouriernow'><p><strong>".$title." (MyCourier Now)</strong></p>";
                    $response['html'] .= do_shortcode ('[webexpert_mycouriernow_courier_track_checkpoints order_id="'.$order_id.'" voucher_no="'.$voucher.'"]');
                    $response['html'] .= "</div>";
                    break;
                case "taxydema-courier":
                    $response['html'] = "<div class='track-results-taxydema'><p><strong>".$title." (Taxydema)</strong></p>";
                    $response['html'] .= do_shortcode ('[webexpert_taxydema_courier_track_checkpoints order_id="'.$order_id.'" voucher_no="'.$voucher.'"]');
                    $response['html'] .= "</div>";
                    break;
                default:
                    $response['success']=false;
            }
        }

        wp_send_json($response);
    }

    function outputCsv( $assocDataArray ) {
        if ( !empty( $assocDataArray ) ):
            $fp = fopen( 'php://output', 'w' );
            fputcsv( $fp, array_keys( reset($assocDataArray) ) );
            foreach ( $assocDataArray AS $values ):
                fputcsv( $fp, $values );
            endforeach;
            fclose( $fp );
        endif;
    }

    function webexpert_order_tracking_export_orders() {
        $webexpert_woocommerce_order_trackin_date_from = str_replace("/","-",sanitize_text_field($_POST['webexpert_woocommerce_order_trackin_date_from']));
        $webexpert_woocommerce_order_trackin_date_to = str_replace("/","-",sanitize_text_field($_POST['webexpert_woocommerce_order_trackin_date_to']));
        $webexpert_woocommerce_order_trackin_order_status_export = $_POST['webexpert_woocommerce_order_trackin_order_status_export'];
        $webexpert_woocommerce_order_trackin_payment_methods_export = $_POST['webexpert_woocommerce_order_trackin_payment_methods_export'];
        $args = array(
            'limit'=>-1,
            'payment_method' => $webexpert_woocommerce_order_trackin_payment_methods_export,
            'status' => $webexpert_woocommerce_order_trackin_order_status_export,
            'date_created' =>date_i18n('Y-m-d',strtotime($webexpert_woocommerce_order_trackin_date_from)).'...'.date_i18n('Y-m-d',strtotime($webexpert_woocommerce_order_trackin_date_to))
        );
        $orders = wc_get_orders( $args );
        $csv=[];
        $order_statuses = wc_get_order_statuses();

        foreach ($orders as $order) {
            $job_id = !empty($order->get_meta('we_voucher_job_id')) ? $order->get_meta('we_voucher_job_id') : null;
            $carrier = !empty(get_post_meta($job_id, 'we_voucher_job_provider', true)) ? get_post_meta($job_id, 'we_voucher_job_provider', true) : null;
            if (empty($carrier)) {
                $carrier=!empty($order->get_meta('_webexpert_order_tracking_carrier')) ? $order->get_meta('_webexpert_order_tracking_carrier') : null;
            }

            if (empty($carrier)) {
                continue;
            }

            switch ($carrier) {
	            case 'acs':
		            $courier= __('ACS Courier','webexpert-woocommerce-order-tracking');
		            break;
	            case 'boxnow':
		            $courier= __('BoxNow','webexpert-woocommerce-order-tracking');
		            break;
                case 'courier_center':
                case 'courier-center':
                    $courier= __('Courier Center','webexpert-woocommerce-order-tracking');
                    break;
                case 'taxydema':
                case 'taxydema-courier':
                    $courier= __('Taxydema','webexpert-woocommerce-order-tracking');
                    break;
                case 'elta-courier':
                case 'elta':
                    $courier= __('ELTA Courier','webexpert-woocommerce-order-tracking');
                    break;
                case 'geniki_taxydromiki':
                case 'geniki-taxydromiki':
                    $courier= __('Geniki Taxydromiki','webexpert-woocommerce-order-tracking');
                    break;
                case 'speedex':
                    $courier= __('Speedex','webexpert-woocommerce-order-tracking');
                    break;
                default:
                    $courier="";
            }

            if (empty($courier)) continue;

            $csv[$order->get_id()][__('ID','webexpert-woocommerce-order-tracking')]=$order->get_id();
            $csv[$order->get_id()][__('Status','webexpert-woocommerce-order-tracking')]=$order_statuses["wc-{$order->get_status()}"] ?: '';
            $csv[$order->get_id()][__('Total','webexpert-woocommerce-order-tracking')]=$order->get_total();
            $csv[$order->get_id()][__('Buyer','webexpert-woocommerce-order-tracking')]=$order->get_billing_first_name()." ".$order->get_billing_last_name();
            $csv[$order->get_id()][__('Email','webexpert-woocommerce-order-tracking')]=$order->get_billing_email();
            $csv[$order->get_id()][__('Phone','webexpert-woocommerce-order-tracking')]=apply_filters('webexpert_acs_voucher_custom_phone_field',preg_replace("/[^0-9]/", "",$order->get_billing_phone()),$order);
            $csv[$order->get_id()][__('City','webexpert-woocommerce-order-tracking')]=$order->get_billing_city();
            $csv[$order->get_id()][__('Postcode','webexpert-woocommerce-order-tracking')]=$order->get_billing_postcode();
            $csv[$order->get_id()][__('Courier','webexpert-woocommerce-order-tracking')]=$courier;
            $csv[$order->get_id()][__('Voucher','webexpert-woocommerce-order-tracking')]=!empty($order->get_meta('_shipping_tracking_number')) ? $order->get_meta('_shipping_tracking_number') : "-";
            $csv[$order->get_id()][__('Comments','webexpert-woocommerce-order-tracking')]=!empty($order->get_customer_note()) ? $order->get_customer_note() : "-";
            $csv[$order->get_id()][__('Tracking','webexpert-woocommerce-order-tracking')]=!empty($order->get_meta('voucher_delivery_status')) ? $order->get_meta('voucher_delivery_status') : "-";
        }
        if (empty($csv))
            wp_send_json(['error'=>__('No orders found','webexpert-woocommerce-order-tracking')]);
        $this->outputCsv($csv);
        die();
    }

    function custom_conditional_email_notifications( $yesno, $object ) {
        if ($object instanceof WC_Order) {
            $settings=get_option('woocommerce_customer_tracking_number_settings');
            if (!empty($object->get_meta('_shipping_tracking_number')) && $settings['disable_order_completed_email'] == "yes") {
                return false;
            }
        }
        return $yesno;
    }
}
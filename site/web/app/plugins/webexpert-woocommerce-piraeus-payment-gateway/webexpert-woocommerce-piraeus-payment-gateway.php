<?php
/*
Plugin Name: Web Expert Piraeus Bank WooCommerce Payment Gateway
Plugin URI:  https://www.webexpert.gr/wordpress/piraeus-bank-woocommerce-payment-gateway
Description: Web Expert Piraeus Bank WooCommerce Payment Gateway allows you to accept payments through various credit cards such as Maestro, Mastercard, and Visa on your Woocommerce Site.
Version: 2.0.2
Requires at least: 4.0
Requires PHP:      7.0
Author: Web Expert
Author URI: http://www.webexpert.gr
License: Web Expert license
Text Domain: webexpert-woocommerce-piraeus-payment-gateway
WC requires at least: 3.0
WC tested up to: 9.8.1
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define('Webexpert_Piraeusbank_Payment_Gateway_Version', '2.0.2');
require plugin_dir_path( __FILE__ ).'includes/update/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=webexpert-woocommerce-piraeus-payment-gateway',
	__FILE__,
	'webexpert-woocommerce-piraeus-payment-gateway'
);
$myUpdateChecker->addQueryArgFilter('webexpert_woocommerce_piraeus_payment_gateway_update_checks');
function webexpert_woocommerce_piraeus_payment_gateway_update_checks($queryArgs) {
	$license = get_option('we_piraeus_payment_gateway_license_key');
	$domain = get_bloginfo('url');
	$parse = parse_url($domain);
	$domain = $parse['scheme'].'://'.$parse['host'];
	if ( !empty($license) ) {
		$queryArgs['license_key'] = $license;
	}
	if ( !empty($domain) ) {
		$queryArgs['domain'] = $domain;
	}
	return $queryArgs;
}


class WebExpert_Epay_Payments {
	public static function init() {
		load_plugin_textdomain('webexpert-woocommerce-piraeus-payment-gateway', false, dirname(plugin_basename(__FILE__)) . '/languages/');
		add_action( 'plugins_loaded', array( __CLASS__, 'includes' ), 0 );
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'add_gateway' ) );
		add_action( 'admin_menu', array( __CLASS__,'payment_gateway_options_page'));
		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'blocks_support' ) );
		add_action('admin_notices', array( __CLASS__, 'admin_notices' ));
		add_action('plugin_action_links', array( __CLASS__, 'action_links' ),10, 2);
		add_action( 'admin_init', array( __CLASS__, 'license_settings' ) );
		add_action( 'add_option_we_piraeus_payment_gateway_license_key', array( __CLASS__, 'license_callback_update' ),10,2 );
		add_action( 'update_option_we_piraeus_payment_gateway_license_key', array( __CLASS__, 'license_callback_update' ),10,2 );
		add_action("wp_ajax_webexpert_piraeus_transactions",array( __CLASS__, 'transactions' ));
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'transaction_log_scripts'));
		add_filter( 'cron_schedules', array( __CLASS__, 'payment_gateway_custom_cron_schedule'));
		add_action('webexpert_woocommerce_piraeus_payment_gateway_followup_cron_v2', array( __CLASS__, 'webexpert_woocommerce_piraeus_payment_gateway_followup_cron_fn'));
	}

	public static function webexpert_woocommerce_piraeus_payment_gateway_followup_cron_fn() {
		global $wpdb;
		if (apply_filters('webexpert_woocommerce_piraeus_payment_gateway_enable_followup',true)) {
			$args = array(
				'limit' => -1,
				'payment_method' => 'piraeusbank_gateway',
				'date_created' => date_i18n('Y-m-d H:i:s', current_time('timestamp') - HOUR_IN_SECONDS/2)."...".date_i18n('Y-m-d H:i:s', current_time('timestamp')),
				'status'=>['failed','pending']
			);
			$orders = wc_get_orders( $args );

			$pb=new WC_Piraeusbank_Gateway();
			foreach ($orders as $order) {
				$wc_order_status=$order->get_status();
				$MerchantReference=$wpdb->get_var( 'SELECT merch_ref FROM ' . $wpdb->prefix . 'piraeusbank_transactions WHERE merch_ref LIKE "'.$order->get_id().'-%"');
				$response=$pb->check_transaction($MerchantReference);
				if ($response->StatusFlag=="Success" && ($wc_order_status=="pending" || $wc_order_status=="failed")) {
					$SupportReferenceID = $response->SupportReferenceID;
					$TransactionID = $response->TransactionId;
					$order->add_order_note(__('Payment Via Peiraeus Bank (follow-up).','webexpert-woocommerce-piraeus-payment-gateway').'<br />MerchantReference: '.$MerchantReference.', SupportReferenceID: '.$SupportReferenceID.', Transaction ID: '.$TransactionID);
					$order->payment_complete($TransactionID);
					do_action( 'webexpert_woocommerce_piraeus_bank_success', $order->get_id());
				}
			}
		}
	}

	public static function add_gateway( $gateways ) {
		$options = get_option( 'woocommerce_piraeusbank_gateway_settings', array() );

		if ( isset( $options['hide_for_non_admin_users'] ) ) {
			$hide_for_non_admin_users = $options['hide_for_non_admin_users'];
		} else {
			$hide_for_non_admin_users = 'no';
		}

		if ( ( 'yes' === $hide_for_non_admin_users && current_user_can( 'manage_options' ) ) || 'no' === $hide_for_non_admin_users ) {
			$gateways[] = 'WC_Piraeusbank_Gateway';
		}
		return $gateways;
	}

	public static function payment_gateway_custom_cron_schedule( $schedules ) {
		$schedules['every_minute'] = array(
			'interval'  => 60,
			'display'   => __( 'Every minute', 'webexpert-woocommerce-piraeus-payment-gateway' )
		);
		$schedules['every_five_minutes'] = array(
			'interval'  => 5*60,
			'display'   => __( 'Every 5 minutes', 'webexpert-woocommerce-piraeus-payment-gateway' )
		);
		return $schedules;
	}

	public static function includes() {
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once 'includes/class-webexpert-epay-gateway.php';
		}
	}

	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	public static function plugin_abspath() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	static function payment_gateway_options_page()
	{
		$logo = file_get_contents(WebExpert_Epay_Payments::plugin_abspath().'assets/img/webexpert-icon.svg');
		if ( empty ( $GLOBALS['admin_page_hooks']['webexpert_plugins'] ) )
			add_menu_page('Web Expert Plugins','Web Expert','manage_woocommerce','webexpert_plugins',array(__CLASS__,'payment_gateway_settings_main_menu'),'data:image/svg+xml;base64,' . base64_encode($logo),20);
		add_submenu_page('webexpert_plugins',__( 'Piraeus Bank Gateway', 'webexpert-woocommerce-piraeus-payment-gateway' ), __( 'Piraeus Bank Gateway', 'webexpert-woocommerce-piraeus-payment-gateway' ),'manage_woocommerce','webexpert_piraeusbank_gateway',array(__CLASS__,'payment_gateway_options_page_html'));
		remove_submenu_page('webexpert_plugins','webexpert_plugins');
	}

	static function payment_gateway_settings_main_menu()
	{
		if (!current_user_can('manage_woocommerce')) {
			return;
		}
		?>
        <div class="wrap">
            <h1><?= esc_html(get_admin_page_title()); ?></h1>
            <p>Ευχαριστούμε που επιλέγε την Web Expert.</p>
        </div>
		<?php
	}

	static function payment_gateway_options_page_html()
	{
		if (!current_user_can('manage_woocommerce')) {
			return;
		}
		?>
        <div class="wrap">
            <h1><?= esc_html(get_admin_page_title()); ?></h1>
            <p><?php _e('You can setup the payment gateway at ', 'webexpert-woocommerce-piraeus-payment-gateway'); ?> <a href="<?php echo admin_url("admin.php?page=wc-settings&tab=checkout&section=piraeusbank_gateway"); ?>"><?php _e('WooCommerce Settings', 'webexpert-woocommerce-piraeus-payment-gateway'); ?></a>.</p>
            <p><?php _e('Please fill in the email address and the license key you received when purchasing the Piraeus Bank WooCoommerce Payment Gateway to enable automatic updates and ensure the full functionality of the plugin.', 'webexpert-woocommerce-piraeus-payment-gateway'); ?></p>
            <p><?php _e('For any inquiries please contact ', 'webexpert-woocommerce-piraeus-payment-gateway'); ?><a href="mailto:support@webexpert.gr">support@webexpert.gr</a> <?php _e('or visit our Helpdesk at ', 'webexpert-woocommerce-piraeus-payment-gateway'); ?> <a target="_blank" href="http://support.webexpert.gr">http://support.webexpert.gr</a></p>

            <form action="options.php" method="post">
				<?php
				settings_fields('woocommerce_piraeus_settings-group');
				do_settings_sections('woocommerce_piraeus_settings-group');
				?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Email</th>
                        <td><input type="text" name="we_piraeus_payment_gateway_email" value="<?php echo esc_attr( get_option('we_piraeus_payment_gateway_email') ); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">License Key</th>
                        <td><input type="text" name="we_piraeus_payment_gateway_license_key" value="<?php echo esc_attr( get_option('we_piraeus_payment_gateway_license_key') ); ?>" /></td>
                    </tr>
                </table>
				<?php submit_button();  ?>
                <input type="hidden" name="" value="<?php echo $_SERVER['HTTP_HOST'];?>">
            </form>
            <h2><?php _e('Instructions', 'webexpert-woocommerce-piraeus-payment-gateway'); ?></h2>
            <p><?php _e('If you have applied to Piraeus Bank ePOS, you will be requested to provide some information about your website. The details are', 'webexpert-woocommerce-piraeus-payment-gateway'); ?>:</p>
            <table class="form-table">
                <tr><th>Website url</th><td><?php echo rtrim(get_bloginfo('url'),'/');?></td></tr>
                <tr><th>Referrer page</th><td><?php echo wc_get_checkout_url();?></td></tr>
                <tr><th>Success page</th><td><?php echo rtrim(WC()->api_request_url('WC_Piraeusbank_Gateway?peiraeus=success'),'/');?></td></tr>
                <tr><th>Failure page</th><td><?php echo rtrim(WC()->api_request_url('WC_Piraeusbank_Gateway?peiraeus=fail'),'/');?></td></tr>
                <tr><th>Cancel page</th><td><?php echo rtrim(WC()->api_request_url('WC_Piraeusbank_Gateway?peiraeus=cancel'),'/');?></td></tr>
                <tr><th>Response method</th><td>GET/POST</td></tr>
                <tr><th>IP</th><td><?php echo @file_get_contents("https://api.ipify.org/?format=plain");?></td></tr>
                <tr><th><?php _e('Installments','webexpert-woocommerce-piraeus-payment-gateway');?></th><td>0</td></tr>
            </table>
        </div>
		<?php
	}
	static function action_links($links, $file) {
		static $this_plugin;
		if (!$this_plugin) {
			$this_plugin = plugin_basename(__FILE__);
		}

		if ($file == $this_plugin) {
			$settings_link = '<a href="' . admin_url("admin.php?page=wc-settings&tab=checkout&section=piraeusbank_gateway").'">'.__('Settings','webexpert-woocommerce-piraeus-payment-gateway').'</a>';
			$support_link = '<a target="_blank" href="https://support.webexpert.gr">'.__('Support','webexpert-woocommerce-piraeus-payment-gateway').'</a>';
			array_unshift($links, $settings_link, $support_link);
		}
		return $links;
	}

	static function admin_notices() {
		if (get_option('init_webexpert_woocommerce_piraeus_license_check',false)===false) {
			webexpert_woocommerce_piraeus_license_check();
			update_option('init_webexpert_woocommerce_piraeus_license_check',true);
		}

		if (empty(get_option('we_piraeus_payment_gateway_license_key')) || empty(get_option('we_piraeus_payment_gateway_email'))) {
			?>
            <div class="notice notice-error">
                <p><?php _e('Please activate <strong>Web Expert Piraeusbank WooCommerce Payment Gateway</strong> to enable all it\'s features and automatic updates.', 'webexpert-woocommerce-piraeus-payment-gateway'); ?></p>
            </div>
			<?php
		}
		if (get_option('we_piraeusbank_payment_gateway_valid_license',false)===false) {
			?>
            <div class="notice notice-error">
                <p><?php _e('The license for <strong>Web Expert Piraeusbank WooCommerce Payment Gateway</strong> is invalid. Please fill in a valid license key.', 'webexpert-woocommerce-piraeus-payment-gateway'); ?></p>
            </div>
			<?php
		}
	}

	static function license_settings()
	{
		register_setting( 'woocommerce_piraeus_settings-group', 'we_piraeus_payment_gateway_email' );
		register_setting( 'woocommerce_piraeus_settings-group', 'we_piraeus_payment_gateway_license_key' );
	}

	static function license_callback_update( $old_value, $new_value ) {
		$url='https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=webexpert-woocommerce-piraeus-payment-gateway&license_key='.$new_value.'&domain='.get_bloginfo('url');
		$request = wp_remote_get($url);
		$response = wp_remote_retrieve_body( $request );
		$s = json_decode($response);
		if (isset($s->download_url)) {
			update_option('we_piraeusbank_payment_gateway_valid_license',true);
		}else {
			delete_option('we_piraeusbank_payment_gateway_valid_license');
		}
	}

	static function transactions() {
		global $wpdb;
		$rows=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}piraeusbank_transactions ORDER BY id DESC LIMIT 20",ARRAY_A);
		wp_send_json(['type'=>'success','data'=>$rows]);
	}

	static function transaction_log_scripts($hook) {
		// Check if we are on the WooCommerce settings page
		if ($hook !== 'woocommerce_page_wc-settings') {
			return;
		}

		$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
		$section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';

		if ($tab === 'checkout' && $section === 'piraeusbank_gateway') {
			wp_enqueue_script( 'webexpert-woocommerce-piraeus-payment-gateway-transactions', WebExpert_Epay_Payments::plugin_url() . '/assets/js/admin/index.js', array( 'jquery' ), Webexpert_Piraeusbank_Payment_Gateway_Version );
			wp_localize_script( 'webexpert-woocommerce-piraeus-payment-gateway-transactions', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

			wp_register_style( 'webexpert-woocommerce-piraeus-payment-gateway-transactions', WebExpert_Epay_Payments::plugin_url() . '/assets/css/style.css', false, Webexpert_Piraeusbank_Payment_Gateway_Version );
			wp_enqueue_style( 'webexpert-woocommerce-piraeus-payment-gateway-transactions' );
		}
	}

	public static function blocks_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once 'includes/blocks/class-webexpert-epay-gateway-blocks.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WebExpert_Epay_Payments_Blocks_Support() );
				}
			);
		}
	}
}
WebExpert_Epay_Payments::init();

register_activation_hook( __FILE__, 'webexpert_woocommerce_piraeus_license_check' );
function webexpert_woocommerce_piraeus_license_check(){
    global $wpdb;
	$url='https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=webexpert-woocommerce-piraeus-payment-gateway&license_key='.get_option('we_piraeus_payment_gateway_license_key').'&domain='.get_bloginfo('url');
	$request = wp_remote_get($url);
	$response = wp_remote_retrieve_body( $request );
	$s = json_decode($response);
	if (isset($s->download_url)) {
		update_option('we_piraeusbank_payment_gateway_valid_license',true);
	}else {
		delete_option('we_piraeusbank_payment_gateway_valid_license');
	}
}

function webexpert_woocommerce_piraeus_payment_gateway_activation() {
	$timestamp = wp_next_scheduled ('webexpert_woocommerce_piraeus_payment_gateway_followup_cron');
	wp_unschedule_event ($timestamp, 'webexpert_woocommerce_piraeus_payment_gateway_followup_cron');
	if( !wp_next_scheduled( 'webexpert_woocommerce_piraeus_payment_gateway_followup_cron_v2' ) ) {
		wp_schedule_event( time(), 'every_five_minutes', 'webexpert_woocommerce_piraeus_payment_gateway_followup_cron_v2' );
	}
}
function webexpert_woocommerce_piraeus_payment_gateway_deactivate() {
	$timestamp = wp_next_scheduled ('webexpert_woocommerce_piraeus_payment_gateway_followup_cron_v2');
	wp_unschedule_event ($timestamp, 'webexpert_woocommerce_piraeus_payment_gateway_followup_cron_v2');
	$timestamp = wp_next_scheduled ('webexpert_woocommerce_piraeus_payment_gateway_followup_cron');
	wp_unschedule_event ($timestamp, 'webexpert_woocommerce_piraeus_payment_gateway_followup_cron');
}
register_deactivation_hook (__FILE__, 'webexpert_woocommerce_piraeus_payment_gateway_deactivate');
register_activation_hook( __FILE__, 'webexpert_woocommerce_piraeus_payment_gateway_activation' );

add_action('plugins_loaded', 'woocommerce_piraeusbank_init', 0);

function woocommerce_piraeusbank_init() {
	if (!class_exists('WC_Payment_Gateway'))
		return;

	function piraeusbank_success_message($txt, $order)
	{
		$piraeusbank_message = $order->get_meta('_piraeusbank_message');
		if (!empty($piraeusbank_message) && is_array($piraeusbank_message)) {
			$txt = $piraeusbank_message['message'];
		}
		$order->delete_meta_data('_piraeusbank_message');
        $order->save();
		return $txt;
	}

	function piraeusbank_message() {
		$order_id=null;
		if (is_cart()) {
			if (isset($_GET['order']))
				$order_id=wc_get_order_id_by_order_key($_GET['order']);
		}else {
			$order_id = absint(get_query_var('order-received'));
		}
		$order = wc_get_order($order_id);
		if ($order) {
			if (is_order_received_page() && ( 'piraeusbank_gateway' == $order->get_payment_method() )) {
				$piraeusbank_message = $order->get_meta('_piraeusbank_message');
				if (!empty($piraeusbank_message) && is_array($piraeusbank_message)) {
					$message = $piraeusbank_message['message'];
					$message_type = $piraeusbank_message['message_type'];
					if ($message_type == "success") {
						add_filter('woocommerce_thankyou_order_received_text', 'piraeusbank_success_message', 10, 2);
					} else {
						wc_add_notice($message, $message_type);
                        $order->delete_meta_data('_piraeusbank_message');
                        $order->save();
					}
				}
			}
		}
	}
	add_action('wp', 'piraeusbank_message');
}

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );
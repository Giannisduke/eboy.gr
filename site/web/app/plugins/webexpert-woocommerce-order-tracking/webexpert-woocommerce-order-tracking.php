<?php
/**
 * @link              https://www.webexpert.gr
 * @since             1.0.0
 * @package           Webexpert_woocommerce_order_tracking
 *
 * @wordpress-plugin
 * Plugin Name:       Web Expert WooCommerce Order Tracking
 * Plugin URI:        https://www.webexpert.gr/wordpress/webexpert-woocommerce-order-tracking
 * Description:       Web Expert WooCommerce Order Tracking allows you to send an automated email with shipping tracking number via email. It can be used with Web Expert WooCommerce SMS to send tracking number via SMS as well.
 * Version:           1.0.30
 * Requires at least: 4.0
 * Requires PHP:      7.0
 * Author:            Web Expert
 * Author URI:        https://www.webexpert.gr
 * License:           Web Expert license
 * Text Domain:       webexpert-woocommerce-order-tracking
 * Domain Path:       /languages
 * WC requires at least: 3.0
 * WC tested up to: 9.3.3
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}
if ( ! defined('CUSTOM_WC_EMAIL_PATH_FOR_ORDER_TRACKING'))
    define( 'CUSTOM_WC_EMAIL_PATH_FOR_ORDER_TRACKING', plugin_dir_path( __FILE__ ) );

define( 'Webexpert_woocommerce_order_tracking_Version', '1.0.30' );
defined('ABSPATH') or die('No script kiddies please!');
require plugin_dir_path(__FILE__) . 'includes/update/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker('https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=webexpert-woocommerce-order-tracking', __FILE__, 'webexpert-woocommerce-order-tracking');
$myUpdateChecker->addQueryArgFilter('webexpert_woocommerce_order_trackin_update_checks');
function webexpert_woocommerce_order_trackin_update_checks($queryArgs) {
    $license = get_option('webexpert_woocommerce_order_trackin_license_key');
    $domain  = get_bloginfo('url');
    $parse   = parse_url($domain);
    $domain  = $parse['scheme'] . '://' . $parse['host'];
    if (!empty($license)) {
        $queryArgs['license_key'] = $license;
    }
    if (!empty($domain)) {
        $queryArgs['domain'] = $domain;
    }
    return $queryArgs;
}

function init_webexpert_order_tracking_license_check() {
	if (get_option('init_webexpert_order_tracking_license_check',false)==false) {
		webexpert_order_tracking_license_check();
		update_option('init_webexpert_order_tracking_license_check',true);
	}
}
add_action('admin_notices', 'init_webexpert_order_tracking_license_check');
register_activation_hook( __FILE__, 'webexpert_order_tracking_license_check' );
function webexpert_order_tracking_license_check(){
	$url='https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=webexpert-woocommerce-order-tracking&license_key='.get_option('webexpert_woocommerce_order_trackin_license_key').'&domain='.get_bloginfo('url');
	$request = wp_remote_get($url);
	$response = wp_remote_retrieve_body( $request );
	$s = json_decode($response);
	if (isset($s->download_url)) {
		update_option('woocommerce_order_trackin_valid_license',true);
	}else {
		delete_option('woocommerce_order_trackin_valid_license');
	}
}

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

add_action('admin_menu', 'webexpert_woocommerce_order_trackin_options_page');
function webexpert_woocommerce_order_trackin_options_page() {
    $logo = file_get_contents(plugin_dir_path(__FILE__).'assets/webexpert-icon.svg');
	if (empty($GLOBALS['admin_page_hooks']['webexpert_plugins']))
		add_menu_page('Web Expert Plugins', 'Web Expert', 'manage_woocommerce', 'webexpert_plugins', 'webexpert_woocommerce_order_trackin_settings_main_menu','data:image/svg+xml;base64,' . base64_encode($logo),20);
	add_submenu_page('webexpert_plugins', __('WooCommerce Order Tracking', 'webexpert-woocommerce-order-tracking'), __('WooCommerce Order Tracking', 'webexpert-woocommerce-order-tracking'), 'manage_woocommerce', 'webexpert-woocommerce-order-tracking', 'webexpert_woocommerce_order_trackin_options_page_html');
	remove_submenu_page('webexpert_plugins', 'webexpert_plugins');
}
add_action('admin_init', 'webexpert_woocommerce_order_trackin_settings');
function webexpert_woocommerce_order_trackin_settings() {
	register_setting('webexpert-woocommerce-order-tracking-settings-group', 'webexpert_woocommerce_order_trackin_multiple_carriers');
	register_setting('webexpert-woocommerce-order-tracking-settings-group', 'webexpert_woocommerce_order_trackin_email');
	register_setting('webexpert-woocommerce-order-tracking-settings-group', 'webexpert_woocommerce_order_trackin_license_key');
}
function webexpert_woocommerce_order_trackin_settings_main_menu() {
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
add_action('add_option_webexpert_woocommerce_order_trackin_license_key', 'webexpert_order_tracking_callback_update', 10, 2);
add_action('update_option_webexpert_woocommerce_order_trackin_license_key', 'webexpert_order_tracking_callback_update', 10, 2);
function webexpert_order_tracking_callback_update( $old_value, $new_value ) {
	$url='https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=webexpert-woocommerce-order-tracking&license_key='.$new_value.'&domain='.get_bloginfo('url');
	$request = wp_remote_get($url);
	$response = wp_remote_retrieve_body( $request );
	$s = json_decode($response);
	if (isset($s->download_url)) {
		update_option('woocommerce_order_trackin_valid_license',true);
	}else {
		delete_option('woocommerce_order_trackin_valid_license');
	}
}
function webexpert_woocommerce_order_trackin_options_page_html() {
	if (!current_user_can('manage_woocommerce')) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?= esc_html(get_admin_page_title()); ?></h1>
        <p><?php _e('You can setup the plugin at', 'webexpert-woocommerce-order-tracking'); ?> <a href="<?php echo admin_url("admin.php?page=wc-settings&tab=email"); ?>"><?php _e('WooCommerce Settings', 'webexpert-woocommerce-order-tracking'); ?></a>.</p>
        <p><?php _e('Please fill in the email address and the license key you received when purchasing the WooCommerce Order Tracking to enable automatic updates and ensure the full functionality of the plugin.', 'webexpert-woocommerce-order-tracking'); ?></p>
        <p><?php _e('For any inquiries please contact ', 'webexpert-woocommerce-order-tracking'); ?><a href="mailto:support@webexpert.gr">support@webexpert.gr</a> <?php _e('or visit our Helpdesk at ', 'webexpert-woocommerce-order-tracking'); ?> <a target="_blank" href="http://support.webexpert.gr">http://support.webexpert.gr</a></p>

        <form action="options.php" method="post">
			<?php
			settings_fields('webexpert-woocommerce-order-tracking-settings-group');
			do_settings_sections('webexpert-woocommerce-order-tracking-settings-group');
			?>
            <h2 class="title"><?php _e('Settings', 'webexpert-woocommerce-order-tracking');?></h2>
			<table class="form-table">
                <tr valign="top"><th scope="row"><?php _e('Multiple Vendors', 'webexpert-woocommerce-order-tracking');?></th>
                    <td><label for="webexpert_woocommerce_order_trackin_multiple_carriers">
                    <input name="webexpert_woocommerce_order_trackin_multiple_carriers" type="checkbox" id="webexpert_woocommerce_order_trackin_multiple_carriers" value="1" <?php echo esc_attr(get_option('webexpert_woocommerce_order_trackin_multiple_carriers'),0) ? 'checked' : '';?>>
                            <?php echo __('Enable multi-carrier feature only if you have multiple carriers as shipping option', 'webexpert-woocommerce-order-tracking');?></label></td></tr>
                <tr valign="top"><th scope="row">Email</th><td><input type="text" name="webexpert_woocommerce_order_trackin_email" value="<?php echo esc_attr(get_option('webexpert_woocommerce_order_trackin_email'));?>" /></td></tr>
				<tr valign="top"><th scope="row">License Key</th><td><input type="text" name="webexpert_woocommerce_order_trackin_license_key" value="<?php echo esc_attr(get_option('webexpert_woocommerce_order_trackin_license_key')); ?>" /></td></tr>
			</table>
			<?php submit_button(); ?>
			<input type="hidden" name="" value="<?php echo $_SERVER['HTTP_HOST'];?>">
		</form>
        <h2 class="title"><?php _e('Export vouchers', 'webexpert-woocommerce-order-tracking');?></h2>
        <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="webexpert-woocommerce-export-orders">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="webexpert_woocommerce_order_trackin_order_statuses"><?php _e('Order Statuses','webexpert-woocommerce-order-tracking');?></label></th>
                    <td>
                        <?php
                        $order_statuses = wc_get_order_statuses();
                        foreach ($order_statuses as $key=>$order_status) {
                            if (in_array($key,apply_filters('webexpert_woocommerce_order_trackin_order_status_exclusion',['wc-failed','wc-cancelled','wc-pending','wc-refunded']))) continue;
                            ?>
                            <label><input <?php echo (in_array($key,['wc-processing','wc-completed']) ? 'checked' : '');?> type="checkbox" name="webexpert_woocommerce_order_trackin_order_status_export[]" value="<?php echo $key;?>"><?php echo $order_status;?></label>&nbsp;
                            <?php
                        }
                        ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="webexpert_woocommerce_order_trackin_payment_methods"><?php _e('Payment Methods','webexpert-woocommerce-order-tracking');?></label></th>
                    <td>
                    <?php
                    $available_payment_methods = WC()->payment_gateways()->get_available_payment_gateways();
                    foreach ($available_payment_methods as $payment_method=>$available_payment_method) {
                        ?>
                        <label><input <?php echo ($payment_method == 'cod' ? 'checked' : '');?> type="checkbox" name="webexpert_woocommerce_order_trackin_payment_methods_export[]" value="<?php echo $payment_method;?>"><?php echo $available_payment_method->get_title();?></label>&nbsp;
                        <?php
                    }
                    ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="webexpert_woocommerce_order_trackin_date_from"><?php _e('Date from','webexpert-woocommerce-order-tracking');?></label></th>
                    <td>
                        <input type="text" value="<?php echo date_i18n('01/m/Y');?>" class="datepicker" id="webexpert_woocommerce_order_trackin_date_from" name="webexpert_woocommerce_order_trackin_date_from">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="webexpert_woocommerce_order_trackin_date_to"><?php _e('Date to','webexpert-woocommerce-order-tracking');?></label></th>
                    <td>
                        <input type="text" value="<?php echo date_i18n('d/m/Y');?>" class="datepicker" id="webexpert_woocommerce_order_trackin_date_to" name="webexpert_woocommerce_order_trackin_date_to">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"></th>
                    <td>
                        <button type="submit" name="submit" id="submit" class="button button-primary has-spinner"><?php _e('Export','webexpert-woocommerce-order-tracking');?> <span class="we_spinner"></span></button>
                        <ul class="pickup_list_result_container"></ul>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="action" value="webexpert_order_tracking_export_orders">
        </form>
	</div>
	<?php
}

require plugin_dir_path( __FILE__ ) . 'includes/class-webexpert-woocommerce-order-tracking.php';

function run_webexpert_woocommerce_order_trackingemail() {
    $plugin = new Webexpert_woocommerce_order_tracking();
    $plugin->run();
}
run_webexpert_woocommerce_order_trackingemail();
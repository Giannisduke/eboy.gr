<?php
/**
 * @link              https://www.webexpert.gr/
 * @since             1.0.0
 * @package           Webexpert_Timologio_For_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Timologio for Woocommerce
 * Plugin URI:        https://www.webexpert.gr/wordpress/timologio-for-woocommerce
 * Description:       Web Expert Timologio for WooCommerce extends WooCommerce to support invoice functionality on checkout, according to Greek law. Optionally incorporates exemption option due to VIES or POL1150 / 2017.
 * Version:           2.1.33
 * Requires at least: 4.0
 * Requires PHP:      7.0
 * Author:            Web Expert
 * Author URI:        https://www.webexpert.gr/
 * License:           Web Expert license
 * Text Domain:       webexpert-timologio-for-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 3.0
 * WC tested up to: 9.3.3
 */

define('Webexpert_Timologio_For_Woocommerce_Version', '2.1.33');

if (!defined('WPINC')) {
	die;
}

if (!defined('CUSTOM_WC_EMAIL_PATH'))
    define( 'CUSTOM_WC_EMAIL_PATH', plugin_dir_path( __FILE__ ) );

if (!function_exists('webexpert_get_template')) {
    function webexpert_locate_template($template_name, $template_path = '', $default_path = '')
    {
// Set variable to search in woocommerce-plugin-templates folder of theme.
        if (!$template_path) :
            $template_path = 'webexpert-timologio-for-woocommerce/';
        endif;
// Set default plugin templates path.
        if (!$default_path) :
            $default_path = plugin_dir_path(__FILE__) . 'templates/'; // Path to the template folder
        endif;
// Search template file in theme folder.
        $template = locate_template(array(
            $template_path . $template_name,
            $template_name
        ));
// Get plugins template file.
        if (!$template) :
            $template = $default_path . $template_name;
        endif;
        return apply_filters('webexpert_locate_template', $template, $template_name, $template_path, $default_path);
    }
}

if (!function_exists('webexpert_get_template')) {
    function webexpert_get_template( $template_name, $args = array(), $tempate_path = '', $default_path = '' ) {

        if ( is_array( $args ) && isset( $args ) ) :
            extract( $args );
        endif;

        $template_file = webexpert_locate_template( $template_name, $tempate_path, $default_path );

        if ( ! file_exists( $template_file ) ) :
            _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_file ), '1.0.0' );
            return;
        endif;

        include $template_file;
    }
}

function ajaxLoaderWebexpertTimologio()
{
		return plugins_url( 'public/img/ajax-loader.gif', __FILE__ );
}

function getInvoiceTemplate($data = [], $order=null)
{
	ob_start();
    webexpert_get_template( 'invoice-template.php',['order'=>$order,'data'=>$data]);
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}

function productTaxPercentage($product) {
	$tax = new WC_Tax();
	$taxes = $tax->get_rates($product->get_tax_class());
	$rates = array_shift($taxes);
	$item_rate = round(array_shift($rates));
	$totalTax = floatval($item_rate);
	return $totalTax;
}

function getTaxPercentage($className) {
	$tax = new WC_Tax();
	$taxes = $tax->get_rates($className);
	$rates = array_shift($taxes);
	$item_rate = round(array_shift($rates));
	$totalTax = floatval($item_rate);
	return $totalTax;
}

require plugin_dir_path(__FILE__) . 'includes/update/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker('https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=webexpert-timologio-for-woocommerce', __FILE__, 'webexpert-timologio-for-woocommerce');
$myUpdateChecker->addQueryArgFilter('webexpert_timologio_for_woocommerce_update_checks');
function webexpert_timologio_for_woocommerce_update_checks($queryArgs) {
	$license = get_option('we_timologio_for_wc_email_license_key');
	$domain = get_bloginfo('url');
	$parse = parse_url($domain);
	$domain = $parse['scheme'] . '://' . $parse['host'];
	if (!empty($license)) {
		$queryArgs['license_key'] = $license;
	}
	if (!empty($domain)) {
		$queryArgs['domain'] = $domain;
	}
	return $queryArgs;
}

function init_webexpert_timologio_for_woocommerce_license_check() {
	if (get_option('init_webexpert_timologio_for_woocommerce_license_check',false)===false) {
		webexpert_timologio_for_woocommerce_license_check();
		update_option('init_webexpert_timologio_for_woocommerce_license_check',true);
	}
}
add_action('admin_notices', 'webexpert_timologio_for_woocommerce_license_check');
register_activation_hook( __FILE__, 'webexpert_timologio_for_woocommerce_license_check' );

function webexpert_timologio_for_woocommerce_license_check(){
	$url='https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=webexpert-timologio-for-woocommerce&license_key='.get_option('we_timologio_for_wc_email_license_key').'&domain='.get_bloginfo('url');
	$request = wp_remote_get($url);
	$response = wp_remote_retrieve_body( $request );
	$s = json_decode($response);
	if (isset($s->download_url)) {
		update_option('we_timologio_for_wc_valid_license',true);
	}else {
		delete_option('we_timologio_for_wc_valid_license');
	}
}

add_action('admin_menu', 'webexpert_timologio_for_woocommerce_options_page');
function webexpert_timologio_for_woocommerce_options_page() {
    $logo = file_get_contents(plugin_dir_path(__FILE__).'assets/webexpert-icon.svg');
	if (empty($GLOBALS['admin_page_hooks']['webexpert_plugins'])) {
		add_menu_page('Web Expert Plugins', 'Web Expert', 'manage_woocommerce', 'webexpert_plugins', 'webexpert_timologio_for_woocommerce_settings_main_menu','data:image/svg+xml;base64,' . base64_encode($logo),20);
	}
	add_submenu_page('webexpert_plugins', __('Timologio for WooCommerce', 'webexpert-timologio-for-woocommerce'), __('Timologio for WooCommerce', 'webexpert-timologio-for-woocommerce'), 'manage_woocommerce', 'webexpert-timologio-for-woocommerce', 'webexpert_timologio_for_woocommerce_options_page_html');
	remove_submenu_page('webexpert_plugins', 'webexpert_plugins');
}

add_action('admin_init', 'webexpert_timologio_for_woocommerce_settings');

function webexpert_timologio_for_woocommerce_settings() {
	//register our settings
	register_setting('webexpert-timologio-for-woocommerce-group', 'we_timologio_for_wc_email');
	register_setting('webexpert-timologio-for-woocommerce-group', 'we_timologio_for_wc_email_license_key');
	register_setting('webexpert-timologio-for-woocommerce-group', 'woocommerce_store_vat_id');
	register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_for_woocommerce_aade_username');
	register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_for_woocommerce_aade_password');
	register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_for_woocommerce_validate_aade');
	register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_for_woocommerce_validate_vies');
	register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_for_woocommerce_validate_aade_autocomplete');

	register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_for_woocommerce_exempt_valid_vies');
	register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_for_woocommerce_exempt_non_europe_country');
	register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_for_woocommerce_debug_mode');
	register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_for_woocommerce_aade_down_mode');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_uppercase_checkout');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_invoice_serial');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_receipt_serial');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_credit_serial');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_cancel_serial');

    register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_return_receipt_serial');
    register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_receipt_return_starting');

    register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_cancel_receipt_serial');
    register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_cancel_receipt_starting');

    register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_return_receipt_serial');
    register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_receipt_return_starting');

	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_invoice_starting');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_receipt_starting');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_credit_starting');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_cancel_starting');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_invoice_logo');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_vat_exempt_categories');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_vat_exempt_auto_if_valid');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_vat_exempt_tax_class');

	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_enable_island_reduced_tax');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_24');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_13');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_6');

	register_setting('webexpert-timologio-for-woocommerce-group' ,  'webexpert_timologio_for_woocommerce_invoice_shop_name'  );
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_invoice_qr_delimiter');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_invoice_qr_digit_seperation');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_invoice_qr_thousands_seperation');
	register_setting('webexpert-timologio-for-woocommerce-group' ,  'webexpert_timologio_for_woocommerce_invoice_vat_number' );
    register_setting('webexpert-timologio-for-woocommerce-group' ,  'webexpert_timologio_for_woocommerce_invoice_email_address' );
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_invoice_phone_number');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_invoice_tax_office');

	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_qr_receipt_code');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_qr_credit_code');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_qr_invoice_code');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_qr_cancel_code');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_qr_cancel_receipt_code');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_qr_return_receipt_code');
	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_for_woocommerce_qr_delivery_note_code');

	register_setting('webexpert-timologio-for-woocommerce-group' , 'webexpert_timologio_auto_finalize_and_send_invoice');
    register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_auto_generate_invoice');
    register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_hide_symboloseira');
	register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_for_woocommerce_display_as');
	register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_for_woocommerce_repositioning_fix');
	register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_for_woocommerce_tax_office_dropdown');
	register_setting('webexpert-timologio-for-woocommerce-group', 'webexpert_timologio_for_woocommerce_39a_page');
}

function webexpert_timologio_for_woocommerce_settings_main_menu() {
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

add_action('add_option_we_timologio_for_wc_email_license_key', 'webexpert_timologio_for_woocommerce_callback_update', 10, 2);
add_action('update_option_we_timologio_for_wc_email_license_key', 'webexpert_timologio_for_woocommerce_callback_update', 10, 2);
function webexpert_timologio_for_woocommerce_callback_update( $old_value, $new_value ) {
	$url='https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=webexpert-timologio-for-woocommerce&license_key='.$new_value.'&domain='.get_bloginfo('url');
	$request = wp_remote_get($url);
	$response = wp_remote_retrieve_body( $request );
	$s = json_decode($response);
	if (isset($s->download_url)) {
		update_option('we_timologio_for_wc_valid_license',true);
	}else {
		delete_option('we_timologio_for_wc_valid_license');
	}
}

function webexpert_timologio_for_woocommerce_get_term_path($term, $taxonomy = 'product_cat') {
	// Get the ancestors of the term
	$ancestor_ids = get_ancestors($term->term_id, $taxonomy);
	$ancestor_ids=array_reverse($ancestor_ids);
	// Get the names of the ancestor terms
	$ancestor_names = [];
	foreach ($ancestor_ids as $ancestor_id) {
		$ancestor = get_term($ancestor_id, $taxonomy);
		if ($ancestor && !is_wp_error($ancestor)) {
			$ancestor_names[] = $ancestor->name;
		}
	}
	$ancestor_names[] = $term->name;

	return implode(" > ",$ancestor_names);
}

function webexpert_timologio_for_woocommerce_options_page_html() {
	if (!current_user_can('manage_woocommerce')) {
		return;
	}
	?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
			<?php
			settings_fields('webexpert-timologio-for-woocommerce-group');
			do_settings_sections('webexpert-timologio-for-woocommerce-group');
			?>
            <h2 class="title"><?php _e('Checkout settings','webexpert-timologio-for-woocommerce');?></h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_store_vat_id"><?php _e('VAT ID','webexpert-timologio-for-woocommerce');?></label>
                    </th>
                    <td class="forminp forminp-text">
                        <input name="woocommerce_store_vat_id" id="woocommerce_store_vat_id" type="text" style="" value="<?php echo get_option('woocommerce_store_vat_id');?>" class="" placeholder=""> 							</td>
                </tr>

                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="webexpert_timologio_for_woocommerce_aade_username"><?php _e('IAPR Username', 'webexpert-timologio-for-woocommerce');?></label>
                    </th>
                    <td class="forminp forminp-text">
                        <input name="webexpert_timologio_for_woocommerce_aade_username" id="webexpert_timologio_for_woocommerce_aade_username" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_aade_username');?>" class="" placeholder=""> 							</td>
                </tr>

								<tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="webexpert_timologio_for_woocommerce_aade_password"><?php _e('IAPR Password', 'webexpert-timologio-for-woocommerce');?></label>
                    </th>
                    <td class="forminp forminp-text">
                        <input name="webexpert_timologio_for_woocommerce_aade_password" id="webexpert_timologio_for_woocommerce_aade_password" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_aade_password');?>" class="" placeholder=""> 							</td>
                </tr>

								<tr valign="top">
										<th scope="row" class="titledesc">
												<button type="button" class="button" id="webexpert_timologio_for_woocommerce_check_aade_connection">
													<?php _e('Check IAPR Connection' , 'webexpert-timologio-for-woocommerce'); ?>
												</button>
										</th>
										<td class="forminp forminp-text">
										</td>
								</tr>

                <tr valign="top" class="">
                    <th scope="row" class="titledesc"><?php _e('VAT Validation', 'webexpert-timologio-for-woocommerce'); ?></th>
                    <td class="forminp forminp-checkbox">
                        <fieldset>
                            <label for="webexpert_timologio_for_woocommerce_validate_aade">
                                <input name="webexpert_timologio_for_woocommerce_validate_aade" id="webexpert_timologio_for_woocommerce_validate_aade" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_for_woocommerce_validate_aade') == 'yes' ? 'checked' : '') ?>> <?php _e('Validate VAT number via IAPR.', 'webexpert-timologio-for-woocommerce');
					            ?></label>
                        </fieldset>
                        <fieldset>
                            <label for="webexpert_timologio_for_woocommerce_validate_aade_autocomplete">
                                <input name="webexpert_timologio_for_woocommerce_validate_aade_autocomplete" id="webexpert_timologio_for_woocommerce_validate_aade_autocomplete" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_for_woocommerce_validate_aade_autocomplete') == 'yes' ? 'checked' : '') ?>> <?php _e('Autocomplete details via IAPR.', 'webexpert-timologio-for-woocommerce');
			                    ?></label><br>
                        </fieldset>
                        <fieldset class="">
                            <label for="webexpert_timologio_for_woocommerce_validate_vies">
                                <input name="webexpert_timologio_for_woocommerce_validate_vies" id="webexpert_timologio_for_woocommerce_validate_vies" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_for_woocommerce_validate_vies') == 'yes' ? 'checked' : '') ?>> <?php _e('Validate VAT number via VIES.', 'webexpert-timologio-for-woocommerce'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr valign="top" class="">
                    <th scope="row" class="titledesc"><?php _e('Debug', 'webexpert-timologio-for-woocommerce');?></th>
                    <td class="forminp forminp-checkbox">
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e('Debug mode', 'webexpert-timologio-for-woocommerce'); ?></span></legend>
                            <label for="webexpert_timologio_for_woocommerce_debug_mode">
                                <input name="webexpert_timologio_for_woocommerce_debug_mode" id="webexpert_timologio_for_woocommerce_debug_mode" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_for_woocommerce_debug_mode','') == 'yes' ? 'checked' : '') ?>> <?php _e('Debug mode', 'webexpert-timologio-for-woocommerce'); ?></label>
                        </fieldset>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e('IAPR offline mode', 'webexpert-timologio-for-woocommerce'); ?></span></legend>
                            <label for="webexpert_timologio_for_woocommerce_aade_down_mode">
                                <input name="webexpert_timologio_for_woocommerce_aade_down_mode" id="webexpert_timologio_for_woocommerce_aade_down_mode" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_for_woocommerce_aade_down_mode','') == 'yes' ? 'checked' : '') ?>> <?php _e('Return true if IAPR service is down', 'webexpert-timologio-for-woocommerce'); ?></label>
                        </fieldset>
                    </td>
                </tr>
						<tr valign="top" class="">
								<th scope="row" class="titledesc"><?php _e('Display', 'webexpert-timologio-for-woocommerce');?></th>
								<td class="forminp forminp-checkbox">
                                    <fieldset>
                                        <legend class="screen-reader-text"><span><?php _e('Display as', 'webexpert-timologio-for-woocommerce'); ?></span></legend>
                                        <label for="webexpert_timologio_for_woocommerce_display_as">
                                            <input name="webexpert_timologio_for_woocommerce_display_as" id="webexpert_timologio_for_woocommerce_display_as" type="radio" value="select" <?php echo(get_option('webexpert_timologio_for_woocommerce_display_as','select') == 'select' ? 'checked' : '') ?>> <?php _e('Display invoice option as select box', 'webexpert-timologio-for-woocommerce'); ?>
                                        </label>
                                    </fieldset>
                                    <fieldset>
                                        <label for="webexpert_timologio_for_woocommerce_display_as_radio">
                                            <input name="webexpert_timologio_for_woocommerce_display_as" id="webexpert_timologio_for_woocommerce_display_as_radio" type="radio" value="radio" <?php echo(get_option('webexpert_timologio_for_woocommerce_display_as','select') == 'radio' ? 'checked' : '') ?>> <?php _e('Display invoice option as radio', 'webexpert-timologio-for-woocommerce'); ?>
                                        </label>
                                    </fieldset>
                                    <fieldset>
                                        <label for="webexpert_timologio_for_woocommerce_repositioning_fix">
                                            <input name="webexpert_timologio_for_woocommerce_repositioning_fix" id="webexpert_timologio_for_woocommerce_repositioning_fix" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_for_woocommerce_repositioning_fix','no') == 'yes' ? 'checked' : '') ?>> <?php _e('Repositioning invoice option while using select2 (Appearance bug fix)', 'webexpert-timologio-for-woocommerce'); ?>
                                        </label>
                                    </fieldset>
                                    <p>&nbsp;</p>
                                    <fieldset>
                                        <legend class="screen-reader-text"><span><?php _e('Tax offices', 'webexpert-timologio-for-woocommerce'); ?></span></legend>
                                        <label for="webexpert_timologio_for_woocommerce_tax_office_dropdown">
                                            <input name="webexpert_timologio_for_woocommerce_tax_office_dropdown" id="webexpert_timologio_for_woocommerce_tax_office_dropdown" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_for_woocommerce_tax_office_dropdown') == 'yes' ? 'checked' : '') ?>> <?php _e('Show tax offices as drop-down', 'webexpert-timologio-for-woocommerce'); ?>
                                        </label>
                                    </fieldset>
                                    <p>&nbsp;</p>
                                    <fieldset>
                                        <legend class="screen-reader-text"><span><?php _e('Uppercase Checkout Fields', 'webexpert-timologio-for-woocommerce'); ?></span></legend>
                                        <label for="webexpert_timologio_for_woocommerce_uppercase_checkout">
                                            <input name="webexpert_timologio_for_woocommerce_uppercase_checkout" id="webexpert_timologio_for_woocommerce_uppercase_checkout" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_for_woocommerce_uppercase_checkout') == 'yes' ? 'checked' : '') ?>> <?php _e('Uppercase Checkout Fields', 'webexpert-timologio-for-woocommerce'); ?>
                                        </label>
                                    </fieldset>
								</td>
						</tr>
                </table>
                <h2 class="title"><?php _e( 'Invoices', 'webexpert-timologio-for-woocommerce' ); ?></h2>
                <table class="form-table">

								<tr valign="top" class="">
										<th scope="row" class="titledesc"><?php _e('Invoice Logo Url', 'webexpert-timologio-for-woocommerce');?></th>
										<td class="forminp forminp-checkbox">
												 <input size="50" name="webexpert_timologio_for_woocommerce_invoice_logo" id="webexpert_timologio_for_woocommerce_invoice_logo" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_invoice_logo');?>" class="" placeholder="<?php _e("Logo Url" , "webexpert-timologio-for-woocommerce") ?>">
										</td>
								</tr>

								<tr valign="top" class="">
										<th scope="row" class="titledesc"><?php _e('Invoice Settings', 'webexpert-timologio-for-woocommerce');?></th>
										<td class="forminp forminp-checkbox">
												 <input name="webexpert_timologio_for_woocommerce_invoice_serial" id="webexpert_timologio_for_woocommerce_invoice_serial" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_invoice_serial');?>" class="" placeholder="<?php _e("Sequence" , "webexpert-timologio-for-woocommerce") ?>">
												 <input name="webexpert_timologio_for_woocommerce_invoice_starting" id="webexpert_timologio_for_woocommerce_invoice_starting" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_invoice_starting');?>" class="" placeholder="<?php _e("Starting Number" , "webexpert-timologio-for-woocommerce") ?>">
												 <input name="webexpert_timologio_for_woocommerce_qr_invoice_code" id="webexpert_timologio_for_woocommerce_qr_invoice_code" value="<?php echo get_option('webexpert_timologio_for_woocommerce_qr_invoice_code') ?>" placeholder="<?php _e("QR Code", "webexpert-timologio-for-woocommerce") ?>" type="text" />
										</td>
								</tr>

								<tr valign="top" class="">
										<th scope="row" class="titledesc"><?php _e('Receipt Invoice Settings', 'webexpert-timologio-for-woocommerce');?></th>
										<td class="forminp forminp-checkbox">
												 <input name="webexpert_timologio_for_woocommerce_receipt_serial" id="webexpert_timologio_for_woocommerce_receipt_serial" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_receipt_serial');?>" class="" placeholder="<?php _e("Sequence" , "webexpert-timologio-for-woocommerce") ?>">
												 <input name="webexpert_timologio_for_woocommerce_receipt_starting" id="webexpert_timologio_for_woocommerce_receipt_starting" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_receipt_starting');?>" class="" placeholder="<?php _e("Starting Number" , "webexpert-timologio-for-woocommerce") ?>">
												 <input name="webexpert_timologio_for_woocommerce_qr_receipt_code" id="webexpert_timologio_for_woocommerce_qr_receipt_code" value="<?php echo get_option('webexpert_timologio_for_woocommerce_qr_receipt_code') ?>" placeholder="<?php _e("QR Code", "webexpert-timologio-for-woocommerce") ?>" type="text" />
										</td>
								</tr>


								<tr valign="top" class="">
										<th scope="row" class="titledesc"><?php _e('Credit Invoice Settings', 'webexpert-timologio-for-woocommerce');?></th>
										<td class="forminp forminp-checkbox">
												 <input name="webexpert_timologio_for_woocommerce_credit_serial" id="webexpert_timologio_for_woocommerce_credit_serial" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_credit_serial');?>" class="" placeholder="<?php _e("Sequence" , "webexpert-timologio-for-woocommerce") ?>">
												 <input name="webexpert_timologio_for_woocommerce_credit_starting" id="webexpert_timologio_for_woocommerce_credit_starting" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_credit_starting');?>" class="" placeholder="<?php _e("Starting Number" , "webexpert-timologio-for-woocommerce") ?>">
												 <input name="webexpert_timologio_for_woocommerce_qr_credit_code" id="webexpert_timologio_for_woocommerce_qr_credit_code" value="<?php echo get_option('webexpert_timologio_for_woocommerce_qr_credit_code') ?>" placeholder="<?php _e("QR Code", "webexpert-timologio-for-woocommerce") ?>" type="text" />
										</td>
								</tr>

								<tr valign="top" class="">
										<th scope="row" class="titledesc"><?php _e('Cancel Invoice Settings', 'webexpert-timologio-for-woocommerce');?></th>
										<td class="forminp forminp-checkbox">
												 <input name="webexpert_timologio_for_woocommerce_cancel_serial" id="webexpert_timologio_for_woocommerce_cancel_serial" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_cancel_serial');?>" class="" placeholder="<?php _e("Sequence" , "webexpert-timologio-for-woocommerce") ?>">
												 <input name="webexpert_timologio_for_woocommerce_cancel_starting" id="webexpert_timologio_for_woocommerce_cancel_starting" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_cancel_starting');?>" class="" placeholder="<?php _e("Starting Number" , "webexpert-timologio-for-woocommerce") ?>">
												 <input name="webexpert_timologio_for_woocommerce_qr_cancel_code" id="webexpert_timologio_for_woocommerce_qr_cancel_code" value="<?php echo get_option('webexpert_timologio_for_woocommerce_qr_cancel_code') ?>" placeholder="<?php _e("QR Code", "webexpert-timologio-for-woocommerce") ?>" type="text" />
										</td>
								</tr>

                                <tr valign="top" class="">
                                    <th scope="row" class="titledesc"><?php _e('Return Receipt Settings', 'webexpert-timologio-for-woocommerce');?></th>
                                    <td class="forminp forminp-checkbox">
                                        <input name="webexpert_timologio_for_woocommerce_return_receipt_serial" id="webexpert_timologio_for_woocommerce_return_receipt_serial" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_return_receipt_serial');?>" class="" placeholder="<?php _e("Sequence" , "webexpert-timologio-for-woocommerce") ?>">
                                        <input name="webexpert_timologio_for_woocommerce_receipt_return_starting" id="webexpert_timologio_for_woocommerce_receipt_return_starting" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_receipt_return_starting');?>" class="" placeholder="<?php _e("Starting Number" , "webexpert-timologio-for-woocommerce") ?>">
                                        <input name="webexpert_timologio_for_woocommerce_qr_return_receipt_code" id="webexpert_timologio_for_woocommerce_qr_return_receipt_code" value="<?php echo get_option('webexpert_timologio_for_woocommerce_qr_return_receipt_code') ?>" placeholder="<?php _e("QR Code", "webexpert-timologio-for-woocommerce") ?>" type="text" />
                                    </td>
                                </tr>


                                <tr valign="top" class="">
                                    <th scope="row" class="titledesc"><?php _e('Cancellation Receipt Settings', 'webexpert-timologio-for-woocommerce');?></th>
                                    <td class="forminp forminp-checkbox">
                                        <input name="webexpert_timologio_for_woocommerce_cancel_receipt_serial" id="webexpert_timologio_for_woocommerce_cancel_receipt_serial" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_cancel_receipt_serial');?>" class="" placeholder="<?php _e("Sequence" , "webexpert-timologio-for-woocommerce") ?>">
                                        <input name="webexpert_timologio_for_woocommerce_cancel_receipt_starting" id="webexpert_timologio_for_woocommerce_cancel_receipt_starting" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_cancel_receipt_starting');?>" class="" placeholder="<?php _e("Starting Number" , "webexpert-timologio-for-woocommerce") ?>">
                                        <input name="webexpert_timologio_for_woocommerce_qr_cancel_receipt_code" id="webexpert_timologio_for_woocommerce_qr_cancel_receipt_code" value="<?php echo get_option('webexpert_timologio_for_woocommerce_qr_cancel_receipt_code') ?>" placeholder="<?php _e("QR Code", "webexpert-timologio-for-woocommerce") ?>" type="text" />
                                    </td>
                                </tr>
                    <?php

                    ?>

					<tr valign="top" class="">
	                    <th scope="row" class="titledesc"><?php _e('Automatically generate invoice', 'webexpert-timologio-for-woocommerce'); ?></th>
	                    <td class="forminp forminp-checkbox">
	                        <fieldset>
	                            <legend class="screen-reader-text"><span><?php _e('Tax exempt', 'webexpert-timologio-for-woocommerce'); ?></span></legend>
	                            <label for="webexpert_timologio_auto_generate_invoice">
	                                <input name="webexpert_timologio_auto_generate_invoice" id="webexpert_timologio_auto_generate_invoice" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_auto_generate_invoice') == 'yes' ? 'checked' : '') ?>>
	                                <?php echo __("Automatically generate invoice order's status change to Completed" , "webexpert-timologio-for-woocommerce") ?>

	                            </label>
	                        </fieldset>
	                    </td>
	                </tr>

								<tr valign="top" class="">
                    <th scope="row" class="titledesc"><?php _e('Automatically finalize and send', 'webexpert-timologio-for-woocommerce'); ?></th>
                    <td class="forminp forminp-checkbox">
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e('Tax exempt', 'webexpert-timologio-for-woocommerce'); ?></span></legend>
                            <label for="webexpert_timologio_auto_finalize_and_send_invoice">
                                <input name="webexpert_timologio_auto_finalize_and_send_invoice" id="webexpert_timologio_auto_finalize_and_send_invoice" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_auto_finalize_and_send_invoice') == 'yes' ? 'checked' : '') ?>>
                                <?php echo __("Automatically finalize invoice and send to customer's emails when order's status change to Completed" , "webexpert-timologio-for-woocommerce") ?>

                            </label>
                        </fieldset>
                    </td>
                </tr>

                <tr valign="top" class="">
                    <th scope="row" class="titledesc"><?php _e('Tax exempt', 'webexpert-timologio-for-woocommerce'); ?></th>
                    <td class="forminp forminp-checkbox">

                        <fieldset class="">
                            <label for="webexpert_timologio_for_woocommerce_exempt_valid_vies">
                                <input name="webexpert_timologio_for_woocommerce_exempt_valid_vies" id="webexpert_timologio_for_woocommerce_exempt_valid_vies" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_for_woocommerce_exempt_valid_vies') == 'yes' ? 'checked' : '') ?>> <?php _e('Tax exempt when buyer has a valid Vat on Vies.', 'webexpert-timologio-for-woocommerce'); ?>
                            </label>
                        </fieldset>

                        <fieldset class="">
                            <label for="webexpert_timologio_for_woocommerce_exempt_non_europe_country">
                                <input name="webexpert_timologio_for_woocommerce_exempt_non_europe_country" id="webexpert_timologio_for_woocommerce_exempt_non_europe_country" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_for_woocommerce_exempt_non_europe_country') == 'yes' ? 'checked' : '') ?>> <?php _e('Tax exempt when buyer selects a non europe country', 'webexpert-timologio-for-woocommerce'); ?>
                            </label>
                        </fieldset>


                    </td>
                </tr>

								<tr valign="top" class="">
										<th scope="row" class="titledesc"><?php _e('Invoice Your Shop\'s Name', 'webexpert-timologio-for-woocommerce');?></th>
										<td class="forminp forminp-checkbox">
												 <input size="50" name="webexpert_timologio_for_woocommerce_invoice_shop_name" id="webexpert_timologio_for_woocommerce_invoice_shop_name" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_invoice_shop_name');?>" class="" placeholder="<?php _e("Your shop's name that will be appeared on invoice" , "webexpert-timologio-for-woocommerce") ?>">
										</td>
								</tr>

								<tr valign="top" class="">
										<th scope="row" class="titledesc"><?php _e('Invoice Your Shop\'s Vat number', 'webexpert-timologio-for-woocommerce');?></th>
										<td class="forminp forminp-checkbox">
												 <input size="50" name="webexpert_timologio_for_woocommerce_invoice_vat_number" id="webexpert_timologio_for_woocommerce_invoice_vat_number" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_invoice_vat_number');?>" class="" placeholder="<?php _e("Your shop's Vat number that will be appeared on invoice" , "webexpert-timologio-for-woocommerce") ?>">
										</td>
								</tr>

								<tr valign="top" class="">
											<th scope="row" class="titledesc"><?php _e('Invoice Your Shop\'s Email', 'webexpert-timologio-for-woocommerce');?></th>
										<td class="forminp forminp-checkbox">
												 <input size="50" name="webexpert_timologio_for_woocommerce_invoice_email_address" id="webexpert_timologio_for_woocommerce_invoice_email_address" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_invoice_email_address');?>" class="" placeholder="<?php _e("Your shop's Email that will be appeared on invoice" , "webexpert-timologio-for-woocommerce") ?>">
										</td>
								</tr>


								<tr valign="top" class="">
											<th scope="row" class="titledesc"><?php _e('Invoice Your Shop\'s Phone Number', 'webexpert-timologio-for-woocommerce');?></th>
										<td class="forminp forminp-checkbox">
												 <input size="50" name="webexpert_timologio_for_woocommerce_invoice_phone_number" id="webexpert_timologio_for_woocommerce_invoice_phone_number" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_invoice_phone_number');?>" class="" placeholder="<?php _e("Your shop's Phone Number that will be appeared on invoice" , "webexpert-timologio-for-woocommerce") ?>">
										</td>
								</tr>


								<tr valign="top" class="">
											<th scope="row" class="titledesc"><?php _e('Invoice Your Shop\'s Tax Office', 'webexpert-timologio-for-woocommerce');?></th>
										<td class="forminp forminp-checkbox">
												 <input size="50" name="webexpert_timologio_for_woocommerce_invoice_tax_office" id="webexpert_timologio_for_woocommerce_invoice_tax_office" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_invoice_tax_office');?>" class="" placeholder="<?php _e("Your shop's Tax Office that will be appeared on invoice" , "webexpert-timologio-for-woocommerce") ?>">
										</td>
								</tr>



								<tr valign="top" class="">
										<th scope="row" class="titledesc"><?php _e('QR Code Delimiter', 'webexpert-timologio-for-woocommerce');?></th>
										<td class="forminp forminp-checkbox">
												 <input size="50" name="webexpert_timologio_for_woocommerce_invoice_qr_delimiter" id="webexpert_timologio_for_woocommerce_invoice_qr_delimiter" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_invoice_qr_delimiter');?>" class="" placeholder="<?php _e("Delimiter for QR code" , "webexpert-timologio-for-woocommerce") ?>">
										</td>
								</tr>

								<tr valign="top" class="">
										<th scope="row" class="titledesc"><?php _e('QR Code Decimal Digits Seperator', 'webexpert-timologio-for-woocommerce');?></th>
										<td class="forminp forminp-checkbox">
												 <input size="50" name="webexpert_timologio_for_woocommerce_invoice_qr_digit_seperation" id="webexpert_timologio_for_woocommerce_invoice_qr_digit_seperation" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_invoice_qr_digit_seperation');?>" class="" placeholder="<?php _e("Delimiter for QR code decimal digits" , "webexpert-timologio-for-woocommerce") ?>">
										</td>
								</tr>


								<tr valign="top" class="">
										<th scope="row" class="titledesc"><?php _e('QR Code Thousands Digits Seperator', 'webexpert-timologio-for-woocommerce');?></th>
										<td class="forminp forminp-checkbox">
												 <input size="50" name="webexpert_timologio_for_woocommerce_invoice_qr_thousands_seperation" id="webexpert_timologio_for_woocommerce_invoice_qr_thousands_seperation" type="text" style="" value="<?php echo get_option('webexpert_timologio_for_woocommerce_invoice_qr_thousands_seperation');?>" class="" placeholder="<?php _e("Delimiter for QR code thousands digits" , "webexpert-timologio-for-woocommerce") ?>">
										</td>
								</tr>
                                <tr valign="top" class="">
                                    <th scope="row" class="titledesc"><?php _e('String removal', 'webexpert-timologio-for-woocommerce'); ?></th>
                                    <td class="forminp forminp-checkbox">
                                        <fieldset>
                                            <legend class="screen-reader-text"><span><?php _e('String removal', 'webexpert-timologio-for-woocommerce'); ?></span></legend>
                                            <label for="webexpert_timologio_hide_symboloseira">
                                                <input name="webexpert_timologio_hide_symboloseira" id="webexpert_timologio_hide_symboloseira" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_hide_symboloseira') == 'yes' ? 'checked' : '') ?>>
                                                <?php echo __("Removes string from the bottom of the invoice" , "webexpert-timologio-for-woocommerce") ?>
                                            </label>
                                        </fieldset>
                                    </td>
                                </tr>
            </table>
            <h2 class="title"><?php _e( 'POL1150 / 2017', 'webexpert-timologio-for-woocommerce' ); ?></h2>
            <table class="form-table">
            <tr valign="top" class="">
                <th scope="row" class="titledesc"><?php _e('VAT Exempt', 'webexpert-timologio-for-woocommerce'); ?></th>
                <td class="forminp forminp-checkbox">
                    <fieldset>
                        <label for="webexpert_timologio_for_woocommerce_vat_exempt_categories">
                            <?php _e('Categories that should be tax exempt', 'webexpert-timologio-for-woocommerce') ?>:
                        </label><br>
                        <select tabindex="-1" name="webexpert_timologio_for_woocommerce_vat_exempt_categories[]" multiple="multiple" id="webexpert_timologio_for_woocommerce_vat_exempt_categories" class="webexpert_timologio_for_woocommerce_vat_exempt_categories select2-hidden-accessible">
                            <?php
                            $terms= get_terms( 'product_cat', array( 'get' => 'all' ));
                            foreach ($terms as $term) {
	                            echo "<option value='$term->term_id' ".(is_array(get_option("webexpert_timologio_for_woocommerce_vat_exempt_categories")) && in_array($term->term_id,get_option("webexpert_timologio_for_woocommerce_vat_exempt_categories")) ? 'selected="selected"' : '').">".webexpert_timologio_for_woocommerce_get_term_path($term)."</option>";
                            }
                            ?>
                        </select>
                    </fieldset>
                    <fieldset>
                        <label for="webexpert_timologio_for_woocommerce_vat_exempt_tax_class">
                            <?php _e('Select zero rate class tax', 'webexpert-timologio-for-woocommerce') ?>
                        </label><br>
                            <select tabindex="-1" name="webexpert_timologio_for_woocommerce_vat_exempt_tax_class" id="webexpert_timologio_for_woocommerce_vat_exempt_tax_class" class="webexpert_timologio_for_woocommerce_vat_exempt_tax_class">
                                <?php
                                $taxes = WC_Tax::get_tax_classes();
                                foreach ($taxes as $tax) {
                                    echo "<option value='".sanitize_title($tax)."'  ".(get_option('webexpert_timologio_for_woocommerce_vat_exempt_tax_class') == sanitize_title($tax) ? 'selected="selected"' : '').">$tax</option>";
                                }
                                ?>
                            </select><br><br>
                    </fieldset>
                    <fieldset>
                        <label for="webexpert_timologio_for_woocommerce_39a_page"><?php _e('39a terms page', 'webexpert-timologio-for-woocommerce') ?>:</label><br>
                        <select name="webexpert_timologio_for_woocommerce_39a_page" id="webexpert_timologio_for_woocommerce_39a_page" style="width: 50%">
		                    <?php
		                    $pages = get_pages();
		                    foreach ($pages as $page) { ?>
                                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected(get_option('webexpert_timologio_for_woocommerce_39a_page'),$page->ID);?>><?php echo esc_html($page->post_title); ?></option>
		                    <?php } ?>
                        </select>
                    </fieldset>
                    <fieldset>
                        <label for="webexpert_timologio_for_woocommerce_vat_exempt_auto_if_valid">
                            <input name="webexpert_timologio_for_woocommerce_vat_exempt_auto_if_valid" id="webexpert_timologio_for_woocommerce_vat_exempt_auto_if_valid" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_for_woocommerce_vat_exempt_auto_if_valid') == 'yes' ? 'checked' : '') ?>> <?php _e('Tax exempt on POL 1150/2017 categories when buyer has a valid Vat on IAPR',
                                'webexpert-timologio-for-woocommerce');
			                ?></label>
                    </fieldset>
                </td>
            </tr>

						<tr>
							<td>
								<hr>
							</td>
							<td><hr>
							</td>
						</tr>

						<tr valign="top" class="">
								<th scope="row" class="titledesc"><?php _e('Reduced Taxes for islands', 'webexpert-timologio-for-woocommerce');?></th>
								<td class="forminp forminp-checkbox">
										<fieldset>
												<legend class="screen-reader-text"><span><?php _e('Debug mode', 'webexpert-timologio-for-woocommerce'); ?></span></legend>
												<label for="webexpert_timologio_for_woocommerce_enable_island_reduced_tax">
														<input name="webexpert_timologio_for_woocommerce_enable_island_reduced_tax" id="webexpert_timologio_for_woocommerce_enable_island_reduced_tax" type="checkbox" value="yes" <?php echo(get_option('webexpert_timologio_for_woocommerce_enable_island_reduced_tax') == 'yes' ? 'checked' : '') ?>> <?php _e('Enable', 'webexpert-timologio-for-woocommerce'); ?></label>
										</fieldset>
								</td>
						</tr>

						<tr valign="top" class="">
								<th scope="row" class="titledesc"><?php _e("Island reduced tax for 24%" , 'webexpert-timologio-for-woocommerce');?></th>
								<td class="forminp forminp-checkbox">
									<select tabindex="-1" name="webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_24" id="webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_24" class="webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_24">
											<?php
											$taxes = WC_Tax::get_tax_classes();
											foreach ($taxes as $tax) {
													echo "<option value='".sanitize_title($tax)."'  ".(get_option('webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_24') == sanitize_title($tax) ? 'selected="selected"' : '').">$tax</option>";
											}
											?>
									</select><br><br>
								</td>
						</tr>

						<tr valign="top" class="">
								<th scope="row" class="titledesc"><?php _e("Island reduced tax for 13%" , 'webexpert-timologio-for-woocommerce');?></th>
								<td class="forminp forminp-checkbox">
									<select tabindex="-1" name="webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_13" id="webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_13" class="webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_13">
											<?php
											$taxes = WC_Tax::get_tax_classes();
											foreach ($taxes as $tax) {
													echo "<option value='".sanitize_title($tax)."'  ".(get_option('webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_13') == sanitize_title($tax) ? 'selected="selected"' : '').">$tax</option>";
											}
											?>
									</select><br><br>
								</td>
						</tr>

						<tr valign="top" class="">
								<th scope="row" class="titledesc"><?php _e("Island reduced tax for 6%" , 'webexpert-timologio-for-woocommerce');?></th>
								<td class="forminp forminp-checkbox">
									<select tabindex="-1" name="webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_6" id="webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_6" class="webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_6">
											<?php
											$taxes = WC_Tax::get_tax_classes();
											foreach ($taxes as $tax) {
													echo "<option value='".sanitize_title($tax)."'  ".(get_option('webexpert_timologio_for_woocommerce_island_reduced_tax_class_for_6') == sanitize_title($tax) ? 'selected="selected"' : '').">$tax</option>";
											}
											?>
									</select><br><br>
								</td>
						</tr>


            </table>

            <h2 class="title"><?php _e('License','webexpert-timologio-for-woocommerce');?></h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="we_timologio_for_wc_email"><?php _e('Email','webexpert-timologio-for-woocommerce');?></label></th>
                    <td><input type="text" name="we_timologio_for_wc_email" id="we_timologio_for_wc_email" value="<?php echo esc_attr(get_option('we_timologio_for_wc_email')); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="we_timologio_for_wc_email_license_key"><?php _e('License Key','webexpert-timologio-for-woocommerce');?></label></th>
                    <td><input type="text" name="we_timologio_for_wc_email_license_key" id="we_timologio_for_wc_email_license_key" value="<?php echo esc_attr(get_option('we_timologio_for_wc_email_license_key')); ?>"/></td>
                </tr>
            </table>
			<?php submit_button(); ?>
            <input type="hidden" name="" value="<?php echo $_SERVER['HTTP_HOST']; ?>">
        </form>
    </div>
	<?php
}

add_action('admin_notices', 'webexpert_timologio_for_wc_license_admin_notices');
function webexpert_timologio_for_wc_license_admin_notices() {
	if (empty(get_option('we_timologio_for_wc_email_license_key')) || empty(get_option('we_timologio_for_wc_email'))) {
		?>
        <div class="notice notice-error">
            <p><?php _e('Please activate <strong>Web Expert Timologio for WooCommerce</strong> to enable all it\'s features and automatic updates.', 'webexpert-timologio-for-woocommerce'); ?></p>
        </div>
		<?php
	}
	if (get_option('we_timologio_for_wc_valid_license',false)===false) {
		?>
        <div class="notice notice-error">
            <p><?php _e('The license for <strong>Web Expert Timologio for WooCommerce</strong> is invalid. Please fill in a valid license key.', 'webexpert-timologio-for-woocommerce'); ?></p>
        </div>
		<?php
	}
}

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

function activate_webexpert_timologio_for_woocommerce() {
	require_once plugin_dir_path(__FILE__) . 'includes/class-webexpert-timologio-for-woocommerce-activator.php';
	Webexpert_Timologio_For_Woocommerce_Activator::activate();
}

function deactivate_webexpert_timologio_for_woocommerce() {
	require_once plugin_dir_path(__FILE__) . 'includes/class-webexpert-timologio-for-woocommerce-deactivator.php';
	Webexpert_Timologio_For_Woocommerce_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_webexpert_timologio_for_woocommerce');
register_deactivation_hook(__FILE__, 'deactivate_webexpert_timologio_for_woocommerce');

require plugin_dir_path(__FILE__) . 'includes/class-webexpert-timologio-for-woocommerce.php';

function run_webexpert_timologio_for_woocommerce() {
	$plugin = new Webexpert_Timologio_For_Woocommerce();
	$plugin->run();
}

run_webexpert_timologio_for_woocommerce();
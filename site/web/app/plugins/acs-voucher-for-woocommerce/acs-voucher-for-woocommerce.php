<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.webexpert.gr/
 * @since             1.0.0
 * @package           Acs_Voucher_For_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       ACS Voucher For WooCommerce
 * Plugin URI:        https://www.webexpert.gr/plugin/woocommerce/courier/acs-voucher-for-woocommerce
 * Description:       ACS Voucher for WooCommerce allows you to easily issue/print and track your vouchers for ACS via WooCommerce Admin
 * Version:           1.0.73
 * Requires at least: 4.0
 * Requires PHP:      7.0
 * Author:            Web Expert
 * Author URI:        https://www.webexpert.gr/
 * License:           Web Expert license
 * Text Domain:       acs-voucher-for-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 3.0
 * WC tested up to: 10.3.3
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'ACS_VOUCHER_FOR_WOOCOMMERCE_VERSION', '1.0.73' );

require plugin_dir_path(__FILE__) . 'includes/update/plugin-update-checker.php';
$myUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker('https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=acs-voucher-for-woocommerce', __FILE__, 'acs-voucher-for-woocommerce');
$myUpdateChecker->addQueryArgFilter('webexpert_acs_voucher_update_checks');
function webexpert_acs_voucher_update_checks($queryArgs) {
    $license = get_option('webexpert_acs_gateway_license_key');
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

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

function init_webexpert_woocommerce_acs_license_check() {
	if (get_option('init_webexpert_woocommerce_acs_license_check',false)==false) {
		webexpert_woocommerce_acs_license_check();
		update_option('init_webexpert_woocommerce_acs_license_check',true);
	}
}
add_action('admin_notices', 'init_webexpert_woocommerce_acs_license_check');
register_activation_hook( __FILE__, 'webexpert_woocommerce_acs_license_check' );
function webexpert_woocommerce_acs_license_check(){
	$url='https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=acs-voucher-for-woocommerce&license_key='.get_option('webexpert_acs_gateway_license_key').'&domain='.get_bloginfo('url');
	$request = wp_remote_get($url);
	$response = wp_remote_retrieve_body( $request );
	$s = json_decode($response);
	if (isset($s->download_url)) {
		update_option('webexpert_acs_valid_license',true);
	}else {
		delete_option('webexpert_acs_valid_license');
	}
}

add_action('admin_menu', 'webexpert_acs_voucher_options_page');
function webexpert_acs_voucher_options_page()
{
    $logo = file_get_contents(plugin_dir_path(__FILE__).'assets/webexpert-icon.svg');
    if ( empty ( $GLOBALS['admin_page_hooks']['webexpert_plugins'] ) )
        add_menu_page('Web Expert Plugins','Web Expert','manage_woocommerce','webexpert_plugins','webexpert_acs_voucher_settings_main_menu','data:image/svg+xml;base64,' . base64_encode($logo),20);
    add_submenu_page('webexpert_plugins',__( 'ACS Voucher', 'acs-voucher-for-woocommerce' ), __( 'ACS Voucher', 'acs-voucher-for-woocommerce' ),'manage_woocommerce','webexpert-acs-voucher','webexpert_acs_voucher_options_page_html'
    );
	remove_submenu_page('webexpert_plugins',    'webexpert_plugins');
}
add_action( 'admin_init', 'webexpert_acs_voucher_settings' );
function webexpert_acs_voucher_settings() {

    if( ! get_option("webexpert_multiple_acs_version_added") == "yes" ) {

        if(!empty(get_option('webexpert_acs_sender'))) {
            if ( !empty(get_option('webexpert_acs_sender')) && is_string( get_option('webexpert_acs_sender') ) ) {
                update_option('webexpert_acs_sender', [get_option('webexpert_acs_sender')]);
            }
            if ( !empty(get_option('webexpert_acs_company_id')) && is_string( get_option('webexpert_acs_company_id') ) ) {
                update_option('webexpert_acs_company_id', [get_option('webexpert_acs_company_id')]);
            }
            if ( !empty(get_option('webexpert_acs_company_password')) && is_string( get_option('webexpert_acs_company_password') ) ) {
                update_option('webexpert_acs_company_password', [get_option('webexpert_acs_company_password')]);
            }
            if ( !empty(get_option('webexpert_acs_user_id')) && is_string( get_option('webexpert_acs_user_id') )) {
                update_option('webexpert_acs_user_id', [get_option('webexpert_acs_user_id')]);
            }
            if ( !empty(get_option('webexpert_acs_user_password')) && is_string( get_option('webexpert_acs_user_password') )) {
                update_option('webexpert_acs_user_password', [get_option('webexpert_acs_user_password')]);
            }
            if ( !empty(get_option('webexpert_acs_billing_code') ) && is_string( get_option('webexpert_acs_billing_code') )) {
                update_option('webexpert_acs_billing_code', [get_option('webexpert_acs_billing_code')]);
            }
            if ( !empty(get_option('webexpert_acs_apikey')) && is_string( get_option('webexpert_acs_apikey') )) {
                update_option('webexpert_acs_apikey', [get_option('webexpert_acs_apikey')]);
            }

            update_option('webexpert_default_acs_account', 0);

            //Update current
            $query = new WP_Query([
                'post_type' => 'we_voucher_job',
                'posts_per_page' => -1,
            ]);
            if (is_array($query) && count($query) > 0) {
                foreach ($query->get_posts() as $jobItem) {
                    update_post_meta($jobItem->ID, 'we_acs_account', 0);
                    if (is_array(get_option('webexpert_acs_user_id'))) {
                        update_post_meta($jobItem->ID, 'we_acs_account_user_id', get_option('webexpert_acs_user_id'));
                    }
                }

            }

            update_option("webexpert_multiple_acs_version_added", 'yes');
        }
    }

    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_company_id' );
    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_sender' );
    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_company_password' );
    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_user_id' );
    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_user_password' );
    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_billing_code' );
    register_setting( 'webexpert-acs-voucher-group' ,'webexpert_default_acs_account');
    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_apikey' );
    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_auto_close');
    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_gateway_email' );
    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_gateway_license_key' );
    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_auto_issue_upon_complete' );
	register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_default_print_size' );
	register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_default_weight' );
	register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_shipping_validate_by' );
	register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_address_validation' );
	register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_disable_on_payments' );
    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_disable_on_shipping' );
    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_debug' );
    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_disable_dimensions_volumetric' );
    register_setting( 'webexpert-acs-voucher-group', 'webexpert_acs_enable_inaccessible_check' );
}

function webexpert_acs_rescedule() {
    ACS_Voucher_For_Woocommerce_Cron::unschedule();
    ACS_Voucher_For_Woocommerce_Cron::schedule();
}

function webexpert_acs_voucher_do_after_update($old, $new) {
    webexpert_acs_rescedule();
}
add_action('update_option_webexpert_acs_auto_close','webexpert_acs_voucher_do_after_update', 10, 2);

function webexpert_acs_voucher_do_after_add( $new ) {
    webexpert_acs_rescedule();
}
add_action( 'add_option_webexpert_acs_auto_close', 'webexpert_acs_voucher_do_after_add' );

function webexpert_acs_voucher_settings_main_menu() {
    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <p>Ευχαριστούμε που επιλέγε την Web Expert.</p>
    </div>
    <?php
}
add_action('add_option_webexpert_acs_gateway_license_key', 'webexpert_woocommerce_acs_callback_update', 10, 2);
add_action('update_option_webexpert_acs_gateway_license_key', 'webexpert_woocommerce_acs_callback_update', 10, 2);
function webexpert_woocommerce_acs_callback_update( $old_value, $new_value ) {
	$url='https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=acs-voucher-for-woocommerce&license_key='.$new_value.'&domain='.get_bloginfo('url');
	$request = wp_remote_get($url);
	$response = wp_remote_retrieve_body( $request );
	$s = json_decode($response);
	if (isset($s->download_url)) {
		update_option('webexpert_acs_valid_license',true);
	}else {
		delete_option('webexpert_acs_valid_license');
	}
}
function webexpert_acs_voucher_options_page_html()
{
    if (!current_user_can('manage_woocommerce')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>

        <form action="options.php" method="post">
            <?php
            settings_fields('webexpert-acs-voucher-group');
            do_settings_sections('webexpert-acs-voucher-group');
            $existingAccounts = 1;
            foreach(get_option('webexpert_acs_sender') as $index => $opt) {
                if(strlen(trim($opt)) > 0 && $index > 0) {
                    $existingAccounts++;
                }
            }
            ?>
            <?php for($i=0;$i<$existingAccounts;$i++) { ?>
            <div class="webexpert-acs-account-table">
                <?php if($i>0) { ?>
                <span class="webexpert-acs-account-delete">&times;</span>
                <?php } ?>
                <p class="account_id" style="width: fit-content;font-size: 80%;text-align:center"><?php _e('Account ID:','acs-voucher-for-woocommerce');?> <?php echo $i;?></p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="webexpert_acs_sender"><?php _e('Sender','acs-voucher-for-woocommerce');?></label></th>
                        <td><input type="text" id="webexpert_acs_sender" name="webexpert_acs_sender[]" value="<?php echo esc_attr( get_option('webexpert_acs_sender')[$i] ); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="webexpert_acs_company_id"><?php _e('Company ID','acs-voucher-for-woocommerce');?></label></th>
                        <td><input type="text" id="webexpert_acs_company_id" name="webexpert_acs_company_id[]" value="<?php echo esc_attr( get_option('webexpert_acs_company_id')[$i]); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="webexpert_acs_company_password"><?php _e('Company Password','acs-voucher-for-woocommerce');?></label</th>
                        <td><input type="text" id="webexpert_acs_company_password" name="webexpert_acs_company_password[]" value="<?php echo esc_attr( get_option('webexpert_acs_company_password')[$i] ); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="webexpert_acs_user_id"><?php _e('User ID','acs-voucher-for-woocommerce');?></label</th>
                        <td><input type="text" id="webexpert_acs_user_id" name="webexpert_acs_user_id[]" value="<?php echo esc_attr( get_option('webexpert_acs_user_id')[$i] ); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="webexpert_acs_user_password"><?php _e('User Password','acs-voucher-for-woocommerce');?></label></th>
                        <td><input type="text" id="webexpert_acs_user_password" name="webexpert_acs_user_password[]" value="<?php echo esc_attr( get_option('webexpert_acs_user_password')[$i] ); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="webexpert_acs_billing_code"><?php _e('Billing Code','acs-voucher-for-woocommerce');?></label</th>
                        <td><input type="text" id="webexpert_acs_billing_code" name="webexpert_acs_billing_code[]" value="<?php echo esc_attr( get_option('webexpert_acs_billing_code')[$i] ); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="webexpert_acs_apikey"><?php _e('API key','acs-voucher-for-woocommerce');?></label</th>
                        <td><input type="text" id="webexpert_acs_apikey" name="webexpert_acs_apikey[]" value="<?php echo esc_attr( get_option('webexpert_acs_apikey')[$i] ); ?>" /></td>
                    </tr>
                </table>
            </div>
             <?php } ?>
            <div>
                <a href="#" class="webexpert-add-extra-acs-acc"><?php _e("(+) Add extra account" , 'acs-voucher-for-woocommerce') ?></a>
            </div>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="webexpert_acs_auto_close"><?php _e('Auto close pending jobs','acs-voucher-for-woocommerce');?></label</th>
                    <td><input type="text" id="webexpert_acs_auto_close" name="webexpert_acs_auto_close" value="<?php echo esc_attr( get_option('webexpert_acs_auto_close','20:00:00') ); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="webexpert_acs_default_weight"><?php _e('Default weight','acs-voucher-for-woocommerce');?></label</th>
                    <td><input type="text" placeholder="0.5" id="webexpert_acs_default_weight" name="webexpert_acs_default_weight" value="<?php echo esc_attr( get_option('webexpert_acs_default_weight') ); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Auto issue','acs-voucher-for-woocommerce');?></th>
                    <td><label for="webexpert_acs_auto_issue_upon_complete">
                            <input name="webexpert_acs_auto_issue_upon_complete" type="checkbox" id="webexpert_acs_auto_issue_upon_complete" value="1" <?php echo get_option('webexpert_acs_auto_issue_upon_complete')==1 ? 'checked' : ''; ?>>
                            <?php _e('Auto issue voucher upon order completion','acs-voucher-for-woocommerce');?></label></td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="webexpert_default_acs_account"> <?php _e("Default ACS Account",'acs-voucher-for-woocommerce') ?> </label>
                    </th>
                    <td>
                        <select name="webexpert_default_acs_account" id="webexpert_default_acs_account">
                            <?php
                                $existingAccounts = 0;
                                foreach (get_option('webexpert_acs_sender') as $index => $opt) {
                                    if(strlen(trim($opt)) > 0 && $index > 0) {
                                        $existingAccounts++;
                                    }
                                }
                                for ($i=0;$i<($existingAccounts + 1);$i++) { ?>
                                    <option <?php selected(get_option('webexpert_default_acs_account',0),$i);?> value="<?php echo $i ?>"><?php echo "(" . ($i + 1) . ") - " .  get_option("webexpert_acs_user_id")[$i] ?></option>
                                <?php } ?>
                        </select>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><label for="webexpert_acs_disable_on_payments"><?php _e('Disable on specific Payment Gateways','acs-voucher-for-woocommerce');?></label></th>
                    <td>
                        <select multiple id="webexpert_acs_disable_on_payments" name="webexpert_acs_disable_on_payments[]">
				            <?php
                            if (!is_array(get_option('webexpert_acs_disable_on_payments'))) {
                                update_option('webexpert_acs_disable_on_payments',[]);
                            }
                            foreach ( WC()->payment_gateways->get_available_payment_gateways() as $method ) { ?>
                                <option value="<?php echo $method->id;?>" <?php echo in_array($method->id,get_option('webexpert_acs_disable_on_payments',[])) ? 'selected' : '';?>><?php echo $method->get_method_title();?></option>
				            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="webexpert_acs_disable_on_shipping"><?php _e('Disable on specific Shipping Methods','acs-voucher-for-woocommerce');?></label></th>
                    <td>
			            <?php
			            $methods=[];
			            $zone = new \WC_Shipping_Zone( 0 );
			            foreach ( $zone->get_shipping_methods() as $shipping_method ) {
				            $id=$shipping_method->id;
				            $id.=!empty($shipping_method->get_instance_id()) ? ':'.$shipping_method->get_instance_id() : '';
				            $methods[$id]=$shipping_method->get_method_title();
			            }

			            $zones = WC_Shipping_Zones::get_zones();
			            foreach ( $zones as $zone ) {
				            $zone = new \WC_Shipping_Zone( $zone['id'] );
				            foreach ( $zone->get_shipping_methods() as $shipping_method ) {
					            $id=$shipping_method->id;
					            $id.=!empty($shipping_method->get_instance_id()) ? ':'.$shipping_method->get_instance_id() : '';
					            $methods[$id]=$shipping_method->get_title();
				            }
			            }
			            ?>
                        <select id="webexpert_acs_disable_on_shipping" name="webexpert_acs_disable_on_shipping[]" multiple>
				            <?php
				            if (!is_array(get_option('webexpert_acs_disable_on_shipping'))) {
					            update_option('webexpert_acs_disable_on_shipping',[]);
				            }
				            foreach ( $methods as $k=>$method ) { ?>
                                <option value="<?php echo $k;?>" <?php echo in_array($k,get_option('webexpert_acs_disable_on_shipping',[])) ? 'selected' : '';?>><?php echo $method;?></option>
				            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="webexpert_acs_default_print_size"><?php _e('Auto issue Paper Size','acs-voucher-for-woocommerce');?></label</th>
                    <td>
                        <div class="webexpert-field-label" style="display: inline-block;margin-right:10px;margin-bottom:0">
                            <label><input  type="radio" name="webexpert_acs_default_print_size" value="2" <?php echo (esc_attr( get_option('webexpert_acs_default_print_size') )=="2" ? 'checked' : '') ;?>><?php echo __('Flyer','acs-voucher-for-woocommerce'); ?></label>
                        </div>
                        <div class="webexpert-field-label" style="display: inline-block;margin-right:10px;margin-bottom:0">
                            <label><input  type="radio" name="webexpert_acs_default_print_size" value="1" <?php echo (esc_attr( get_option('webexpert_acs_default_print_size') )=="1" ? 'checked' : '') ;?>><?php echo __('Sticker','acs-voucher-for-woocommerce'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="webexpert_acs_address_validation"><?php _e('Enable address validation and autofill on checkout','acs-voucher-for-woocommerce');?></label</th>
                    <td>
                        <div class="webexpert-field-label" style="display: inline-block;margin-right:10px;margin-bottom:0">
                            <label><input  type="radio" name="webexpert_acs_address_validation" value="y" <?php echo (esc_attr( get_option('webexpert_acs_address_validation','n') )=="y" ? 'checked' : '') ;?>><?php echo __('Yes','acs-voucher-for-woocommerce'); ?></label>
                        </div>
                        <div class="webexpert-field-label" style="display: inline-block;margin-right:10px;margin-bottom:0">
                            <label><input  type="radio" name="webexpert_acs_address_validation" value="n" <?php echo (esc_attr( get_option('webexpert_acs_address_validation','n') )=="n" ? 'checked' : '') ;?>><?php echo __('No','acs-voucher-for-woocommerce'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Dimensions','acs-voucher-for-woocommerce');?></th>
                    <td><label for="webexpert_acs_disable_dimensions_volumetric">
                            <input name="webexpert_acs_disable_dimensions_volumetric" type="checkbox" id="webexpert_acs_disable_dimensions_volumetric" value="1" <?php echo get_option('webexpert_acs_disable_dimensions_volumetric','0')=='1' ? 'checked' : ''; ?>>
                            <?php _e('Disable volumetric weight calculation based on dimensions','acs-voucher-for-woocommerce');?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Inaccessible check','acs-voucher-for-woocommerce');?></th>
                    <td><label for="webexpert_acs_enable_inaccessible_check">
                            <input name="webexpert_acs_enable_inaccessible_check" type="checkbox" id="webexpert_acs_enable_inaccessible_check" value="1" <?php echo get_option('webexpert_acs_enable_inaccessible_check','0')=='1' ? 'checked' : ''; ?>>
                            <?php _e('Enable inaccessible check upon order complete','acs-voucher-for-woocommerce');?></label></td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Debug','acs-voucher-for-woocommerce');?></th>
                    <td><label for="webexpert_acs_debug">
                            <input name="webexpert_acs_debug" type="checkbox" id="webexpert_acs_debug" value="1" <?php echo get_option('webexpert_acs_debug',0)==1 ? 'checked' : ''; ?>>
                            <?php _e('Debug mode logs every request made','acs-voucher-for-woocommerce');?></label></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="webexpert_acs_shipping_validate_by"><?php _e('Default method for ACS shipping calculator','acs-voucher-for-woocommerce');?></label</th>
                    <td>
                        <div class="webexpert-field-label" style="display: inline-block;margin-right:10px;margin-bottom:0">
                            <label><input  type="radio" name="webexpert_acs_shipping_validate_by" value="address" <?php echo (esc_attr( get_option('webexpert_acs_shipping_validate_by','address') )=="address" ? 'checked' : '') ;?>><?php echo __('Address','acs-voucher-for-woocommerce'); ?></label>
                        </div>
                        <div class="webexpert-field-label" style="display: inline-block;margin-right:10px;margin-bottom:0">
                            <label><input  type="radio" name="webexpert_acs_shipping_validate_by" value="zipcode" <?php echo (esc_attr( get_option('webexpert_acs_shipping_validate_by','address') )=="zipcode" ? 'checked' : '') ;?>><?php echo __('Zipcode','acs-voucher-for-woocommerce'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="webexpert_acs_gateway_email">Email</label></th>
                    <td><input type="text" id="webexpert_acs_gateway_email" name="webexpert_acs_gateway_email" value="<?php echo esc_attr( get_option('webexpert_acs_gateway_email') ); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="webexpert_acs_gateway_license_key">License Key</label</th>
                    <td><input type="text" id="webexpert_acs_gateway_license_key" name="webexpert_acs_gateway_license_key" value="<?php echo esc_attr( get_option('webexpert_acs_gateway_license_key') ); ?>" /></td>
                </tr>
            </table>
            <?php submit_button();  ?>
            <input type="hidden" name="" value="<?php echo $_SERVER['HTTP_HOST'];?>">
        </form>

        <h2 class="title"><?php _e('Print voucher', 'acs-voucher-for-woocommerce'); ?></h2>
        <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="print-voucher-debug">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="webexpert_print_voucher_acs"><?php _e('Voucher No','acs-voucher-for-woocommerce');?></label></th>
                    <td>
                        <label><input type="radio" name="acs_print_voucher_type" value="1"><?php echo __('Sticker','acs-voucher-for-woocommerce'); ?></label><br>
                        <label><input  type="radio" name="acs_print_voucher_type" value="2" checked><?php echo __('Flyer','acs-voucher-for-woocommerce'); ?></label><br><br>
                        <input type="text" id="webexpert_print_voucher_acs" name="webexpert_print_voucher_acs" value="" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"></th>
                    <td>
                        <button type="submit" name="submit" id="submit" class="button button-primary has-spinner">
	                        <?php _e('Print voucher','acs-voucher-for-woocommerce');?> <span class="we_spinner"></span></button>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="action" value="webexpert_print_voucher_acs">
        </form>

        <h2 class="title"><?php _e('Cancel voucher', 'acs-voucher-for-woocommerce'); ?></h2>
        <form  data-success="<?php _e('Voucher has been cancelled', 'acs-voucher-for-woocommerce'); ?>" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="acs-cancel-voucher-debug">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="webexpert_cancel_voucher_acs"><?php _e('Voucher No','acs-voucher-for-woocommerce');?></label></th>
                    <td>
                        <input type="text" id="webexpert_cancel_voucher_acs" name="webexpert_cancel_voucher_acs" value="" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"></th>
                    <td>
                        <button type="submit" name="submit" id="submit" class="button button-primary has-spinner"><?php _e('Cancel voucher','acs-voucher-for-woocommerce');?> <span class="we_spinner"></span></button>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="action" value="webexpert_cancel_voucher_acs">
        </form>

        <h2 class="title"><?php _e('Print Pickup Lists', 'acs-voucher-for-woocommerce'); ?></h2>
        <form data-success="<?php _e('No Pickup Lists found', 'acs-voucher-for-woocommerce'); ?>" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="acs-find-pickup-lists">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="webexpert_acs_date_pickup"><?php _e('Date','acs-voucher-for-woocommerce');?></label></th>
                    <td>
                        <input type="date" id="webexpert_acs_date_pickup" name="webexpert_acs_date_pickup" value="" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"></th>
                    <td>
                        <button type="submit" name="submit" id="submit" class="button button-primary has-spinner"><?php _e('Find Pickup Lists','acs-voucher-for-woocommerce');?> <span class="we_spinner"></span></button>
                        <ul class="pickup_list_result_container"></ul>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="action" value="webexpert_acs_find_pickup_lists">
        </form>

        <h2 class="title"><?php _e('COD Beneficiary Info', 'acs-voucher-for-woocommerce'); ?></h2>
        <form data-success="<?php _e('No COD Beneficiary info found', 'acs-voucher-for-woocommerce'); ?>" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="acs-cod-beneficiary-info">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="webexpert_acs_cod_date_pickup"><?php _e('Date','acs-voucher-for-woocommerce');?></label></th>
                    <td>
                        <input type="date" id="webexpert_acs_cod_date_pickup" name="webexpert_acs_cod_date_pickup" value="" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"></th>
                    <td>
                        <button type="submit" name="submit" id="submit" class="button button-primary has-spinner"><?php _e('COD Beneficiary Info','acs-voucher-for-woocommerce');?> <span class="we_spinner"></span></button>
                        <table class="cod_beneficiary_container" cellspacing="0"></table>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="action" value="webexpert_acs_cod_beneficiary_info">
        </form>

        <h2 class="title"><?php _e('Export Orders', 'acs-voucher-for-woocommerce'); ?></h2>
        <?php if (class_exists('Webexpert_woocommerce_order_tracking')) { ?>
            <a href="<?php echo admin_url("admin.php?page=webexpert-woocommerce-order-tracking");?>"><?php _e('Export Orders', 'acs-voucher-for-woocommerce'); ?></a>
        <?php }else {
            echo __('Please install Web Expert WooCommerce Order Tracking in order to enable exporter.','acs-voucher-for-woocommerce');
        }?>
    </div>
    <?php
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-acs-voucher-for-woocommerce-activator.php
 */
function activate_acs_voucher_for_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-acs-voucher-for-woocommerce-activator.php';
	Acs_Voucher_For_Woocommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-acs-voucher-for-woocommerce-deactivator.php
 */
function deactivate_acs_voucher_for_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-acs-voucher-for-woocommerce-deactivator.php';
	Acs_Voucher_For_Woocommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_acs_voucher_for_woocommerce' );
register_deactivation_hook( __FILE__, 'deactivate_acs_voucher_for_woocommerce' );
add_action( 'upgrader_process_complete', 'update_acs_voucher_for_woocommerce_check',10,2);
function update_acs_voucher_for_woocommerce_check($upgrader_object, $options) {
    $current_plugin_path_name = plugin_basename( __FILE__ );
    if ($options['action'] == 'update' && $options['type'] == 'plugin' ) {
        foreach($options['plugins'] as $each_plugin) {
            if ($each_plugin==$current_plugin_path_name) {
                require_once plugin_dir_path( __FILE__ ) . 'includes/class-acs-voucher-for-woocommerce-activator.php';
                require_once plugin_dir_path( __FILE__ ) . 'includes/class-acs-voucher-for-woocommerce-deactivator.php';
                Acs_Voucher_For_Woocommerce_Deactivator::deactivate();
                Acs_Voucher_For_Woocommerce_Activator::activate();
                update_option('acs_voucher_for_woocommerce_version',ACS_VOUCHER_FOR_WOOCOMMERCE_VERSION);
            }
        }
    }
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-acs-voucher-for-woocommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_acs_voucher_for_woocommerce() {

	$plugin = new Acs_Voucher_For_Woocommerce();
	$plugin->run();

}
run_acs_voucher_for_woocommerce();

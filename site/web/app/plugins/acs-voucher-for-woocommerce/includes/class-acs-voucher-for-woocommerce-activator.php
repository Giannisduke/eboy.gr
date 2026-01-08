<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.webexpert.gr/
 * @since      1.0.0
 *
 * @package    Acs_Voucher_For_Woocommerce
 * @subpackage Acs_Voucher_For_Woocommerce/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Acs_Voucher_For_Woocommerce
 * @subpackage Acs_Voucher_For_Woocommerce/includes
 * @author     Web Expert <info@webexpert.gr>
 */
class Acs_Voucher_For_Woocommerce_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        require_once plugin_dir_path( __FILE__ ) . 'class-acs-voucher-for-woocommerce-cron.php';
        ACS_Voucher_For_Woocommerce_Cron::schedule();
	}

}

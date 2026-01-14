<?php
/*
Plugin Name: Web Expert WooCommerce Google Merchant Product Data Feed
Plugin URI: https://www.webexpert.gr/woocommerce-google-merchant-xml-feed
Description: Web Expert WooCommerce Google Merchant Product Data Feed is a WooCommerce plugin that creates valid data feed for Google Merchant.
Version: 1.0.6
Requires at least: 4.0
Requires PHP:      7.0
Author: Web Expert
Author URI: http://www.webexpert.gr
License: Web Expert license
Text Domain: webexpert-google-merchant-product-data-feed
WC requires at least: 3.0
WC tested up to: 9.3.3
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
if (!defined('WE_GOOGLE_MERCHANT_DATA_FEED_PLUGIN_PATH')) {
	$upload_dir = wp_upload_dir();
	$google_merchant_xml_dir = $upload_dir['basedir'] . '/webexpert-google-merchant-product-data-feed';
	define( 'WE_GOOGLE_MERCHANT_DATA_FEED_PLUGIN_PATH', $google_merchant_xml_dir );
}

if (!defined('WE_GOOGLE_MERCHANT_DATA_FEED_PLUGIN_URL')) {
    $upload_dir = wp_upload_dir();
    $google_merchant_xml_url = $upload_dir['baseurl'] . '/webexpert-google-merchant-product-data-feed';
	define( 'WE_GOOGLE_MERCHANT_DATA_FEED_PLUGIN_URL', $google_merchant_xml_url );
}

define( 'WE_XML_GOOGLE_MERCHANT_PLUGIN_VERSION',"1.0.6");

require plugin_dir_path( __FILE__ ).'includes/update/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=webexpert-google-merchant-product-data-feed',
    __FILE__,
    'webexpert-google-merchant-product-data-feed'
);

$myUpdateChecker->addQueryArgFilter('webexpert_google_merchant_product_data_feed_update_checks');
function webexpert_google_merchant_product_data_feed_update_checks($queryArgs) {
    $license = get_option('we_google_merchant_product_data_feed_license_key');
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

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

function activate_we_google_merchant_product_data_feed() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-activator.php';
    WEGoogleMerchantProductDataFeed_Activator::activate();
}
function deactivate_we_google_merchant_product_data_feed() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-deactivator.php';
    WEGoogleMerchantProductDataFeed_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_we_google_merchant_product_data_feed' );
register_deactivation_hook( __FILE__, 'deactivate_we_google_merchant_product_data_feed' );
add_action('we_daily_google_merchant_xml','we_do_this_daily_google_merchant');

function we_do_this_daily_google_merchant() {
	if (get_option('we_google_merchant_product_data_feed_cron_schedule')=="disabled") return;
    require "includes/scripts/google-merchant-engine.php";
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp.php';
function WEGoogleMerchantProductDataFeed() {
    $wewp = new WEGoogleMerchantProductDataFeed();
    $wewp->run();
}

WEGoogleMerchantProductDataFeed();
<?php
$root = dirname(dirname(dirname(dirname(__FILE__))));
if (file_exists($root.'/wp-load.php')) {
// WP 2.6
    require_once($root.'/wp-load.php');
} else {
// Before 2.6
    require_once($root.'/wp-config.php');
}

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

include(__DIR__.'/includes/scripts/google-merchant-engine.php');
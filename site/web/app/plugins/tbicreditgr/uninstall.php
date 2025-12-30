<?php
// If uninstall not called from WordPress, then exit.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}
global $wpdb;
// remove plugin options
$options = array(
    'tbigr_hide',
    'tbigr_unicid',
    'credittbigr_store_id',
    'credittbigr_username',
    'credittbigr_password',
    'credittbigr_iris_iban',
    'credittbigr_iris_key',
    'woocommerce_tbiiris_settings',
    'woocommerce_tbigr_settings'
);
foreach ($options as $option) {
    delete_option( $option );
    delete_site_option( $option );
}
// Clear any cached data that has been removed.
wp_cache_flush();
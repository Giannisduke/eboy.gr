<?php
/**
 * Plugin Name: tbi bank GR
 * Plugin URI: 
 * Description: Credit calculator 
 * Version: 1.1.3
 * Author: Ilko Ivanov
 * Author URI: http://avalonbg.com
 * Text Domain: tbicreditgr
 * Domain Path: /languages/
 * Network: 
 * License: 
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if (!defined('TBIGR_LIVEURL'))
define('TBIGR_LIVEURL', 'https://calc.tbibank.gr');
/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    /** definitions */
    define( 'TBIGR_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );
    define( 'TBIGR_INCLUDES_DIR', TBIGR_PLUGIN_DIR . '/includes' );
    
    define( 'TBIGR_MOD_VERSION', '1.1.3' );
    /** includes */
    require_once TBIGR_INCLUDES_DIR . '/functions.php';
    require_once TBIGR_INCLUDES_DIR . '/admin.php';
    
    /** add text domain */
    add_action( 'init', 'tbigr_load_textdomain' );
    
    /** add origins */
    add_filter( 'allowed_http_origins', 'tbigr_add_allowed_origins' );
    
    /** add order column tbigr status */
    add_filter( 'manage_edit-shop_order_columns', 'tbigr_add_order_column_status' );
    /** add order column tbigr status values */
    add_action( 'manage_shop_order_posts_custom_column', 'tbigr_add_order_column_status_values', 2 );
    
    //make theme ready for translation
    load_plugin_textdomain( 'tbicreditgr', false, TBIGR_PLUGIN_DIR . '/languages' );
    /** add admin menu ###includes/admin.php### */
    add_action('admin_menu', 'tbigr_admin_actions');
    /** output buffer ###includes/functions.php### */
    add_action('init', 'tbigr_do_output_buffer');
    /** vizualize credit button ###includes/functions.php### */
    add_action('woocommerce_after_add_to_cart_button','tbigr_credit_button');
    
    /** reklama ###includes/functions.php### */
    add_action('wp_enqueue_scripts', 'tbigr_add_meta');
    add_action( 'loop_start', 'tbigr_reklama' );
    
    /** payment gateway **/
    add_action( 'plugins_loaded', 'tbigr_init_tbigr_gateway_class' );
    add_action( 'plugins_loaded', 'tbigr_init_tbiiris_gateway_class' );
    add_filter( 'woocommerce_payment_gateways', 'add_tbigr_gateway_class' );
    
    /** check query ###includes/functions.php### */
    add_filter( 'query_vars', 'tbigr_add_query_vars_filter' );
    
}
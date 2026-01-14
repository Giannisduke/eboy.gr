<?php

class Webexpert_woocommerce_order_tracking {

	protected $loader;
	protected $plugin_slug;
	protected $version;

	public function __construct() {

		$this->plugin_slug = 'webexpert-woocommerce-order-tracking';
		$this->version = '1.0.19';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->set_locale();
	}

	private function load_dependencies() {
		require_once plugin_dir_path(__FILE__) . 'class-webexpert-woocommerce-order-tracking-loader.php';
		require_once plugin_dir_path(__FILE__) . 'class-webexpert-woocommerce-order-tracking-admin.php';
		require_once plugin_dir_path(__FILE__) . 'class-webexpert-woocommerce-order-tracking-i18n.php';
		require_once plugin_dir_path(__FILE__) . 'class-webexpert-woocommerce-order-tracking-importer.php';
		$this->loader = new Webexpert_woocommerce_order_tracking_Loader();
	}

	private function define_admin_hooks() {
		$plugin_admin = new Webexpert_woocommerce_order_tracking_Admin($this->get_name(),$this->get_version());
		$this->loader->add_filter('woocommerce_admin_shipping_fields', $plugin_admin, 'webexpert_woocommerce_order_tracking_admin_billing_fields');
		if (get_option('webexpert_woocommerce_order_trackin_multiple_carriers',false)) {
			$this->loader->add_action('add_meta_boxes', $plugin_admin, 'webexpert_woocommerce_order_tracking_meta_box');
		}
		$this->loader->add_action('woocommerce_after_order_object_save', $plugin_admin, 'webexpert_woocommerce_order_tracking_meta_box_save', 10, 1);
		$this->loader->add_action('woocommerce_email_classes', $plugin_admin, 'webexpert_woocommerce_order_tracking_register_email', 90, 1);
		$this->loader->add_action('woocommerce_order_actions', $plugin_admin, 'webexpert_woocommerce_order_tracking_custom_wc_order_action', 10, 1);
		$this->loader->add_action('woocommerce_order_action_send_tracking_number', $plugin_admin, 'webexpert_woocommerce_order_tracking_fired_send_tracking_number');
		$this->loader->add_filter('webexpert_woocommerce_order_tracking_custom_shipping_company_name',$plugin_admin, 'multi_carrier_add_company_name',10,2);
		$this->loader->add_filter('webexpert_woocommerce_order_tracking_custom_shipping_tracking_url',$plugin_admin, 'multi_carrier_add_tracking_url',10,2);
		$this->loader->add_action('admin_notices', $plugin_admin, 'webexpert_order_tracking_license_admin_notices');
		$this->loader->add_action('plugin_action_links', $plugin_admin, 'order_tracking_plugin_action_links', 10, 2);
		$this->loader->add_filter( 'woocommerce_shop_order_search_fields', $plugin_admin, 'woocommerce_shop_order_search_voucher' );
		$this->loader->add_filter( 'woocommerce_order_table_search_query_meta_keys', $plugin_admin, 'woocommerce_shop_order_search_voucher' );
		$this->loader->add_filter( 'manage_woocommerce_page_wc-orders_columns', $plugin_admin, 'webexpert_order_tracking_add_tracking_column_header', 20 );
		$this->loader->add_action( 'manage_woocommerce_page_wc-orders_custom_column', $plugin_admin, 'webexpert_order_tracking_add_tracking_column_content',10,2);
		$this->loader->add_filter( 'manage_edit-shop_order_columns', $plugin_admin, 'webexpert_order_tracking_add_tracking_column_header', 20 );
		$this->loader->add_action( 'manage_shop_order_posts_custom_column', $plugin_admin, 'webexpert_order_tracking_add_tracking_column_content',10,2);
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'wp_ajax_webexpert_order_tracking_thickbox',$plugin_admin,  'webexpert_order_tracking_thickbox' );
        $this->loader->add_action( 'woocommerce_email_enabled_customer_completed_order', $plugin_admin, 'custom_conditional_email_notifications',10,2 );
        $this->loader->add_action("wp_ajax_webexpert_order_tracking_export_orders", $plugin_admin,"webexpert_order_tracking_export_orders");

    }

	public function get_version() {
		return $this->version;
	}

	public function get_name() {
		return $this->plugin_slug;
	}

	public function run() {
		$this->loader->run();
	}

	private function set_locale()
	{
		$plugin_i18n = new Webexpert_woocommerce_order_tracking_i18n();
		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}
}
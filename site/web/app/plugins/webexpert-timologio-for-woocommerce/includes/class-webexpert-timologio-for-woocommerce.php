<?php

class Webexpert_Timologio_For_Woocommerce {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		if (defined('Webexpert_Timologio_For_Woocommerce_Version')) {
			$this->version = Webexpert_Timologio_For_Woocommerce_Version;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'webexpert-timologio-for-woocommerce';
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/vendor/autoload.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-webexpert-timologio-for-woocommerce-loader.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-webexpert-timologio-for-woocommerce-i18n.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-webexpert-timologio-for-woocommerce-admin.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-webexpert-timologio-for-woocommerce-public.php';
		$this->loader = new Webexpert_Timologio_For_Woocommerce_Loader();
	}

	private function set_locale() {
		$plugin_i18n = new Webexpert_Timologio_For_Woocommerce_i18n();
		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	private function define_admin_hooks() {
		$plugin_admin = new Webexpert_Timologio_For_Woocommerce_Admin($this->get_plugin_name(), $this->get_version());
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_filter('woocommerce_admin_billing_fields', $plugin_admin, 'webexpert_timologio_for_woocommerce_admin_billing_fields');
		$this->loader->add_filter('woocommerce_formatted_address_replacements', $plugin_admin, 'webexpert_timologio_for_woocommerce_add_woocommerce_formatted_address_replacements', 10, 2);
		$this->loader->add_filter('woocommerce_order_formatted_billing_address', $plugin_admin, 'webexpert_timologio_for_woocommerce_add_woocommerce_order_fields', 10, 2);
		$this->loader->add_filter('woocommerce_localisation_address_formats', $plugin_admin, 'webexpert_timologio_for_woocommerce_add_woocommerce_localisation_address_formats', 10, 1);
		$this->loader->add_action('woocommerce_process_shop_order_meta', $plugin_admin, 'woocommerce_process_shop_order');
		$this->loader->add_action('add_meta_boxes', $plugin_admin, 'webexpert_timologio_for_woocommerce_add_metaboxes');
		$this->loader->add_action( 'woocommerce_shop_order_search_fields', $plugin_admin, 'webexpert_timologio_for_wc_search_for_billing_vat_id', 10, 1 );
		$this->loader->add_action( 'woocommerce_order_table_search_query_meta_keys', $plugin_admin, 'webexpert_timologio_for_wc_search_for_billing_vat_id', 10, 1 );

		$this->loader->add_action('init', $plugin_admin, 'webexpert_timologio_for_woocommerce_init');

		$this->loader->add_action("wp_ajax_webexpert_timologio_for_wc_finalize_order_invoice", $plugin_admin, "webexpert_timologio_for_wc_finalize_order_invoice");
		$this->loader->add_action("wp_ajax_nopriv_webexpert_timologio_for_wc_finalize_order_invoice", $plugin_admin, "webexpert_timologio_for_wc_finalize_order_invoice");

		$this->loader->add_action("wp_ajax_webexpert_timologio_for_wc_finalize_order_invoice_upload", $plugin_admin, "webexpert_timologio_for_wc_finalize_order_invoice_upload");
		$this->loader->add_action("wp_ajax_nopriv_webexpert_timologio_for_wc_finalize_order_invoice_upload", $plugin_admin, "webexpert_timologio_for_wc_finalize_order_invoice_upload");

		$this->loader->add_action("wp_ajax_webexpert_timologio_for_wc_order_invoice_upload_delete", $plugin_admin, "webexpert_timologio_for_wc_order_invoice_upload_delete");
		$this->loader->add_action("wp_ajax_nopriv_webexpert_timologio_for_wc_order_invoice_upload_delete", $plugin_admin, "webexpert_timologio_for_wc_order_invoice_upload_delete");

		$this->loader->add_action( 'woocommerce_order_actions', $plugin_admin , 'webexpert_timologio_for_wc_add_order_custom_actions' , 10 , 2);
		$this->loader->add_action( 'woocommerce_order_action_wc_webexpert_send_invoice_to_email_action', $plugin_admin, 'webexpert_timologio_for_wc_process_send_invoice_to_email_action');

		$this->loader->add_action("wp_ajax_webexpert_timologio_for_wc_check_aade_connection", $plugin_admin, "webexpert_timologio_for_wc_check_aade_connection");
		$this->loader->add_action("wp_ajax_nopriv_webexpert_timologio_for_wc_check_aade_connection", $plugin_admin, "webexpert_timologio_for_wc_check_aade_connection");

		$this->loader->add_filter( 'woocommerce_email_classes', $plugin_admin , 'webexpert_timologio_register_email' , 90, 1 );
		$this->loader->add_filter( 'woocommerce_email_attachments', $plugin_admin ,  'webexpert_attach_disclaimer_pdf_to_email', 10, 3);

		$this->loader->add_action('wp_ajax_webexpert_timologio_for_woocommerce_generate_invoice', $plugin_admin, 'webexpert_timologio_for_woocommerce_generate_invoice', 10, 2);

		$this->loader->add_action( 'woocommerce_order_status_completed', $plugin_admin ,  'webexpert_timologio_for_wc__order_status_completed', 10, 1 );
		$this->loader->add_action('woocommerce_admin_order_data_after_billing_address',$plugin_admin,'webexpert_timologio_for_wc_admin_formatted',10,1);
		$this->loader->add_action('pre_user_query', $plugin_admin, 'pre_user_search', 10, 2);
	}


	private function define_public_hooks() {
		$plugin_public = new Webexpert_Timologio_For_Woocommerce_Public($this->get_plugin_name(), $this->get_version());
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
		$this->loader->add_filter('woocommerce_billing_fields', $plugin_public, 'webexpert_timologio_for_woocommerce_billing_fields');
		$this->loader->add_filter('woocommerce_customer_meta_fields', $plugin_public, 'webexpert_timologio_for_woocommerce_customer_meta_fields');
		$this->loader->add_action('woocommerce_after_checkout_validation', $plugin_public, 'webexpert_timologio_for_woocommerce_checkout_field_process', 10, 2);
		$this->loader->add_action( 'woocommerce_checkout_order_processed', $plugin_public, 'woocommerce_checkout_order_processed' );
		$this->loader->add_action('woocommerce_found_customer_details', $plugin_public, 'webexpert_timologio_for_woocommerce_add_woocommerce_found_customer_details', 10, 3);
		$this->loader->add_action('woocommerce_before_calculate_totals', $plugin_public, 'woo_add_cart_fee');
		$this->loader->add_action("wp_ajax_webexpert_timologio_for_wc_aade_fill", $plugin_public, "webexpert_timologio_for_wc_aade_fill");
		$this->loader->add_action("wp_ajax_nopriv_webexpert_timologio_for_wc_aade_fill", $plugin_public, "webexpert_timologio_for_wc_aade_fill");
		$this->loader->add_filter( 'woocommerce_my_account_my_orders_actions', $plugin_public, 'add_my_account_my_orders_timologio_for_woocommerce_custom_action', 10, 2 );
		$this->loader->add_action("wp_ajax_webexpert_timologio_for_wc_invoice_value", $plugin_public, "webexpert_timologio_for_wc_invoice_value");
		$this->loader->add_action("wp_ajax_nopriv_webexpert_timologio_for_wc_invoice_value", $plugin_public, "webexpert_timologio_for_wc_invoice_value");
		$this->loader->add_action('wp_footer', $plugin_public, 'set_invoice_option_on_checkout');
		$this->loader->add_filter('woocommerce_order_formatted_billing_address', $plugin_public, 'add_custom_field_to_formatted_billing_address', 10, 2);
		$this->loader->add_action('rest_api_init', $plugin_public,'add_custom_billing_fields_to_rest_api');
		$this->loader->add_action('woocommerce_checkout_create_order', $plugin_public, 'add_custom_field_to_order', 20, 2);
	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}
}

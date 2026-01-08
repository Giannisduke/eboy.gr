<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.webexpert.gr/
 * @since      1.0.0
 *
 * @package    Acs_Voucher_For_Woocommerce
 * @subpackage Acs_Voucher_For_Woocommerce/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Acs_Voucher_For_Woocommerce
 * @subpackage Acs_Voucher_For_Woocommerce/includes
 * @author     Web Expert <info@webexpert.gr>
 */
class Acs_Voucher_For_Woocommerce {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Acs_Voucher_For_Woocommerce_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'ACS_VOUCHER_FOR_WOOCOMMERCE_VERSION' ) ) {
			$this->version = ACS_VOUCHER_FOR_WOOCOMMERCE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'acs-voucher-for-woocommerce';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Acs_Voucher_For_Woocommerce_Loader. Orchestrates the hooks of the plugin.
	 * - Acs_Voucher_For_Woocommerce_i18n. Defines internationalization functionality.
	 * - Acs_Voucher_For_Woocommerce_Admin. Defines all hooks for the admin area.
	 * - Acs_Voucher_For_Woocommerce_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-acs-voucher-for-woocommerce-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-acs-voucher-for-woocommerce-i18n.php';
        if (!class_exists('PostTypes\PostType'))
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/PostTypes/src/PostType.php';
        if (!class_exists('PostTypes\Taxonomy'))
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/PostTypes/src/Taxonomy.php';
        if (!class_exists('PostTypes\Columns'))
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/PostTypes/src/Columns.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-acs-voucher-for-woocommerce-shipping-class.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . "includes/fpdf/fpdf.php";
		require_once plugin_dir_path( dirname( __FILE__ ) ) . "includes/fpdi/autoload.php";
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-acs-voucher-for-woocommerce-admin.php';
        $this->loader = new Acs_Voucher_For_Woocommerce_Loader();
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-acs-voucher-for-woocommerce-cron.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-acs-voucher-for-woocommerce-public.php';
  }

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Acs_Voucher_For_Woocommerce_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Acs_Voucher_For_Woocommerce_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
    private function define_admin_hooks() {

        $plugin_admin = new Acs_Voucher_For_Woocommerce_Admin( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'init', $plugin_admin, 'jobs_ctp' ,5);
        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'jobs_metabox' );
        $this->loader->add_filter('bulk_actions-edit-we_voucher_job', $plugin_admin ,'register_my_bulk_actions');
        $this->loader->add_filter( 'handle_bulk_actions-edit-we_voucher_job',$plugin_admin, 'register_my_bulk_actions_handler', 10, 3 );
        $this->loader->add_action('admin_head', $plugin_admin, 'remove_date_drop');
        $this->loader->add_action("wp_ajax_acs_create_voucher", $plugin_admin,"acs_create_voucher");
        $this->loader->add_action("wp_ajax_acs_print_voucher", $plugin_admin,"acs_print_voucher");
        $this->loader->add_action("wp_ajax_acs_cancel_voucher", $plugin_admin,"acs_cancel_voucher");
        $this->loader->add_action("wp_ajax_acs_close_voucher", $plugin_admin,"acs_close_voucher");
	    $this->loader->add_action("wp_ajax_acs_print_pickup_list", $plugin_admin,"acs_print_pickup_list");
        $this->loader->add_action("wp_ajax_webexpert_acs_cod_beneficiary_info", $plugin_admin,"acs_cod_beneficiary_info");
        $this->loader->add_action("wp_ajax_webexpert_acs_find_pickup_lists", $plugin_admin,"webexpert_acs_find_pickup_lists");
	    $this->loader->add_action("wp_ajax_acs_reset_voucher", $plugin_admin,"acs_reset_voucher");
        $this->loader->add_action( 'restrict_manage_posts', $plugin_admin, 'form' );
        $this->loader->add_action( 'pre_get_posts', $plugin_admin, 'filterquery' );
	    $this->loader->add_action( 'wp_ajax_webexpert_print_voucher_acs',$plugin_admin,  'webexpert_print_voucher_acs' );
	    $this->loader->add_action( 'wp_ajax_webexpert_cancel_voucher_acs',$plugin_admin,  'webexpert_cancel_voucher_acs' );
	    $this->loader->add_action( ACS_Voucher_For_Woocommerce_Cron::ACS_VOUCHER_FOR_WOOCOMMERCE_CRON_HOOK, $plugin_admin, 'run_daily_event' );
	    $this->loader->add_action( ACS_Voucher_For_Woocommerce_Cron::ACS_VOUCHER_FOR_WOOCOMMERCE_CHECK_STATUS, $plugin_admin, 'run_hourly_event' );
        $this->loader->add_shortcode( 'webexpert_acs_track_status', $plugin_admin, 'webexpert_acs_track_status' );
        $this->loader->add_shortcode( 'webexpert_acs_track_checkpoints', $plugin_admin, 'webexpert_acs_track_checkpoints' );
	    $this->loader->add_action( 'admin_notices', $plugin_admin, 'webexpert_acs_courier_bulk_action_notices' );
	    $this->loader->add_filter( 'woocommerce_my_account_my_orders_actions', $plugin_admin, 'webexpert_add_edit_order_my_account_orders_actions', 50, 2 );
	    $this->loader->add_action( 'woocommerce_after_account_orders', $plugin_admin, 'action_after_account_orders_js');
	    $this->loader->add_action( 'woocommerce_order_status_completed',$plugin_admin, 'acs_courier_voucher_auto_issue', 10, 1 );
	    $this->loader->add_filter( 'webexpert_woocommerce_order_tracking_custom_shipping_company_name',$plugin_admin, 'acs_courier_shipping_company_name', 11, 2 );
	    $this->loader->add_filter( 'webexpert_woocommerce_order_tracking_custom_shipping_tracking_url',$plugin_admin, 'acs_courier_shipping_tracking_url', 11, 2 );
	    $this->loader->add_action('admin_notices', $plugin_admin, 'webexpert_acs_license_admin_notices');
	    $this->loader->add_action('plugin_action_links', $plugin_admin, 'acs_plugin_action_links', 10, 2);
	    $this->loader->add_filter('post_row_actions',$plugin_admin, 'webexpert_action_row', 10, 2);
	    $this->loader->add_action('wp_enqueue_scripts', $plugin_admin, 'enqueue_public_scripts');
	    $this->loader->add_action("wp_ajax_webexpert_acs_validate_address", $plugin_admin, "webexpert_acs_validate_address_ajax");
	    $this->loader->add_action("wp_ajax_nopriv_webexpert_acs_validate_address", $plugin_admin, "webexpert_acs_validate_address_ajax");
	    $this->loader->add_action( 'manage_shop_order_posts_custom_column', $plugin_admin, 'webexpert_acs_delivered_list', 10, 2 );
	    $this->loader->add_action( 'woocommerce_shop_order_list_table_custom_column', $plugin_admin, 'webexpert_acs_delivered_list' , 10, 2 );
    }

	public function define_public_hooks() {
		$plugin_public = new Acs_Voucher_For_Woocommerce_Public($this->get_plugin_name() , $this->get_version());
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
		$this->loader->add_action('wp_ajax_webexpert_get_acs_order_html', $plugin_public  , 'webexpert_get_acs_order_html');
		$this->loader->add_action('wp_ajax_nopriv_webexpert_get_acs_order_html' , $plugin_public , 'webexpert_get_acs_order_html');
		$this->loader->add_shortcode('webexpert_acs_track_form', $plugin_public, 'webexpert_acs_track_form');
		$this->loader->add_action( 'woocommerce_thankyou', $plugin_public, 'webexpert_acs_woocommerce_thankyou', 10, 1 );
		$this->loader->add_action( 'woocommerce_payment_complete', $plugin_public, 'webexpert_acs_payment_complete', 10, 1);

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Acs_Voucher_For_Woocommerce_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}

<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 */
class Dicha_Skroutz_Feed {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var Dicha_Skroutz_Feed_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected Dicha_Skroutz_Feed_Loader $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected string $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var string $version The current version of the plugin.
	 */
	protected string $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 */
	public function __construct() {

		$this->version     = defined( 'DICHA_SKROUTZ_FEED_VERSION' ) ? DICHA_SKROUTZ_FEED_VERSION : '1.0.0';
		$this->plugin_name = defined( 'DICHA_SKROUTZ_FEED_SLUG' ) ? DICHA_SKROUTZ_FEED_SLUG : 'xml-feed-for-skroutz-for-woocommerce';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_feed_generation_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Dicha_Skroutz_Feed_Loader. Orchestrates the hooks of the plugin.
	 * - Dicha_Skroutz_Feed_i18n. Defines internationalization functionality.
	 * - Dicha_Skroutz_Feed_Admin. Defines all hooks for the admin area.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dc-skroutz-feed-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dc-skroutz-feed-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-dc-skroutz-feed-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-dc-skroutz-feed-creator.php';

		/**
		 * The class responsible for WP CLI commands.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dc-skroutz-feed-cli.php';

		$this->loader = new Dicha_Skroutz_Feed_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Dicha_Skroutz_Feed_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 */
	private function set_locale() {

		$plugin_i18n = new Dicha_Skroutz_Feed_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all hooks related to the admin area functionality of the plugin.
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Dicha_Skroutz_Feed_Admin( $this->get_plugin_name(), $this->get_version() );

		// menu and settings hooks
		if ( ( isset( $_GET['page'] ) && $_GET['page'] === $this->plugin_name ) || wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'dicha_skroutz_feed_option_group-options' ) ) {
			$this->loader->add_action( 'admin_init', $plugin_admin, 'register_plugin_settings' );
			$this->loader->add_action( 'digital_challenge_plugin_settings', $plugin_admin, 'render_plugin_settings' );
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'digital_challenge_plugins' && has_action( 'digital_challenge_plugin_settings' ) === false ) {
			$this->loader->add_action( 'digital_challenge_plugin_settings', $plugin_admin, 'dc_render_settings_homepage' );
		}

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'create_dc_toplevel_menu' );
		$this->loader->add_action( 'digital_challenge_plugin_settings_tabs', $plugin_admin, 'create_plugin_settings_tab' );
		$this->loader->add_action( 'admin_post_save_settings', $plugin_admin, 'save_settings' );
		$this->loader->add_filter( 'plugin_action_links', $plugin_admin, 'add_plugin_action_links', 10, 2 );


		// declare WC compatibilities
		$this->loader->add_action( 'before_woocommerce_init', $plugin_admin, 'declare_compatibility_with_wc_features' );


		// Product tabs and custom product/variation fields
		$this->loader->add_filter( 'woocommerce_product_data_tabs', $plugin_admin, 'register_new_exports_tab' );
		$this->loader->add_filter( 'woocommerce_product_data_panels', $plugin_admin, 'print_exports_tab_content' );
		$this->loader->add_action( 'woocommerce_product_options_sku', $plugin_admin, 'add_ean_field_under_sku', 50 );
		$this->loader->add_action( 'woocommerce_product_options_pricing', $plugin_admin, 'add_skroutz_price_field', 50 );
		$this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'save_product_custom_fields' );
		$this->loader->add_action( 'woocommerce_product_after_variable_attributes', $plugin_admin, 'print_variation_skroutz_fields', 10, 3 );
		$this->loader->add_action( 'woocommerce_save_product_variation', $plugin_admin, 'save_variation_skroutz_fields', 10, 2 );



		// Product list filters and new availability column
		$this->loader->add_filter( 'manage_product_posts_columns', $plugin_admin, 'register_availability_column' );
		$this->loader->add_filter( 'manage_product_posts_custom_column', $plugin_admin, 'fill_availability_column', 10, 2 );
		$this->loader->add_action( 'restrict_manage_posts', $plugin_admin, 'print_custom_fields_filter_in_admin_list', 99 );
		$this->loader->add_filter( 'parse_query', $plugin_admin, 'filter_by_custom_fields_query_mod' );


		// Quick edit and bulk edit actions for Skroutz Availability field
		$this->loader->add_action( 'woocommerce_product_quick_edit_end', $plugin_admin, 'add_availability_field_to_quick_edit_box', 20 );
		$this->loader->add_action( 'woocommerce_product_bulk_edit_end', $plugin_admin, 'add_availability_field_to_bulk_edit_box' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'quick_and_bulk_edit_save_availability' );


		// Feed monitor
		$this->loader->add_action( 'dicha_skroutz_feed_monitor', $plugin_admin, 'check_feed_generation_status' );
		
		
		// enqueues
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
	}

	/**
	 * Register all hooks related to the XML feed generation.
	 */
	private function define_feed_generation_hooks() {

		$plugin_feed  = new Dicha_Skroutz_Feed_Creator();

		// feed generation related hooks
		$this->loader->add_action( 'dicha_skroutz_feed_generation', $plugin_feed, 'create_feed' );
		$this->loader->add_action( 'admin_post_dicha_skroutz_feed_create_feed', $plugin_feed, 'create_feed_manual_mode' );
		$this->loader->add_filter( 'woocommerce_product_data_store_cpt_get_products_query', $plugin_feed, 'handle_skroutz_products_query_vars', 10, 2 );
	}


	/**
	 * Run the loader to execute all hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return Dicha_Skroutz_Feed_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader(): Dicha_Skroutz_Feed_Loader {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return string The version number of the plugin.
	 */
	public function get_version(): string {
		return $this->version;
	}
}

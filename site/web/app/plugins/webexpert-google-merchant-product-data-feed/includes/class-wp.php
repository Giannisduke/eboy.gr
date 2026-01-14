<?php

class WEGoogleMerchantProductDataFeed
{

    protected $loader;
    protected $plugin_slug;
    protected $version;

    public function __construct()
    {
        $this->plugin_slug = 'webexpert-google-merchant-product-data-feed';
        $this->version = WE_XML_GOOGLE_MERCHANT_PLUGIN_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_wp_cli_commands();
    }

    private function load_dependencies()
    {
        require_once plugin_dir_path(__FILE__) . 'class-wp-admin.php';
        require_once plugin_dir_path(__FILE__) . 'class-wp-loader.php';
        $this->loader = new WEGoogleMerchantProductDataFeed_Loader();
    }

    private function define_admin_hooks()
    {
        $admin = new WEGoogleMerchantProductDataFeed_Admin($this->get_version(),$this->plugin_slug);
        $this->loader->add_action('init', $admin, 'we_google_merchant_product_data_feed_load_textdomain');
	    $this->loader->add_action('admin_menu', $admin, 'we_google_merchant_product_data_feed_create_menu');
        $this->loader->add_action('admin_post_nopriv_we_run_xml_google_merchant', $admin, 'we_run_xml');
        $this->loader->add_action('admin_post_we_run_xml_google_merchant', $admin, 'we_run_xml');
        $this->loader->add_action('admin_post_nopriv_we_save_settings_google_merchant', $admin, 'we_save_settings');
        $this->loader->add_action('admin_post_we_save_settings_google_merchant', $admin, 'we_save_settings');
        $this->loader->add_action('admin_init', $admin, 'we_register_settings');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'we_google_merchant_product_data_feed_enqueue_scripts');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'we_google_merchant_product_data_feed_enqueue_styles');
	    $this->loader->add_action('plugins_loaded',$admin, 'init_webexpert_google_merchant_license_check');
	    $this->loader->add_action('admin_notices',$admin, 'webexpert_google_merchant_license_admin_notices');
	    $this->loader->add_action('add_option_we_google_merchant_product_data_feed_license_key', $admin, 'webexpert_google_merchant_callback_update', 10, 2);
	    $this->loader->add_action('update_option_we_google_merchant_product_data_feed_license_key', $admin, 'webexpert_google_merchant_callback_update', 10, 2);
	    $this->loader->add_action('plugin_action_links', $admin, 'google_merchant_plugin_action_links', 10, 2);


	    $this->loader->add_action('product_cat_add_form_fields', $admin, 'category_mapping_taxonomy_add_new_meta_field', 10, 1);
	    $this->loader->add_action('product_cat_edit_form_fields', $admin, 'category_mapping_taxonomy_edit_meta_field', 10, 1);
	    $this->loader->add_action('edited_product_cat', $admin, 'save_taxonomy_custom_meta', 10, 1);
	    $this->loader->add_action('create_product_cat', $admin, 'save_taxonomy_custom_meta', 10, 1);
    }

    private function define_wp_cli_commands() {
        if ( ! class_exists( 'WP_CLI' ) ) {
            return;
        }
        require_once plugin_dir_path(__FILE__) . 'class-wp-admin-cli.php';

        WP_CLI::add_command( 'webexpert-google-xml', 'WEGoogleXML_Admin_CLI' );
    }


    public function run()
    {
        $this->loader->run();
    }

    public function get_version()
    {
        return $this->version;
    }
}
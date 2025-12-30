<?php

namespace PixelYourSite;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * PixelYourSite Core class.
 */
final class PYS extends Settings implements Plugin {

    private static $_instance;

    private $form_track;

    /** @var $eventsManager EventsManager */
    private $eventsManager;

    /** @var $registeredPixels array Registered pixels */
    private $registeredPixels = array();

    /** @var $registeredPlugins array Registered plugins */
    private $registeredPlugins = array();

    private $adminPagesSlugs = array();

    private $externalId;
    /**
     * @var PYS_Logger
     */
    private $logger;

    private $containers;

    private $crawlerDetect;

    public static function instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;

    }

    public function getPluginName() {
        return PYS_PLUGIN_NAME;
    }

    public function getPluginFile() {
        return PYS_PRO_PLUGIN_FILE;
    }

    public function getPluginVersion() {
        return PYS_VERSION;
    }

    public function __construct() {

        add_filter( 'plugin_row_meta', array( $this, 'pluginRowMeta' ), 10, 2 );
        // initialize settings
        parent::__construct( 'core' );

        add_action( 'admin_init', array( $this, 'updatePlugin' ), 0 );
        add_action( 'admin_init', 'PixelYourSite\manageAdminPermissions' );

        /**
         * Priority 9 used because on some events, like EDD's CompleteRegistration, are fired on 'init' action
         * with default (10) priority and PYS should be initialized before it.
         *
         * 3rd party extensions, like Pinterest addon, should be loaded with lower priority.
         */
        add_action( 'wp', array( $this, 'controllSessionStart'), 10);
        add_action( 'init', array( $this, 'set_pbid'), 8);
        add_action( 'init', array( $this, 'init' ), 9 );
        add_action( 'init', array( $this, 'afterInit' ), 11 );

        add_action( 'admin_menu', array( $this, 'adminMenu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'adminEnqueueScripts' ) );
        add_action( 'admin_notices', 'PixelYourSite\adminRenderNotices' );
        add_action( 'admin_init', array( $this, 'adminProcessRequest' ), 11 );

        // run Events Manager
        add_action( 'template_redirect', array( $this, 'managePixels' ), 1);

        // track user registrations
        add_action( 'user_register', array( $this, 'userRegisterHandler' ) );

        // track user login event
        add_action('wp_login', [$this,'userLogin'], 10, 2);

        // "admin_permission" option custom sanitization function
        add_filter( 'pys_core_settings_sanitize_admin_permissions_field', function( $value ) {

            // "administrator" always should be allowed
            if ( ! is_array( $value ) || ! in_array( 'administrator', $value ) ) {
                $value[] = 'administrator';
            }

            manageAdminPermissions();

            return $this->sanitize_multi_select_field( $value );

        } );

        // redirect template for generate file
        add_action( 'admin_init', array($this,'generate_event_export_json') );
        add_action( 'wp_ajax_pys_import_events', array( $this, 'import_custom_events' ) );

        add_action( 'wp_ajax_pys_get_gdpr_filters_values', array( $this, 'ajaxGetGdprFiltersValues' ) );
        add_action( 'wp_ajax_nopriv_pys_get_gdpr_filters_values', array( $this, 'ajaxGetGdprFiltersValues' ) );

        add_action( 'wp_ajax_pys_get_pbid', array( $this, 'get_pbid_ajax' ) );
        add_action( 'wp_ajax_nopriv_pys_get_pbid', array( $this, 'get_pbid_ajax' ) );

        /*
         * Restore settings after COG plugin
         * */
        add_action( 'deactivate_pixel-cost-of-goods/pixel-cost-of-goods.php',array($this,"restoreSettingsAfterCog"));

        /*
         * Create facebook category pixel id field for woo
         * */
        add_action('product_cat_add_form_fields', array($this,'add_product_category_fb_pixel_field'));
        add_action('product_cat_edit_form_fields', array($this,'add_product_category_fb_pixel_field'));

        add_action('edited_product_cat', array($this,'save_product_category_fb_woo_pixel_field'));
        add_action('create_product_cat', array($this,'save_product_category_fb_woo_pixel_field'));

        /*
         * For EDD
         * */
        add_action('download_category_add_form_fields', array($this,'add_product_category_fb_edd_pixel_field'));
        add_action('download_category_edit_form_fields', array($this,'add_product_category_fb_edd_pixel_field'));

        add_action('edited_download_category', array($this,'save_product_category_fb_edd_pixel_field'));
        add_action('create_download_category', array($this,'save_product_category_fb_edd_pixel_field'));

        /**
         * For Woo
         */
        add_action("woocommerce_checkout_order_processed",array($this,'woo_checkout_process'),10,3);
        /**
         * Send Server Events
         */
        if(!$this->isCachePreload()){
	        add_action('woocommerce_order_status_completed', array($this, 'woo_completed_purchase'),30);
	        add_action('woocommerce_order_status_refunded', array($this, 'woo_refund_order'),30);

	        add_action( 'edd_recurring_record_payment', array( $this, 'edd_recurring_payment' ),10,4 );
	        add_action( 'edd_complete_purchase', array( $this, 'edd_completed_purchase' ),10,1 );
	        add_action( 'edd_refund_order', array( $this, 'edd_refund_order' ), 10, 3 );
        }

        if( ! wp_next_scheduled( 'license_check_event' ) ) {

            wp_schedule_event( time(), 'daily', 'license_check_event');
        }
        add_action('license_check_event', array( $this, 'cronCheckLicense'));
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this,'add_list_name_to_order_items'), 10, 4 );

        $this->logger = new PYS_Logger();
        $this->crawlerDetect = new CrawlerDetect();
        $this->containers = new gtmContainers();
	    new FormSubmissionHandler();


    }

    public function init() {

        if (current_user_can( 'manage_pys' ) ) {
            $this->logger->init();

            $loggers = [
                'meta' => [$this->logger, 'downloadLogFile'],
                'ga' => [GA()->getLog(), 'downloadLogFile'],
                'tiktok' => [Tiktok()->getLog(), 'downloadLogFile'],
            ];


            $clearLoggers = [
                'clear_plugin_logs' => [$this->logger, 'remove'],
                'clear_ga_logs' => [GA()->getLog(), 'remove'],
                'clear_tiktok_logs' => [Tiktok()->getLog(), 'remove']
            ];

            if (isPinterestActive()) {
                $loggers['pinterest'] = [Pinterest()->getLog(), 'downloadLogFile'];
                $clearLoggers['clear_pinterest_logs'] = [Pinterest()->getLog(), 'remove'];
            }

            if (isset($_GET['download_logs']) && array_key_exists($_GET['download_logs'], $loggers)) {
                if (!isset($_GET['_wpnonce_download_logs']) || !wp_verify_nonce($_GET['_wpnonce_download_logs'], 'download_logs_nonce')) {
                    wp_die(__('Invalid nonce', 'pixelyoursite'));
                }
                $logger = $loggers[$_GET['download_logs']];
                if (is_callable($logger)) {
                    call_user_func($logger);
                } elseif (is_callable([$logger[0], $logger[1]])) {
                    call_user_func([$logger[0], $logger[1]]);
                }
            }

            foreach ($clearLoggers as $key => $logger) {
                if (isset($_GET[$key]) && (is_callable($logger) || (is_callable([$logger[0], $logger[1]]) && method_exists($logger[0], $logger[1])))) {
                    if (!isset($_GET['_wpnonce_clear_logs']) || !wp_verify_nonce($_GET['_wpnonce_clear_logs'], 'clear_logs_nonce')) {
                        wp_die(__('Invalid nonce', 'pixelyoursite'));
                    }
                    if (is_callable($logger)) {
                        call_user_func($logger);
                    } else {
                        call_user_func([$logger[0], $logger[1]]);
                    }
                    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                    wp_redirect(remove_query_arg($key, $actual_link));
                    exit;
                }
            }

            if (isset($_GET['download_container'])) {
                if (!isset($_GET['_wpnonce_template_logs']) || !wp_verify_nonce($_GET['_wpnonce_template_logs'], 'download_template_nonce')) {
                    wp_die(__('Invalid nonce', 'pixelyoursite'));
                }
                $this->containers->downloadLogFile($_GET['download_container']);
            }
        }

        $db = new \PixelYourSite\DataBaseManager();
        $db->create_table();

        register_post_type( 'pys_event', array(
            'public' => false,
            'supports' => array( 'title' )
        ) );

        // initialize options
        $this->locateOptions(
            PYS_PATH . '/includes/options_fields.json',
            PYS_PATH . '/includes/options_defaults.json'
        );

        // register pixels and plugins (add-ons)
        do_action( 'pys_register_pixels', $this );
        do_action( 'pys_register_plugins', $this );

        // load dummy Pinterest plugin for admin UI
        if ( ! array_key_exists( 'pinterest', $this->registeredPlugins ) ) {
            /** @noinspection PhpIncludeInspection */
            require_once PYS_PATH . '/modules/pinterest/pinterest.php';
        }

        // load dummy Bing plugin for admin UI
        if ( ! array_key_exists( 'bing', $this->registeredPlugins ) ) {
            /** @noinspection PhpIncludeInspection */
            require_once PYS_PATH . '/modules/bing/bing.php';
        }

        // maybe disable Facebook for WooCommerce pixel output
        if ( isWooCommerceActive()
            && array_key_exists( 'facebook', $this->registeredPixels ) && Facebook()->configured() ) {
            add_filter( 'facebook_for_woocommerce_integration_pixel_enabled', '__return_false' );
        }



        if(Facebook()->getOption('test_api_event_code_expiration_at'))
        {
            foreach (Facebook()->getOption('test_api_event_code_expiration_at') as $key => $test_code_expiration_at)
            {
                if(time() >= $test_code_expiration_at)
                {
                    Facebook()->updateOptions(array("test_api_event_code" => array()));
                    Facebook()->updateOptions(array("test_api_event_code_expiration_at" => array()));
                }
            }
        }

        if(Tiktok()->getOption('test_api_event_code_expiration_at'))
        {
            foreach (Tiktok()->getOption('test_api_event_code_expiration_at') as $test_code_expiration_at)
            {
                if(time() >= $test_code_expiration_at)
                {
                    Tiktok()->updateOptions(array("test_api_event_code" => array()));
                    Tiktok()->updateOptions(array("test_api_event_code_expiration_at" => array()));
                }
            }
        }

        $eventsFormFactory = apply_filters("pys_form_event_factory",[]);
        if(!$eventsFormFactory)
        {
            $options = array(
                'enable_success_send_form'     => false
            );
            PYS()->updateOptions($options);
        }

        if (isRealCookieBannerPluginActivated()) {

            add_action('RCB/Templates/TechnicalHandlingIntegration', function ( $integration ) {

                $this->handle_rcb_integration($integration, Facebook()->configured(), 'facebook-pixel', PYS_PRO_PLUGIN_FILE);
                $this->handle_rcb_integration($integration, GA()->configured(), 'google-analytics-analytics-4', PYS_PRO_PLUGIN_FILE);
                $this->handle_rcb_integration($integration, Ads()->configured(), 'google-ads-conversion-tracking', PYS_PRO_PLUGIN_FILE);
                $this->handle_rcb_integration($integration, Tiktok()->configured(), 'tik-tok-pixel', PYS_PRO_PLUGIN_FILE);
                if(isPinterestActive()){
                    $this->handle_rcb_integration($integration, Pinterest()->configured(), 'pinterest-tag', PYS_PINTEREST_PLUGIN_FILE);
                }
                if(isBingActive()){
                    $this->handle_rcb_integration($integration, Bing()->configured(), 'bing-ads', PYS_BING_PLUGIN_FILE);
                }



            });
        }
        EnrichOrder()->init();
        AjaxHookEventManager()->addHooks();
    }
    private function handle_rcb_integration( $integration, $is_active, $type, $plugin_dir) {

        if (
            $is_active
            && $integration->integrate($plugin_dir, $type)
        ) {
            $integration->setCodeOptIn('');
            $integration->setCodeOptOut('');
        }
    }
	function controllSessionStart(){
		if(PYS()->getOption('session_disable')) return;

	    if (!is_admin() && php_sapi_name() !== 'cli' && session_status() != PHP_SESSION_DISABLED) {
		    if (!headers_sent() && session_status() == PHP_SESSION_NONE) {
			    session_start();
		    }
            if (empty($_SESSION['TrafficSource'])) {
                $_SESSION['TrafficSource'] = getTrafficSource();
            }
            if (empty($_SESSION['LandingPage'])) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $currentUrl = $protocol . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
                $landing = explode('?', $currentUrl)[0];
                $_SESSION['LandingPage'] = $landing;
            }
            if (empty($_SESSION['TrafficUtms'])) {
                $_SESSION['TrafficUtms'] = getUtms();
            }
            if (empty($_SESSION['TrafficUtmsId'])) {
                $_SESSION['TrafficUtmsId'] = getUtmsId();
            }
        }
    }

	function get_pbid(){
		return $this->externalId;
	}
    function set_pbid() {
        $pbidCookieName = 'pbid';
        $isTrackExternalId = EventsManager::isTrackExternalId();
        $user = wp_get_current_user();

        if ($user && $isTrackExternalId) {
            $userExternalId = $user->get('external_id');
            if (!empty($userExternalId)) {
                $this->externalId = $userExternalId;
                return;
            }
        }

        if ($isTrackExternalId) {

            if (!empty($_COOKIE[$pbidCookieName])) {
                $this->externalId = $_COOKIE[$pbidCookieName];
            } elseif ( PYS()->getOption('external_id_use_transient') && get_transient('externalId-' . PYS()->get_user_ip())) {
                $this->externalId = get_transient('externalId-' . PYS()->get_user_ip());
            }
            else {
                $uniqueId = bin2hex(random_bytes(16));
                $encryptedUniqueId = hash('sha256', $uniqueId);
                $this->externalId = $encryptedUniqueId;
                if(PYS()->getOption('external_id_use_transient')){
                    set_transient('externalId-' . PYS()->get_user_ip(), $this->externalId, 60 * 10);
                }
            }
        }
    }

    public function get_pbid_ajax(){
        if(defined('DOING_AJAX') && DOING_AJAX){
            $isTrackExternalId = EventsManager::isTrackExternalId();
	        if ($isTrackExternalId && !empty($this->externalId)) {
                $transient = get_transient('externalId-'.PYS()->get_user_ip());
                wp_send_json_success( array('pbid'=> $this->externalId, 'transient' => !empty($transient) ? $transient : false ));
            }
        }
    }
    public function add_list_name_to_order_items( $item, $cart_item_key, $values, $order ) {

        if ( array_key_exists('item_list_name', $values )) {
            $item_list_name = $values['item_list_name'];
            $item->update_meta_data('item_list_name', $item_list_name );
        }
        if ( array_key_exists('item_list_id', $values )) {
            $item_list_id = $values['item_list_id'];
            $item->update_meta_data('item_list_id', $item_list_id );
        }
    }

    public function cronCheckLicense(){
        checkLicense();
    }
    /**
     * Extend options after post types are registered
     */
    public function afterInit() {

        // add available public custom post types to settings
        foreach ( get_post_types( array( 'public' => true, '_builtin' => false ), 'objects' ) as $post_type ) {

            // skip product post type when WC is active
            if ( isWooCommerceActive() && $post_type->name == 'product' ) {
                continue;
            }

            // skip download post type when EDD is active
            if ( isEddActive() && $post_type->name == 'download' ) {
                continue;
            }

        }

        maybeMigrate();

    }

    public function import_custom_events() {
        if ( ! $this->adminSecurityCheck()
            || !wp_verify_nonce($_REQUEST['_wpnonce'],"import_events_file_nonce")
        ) {
            return;
        }
        if(isset($_FILES["import_events_file"])) {
            if($_FILES["import_events_file"]['size'] == 0) {
                wp_send_json_error("File is empty ");
                return;
            }
            if( $_FILES["import_events_file"]['type'] != "application/json") {
                wp_send_json_error("File has wrong format ".$_FILES["import_events_file"]['type']);
                return;
            }
            $content = file_get_contents($_FILES["import_events_file"]['tmp_name']);
            $data = json_decode($content,true);

            if(!isset($data['events'])) {
                wp_send_json_error("Events not found");
                return;
            }

            // replace new site url
            $oldSiteUrl = str_replace("/","\/",$data["site_url"]);
            $siteUrl = str_replace("/","\/",site_url());
            $content = str_replace($oldSiteUrl,$siteUrl,$content);

            $data = json_decode(  $content,true);

            // create custom events
            foreach ($data['events'] as $event) {
                CustomEventFactory::import($event);
            }
            wp_send_json_success("OK");
        } else {
            wp_send_json_error("File not found");
        }
    }

    public function generate_event_export_json() {
        if($this->adminSecurityCheck()
            && isset($_GET['tab']) && $_GET['tab'] == "events"
            && isset($_GET['action']) && $_GET['action'] == 'export'
            && wp_verify_nonce($_REQUEST['_wpnonce'],"export_events_file_nonce")
        ) {
            include "views/html-main-events-export.php";
            die();
        }
    }

    /**
     * @param Pixel|Settings $pixel
     */
    public function registerPixel( &$pixel ) {
        if(!is_admin() && !PYS()->getOption('enable_all_tracking_ids') && $pixel->getSlug() != 'gtm'){
            return;
        }
	    switch ($pixel->getSlug()) {
		    case 'pinterest':
			    if(!isPinterestVersionIncompatible()){
				    $this->registeredPixels[ $pixel->getSlug() ] = $pixel;
			    }
                else{
                    $minVersion = PYS_PINTEREST_MIN_VERSION;
	                add_action( 'wp_head', function() use ($minVersion) {
		                echo "<script type='application/javascript' id='pys-pinterest-version-incompatible'>console.warn('You are using incompatible version of PixelYourSite Pinterest Add-On. PixelYourSite PRO requires at least PixelYourSite Pinterest Add-On $minVersion. Please, update to latest version.');</script>\r\n";
	                } );
                }
			    break;
		    case 'bing' :
			    if(!isBingVersionIncompatible()){
				    $this->registeredPixels[ $pixel->getSlug() ] = $pixel;
			    }
			    else{
				    $minVersion = PYS_BING_MIN_VERSION;
				    add_action( 'wp_head', function() use ($minVersion) {
					    echo "<script type='application/javascript' id='pys-bing-version-incompatible'>console.warn('You are using incompatible version of PixelYourSite Bing Add-On. PixelYourSite PRO requires at least PixelYourSite Bing Add-On $minVersion. Please, update to latest version.');</script>\r\n";
				    } );
			    }
			    break;
		    case 'superpack' :
			    if(!isSuperPackVersionIncompatible()){
				    $this->registeredPixels[ $pixel->getSlug() ] = $pixel;
			    }
			    else{
				    $minVersion = PYS_SUPER_PACK_MIN_VERSION;
				    add_action( 'wp_head', function() use ($minVersion) {
					    echo "<script type='application/javascript' id='pys-super-pack-version-incompatible'>console.warn('You are using incompatible version of PixelYourSite Super Pack Add-On. PixelYourSite PRO requires at least PixelYourSite Super Pack Add-On $minVersion. Please, update to latest version.');</script>\r\n";
				    } );
			    }
			    break;
		    default :
			    $this->registeredPixels[ $pixel->getSlug() ] = $pixel;
			    break;
	    }
    }

    /**
     * Return array of registered pixels
     *
     * @return Pixel[]
     */
    public function getRegisteredPixels() {
        return $this->registeredPixels;
    }

    /**
     * @param Pixel|Settings $plugin
     */
    public function registerPlugin( &$plugin ) {
        $this->registeredPlugins[ $plugin->getSlug() ] = $plugin;
    }

    /**
     * Return array of registered plugins
     *
     * @return array
     */
    public function getRegisteredPlugins() {
        return $this->registeredPlugins;
    }

    /**
     * Front-end entry point
     */
    public function managePixels() {

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        // disable Events Manager on Customizer and preview mode
        if (is_admin() || is_customize_preview() || is_preview()) {
            return;
        }
	    if($this->is_user_agent_bot())
	    {
		    return;
	    }
        // disable Events Manager on Elementor editor
        if (did_action('elementor/preview/init')
            || did_action('elementor/editor/init')
            || (isset( $_GET['action'] ) && $_GET['action'] == 'piotnetforms') // skip preview for piotnet forms plugin
        ) {
            return;
        }

        // Disable Events Manager on Divi Builder
        if (function_exists('et_core_is_fb_enabled') && et_core_is_fb_enabled()) {
            return;
        }

        if(PYS()->getOption( 'block_ip_enabled') && in_array($this->get_user_ip(), PYS()->getOption('blocked_ips')))
        {
            return;
        }

        $theme = wp_get_theme(); // gets the current theme
        if ( ('Bricks' == $theme->name || 'Bricks' == $theme->parent_theme) && isset($_GET['bricks']) && $_GET['bricks']=='run') {
            return;
        }

        // output debug info
        if(!PYS()->getOption( 'hide_version_plugin_in_console'))
        {
            add_action( 'wp_head', function() {
                echo "<script type='application/javascript' id='pys-version-script'>console.log('PixelYourSite PRO version " . PYS_VERSION . "');</script>\r\n";
            }, 1 );
        }

        if ( isDisabledForCurrentRole() ) {
            return;
        }

        foreach ( PYS()->getRegisteredPixels() as $pixel ) {
            /** @var Pixel|Settings $pixel */
            if ( $pixel->configured() ) {
                $pixel->checkHidePixel();
            }
        }
        // setup events
        $this->eventsManager = new EventsManager();

//form_track = array('formType' => 'gravity', 'formId' => $form['id'])
        if( get_transient( "form_track" ) )
        {

            $this->eventsManager->trackFormAfterReload(get_transient( "form_track" ));
	        delete_transient(  "form_track" );
        }

        if ( ! Facebook()->configured() && ! GA()->configured() && ! Ads()->configured()
            && ! Pinterest()->configured() && ! Bing()->configured() && ! Tiktok()->configured() ) {
            if(!PYS()->getOption( 'hide_version_plugin_in_console')) {
                add_action('wp_head', function () {
                    echo "<script type='application/javascript' id='pys-config-warning-script'>console.warn('PixelYourSite PRO: no pixel configured.');</script>\r\n";
                });
            }
            return;

        }



    }

    function get_user_ip() {
        // Initialize the IP variable with a default value
        $ip = $_SERVER['REMOTE_ADDR'];

        // Check if HTTP_X_FORWARDED_FOR is set and not empty
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Split the list of IPs using a comma and take the first IP
            $forwarded_ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($forwarded_ips[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            // Use HTTP_CLIENT_IP if available
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }

        return $ip;
    }

    function is_user_agent_bot(){
        if (!PYS()->getOption('block_robot_enabled')) {
            return false;
        }
	    if (!empty($_SERVER['HTTP_USER_AGENT'])) {

            $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
            $excludedRobots = PYS()->getOption('exclude_blocked_robots');

            if (!empty($excludedRobots)) {
                foreach ($excludedRobots as $robot) {
                    if (stripos($userAgent, strtolower($robot)) !== false) {
                        return false;
                    }
                }
            }

            $options = array(
	            'YandexBot', 'YandexAccessibilityBot', 'YandexMobileBot', 'YandexDirectDyn', 'YandexScreenshotBot',
	            'YandexImages', 'YandexVideo', 'YandexVideoParser', 'YandexMedia', 'YandexBlogs',
	            'YandexFavicons', 'YandexWebmaster', 'YandexPagechecker', 'YandexImageResizer', 'YandexAdNet',
	            'YandexDirect', 'YaDirectFetcher', 'YandexCalendar', 'YandexSitelinks', 'YandexMetrika',
	            'YandexNews', 'YandexNewslinks', 'YandexCatalog', 'YandexAntivirus', 'YandexMarket',
	            'YandexVertis', 'YandexForDomain', 'YandexSpravBot', 'YandexSearchShop', 'YandexMedianaBot',
	            'YandexOntoDB', 'YandexOntoDBAPI', 'Googlebot', 'Googlebot-Image', 'Googlebot-News',
	            'Googlebot-Video', 'Mediapartners-Google', 'AdsBot-Google', 'Chrome-Lighthouse', 'Lighthouse',
	            'Mail.RU_Bot', 'bingbot', 'Accoona', 'ia_archiver', 'Ask Jeeves', 'OmniExplorer_Bot',
	            'W3C_Validator', 'WebAlta', 'YahooFeedSeeker', 'Yahoo!', 'Ezooms', 'Tourlentabot', 'MJ12bot',
	            'AhrefsBot', 'SearchBot', 'SiteStatus', 'Nigma.ru', 'Baiduspider', 'Statsbot', 'SISTRIX',
	            'AcoonBot', 'findlinks', 'proximic', 'OpenindexSpider', 'statdom.ru', 'Exabot', 'Spider',
	            'SeznamBot', 'oBot', 'C-T bot', 'Updownerbot', 'Snoopy', 'heritrix', 'Yeti', 'DomainVader',
	            'DCPbot', 'PaperLiBot', 'APIs-Google', 'AdsBot-Google-Mobile', 'AdsBot-Google-Mobile-Apps',
	            'FeedFetcher-Google', 'Google-Read-Aloud', 'DuplexWeb-Google', 'Storebot-Google',
	            'ClaudeBot', 'SeekportBot', 'GPTBot', 'Applebot',
	            'DuckDuckBot', 'Sogou', 'facebookexternalhit', 'Swiftbot', 'Slurp', 'CCBot', 'Go-http-client',
	            'Sogou Spider', 'Facebot', 'Alexa Crawler', 'Cốc Cốc Bot', 'Majestic-12', 'SemrushBot',
	            'DotBot', 'Qwantify', 'Pinterest', 'GrapeshotCrawler', 'archive.org_bot', 'LinkpadBot',
	            'uptimebot', 'Wget', 'curl', 'Google-Structured-Data-Testing-Tool', 'Google-PageSpeed Insights',
	            'Google-Site-Verification', 'BingPreview', 'Slackbot', 'TelegramBot', 'DiscordBot', 'WhatsApp',
	            'RedditBot', 'Yahoo! Slurp', 'Tumblr', 'PetalBot', 'FlipboardProxy', 'LinkedInBot',
	            'SkypeUriPreview', 'Google-Firebase-Storage', 'BitlyBot', 'FeedlyBot', 'AppleNewsBot',
	            'ZyBorg', 'ia_archiver-web.archive.org', 'Mediatoolkitbot', 'DeuSu', 'SMTBot', 'MegaIndex.ru',
	            'Seomoz', 'BLEXBot', 'YisouSpider', '360Spider', 'AddThis', 'TweetmemeBot', 'ContextAd Bot',
	            'Screaming Frog SEO Spider', 'Nutch', 'Baiduspider-image', 'Panscient.com', 'Twitterbot',
	            'YoudaoBot', 'OpenSiteExplorer', 'Linkfluence', 'YaK', 'ContentKing', 'Spinn3r', 'PhantomJS',
	            'HeadlessChrome', 'Snapchat', 'Pingdom', 'Googlebot-Mobile', 'Barkrowler'
            );

            foreach($options as $row) {
                if (stripos($userAgent, strtolower($row)) !== false) {
                    return true;
                }
            }
        }

        return $this->crawlerDetect->isCrawler();
    }

    public function ajaxGetGdprFiltersValues() {

        wp_send_json_success( array(
            'all_disabled_by_api'       => apply_filters( 'pys_disable_by_gdpr', false ),
            'facebook_disabled_by_api'  => apply_filters( 'pys_disable_facebook_by_gdpr', false ),
            'tiktok_disabled_by_api'  => apply_filters( 'pys_disable_tiktok_by_gdpr', false ),
            'analytics_disabled_by_api' => apply_filters( 'pys_disable_analytics_by_gdpr', false ),
            'google_ads_disabled_by_api' => apply_filters( 'pys_disable_google_ads_by_gdpr', false ),
            'pinterest_disabled_by_api' => apply_filters( 'pys_disable_pinterest_by_gdpr', false ),
            'bing_disabled_by_api' => apply_filters( 'pys_disable_bing_by_gdpr', false ),
            'externalID_disabled_by_api' => apply_filters( 'pys_disable_externalID_by_gdpr', false ),
            'disabled_all_cookie'       => apply_filters( 'pys_disable_all_cookie', false ),
            'disabled_start_session_cookie' => apply_filters( 'pys_disabled_start_session_cookie', false ),
            'disabled_advanced_form_data_cookie' => apply_filters( 'pys_disable_advanced_form_data_cookie', false ) || apply_filters( 'pys_disable_advance_data_cookie', false ),
            'disabled_landing_page_cookie'  => apply_filters( 'pys_disable_landing_page_cookie', false ),
            'disabled_first_visit_cookie'  => apply_filters( 'pys_disable_first_visit_cookie', false ),
            'disabled_trafficsource_cookie' => apply_filters( 'pys_disable_trafficsource_cookie', false ),
            'disabled_utmTerms_cookie' => apply_filters( 'pys_disable_utmTerms_cookie', false ),
            'disabled_utmId_cookie' => apply_filters( 'pys_disable_utmId_cookie', false ),
        ) );

    }

    public function userRegisterHandler( $user_id ) {

        if ( PYS()->getOption( 'woo_complete_registration_enabled' )
            || PYS()->getOption( 'automatic_event_signup_enabled' )
        ) {
            update_user_meta( $user_id, 'pys_complete_registration', true );
        }

    }

    /**
     * Hook
     * @param String $user_login
     * @param \WP_User $user
     */
	function userLogin( $user_login, $user ) {
		delete_transient( 'externalId-' . PYS()->get_user_ip() );
		setcookie( "pbid", "", time() - 3600, '/' );
		update_user_meta( $user->ID, 'pys_just_login', true );
		if ( !$user->get( 'external_id' ) ) {
			update_user_meta( $user->ID, 'external_id', $this->externalId );
		}
		// update advance matching user data
		update_advance_matching_user_data( $user->first_name, $user->last_name, $user->user_email, '' );
	}

    /**
     * @param $order_id
     * @param $posted_data
     * @param \WC_Order $order
     */
	public function woo_checkout_process( $order_id, $posted_data, $order ) {

		// update advance matching user data
		$first_name = $order->get_billing_first_name();
		$last_name = $order->get_billing_last_name();
		$email = $order->get_billing_email();
		$phone = $order->get_billing_phone();
		$phone = preg_replace( '/[^0-9.]+/', '', $phone );

		update_advance_matching_user_data( $first_name, $last_name, $email, $phone );
	}

    public function getEventsManager() {
        return $this->eventsManager;
    }

    public function adminMenu() {
        global $submenu;

        add_menu_page( 'PixelYourSite', 'PixelYourSite', 'manage_pys', 'pixelyoursite',
            array( $this, 'adminPageMain' ), PYS_URL . '/dist/images/favicon.png' );
        add_submenu_page( 'pixelyoursite', 'Global Settings', 'Global Settings',
            'manage_pys', 'pixelyoursite_settings', array( $this, 'settingsTemplate' ) );
        PysStatistic()->adminMenu();

        add_submenu_page( 'pixelyoursite', 'UTM Builder', 'UTM Builder',
            'manage_pys', 'pixelyoursite_utm', array( $this, 'utmTemplate' ) );

        add_submenu_page( 'pixelyoursite', 'Licenses', 'Licenses',
            'manage_pys', 'pixelyoursite_licenses', array( $this, 'adminPageLicenses' ) );

        add_submenu_page( 'pixelyoursite', 'System Report', 'System Report',
            'manage_pys', 'pixelyoursite_report', array( $this, 'adminPageReport' ) );


        // core admin pages
        $this->adminPagesSlugs = array(
            'pixelyoursite',
            'pixelyoursite_settings',
            'pixelyoursite_licenses',
            'pixelyoursite_report',
            'pixelyoursite_woo_reports',
            'pixelyoursite_edd_reports',
            'pixelyoursite_utm',
        );

        // rename first submenu item
        if ( isset( $submenu['pixelyoursite'] ) ) {
            $submenu['pixelyoursite'][0][0] = 'Dashboard';
        }

        $this->adminSaveSettings();

    }

    public function add_product_category_fb_pixel_field($term) {

        if(!Facebook()->enabled()) return;

        if(is_a($term,"WP_Term") ) { // edit category view

            $term_id = $term->term_id;
            $categoryIds = Facebook()->getOption("category_pixel_ids");
            $severIds = Facebook()->getOption("category_pixel_server_ids");
            $testCodes = Facebook()->getOption("category_pixel_server_test_code");
            ?>
            <tr class="form-field">
                <th scope="row" valign="top"><label for="pys_fb_pixel_id">Facebook Category Pixel ID:</label></th>
                <td>
                    <input type="text" name="pys_fb_pixel_id" id="pys_fb_pixel_id"
                           value="<?php echo isset($categoryIds[$term_id]) ? $categoryIds[$term_id] : ''; ?>">
                    <p class="description"></p>
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top"><label for="pys_fb_pixel_server_id">Facebook Server Access Token:</label></th>
                <td>
                    <textarea  name="pys_fb_pixel_server_id" id="pys_fb_pixel_server_id"><?php echo isset($severIds[$term_id]) ? $severIds[$term_id] : ''; ?></textarea>
                    <p class="description"></p>
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top"><label for="pys_fb_pixel_server_test_code">Facebook test_event_code:</label></th>
                <td>
                    <input type="text" name="pys_fb_pixel_server_test_code" id="pys_fb_pixel_server_test_code"
                           value="<?php echo isset($testCodes[$term_id]) ? $testCodes[$term_id] : ''; ?>">
                    <p class="description"></p>
                </td>
            </tr>
            <?php
        } else { // new category view
            ?>
            <div class="form-field">
                <label for="pys_fb_pixel_id">Facebook Category Pixel ID:</label>
                <input type="text" name="pys_fb_pixel_id" id="pys_fb_pixel_id">
                <p class="description"></p>
            </div>

            <div class="form-field">
                <label for="pys_fb_pixel_server_id">Facebook Server Access Token:</label>
                <textarea name="pys_fb_pixel_server_id" id="pys_fb_pixel_server_id"></textarea>
                <p class="description"></p>
            </div>

            <div class="form-field">
                <label for="pys_fb_pixel_server_test_code">Facebook test_event_code:</label>
                <input type="text" name="pys_fb_pixel_server_test_code" id="pys_fb_pixel_server_test_code">
                <p class="description"></p>
            </div>
            <?php
        }
    }

    public function save_product_category_fb_woo_pixel_field($term_id) {
        $id = filter_input(INPUT_POST, 'pys_fb_pixel_id');
        $serverId = filter_input(INPUT_POST, 'pys_fb_pixel_server_id');
        $testCode = filter_input(INPUT_POST, 'pys_fb_pixel_server_test_code');

        // save pixel Id
        $categoryIds = (array)Facebook()->getOption("category_pixel_ids");
        if($id) {
            $categoryIds[$term_id] = $id;
        } else {
            if(isset($categoryIds[$term_id]))
                unset($categoryIds[$term_id]);
        }
        Facebook()->updateOptions(array("category_pixel_ids" => $categoryIds));

        // Save server token
        $categoryServerIds = (array)Facebook()->getOption("category_pixel_server_ids");
        if($serverId) {
            $categoryServerIds[$term_id] = $serverId;
        } else {
            if(isset($categoryServerIds[$term_id]))
                unset($categoryServerIds[$term_id]);
        }
        Facebook()->updateOptions(array("category_pixel_server_ids" => $categoryServerIds));

        //Save server test code
        $categoryServerTestCode = (array)Facebook()->getOption("category_pixel_server_test_code");
        if($testCode) {
            $categoryServerTestCode[$term_id] = $testCode;
        } else {
            if(isset($categoryServerTestCode[$term_id]))
                unset($categoryServerTestCode[$term_id]);
        }
        Facebook()->updateOptions(array("category_pixel_server_test_code" => $categoryServerTestCode));
    }

    public function add_product_category_fb_edd_pixel_field($term) {

        if(!Facebook()->enabled()) return;

        if(is_a($term,"WP_Term") ) { // edit category view

            $term_id = $term->term_id;
            $categoryIds = Facebook()->getOption("edd_category_pixel_ids");
            $severIds = Facebook()->getOption("edd_category_pixel_server_ids");
            $testCodes = Facebook()->getOption("edd_category_pixel_server_test_code");
            ?>
            <tr class="form-field">
                <th scope="row" valign="top"><label for="pys_fb_pixel_id">Facebook Category Pixel ID:</label></th>
                <td>
                    <input type="text" name="pys_fb_pixel_id" id="pys_fb_pixel_id"
                           value="<?php echo isset($categoryIds[$term_id]) ? $categoryIds[$term_id] : ''; ?>">
                    <p class="description"></p>
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top"><label for="pys_fb_pixel_server_id">Facebook Server Access Token:</label></th>
                <td>
                    <textarea  name="pys_fb_pixel_server_id" id="pys_fb_pixel_server_id"><?php echo isset($severIds[$term_id]) ? $severIds[$term_id] : ''; ?></textarea>
                    <p class="description"></p>
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top"><label for="pys_fb_pixel_server_test_code">Facebook test_event_code:</label></th>
                <td>
                    <input type="text" name="pys_fb_pixel_server_test_code" id="pys_fb_pixel_server_test_code"
                           value="<?php echo isset($testCodes[$term_id]) ? $testCodes[$term_id] : ''; ?>">
                    <p class="description"></p>
                </td>
            </tr>
            <?php
        } else { // new category view
            ?>
            <div class="form-field">
                <label for="pys_fb_pixel_id">Facebook Category Pixel ID:</label>
                <input type="text" name="pys_fb_pixel_id" id="pys_fb_pixel_id">
                <p class="description"></p>
            </div>

            <div class="form-field">
                <label for="pys_fb_pixel_server_id">Facebook Server Access Token:</label>
                <textarea name="pys_fb_pixel_server_id" id="pys_fb_pixel_server_id"></textarea>
                <p class="description"></p>
            </div>

            <div class="form-field">
                <label for="pys_fb_pixel_server_test_code">Facebook test_event_code:</label>
                <input type="text" name="pys_fb_pixel_server_test_code" id="pys_fb_pixel_server_test_code">
                <p class="description"></p>
            </div>
            <?php
        }
    }

    public function save_product_category_fb_edd_pixel_field($term_id) {
        $id = filter_input(INPUT_POST, 'pys_fb_pixel_id');
        $serverId = filter_input(INPUT_POST, 'pys_fb_pixel_server_id');
        $testCode = filter_input(INPUT_POST, 'pys_fb_pixel_server_test_code');

        // save pixel Id
        $categoryIds = (array)Facebook()->getOption("edd_category_pixel_ids");
        if($id) {
            $categoryIds[$term_id] = $id;
        } else {
            if(isset($categoryIds[$term_id]))
                unset($categoryIds[$term_id]);
        }
        Facebook()->updateOptions(array("edd_category_pixel_ids" => $categoryIds));

        // Save server token
        $categoryServerIds = (array)Facebook()->getOption("edd_category_pixel_server_ids");
        if($serverId) {
            $categoryServerIds[$term_id] = $serverId;
        } else {
            if(isset($categoryServerIds[$term_id]))
                unset($categoryServerIds[$term_id]);
        }
        Facebook()->updateOptions(array("edd_category_pixel_server_ids" => $categoryServerIds));

        //Save server test code
        $categoryServerTestCode = (array)Facebook()->getOption("edd_category_pixel_server_test_code");
        if($testCode) {
            $categoryServerTestCode[$term_id] = $testCode;
        } else {
            if(isset($categoryServerTestCode[$term_id]))
                unset($categoryServerTestCode[$term_id]);
        }
        Facebook()->updateOptions(array("edd_category_pixel_server_test_code" => $categoryServerTestCode));
    }

    public function adminEnqueueScripts() {

        wp_enqueue_style( 'pys_notice', PYS_URL . '/dist/styles/notice.css', array(), PYS_VERSION );
        if ( in_array( getCurrentAdminPage(), $this->adminPagesSlugs ) ) {

            wp_register_style( 'select2_css', PYS_URL . '/dist/styles/select2.min.css', array(), PYS_VERSION);
            wp_register_script( 'select2_js', PYS_URL . '/dist/scripts/select2.min.js',array( 'jquery' ), PYS_VERSION );

            wp_deregister_script( 'jquery' );
            wp_deregister_script( 'jquery-core' ); // prevent duplicate
            wp_enqueue_script( 'jquery', '//cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js' );

            wp_enqueue_script( 'popper', PYS_URL . '/dist/scripts/popper.min.js', 'jquery' );
            wp_enqueue_script( 'bootstrap', PYS_URL . '/dist/scripts/bootstrap.min.js', 'jquery','popper' );

            wp_enqueue_style( 'pys_css', PYS_URL . '/dist/styles/admin.css', array( 'select2_css' ), PYS_VERSION );
            wp_enqueue_script( 'pys_js', PYS_URL . '/dist/scripts/admin.js', array( 'jquery', 'select2_js', 'popper',
                'bootstrap' ), PYS_VERSION );

            if( isset($_GET['page'])
                && $_GET['page'] == "pixelyoursite"
                && isset($_GET['tab'])
                && ($_GET['tab'] == 'woo' || $_GET['tab'] == 'edd')
            ) {
                wp_enqueue_script( 'jquery-ui-datepicker' );
                wp_enqueue_style( 'pys_calendar', PYS_URL . '/dist/styles/calendar.css', array(  ), PYS_VERSION );
                wp_enqueue_script( 'pys_js_offline_export', PYS_URL . '/dist/scripts/admin_offline_events.js', array( 'pys_js' ), PYS_VERSION );
                wp_enqueue_script( 'pys_js_customer_export', PYS_URL . '/dist/scripts/admin_export_customer.js', array( 'pys_js' ), PYS_VERSION );
            }
            if( (isset($_GET['page']) && $_GET['page'] == "pixelyoursite" && isset($_GET['tab']) && ($_GET['tab'] == 'events')) ||
                ( $_GET['page'] && (( $_GET['page'] == "pixelyoursite_woo_reports" ) || $_GET['page'] == "pixelyoursite_edd_reports" ))
            ) {
                wp_enqueue_style( 'pys_confirm_style', PYS_URL . '/dist/scripts/confirm/jquery-confirm.min.css', array(  ), PYS_VERSION );
                wp_enqueue_style( 'pys_confirm_style_theme', PYS_URL . '/dist/scripts/confirm/bs3.css', array(  ), PYS_VERSION );
                wp_enqueue_script( 'pys_confirm_script', PYS_URL . '/dist/scripts/confirm/jquery-confirm.min.js', array( 'pys_js' ), PYS_VERSION );
                wp_enqueue_script( 'pys_custom_confirm_script', PYS_URL . '/dist/scripts/confirm/custom-confirm.js', array( 'pys_js' ), PYS_VERSION );
            }



        }



    }

    public function adminPageMain() {

        $this->adminResetSettings();
        $this->adminExportCustomAudiences();

        include 'views/html-wrapper-main.php';

    }

    public function adminPageReport() {
        include 'views/html-report.php';
    }

    public function utmTemplate() {
        include 'views/html-utm-templates.php';
    }
    public function settingsTemplate() {
        include 'views/html-main-settings.php';
    }


    public function adminPageLicenses() {

        $this->adminUpdateLicense();

        /** @var Plugin|Settings $plugin */
        foreach ( $this->registeredPlugins as $plugin ) {
            if ( $plugin->getSlug() !== 'head_footer' ) {
                $plugin->adminUpdateLicense();
            }
        }

        include 'views/html-licenses.php';

    }
// Add a new column to the list of orders
    function add_tracking_type_column($columns) {
        $columns['tracking_type'] = 'Tracking type';
        return $columns;
    }


// Fill the new column with data from the order metafield
    function fill_tracking_type($column) {

        if ('tracking_type' === $column) {
            $tracking_type = 'Not tracked';
            if(get_post_meta( get_the_ID(), '_pys_advance_purchase_event_fired', true)){
                $tracking_type = 'Advanced Purchase Tracking (APT)';
            }elseif(get_post_meta( get_the_ID(), '_pys_purchase_event_fired', true)){
                $tracking_type = 'Tag or API';
            }
            echo $tracking_type;
        }


    }

	function fill_tracking_type_hpos($column, $order) {

		if ('tracking_type' === $column) {
			$tracking_type = 'Not tracked';
			if($order->get_meta('_pys_advance_purchase_event_fired', true)){
				$tracking_type = 'Advanced Purchase Tracking (APT)';
			}elseif($order->get_meta('_pys_purchase_event_fired', true)){
				$tracking_type = 'Tag or API';
			}
			echo $tracking_type;
		}


	}

    public function adminProcessRequest() {
        $this->adminCheckLicense();
        $this->adminUpdateCustomEvents();
        $this->adminEnableGdprAjax();
        OfflineEvents();


        if(isset($_GET['page']) && ($_GET['page'] == 'pixelyoursite_woo_reports' || $_GET['page'] == 'pixelyoursite_edd_reports')) {
            if ( isset($_REQUEST['export_csw']) && $_REQUEST['export_csw'] == 'woo_report' ) {
                PysStatistic()->globalWooReport();
                exit;
            }

            if ( isset($_REQUEST['export_csw']) && $_REQUEST['export_csw'] == 'woo_single_report' ) {
                PysStatistic()->singleWooReport();
                exit;
            }
            if ( isset($_REQUEST['export_csw']) && $_REQUEST['export_csw'] == 'edd_report' ) {
                PysStatistic()->globalEddReport();
                exit;
            }
            if ( isset($_REQUEST['export_csw']) && $_REQUEST['export_csw'] == 'edd_single_report' ) {
                PysStatistic()->singleEddReport();
                exit;
            }
        }
        if(PYS()->getOption('woo_enabled_show_tracking_type')){
            if(isWooUseHPStorage()){
                add_filter('woocommerce_shop_order_list_table_columns', array($this,'add_tracking_type_column'));
                add_action('woocommerce_shop_order_list_table_custom_column', array($this,'fill_tracking_type_hpos'), 20, 2 );
            }
            else{
                add_filter('manage_edit-shop_order_columns', array($this,'add_tracking_type_column'));
                add_action('manage_shop_order_posts_custom_column', array($this,'fill_tracking_type'));
            }

        }
    }

    private function adminCheckLicense() {

        $is_dashboard = isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'pixelyoursite';
        $license_status = $this->getOption( 'license_status' );

        // redirect to license page in case if license was never activated
        if ( $is_dashboard && empty( $license_status ) ) {
            wp_safe_redirect( buildAdminUrl( 'pixelyoursite_licenses' ) );
            exit;
        }

    }

    public function adminUpdateLicense() {

        if ( ! $this->adminSecurityCheck() ) {
            return;
        }

        updateLicense( $this );

    }

    public function updatePlugin() {

        foreach ( $this->registeredPlugins as $slug => $plugin ) {

            if ( $slug == 'head_footer' ) {
                continue;
            }

            updatePlugin( $plugin );

        }

        updatePlugin( $this );

    }

    public function adminSecurityCheck() {

        // verify user access
        if ( ! current_user_can( 'manage_pys' ) ) {
            return false;
        }

        // nonce filed are required request
        if ( ! isset( $_REQUEST['_wpnonce'] ) || !$_REQUEST['_wpnonce'] ) {
            return false;
        }

        return true;

    }

    private function adminEnableGdprAjax() {

        if ( ! $this->adminSecurityCheck() ) {
            return;
        }

        if ( isset( $_REQUEST['pys']['enable_gdpr_ajax'] ) ) {
            $this->updateOptions( array(
                'gdpr_ajax_enabled' => true,
                'gdpr_cookie_law_info_integration_enabled' => true,
                'consent_magic_integration_enabled' => true,
            ) );

            add_action( 'admin_notices', 'PixelYourSite\adminGdprAjaxEnabledNotice' );
            purgeCache();
        }

    }

    private function adminUpdateCustomEvents() {

        if ( ! $this->adminSecurityCheck() ) {
            return;
        }

        /**
         * Single Custom Event Actions
         */
        if ( isset( $_REQUEST['pys']['event'] ) && isset( $_REQUEST['action']) && is_array($_REQUEST['pys']['event'])  ) {

            $nonce   = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : null;
            $action  = $_REQUEST['action'];
            if(isset( $_REQUEST['pys']['event']['post_id'] )) {
                $post_id = sanitize_key( $_REQUEST['pys']['event']['post_id']) ;
            } else {
                $post_id =  false;
            }


            if ( $action == 'update' && wp_verify_nonce( $nonce, 'pys_update_event' ) ) {

                $pys_event = $_REQUEST['pys']['event'];

                if ( $post_id ) {
                    $event = CustomEventFactory::getById( $post_id );
                    $event->update( $pys_event );
                } else {
                    $event = CustomEventFactory::create( $pys_event );
                }

                purgeCache();

                // redirect to events tab
                wp_safe_redirect( buildAdminUrl( 'pixelyoursite', 'events', 'edit', array(
                    'id' => $event->getPostId()
                ) ));
                exit;

            } elseif ( $action == 'enable' && $post_id && wp_verify_nonce( $nonce, 'pys_enable_event' ) ) {

                $event = CustomEventFactory::getById( $post_id );
                $event->enable();

            } elseif ( $action == 'disable' && $post_id && wp_verify_nonce( $nonce, 'pys_disable_event' ) ) {

                $event = CustomEventFactory::getById( $post_id );
                $event->disable();

            } elseif ( $action == 'remove' && $post_id && wp_verify_nonce( $nonce, 'pys_remove_event' ) ) {

                CustomEventFactory::remove( $post_id );

            }

            purgeCache();

            // redirect to events tab
            wp_safe_redirect( buildAdminUrl( 'pixelyoursite', 'events' ) );
            exit;

        }

        /**
         * Bulk Custom Events Actions
         */
        if ( isset( $_REQUEST['pys']['bulk_event_action'], $_REQUEST['pys']['selected_events'] )
            && isset( $_REQUEST['pys']['bulk_event_action_nonce'] )
            && wp_verify_nonce( $_REQUEST['pys']['bulk_event_action_nonce'], 'bulk_event_action' )
            && is_array( $_REQUEST['pys']['selected_events'] ) ) {

            foreach ( $_REQUEST['pys']['selected_events'] as $event_id ) {

                $event_id = (int) $event_id;

                switch ( $_REQUEST['pys']['bulk_event_action'] ) {
                    case 'enable':
                        $event = CustomEventFactory::getById( $event_id );
                        $event->enable();
                        break;

                    case 'disable':
                        $event = CustomEventFactory::getById( $event_id );
                        $event->disable();
                        break;

                    case 'clone':
                        CustomEventFactory::makeClone( $event_id );
                        break;

                    case 'delete':
                        CustomEventFactory::remove( $event_id );
                        break;
                }

            }

            purgeCache();

            // redirect to events tab
            wp_safe_redirect( buildAdminUrl( 'pixelyoursite', 'events' ) );
            exit;

        }

    }

    /**
     * Show row meta on the plugin screen.
     *
     * @param mixed $links Plugin Row Meta.
     * @param mixed $file  Plugin Base file.
     *
     * @return array
     */
    public function pluginRowMeta( $links, $file ) {

        if ( PYS_PRO_PLUGIN_BASENAME === $file ) {
            $links[] = '<a href="https://www.pixelyoursite.com/documentation">Help</a>';
        }

        return (array) $links;

    }

    private function adminSaveSettings() {

        if ( ! $this->adminSecurityCheck() ) {
            return;
        }

        if ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'pys_save_settings' ) ) {

            if(isset( $_POST['pys']['core'] ) && is_array($_POST['pys']['core'])) {
                $core_options =  $_POST['pys']['core'];
            } else {
                $core_options =  array();
            }


            $gdpr_ajax_enabled = isset( $core_options['gdpr_ajax_enabled'] )
                ? $core_options['gdpr_ajax_enabled']        // value from form data
                : $this->getOption('gdpr_ajax_enabled');    // previous value

            // allow 3rd party plugins to by-pass option value
            $core_options['gdpr_ajax_enabled'] = apply_filters( 'pys_gdpr_ajax_enabled', $gdpr_ajax_enabled );

            if (isPixelCogActive() ) {
                if (isset($core_options['woo_purchase_value_option'])) {
                    $core_options = $this->updateDefaultNoCogOption($core_options,'woo_purchase_value_option','woo_purchase_value_cog');
                }
                if (isset($core_options['woo_view_content_value_option'])) {
                    $core_options = $this->updateDefaultNoCogOption($core_options,'woo_view_content_value_option','woo_content_value_cog');
                }
                if (isset($core_options['woo_add_to_cart_value_option'])) {
                    $core_options = $this->updateDefaultNoCogOption($core_options,'woo_add_to_cart_value_option','woo_add_to_cart_value_cog');
                }
                if (isset($core_options['woo_initiate_checkout_value_option'])) {
                    $core_options = $this->updateDefaultNoCogOption($core_options,'woo_initiate_checkout_value_option','woo_initiate_checkout_value_cog');
                }
            }

            // update core options
            $this->updateOptions( $core_options );


            $objects = array_merge( $this->registeredPixels, $this->registeredPlugins );

            // update plugins and pixels options
            foreach ( $objects as $obj ) {
                /** @var Plugin|Pixel|Settings $obj */
                $obj->updateOptions();
            }
            GATags()->updateOptions();
            purgeCache();

        }

    }

    private function updateDefaultNoCogOption($core_options,$optionName,$defaultOptionName) {
        $val = $core_options[$optionName];
        $currentVal = $this->getOption($optionName);
        if($val != 'cog') {
            $core_options[$defaultOptionName] = $val;
        } elseif ( $currentVal != 'cog' ) {
            $core_options[$defaultOptionName] = $currentVal;
        }
        return $core_options;
    }

    private function adminResetSettings() {

        if ( ! $this->adminSecurityCheck() ) {
            return;
        }

        if ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'pys_save_settings' ) && isset( $_REQUEST['pys']['reset_settings'] ) ) {

            if ( isSuperPackActive() ) {

                $old_options = array(
                    'license_key'     => SuperPack()->getOption( 'license_key' ),
                    'license_status'  => SuperPack()->getOption( 'license_status' ),
                    'license_expires' => SuperPack()->getOption( 'license_expires' ),
                );

                SuperPack()->resetToDefaults();
                SuperPack()->updateOptions( $old_options );

            }

            if ( isPinterestActive() ) {

                $old_options = array(
                    'license_key'     => Pinterest()->getOption( 'license_key' ),
                    'license_status'  => Pinterest()->getOption( 'license_status' ),
                    'license_expires' => Pinterest()->getOption( 'license_expires' ),
                    'pixel_id'        => Pinterest()->getPixelIDs(),
                );

                Pinterest()->resetToDefaults();
                Pinterest()->updateOptions( $old_options );

            }

            // Core
            $old_options = array(
                'license_key'     => $this->getOption( 'license_key' ),
                'license_status'  => $this->getOption( 'license_status' ),
                'license_expires' => $this->getOption( 'license_expires' ),
            );

            PYS()->resetToDefaults();
            PYS()->updateOptions( $old_options );

            // Facebook
            $old_options = array(
                'pixel_id' => Facebook()->getPixelIDs(),
            );

            Facebook()->resetToDefaults();
            Facebook()->updateOptions( $old_options );

            // Google Analytics
            $old_options = array(
                'tracking_id' => GA()->getPixelIDs(),
            );

            GA()->resetToDefaults();
            GA()->updateOptions( $old_options );

            // Google Analytics
            $old_options = array(
                'ads_ids' => Ads()->getPixelIDs(),
                'woo_purchase_conversion_labels' => Ads()->getOption( 'woo_purchase_conversion_labels' ),
                'woo_initiate_checkout_conversion_labels' => Ads()->getOption( 'woo_initiate_checkout_conversion_labels' ),
                'woo_add_to_cart_conversion_labels' => Ads()->getOption( 'woo_add_to_cart_conversion_labels' ),
                'woo_view_content_conversion_labels' => Ads()->getOption( 'woo_view_content_conversion_labels' ),
                'woo_view_category_conversion_labels' => Ads()->getOption( 'woo_view_category_conversion_labels' ),
                'edd_purchase_conversion_labels' => Ads()->getOption( 'edd_purchase_conversion_labels' ),
                'edd_initiate_checkout_conversion_labels' => Ads()->getOption( 'edd_initiate_checkout_conversion_labels' ),
                'edd_add_to_cart_conversion_labels' => Ads()->getOption( 'edd_add_to_cart_conversion_labels' ),
                'edd_view_content_conversion_labels' => Ads()->getOption( 'edd_view_content_conversion_labels' ),
                'edd_view_category_conversion_labels' => Ads()->getOption( 'edd_view_category_conversion_labels' ),
            );
            Ads()->resetToDefaults();
            Ads()->updateOptions( $old_options );

            //HeadFooter()->resetToDefaults();

            // do redirect
            wp_safe_redirect( buildAdminUrl( 'pixelyoursite' ) );
            exit;

        }

    }

    private function adminExportCustomAudiences() {

        if ( ! $this->adminSecurityCheck() ) {
            return;
        }

        if ( isset( $_REQUEST['pys']['export_custom_audiences'] )
            && wp_verify_nonce( $_REQUEST['_wpnonce'], 'pys_save_settings' ) ) {

            if ( $_REQUEST['pys']['export_custom_audiences'] == 'woo' && isWooCommerceActive() ) {
                wooExportCustomAudiences();
            } elseif ( $_REQUEST['pys']['export_custom_audiences'] == 'edd' ) {
                eddExportCustomAudiences();
            }

        }

    }

    public function restoreSettingsAfterCog() {
        $old = Facebook()->getOption("woo_complete_registration_custom_value_old");
        if(!empty($old) ) {
            Facebook()->updateOptions(array(
                "woo_complete_registration_custom_value" => $old,
                'woo_complete_registration_custom_value_old' => ""));
        }
        $params = array();
        $oldPurchase = $this->getOption("woo_purchase_value_cog");
        $oldContent = $this->getOption("woo_content_value_cog");
        $oldAddCart = $this->getOption("woo_add_to_cart_value_cog");
        $oldInitCheckout = $this->getOption("woo_initiate_checkout_value_cog");

        if($this->getOption('woo_purchase_value_option') == 'cog') {
            if(!empty($oldPurchase)) $params['woo_purchase_value_option'] = $oldPurchase;
            else $params['woo_purchase_value_option'] = "price";
        }
        if($this->getOption('woo_view_content_value_option') == 'cog') {
            if(!empty($oldContent)) $params['woo_view_content_value_option'] = $oldContent;
            else $params['woo_view_content_value_option'] = "price";
        }
        if($this->getOption('woo_add_to_cart_value_option') == 'cog') {
            if(!empty($oldAddCart)) $params['woo_add_to_cart_value_option'] = $oldAddCart;
            else $params['woo_add_to_cart_value_option'] = "price";
        }
        if($this->getOption('woo_initiate_checkout_value_option') == 'cog') {
            if(!empty($oldInitCheckout)) $params['woo_initiate_checkout_value_option'] = $oldInitCheckout;
            else $params['woo_initiate_checkout_value_option'] = "price";
        }

        $params['woo_purchase_value_cog'] = '';
        $params['woo_content_value_cog'] = '';
        $params['woo_add_to_cart_value_cog'] = '';
        $params['woo_initiate_checkout_value_cog'] = '';

        $this->updateOptions($params);
    }

    public function getLog() {
        return $this->logger;
    }

    function edd_completed_purchase( $payment_id) {

        if(!PYS()->getOption("edd_advance_purchase_fb_enabled")
            && !PYS()->getOption("edd_advance_purchase_ga_enabled")
			&& ( !Tiktok()->enabled() || ( Tiktok()->enabled() && !Tiktok()->getOption( 'edd_advance_purchase_enabled' ) ) )
			&& ( !Pinterest()->enabled() || ( Pinterest()->enabled() && !Pinterest()->getOption( 'edd_advance_purchase_enabled' ) ) )
        ) {
            return;
        }
        if(
            !PYS()->getOption("edd_enabled_purchase_recurring")
            || edd_get_payment_meta( $payment_id, '_pys_purchase_event_fired', true )
            || !PYS()->getOption( 'edd_purchase_enabled' ) || !is_admin()) {
            return;
        }
        $userId = edd_get_payment_user_id($payment_id);
        $user = get_user_by('id', $userId);
        if(isDisabledForUserRole($user)) {
            return;
        }

        add_filter("pys_edd_checkout_order_id",function () use ($payment_id) {return $payment_id;});
        $event = EventsEdd()->getEvent('edd_purchase', true);
        if ( $event == null ) {
            return;
        }
        if(PYS()->getOption("edd_advance_purchase_fb_enabled") ) {//send fb server events
            $fbEvents = Facebook()->generateEvents($event);
            FacebookServer()->sendEventsNow($fbEvents);
        }
        if(PYS()->getOption("edd_advance_purchase_ga_enabled") ) { // send GA
            $gaEvents = GA()->generateEvents($event);

            (new GaMeasurementProtocolAPI())->sendEventsNow($gaEvents);
        }
		if( Tiktok()->enabled()
            && Tiktok()->isServerApiEnabled()
            && Tiktok()->getOption( "edd_advance_purchase_enabled" )
        ) {// send Tiktok Completed Payment event
			Tiktok()->getLog()->debug( 'Send Completed Payment Tiktok' );
			$tiktokEvents = Tiktok()->generateEvents( $event );
			TikTokServer()->sendEventsNow( $tiktokEvents );
		}
		if( Pinterest()->enabled()
            && method_exists( Pinterest(), 'isServerApiEnabled' )
            && Pinterest()->isServerApiEnabled()
            && Pinterest()->getOption( "edd_advance_purchase_enabled" )
        ) {// send Pinterest Checkout
			Pinterest()->getLog()->debug( 'Send Checkout Pinterest' );
			$pinterestEvents = Pinterest()->generateEvents( $event );
			PinterestServer()->sendEventsNow( $pinterestEvents );
		}
    }
    function edd_refund_order($payment_id, $refund_id, $all_refunded) {
        $log = $this->logger;
        if(!PYS()->getOption("edd_track_refunds_GA") && !edd_get_payment_meta( $payment_id, '_pys_purchase_event_fired', true )) {
            return;
        }
        if(!$payment_id) {
            return;
        }
        $userId = edd_get_payment_user_id($payment_id);
        $user = get_user_by('id', $userId);
        if(isDisabledForUserRole($user)) {
            return;
        }


	    edd_update_payment_meta( $payment_id, '_pys_purchase_event_fired', false );
        add_filter("pys_edd_checkout_order_id",function () use ($payment_id) {return $payment_id;});
        $event = EventsEdd()->getEvent('edd_refund');
        if ( $event == null ) {
            return;
        }
        if(PYS()->getOption("edd_track_refunds_GA") ) {// send GA

            $gaEvents = GA()->generateEvents($event);
            (new GaMeasurementProtocolAPI())->sendEventsNow($gaEvents);
            $log->debug("Send completed refund GA");
        }
    }
    function edd_recurring_payment( $payment_id, $parent_payment_id, $amount, $transaction_id) {

		EnrichOrder()->edd_save_subscription_meta( $payment_id );

        if(!PYS()->getOption("edd_advance_purchase_fb_enabled")
            && !PYS()->getOption("edd_advance_purchase_ga_enabled")) {
            return;
        }
        if(
            !PYS()->getOption("edd_enabled_purchase_recurring")
            || edd_get_payment_meta( $payment_id, '_pys_purchase_event_fired', true )
            || !PYS()->getOption( 'edd_purchase_enabled' )) {
            return;
        }
        PYS()->getLog()->debug("Purchase recurring Edd", $payment_id);
        $userId = edd_get_payment_user_id($payment_id);
        $user = get_user_by('id', $userId);
        if(isDisabledForUserRole($user)) {
            return;
        }

        add_filter("pys_edd_checkout_order_id",function () use ($payment_id) {return $payment_id;});
        $event = EventsEdd()->getEvent('edd_purchase');
        if ( $event == null ) {
            return;
        }
        if(PYS()->getOption("edd_advance_purchase_fb_enabled") ) {//send fb server events
            $fbEvents = Facebook()->generateEvents($event);
            FacebookServer()->sendEventsNow($fbEvents);
        }
        if(PYS()->getOption("edd_advance_purchase_ga_enabled") ) { // send GA
            $gaEvents = GA()->generateEvents($event);
            (new GaMeasurementProtocolAPI())->sendEventsNow($gaEvents);
        }
    }
    /**
     * Tracks a completed purchase
     *
     * @param int $order_id the order ID
     */
    function woo_completed_purchase($order_id) {
        $log = $this->logger;
        $log->debug("Send woo_completed_purchase");
        if(!PYS()->getOption("woo_advance_purchase_fb_enabled")
            && !PYS()->getOption("woo_advance_purchase_ga_enabled")
            && ( !Tiktok()->enabled() || ( Tiktok()->enabled() && !Tiktok()->getOption( 'woo_advance_purchase_enabled' ) ) )
			&& ( !Pinterest()->enabled() || ( Pinterest()->enabled() && !Pinterest()->getOption( 'woo_advance_purchase_enabled' ) ) )
        ) {
            return;
        }
        $order = wc_get_order($order_id);
        if(!$order
            || !PYS()->getOption( 'woo_purchase_enabled' )
            || $order->get_meta( '_pys_purchase_event_fired', true )
            || isset($_REQUEST['wc-ajax']) // skip woo ajax request
        ) {
            return;
        }
        $order = wc_get_order($order_id);
        if(isDisabledForUserRole($order->get_user())) {
            return;
        }
        add_filter("pys_woo_checkout_order_id",function () use ($order_id) {return $order_id;});
        $event = EventsWoo()->getEvent('woo_purchase');
        if ( $event == null ) {
            return;
        }

        $event->addParams(array('advanced_purchase_tracking' => true));

        if(PYS()->getOption("woo_advance_purchase_fb_enabled") ) {//send fb server events
            $log->debug("Send  FB");
            $fbEvents = Facebook()->generateEvents($event);
            FacebookServer()->sendEventsNow($fbEvents);
        }
	    if(PYS()->getOption("woo_advance_purchase_ga_enabled") ) {// send GA
            GA()->getLog()->debug("Send completed purchase GA");
            $gaEvents = GA()->generateEvents($event);
            (new GaMeasurementProtocolAPI())->sendEventsNow($gaEvents);
        }
		if( Tiktok()->enabled()
            && Tiktok()->isServerApiEnabled()
            && Tiktok()->getOption( "woo_advance_purchase_enabled" )
        ) {// send Tiktok Completed Payment event
			Tiktok()->getLog()->debug( 'Send Completed Payment Tiktok' );
			$tiktokEvents = Tiktok()->generateEvents( $event );
			TikTokServer()->sendEventsNow( $tiktokEvents );
		}
		if( Pinterest()->enabled()
            && method_exists( Pinterest(), 'isServerApiEnabled' )
            && Pinterest()->isServerApiEnabled()
            && Pinterest()->getOption( "woo_advance_purchase_enabled" )
        ) {// send Pinterest Checkout
			Pinterest()->getLog()->debug( 'Send Checkout Pinterest' );
			$pinterestEvents = Pinterest()->generateEvents( $event );
			PinterestServer()->sendEventsNow( $pinterestEvents );
		}
        if ( isWooCommerceVersionGte('3.0.0') ) {
            // WooCommerce >= 3.0
            if($order) {
                $order->update_meta_data( '_pys_advance_purchase_event_fired', true );
                if(!$order->get_meta( 'traking_type', true ))
                {
                    $order->update_meta_data( 'traking_type', 'Advanced Purchase Tracking (APT)' );
                }
                $order->save();
            }

        } else {
            // WooCommerce < 3.0
            update_post_meta( $order_id, '_pys_advance_purchase_event_fired', true );
            if (!get_post_meta($order_id, "traking_type", true)) {
                update_post_meta( $order_id, 'traking_type', 'Advanced Purchase Tracking (APT)' );
            }
        }

    }
    /**
     * Tracks a refund purchase
     *
     * @param int $order_id the order ID
     */
    function woo_refund_order($order_id) {
        $log = $this->logger;
        $log->debug("Send woo_refund_order");
        if(!PYS()->getOption("woo_track_refunds_GA")) {
            return;
        }
        $order = wc_get_order($order_id);
        if(!$order) {
            return;
        }
        if(isDisabledForUserRole($order->get_user())) {
            return;
        }



        add_filter("pys_woo_checkout_order_id",function () use ($order_id) {return $order_id;});
        $event = EventsWoo()->getEvent('woo_refund');
        if ( $event == null ) {
            return;
        }
        if(PYS()->getOption("woo_track_refunds_GA") ) {// send GA

            $gaEvents = GA()->generateEvents($event);
            (new GaMeasurementProtocolAPI())->sendEventsNow($gaEvents);
            $log->debug("Send completed refund GA");
        }
        if ( isWooCommerceVersionGte('3.0.0') ) {
            // WooCommerce >= 3.0
            if($order) {
                $order->update_meta_data( '_pys_advance_purchase_event_fired', true );
                $order->save();
            }

        } else {
            // WooCommerce < 3.0
            update_post_meta( $order_id, '_pys_advance_purchase_event_fired', true );
        }
    }
    function woo_is_order_received_page() {
        if(is_order_received_page()) return true;
        global $post;
        $ids = PYS()->getOption("woo_checkout_page_ids");
        if(!empty($ids)) {
            if($post && in_array($post->ID,$ids)) {
                return true;
            }
        }
        if (did_action( 'elementor/loaded' )) {
            if ($post) {
                $elementor_page_id = get_option('elementor_woocommerce_purchase_summary_page_id');
                if ($elementor_page_id == $post->ID) return true;
            }
        }

        if(is_wc_endpoint_url( 'order-received')){
            return true;
        }

        return false;
    }

    public function isCachePreload() {
        if(!empty($_SERVER['HTTP_USER_AGENT'])){
	        $options = array('WP Rocket/Preload', 'WP-Rocket-Preload', 'WP Rocket/Homepage_Preload', 'lscache_runner', 'LiteSpeed Cache', 'LiteSpeed-Cache');
	        foreach($options as $row) {
		        if (stripos(strtolower($_SERVER['HTTP_USER_AGENT']), strtolower($row)) !== false) {
			        return true;
		        }
	        }
        }
        return false;
    }
	public function getCurrentUrl() {
		$protocol = ( ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$domainName = $_SERVER['HTTP_HOST'] ?? '';
		$requestUri = $_SERVER['REQUEST_URI'];
		return $protocol . $domainName . $requestUri;
	}
}

/**
 * @return PYS
 */
function PYS() {
    return PYS::instance();
}
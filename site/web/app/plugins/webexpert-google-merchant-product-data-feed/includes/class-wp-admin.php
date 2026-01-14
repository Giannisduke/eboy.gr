<?php
class WEGoogleMerchantProductDataFeed_Admin
{

	private $version;
	private $plugin_slug;

	public function __construct($version,$plugin_slug)
	{
		$this->version = $version;
		$this->plugin_slug = $plugin_slug;
	}

	function we_google_merchant_product_data_feed_load_textdomain() {
		load_plugin_textdomain( $this->plugin_slug, false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}

	function we_google_merchant_product_data_feed_enqueue_styles($hook) {
		if( ! wp_style_is( 'select2', 'registered' ) ) {
			wp_register_style( 'select2', WC()->plugin_url() . '/assets/css/select2.css', null, $this->version );
		}
		wp_enqueue_style( 'select2' );
		wp_register_style($this->plugin_slug, plugins_url('/templates/css/style.min.css', __FILE__),null,$this->version);
		wp_enqueue_style($this->plugin_slug);
	}

	function we_google_merchant_product_data_feed_enqueue_scripts($hook) {
		if( ! wp_script_is( 'select2', 'registered' ) ) {
			wp_register_script( 'select2', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', array( 'jquery' ), $this->version );
		}
		wp_enqueue_script('select2');
		wp_register_script($this->plugin_slug, plugins_url('/templates/js/custom.js', __FILE__),['select2'],$this->version);
		wp_enqueue_script($this->plugin_slug);
	}

	public function we_google_merchant_product_data_feed_create_menu()
	{
        $logo = file_get_contents(plugin_dir_path(__DIR__).'assets/webexpert-icon.svg');
		if ( empty ( $GLOBALS['admin_page_hooks']['webexpert_plugins'] ) )
			add_menu_page('Web Expert Plugins','Web Expert','manage_woocommerce','webexpert_plugins',array($this, 'webexpert_plugins'),'data:image/svg+xml;base64,' . base64_encode($logo),20);
		add_submenu_page('webexpert_plugins',__( 'Google Merchant Product Data Feed', $this->plugin_slug ), __( 'Google Merchant Product Data Feed', $this->plugin_slug ),'manage_woocommerce',$this->plugin_slug,array($this, 'we_google_merchant_product_data_feed_client_main'));
		remove_submenu_page('webexpert_plugins', 'webexpert_plugins');
	}

	public function webexpert_plugins() {
		if (!current_user_can('manage_woocommerce')) {
			return;
		}
		?>
        <div class="wrap">
            <h1><?= esc_html(get_admin_page_title()); ?></h1>
            <p>Ευχαριστούμε που επιλέγε την Web Expert.</p>
        </div>
		<?php
	}

	public function we_google_merchant_product_data_feed_client_main()
	{
		include "templates/admin.index.php";
	}

	public function we_register_settings() {
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_lastrun' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_email' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_license_key' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_categories_not_to_list' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_tags_not_to_list' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_attributes_not_to_list' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_cron_schedule' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_custom_mpn' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_custom_id' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_custom_gtin' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_max_page_size' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_shipping_lead_time' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_desc_field' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_brand' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_colour' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_size' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_custom_weight' );
		register_setting( 'webexpert-google-merchant-product-data-feed-settings-group', 'we_google_merchant_product_data_feed_hide_out_of_stock' );
	}

	public function we_save_settings() {
		$we_google_merchant_product_data_feed_brand= is_array($_POST['we_google_merchant_product_data_feed_brand']) ? array_map( 'sanitize_text_field', $_POST['we_google_merchant_product_data_feed_brand']) : sanitize_text_field($_POST['we_google_merchant_product_data_feed_brand']);
		$we_google_merchant_product_data_feed_colour= is_array($_POST['we_google_merchant_product_data_feed_colour']) ? array_map( 'sanitize_text_field', $_POST['we_google_merchant_product_data_feed_colour']) : sanitize_text_field($_POST['we_google_merchant_product_data_feed_colour']);
		$we_google_merchant_product_data_feed_size= is_array($_POST['we_google_merchant_product_data_feed_size']) ? array_map( 'sanitize_text_field', $_POST['we_google_merchant_product_data_feed_size']) : sanitize_text_field($_POST['we_google_merchant_product_data_feed_size']);
        $we_google_merchant_product_data_feed_custom_weight=$_POST['we_google_merchant_product_data_feed_custom_weight'];

		$we_google_merchant_product_data_feed_email= sanitize_text_field($_POST['we_google_merchant_product_data_feed_email']);
		$we_google_merchant_product_data_feed_license_key= sanitize_text_field($_POST['we_google_merchant_product_data_feed_license_key']);
		$we_google_merchant_product_data_feed_categories_not_to_list=$_POST['we_google_merchant_product_data_feed_categories_not_to_list'];
		$we_google_merchant_product_data_feed_tags_not_to_list=$_POST['we_google_merchant_product_data_feed_tags_not_to_list'];
		$we_google_merchant_product_data_feed_attributes_not_to_list=$_POST['we_google_merchant_product_data_feed_attributes_not_to_list'];
		$we_google_merchant_product_data_feed_categories_not_to_list_invert=$_POST['we_google_merchant_product_data_feed_categories_not_to_list_invert'];
		$we_google_merchant_product_data_feed_tags_not_to_list_invert=$_POST['we_google_merchant_product_data_feed_tags_not_to_list_invert'];
		$we_google_merchant_product_data_feed_attributes_not_to_list_invert=$_POST['we_google_merchant_product_data_feed_attributes_not_to_list_invert'];
		$we_google_merchant_product_data_feed_cron_schedule=$_POST['we_google_merchant_product_data_feed_cron_schedule'];
		$we_google_merchant_product_data_feed_custom_id=$_POST['we_google_merchant_product_data_feed_custom_id'];
		$we_google_merchant_product_data_feed_custom_sku=$_POST['we_google_merchant_product_data_feed_custom_sku'];
		$we_google_merchant_product_data_feed_custom_gtin=$_POST['we_google_merchant_product_data_feed_custom_gtin'];
		$we_google_merchant_product_data_feed_custom_mpn=$_POST['we_google_merchant_product_data_feed_custom_mpn'];
		$we_google_merchant_product_data_feed_max_page_size=$_POST['we_google_merchant_product_data_feed_max_page_size'];
        $we_google_merchant_product_data_feed_hide_out_of_stock = $_POST['we_google_merchant_product_data_feed_hide_out_of_stock'];
		$we_google_merchant_product_data_feed_desc_field=$_POST['we_google_merchant_product_data_feed_desc_field'];
		$we_google_merchant_product_data_feed_default_category=$_POST['we_google_merchant_product_data_feed_default_category'];

		update_option('we_google_merchant_product_data_feed_brand',$we_google_merchant_product_data_feed_brand ?? []);
		update_option('we_google_merchant_product_data_feed_colour',$we_google_merchant_product_data_feed_colour ?? []);
		update_option('we_google_merchant_product_data_feed_size',$we_google_merchant_product_data_feed_size ?? []);
		update_option('we_google_merchant_product_data_feed_desc_field',$we_google_merchant_product_data_feed_desc_field);
		update_option('we_google_merchant_product_data_feed_custom_weight',$we_google_merchant_product_data_feed_custom_weight);
		update_option('we_google_merchant_product_data_feed_hide_out_of_stock',$we_google_merchant_product_data_feed_hide_out_of_stock);
		update_option('we_google_merchant_product_data_feed_cron_schedule',$we_google_merchant_product_data_feed_cron_schedule);
		update_option('we_google_merchant_product_data_feed_categories_not_to_list',$we_google_merchant_product_data_feed_categories_not_to_list);
		update_option('we_google_merchant_product_data_feed_tags_not_to_list',$we_google_merchant_product_data_feed_tags_not_to_list);
		update_option('we_google_merchant_product_data_feed_attributes_not_to_list',$we_google_merchant_product_data_feed_attributes_not_to_list);
		update_option('we_google_merchant_product_data_feed_categories_not_to_list_invert',$we_google_merchant_product_data_feed_categories_not_to_list_invert);
		update_option('we_google_merchant_product_data_feed_tags_not_to_list_invert',$we_google_merchant_product_data_feed_tags_not_to_list_invert);
		update_option('we_google_merchant_product_data_feed_attributes_not_to_list_invert',$we_google_merchant_product_data_feed_attributes_not_to_list_invert);
		update_option('we_google_merchant_product_data_feed_default_category',$we_google_merchant_product_data_feed_default_category);
		update_option('we_google_merchant_product_data_feed_email',$we_google_merchant_product_data_feed_email);
		update_option('we_google_merchant_product_data_feed_license_key',$we_google_merchant_product_data_feed_license_key);
		update_option('we_google_merchant_product_data_feed_custom_sku',$we_google_merchant_product_data_feed_custom_sku);
		update_option('we_google_merchant_product_data_feed_custom_id',$we_google_merchant_product_data_feed_custom_id);
		update_option('we_google_merchant_product_data_feed_custom_gtin',$we_google_merchant_product_data_feed_custom_gtin);
		update_option('we_google_merchant_product_data_feed_custom_mpn',$we_google_merchant_product_data_feed_custom_mpn);
		update_option('we_google_merchant_product_data_feed_max_page_size',$we_google_merchant_product_data_feed_max_page_size);

		if (get_option('we_google_merchant_product_data_feed_cron_schedule')!=$we_google_merchant_product_data_feed_cron_schedule) {
			wp_clear_scheduled_hook('we_daily_google_merchant_xml');
			if (! wp_next_scheduled ( 'we_daily_google_merchant_xml' && $we_google_merchant_product_data_feed_cron_schedule!="disabled")) {
				wp_schedule_event(time(), $we_google_merchant_product_data_feed_cron_schedule, 'we_daily_google_merchant_xml');
			}
		}
		update_option('we_google_merchant_product_data_feed_cron_schedule',$we_google_merchant_product_data_feed_cron_schedule);
		set_transient('success', 'Οι αλλαγές αποθηκεύτηκαν.');
		wp_redirect(admin_url('admin.php?page=webexpert-google-merchant-product-data-feed'));
		die();
	}

	function delete_wc_add_disable_grouping_field($id) {
		delete_option( "we_google_merchant_product_data_feed_disable_grouping-$id" );
	}

	function save_wc_add_disable_grouping_field( $id ) {
		if ( is_admin() && isset( $_POST['we_google_merchant_product_data_feed_disable_grouping'] ) ) {
			$option = "we_google_merchant_product_data_feed_disable_grouping-$id";
			update_option( $option, sanitize_text_field( $_POST['we_google_merchant_product_data_feed_disable_grouping'] ) );
		}else {
			delete_option("we_google_merchant_product_data_feed_disable_grouping-$id");
		}
	}

	function init_webexpert_google_merchant_license_check() {
		if (get_option('init_webexpert_google_merchant_license_check',false)==false) {
			$url='https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=webexpert-google-merchant-product-data-feed&license_key='.get_option('we_google_merchant_product_data_feed_license_key').'&domain='.get_bloginfo('url');
			$request = wp_remote_get($url);
			$response = wp_remote_retrieve_body( $request );
			$s = json_decode($response);
			if (isset($s->download_url)) {
				update_option('we_google_merchant_product_data_feed_valid_license',true);
			}else {
				delete_option('we_google_merchant_product_data_feed_valid_license');
			}
			update_option('init_webexpert_google_merchant_license_check',true);
		}
	}

	function webexpert_google_merchant_callback_update($old_value, $new_value) {
		$url='https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=webexpert-google-merchant-product-data-feed&&license_key='.$new_value.'&domain='.get_bloginfo('url');
		$request = wp_remote_get($url);
		$response = wp_remote_retrieve_body( $request );
		$s = json_decode($response);
		if (isset($s->download_url)) {
			update_option('we_google_merchant_product_data_feed_valid_license',true);
		}else {
			delete_option('we_google_merchant_product_data_feed_valid_license');
		}
	}

	function webexpert_google_merchant_license_admin_notices() {
		if (empty(get_option('we_google_merchant_product_data_feed_license_key')) || empty(get_option('we_google_merchant_product_data_feed_email'))) {
			?>
            <div class="notice notice-error">
                <p><?php _e('Please activate <strong>Web Expert WooCommerce Google Merchant Product Data Feed</strong> to enable all it\'s features and automatic updates.', 'webexpert-google-merchant-product-data-feed'); ?></p>
            </div>
			<?php
		}
		if (get_option('we_google_merchant_product_data_feed_valid_license',false)===false) {
			?>
            <div class="notice notice-error">
                <p><?php _e('The license for <strong>Web Expert WooCommerce Google Merchant Product Data Feed</strong> is invalid. Please fill in a valid license key.', 'webexpert-google-merchant-product-data-feed'); ?></p>
            </div>
			<?php
		}
	}

	function google_merchant_plugin_action_links($links, $file)
	{
		static $this_plugin;
		if (!$this_plugin) {
			$this_plugin = ( dirname(plugin_basename(__FILE__), 2) . '/' . $this->plugin_slug . '.php' );
		}
		if ($file == $this_plugin) {
			$settings_link = '<a href="' . admin_url("admin.php?page=webexpert-google-merchant-product-data-feed").'">'.__('Settings').'</a>';
			$support_link = '<a target="_blank" href="https://support.webexpert.gr">'.__('Support').'</a>';
			array_unshift($links, $settings_link, $support_link);
		}
		return $links;
	}

	function we_run_xml(){
		include "scripts/google-merchant-engine.php";
	}

	function we_google_merchant_product_data_feed_lastrun() {
		if (get_option('we_google_merchant_product_data_feed_lastrun')!="") {
			return get_option('we_google_merchant_product_data_feed_lastrun');
		}else {
			return __("Never",$this->plugin_slug);
		}
	}

	function category_mapping_taxonomy_add_new_meta_field() {
		$file=file_get_contents(plugin_dir_path( __DIR__ )."includes/categories/taxonomy-with-ids.en-US.txt");
        $lines = explode("\n", $file);;
		?>
        <div class="form-field">
            <label for="google_categories_map"><?php _e('Google Merchant category', $this->plugin_slug); ?></label>
            <select id="google_categories_map" name="google_categories_map">
                <option value=""><?php _e('(None)',$this->plugin_slug);?></option>
				<?php foreach ($lines as $line) :
                    $split = explode(" - ",$line); ?>
                    <option value="<?php echo $split[0];?>" <?php selected( null, $split[0] );?>><?php echo $split[1];?></option>
				<?php endforeach; ?>
            </select>
            <p class="description"><?php _e('Google Merchant product category mapping', $this->plugin_slug); ?></p>
        </div>
		<?php
	}

	function category_mapping_taxonomy_edit_meta_field($term) {
		$file=file_get_contents(plugin_dir_path( __DIR__ )."includes/categories/taxonomy-with-ids.en-US.txt");
		$lines = explode("\n", $file);;
		$value= get_term_meta($term->term_id, 'google_categories_map', true);
		?>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="google_categories_map"><?php _e('Google Merchant categories', $this->plugin_slug); ?></label></th>
            <td>
                <select id="google_categories_map" name="google_categories_map">
                    <option value=""><?php _e('(None)',$this->plugin_slug);?></option>
	                <?php foreach ($lines as $line) :
		                $split = explode(" - ",$line); ?>
                        <option value="<?php echo $split[0];?>" <?php selected( $value, $split[0] );?>><?php echo $split[1];?></option>
					<?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Google Merchant category mapping', $this->plugin_slug); ?></p>
            </td>
        </tr>
		<?php
	}

	function save_taxonomy_custom_meta($term_id) {
		$google_categories_map = filter_input(INPUT_POST, 'google_categories_map');
		update_term_meta($term_id, 'google_categories_map', $google_categories_map);
	}
}
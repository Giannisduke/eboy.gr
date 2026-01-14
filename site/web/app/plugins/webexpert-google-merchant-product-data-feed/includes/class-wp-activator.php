<?php
class WEGoogleMerchantProductDataFeed_Activator {
	public static function activate() {
		global $wpdb;
        if (! wp_next_scheduled ( 'we_daily_google_merchant_xml' )) {
    	   wp_schedule_event(time(), get_option('we_google_merchant_product_data_feed_cron_schedule','daily'), 'we_daily_google_merchant_xml');
        }
        update_option('we_google_merchant_plugin_version', WE_XML_GOOGLE_MERCHANT_PLUGIN_VERSION);

		$url='https://www.webexpert.gr/plugins/updates/?action=get_metadata&slug=webexpert-google-merchant-product-data-feed&license_key='.get_option('we_google_merchant_product_data_feed_license_key').'&domain='.get_bloginfo('url');
		$request = wp_remote_get($url);
		$response = wp_remote_retrieve_body( $request );
		$s = json_decode($response);
		if (isset($s->download_url)) {
			update_option('we_google_merchant_product_data_feed_valid_license',true);
		}else {
			delete_option('we_google_merchant_product_data_feed_valid_license');
		}

		// moving XML to uploads
        $upload_dir = wp_upload_dir();
        $google_merchant_xml_dir = $upload_dir['basedir'] . '/webexpert-google-merchant-product-data-feed';
        wp_mkdir_p($google_merchant_xml_dir);
	}

    public function we_do_this_daily() {
	   include "scripts/google-merchant-engine.php";
    }
}
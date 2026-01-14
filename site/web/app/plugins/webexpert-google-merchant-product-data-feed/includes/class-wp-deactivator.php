<?php
class WEGoogleMerchantProductDataFeed_Deactivator {
	public static function deactivate() {
        wp_clear_scheduled_hook('we_daily_google_merchant_xml');
	}
}
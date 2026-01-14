<?php

class Webexpert_Timologio_For_Woocommerce_i18n {
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'webexpert-timologio-for-woocommerce',
			false,
			dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
		);
	}
}

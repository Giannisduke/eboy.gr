<?php

class Webexpert_woocommerce_order_tracking_i18n
{
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'webexpert-woocommerce-order-tracking',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}

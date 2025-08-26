<?php

namespace App\Providers;

use Roots\Acorn\Sage\SageServiceProvider;

class ThemeServiceProvider extends SageServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}


use Automattic\WooCommerce\Client;

$woocommerce = new Client(
  'https://simple-city.eboy.gr/wp-json/wc/v3/products',
  'ck_411069644bb7de3656fc0aa91c7b1ae647491d73',
  'cs_084bc2a33a810a4beaaf88da2cd8d9b15e8c326d',
  [
    'version' => 'wc/v3',
  ]
);
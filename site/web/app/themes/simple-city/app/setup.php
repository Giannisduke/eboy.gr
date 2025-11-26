<?php

/**
 * Theme setup.
 */

namespace App;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;

/**
 * Inject styles into the block editor.
 *
 * @return array
 */
add_filter('block_editor_settings_all', function ($settings) {
    $style = Vite::asset('resources/css/editor.scss');

    $settings['styles'][] = [
        'css' => Vite::isRunningHot()
            ? "@import url('{$style}')"
            : Vite::content('resources/css/editor.scss'),
    ];

    return $settings;
});

/**
 * Inject scripts into the block editor.
 *
 * @return void
 */
add_filter('admin_head', function () {
    if (! get_current_screen()?->is_block_editor()) {
        return;
    }

    $dependencies = json_decode(Vite::content('editor.deps.json'));

    foreach ($dependencies as $dependency) {
        if (! wp_script_is($dependency)) {
            wp_enqueue_script($dependency);
        }
    }

    echo Vite::withEntryPoints([
        'resources/js/editor.js',
    ])->toHtml();
});

/**
 * Add Vite's HMR client to the block editor.
 *
 * @return void
 */
add_action('enqueue_block_assets', function () {
    if (! is_admin() || ! get_current_screen()?->is_block_editor()) {
        return;
    }

    if (! Vite::isRunningHot()) {
        return;
    }

    $script = sprintf(
        <<<'JS'
        window.__vite_client_url = '%s';

        window.self !== window.top && document.head.appendChild(
            Object.assign(document.createElement('script'), { type: 'module', src: '%s' })
        );
        JS,
        untrailingslashit(Vite::asset('')),
        Vite::asset('@vite/client')
    );

    wp_add_inline_script('wp-blocks', $script);
});

/**
 * Use the generated theme.json file.
 *
 * @return string
 */
add_filter('theme_file_path', function ($path, $file) {
    return $file === 'theme.json'
        ? public_path('build/assets/theme.json')
        : $path;
}, 10, 2);

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Disable full-site editing support.
     *
     * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
     */
    remove_theme_support('block-templates');

    /**
     * Register the navigation menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'sage'),
        'info_navigation' => __('Info Navigation', 'sage'),
        'help_navigation' => __('Help Navigation', 'sage'),
    ]);

    /**
     * Disable the default block patterns.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     */
    remove_theme_support('core-block-patterns');

    /**
     * Enable plugins to manage the document title.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable custom logo.
     *
     * @link hhttps://developer.wordpress.org/themes/functionality/custom-logo/
     */
    add_theme_support('custom-logo');

        /**
     * Enable woocommerce support.
     *
     * @link https://github.com/woocommerce/woocommerce/wiki/Declaring-WooCommerce-support-in-themes#basic-usage
     */
    add_theme_support('woocommerce');
    add_theme_support( 'wc-product-gallery-zoom' ); // Μεγέθυνση εικόνας στη gallery
    add_theme_support( 'wc-product-gallery-lightbox' ); // Lightbox για gallery
    add_theme_support( 'wc-product-gallery-slider' ); // Ενεργοποίηση slider στη gallery προϊόντος

    add_action( 'init', function() {
    update_option( 'woocommerce_setup_complete', 'yes' );
    });

    /**
     * Enable post thumbnail support.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');
    add_image_size('featured_carousel', 1000, 9999, true);
    /**
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');

    /**
     * Enable HTML5 markup support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    /**
     * Enable selective refresh for widgets in customizer.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#customize-selective-refresh-widgets
     */
    add_theme_support('customize-selective-refresh-widgets');
}, 20);

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => __('Primary', 'sage'),
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'sage'),
        'id' => 'sidebar-footer',
    ] + $config);
});

/**
 * Fix asset URLs for multisite subdomain.
 * Replace the main domain with the current site's URL.
 *
 * @return string
 */
$fix_asset_url = function ($url) {
    // Get the main network URL
    $network_url = network_site_url();
    $network_domain = parse_url($network_url, PHP_URL_HOST);

    // Get the current site URL
    $site_url = home_url();
    $site_domain = parse_url($site_url, PHP_URL_HOST);

    // Replace network domain with site domain in asset URLs
    if ($network_domain !== $site_domain && strpos($url, $network_domain) !== false) {
        $url = str_replace('https://' . $network_domain, $site_url, $url);
        $url = str_replace('http://' . $network_domain, $site_url, $url);
    }

    return $url;
};

// Apply to multiple filters to ensure it catches all asset URLs
add_filter('acorn/asset.url', $fix_asset_url, 10, 1);
add_filter('theme_file_uri', $fix_asset_url, 10, 1);
add_filter('stylesheet_directory_uri', $fix_asset_url, 10, 1);
add_filter('template_directory_uri', $fix_asset_url, 10, 1);

/**
 * Replace asset URLs in HTML output as a last resort.
 * This catches URLs that aren't filtered by the above filters.
 */
add_action('template_redirect', function () {
    if (is_admin()) {
        return;
    }

    ob_start(function ($buffer) {
        $network_url = network_site_url();
        $network_domain = parse_url($network_url, PHP_URL_HOST);
        $site_url = home_url();
        $site_domain = parse_url($site_url, PHP_URL_HOST);

        // Only replace if domains are different
        if ($network_domain !== $site_domain) {
            // Replace asset URLs in script and link tags
            $buffer = preg_replace(
                '/(src|href)=["\']https?:\/\/' . preg_quote($network_domain, '/') . '(\/app\/themes\/[^"\']+)["\']/',
                '$1="' . $site_url . '$2"',
                $buffer
            );
        }

        return $buffer;
    });
}, 1);



<?php

/**
 * Banner assets.
 *
 * Ad-banner creatives are reviewed by pasting their markup into a post filed
 * under the "Banners" category. The CSS/JS + GSAP they need are enqueued here
 * (WordPress strips <script>/<link> from post content), and only on single
 * posts in that category so nothing leaks onto the rest of the site.
 */

namespace App;

add_action('wp_enqueue_scripts', function () {
    if (! (is_single() && has_category('Banners'))) {
        return;
    }

    // GSAP once, shared by every banner size.
    wp_enqueue_script(
        'gsap',
        'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/gsap.min.js',
        [],
        '3.13.0',
        true
    );

    // bazaar '26 — 300x250
    $b250 = get_theme_file_uri('public/build/assets/bazaar26-300x250');
    wp_enqueue_style('banner-bazaar26-300x250', "{$b250}/styles_bazaar26_300x250.css", [], null);
    wp_enqueue_script('banner-bazaar26-300x250', "{$b250}/simplecity_bazaar26_300x250.js", ['gsap'], null, true);

    // bazaar '26 — 300x600
    $b600 = get_theme_file_uri('public/build/assets/bazaar26-300x600');
    wp_enqueue_style('banner-bazaar26-300x600', "{$b600}/styles_bazaar26_300x600.css", [], null);
    wp_enqueue_script('banner-bazaar26-300x600', "{$b600}/simplecity_bazaar26_300x600.js", ['gsap'], null, true);
});

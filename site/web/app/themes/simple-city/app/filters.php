<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});

add_filter( 'upload_mimes', 'my_own_mime_types' );

// Disable woocommerce stylesheets
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

add_filter('use_block_editor_for_post', '__return_false');


// Facet remove All css
add_filter( 'facetwp_load_css', '__return_false' );

// Facet product catalogue visibility
add_filter( 'facetwp_search_query_args', function( $search_args, $params ) {
  $search_args['tax_query'] =
   array(
    array(
      'taxonomy' => 'product_visibility',
      'field'    => 'name',
      'terms'    => 'exclude-from-search', // Is true if "Catalog visibility" is set to "Shop only" or "Hidden".
      'operator' => 'NOT IN',
    ),
  );
  return $search_args;
}, 10, 2 );

// Facet instock, outofstock, and onbackorder
add_filter( 'facetwp_facet_display_value', function( $label, $params ) {
  if ( 'my_stock_status_facet' == $params['facet']['name'] ) { // Replace 'my_stock_status_facet' with the name of your stock status facet
    if ( 'instock' == $label ) {
      $label = 'In Stock';
    }
    if ( 'outofstock' == $label ) {
      $label = 'Out of Stock';
    }
    if ( 'onbackorder' == $label ) {
      $label = 'On Backorder';
    }
  }
  return $label;
}, 10, 2 );

// Facet images for labels on main category facet
add_filter( 'facetwp_facet_display_value', function( $label, $params ) {
 
  // Only apply to a facet named "vehicle_type"
  if ( 'sales' == $params['facet']['name'] ) { // Repace "vehicle_type" with the name of your facet
 
    // Get the raw value
    $val = $params['row']['facet_value'];
 
    // Use the raw value to generate the image URL
    $label = '<div class="cat_image"><img src="/app/themes/simple-city/resources/images/sales.svg" alt="{val}" /></div>';
    $label = str_replace( '{val}', $val, $label );
  }
  return $label;
}, 20, 2 );


// Facet images for labels on main category facet
add_filter( 'facetwp_facet_display_value', function( $label, $params ) {
 
  // Only apply to a facet named "vehicle_type"
  if ( 'product_categories' == $params['facet']['name'] ) { // Repace "vehicle_type" with the name of your facet
 
    // Get the raw value
    $val = $params['row']['facet_value'];
 
    // Use the raw value to generate the image URL
    $label = '<div class="cat_image"><img src="/app/themes/simple-city/resources/images/{val}.svg" alt="{val}" /></div>';
    $label = str_replace( '{val}', $val, $label );
  }
  return $label;
}, 20, 2 );


// Facet result count
add_filter( 'facetwp_result_count', function( $output, $params ) {
  $output = 'Dispalying <span class="results-visible">' . $params['lower'] . '-' . $params['upper'] . '</span> of <span class="results-total">' . $params['total'] . '</span> results';
  return $output;
}, 10, 2 );

add_filter( 'facetwp_facet_display_value', function( $label, $params ) {
    if ( 'colors' == $params['facet']['name'] ) { // Replace "my_color_facet_name" with the name of your Color facet.
      $label = $params['row']['facet_value']; // Use the facet_value (term slug) as label. By default, $label is empty.
      $label = ucwords(str_replace('-', ' ', $label)); // Replace dashes with  and capitalize the first letters.
    }
    return $label;
  }, 10, 2);
  
  add_filter( 'facetwp_facet_display_value', function( $label, $params ) {
    if ( 'product_categories' == $params['facet']['name'] ) { // Replace "my_facet_name" with the name of your facet
      $name = $params['row']['facet_display_value'];
      $label =  $label . '<div class="cat_name"><h2>' . $name  . '</h2></div>';
    }
    return $label;
  }, 20, 2 );

add_filter( 'facetwp_index_row', function( $params, $class ) {
    if ( 'product_categories' == $params['facet_name'] ) { // Αντικαταστήστε με το όνομα του facet σας
        $excluded_terms = [ 'Uncategorized' ]; // Η κατηγορία που θέλετε να αποκρύψετε
        if ( in_array( $params['facet_display_value'], $excluded_terms ) ) {
            $params['facet_value'] = ''; // Αποκλείει την κατηγορία από το facet
        }
    }
    return $params;
}, 10, 2 );



add_filter('wp_image_editors', 'fi_force_imagick');



  // Disable Woocommerce setup_wizard
add_filter( 'woocommerce_prevent_automatic_wizard_redirect', '__return_true' );

// "Τεχνικά χαρακτηριστικά" tab — reads AI-generated _tech_specs meta (all suppliers).
add_filter('woocommerce_product_tabs', function (array $tabs): array {
    global $product;
    if (! $product instanceof \WC_Product) {
        return $tabs;
    }
    $tech_specs = get_post_meta($product->get_id(), '_tech_specs', true);
    if (empty($tech_specs)) {
        return $tabs;
    }
    $tabs['tech_specs'] = [
        'title'    => 'Τεχνικά χαρακτηριστικά',
        'priority' => 12,
        'callback' => static function () use ($tech_specs): void {
            echo wp_kses_post($tech_specs);
        },
    ];
    return $tabs;
}, 20);

// Hide "Επιπλέον πληροφορίες" tab from non-managers (shop_manager + administrator only).
add_filter('woocommerce_product_tabs', function (array $tabs): array {
    if (! current_user_can('manage_woocommerce')) {
        unset($tabs['additional_information']);
    }
    return $tabs;
}, 98);

// Add "Διαστάσεις / Βάρος" tab after "Περιγραφή" (priority 15, between description=10 and additional_information=20).
add_filter('woocommerce_product_tabs', function (array $tabs): array {
    $tabs['dimensions_weight'] = [
        'title'    => 'Διαστάσεις / Βάρος',
        'priority' => 15,
        'callback' => function () {
            global $product;
            if (! $product instanceof \WC_Product) {
                return;
            }

            $keywords = ['βάρος', 'weight', 'διαστάσ', 'dimension', 'μήκος', 'πλάτος', 'ύψος', 'βάθος', 'length', 'width', 'height', 'depth'];
            $exclude  = ['Μεικτό Βάρος', 'Ογκομετρικό Βάρος'];
            $rows     = [];

            // WooCommerce built-in weight & dimensions.
            if ($product->get_weight()) {
                $rows[] = ['label' => 'Βάρος', 'value' => wc_format_weight($product->get_weight())];
            }

            if ($product->get_length() || $product->get_width() || $product->get_height()) {
                $rows[] = ['label' => 'Διαστάσεις', 'value' => wc_format_dimensions($product->get_dimensions(false))];
            }

            // Product attributes filtered by dimension/weight keywords.
            foreach ($product->get_attributes() as $attribute) {
                $label = $attribute->is_taxonomy()
                    ? wc_attribute_label($attribute->get_name(), $product)
                    : $attribute->get_name();

                $matched = false;
                foreach ($keywords as $kw) {
                    if (mb_stripos($label, $kw) !== false) {
                        $matched = true;
                        break;
                    }
                }

                if (! $matched || in_array($label, $exclude, true)) {
                    continue;
                }

                if ($attribute->is_taxonomy()) {
                    $terms  = $attribute->get_terms();
                    $values = $terms ? wp_list_pluck($terms, 'name') : [];
                } else {
                    $values = $attribute->get_options();
                }

                if (! empty($values)) {
                    $rows[] = ['label' => $label, 'value' => implode(', ', $values)];
                }
            }

            if (empty($rows)) {
                return;
            }

            echo '<table class="woocommerce-product-attributes shop_attributes">';
            foreach ($rows as $row) {
                printf(
                    '<tr><th class="woocommerce-product-attributes-item__label">%s</th><td class="woocommerce-product-attributes-item__value">%s</td></tr>',
                    esc_html($row['label']),
                    esc_html($row['value'])
                );
            }
            echo '</table>';
        },
    ];
    return $tabs;
}, 15);

// ── WooCommerce + Sage template integration ──────────────────────────────────

// 1. Top-level templates (single-product.php, archive-product.php):
//    Prepend the Sage Blade/PHP paths so WooCommerce's locate_template() finds
//    resources/views/woocommerce/ before falling back to the plugin.
add_filter('woocommerce_template_loader_files', function (array $files, string $default): array {
    $base  = 'resources/views/woocommerce/';
    $blade = $base . str_replace('.php', '.blade.php', $default);
    $php   = $base . $default;
    return array_merge([$blade, $php], $files);
}, 5, 2);

// 2. Template parts (content-single-product.php, single-product/product-image.php …):
//    Redirect wc_get_template() / wc_get_template_part() to resources/views/woocommerce/.
add_filter('woocommerce_locate_template', function (string $template, string $template_name): string {
    $base  = get_stylesheet_directory() . '/resources/views/woocommerce/';
    $blade = $base . str_replace('.php', '.blade.php', $template_name);
    $php   = $base . $template_name;

    if (file_exists($blade)) {
        return $blade;
    }
    if (file_exists($php)) {
        return $php;
    }
    return $template;
}, 10, 3);

// 3. Template parts via wc_get_template_part() (e.g. content-single-product.php):
//    wc_get_template_part() uses locate_template() which checks theme root only,
//    so we redirect here to resources/views/woocommerce/.
add_filter('wc_get_template_part', function (string $template, string $slug, string $name): string {
    $base     = get_stylesheet_directory() . '/resources/views/woocommerce/';
    $filename = $name ? "{$slug}-{$name}" : $slug;
    $blade    = $base . $filename . '.blade.php';
    $php      = $base . $filename . '.php';

    if (file_exists($blade)) {
        return $blade;
    }
    if (file_exists($php)) {
        return $php;
    }
    return $template;
}, 10, 3);



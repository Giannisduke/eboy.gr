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

  add_filter('wp_image_editors', 'fi_force_imagick');

// Disable Woocommerce setup_wizard
add_filter( 'woocommerce_prevent_automatic_wizard_redirect', '__return_true' );
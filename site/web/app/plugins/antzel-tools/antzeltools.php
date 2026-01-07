<?php
/*
Plugin Name: Antzel tools
Plugin URI: https://antzel.gr
Description: Antzel various tools
Author: Antzel
Version: 1.0
Author URI: https://antzel.gr
Text Domain: antzeltools
*/
add_action( 'woocommerce_before_quantity_input_field', 'antzel_before_qty_text' );

/**
 * Function for inserting text before quantiy field on single product
 * 
 * @return void
 */
function antzel_before_qty_text(){
	if ( is_product()) {
		echo '<span class="jo-qty">' . __('Quantity','antzeltools') . '</span>';
	}
}
//Debug purposes - display all actions attached to specific hook
//add_action( 'woocommerce_single_product_summary', 'antzel_before_qty_text' );
function anztel_debug_prd_summary() {
	$hook_name = 'woocommerce_single_product_summary';
	global $wp_filter;
	var_dump( $wp_filter[$hook_name] );
}
//change price order
//remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
//remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
//remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
//remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
//remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
//remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

//add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 10 );
//add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
//add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 30 );
//add_action( ‘woocommerce_single_product_summary’, ‘woocommerce_template_single_price’, 29 );
//add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 40 );

//following two codes deactived on 24/9/2023 - check and reactivate if probs with price (not appearing on single products
//remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
//add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 35 );

//add extra class on mega menu, sub menu (white are) -> Titles (when there is an ul underneath)
function antzel_widget_enqueue_script() {   
    wp_enqueue_script( 'jomegamenuextra', plugin_dir_url( __FILE__ ) . 'js/megajo1.js' ,array(), false, true );
	if( is_shop() || (is_archive() && is_woocommerce()) ) {
        wp_enqueue_script( 'jomonoarchiveonly', plugin_dir_url( __FILE__ ) . 'js/monkfilters1.js',array(), false, true );
	}
	if ( !wp_is_mobile() ) {
		wp_enqueue_script( 'jomenemegamenuspecial', plugin_dir_url( __FILE__ ) . 'js/monkmegasp.js',array('megamenu'), false, true );
	}
	if ( wp_is_mobile() ) {
		//wp_enqueue_script( 'jomonomobtools', plugin_dir_url( __FILE__ ) . 'js/monkmobmenutools.js',array(), false, true );
		$translation_array = array( 
		'goback' => __( 'Back', 'woocommerce' ) 
		);
		//wp_localize_script( 'jomonomobtools', 'antzelmobmenu', $translation_array );
	}
	if (is_checkout()) {
	   //wp_enqueue_script( 'jomonocheckout', plugin_dir_url( __FILE__ ) . 'js/antzel-chckout.js',array(), false, true );
	}
}
add_action('wp_enqueue_scripts', 'antzel_widget_enqueue_script');
//add_action( 'woocommerce_single_product_summary', 'antzel_display_prd_acfs', 8 );
  
function antzel_display_prd_acfs() {
    global $product;
	echo ' demo ';
	print_r(prd_barcode);
	if (get_field('prd_barcode')) {
		echo '<div class="monk_barcode">';
        _e( 'Barcode: ', 'woocommerce' );
        echo '<span>' . get_field('prd_barcode') . '</span>';
        echo '</div>';
	}
	if (get_field('prd_mpn')) {
		echo '<div class="monk_mpn">';
        _e( 'Barcode: ', 'woocommerce' );
        echo '<span>' . get_field('prd_mpn') . '</span>';
        echo '</div>';
	}
}

//disable pagination os admin menu to avoid nesting appearance problems
add_filter( 'nav_menu_meta_box_object', 'disable_pagination_in_menu_meta_box', 9 );

  function disable_pagination_in_menu_meta_box($obj) {
    $obj->_default_query = array(
      'posts_per_page' => -1
    );
    return $obj;
  }
  add_action( 'woocommerce_review_order_before_submit', 'antzel_checkout_privacy_policy', 9 );
    
function antzel_checkout_privacy_policy() {
   
woocommerce_form_field( 'privacy_policy', array(
   'type'          => 'checkbox',
   'class'         => array('form-row privacy'),
   'label_class'   => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
   'input_class'   => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
   'required'      => true,
   'label'         => __('I have read and accept the <a href="/en/privacy-policy">Privacy Policy</a>','antzeltools')
   )); 
   
}
   
// Show notice if customer does not tick
    
add_action( 'woocommerce_checkout_process', 'antzel_not_approved_privacy' );
   
function antzel_not_approved_privacy() {
    if ( ! (int) isset( $_POST['privacy_policy'] ) ) {
        wc_add_notice( __( 'Please acknowledge the Privacy Policy' ), 'error' );
    }
}
// add ability to search by ACF: Barcode / MPN
//add_filter( 'posts_where', 'antzel_searchPrds_by_ACF', 9999, 2 );
 
function antzel_searchPrds_by_ACF( $where, $wp_query ) {   
    global $wpdb, $pagenow;
    $post_type = 'product';
    $custom_fields = array(
        "prd_barcode",
        "prd_mpn",
    );
    if ( is_admin() && 'edit.php' === $pagenow && $wp_query->query['post_type'] === $post_type && isset( $_GET['s'] ) ) {
      $get_post_ids = array();
      foreach ( $custom_fields as $custom_field_name ) {
         $args = array(
            'posts_per_page' => -1,
            'post_type' => $post_type,
            'meta_query' => array(
               array(
                  'key' => $custom_field_name,
                  'value' => wc_clean( wp_unslash( $_GET['s'] ) ),
                  'compare' => 'LIKE'
               )
            ),
            'fields' => 'ids',
         );
         $posts = get_posts( $args );
         if ( ! empty( $posts ) ) {
            foreach ( $posts as $post_id ) {
               $get_post_ids[] = $post_id;
            }
         }
      }
      $search_ids = array_filter( array_unique( array_map( 'absint', $get_post_ids ) ) );
      if ( count( $search_ids ) > 0 ) {
         $where = str_replace( "wp_posts.ID IN (0)", "wp_posts.ID IN (" . implode( ',', $search_ids ) . ")", $where );
      }     
   }  
    return $where;   
} 
// Add a filter on product for set product image
add_action('restrict_manage_posts', 'filter_products_by_image_presence');
function filter_products_by_image_presence() {
    global $typenow;
    $selected = isset($_GET['product_image_presence']) ? $_GET['product_image_presence'] : '';
    if ('product' === $typenow) {
        ?>
        <select name="product_image_presence" id="product_image_presence">
            <option value="">Filter by product image</option>
            <option value="set" <?php selected('set', $selected); ?>>Image Set</option>
            <option value="notset" <?php selected('notset', $selected); ?>>Image Not Set</option>
        </select>
        <?php
    }
}
add_filter('parse_query', 'filter_products_query_by_image_presence');
function filter_products_query_by_image_presence($query) {
    global $pagenow, $typenow;

    if ('edit.php' === $pagenow && 'product' === $typenow && isset($_GET['product_image_presence']) && $_GET['product_image_presence'] != '') {
        $presence = $_GET['product_image_presence'];
        $meta_query = array(
            'relation' => 'OR',
            array(
                'key' => '_thumbnail_id',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => '_thumbnail_id',
                'value' => '0'
            )
        );

        if ('set' === $presence) {
            $meta_query = array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_thumbnail_id',
                    'value' => array('', '0'), // Assuming '0' or '' could be placeholders for no image.
                    'compare' => 'NOT IN'
                ),
            );
        } elseif ('notset' === $presence) {
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_thumbnail_id',
                    'value' => '0'
                )
            );
        }

        $query->set('meta_query', $meta_query);
    }
}
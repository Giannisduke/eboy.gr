<?php
/**
 * Enqueue script and styles for child theme
 */
function woodmart_child_enqueue_styles() {
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'woodmart-style' ), woodmart_get_theme_info( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'woodmart_child_enqueue_styles', 10010 );
//antzel - custom product grid
function jo_custom_cat_grid($attr){
 
    $myargs = shortcode_atts( array(
            'ids' => '',
        ), $attr );
	$myids = explode(",", $myargs['ids']);
	$newids = array();
	foreach ($myids as $myid) {
		$newids[] = apply_filters( 'wpml_object_id', $myid, 'product_cat', TRUE  );
	}
	$taxonomy     = 'product_cat';
	$orderby      = 'name';
	$show_count   = 0;      // 1 for yes, 0 for no
	$pad_counts   = 0;      // 1 for yes, 0 for no
	$hierarchical = 0;      // 1 for yes, 0 for no  
	$title        = '';  
	$empty        = 0;

	$args = array(
			 'taxonomy'     => $taxonomy,
			 'hierarchical' => $hierarchical,
			 'hide_empty'   => $empty,
			 'order'		=> 'menu_order',
			 'include' => $newids,
			 
	  );
	//$product_categories = get_terms( $args );
	$product_categories = get_categories( $args );
	$output = '';
	$output = '<div class="jo-grid-loop">';
	foreach ($product_categories as $cat) {
			$category_id = $cat->term_id;
			$thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
			$image = wp_get_attachment_url( $thumbnail_id );
			$output .= '<div class="jo-grid-cat">';
			$output .= '<div class="jo-grid-head">';
			if ( $image ) {
				$output .=  '<a href="'. get_term_link($cat->slug, 'product_cat') . '"><img src="' . $image . '" alt="' . $cat->name . '" /></a>';
			}
			$output .= '</div>';
			$output .= '<div class="jo-grid-body">';
			$output .= '<h2 class="jo-term-title">'.$cat->name.'</h2>';
			//$output .= '<span class="jo-term-desc">'.$cat->description.'</span>';
			$extradesc1 = get_term_meta( $cat->term_id, 'category_extra_description_text', true );
			$output .= '<span class="jo-term-desc">'.$extradesc1.'</span>';
			$output .= '<a href="'. get_term_link($cat->slug, 'product_cat') .'"><span class="jo-term-buttontext menelefticon">'. __('Δείτε εδώ','woodmart') . '</span>';
			$output .= '</span></a>';
			$output .= '</div>';
			$output .= '</div>';
	}
	$output .= '</div>';
	//wp_reset_query();
    return $output;
 
}
 
add_shortcode( 'jo_cat_grid' , 'jo_custom_cat_grid' );

//antzel - show sku under product grid title
add_action( 'wp_enqueue_scripts', 'antzel_custom_style2', 9999999999999999 );
function antzel_custom_style2($hook) {
    wp_enqueue_style('monojo_css', get_stylesheet_directory_uri() .'/css/monojo.css');
	//wp_register_style('monojo_css', get_stylesheet_directory_uri() .'css/monojo.css', array('woodmart-child'));
	//wp_enqueue_style('monojo_css');
}


//eboy - Easter 2025 animation
add_action( 'wp_enqueue_scripts', 'eboy_gsap', 99999999999999999 );
function eboy_gsap($hook) {
      if( is_front_page() ){
    wp_enqueue_style( 'embla', get_stylesheet_directory_uri().'/css/embla_n.css' ); 
    wp_enqueue_script( 'gsap_js', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.6.1/gsap.min.js', array(), false, true );
    wp_enqueue_script( 'embla_js', 'https://unpkg.com/embla-carousel/embla-carousel.umd.js' );
        wp_enqueue_script( 'embla_autoplay_js', 'https://unpkg.com/embla-carousel-autoplay/embla-carousel-autoplay.umd.js', array(), false, true );

    wp_enqueue_script( 'spring25', get_stylesheet_directory_uri() . '/js/spring2025_i.js', array(), false, true );
    }

}

function eboy_gsap_test() {
  if ( is_page( 41450 ) ) {
    wp_enqueue_style( 'slider_4_css', get_stylesheet_directory_uri().'/css/slider_easter_4.css' ); 
    wp_enqueue_script( 'gsap_js', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.6.1/gsap.min.js', array(), false, true );
    wp_enqueue_script( 'gsap_draggables', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.2.6/Draggable.min.js', array(), false, true );
    wp_enqueue_script( 'easter25', get_stylesheet_directory_uri() . '/js/easter25.js', array(), false, true );

    wp_enqueue_style( 'embla', get_stylesheet_directory_uri().'/css/embla_n.css' ); 
    wp_enqueue_script( 'embla_js', 'https://unpkg.com/embla-carousel/embla-carousel.umd.js' );
    wp_enqueue_script( 'embla_autoplay_js', 'https://unpkg.com/embla-carousel-autoplay/embla-carousel-autoplay.umd.js', array(), false, true );
    wp_enqueue_script( 'spring25', get_stylesheet_directory_uri() . '/js/spring2025_i.js', array(), false, true );
  }
} 

add_action('wp_enqueue_scripts', 'eboy_gsap_test');


//Change Additional Information Tab label
add_filter( 'woocommerce_product_tabs', 'antzel_additionaltab' );
function antzel_additionaltab( $tabs ) {

	$tabs[ 'additional_information' ][ 'title' ] = esc_html__('Characteristics','woodmart');

	return $tabs;

}

//monk custom button with animated triangle
add_shortcode( 'jo_button1' , 'monkcustombutton1' );
function monkcustombutton1 ($myattr){
    $myargs = shortcode_atts( array(
            'text' => '',
			'link' => '',
			'extraclass' => '',
        ), $myattr );
	$mytext= $myargs['text'];
	$mylink= $myargs['link'];
	$myclass= $myargs['extraclass'];
	$output = '<a href="'. $mylink .'" class="'. $myclass. '"><span class="jo-term-buttontext menelefticon">'. __($mytext,'woodmart') . '</span>';
	$output .= '</span></a>';
	return $output;
}
/* Monk custom button on side cart  */
function custom_widget_cart_btn_view_cart()
{
  echo '<a href="#" class="button close-side-widget wd-action-btn">' . esc_html__('Continue shopping', 'woocommerce') . '</a>';
}

function custom_widget_cart_checkout()
{
  echo '<span class="monkminicartinfo" >' . esc_html__('Shipping, taxes, and discount codes calculated at checkout.', 'woocommerce') . '</span>';
  echo '<a href="' . esc_url(wc_get_checkout_url()) . '" class="button checkout wc-forward">' . esc_html__('Complete order', 'woocommerce') . '</a>';
}


remove_action('woocommerce_widget_shopping_cart_buttons', 'woodmart_mini_cart_view_cart_btn', 10);
remove_action('woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_proceed_to_checkout', 20);
//add_action('woocommerce_widget_shopping_cart_buttons', 'custom_widget_cart_checkout', 12);
add_action('woocommerce_widget_shopping_cart_buttons', 'custom_widget_cart_checkout', 12);
add_action('woocommerce_widget_shopping_cart_buttons', 'custom_widget_cart_btn_view_cart', 21);

function monk_arc_img($atts) {
	$queried_object = get_queried_object();
	//print_r("monk-11--");
	//print_r($queried_object);
	//print_r($queried_object->term_id);
		//$queried_object = get_queried_object();
		//print_r($queried_object);
		if ( $queried_object && property_exists( $queried_object, 'term_id' ) ) {
			$thumbnail_id = get_term_meta( $queried_object->term_id, 'title_image', true );
			//$thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
			//$imageurl = wp_get_attachment_url( $thumbnail_id );
			//print_r($thumbnail_id);
			//$image = wp_get_attachment_url( $thumbnail_id['url'] );
				
			if($thumbnail_ifd){
				$imageurl = $thumbnail_id['url'];
				echo "<img class='monk-arcimag' src='$imageurl' alt='' width='100%' height='auto' />";
			}else{
				$imageurl = '/wp-content/uploads/2024/11/bg-e1731510673809.png';
				echo "<img class='monk-arcimag-none' src='$imageurl' alt='' width='100%' height=0 />";
			}
			

		}
	return;
}
add_shortcode('monkarchiveimage', 'monk_arc_img');






// Change buy now button based on language
add_filter( 'woocommerce_product_single_add_to_cart_text', 'custom_woodmart_single_add_to_cart_text' ); // For single product page
add_filter( 'woocommerce_product_add_to_cart_text', 'custom_woodmart_archive_add_to_cart_text' ); // For product archives

function custom_woodmart_single_add_to_cart_text() {
    if ( get_locale() == 'el' ) {
        return 'Αγορά'; // Greek version
    } else {
        return 'Shop'; // English or other versions
    }
}

function custom_woodmart_archive_add_to_cart_text() {
    if ( get_locale() == 'el' ) {
        return 'Αγορά'; // Greek version
    } else {
        return 'Shop'; // English or other versions
    }
}



// Display product price before the Add to Cart form
add_action('woocommerce_before_add_to_cart_form', 'display_product_price_before_cart');

function display_product_price_before_cart() {
    global $product;
    echo '<p class="custom-product-price">' . $product->get_price_html() . '</p>';
}




// Shortcode to display localized contact link in the header
function custom_contact_link_shortcode() {
    // Check the locale and set the contact link accordingly
    if ( get_locale() == 'el' ) {
        return '<a href="https://monk.gr/store-contact/">Επικοινωνία</a>';
    } else {
        return '<a href="https://monk.gr/en/store-contact/">Contact</a>';
    }
}
add_shortcode( 'custom_contact_link', 'custom_contact_link_shortcode' );



// Shortcode to display localized Christmas text in bold and black color in the header
function custom_christmas_text_shortcode() {
    // Check the locale and set the Christmas text accordingly
    if ( get_locale() == 'el' ) {
        return '<span style="font-weight: bold; color: black;">Μοντέρνα διακόσμηση και εξοπλισμός σπιτιού</span>';
    } else {
        return '<span style="font-weight: bold; color: black;">Modern Home Decor</span>';
    }
}
add_shortcode( 'custom_christmas_text', 'custom_christmas_text_shortcode' );




// Create a shortcode to display terms acceptance link based on locale
function custom_terms_link_shortcode() {
    // Check the locale and return the appropriate link and text
    if (get_locale() == 'el') {
        return '<a class="meneroundbox" href="https://monk.gr/oroi-xrisis/" target="_blank">Αποδέχομαι τους όρους χρήσης</a>';
    } else {
        return '<a class="meneroundbox" href="https://monk.gr/en/oroi-xrisis/" target="_blank">I accept the terms of use</a>';
    }
}
add_shortcode('terms_link', 'custom_terms_link_shortcode');




// 
// 
// // Disable purchase for products with a price of 0
add_filter( 'woocommerce_is_purchasable', 'disable_purchase_for_zero_price', 10, 2 );

function disable_purchase_for_zero_price( $purchasable, $product ) {
    if ( $product->get_price() == 0 ) {
        $purchasable = false; // Disable purchase
    }
    return $purchasable;
}

// Prevent adding zero-priced products to the cart
add_filter( 'woocommerce_add_to_cart_validation', 'block_zero_price_products_from_cart', 10, 2 );

function block_zero_price_products_from_cart( $passed, $product_id ) {
    $product = wc_get_product( $product_id );

    if ( $product->get_price() == 0 ) {
        wc_add_notice( __( 'You cannot purchase this product because it has no price.', 'woocommerce' ), 'error' );
        return false; // Block the addition to cart
    }

    return $passed;
}

// Customize button text based on locale and product price
add_filter( 'woocommerce_loop_add_to_cart_link', 'customize_archive_add_to_cart_button', 10, 2 );

function customize_archive_add_to_cart_button( $button, $product ) {
    // Get the current locale
    $locale = get_locale();

    // Check if the product price is 0
    if ( $product->get_price() == 0 ) {
        $text = __( 'Unavailable', 'woocommerce' );
    } else {
        // Set button text based on locale
        if ( $locale === 'el' ) { // Greek locale
			$text = __( 'Αγορά', 'woocommerce' );

        } else { // Default to English or other locales
            $text = __( 'Buy', 'woocommerce' );
        }
    }

    // Rebuild the button with the new text
    $button = sprintf(
        '<a href="%s" class="%s" %s>%s</a>',
        esc_url( $product->get_permalink() ),
        esc_attr( 'button product_type_' . $product->get_type() ),
        $product->is_in_stock() ? '' : 'disabled',
        esc_html( $text )
    );

    return $button;
}


//add_action( 'woocommerce_before_add_to_cart_form', 'show_fields' );
function show_fields() {
	
	global $product;
	$product_id = $product->get_id();	

	$barcode = get_field( 'prd_barcode', $product_id );

	if( $barcode ){
		echo '<p class="meta-fields"> BARCODE: '.$barcode.'</p>';
	}
	
	$mpn = get_field( 'prd_mpn', $product_id );

	if( $mpn ){
		echo '<p class="meta-fields"> MPN: '.$mpn.'</p>';
	}
	
	
}


// Keep only specific sorting options in WooCommerce for both Greek and English versions
add_filter( 'woocommerce_catalog_orderby', 'custom_woocommerce_catalog_orderby' );

function custom_woocommerce_catalog_orderby( $sortby ) {
    // Check locale for Greek or English labels
    if ( get_locale() == 'el' ) {
        // Greek labels
        $sortby = array(
            'date'       => 'Νεότερα',     // Νεότερα
            'popularity' => 'Δημοφιλία',   // Δημοφιλία
			'price' 	=> 'Τιμή χαμηλή προς υψηλή', // Sort by price: low to high
			'price-desc' => 'Τιμή υψηλή προς χαμηλή' // Sort by price: high to low
			
        );
    } else {
        // English labels
        $sortby = array(
            'date'       => 'Newest',     // Newest
            'popularity' => 'Popularity',  // Popularity
			'price' 	=> 'price', // Price: low to high
			'price-desc' => 'price-desc' // Price: high to low
        );
    }

    return $sortby;
}

// Alter WooCommerce Text
add_filter( 'gettext', function( $translated_text ) {
    if ( 'Εισάγετε την διεύθυνσή σας για να εμφανιστούν οι επιλογές αποστολής της παραγγελίας σας.' === $translated_text ) {
        $translated_text = 'Κόστος μεταφορικών κατόπιν συνεννόησης με το κατάστημα';
    }
    return $translated_text;
} );

// Alter WooCommerce Text
add_filter( 'gettext', function( $translated_text ) {
    if ( 'Enter your address to view shipping options.' === $translated_text ) {
        $translated_text = 'Shipping cost upon request';
    }
    return $translated_text;
} );

add_action('wp_footer', function() {
    if ((is_checkout() || is_cart()) && (strpos($_SERVER['REQUEST_URI'], '/en/checkout/') !== false || strpos($_SERVER['REQUEST_URI'], '/en/cart/') !== false)) {
        ?>
        <script>
        jQuery(function($) {
            // Function to replace ΦΠΑ with VAT
            function replaceVAT() {
                $('small.includes_tax').each(function() {
                    const html = $(this).html();
                    $(this).html(html.replace('ΦΠΑ', 'VAT'));
                });
            }

            // Run replacement on page load
            replaceVAT();

            // Run replacement after WooCommerce AJAX updates
            $(document.body).on('updated_checkout updated_cart_totals', function() {
                replaceVAT();
            });
        });
        </script>
        <?php
    }
});

add_action('wp', 'buffer_and_modify_output');
function buffer_and_modify_output() {
    ob_start('add_links_to_jo_grid_elements');
}

function add_links_to_jo_grid_elements($content) {
    // Check if the content contains the specific grid structure
    if (strpos($content, 'jo-grid-loop') !== false) {
        // Use regex to find each `jo-grid-cat` block
        $pattern = '/<div class="jo-grid-cat">(.*?)<\/div>\s*<\/div>/s';
        $content = preg_replace_callback($pattern, function ($matches) {
            $block = $matches[0];

            // Extract the URL from the <a> tag in the head section
            preg_match('/<a href="([^"]+)">/', $block, $urlMatches);
            $url = $urlMatches[1] ?? '';

            if ($url) {
                // Wrap the title and description text with the same link
                $block = preg_replace(
                    [
                        '/<h2 class="jo-term-title">(.*?)<\/h2>/',
                        '/<span class="jo-term-desc">(.*?)<\/span>/',
                    ],
                    [
                        '<h2 class="jo-term-title"><a href="' . $url . '">$1</a></h2>',
                        '<span class="jo-term-desc"><a href="' . $url . '">$1</a></span>',
                    ],
                    $block
                );
            }

            return $block;
        }, $content);
    }

    return $content;
}

add_action('wp_enqueue_scripts', 'make_product_wrapper_clickable_inline_script');
function make_product_wrapper_clickable_inline_script() {
    // Inline JavaScript to make the product wrapper clickable
    $script = "
        jQuery(document).ready(function ($) {
            $('.product-wrapper').each(function () {
                var wrapper = $(this);
                var link = wrapper.find('.product-image-link').attr('href');

                if (link) {
                    wrapper.css('cursor', 'pointer'); // Add pointer cursor to indicate a clickable area

                    // Attach the click event to redirect to the product page
                    wrapper.on('click', function (e) {
                        // Prevent conflicts with inner links or buttons
                        if (!$(e.target).is('a, button')) {
                            window.location.href = link;
                        }
                    });
                }
            });
        });
    ";

    // Add the script inline
    wp_add_inline_script('jquery', $script);
}


if (!function_exists('additional_font_styles')) {
    function additional_font_styles () {
        wp_enqueue_style('PF Bague Sans Pro Bold', 'https://monk.gr/wp-content/uploads/2025/03/');
		 wp_enqueue_style('PF Bague Sans Pro Light', 'https://monk.gr/wp-content/uploads/2025/03/');
    }
    add_action('wp_enqueue_scripts', 'additional_font_styles');
}
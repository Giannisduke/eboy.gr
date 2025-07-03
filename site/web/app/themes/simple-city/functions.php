<?php

use Roots\Acorn\Application;

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our theme. We will simply require it into the script here so that we
| don't have to worry about manually loading any of our classes later on.
|
*/

if (! file_exists($composer = __DIR__.'/vendor/autoload.php')) {
    wp_die(__('Error locating autoloader. Please run <code>composer install</code>.', 'sage'));
}

require $composer;

/*
|--------------------------------------------------------------------------
| Register The Bootloader
|--------------------------------------------------------------------------
|
| The first thing we will do is schedule a new Acorn application container
| to boot when WordPress is finished loading the theme. The application
| serves as the "glue" for all the components of Laravel and is
| the IoC container for the system binding all of the various parts.
|
*/

Application::configure()
    ->withProviders([
        App\Providers\ThemeServiceProvider::class,
    ])
    ->boot();

/*
|--------------------------------------------------------------------------
| Register Sage Theme Files
|--------------------------------------------------------------------------
|
| Out of the box, Sage ships with categorically named theme files
| containing common functionality and setup to be bootstrapped with your
| theme. Simply add (or remove) files from the array below to change what
| is registered alongside Sage.
|
*/

collect(['setup', 'filters'])
    ->each(function ($file) {
        if (! locate_template($file = "app/{$file}.php", true, true)) {
            wp_die(
                /* translators: %s is replaced with the relative file path */
                sprintf(__('Error locating <code>%s</code> for inclusion.', 'sage'), $file)
            );
        }
    });


    function my_own_mime_types( $mimes ) {
        $mimes['svg'] = 'image/svg+xml';
        return $mimes;
    }

    



    function woocommerce_template_loop_info() { 
         global $product;
        $terms = wp_get_post_terms( get_the_id(), 'product_cat' );
        $term  = reset($terms);

        echo '<div class="product_info">';
        echo $term->name;
        echo '</div>';
     }
//add_action( 'woocommerce_after_shop_loop_item_title', 'App\\woocommerce_template_loop_info', 6 );
  // add_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_info', 6);


   


add_action( 'woocommerce_before_shop_loop', function() {
    if ( !is_singular() ) { ?>
<section class="shop_controls">
     <div class="control_catalogue">
        <div class="row">
    <?php }
}, 3);

add_action( 'woocommerce_before_shop_loop', function() {
    if ( !is_singular() ) { ?>
    </div>
    </div>
    </section>
    <?php }
}, 50);


add_action( 'woocommerce_before_shop_loop', function() {
    echo '<div class="col-6">';
    echo facetwp_display( 'counts' );
    echo '</div>';
    
}, 4);




add_action ( 'simple_product_loop', function() {
                $args = array( 'post_type' => 'product' );
            $loop = new WP_Query( $args );
            while ( $loop->have_posts() ) : $loop->the_post();
                $product_s = wc_get_product( $loop->post->ID ); 
                if ($product_s->product_type == 'variable') {
                    $args = array(
                    'post_parent' => $plan->ID,
                    'post_type'   => 'product_variation',
                    'numberposts' => -1,
                    );
                    $variations = $product_s->get_available_variations();
                    echo '<pre>';
                    print_r($variations);
                    // You may get all images from $variations variable using loop
                    echo '</pre>';
                }
            endwhile; wp_reset_query();
}, 10);


/**
 * Show category in catalogue single product.
 */

function category_single_product(){

    $product_cats = wp_get_post_terms( get_the_ID(), 'product_cat' );

    if ( $product_cats && ! is_wp_error ( $product_cats ) ){

        $single_cat = array_shift( $product_cats ); ?>

        <h4 itemprop="name" class="product_category_title"><span><?php echo $single_cat->name; ?></span></h4>

<?php }
}
//add_action( 'woocommerce_after_shop_loop_item', 'category_single_product', 25 );




function open_woocommerce_shop_loop_item_title() { 
    echo '<div class="card-footer">';
}
add_action( 'woocommerce_after_shop_loop_item', 'open_woocommerce_shop_loop_item_title', 11 );

function action_woocommerce_shop_loop_item_title_open() {
    // Removes a function from a specified action hook.
    echo '<div class="info">';
}
   add_action( 'woocommerce_after_shop_loop_item', 'action_woocommerce_shop_loop_item_title_open', 12 );

/**
 * Show the product title in the product loop. By default this is an H2.
 */
function action_woocommerce_shop_loop_item_title() {
    // Removes a function from a specified action hook.

    
    echo '<h3 class="' . esc_attr( apply_filters( 'woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title' ) ) . '">' . get_the_title() . '</h3>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
   add_action( 'woocommerce_after_shop_loop_item', 'action_woocommerce_shop_loop_item_title', 13 );


   add_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_price', 14);


function display_shop_loop_product_attributes() {
    global $product;

// The attribute slug
$attribute = 'color';
// Get attribute term names in a coma separated string
$term_names = $product->get_attribute( $attribute );

// Get the array of the WP_Term objects
$term_slugs = array();
$term_names = str_replace(', ', ',', $term_names);
$term_names_array = explode(',', $term_names); ?>
<div class="color">
<?php if(reset($term_names_array)){
    foreach( $term_names_array as $term_name ){
        // Get the WP_Term object for each term name
        $term = get_term_by( 'name', $term_name, 'pa_'.$attribute );
        // Set the term slug in an array
        $term_slugs[] = $term->slug;

           // Display a coma separted string of term slugs
    echo '<span class="' . $term->slug .'" style="background-color: ' . $term->slug .';"></span>';
    }
 
} ?>
</div>
<?php }
add_action('woocommerce_before_shop_loop_item', 'display_shop_loop_product_attributes', 5);

function action_woocommerce_shop_loop_item_title_close() {
    // Removes a function from a specified action hook.
    echo '</div>';
}
   add_action( 'woocommerce_after_shop_loop_item', 'action_woocommerce_shop_loop_item_title_close', 15 );

function close_woocommerce_shop_loop_item_title() { 
    echo '</div>';
}
add_action( 'woocommerce_after_shop_loop_item', 'close_woocommerce_shop_loop_item_title', 17 );

// Automatically shortens WooCommerce product titles on the main shop, category, and tag pages 
// to a specific number of words
function short_woocommerce_product_titles_words( $title, $id ) {
  if ( ( is_shop() || is_product_tag() || is_product_category() ) && get_post_type( $id ) === 'product' ) {
    $title_words = explode(" ", $title);

    // Kicks in if the product title is longer than 5 words
    if ( count($title_words) > 5 ) { 
      // Shortens the title to 5 words and adds ellipsis at the end
      return implode(' ', array_slice( $title_words, 0, 5) ) . '...';

    }
  }

  return $title;
}
add_filter( 'the_title', 'short_woocommerce_product_titles_words', 10, 2 );

function fi_force_imagick() {
    return array('WP_Image_Editor_Imagick');
 }

 //Convert uploaded files to webp and delete uploaded file. Imagick needs to be enabled. This can be done via the php config settings in your servers control panel.

function compress_and_convert_images_to_webp($file) {
    // Check if file type is supported
    $supported_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $blackPoint = 10;
    $whitePoint = 200;

    if (!in_array($file['type'], $supported_types)) {
        return $file;
    }

    // Check if file is already a WebP image
    if ($file['type'] === 'image/webp') {
        return $file;
    }

    // Get the path to the upload directory
    $wp_upload_dir = wp_upload_dir();

    // Set up the file paths
    $old_file_path = $file['file'];
    $file_name = basename($file['file']);
    $webp_file_path = $wp_upload_dir['path'] . '/' . pathinfo($file_name, PATHINFO_FILENAME) . '.webp';

    // Load the image using Imagick
    $image = new Imagick($old_file_path);

    // Resize the image if the width is greater than 1400 pixels
    $max_width = 1400;
    if ($image->getImageWidth() > $max_width) {
        $image->resizeImage($max_width, 0, Imagick::FILTER_LANCZOS, 1);
    }

    // We replace white background with fuchsia to improve clipping
    $image->floodFillPaintImage("rgb(255, 0, 255)", 2500, "rgb(255,255,255)", 0 , 0, false);
    // We convert fuchsia to transparent
    $image->paintTransparentImage("rgb(255,0,255)", 0, 10);
    // We eliminate empty areas to only leave objects
   // $image->trimImage(0);

    // Compress the image
    $quality = 75; // Adjust this value to control the compression level
    $image->setImageCompressionQuality($quality);
    $image->stripImage(); // Remove all profiles and comments to reduce file size

    $image->autoLevelImage();

    // Convert the image to WebP
    $image->setImageFormat('webp');
    $image->setOption('webp:lossless', 'false');
    $image->setOption('webp:method', '0'); // Adjust this value to control the compression level for WebP
    $image->writeImage($webp_file_path);

    // Delete the old image file
    unlink($old_file_path);


            // We clean cache
        $image->clear();

        // We destroy everything
        $image->destroy();

    // Return the updated file information
    return [
        'file' => $webp_file_path,
        'url' => $wp_upload_dir['url'] . '/' . basename($webp_file_path),
        'type' => 'image/webp',
    ];
}
add_filter('wp_handle_upload', 'compress_and_convert_images_to_webp');




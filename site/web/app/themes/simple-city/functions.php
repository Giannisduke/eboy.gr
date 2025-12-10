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
        $mimes['csv'] = 'text/csv';
        return $mimes;
    }
add_action( 'init', function() {
    update_option( 'woocommerce_setup_complete', 'yes' );
});


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


// Removed FacetWP counts display
// add_action( 'woocommerce_before_shop_loop', function() {
//     echo '<div class="col-6">';
//     echo facetwp_display( 'counts' );
//     echo '</div>';
//
// }, 4);

function open_woocommerce_product_image() { 
    echo '<div class="image_wrapper"><div class="image_inner">';
}
add_action( 'woocommerce_before_single_product_summary', 'open_woocommerce_product_image', 5);

function close_woocommerce_catalog_ordering() { 
    echo '</div></div>';
}
add_action( 'woocommerce_before_single_product_summary', 'close_woocommerce_catalog_ordering', 25);

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
//add_filter('wp_handle_upload', 'compress_and_convert_images_to_webp');


/**
 * Register Custom REST API Endpoints for Vue Shop
 */
add_action('rest_api_init', function () {
    // Products endpoint
    register_rest_route('theme/v1', '/products', [
        'methods' => 'GET',
        'callback' => 'get_shop_products',
        'permission_callback' => '__return_true',
    ]);

    // Categories endpoint
    register_rest_route('theme/v1', '/categories', [
        'methods' => 'GET',
        'callback' => 'get_shop_categories',
        'permission_callback' => '__return_true',
    ]);

    // Tags endpoint
    register_rest_route('theme/v1', '/tags', [
        'methods' => 'GET',
        'callback' => 'get_shop_tags',
        'permission_callback' => '__return_true',
    ]);

    // Colors endpoint
    register_rest_route('theme/v1', '/colors', [
        'methods' => 'GET',
        'callback' => 'get_shop_colors',
        'permission_callback' => '__return_true',
    ]);

    // Materials endpoint
    register_rest_route('theme/v1', '/materials', [
        'methods' => 'GET',
        'callback' => 'get_shop_materials',
        'permission_callback' => '__return_true',
    ]);

    // Heights endpoint
    register_rest_route('theme/v1', '/heights', [
        'methods' => 'GET',
        'callback' => 'get_shop_heights',
        'permission_callback' => '__return_true',
    ]);

    // Widths endpoint
    register_rest_route('theme/v1', '/widths', [
        'methods' => 'GET',
        'callback' => 'get_shop_widths',
        'permission_callback' => '__return_true',
    ]);

    // Depths endpoint
    register_rest_route('theme/v1', '/depths', [
        'methods' => 'GET',
        'callback' => 'get_shop_depths',
        'permission_callback' => '__return_true',
    ]);

    // Price range endpoint
    register_rest_route('theme/v1', '/price-range', [
        'methods' => 'GET',
        'callback' => 'get_price_range',
        'permission_callback' => '__return_true',
    ]);
});

/**
 * Get WooCommerce products with filters
 */
function get_shop_products($request) {
    $params = $request->get_params();

    // Create cache key from all parameters
    $cache_key = 'shop_products_' . md5(serialize($params));

    // Try to get from cache (3 minutes - shorter for products)
    $cached_response = get_transient($cache_key);
    if ($cached_response !== false) {
        return new WP_REST_Response($cached_response);
    }

    // Prepare WP_Query arguments
    $orderby = isset($params['orderby']) ? sanitize_text_field($params['orderby']) : 'menu_order';
    $order = isset($params['order']) ? sanitize_text_field($params['order']) : 'ASC';

    $args = [
        'post_type' => 'product',
        'posts_per_page' => isset($params['per_page']) ? intval($params['per_page']) : 10,
        'paged' => isset($params['page']) ? intval($params['page']) : 1,
        'post_status' => 'publish',
        'order' => $order,
    ];

    // Handle price sorting specially
    if ($orderby === 'price') {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = '_price';
    } else {
        $args['orderby'] = $orderby;
    }

    // Add category and tags filters
    $tax_query = [];

    if (isset($params['category']) && !empty($params['category'])) {
        $tax_query[] = [
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => intval($params['category']),
        ];
    }

    if (isset($params['tags']) && !empty($params['tags'])) {
        $tag_ids = array_map('intval', explode(',', $params['tags']));
        $tax_query[] = [
            'taxonomy' => 'product_tag',
            'field' => 'term_id',
            'terms' => $tag_ids,
            'operator' => 'AND', // Products must have ALL selected tags
        ];
    }

    if (isset($params['colors']) && !empty($params['colors'])) {
        $color_ids = array_map('intval', explode(',', $params['colors']));
        $tax_query[] = [
            'taxonomy' => 'pa_color',
            'field' => 'term_id',
            'terms' => $color_ids,
            'operator' => 'IN', // Products can have ANY of the selected colors
        ];
    }

    if (isset($params['materials']) && !empty($params['materials'])) {
        $material_ids = array_map('intval', explode(',', $params['materials']));
        $tax_query[] = [
            'taxonomy' => 'pa_υλικό',
            'field' => 'term_id',
            'terms' => $material_ids,
            'operator' => 'AND', // Products must have ALL selected materials
        ];
    }

    if (isset($params['height']) && !empty($params['height'])) {
        $tax_query[] = [
            'taxonomy' => 'pa_ύψος',
            'field' => 'slug',
            'terms' => sanitize_text_field($params['height']),
        ];
    }

    if (isset($params['width']) && !empty($params['width'])) {
        $tax_query[] = [
            'taxonomy' => 'pa_πλάτος',
            'field' => 'slug',
            'terms' => sanitize_text_field($params['width']),
        ];
    }

    if (isset($params['depth']) && !empty($params['depth'])) {
        $tax_query[] = [
            'taxonomy' => 'pa_μήκος',
            'field' => 'slug',
            'terms' => sanitize_text_field($params['depth']),
        ];
    }

    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
        if (count($tax_query) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }
    }

    // Add search filter
    if (isset($params['search']) && !empty($params['search'])) {
        $args['s'] = sanitize_text_field($params['search']);
    }

    // Build meta_query
    $meta_query = [];

    // Add on sale filter
    if (isset($params['on_sale']) && $params['on_sale'] === 'true') {
        $meta_query[] = [
            'key' => '_sale_price',
            'value' => 0,
            'compare' => '>',
            'type' => 'NUMERIC',
        ];
    }

    // Add price range filter
    if (isset($params['min_price']) && !empty($params['min_price'])) {
        $meta_query[] = [
            'key' => '_price',
            'value' => floatval($params['min_price']),
            'compare' => '>=',
            'type' => 'NUMERIC',
        ];
    }

    if (isset($params['max_price']) && !empty($params['max_price'])) {
        $meta_query[] = [
            'key' => '_price',
            'value' => floatval($params['max_price']),
            'compare' => '<=',
            'type' => 'NUMERIC',
        ];
    }

    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
        if (count($meta_query) > 1) {
            $args['meta_query']['relation'] = 'AND';
        }
    }

    // Execute query
    $query = new WP_Query($args);

    // Format products for Vue
    $products = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());

            // Get product categories
            $categories = [];
            $terms = get_the_terms(get_the_ID(), 'product_cat');
            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $categories[] = [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                    ];
                }
            }

            // Get product image
            $image = null;
            if (has_post_thumbnail()) {
                $image_id = get_post_thumbnail_id();
                $image_data = wp_get_attachment_image_src($image_id, 'medium');
                $image = [
                    'src' => $image_data[0],
                    'alt' => get_the_title(),
                ];
            }

            $products[] = [
                'id' => get_the_ID(),
                'name' => get_the_title(),
                'slug' => $product->get_slug(),
                'permalink' => get_permalink(),
                'price' => $product->get_price(),
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'price_html' => $product->get_price_html(),
                'on_sale' => $product->is_on_sale(),
                'in_stock' => $product->is_in_stock(),
                'categories' => $categories,
                'images' => $image ? [$image] : [],
                'short_description' => $product->get_short_description(),
            ];
        }
        wp_reset_postdata();
    }

    // Prepare response data
    $response_data = [
        'products' => $products,
        'total' => $query->found_posts,
        'totalPages' => $query->max_num_pages,
        'currentPage' => intval($args['paged']),
    ];

    // Cache the response for 3 minutes (180 seconds)
    set_transient($cache_key, $response_data, 180);

    $response = new WP_REST_Response($response_data);

    // Add pagination headers
    $response->header('X-WP-Total', $query->found_posts);
    $response->header('X-WP-TotalPages', $query->max_num_pages);

    return $response;
}

/**
 * Clear shop cache when products are updated
 */
function clear_shop_cache($post_id) {
    // Only clear cache for products
    if (get_post_type($post_id) !== 'product') {
        return;
    }

    // Clear all shop-related transients
    global $wpdb;

    // Delete all shop transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_shop_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_shop_%'");

    // Also clear WordPress object cache if available
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}

// Hook into product save/update/delete events
add_action('save_post_product', 'clear_shop_cache');
add_action('delete_post', 'clear_shop_cache');
add_action('woocommerce_update_product', 'clear_shop_cache');
add_action('woocommerce_new_product', 'clear_shop_cache');

/**
 * Clear shop cache when terms are updated
 */
function clear_shop_cache_on_term_change($term_id, $tt_id, $taxonomy) {
    // Clear cache for product-related taxonomies
    if (in_array($taxonomy, ['product_cat', 'product_tag', 'pa_color'])) {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_shop_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_shop_%'");
    }
}

add_action('created_term', 'clear_shop_cache_on_term_change', 10, 3);
add_action('edited_term', 'clear_shop_cache_on_term_change', 10, 3);
add_action('delete_term', 'clear_shop_cache_on_term_change', 10, 3);

/**
 * Add admin menu for cache management
 */
function shop_cache_admin_menu() {
    add_submenu_page(
        'woocommerce',
        'Shop Cache',
        'Shop Cache',
        'manage_woocommerce',
        'shop-cache',
        'shop_cache_admin_page'
    );
}
add_action('admin_menu', 'shop_cache_admin_menu');

/**
 * Shop cache admin page
 */
function shop_cache_admin_page() {
    global $wpdb;

    // Handle manual cache clear
    if (isset($_POST['clear_cache']) && check_admin_referer('clear_shop_cache')) {
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_shop_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_shop_%'");
        echo '<div class="notice notice-success"><p>Cache cleared successfully!</p></div>';
    }

    // Get cache statistics
    $cache_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_shop_%'");
    $cache_size = $wpdb->get_var("SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE '_transient_shop_%'");

    ?>
    <div class="wrap">
        <h1>Shop Cache Management</h1>

        <div class="card" style="max-width: 800px;">
            <h2>Cache Statistics</h2>
            <table class="widefat">
                <tr>
                    <td><strong>Cached Items:</strong></td>
                    <td><?php echo number_format($cache_count); ?></td>
                </tr>
                <tr>
                    <td><strong>Cache Size:</strong></td>
                    <td><?php echo size_format($cache_size, 2); ?></td>
                </tr>
                <tr>
                    <td><strong>Cache Duration:</strong></td>
                    <td>
                        Products: 3 minutes<br>
                        Filters (tags, colors, price): 5 minutes
                    </td>
                </tr>
            </table>

            <h2 style="margin-top: 20px;">How It Works</h2>
            <ul style="margin-left: 20px;">
                <li>API responses are cached in WordPress transients</li>
                <li>Each unique combination of filters creates a separate cache entry</li>
                <li>Cache is automatically cleared when products or terms are updated</li>
                <li>Reduces database queries by 80-90% for repeated requests</li>
            </ul>

            <h2 style="margin-top: 20px;">Manual Cache Clear</h2>
            <p>Use this if you need to force refresh all shop data:</p>
            <form method="post">
                <?php wp_nonce_field('clear_shop_cache'); ?>
                <button type="submit" name="clear_cache" class="button button-primary button-large">
                    Clear All Shop Cache
                </button>
            </form>
        </div>
    </div>
    <?php
}
add_action('admin_menu', 'shop_cache_admin_menu');

/**
 * Get WooCommerce product categories
 */
function get_shop_categories($request) {
    $params = $request->get_params();

    // Create cache key from all parameters
    $cache_key = 'shop_categories_' . md5(serialize($params));

    // Try to get from cache (5 minutes)
    $cached_response = get_transient($cache_key);
    if ($cached_response !== false) {
        return new WP_REST_Response($cached_response);
    }

    $args = [
        'taxonomy' => 'product_cat',
        'hide_empty' => isset($params['hide_empty']) ? filter_var($params['hide_empty'], FILTER_VALIDATE_BOOLEAN) : true,
        'parent' => isset($params['parent']) ? intval($params['parent']) : 0,
        'number' => isset($params['per_page']) ? intval($params['per_page']) : 100,
    ];

    $categories = get_terms($args);

    if (is_wp_error($categories)) {
        return new WP_Error('no_categories', 'No categories found', ['status' => 404]);
    }

    $formatted_categories = [];
    foreach ($categories as $category) {
        $formatted_categories[] = [
            'id' => $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'count' => $category->count,
            'description' => $category->description,
            'parent' => $category->parent,
        ];
    }

    // Cache the response for 5 minutes (300 seconds)
    set_transient($cache_key, $formatted_categories, 300);

    return new WP_REST_Response($formatted_categories);
}

/**
 * Get WooCommerce product tags
 */
function get_shop_tags($request) {
    $params = $request->get_params();

    // Create cache key from all parameters
    $cache_key = 'shop_tags_' . md5(serialize($params));

    // Try to get from cache (5 minutes)
    $cached_response = get_transient($cache_key);
    if ($cached_response !== false) {
        return new WP_REST_Response($cached_response);
    }

    $args = [
        'taxonomy' => 'product_tag',
        'hide_empty' => isset($params['hide_empty']) ? filter_var($params['hide_empty'], FILTER_VALIDATE_BOOLEAN) : true,
        'number' => isset($params['per_page']) ? intval($params['per_page']) : 100,
    ];

    // Base product IDs (from category filter)
    $base_product_ids = null;

    // If category filter is provided, get only tags from products in that category
    if (isset($params['category']) && !empty($params['category'])) {
        $category_id = intval($params['category']);

        // Get all product IDs in this category
        $base_product_ids = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ],
            ],
        ]);

        if (!empty($base_product_ids)) {
            $args['object_ids'] = $base_product_ids;
        } else {
            // No products in this category, return empty array
            return new WP_REST_Response([]);
        }
    }

    $tags = get_terms($args);

    if (is_wp_error($tags)) {
        return new WP_Error('no_tags', 'No tags found', ['status' => 404]);
    }

    // Get available tags based on ALL current filters
    $available_tag_ids = [];
    if (isset($params['selected_tags']) || isset($params['colors']) || isset($params['materials']) || isset($params['min_price']) || isset($params['max_price'])) {
        // Build query for filtered products
        $filtered_query_args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];

        $tax_query = [];
        $meta_query = [];

        // Add category filter
        if (isset($params['category']) && !empty($params['category'])) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => intval($params['category']),
            ];
        }

        // Add selected tags filter
        if (isset($params['selected_tags']) && !empty($params['selected_tags'])) {
            $selected_tag_ids = array_map('intval', explode(',', $params['selected_tags']));
            $tax_query[] = [
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $selected_tag_ids,
                'operator' => 'AND',
            ];
        }

        // Add colors filter
        if (isset($params['colors']) && !empty($params['colors'])) {
            $color_ids = array_map('intval', explode(',', $params['colors']));
            $tax_query[] = [
                'taxonomy' => 'pa_color',
                'field' => 'term_id',
                'terms' => $color_ids,
                'operator' => 'IN',
            ];
        }

        // Add materials filter
        if (isset($params['materials']) && !empty($params['materials'])) {
            $material_ids = array_map('intval', explode(',', $params['materials']));
            $tax_query[] = [
                'taxonomy' => 'pa_υλικό',
                'field' => 'term_id',
                'terms' => $material_ids,
                'operator' => 'AND',
            ];
        }

        // Add height filter
        if (isset($params['height']) && !empty($params['height'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_ύψος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['height']),
            ];
        }

        // Add width filter
        if (isset($params['width']) && !empty($params['width'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_πλάτος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['width']),
            ];
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $filtered_query_args['tax_query'] = $tax_query;
        }

        // Add price range filter
        if (isset($params['min_price']) && !empty($params['min_price'])) {
            $meta_query[] = [
                'key' => '_price',
                'value' => floatval($params['min_price']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }
        if (isset($params['max_price']) && !empty($params['max_price'])) {
            $meta_query[] = [
                'key' => '_price',
                'value' => floatval($params['max_price']),
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }

        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $filtered_query_args['meta_query'] = $meta_query;
        }

        // Get filtered product IDs
        $filtered_product_ids = get_posts($filtered_query_args);

        if (!empty($filtered_product_ids)) {
            // Get tags that exist in these filtered products
            $available_tags = wp_get_object_terms($filtered_product_ids, 'product_tag', ['fields' => 'ids']);
            $available_tag_ids = is_array($available_tags) ? $available_tags : [];
        }
    }

    $formatted_tags = [];
    foreach ($tags as $tag) {
        $is_available = true;

        // If we have filters applied, check if this tag is available
        if (!empty($available_tag_ids)) {
            $is_available = in_array($tag->term_id, $available_tag_ids);
        }

        $formatted_tags[] = [
            'id' => $tag->term_id,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'count' => $tag->count,
            'available' => $is_available,
        ];
    }

    // Cache the response for 5 minutes (300 seconds)
    set_transient($cache_key, $formatted_tags, 300);

    return new WP_REST_Response($formatted_tags);
}

/**
 * Get WooCommerce product colors (from pa_color attribute)
 */
function get_shop_colors($request) {
    $params = $request->get_params();

    // Create cache key from all parameters
    $cache_key = 'shop_colors_' . md5(serialize($params));

    // Try to get from cache (5 minutes)
    $cached_response = get_transient($cache_key);
    if ($cached_response !== false) {
        return new WP_REST_Response($cached_response);
    }

    $args = [
        'taxonomy' => 'pa_color',
        'hide_empty' => isset($params['hide_empty']) ? filter_var($params['hide_empty'], FILTER_VALIDATE_BOOLEAN) : true,
        'number' => isset($params['per_page']) ? intval($params['per_page']) : 100,
    ];

    // Base product IDs (from category filter)
    $base_product_ids = null;

    // If category filter is provided, get only colors from products in that category
    if (isset($params['category']) && !empty($params['category'])) {
        $category_id = intval($params['category']);

        // Get all product IDs in this category
        $base_product_ids = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ],
            ],
        ]);

        if (!empty($base_product_ids)) {
            $args['object_ids'] = $base_product_ids;
        } else {
            // No products in this category, return empty array
            return new WP_REST_Response([]);
        }
    }

    $colors = get_terms($args);

    if (is_wp_error($colors)) {
        return new WP_REST_Response([]);
    }

    // Get available colors based on ALL current filters
    $available_color_ids = [];
    if (isset($params['selected_tags']) || isset($params['selected_colors']) || isset($params['min_price']) || isset($params['max_price'])) {
        // Build query for filtered products
        $filtered_query_args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];

        $tax_query = [];
        $meta_query = [];

        // Add category filter
        if (isset($params['category']) && !empty($params['category'])) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => intval($params['category']),
            ];
        }

        // Add tags filter
        if (isset($params['selected_tags']) && !empty($params['selected_tags'])) {
            $tag_ids = array_map('intval', explode(',', $params['selected_tags']));
            $tax_query[] = [
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $tag_ids,
                'operator' => 'AND',
            ];
        }

        // Add selected colors filter
        if (isset($params['selected_colors']) && !empty($params['selected_colors'])) {
            $selected_color_ids = array_map('intval', explode(',', $params['selected_colors']));
            $tax_query[] = [
                'taxonomy' => 'pa_color',
                'field' => 'term_id',
                'terms' => $selected_color_ids,
                'operator' => 'IN',
            ];
        }

        // Add selected height filter
        if (isset($params['selected_height']) && !empty($params['selected_height'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_ύψος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_height']),
            ];
        }

        // Add selected width filter
        if (isset($params['selected_width']) && !empty($params['selected_width'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_πλάτος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_width']),
            ];
        }

        // Add selected depth filter
        if (isset($params['selected_depth']) && !empty($params['selected_depth'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_μήκος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_depth']),
            ];
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $filtered_query_args['tax_query'] = $tax_query;
        }

        // Add price range filter
        if (isset($params['min_price']) && !empty($params['min_price'])) {
            $meta_query[] = [
                'key' => '_price',
                'value' => floatval($params['min_price']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }
        if (isset($params['max_price']) && !empty($params['max_price'])) {
            $meta_query[] = [
                'key' => '_price',
                'value' => floatval($params['max_price']),
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }

        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $filtered_query_args['meta_query'] = $meta_query;
        }

        // Get filtered product IDs
        $filtered_product_ids = get_posts($filtered_query_args);

        if (!empty($filtered_product_ids)) {
            // Get colors that exist in these filtered products
            $available_colors = wp_get_object_terms($filtered_product_ids, 'pa_color', ['fields' => 'ids']);
            $available_color_ids = is_array($available_colors) ? $available_colors : [];
        }
    }

    $formatted_colors = [];
    foreach ($colors as $color) {
        $is_available = true;

        // If we have filters applied, check if this color is available
        if (!empty($available_color_ids)) {
            $is_available = in_array($color->term_id, $available_color_ids);
        }

        $formatted_colors[] = [
            'id' => $color->term_id,
            'name' => $color->name,
            'slug' => $color->slug,
            'count' => $color->count,
            'available' => $is_available,
        ];
    }

    // Cache the response for 5 minutes (300 seconds)
    set_transient($cache_key, $formatted_colors, 300);

    return new WP_REST_Response($formatted_colors);
}

/**
 * Get product materials (attribute pa_υλικό)
 */
function get_shop_materials($request) {
    $params = $request->get_params();

    // Create cache key from all parameters
    $cache_key = 'shop_materials_' . md5(serialize($params));

    // Try to get from cache (5 minutes)
    $cached_response = get_transient($cache_key);
    if ($cached_response !== false) {
        return new WP_REST_Response($cached_response);
    }

    $args = [
        'taxonomy' => 'pa_υλικό',
        'hide_empty' => isset($params['hide_empty']) ? filter_var($params['hide_empty'], FILTER_VALIDATE_BOOLEAN) : true,
        'number' => isset($params['per_page']) ? intval($params['per_page']) : 100,
    ];

    // Base product IDs (from category filter)
    $base_product_ids = null;

    // If category filter is provided, get only materials from products in that category
    if (isset($params['category']) && !empty($params['category'])) {
        $category_id = intval($params['category']);

        // Get all product IDs in this category
        $base_product_ids = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ],
            ],
        ]);

        if (!empty($base_product_ids)) {
            $args['object_ids'] = $base_product_ids;
        } else {
            // No products in this category, return empty array
            return new WP_REST_Response([]);
        }
    }

    $materials = get_terms($args);

    if (is_wp_error($materials)) {
        return new WP_REST_Response([]);
    }

    // Get available materials based on ALL current filters
    $available_material_ids = [];
    if (isset($params['selected_tags']) || isset($params['selected_colors']) || isset($params['selected_materials']) || isset($params['min_price']) || isset($params['max_price'])) {
        // Build query for filtered products
        $filtered_query_args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];

        $tax_query = [];
        $meta_query = [];

        // Add category filter
        if (isset($params['category']) && !empty($params['category'])) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => intval($params['category']),
            ];
        }

        // Add tags filter
        if (isset($params['selected_tags']) && !empty($params['selected_tags'])) {
            $tag_ids = array_map('intval', explode(',', $params['selected_tags']));
            $tax_query[] = [
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $tag_ids,
                'operator' => 'AND',
            ];
        }

        // Add selected colors filter
        if (isset($params['selected_colors']) && !empty($params['selected_colors'])) {
            $selected_color_ids = array_map('intval', explode(',', $params['selected_colors']));
            $tax_query[] = [
                'taxonomy' => 'pa_color',
                'field' => 'term_id',
                'terms' => $selected_color_ids,
                'operator' => 'IN',
            ];
        }

        // Add selected materials filter
        if (isset($params['selected_materials']) && !empty($params['selected_materials'])) {
            $selected_material_ids = array_map('intval', explode(',', $params['selected_materials']));
            $tax_query[] = [
                'taxonomy' => 'pa_υλικό',
                'field' => 'term_id',
                'terms' => $selected_material_ids,
                'operator' => 'AND',
            ];
        }

        // Add selected height filter
        if (isset($params['selected_height']) && !empty($params['selected_height'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_ύψος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_height']),
            ];
        }

        // Add selected width filter
        if (isset($params['selected_width']) && !empty($params['selected_width'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_πλάτος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_width']),
            ];
        }

        // Add selected depth filter
        if (isset($params['selected_depth']) && !empty($params['selected_depth'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_μήκος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_depth']),
            ];
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $filtered_query_args['tax_query'] = $tax_query;
        }

        // Add price range filter
        if (isset($params['min_price']) && !empty($params['min_price'])) {
            $meta_query[] = [
                'key' => '_price',
                'value' => floatval($params['min_price']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }
        if (isset($params['max_price']) && !empty($params['max_price'])) {
            $meta_query[] = [
                'key' => '_price',
                'value' => floatval($params['max_price']),
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }

        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $filtered_query_args['meta_query'] = $meta_query;
        }

        // Get filtered product IDs
        $filtered_product_ids = get_posts($filtered_query_args);

        if (!empty($filtered_product_ids)) {
            // Get materials that exist in these filtered products
            $available_materials = wp_get_object_terms($filtered_product_ids, 'pa_υλικό', ['fields' => 'ids']);
            $available_material_ids = is_array($available_materials) ? $available_materials : [];
        }
    }

    $formatted_materials = [];
    foreach ($materials as $material) {
        $is_available = true;

        // If we have filters applied, check if this material is available
        if (!empty($available_material_ids)) {
            $is_available = in_array($material->term_id, $available_material_ids);
        }

        $formatted_materials[] = [
            'id' => $material->term_id,
            'name' => $material->name,
            'slug' => $material->slug,
            'count' => $material->count,
            'available' => $is_available,
        ];
    }

    // Cache the response for 5 minutes (300 seconds)
    set_transient($cache_key, $formatted_materials, 300);

    return new WP_REST_Response($formatted_materials);
}

/**
 * Get product heights (attribute pa_ύψος)
 */
function get_shop_heights($request) {
    $params = $request->get_params();

    // Create cache key from all parameters
    $cache_key = 'shop_heights_' . md5(serialize($params));

    // Try to get from cache (5 minutes)
    $cached_response = get_transient($cache_key);
    if ($cached_response !== false) {
        return new WP_REST_Response($cached_response);
    }

    $args = [
        'taxonomy' => 'pa_ύψος',
        'hide_empty' => isset($params['hide_empty']) ? filter_var($params['hide_empty'], FILTER_VALIDATE_BOOLEAN) : true,
        'number' => isset($params['per_page']) ? intval($params['per_page']) : 100,
    ];

    // Base product IDs (from category filter)
    $base_product_ids = null;

    // If category filter is provided, get only heights from products in that category
    if (isset($params['category']) && !empty($params['category'])) {
        $category_id = intval($params['category']);

        // Get all product IDs in this category
        $base_product_ids = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ],
            ],
        ]);

        if (!empty($base_product_ids)) {
            $args['object_ids'] = $base_product_ids;
        } else {
            // No products in this category, return empty array
            return new WP_REST_Response([]);
        }
    }

    $heights = get_terms($args);

    if (is_wp_error($heights)) {
        return new WP_REST_Response([]);
    }

    // Get available heights based on ALL current filters
    $available_height_ids = [];
    if (isset($params['selected_tags']) || isset($params['selected_colors']) || isset($params['selected_materials']) || isset($params['selected_height']) || isset($params['min_price']) || isset($params['max_price'])) {
        // Build query for filtered products
        $filtered_query_args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];

        $tax_query = [];
        $meta_query = [];

        // Add category filter
        if (isset($params['category']) && !empty($params['category'])) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => intval($params['category']),
            ];
        }

        // Add tags filter
        if (isset($params['selected_tags']) && !empty($params['selected_tags'])) {
            $tag_ids = array_map('intval', explode(',', $params['selected_tags']));
            $tax_query[] = [
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $tag_ids,
                'operator' => 'AND',
            ];
        }

        // Add selected colors filter
        if (isset($params['selected_colors']) && !empty($params['selected_colors'])) {
            $selected_color_ids = array_map('intval', explode(',', $params['selected_colors']));
            $tax_query[] = [
                'taxonomy' => 'pa_color',
                'field' => 'term_id',
                'terms' => $selected_color_ids,
                'operator' => 'IN',
            ];
        }

        // Add selected materials filter
        if (isset($params['selected_materials']) && !empty($params['selected_materials'])) {
            $selected_material_ids = array_map('intval', explode(',', $params['selected_materials']));
            $tax_query[] = [
                'taxonomy' => 'pa_υλικό',
                'field' => 'term_id',
                'terms' => $selected_material_ids,
                'operator' => 'AND',
            ];
        }

        // Add selected height filter
        if (isset($params['selected_height']) && !empty($params['selected_height'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_ύψος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_height']),
            ];
        }

        // Add selected width filter
        if (isset($params['selected_width']) && !empty($params['selected_width'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_πλάτος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_width']),
            ];
        }

        // Add selected depth filter
        if (isset($params['selected_depth']) && !empty($params['selected_depth'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_μήκος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_depth']),
            ];
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $filtered_query_args['tax_query'] = $tax_query;
        }

        // Add price range filter
        if (isset($params['min_price']) && !empty($params['min_price'])) {
            $meta_query[] = [
                'key' => '_price',
                'value' => floatval($params['min_price']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }
        if (isset($params['max_price']) && !empty($params['max_price'])) {
            $meta_query[] = [
                'key' => '_price',
                'value' => floatval($params['max_price']),
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }

        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $filtered_query_args['meta_query'] = $meta_query;
        }

        // Get filtered product IDs
        $filtered_product_ids = get_posts($filtered_query_args);

        if (!empty($filtered_product_ids)) {
            // Get heights that exist in these filtered products
            $available_heights = wp_get_object_terms($filtered_product_ids, 'pa_ύψος', ['fields' => 'ids']);
            $available_height_ids = is_array($available_heights) ? $available_heights : [];
        }
    }

    $formatted_heights = [];
    foreach ($heights as $height) {
        $is_available = true;

        // If we have filters applied, check if this height is available
        if (!empty($available_height_ids)) {
            $is_available = in_array($height->term_id, $available_height_ids);
        }

        $formatted_heights[] = [
            'id' => $height->term_id,
            'name' => $height->name,
            'slug' => $height->slug,
            'count' => $height->count,
            'available' => $is_available,
        ];
    }

    // Cache the response for 5 minutes (300 seconds)
    set_transient($cache_key, $formatted_heights, 300);

    return new WP_REST_Response($formatted_heights);
}

/**
 * Get product widths (attribute pa_πλάτος)
 */
function get_shop_widths($request) {
    $params = $request->get_params();

    // Create cache key from all parameters
    $cache_key = 'shop_widths_' . md5(serialize($params));

    // Try to get from cache (5 minutes)
    $cached_response = get_transient($cache_key);
    if ($cached_response !== false) {
        return new WP_REST_Response($cached_response);
    }

    $args = [
        'taxonomy' => 'pa_πλάτος',
        'hide_empty' => isset($params['hide_empty']) ? filter_var($params['hide_empty'], FILTER_VALIDATE_BOOLEAN) : true,
        'number' => isset($params['per_page']) ? intval($params['per_page']) : 100,
    ];

    // Base product IDs (from category filter)
    $base_product_ids = null;

    // If category filter is provided, get only widths from products in that category
    if (isset($params['category']) && !empty($params['category'])) {
        $category_id = intval($params['category']);

        // Get all product IDs in this category
        $base_product_ids = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ],
            ],
        ]);

        if (!empty($base_product_ids)) {
            $args['object_ids'] = $base_product_ids;
        } else {
            // No products in this category, return empty array
            return new WP_REST_Response([]);
        }
    }

    $widths = get_terms($args);

    if (is_wp_error($widths)) {
        return new WP_REST_Response([]);
    }

    // Get available widths based on ALL current filters
    $available_width_ids = [];
    if (isset($params['selected_tags']) || isset($params['selected_colors']) || isset($params['selected_materials']) || isset($params['selected_height']) || isset($params['selected_width']) || isset($params['min_price']) || isset($params['max_price'])) {
        // Build query for filtered products
        $filtered_query_args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];

        $tax_query = [];
        $meta_query = [];

        // Add category filter
        if (isset($params['category']) && !empty($params['category'])) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => intval($params['category']),
            ];
        }

        // Add tags filter
        if (isset($params['selected_tags']) && !empty($params['selected_tags'])) {
            $tag_ids = array_map('intval', explode(',', $params['selected_tags']));
            $tax_query[] = [
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $tag_ids,
                'operator' => 'AND',
            ];
        }

        // Add selected colors filter
        if (isset($params['selected_colors']) && !empty($params['selected_colors'])) {
            $selected_color_ids = array_map('intval', explode(',', $params['selected_colors']));
            $tax_query[] = [
                'taxonomy' => 'pa_color',
                'field' => 'term_id',
                'terms' => $selected_color_ids,
                'operator' => 'IN',
            ];
        }

        // Add selected materials filter
        if (isset($params['selected_materials']) && !empty($params['selected_materials'])) {
            $selected_material_ids = array_map('intval', explode(',', $params['selected_materials']));
            $tax_query[] = [
                'taxonomy' => 'pa_υλικό',
                'field' => 'term_id',
                'terms' => $selected_material_ids,
                'operator' => 'AND',
            ];
        }

        // Add selected height filter
        if (isset($params['selected_height']) && !empty($params['selected_height'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_ύψος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_height']),
            ];
        }

        // Add selected width filter
        if (isset($params['selected_width']) && !empty($params['selected_width'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_πλάτος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_width']),
            ];
        }

        // Add selected depth filter
        if (isset($params['selected_depth']) && !empty($params['selected_depth'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_μήκος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_depth']),
            ];
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $filtered_query_args['tax_query'] = $tax_query;
        }

        // Add price range filter
        if (isset($params['min_price']) && !empty($params['min_price'])) {
            $meta_query[] = [
                'key' => '_price',
                'value' => floatval($params['min_price']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }
        if (isset($params['max_price']) && !empty($params['max_price'])) {
            $meta_query[] = [
                'key' => '_price',
                'value' => floatval($params['max_price']),
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }

        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $filtered_query_args['meta_query'] = $meta_query;
        }

        // Get filtered product IDs
        $filtered_product_ids = get_posts($filtered_query_args);

        if (!empty($filtered_product_ids)) {
            // Get widths that exist in these filtered products
            $available_widths = wp_get_object_terms($filtered_product_ids, 'pa_πλάτος', ['fields' => 'ids']);
            $available_width_ids = is_array($available_widths) ? $available_widths : [];
        }
    }

    $formatted_widths = [];
    foreach ($widths as $width) {
        $is_available = true;

        // If we have filters applied, check if this width is available
        if (!empty($available_width_ids)) {
            $is_available = in_array($width->term_id, $available_width_ids);
        }

        $formatted_widths[] = [
            'id' => $width->term_id,
            'name' => $width->name,
            'slug' => $width->slug,
            'count' => $width->count,
            'available' => $is_available,
        ];
    }

    // Cache the response for 5 minutes (300 seconds)
    set_transient($cache_key, $formatted_widths, 300);

    return new WP_REST_Response($formatted_widths);
}

/**
 * Get product depths (attribute pa_μήκος)
 */
function get_shop_depths($request) {
    $params = $request->get_params();

    // Create cache key from all parameters
    $cache_key = 'shop_depths_' . md5(serialize($params));

    // Try to get from cache (5 minutes)
    $cached_response = get_transient($cache_key);
    if ($cached_response !== false) {
        return new WP_REST_Response($cached_response);
    }

    $args = [
        'taxonomy' => 'pa_μήκος',
        'hide_empty' => isset($params['hide_empty']) ? filter_var($params['hide_empty'], FILTER_VALIDATE_BOOLEAN) : true,
        'number' => isset($params['per_page']) ? intval($params['per_page']) : 100,
    ];

    // Base product IDs (from category filter)
    $base_product_ids = null;

    // If category filter is provided, get only depths from products in that category
    if (isset($params['category']) && !empty($params['category'])) {
        $category_id = intval($params['category']);

        // Get all product IDs in this category
        $base_product_ids = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ],
            ],
        ]);

        if (!empty($base_product_ids)) {
            $args['object_ids'] = $base_product_ids;
        } else {
            // No products in this category, return empty array
            return new WP_REST_Response([]);
        }
    }

    $depths = get_terms($args);

    if (is_wp_error($depths)) {
        return new WP_REST_Response([]);
    }

    // Get available depths based on ALL current filters
    $available_depth_ids = [];
    if (isset($params['selected_tags']) || isset($params['selected_colors']) || isset($params['selected_materials']) || isset($params['selected_height']) || isset($params['selected_width']) || isset($params['selected_depth']) || isset($params['min_price']) || isset($params['max_price'])) {
        // Build query for filtered products
        $filtered_query_args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];

        $tax_query = [];
        $meta_query = [];

        // Add category filter
        if (isset($params['category']) && !empty($params['category'])) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => intval($params['category']),
            ];
        }

        // Add tags filter
        if (isset($params['selected_tags']) && !empty($params['selected_tags'])) {
            $tag_ids = array_map('intval', explode(',', $params['selected_tags']));
            $tax_query[] = [
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $tag_ids,
                'operator' => 'AND',
            ];
        }

        // Add selected colors filter
        if (isset($params['selected_colors']) && !empty($params['selected_colors'])) {
            $selected_color_ids = array_map('intval', explode(',', $params['selected_colors']));
            $tax_query[] = [
                'taxonomy' => 'pa_color',
                'field' => 'term_id',
                'terms' => $selected_color_ids,
                'operator' => 'IN',
            ];
        }

        // Add selected materials filter
        if (isset($params['selected_materials']) && !empty($params['selected_materials'])) {
            $selected_material_ids = array_map('intval', explode(',', $params['selected_materials']));
            $tax_query[] = [
                'taxonomy' => 'pa_υλικό',
                'field' => 'term_id',
                'terms' => $selected_material_ids,
                'operator' => 'AND',
            ];
        }

        // Add selected height filter
        if (isset($params['selected_height']) && !empty($params['selected_height'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_ύψος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_height']),
            ];
        }

        // Add selected width filter
        if (isset($params['selected_width']) && !empty($params['selected_width'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_πλάτος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_width']),
            ];
        }

        // Add selected depth filter
        if (isset($params['selected_depth']) && !empty($params['selected_depth'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_μήκος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_depth']),
            ];
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $filtered_query_args['tax_query'] = $tax_query;
        }

        // Add price range filter
        if (isset($params['min_price']) && !empty($params['min_price'])) {
            $meta_query[] = [
                'key' => '_price',
                'value' => floatval($params['min_price']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }
        if (isset($params['max_price']) && !empty($params['max_price'])) {
            $meta_query[] = [
                'key' => '_price',
                'value' => floatval($params['max_price']),
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }

        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $filtered_query_args['meta_query'] = $meta_query;
        }

        // Get filtered product IDs
        $filtered_product_ids = get_posts($filtered_query_args);

        if (!empty($filtered_product_ids)) {
            // Get depths that exist in these filtered products
            $available_depths = wp_get_object_terms($filtered_product_ids, 'pa_μήκος', ['fields' => 'ids']);
            $available_depth_ids = is_array($available_depths) ? $available_depths : [];
        }
    }

    $formatted_depths = [];
    foreach ($depths as $depth) {
        $is_available = true;

        // If we have filters applied, check if this depth is available
        if (!empty($available_depth_ids)) {
            $is_available = in_array($depth->term_id, $available_depth_ids);
        }

        $formatted_depths[] = [
            'id' => $depth->term_id,
            'name' => $depth->name,
            'slug' => $depth->slug,
            'count' => $depth->count,
            'available' => $is_available,
        ];
    }

    // Cache the response for 5 minutes (300 seconds)
    set_transient($cache_key, $formatted_depths, 300);

    return new WP_REST_Response($formatted_depths);
}

/**
 * Get price range (min and max) from all products
 */
function get_price_range($request) {
    global $wpdb;

    $params = $request->get_params();

    // Create cache key from all parameters
    $cache_key = 'shop_price_range_' . md5(serialize($params));

    // Try to get from cache (5 minutes)
    $cached_response = get_transient($cache_key);
    if ($cached_response !== false) {
        return new WP_REST_Response($cached_response);
    }

    // Get overall price range based on category
    $where_clause = "WHERE meta_key = '_price'
        AND {$wpdb->posts}.post_type = 'product'
        AND {$wpdb->posts}.post_status = 'publish'
        AND meta_value != ''";

    // If category filter is provided, add category filter
    if (isset($params['category']) && !empty($params['category'])) {
        $category_id = intval($params['category']);

        // Get all product IDs in this category
        $product_ids = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ],
            ],
        ]);

        if (!empty($product_ids)) {
            $product_ids_string = implode(',', array_map('intval', $product_ids));
            $where_clause .= " AND {$wpdb->posts}.ID IN ($product_ids_string)";
        } else {
            // No products in this category, return default range
            return new WP_REST_Response([
                'min' => 0,
                'max' => 1000,
                'filteredMin' => 0,
                'filteredMax' => 1000,
            ]);
        }
    }

    // Get min and max prices from published products
    $results = $wpdb->get_row("
        SELECT
            MIN(CAST(meta_value AS DECIMAL(10,2))) as min_price,
            MAX(CAST(meta_value AS DECIMAL(10,2))) as max_price
        FROM {$wpdb->postmeta}
        INNER JOIN {$wpdb->posts} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
        {$where_clause}
    ");

    $min_price = $results->min_price ? floatval($results->min_price) : 0;
    $max_price = $results->max_price ? floatval($results->max_price) : 1000;

    // Calculate filtered price range if other filters are applied
    $filtered_min = $min_price;
    $filtered_max = $max_price;

    if (isset($params['selected_tags']) || isset($params['selected_colors']) || isset($params['selected_materials'])) {
        // Build query for filtered products
        $filtered_query_args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];

        $tax_query = [];

        // Add category filter
        if (isset($params['category']) && !empty($params['category'])) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => intval($params['category']),
            ];
        }

        // Add tags filter
        if (isset($params['selected_tags']) && !empty($params['selected_tags'])) {
            $tag_ids = array_map('intval', explode(',', $params['selected_tags']));
            $tax_query[] = [
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $tag_ids,
                'operator' => 'AND',
            ];
        }

        // Add colors filter
        if (isset($params['selected_colors']) && !empty($params['selected_colors'])) {
            $color_ids = array_map('intval', explode(',', $params['selected_colors']));
            $tax_query[] = [
                'taxonomy' => 'pa_color',
                'field' => 'term_id',
                'terms' => $color_ids,
                'operator' => 'IN',
            ];
        }

        // Add materials filter
        if (isset($params['selected_materials']) && !empty($params['selected_materials'])) {
            $material_ids = array_map('intval', explode(',', $params['selected_materials']));
            $tax_query[] = [
                'taxonomy' => 'pa_υλικό',
                'field' => 'term_id',
                'terms' => $material_ids,
                'operator' => 'AND',
            ];
        }

        // Add height filter
        if (isset($params['selected_height']) && !empty($params['selected_height'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_ύψος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_height']),
            ];
        }

        // Add width filter
        if (isset($params['selected_width']) && !empty($params['selected_width'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_πλάτος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_width']),
            ];
        }

        // Add depth filter
        if (isset($params['selected_depth']) && !empty($params['selected_depth'])) {
            $tax_query[] = [
                'taxonomy' => 'pa_μήκος',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['selected_depth']),
            ];
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $filtered_query_args['tax_query'] = $tax_query;
        }

        // Get filtered product IDs
        $filtered_product_ids = get_posts($filtered_query_args);

        if (!empty($filtered_product_ids)) {
            $filtered_ids_string = implode(',', array_map('intval', $filtered_product_ids));

            // Get price range for filtered products
            $filtered_results = $wpdb->get_row("
                SELECT
                    MIN(CAST(meta_value AS DECIMAL(10,2))) as min_price,
                    MAX(CAST(meta_value AS DECIMAL(10,2))) as max_price
                FROM {$wpdb->postmeta}
                INNER JOIN {$wpdb->posts} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                WHERE meta_key = '_price'
                AND {$wpdb->posts}.post_type = 'product'
                AND {$wpdb->posts}.post_status = 'publish'
                AND meta_value != ''
                AND {$wpdb->posts}.ID IN ($filtered_ids_string)
            ");

            $filtered_min = $filtered_results->min_price ? floatval($filtered_results->min_price) : $min_price;
            $filtered_max = $filtered_results->max_price ? floatval($filtered_results->max_price) : $max_price;
        }
    }

    $response_data = [
        'min' => $min_price,
        'max' => $max_price,
        'filteredMin' => $filtered_min,
        'filteredMax' => $filtered_max,
    ];

    // Cache the response for 5 minutes (300 seconds)
    set_transient($cache_key, $response_data, 300);

    return new WP_REST_Response($response_data);
}
<section class="featured">
    <?php
// Save the current page content before running custom query
global $post;
$page_content = $post ? $post->post_content : '';

$slides = array();
$args = array(
    'post_type' => 'product',
    'posts_per_page' => 12,
    'tax_query' => array(
            array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'featured',
                'facetwp' => true, // we added this
            ),
        ),
    );
    $slider_query = new WP_Query( $args );

if ( $slider_query->have_posts() ) {
    while ( $slider_query->have_posts() ) {
        $slider_query->the_post();
        if(has_post_thumbnail()){
            $temp = array();
            $thumb_id = get_post_thumbnail_id();
            $thumb_url_array = wp_get_attachment_image_src($thumb_id, 'large', true);
            $thumb_url = $thumb_url_array[0];
            $temp['title'] = get_the_title();
            $temp['content'] = get_the_content();
            $temp['image'] = $thumb_url;
            $slides[] = $temp;
        }
    }
} 
wp_reset_postdata();

//$value_1 = get_field( "main_slogan_1" );
//$value_2 = get_field( "main_slogan_2" );
?>
<div class="embla">
    <div class="container">
        <div class="row">
            <div class="col content">
                <?php echo apply_filters('the_content', $page_content); ?>
            </div>
        </div>
    </div>

    <div class="embla__viewport">
      <div class="embla__container">
        <?php $i=0; foreach($slides as $slide) { extract($slide); ?>
        <div class="embla__slide"> <img src="<?php echo $image ?>" alt="<?php echo esc_attr($title); ?>"></div>
        <?php  } ?>
      </div>
    </div>

</div>

</section>
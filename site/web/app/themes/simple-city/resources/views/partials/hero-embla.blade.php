<section class="featured">
    <?php
$slides = array(); 
$args = array(
    'post_type' => 'product',
    'posts_per_page' => 12,
    'tax_query' => array(
            array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'featured',
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
            $thumb_url_array = wp_get_attachment_image_src($thumb_id, 'full', true);
            $thumb_url = $thumb_url_array[0];
            $temp['title'] = get_the_title();
            $temp['excerpt'] = get_the_excerpt();
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
  <div class="embla__container">
    <?php $i=0; foreach($slides as $slide) { extract($slide); ?>
    <div class="embla__slide"> <img src="<?php echo $image ?>" alt="<?php echo esc_attr($title); ?>"></div>
 <?php  } ?>
  </div>
</div>

</section>
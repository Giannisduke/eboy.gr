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

$value_1 = get_field( "main_slogan_1" );
$value_2 = get_field( "main_slogan_2" );
?>

<div class="featured_wrapper">
    <div class="container">
     <div class="row">
                <h1 class="display-2">
                <?php if( $value_1 ) {
                echo wp_kses_post( $value_1 );
                } ?>
                </h1>
        <div class="slogan">
                <h2 class="display-1">
                <?php if( $value_2 ) {
                echo wp_kses_post( $value_2 );
                } ?>
                </h2>
            <div class="buttons">
                <button type="button" class="btn btn-primary btn-lg">15.345 προίόντα</button>
                <button type="button" class="btn btn-secondary btn-lg">1743 Προσφορές</button>
            </div>

        </div>

        <div class="carousel_wrap">
            <div id="carousel-example-generic" class="carousel slide" data-bs-ride="carousel">
        

                <div class="carousel-inner" role="listbox">
                    <?php $i=0; foreach($slides as $slide) { extract($slide); ?>
                    <div class="carousel-item <?php if($i == 0) { ?>active<?php } ?>">
                        <?php do_action( 'woocommerce_shop_loop_item_title' ); ?>
                        <div class="row">

                            <div class="featured_image">
                            <img src="<?php echo $image ?>" alt="<?php echo esc_attr($title); ?>">
                            </div>

                            <div class="carousel-caption">
                                <h5><?php echo $title; ?></h5>
                                <p><?php echo $excerpt; ?></p>
                            </div>

                        </div>
                    </div>
                    <?php $i++; } ?>
                </div>

        
    <button class="carousel-control-prev" type="button" data-bs-target="#carousel-example-generic" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Previous</span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#carousel-example-generic" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Next</span>
  </button>
     

        </div>
        </div>

            <div class="buttons">
                <button type="button" class="btn btn-primary btn-lg">15.345 προίόντα</button>
                <button type="button" class="btn btn-secondary btn-lg">1743 Προσφορές</button>
            </div>
        </div>
        </div>



</section>
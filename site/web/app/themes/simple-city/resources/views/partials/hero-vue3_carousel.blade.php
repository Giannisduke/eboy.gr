<section class="featured">
<?php
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
 

        </div>

        </div>
        </div>

<div class="featured_slider" id="slider">
    Slider
</div>

</section>
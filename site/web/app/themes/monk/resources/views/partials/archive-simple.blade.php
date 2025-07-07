<section class="shop">
	<div class="row test">
	Test

	</div>
	<div class="products facetwp-template ">


		<?php


			$args = array(


				'post_type' => 'product',


				'products_per_page' => 12,


				'facetwp' => true, // we added this


				);


			$loop = new WP_Query( $args );


			if ( $loop->have_posts() ) {


				while ( $loop->have_posts() ) : $loop->the_post();


					wc_get_template_part( 'content', 'product' );


				endwhile;


			} else {


				echo __( 'No products found' );


			}


			wp_reset_postdata();


		?>

	</div><!-/.facet->
</section>
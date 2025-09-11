<section class="shop">
	<div class="row test">
		<button id="grid_2" data-value="view_small">x2</button>
		<button id="grid_4" data-value="view_normal">x4</button>
		<button id="grid_6" data-value="view_large">x6</button>

@php echo facetwp_display( 'facet', 'sort_products' ); @endphp

	</div>
	<ul class="products facetwp-template ">


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

		</ul><!-/.facet->
</section>
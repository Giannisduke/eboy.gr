<section class="shop">

		<div class="views">
			<div class="left">
				<button id="grid_2" data-value="view_small"> </button>
				<button id="grid_4" class="selected" data-value="view_normal"></button>
				<button id="grid_6" data-value="view_large"></button>
			</div>
	
				@php echo facetwp_display( 'facet', 'sort_products' ); @endphp
	
		</div>

 <div id="vue-shop-app"></div>
</section>
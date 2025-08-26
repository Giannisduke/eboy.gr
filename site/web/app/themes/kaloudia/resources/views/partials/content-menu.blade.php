@php $terms = get_terms(array('taxonomy' => 'product_tag', 'hide_empty' => false)); @endphp

<section class="controls">
  <div class="control">

    <div class="product-tags collapse" id="search_tools" class="search_tools">  
        <div class="checkboxes">
          @php echo facetwp_display( 'facet', 'tags' ); 
          echo facetwp_display( 'facet', 'material' );

          echo '<div class="colorprice">';
          echo facetwp_display( 'facet', 'colors' );    
          echo facetwp_display( 'facet', 'price' );
          echo '</div>';
          @endphp
        </div>
      </div>
              <a class="buttonmenu" data-bs-toggle="collapse" href="#search_tools" role="button" aria-expanded="false" aria-controls="search_tools">
              <svg version="1.1" baseProfile="basic" id="buttonicon"
      	 xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 9 5"
      	 xml:space="preserve">
      <g>
      	<path d="M0,0.5c0-0.13,0.05-0.26,0.15-0.35c0.2-0.2,0.51-0.2,0.71,0L4.5,3.79l3.65-3.65c0.2-0.2,0.51-0.2,0.71,0s0.2,0.51,0,0.71
      		l-4,4c-0.2,0.2-0.51,0.2-0.71,0l-4-4C0.05,0.76,0,0.63,0,0.5z"/>
      </g>
      </svg>

        </a>
    
</div>
</section>
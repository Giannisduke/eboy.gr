<header class="head02">
    <div class="top">

        <div class="top_left">
            <button class="btn p-0 menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#siteOffcanvas" aria-controls="siteOffcanvas" aria-label="Άνοιγμα μενού">
                
                  <svg class="ham hamRotate ham7" viewBox="0 0 100 100" width="40" onclick="this.classList.toggle('active')">
                  <path class="line top" d="m 70,33 h -40 c 0,0 -6,1.368796 -6,8.5 0,7.131204 6,8.5013 6,8.5013 l 20,-0.0013" />
                  <path class="line middle" d="m 70,50 h -40" />
                  <path class="line bottom" d="m 69.575405,67.073826 h -40 c -5.592752,0 -6.873604,-9.348582 1.371031,-9.348582 8.244634,0 19.053564,21.797129 19.053564,12.274756 l 0,-40" />
                </svg>
               
            </button>
        </div>

        <div class="top_wrapper">
          @if (has_nav_menu('info_navigation'))
            <nav class="nav-info" aria-label="{{ wp_get_nav_menu_name('info_navigation') }}">
              {!! wp_nav_menu(['theme_location' => 'info_navigation', 'menu_class' => 'nav', 'echo' => false]) !!}
            </nav>
          @endif

          <div class="logosearch">
            <div class="searchrow">
              <div class="searchwrap">
                @if ( function_exists( 'the_custom_logo' ) )
                  @php echo (the_custom_logo()); @endphp
                @endif
                <div class="searchbar">
                  <input
                    type="text"
                    id="shop-search-input"
                    placeholder="Αναζήτηση προϊόντων..."
                    class="shop-search-input"
                  />
                </div>
              </div>
            </div>
          </div>

          @if (has_nav_menu('help_navigation'))
            <nav class="nav-help" aria-label="{{ wp_get_nav_menu_name('help_navigation') }}">
              {!! wp_nav_menu(['theme_location' => 'help_navigation', 'menu_class' => 'nav', 'echo' => false]) !!}
            </nav>
          @endif

        </div>

        <div class="top_right">
            <div class="cart-icon">
              <a href="{{ wc_get_cart_url() }}" class="cart-link">
                <span class="cart-count">{{ WC()->cart->get_cart_contents_count() }}</span>
              </a>
            </div>
            <div class="account-icon">
              <?php if ( is_user_logged_in() ) { ?>
                <a href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>" title="<?php _e('My Account','woothemes'); ?>"><?php _e('','woothemes'); ?></a>
              <?php } else { ?>
                <a href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>" title="<?php _e('Login / Register','woothemes'); ?>"><?php _e('','woothemes'); ?></a>
              <?php } ?>
            </div>
        </div>

    </div>



</header>

<header class="head02 fixed-top">
    <div class="top">
      
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
                </div>

              </div>         
            </div>

            @if (has_nav_menu('help_navigation'))
              <nav class="nav-help" aria-label="{{ wp_get_nav_menu_name('help_navigation') }}">
                {!! wp_nav_menu(['theme_location' => 'help_navigation', 'menu_class' => 'nav', 'echo' => false]) !!}
              </nav>
            @endif

        </div>
                <div class="main_menu_rapper">
              @if (has_nav_menu('main_navigation'))
                <nav class="nav-main" aria-label="{{ wp_get_nav_menu_name('main_navigation') }}">
                  {!! wp_nav_menu(['theme_location' => 'main_navigation', 'menu_class' => 'nav', 'echo' => false]) !!}
                </nav>
              @endif
            </div>
    </div>

  </header>

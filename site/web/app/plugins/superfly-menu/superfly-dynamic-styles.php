    .sfm-navicon, .sfm-navicon:after, .sfm-navicon:before, .sfm-sidebar-close:before, .sfm-sidebar-close:after {
        height: <?php echo $line; ?>px !important;
    }
    .sfm-label-square .sfm-navicon-button, .sfm-label-rsquare .sfm-navicon-button, .sfm-label-circle .sfm-navicon-button {
    border-width: <?php echo $line; ?>px !important;
    }

    .sfm-vertical-nav .sfm-submenu-visible > a .sfm-sm-indicator i:after {
    -webkit-transform: rotate(180deg);
    transform: rotate(180deg);
    }

    #sfm-mob-navbar .sfm-navicon-button:after {
    /*width: 30px;*/
    }

    .sfm-pos-right .sfm-vertical-nav .sfm-has-child-menu > a:before {
    display: none;
    }

    #sfm-sidebar.sfm-vertical-nav .sfm-menu .sfm-sm-indicator {
    /*background: rgba(255,255,255,0.085);*/
    }

    .sfm-pos-right #sfm-sidebar.sfm-vertical-nav .sfm-menu li a {
    /*padding-left: 10px !important;*/
    }

    .sfm-pos-right #sfm-sidebar.sfm-vertical-nav .sfm-sm-indicator {
    left: auto;
    right: 0;
    }

    #sfm-sidebar.sfm-compact .sfm-nav {
    min-height: 50vh;
    height: auto;
    max-height: none;
    margin-top: 30px;
    }

    #sfm-sidebar.sfm-compact  input[type=search] {
    font-size: 16px;
    }
    /*}*/

    <?php if(isset($options['sf_bg_color_panel_1'])): ?>
        #sfm-sidebar .sfm-sidebar-bg, #sfm-sidebar .sfm-social {
        background-color: <?php echo $options['sf_bg_color_panel_1']; ?> !important;
        }

    <?php endif; ?>
<?php if(isset($options['sf_logo_size'])): ?>
        #sfm-sidebar .sfm-logo img {
        max-height: <?php echo $options['sf_logo_size']; ?>px;
        }
    <?php endif; ?>

<?php if(isset($options['sf_transparent_panel']) && $options['sf_transparent_panel'] != 'none'): ?>
        #sfm-sidebar .sfm-sidebar-bg  {
        background-color: <?php echo $options['sf_transparent_panel'] == 'dark' ? 'rgba(0,0,0,0.7)' : 'rgba(255,255,255, 0.7)'; ?> !important;
        }

        #sfm-sidebar .sfm-social {
        background-color: transparent !important;
        }
        .sfm-sidebar-slide.sfm-body-pushed .sfm-rollback {
        opacity: 0;
        }
    <?php endif; ?>

    #sfm-sidebar, .sfm-sidebar-bg, #sfm-sidebar .sfm-nav, #sfm-sidebar .sfm-widget, #sfm-sidebar .sfm-logo, #sfm-sidebar .sfm-social, .sfm-style-toolbar .sfm-copy {
    width: <?php echo $width_panel_1; ?>px;
    }
    <?php if(intval($options['sf_font_size']) > 25): ?>
        #sfm-sidebar .sfm-menu li > a span {
        vertical-align: baseline;
        }
    <?php endif; ?>
    #sfm-sidebar:not(.sfm-iconbar) .sfm-menu li > a span{
    <?php
$shift = ((int) $options['sf_icon_size']) + 28 + 12;
?>
    max-width: <?php echo "calc({$width_panel_1}px - {$shift}px);"; ?>
    }
    #sfm-sidebar .sfm-social {
    background-color: transparent !important;
    }

    <?php if(!empty($options['sf_image_bg'])): ?>
        .sfm-sidebar-bg {
        background-image: url(<?php echo $options['sf_image_bg'] ; ?>);
        background-repeat: no-repeat;
        -webkit-background-size: cover;
        background-size: cover;
        background-position: 0 0;
        }
        #sfm-sidebar .sfm-social {
        background-color: transparent !important;
        }
    <?php endif; ?>
<?php if($options['sf_highlight'] == 'semi-dark'): ?>
        #sfm-sidebar .sfm-menu li > a:before {
        background: rgba(0, 0, 0, 0.05) !important;
        }
    <?php endif; ?>


    <?php if($options['sf_highlight'] == 'solid'): ?>
    @media only screen and (min-width: 800px) {
        #sfm-sidebar .sfm-menu-level-0 li > a:before,
        #sfm-sidebar .sfm-menu-level-0 .sfm-active-item > a:before,
        #sfm-sidebar .sfm-menu-level-0 .sfm-active-smooth > a:before {
        background-color: <?php echo $bg_color_panel_2; ?>;
        }
    }

    #sfm-sidebar .sfm-view-level-1 .sfm-menu a:before,
    #sfm-sidebar .sfm-view-level-1 .sfm-menu .sfm-active-item a:before {
    background-color: <?php echo $bg_color_panel_3; ?>;
    }

    #sfm-sidebar .sfm-view-level-2 .sfm-menu a:before,
    #sfm-sidebar .sfm-view-level-2 .sfm-menu .sfm-active-item a:before {
    background-color: <?php echo $bg_color_panel_4; ?>;
    }

    #sfm-sidebar .sfm-view-level-3 .sfm-menu a:before,
    #sfm-sidebar .sfm-view-level-3 .sfm-menu .sfm-active-item a:before {
    background-color: <?php echo $bg_color_panel_1; ?>;
    }
    <?php endif; ?>

    <?php if($options['sf_highlight'] == 'text'): ?>
    #sfm-sidebar .sfm-menu li:hover > a,
    #sfm-sidebar .sfm-menu li > a:focus {
        color: <?php echo $options['sf_hover_panel_1']; ?>;
    }

    #sfm-sidebar .sfm-menu li:hover .sfm-sm-indicator i {
        border-color: <?php echo $options['sf_hover_panel_1']; ?>;
    }

    #sfm-sidebar .sfm-menu-level-1 li:hover > a,
    #sfm-sidebar .sfm-menu-level-1 li > a:focus {
        color: <?php echo $options['sf_hover_panel_2']; ?>;
    }

    #sfm-sidebar .sfm-menu-level-1 li:hover .sfm-sm-indicator i {
    border-color: <?php echo $options['sf_hover_panel_2']; ?>;
    }

    #sfm-sidebar .sfm-menu-level-2 li:hover > a,
    #sfm-sidebar .sfm-menu-level-2 li > a:focus {
        color: <?php echo $options['sf_hover_panel_3']; ?>;
    }

    #sfm-sidebar .sfm-menu-level-2 li:hover .sfm-sm-indicator i {
    border-color: <?php echo $options['sf_hover_panel_3']; ?>;
    }

    #sfm-sidebar .sfm-menu-level-3 li:hover > a,
    #sfm-sidebar .sfm-menu-level-3 li > a:focus {
        color: <?php echo $options['sf_hover_panel_4']; ?>;
    }

    #sfm-sidebar .sfm-menu-level-3 li:hover .sfm-sm-indicator i {
    border-color: <?php echo $options['sf_hover_panel_4']; ?>;
    }

    #sfm-sidebar .sfm-menu li a, #sfm-sidebar .sfm-menu a span, #sfm-sidebar .sfm-menu a img {
        -webkit-transition: none;
        transition: none;
    }
    #sfm-sidebar .sfm-menu li:hover > a span,
    #sfm-sidebar .sfm-menu li > a:focus span,
    #sfm-sidebar .sfm-vertical-nav .sfm-menu li:hover > a img,
    #sfm-sidebar .sfm-vertical-nav .sfm-menu li > a:focus img,
    #sfm-sidebar .sfm-menu li.sfm-submenu-visible > a img,
    #sfm-sidebar .sfm-menu li.sfm-submenu-visible > a span {
        right: 0;
    }

    #sfm-sidebar .sfm-menu li > a:before {
        height: 0;
    }
    <?php endif; ?>

    <?php if($options['sf_highlight'] == 'strike'): ?>

    #sfm-sidebar .sfm-menu li > a span {
    position: relative;
    }
    #sfm-sidebar .sfm-menu li > a:before {
    height: 0;
    }
    #sfm-sidebar .sfm-menu li a, #sfm-sidebar .sfm-menu a span, #sfm-sidebar .sfm-menu a img {
    -webkit-transition: none;
    transition: none;
    }
    #sfm-sidebar .sfm-menu li:hover > a span,
    #sfm-sidebar .sfm-menu li > a:focus span,
    #sfm-sidebar .sfm-vertical-nav .sfm-menu li:hover > a img,
    #sfm-sidebar .sfm-vertical-nav .sfm-menu li > a:focus img,
    #sfm-sidebar .sfm-menu li.sfm-submenu-visible > a img,
    #sfm-sidebar .sfm-menu li.sfm-submenu-visible > a span {
    right: 0;
    }

    #sfm-sidebar .sfm-menu li > a:before {
    height: 0;
    }
    #sfm-sidebar .sfm-menu li > a span:before, #sfm-sidebar .sfm-menu li > a span:after {
    content: '';
    position: absolute;
    width: 0%;
    height: 4px;
    /*top: 50%;*/
    top: <?php echo intval($options['sf_font_size']) / 2 ?>px;
    margin-top: -1px;
    background: #fff;
    }
    #sfm-sidebar .sfm-menu li > a span:before, #sfm-sidebar .sfm-menu li > a span:after{
        background: <?php echo $options['sf_hover_panel_1']; ?>;
        }

    #sfm-sidebar .sfm-menu-level-1 li > a span:before, #sfm-sidebar .sfm-menu-level-1 li > a span:after {
        background: <?php echo $options['sf_hover_panel_2']; ?>;
        }

    #sfm-sidebar .sfm-menu-level-2 li > a span:before, #sfm-sidebar .sfm-menu-level-2 li > a span:after {
        background: <?php echo $options['sf_hover_panel_3']; ?>;
        }
    #sfm-sidebar .sfm-menu-level-3 li > a span:before, #sfm-sidebar .sfm-menu-level-3 li > a span:after {
        background: <?php echo $options['sf_hover_panel_4']; ?>;
        }

    #sfm-sidebar .sfm-menu li > a span:before {
    left: -4px;
    }
    #sfm-sidebar .sfm-menu li > a span:after {
    right: -4px;
    transition: width 0.3s cubic-bezier(0.22, 0.61, 0.36, 1);
    }

    #sfm-sidebar .sfm-menu li:hover > a span:before,
    #sfm-sidebar .sfm-menu li > a:focus span:before {
    width: calc(100% + 8px);
    transition: width 0.3s cubic-bezier(0.22, 0.61, 0.36, 1);
    }

    #sfm-sidebar .sfm-menu li:hover > a span:after,
    #sfm-sidebar .sfm-menu li > a:focus span:after {
    background: transparent;
    width: 100%;
    transition: 0s;
    }
    <?php endif; ?>

    <?php if($options['sf_highlight'] == 'line'): ?>

    #sfm-sidebar .sfm-menu li > a:before {
        background: <?php echo $options['sf_hover_panel_1']; ?>;
    }

    #sfm-sidebar .sfm-menu-level-1 li > a:before {
        background: <?php echo $options['sf_hover_panel_2']; ?>;
    }

    #sfm-sidebar .sfm-menu-level-2 li > a:before {
        background: <?php echo $options['sf_hover_panel_3']; ?>;
    }
    #sfm-sidebar .sfm-menu-level-3 li > a:before {
        background: <?php echo $options['sf_hover_panel_4']; ?>;
    }

    @media only screen and (min-width: 800px) {
        #sfm-sidebar .sfm-menu-level-0 li:hover:before,
        #sfm-sidebar .sfm-menu-level-0 li.sfm-active-item:before,
        #sfm-sidebar .sfm-menu-level-0 li.sfm-active-smooth:before {
            background-color: <?php echo $color_panel_1; ?>;
        }
    }

    #sfm-sidebar .sfm-view-level-1 .sfm-menu li:hover:before,
    #sfm-sidebar .sfm-view-level-1 .sfm-menu li.sfm-active-item:before {
        background-color: <?php echo $color_panel_2; ?>;
    }

    #sfm-sidebar .sfm-view-level-2 .sfm-menu li:hover:before,
    #sfm-sidebar .sfm-view-level-2 .sfm-menu li.sfm-active-item:before {
        background-color: <?php echo $color_panel_3; ?>;
    }

    #sfm-sidebar .sfm-view-level-3 .sfm-menu li:hover:before,
    #sfm-sidebar .sfm-view-level-3 .sfm-menu li.sfm-active-item:before {
        background-color: <?php echo $color_panel_4; ?>;
    }
    <?php endif; ?>
<?php if ($options['sf_hover_style'] === 'text' ): ?>
    #sfm-sidebar .sfm-menu li:hover > a span,
    #sfm-sidebar .sfm-menu li > a:focus span,
    #sfm-sidebar .sfm-menu li:hover > a img,
    #sfm-sidebar .sfm-menu li > a:focus img,
    #sfm-sidebar .sfm-menu li:hover > a .la_icon,
    #sfm-sidebar .sfm-menu li > a:focus .la_icon,
    #sfm-sidebar .sfm-menu li.sfm-submenu-visible > a img,
    #sfm-sidebar .sfm-menu li.sfm-submenu-visible > a .la_icon,
    #sfm-sidebar .sfm-menu li.sfm-submenu-visible > a span {
    right: -2px;
    left: auto;
    }

    .sfm-pos-right .sfm-sub-flyout #sfm-sidebar .sfm-menu li > a:focus span,
    .sfm-pos-right .sfm-sub-flyout #sfm-sidebar .sfm-menu li:hover > a span,
    .sfm-pos-right .sfm-sub-flyout #sfm-sidebar .sfm-menu li:hover > a img,
    .sfm-pos-right .sfm-sub-flyout #sfm-sidebar .sfm-menu li > a:focus img,
    .sfm-pos-right .sfm-sub-flyout #sfm-sidebar .sfm-menu li.sfm-submenu-visible > a img,
    .sfm-pos-right .sfm-sub-flyout #sfm-sidebar .sfm-menu li.sfm-submenu-visible > a span {
    right: 2px;
    left: auto;
    }
<?php endif; ?>
<?php if ($options['sf_hover_style'] === 'arrow' ): ?>
    .sfm-pos-left #sfm-sidebar:not(.sfm-vertical-nav) .sfm-has-child-menu:hover .sfm-sm-indicator,
    .sfm-pos-left #sfm-sidebar:not(.sfm-vertical-nav) .sfm-has-child-menu a:focus .sfm-sm-indicator {
    right: -2px;
    }
    .sfm-pos-right #sfm-sidebar:not(.sfm-vertical-nav) .sfm-has-child-menu:hover .sfm-sm-indicator,
    .sfm-pos-right #sfm-sidebar:not(.sfm-vertical-nav) .sfm-has-child-menu a:focus .sfm-sm-indicator {
    left: -2px;
    }
<?php endif; ?>

<?php if ( /* $options['sf_sidebar_style'] == 'side' && */ $options['sf_alignment'] === 'left' ): ?>
        #sfm-sidebar .sfm-menu li a,
        #sfm-sidebar .sfm-chapter,
        #sfm-sidebar .widget-area,
        .sfm-search-form input {
        padding-left: <?php echo $margin; ?>% !important;
        }

        .sfm-pos-right .sfm-sub-flyout #sfm-sidebar .sfm-menu li a,
        .sfm-pos-right .sfm-sub-flyout #sfm-sidebar .sfm-view .sfm-back-parent,
        .sfm-pos-right .sfm-sub-flyout #sfm-sidebar .sfm-chapter,
        .sfm-pos-right .sfm-sub-flyout #sfm-sidebar .widget-area,
        .sfm-pos-right .sfm-sub-flyout .sfm-search-form input {
        padding-left: <?php echo $margin + 4; ?>% !important;
        }
    <?php endif; ?>
    <?php if ( $options['sf_sidebar_style'] == 'side' && $options['sf_alignment'] === 'left' ): ?>
        #sfm-sidebar .sfm-child-menu.sfm-menu-level-1 li a {
        padding-left: <?php echo $margin + 2; ?>% !important;
        }
        #sfm-sidebar .sfm-child-menu.sfm-menu-level-2 li a {
        padding-left: <?php echo $margin + 4; ?>% !important;
        }
    <?php endif; ?>
<?php if ( /* $options['sf_sidebar_style'] == 'side' && */ $options['sf_alignment'] === 'right' ): ?>
        .sfm-pos-left .sfm-sub-flyout #sfm-sidebar .sfm-menu li a,
        .sfm-pos-left .sfm-sub-flyout #sfm-sidebar .sfm-social,
        .sfm-pos-left .sfm-sub-flyout #sfm-sidebar .sfm-chapter,
        .sfm-pos-left .sfm-sub-flyout #sfm-sidebar .widget-area,
        .sfm-pos-left .sfm-sub-flyout #sfm-sidebar .sfm-view .sfm-back-parent,
        .sfm-pos-left .sfm-sub-flyout .sfm-search-form input {
        padding-right: <?php echo $margin + 6; ?>% !important;
        }
        #sfm-sidebar .sfm-menu li a,
        #sfm-sidebar .sfm-chapter,
        #sfm-sidebar .widget-area,
        .sfm-search-form input {
        padding-right: <?php echo $margin; ?>% !important;
        }
    <?php endif; ?>
<?php if ($options['sf_alignment'] !== 'center'): ?>

        #sfm-sidebar.sfm-compact .sfm-social li {
        text-align: <?php echo $options['sf_alignment']; ?>;
        }

        #sfm-sidebar.sfm-compact .sfm-social:before {
        right: auto;
        left: auto;
        <?php echo $options['sf_alignment']; ?>: 10%;
        }

    <?php endif; ?>
    <?php if ($options['sf_social_align'] == 'left' ): ?>
        #sfm-sidebar .sfm-social {
        padding-left: 7% !important;
        }
    <?php endif; ?>
    <?php if ($options['sf_social_align'] == 'right' ): ?>
        .sfm-pos-left .sfm-sub-flyout #sfm-sidebar .sfm-social {
        padding-right: 13% !important;
        }
        #sfm-sidebar .sfm-social {
        padding-right: 7% !important;
        }
    <?php endif; ?>
    #sfm-sidebar:after {
    display: none !important;
    }

    <?php if ($options['sf_search'] == 'hidden'): ?>
        #sfm-sidebar .search-form {
        display: none !important;
        }
    <?php endif; ?>

<?php if($options['sf_sidebar_behaviour'] == 'push' && ($options['sf_sidebar_style'] == 'side' || $options['sf_sidebar_style'] == 'skew')): ?>
        body.sfm-body-pushed > * {
        -webkit-transform: translate3d(<?php echo $width_panel_23; ?>px,0,0);
        transform: translate3d(<?php echo $width_panel_23; ?>px,0,0);
        }

        .sfm-pos-right .sfm-body-pushed > * {
        -webkit-transform: translate3d(-<?php echo $width_panel_23; ?>px,0,0);
        transform: translate3d(-<?php echo $width_panel_23; ?>px,0,0);
        }
    <?php endif; ?>


    #sfm-sidebar,
    .sfm-pos-right .sfm-sidebar-slide.sfm-body-pushed #sfm-mob-navbar {
    -webkit-transform: translate3d(-<?php echo $panel1_adjusted; ?>px,0,0);
    transform: translate3d(-<?php echo $panel1_adjusted; ?>px,0,0);
    }


    .sfm-pos-right #sfm-sidebar, .sfm-sidebar-slide.sfm-body-pushed #sfm-mob-navbar {
    -webkit-transform: translate3d(<?php echo $panel1_adjusted; ?>px,0,0);
    transform: translate3d(<?php echo $panel1_adjusted; ?>px,0,0);
    }

<?php if($options['sf_sidebar_behaviour'] == 'always' && $options['sf_sidebar_style'] != 'full'): ?>
        @media only screen and (min-width: 800px) {
            #sfm-sidebar {
            -webkit-transform: translate3d(0,0,0);
            transform: translate3d(0,0,0);
            }

            .sfm-pos-left body, .sfm-pos-left #wpadminbar {
            padding-left: <?php echo $panel1_adjusted; ?>px !important;
            box-sizing: border-box !important;
            }

            #sfm-sidebar{
            display:block !important
            }

            .sfm-pos-right body, .sfm-pos-right #wpadminbar {
            padding-right: <?php echo $panel1_adjusted; ?>px !important;
            box-sizing: border-box !important;
            }
        }

        #sfm-sidebar .sfm-rollback {
        /*display: none !important;*/
        }

        body.sfm-body-pushed > * {
        -webkit-transform: translate3d(<?php echo $width_panel_23; ?>px,0,0);
        transform: translate3d(<?php echo $width_panel_23; ?>px,0,0);
        }

        .sfm-pos-right .sfm-body-pushed > * {
        -webkit-transform: translate3d(-<?php echo $width_panel_23; ?>px,0,0);
        transform: translate3d(-<?php echo $width_panel_23; ?>px,0,0);
        }

    <?php endif; ?>


    .sfm-pos-left #sfm-sidebar .sfm-view-level-1 {
    left: <?php echo $width_panel_1; ?>px;
    width: <?php echo $width_panel_2; ?>px;
    -webkit-transform: translate3d(-<?php echo $width_panel_2; ?>px,0,0);
    transform: translate3d(-<?php echo $width_panel_2; ?>px,0,0);
    }

    #sfm-sidebar .sfm-view-level-1 .sfm-menu {
         width: <?php echo $width_panel_2; ?>px;
    }
    #sfm-sidebar .sfm-view-level-2 .sfm-menu {
         width: <?php echo $width_panel_3; ?>px;
    }
    #sfm-sidebar .sfm-view-level-3 .sfm-menu {
         width: <?php echo $width_panel_4; ?>px;
    }

    .sfm-pos-right #sfm-sidebar .sfm-view-level-1 {
    left: auto;
    right: <?php echo $width_panel_1; ?>px;
    width: <?php echo $width_panel_2; ?>px;
    -webkit-transform: translate3d(<?php echo $width_panel_2; ?>px,0,0);
    transform: translate3d(<?php echo $width_panel_2; ?>px,0,0);
    }

    .sfm-pos-left #sfm-sidebar .sfm-view-level-2 {
    left: <?php echo $sum1; ?>px;
    width: <?php echo $width_panel_3; ?>px;
    -webkit-transform: translate3d(-<?php echo $sum2; ?>px,0,0);
    transform: translate3d(-<?php echo $sum2; ?>px,0,0);
    }

    .sfm-pos-right #sfm-sidebar .sfm-view-level-2
    {
    left: auto;
    right: <?php echo $sum1; ?>px;
    width: <?php echo $width_panel_3; ?>px;
    -webkit-transform: translate3d(<?php echo $sum2; ?>px,0,0);
    transform: translate3d(<?php echo $sum2; ?>px,0,0);
    }

    .sfm-pos-left #sfm-sidebar .sfm-view-level-3 {
    left: <?php echo $sum2; ?>px;
    width: <?php echo $width_panel_4; ?>px;
    -webkit-transform: translate3d(-<?php echo $sum2a; ?>px,0,0);
    transform: translate3d(-<?php echo $sum2a; ?>px,0,0);
    }

    .sfm-pos-right #sfm-sidebar .sfm-view-level-3 {
    left: auto;
    right: <?php echo $sum2; ?>px;
    width: <?php echo $width_panel_4; ?>px;
    -webkit-transform: translate3d(<?php echo $sum2; ?>px,0,0);
    transform: translate3d(<?php echo $sum2; ?>px,0,0);
    }

    .sfm-view-pushed-1 #sfm-sidebar .sfm-view-level-2 {
    -webkit-transform: translate3d(-<?php echo $width_panel_3; ?>px,0,0);
    transform: translate3d(-<?php echo $width_panel_3; ?>px,0,0);
    }

    .sfm-pos-right .sfm-view-pushed-1 #sfm-sidebar .sfm-view-level-2 {
    -webkit-transform: translate3d(<?php echo $width_panel_3; ?>px,0,0);
    transform: translate3d(<?php echo $width_panel_3; ?>px,0,0);
    }

    .sfm-view-pushed-2 #sfm-sidebar .sfm-view-level-3 {
    -webkit-transform: translate3d(-<?php echo $width_panel_4; ?>px,0,0);
    transform: translate3d(-<?php echo $width_panel_4; ?>px,0,0);
    }

    .sfm-pos-right .sfm-view-pushed-2 #sfm-sidebar .sfm-view-level-3 {
    -webkit-transform: translate3d(<?php echo $width_panel_4; ?>px,0,0);
    transform: translate3d(<?php echo $width_panel_4; ?>px,0,0);
    }

    .sfm-sub-swipe #sfm-sidebar .sfm-view-level-1,
    .sfm-sub-swipe #sfm-sidebar .sfm-view-level-2,
    .sfm-sub-swipe #sfm-sidebar .sfm-view-level-3,
    .sfm-sub-swipe #sfm-sidebar .sfm-view-level-custom,
    .sfm-sub-dropdown #sfm-sidebar .sfm-view-level-custom {
    left: <?php echo $width_panel_1; ?>px;
    width: <?php echo $width_panel_1; ?>px;
    }

    .sfm-sub-dropdown #sfm-sidebar .sfm-view-level-custom {
    width: <?php echo $width_panel_1; ?>px !important;
    }

    .sfm-sub-swipe #sfm-sidebar .sfm-view-level-custom,
    .sfm-sub-swipe #sfm-sidebar .sfm-view-level-custom .sfm-custom-content,
    .sfm-sub-swipe #sfm-sidebar .sfm-view-level-custom .sfm-content-wrapper {
    width: <?php echo $width_panel_2; ?>px !important;
    }

    .sfm-sub-swipe #sfm-sidebar .sfm-menu {
    width: <?php echo $width_panel_1; ?>px;
    }

    .sfm-sub-swipe.sfm-view-pushed-1 #sfm-sidebar .sfm-view-level-1,
    .sfm-sub-swipe.sfm-view-pushed-2 #sfm-sidebar .sfm-view-level-2,
    .sfm-sub-swipe.sfm-view-pushed-3 #sfm-sidebar .sfm-view-level-3,
    .sfm-sub-dropdown.sfm-view-pushed-custom #sfm-sidebar .sfm-view-level-custom,
    .sfm-sub-swipe.sfm-view-pushed-custom #sfm-sidebar .sfm-view-level-custom {
    -webkit-transform: translate3d(-<?php echo $width_panel_1; ?>px,0,0) !important;
    transform: translate3d(-<?php echo $width_panel_1; ?>px,0,0) !important;
    }

    .sfm-sub-swipe.sfm-view-pushed-1 #sfm-sidebar .sfm-scroll-main,
    .sfm-sub-swipe.sfm-view-pushed-custom #sfm-sidebar .sfm-scroll-main,
    .sfm-sub-dropdown.sfm-view-pushed-custom #sfm-sidebar .sfm-scroll-main {
    -webkit-transform: translate3d(-100%,0,0) !important;
    transform: translate3d(-100%,0,0) !important;
    }

    .sfm-sub-swipe.sfm-view-pushed-2 #sfm-sidebar .sfm-view-level-1,
    .sfm-sub-swipe.sfm-view-pushed-custom #sfm-sidebar .sfm-view-level-1,
    .sfm-sub-swipe.sfm-view-pushed-3 #sfm-sidebar .sfm-view-level-2,
    .sfm-sub-swipe.sfm-view-pushed-custom.sfm-view-pushed-2 #sfm-sidebar .sfm-view-level-2 {
    -webkit-transform: translate3d(-200%,0,0) !important;
    transform: translate3d(-200%,0,0) !important;
    }

    /* custom content */

    .sfm-pos-left .sfm-view-pushed-1.sfm-view-pushed-custom #sfm-sidebar .sfm-view-level-custom {
    right: -<?php echo $width_panel_2; ?>px;
    }
    .sfm-pos-left .sfm-view-pushed-2.sfm-view-pushed-custom #sfm-sidebar .sfm-view-level-custom {
    right: -<?php echo $sum3; ?>px;
    }
    .sfm-pos-left .sfm-view-pushed-3.sfm-view-pushed-custom #sfm-sidebar .sfm-view-level-custom {
    right: -<?php echo $sum4; ?>px;
    }

    .sfm-sub-swipe.sfm-view-pushed-custom #sfm-sidebar .sfm-view-level-custom,
    .sfm-sub-dropdown.sfm-view-pushed-custom #sfm-sidebar .sfm-view-level-custom {
    right: 0;
    }
    .sfm-pos-right .sfm-view-pushed-1.sfm-view-pushed-custom #sfm-sidebar.sfm-sub-flyout .sfm-view-level-custom {
    left: -<?php echo $width_panel_2; ?>px;
    }
    .sfm-pos-right .sfm-view-pushed-2.sfm-view-pushed-custom #sfm-sidebar.sfm-sub-flyout .sfm-view-level-custom {
    left: -<?php echo $sum3; ?>px;
    }
    .sfm-pos-right .sfm-view-pushed-3.sfm-view-pushed-custom #sfm-sidebar.sfm-sub-flyout .sfm-view-level-custom {
    left: -<?php echo $sum4; ?>px;
    }

    .sfm-pos-left .sfm-view-pushed-custom #sfm-sidebar .sfm-view-level-custom {
    transform: translate3d(100%,0,0);
    }
    .sfm-pos-right .sfm-view-pushed-custom #sfm-sidebar .sfm-view-level-custom {
    transform: translate3d(-100%,0,0);
    }


    <?php if($options['sf_sidebar_style'] == 'full'): ?>
        #sfm-sidebar {
        /*z-index: 1;*/
        }
    #sfm-sidebar .sfm-sidebar-bg, #sfm-sidebar .sfm-social {
    background-color: transparent !important;
    }
    /*#sfm-sidebar .sfm-sidebar-bg, #sfm-sidebar .sfm-scroll-wrapper {
        opacity: 0 !important;
        }
        #sfm-sidebar.sfm-video-bg .sfm-sidebar-bg, #sfm-sidebar.sfm-sidebar-exposed .sfm-scroll-wrapper {
        opacity: 1 !important;
        }*/

        #sfm-sidebar .sfm-social {
        background-color: transparent !important;
        }

        #sfm-sidebar .sfm-widget,
        .sfm-menu li a{
        text-align: left !important;
        }

        .sfm-nav form, .sfm-menu li img, #sfm-sidebar .sfm-chapter, .sfm-menu li br,#sfm-sidebar .sfm-menu li:after  {
        display: none !important;
        }

        #sfm-sidebar.sfm-sidebar-exposed, .sfm-sidebar-bg, #sfm-sidebar .sfm-nav,  #sfm-sidebar .sfm-logo, #sfm-sidebar .sfm-social, #sfm-sidebar .sfm-widget {
        width: 100%;
        }
        .sfm-rollback {
        /*z-index: 3000002;*/
        }
        .sfm-sidebar-close:before, .sfm-sidebar-close:after {
        background-color: <?php echo $color_panel_1; ?>;
        }

        #sfm-sidebar {
        opacity: 0 !important;
        visibility: hidden;
        width: 100% !important;
        }

        .sfm-body-pushed #sfm-sidebar, .sfm-ui-shown #sfm-sidebar {
       opacity: 1 !important;
        visibility: visible;
        }

        .sfm-pos-left .sfm-rollback {
        left: 0;
        right: auto;
        }

        .sfm-pos-right .sfm-rollback {
        left: auto;
        right: 0;
        }

         #sfm-overlay {
         display: none;
        }

        .sfm-body-pushed #sfm-overlay, body[class*="sfm-view-pushed"] #sfm-overlay {
        opacity: 1 !important;
        background: transparent !important;
        }
        .sfm-nav-bg_item {
        background-color: <?php echo $options['sf_fade_full']?> !important;
        }
        .sfm-style-full #sfm-sidebar .sfm-logo:before {
        background-color: <?php echo $options['sf_full_head']?> !important;
        }
        .sfm-style-full #sfm-sidebar .sfm-fs-bottom-box:before {
        background-color: <?php echo $options['sf_full_sec']?>;
        }

        .sfm-style-full #sfm-sidebar .sfm-sidebar-close:before,
        .sfm-style-full #sfm-sidebar .sfm-sidebar-close:after {
        background-color: <?php echo $options['sf_full_x']?>;
        }

        #sfm-sidebar .sfm-menu li > a:before {
        /*-webkit-transition: none;
        transition: none;
        background: <?php echo $color_panel_1?>;*/
        }

        #sfm-sidebar .sfm-menu li a {
        padding-left: 15px !important;
        padding-right: 15px !important;
        }

        #sfm-sidebar.sfm-hl-line .sfm-menu li a,
        #sfm-sidebar.sfm-hl-text .sfm-menu li a {
        padding-left: 0px !important;
        padding-right: 0px !important;
        }

    <?php endif; ?>

    #sfm-sidebar .sfm-menu a img{
    max-width: <?php echo $options['sf_icon_size']; ?>px;
    max-height: <?php echo $options['sf_icon_size']; ?>px;
    }
    #sfm-sidebar .sfm-menu .la_icon{
    font-size: <?php echo $options['sf_icon_size']; ?>px;
    min-width: <?php echo $options['sf_icon_size']; ?>px;
    min-height: <?php echo $options['sf_icon_size']; ?>px;
    }

    <?php if($options['sf_ordered'] == 'yes'): ?>
    #sfm-sidebar .sfm-menu li > a span:before {
    content: "0" attr(data-index);
    display: inline-block;
    vertical-align: super;
    font-size: 55%;
    opacity: 0.4;
    margin-right: 5px;
    }
    <?php endif; ?>
    <?php if($options['sf_highlight_active'] == 'yes'): ?>
    @media only screen and (min-width: 800px) {
        #sfm-sidebar .sfm-menu li.sfm-active-class > a:before {
            width: 100%;
        }
    }
    #sfm-sidebar.sfm-hl-line .sfm-menu li.sfm-active-class > a {
        background: rgba(0, 0, 0, 0.15);
    }
    <?php endif; ?>

    #sfm-sidebar .sfm-back-parent {
        background: <?php echo $bg_color_panel_1; ?>;
    }

    #sfm-sidebar .sfm-view-level-1, #sfm-sidebar ul.sfm-menu-level-1 {
        background: <?php echo $bg_color_panel_2; ?>;
    }

    #sfm-sidebar .sfm-view-level-2, #sfm-sidebar ul.sfm-menu-level-2 {
        background: <?php echo $bg_color_panel_3; ?>;
    }

    #sfm-sidebar .sfm-view-level-3, #sfm-sidebar ul.sfm-menu-level-3 {
    background: <?php echo $bg_color_panel_4; ?>;
    }

    #sfm-sidebar .sfm-menu-level-0 li, #sfm-sidebar .sfm-menu-level-0 li a, .sfm-title h3, #sfm-sidebar .sfm-back-parent {
    color: <?php echo $color_panel_1; ?>;
    }

    #sfm-sidebar .sfm-menu li a, #sfm-sidebar .sfm-chapter, #sfm-sidebar .sfm-back-parent {
    padding: <?php echo $options['sf_padding']; ?>px 0;
    text-transform: <?php echo $transform; ?>;
    }
    .sfm-style-full #sfm-sidebar.sfm-hl-line .sfm-menu li > a:before {
    bottom:  <?php echo intval( $options['sf_padding'] ) - 5; ?>px
    }

    #sfm-sidebar .sfm-search-form input[type=text] {
    padding-top:<?php echo $options['sf_padding']; ?>px;
    padding-bottom:<?php echo $options['sf_padding']; ?>px;
    }

    .sfm-sub-swipe #sfm-sidebar .sfm-view .sfm-menu,
    .sfm-sub-swipe .sfm-custom-content,
    .sfm-sub-dropdown .sfm-custom-content {
    padding-top:  <?php echo intval( $options['sf_padding'] ) * 2 + intval( $options['sf_font_size'] ); ?>px;
    }

    #sfm-sidebar .sfm-search-form span {
    top: <?php echo intval($options['sf_padding']) + (intval($options['sf_font_size']) - 16) / 2 + 2; ?>px;
    font-size: <?php echo intval($options['sf_font_size']) - 3; ?>px;
    font-weight: <?php echo $weight; ?>;
    }

    #sfm-sidebar {
    font-family: <?php echo $font; ?>;
    }

    #sfm-sidebar .sfm-sm-indicator {
    line-height: <?php echo $options['sf_font_size'];?>px;
    }

    #sfm-sidebar.sfm-indicators .sfm-sm-indicator i  {
    width: <?php echo $options['sf_ind_s'];?>px;
    height: <?php echo $options['sf_ind_s'];?>px;
    border-top-width: <?php echo $options['sf_ind_st'];?>px;
    border-right-width: <?php echo $options['sf_ind_st'];?>px;
    margin: -<?php echo $ind_mg ;?>px 0 0 -<?php echo $ind_mg;?>px;
    }

    #sfm-sidebar .sfm-search-form input {
    font-size: <?php echo $options['sf_font_size']; ?>px;
    }

    #sfm-sidebar .sfm-menu li a, #sfm-sidebar .sfm-menu .sfm-chapter, #sfm-sidebar .sfm-back-parent {
    font-family: <?php echo $font; ?>;
    font-weight: <?php echo $weight; ?>;
    font-size: <?php echo $options['sf_font_size']; ?>px;
    letter-spacing: <?php echo isset( $options['sf_spacing'] ) ? $options['sf_spacing'] : 0 ; ?>px;
    text-align: <?php echo $options['sf_alignment']; ?>;
    -webkit-font-smoothing: antialiased;
    font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
    }

    #sfm-sidebar .sfm-social-abbr a {
    font-family: <?php echo $font; ?>;
    }
    #sfm-sidebar .sfm-widget,
    #sfm-sidebar .widget-area {
    text-align: <?php echo $options['sf_alignment']; ?>;
    }

    #sfm-sidebar .sfm-social {
    text-align: <?php echo $options['sf_social_align']; ?> !important;
    }

    #sfm-sidebar .sfm-menu .sfm-chapter {
    font-size: <?php echo $options['sf_c_fs']; ?>px;
    margin-top: <?php echo $options['sf_padding']; ?>px;
    font-weight: <?php echo $options['sf_c_weight']; ?>;
    text-transform: <?php echo $options['sf_c_trans'] === 'yes' ? 'uppercase' : 'capitalize'; ?>;
    }
    #sfm-sidebar .sfm-menu .sfm-chapter div{
    font-family: <?php echo $font_c; ?>;
    font-size: <?php echo $options['sf_c_fs']; ?>px;
    }
    <?php if($options['sf_alignment'] == 'center'): ?>
        .sfm-has-child-menu > a {
        padding-right: 0 !important;
        }
        .sfm-pos-right .sfm-has-child-menu > a {
        padding-left: 0 !important;
        }
    <?php endif; ?>
    .sfm-rollback a {
    font-family: <?php echo $font; ?>;
    }
    #sfm-sidebar .sfm-menu .la_icon{
    color: <?php echo $icon_color; ?>;
    }

    #sfm-sidebar .sfm-menu-level-0 li .sfm-sm-indicator i {
    border-color: <?php echo $color_panel_1; ?>;
    }
    #sfm-sidebar .sfm-menu-level-0 .sfm-sl, .sfm-title h2, .sfm-social:after {
    color: <?php echo $options['sf_scolor_panel_1']; ?>;
    }
    #sfm-sidebar .sfm-menu-level-1 li .sfm-sm-indicator i {
    border-color: <?php echo $color_panel_2; ?>;
    }
    #sfm-sidebar .sfm-menu-level-1 .sfm-sl {
    color: <?php echo $options['sf_scolor_panel_2']; ?>;
    }
    #sfm-sidebar .sfm-menu-level-2 li .sfm-sm-indicator i {
    border-color: <?php echo $color_panel_3; ?>;
    }
    #sfm-sidebar .sfm-menu-level-2 .sfm-sl {
    color: <?php echo $options['sf_scolor_panel_3']; ?>;
    }
    #sfm-sidebar .sfm-menu-level-3 li .sfm-sm-indicator i {
    border-color: <?php echo $color_panel_4; ?>;
    }
    #sfm-sidebar .sfm-menu-level-3 .sfm-sl {
    color: <?php echo $options['sf_scolor_panel_4']; ?>;
    }
    .sfm-menu-level-0 .sfm-chapter {
    color: <?php echo $options['sf_chapter_1']; ?> !important;
    }
    .sfm-menu-level-1 .sfm-chapter {
    color: <?php echo $options['sf_chapter_2']; ?> !important;
    }
    .sfm-menu-level-2 .sfm-chapter {
    color: <?php echo $options['sf_chapter_3']; ?> !important;
    }
    .sfm-menu-level-3 .sfm-chapter {
    color: <?php echo $options['sf_chapter_4']; ?> !important;
    }
    #sfm-sidebar .sfm-view-level-1 li a,
    #sfm-sidebar .sfm-menu-level-1 li a{
    color: <?php echo $color_panel_2; ?>;
    border-color: <?php echo $color_panel_2; ?>;
    }

    #sfm-sidebar:after {
    background-color: <?php echo $bg_color_panel_1; ?>;
    }

    #sfm-sidebar .sfm-view-level-2 li a,
    #sfm-sidebar .sfm-menu-level-2 li a{
    color: <?php echo $color_panel_3; ?>;
    border-color: <?php echo $color_panel_3; ?>;
    }

    #sfm-sidebar .sfm-view-level-3 li a,
    #sfm-sidebar .sfm-menu-level-3 li a {
    color: <?php echo $color_panel_4; ?>;
    border-color: <?php echo $color_panel_4; ?>;
    }

    .sfm-navicon-button {
    top: <?php echo $options['sf_label_top'] ?>;
    }
    @media only screen and (max-width: 800px) {
    .sfm-navicon-button {
    top: <?php echo $options['sf_label_top_mobile'] ?>;
    }
    }

    <?php if($options['sf_sidebar_pos'] == 'left'): ?>
        .sfm-navicon-button {
        left: <?php echo $options['sf_label_shift'] ?> !important;
        }
        @media only screen and (max-width: 800px) {
        .sfm-navicon-button {
        left: <?php echo $options['sf_label_shift_m'] ?> !important;
        }
        }
    <?php endif; ?>

    <?php if($options['sf_sidebar_pos'] == 'right'): ?>
        .sfm-navicon-button {
        right: <?php echo $options['sf_label_shift'] ?> !important;
        }
        @media only screen and (max-width: 800px) {
        .sfm-navicon-button {
        right: <?php echo $options['sf_label_shift_m'] ?> !important;
        }
        }
    <?php endif; ?>
    @media only screen and (min-width: 800px) {
        .sfm-pos-left.sfm-bar body, .sfm-pos-left.sfm-bar #wpadminbar {
        padding-left: <?php echo $width_panel_1;?>px !important;
        }
        .sfm-pos-right.sfm-bar body, .sfm-pos-right.sfm-bar #wpadminbar {
        padding-right: <?php echo $width_panel_1;?>px !important;
        }
    }
    .sfm-navicon:after,
    .sfm-label-text .sfm-navicon:after,
    .sfm-label-none .sfm-navicon:after {
    top: -<?php echo $options['sf_label_gaps'] ?>px;
    }
    .sfm-navicon:before,
    .sfm-label-text .sfm-navicon:before,
    .sfm-label-none .sfm-navicon:before {
    top: <?php echo $options['sf_label_gaps'] ?>px;
    }

    .sfm-body-pushed #sfm-overlay, body[class*="sfm-view-pushed"] #sfm-overlay {
    opacity: <?php echo $opacityLevel; ?>;
    }
    <?php if($opacityLevel != 0): ?>
        .sfm-body-pushed #sfm-overlay, body[class*="sfm-view-pushed"] #sfm-overlay {
        cursor: url("<?php echo plugins_url('/img/', __FILE__);?>close3.png") 16 16,pointer;
        }
    <?php endif; ?>

<?php if($options['sf_label_text'] == 'yes'): ?>

        .sfm-navicon-button:after {
        display: block;
        content: "<?php echo esc_html( _e( $options['sf_label_text_field'] ), 'superfly-menu' ); ?>";
        line-height: 18px;
        font-size: 12px;
        font-weight: <?php echo $weight; ?>;
        text-align: center;
        text-decoration: none !important;
        position: absolute;
        left: -50%;
        top: 100%;
        width: 200%;
        margin: 5px 0 0 0;
        color: <?php echo $options['sf_label_text_color']; ?>;
        }

        .sfm-label-none .sfm-navicon-button:after {
        /*margin: -8px 0 0 -5px;*/
        /*left: 100%;*/
        /*top: 50%;*/
        /*width: auto;*/
        }

    <?php endif; ?>

<?php if($options['sf_separators'] == 'yes'): ?>

        #sfm-sidebar .sfm-menu li:after {
        content: '';
        display: block;
        width: <?php echo $options['sf_separators_width'] ?>%;
        box-sizing: border-box;
        position: absolute;
        bottom: 0px;
        left: 0;
        right: 0;
        height: 1px;
        background: <?php echo $options['sf_separators_color']; ?>;
        margin: 0 auto;
        z-index: 0;
        }

        #sfm-sidebar .sfm-menu li:last-child:after {
        display: none;
        }

    <?php endif; ?>

    .sfm-style-skew #sfm-sidebar .sfm-social{
    height: auto;
    /*min-height: 75px;*/
    }
    .sfm-theme-top .sfm-sidebar-bg,
    .sfm-theme-bottom .sfm-sidebar-bg{
    width: <?php echo $width_panel_skew; ?>px;
    }
    /* Pos left */
    .sfm-theme-top .sfm-sidebar-bg{
    -webkit-transform: translate3d(-<?php echo $width_panel_1 - $width_panel_skew_stroke; ?>px,0,0) skewX(-12.05deg);
    transform: translate3d(-<?php echo $width_panel_1 - $width_panel_skew_stroke; ?>px,0,0) skewX(-12.05deg);
    }
    .sfm-theme-bottom .sfm-sidebar-bg{
    -webkit-transform: translate3d(-<?php echo $width_panel_skew - $width_panel_skew_stroke; ?>px,0,0) skewX(12.05deg);
    transform: translate3d(-<?php echo $width_panel_1 - $width_panel_skew_stroke; ?>px,0,0) skewX(12.05deg);
    }
    /* Pos right */
    .sfm-pos-right .sfm-theme-top .sfm-sidebar-bg{
    -webkit-transform: translate3d(-<?php echo $width_panel_skew_stroke;  ?>px,0,0) skewX(12.05deg);
    transform: translate3d(-<?php echo $width_panel_skew_stroke;  ?>px,0,0) skewX(12.05deg);
    }
    .sfm-pos-right .sfm-theme-bottom .sfm-sidebar-bg{
    -webkit-transform: translate3d(-<?php echo $width_panel_skew_stroke; ?>px,0,0) skewX(-12.05deg);
    transform: translate3d(-<?php echo $width_panel_skew_stroke;  ?>px,0,0) skewX(-12.05deg);
    }
    /* exposed */
    .sfm-sidebar-exposed.sfm-theme-top .sfm-sidebar-bg,
    .sfm-sidebar-always .sfm-theme-top .sfm-sidebar-bg{
    -webkit-transform: translate3d(-<?php echo $width_panel_1/3; ?>px,0,0) skewX(-12.05deg);
    transform: translate3d(-<?php echo $width_panel_1/3; ?>px,0,0) skewX(-12.05deg);
    }
    .sfm-pos-right .sfm-sidebar-exposed.sfm-theme-top .sfm-sidebar-bg,
    .sfm-pos-right .sfm-sidebar-always .sfm-theme-top .sfm-sidebar-bg{
    -webkit-transform: translate3d(-<?php echo $width_panel_1; ?>px,0,0) skewX(12.05deg);
    transform: translate3d(-<?php echo $width_panel_1*0.5; ?>px,0,0) skewX(12.05deg);
    }
    .sfm-sidebar-exposed.sfm-theme-bottom .sfm-sidebar-bg,
    .sfm-sidebar-always .sfm-theme-bottom .sfm-sidebar-bg{
    -webkit-transform: translate3d(-<?php echo $width_panel_1/1.5; ?>px,0,0) skewX(12.05deg);
    transform: translate3d(-<?php echo $width_panel_1/1.5; ?>px,0,0) skewX(12.05deg);
    }
    .sfm-pos-right .sfm-sidebar-exposed.sfm-theme-bottom .sfm-sidebar-bg,
    .sfm-pos-right .sfm-sidebar-always .sfm-theme-bottom .sfm-sidebar-bg{
    -webkit-transform: translate3d(-<?php echo $width_panel_1/1.5; ?>px,0,0) skewX(-12.05deg);
    transform: translate3d(-<?php echo $width_panel_1/1.5; ?>px,0,0) skewX(-12.05deg);
    }

    /* Always visible */
    .sfm-sidebar-always.sfm-theme-top .sfm-sidebar-bg{
    -webkit-transform: skewX(-12.05deg);
    transform: skewX(-12.05deg);
    }
    .sfm-pos-right .sfm-sidebar-always.sfm-theme-top .sfm-sidebar-bg{
    -webkit-transform: skewX(12.05deg);
    transform: skewX(12.05deg);
    }
    .sfm-sidebar-always.sfm-theme-bottom .sfm-sidebar-bg{
    -webkit-transform: skewX(-160.65deg);
    transform: skewX(-160.65deg);
    }
    .sfm-pos-right .sfm-sidebar-always.sfm-theme-bottom .sfm-sidebar-bg{
    -webkit-transform: skewX(160.65deg);
    transform: skewX(160.65deg);
    }

    .sfm-navicon,
    .sfm-navicon:after,
    .sfm-navicon:before,
    .sfm-label-metro .sfm-navicon-button,
    #sfm-mob-navbar {
    background-color: <?php echo $options['sf_label_color']; ?>;
    }

    .sfm-label-metro .sfm-navicon,
    #sfm-mob-navbar .sfm-navicon,
    .sfm-label-metro .sfm-navicon:after,
    #sfm-mob-navbar .sfm-navicon:after,
    .sfm-label-metro .sfm-navicon:before,
    #sfm-mob-navbar .sfm-navicon:before  {
    background-color: <?php echo $options['sf_label_icon_color']; ?>;
    }
    .sfm-navicon-button .sf_label_icon{
    color: <?php echo $options['sf_label_icon_color']; ?>;
    }

    .sfm-label-square .sfm-navicon-button,
    .sfm-label-rsquare .sfm-navicon-button,
    .sfm-label-circle .sfm-navicon-button {
    color: <?php echo $options['sf_label_color']; ?>;
    }

    .sfm-navicon-button .sf_label_icon{
    width: <?php echo $label_size; ?>;
    height: <?php echo $label_size; ?>;
    font-size: calc(<?php echo $label_size; ?> * .6);
    }
    .sfm-navicon-button .sf_label_icon.la_icon_manager_custom{
    width: <?php echo $label_size; ?>;
    height: <?php echo $label_size; ?>;
    }
    .sfm-navicon-button.sf_label_default{
    width: <?php echo $label_size; ?>;
    height: <?php echo $label_size; ?>;
    }

    #sfm-sidebar [class*="sfm-icon-"] {
    color: <?php echo $options['sf_social_color']; ?>;
    }

    #sfm-sidebar .sfm-social li {
    border-color: <?php echo $options['sf_social_color']; ?>;
    }

    #sfm-sidebar .sfm-social a:before {
    color: <?php echo $options['sf_social_color']; ?>;
    }

    #sfm-sidebar .sfm-search-form {
    background-color: <?php echo $searchbg; ?>;
    }

    #sfm-sidebar li:hover span[class*='fa-'] {
    opacity: 1 !important;
    }
    <?php if( $options['sf_mob_nav'] === 'yes' ): ?>
        @media screen and (max-width: <?php echo $options['sf_threshold_point'];?>px) {
        #sfm-mob-navbar  {
        display: none;
        height: 62px;
        width: 100%;
        -webkit-backface-visibility: hidden;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 999999;
        text-align: center;
        -webkit-transition: all 0.4s cubic-bezier(0.215, 0.061, 0.355, 1);
        transition: all 0.4s cubic-bezier(0.215, 0.061, 0.355, 1);
        -webkit-transition-delay: .05s;
        transition-delay: .05s;
        }

        .sfm-rollback {
        display: none !important;
        }

        .superfly-on #sfm-mob-navbar  {
        display: block;
        }

        .sfm-mob-nav .sfm-rollback {
        display: none !important;
        }

        .sfm-mob-nav {
        margin-top: 62px !important;
        }

        #sfm-mob-navbar a {
        display: inline-block;
        min-width: 100px;
        }

        #sfm-mob-navbar img {
        height: 50px;
        display: inline-block;
        margin-top: 6px;
        }

        #sfm-mob-navbar .sfm-navicon-button {
        position: absolute;
        left: 0;
        top:0;
        padding: 30px 24px;
        }

        .sfm-pos-right #sfm-mob-navbar .sfm-navicon-button {
        right: 0;
        left: auto;
        }
        .sfm-navicon-button:after {
        font-size: 18px !important;
        }
        }
    <?php endif; ?>
<?php if(isset($options['sf_css'])): ?>
        <?php echo $options['sf_css']; ?>
    <?php endif; ?>
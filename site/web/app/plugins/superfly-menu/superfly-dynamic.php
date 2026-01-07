<?php

$width_panel_1 = !empty($options['sf_width_panel_1']) ? $options['sf_width_panel_1'] : 250;
$width_panel_2 = !empty($options['sf_width_panel_2']) ? $options['sf_width_panel_2'] : 225;
$width_panel_3 = !empty($options['sf_width_panel_3']) ? $options['sf_width_panel_3'] : 250;
$width_panel_4 = !empty($options['sf_width_panel_4']) ? $options['sf_width_panel_4'] : 250;

if ($options['sf_sidebar_style'] == 'toolbar' && !wp_is_mobile()) $width_panel_1 = 100;
if ($options['sf_sub_type'] == 'swipe') $width_panel_2 = $width_panel_3 = $width_panel_4 = $width_panel_1;

$panel1_adjusted = $width_panel_1;

$sum1 = $width_panel_1 + $width_panel_2;
$sum2 = $width_panel_1 + $width_panel_2 + $width_panel_3;
$sum2a = $width_panel_1 + $width_panel_2 + $width_panel_3 + $width_panel_4;
$sum3 = $width_panel_2 + $width_panel_3;
$sum4 = $width_panel_2 + $width_panel_3 + $width_panel_4;

$color_panel_1 = $options['sf_color_panel_1'];
$color_panel_2 = $options['sf_color_panel_2'];
$color_panel_3 = $options['sf_color_panel_3'];
$color_panel_4 = $options['sf_color_panel_4'];
$bg_color_panel_1 = !empty($options['sf_bg_color_panel_1']) ? $options['sf_bg_color_panel_1'] : '#202b2d';
$bg_color_panel_2 = !empty($options['sf_bg_color_panel_2']) ? $options['sf_bg_color_panel_2'] : '#b5b5b5';
$bg_color_panel_3 = !empty($options['sf_bg_color_panel_3']) ? $options['sf_bg_color_panel_3'] : '#36939e';
$bg_color_panel_4 = !empty($options['sf_bg_color_panel_4']) ? $options['sf_bg_color_panel_4'] : '#9e466b';

$icon_color = !empty($options['sf_icon_color']) ? $options['sf_icon_color'] : '#777';

$font = $options['sf_font'];
$font_c = $options['sf_c_font'];
$weight = $options['sf_font_weight'];
$opacityLevel = $options['sf_fade_content'] === 'light' ? 0.6 : ($options['sf_fade_content'] === 'dark' ? 0.9 : 0);

$width_panel_23 = $width_panel_1 / 2;
$width_panel_skew_shift = $width_panel_1;
$width_panel_skew_stroke = 0;
$width_panel_skew = $width_panel_1 * 2;

$margin = isset( $options['sf_padding_h'] ) ? intval( $options['sf_padding_h'] ) : 10;

$label_size = strpos( $options['sf_label_size'], 'px' ) !== false ? $options['sf_label_size'] : $options['sf_label_size'] . 'px';
$line = $options['sf_label_width'];
$transform = $options['sf_uppercase'] === 'yes' ? 'uppercase' : 'capitalize';
$searchbg = $options['sf_search_bg'] === 'light' ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.3)';

if ( isset( $options['sf_ind_s'] ) && isset( $options['sf_ind_st'] ) ) {
    $ind_mg = ( intval( $options['sf_ind_s'] ) + intval( $options['sf_ind_st'] )) / 2;
    $ind_mg -= 1;
} else {
    $ind_mg = 3;
}

?>
<script>

    // global
    window.SFM_is_mobile = (function () {
        var n = navigator.userAgent;
        var reg = new RegExp('Android\s([0-9\.]*)')
        var match = n.toLowerCase().match(reg);
        var android =  match ? parseFloat(match[1]) : false;
        if (android && android < 3.6) {
        	return;
        };

        return n.match(/Android|BlackBerry|IEMobile|iPhone|iPad|iPod|Opera Mini/i);
    })();

    window.SFM_current_page_menu = '<?php echo $current_page_sf_menu; ?>';

    (function(){

        var mob_bar = '<?php echo $options['sf_mob_nav'] === 'yes'?>';
        var pos = '<?php echo $options['sf_sidebar_pos']?>';
        var iconbar = '<?php echo $options['sf_sidebar_style'] == 'toolbar'; ?>';

        var SFM_skew_disabled = ( function( ) {
            var window_width = window.innerWidth;
            var sfm_width = <?php echo $width_panel_1; ?>;
            if ( sfm_width * 2 >= window_width ) {
                return true;
            }
            return false;
        } )( );

        var classes = SFM_is_mobile ? 'sfm-mobile' : 'sfm-desktop';
        var html = document.getElementsByTagName('html')[0]; // pointer
        classes += mob_bar ? ' sfm-mob-nav' : '';
        classes += ' sfm-pos-' + pos;
        classes += iconbar ? ' sfm-bar' : '';
		classes += SFM_skew_disabled ? ' sfm-skew-disabled' : '';

        html.className = html.className == '' ?  classes : html.className + ' ' + classes;

    })();
</script>
<style type="text/css" id="superfly-dynamic">
    @font-face {
        font-family: 'sfm-icomoon';
        src:url('<?php echo plugins_url('/img/', __FILE__);?>fonts/icomoon.eot?wehgh4');
        src: url('<?php echo plugins_url('/img/', __FILE__);?>fonts/icomoon.svg?wehgh4#icomoon') format('svg'),
        url('<?php echo plugins_url('/img/', __FILE__);?>fonts/icomoon.eot?#iefixwehgh4') format('embedded-opentype'),
        url('<?php echo plugins_url('/img/', __FILE__);?>fonts/icomoon.woff?wehgh4') format('woff'),
        url('<?php echo plugins_url('/img/', __FILE__);?>fonts/icomoon.ttf?wehgh4') format('truetype');
        font-weight: normal;
        font-style: normal;
        font-display: swap;
    }
    <?php include_once(dirname(__FILE__) . '/superfly-dynamic-styles.php'); ?>
</style>
<?php if($options['sf_transition'] == 'yes'): ?>
<style type="text/css">
    body {
        display: none;
    }
</style>
<?php endif; ?>
<script>

    ;(function (){
        var insertListener = function(event){
            if (event.animationName == "bodyArrived") {
                afterContentArrived();
            }
        }
        var timer, _timer;

        if (document.addEventListener && false) {
            document.addEventListener("animationstart", insertListener, false); // standard + firefox
            document.addEventListener("MSAnimationStart", insertListener, false); // IE
            document.addEventListener("webkitAnimationStart", insertListener, false); // Chrome + Safari
        } else {
            timer = setInterval(function(){
                if (document.body) { //
                    clearInterval(timer);
                    afterContentArrived();
                }
            },14);
        }

        function afterContentArrived() {
            clearTimeout(_timer);
            var htmlClss;

            if ( window.jQuery && window.jQuery.Deferred ) { // additional check bc of Divi theme
                htmlClss = document.getElementsByTagName('html')[0].className;
                if (htmlClss.indexOf('sfm-pos') === -1) {
                    document.getElementsByTagName('html')[0].className = htmlClss + ' ' + window.SFM_classes;
                }
                jQuery('body').fadeIn();
                jQuery(document).trigger('sfm_doc_body_arrived');
                window.SFM_EVENT_DISPATCHED = true;
            } else {
                _timer = setTimeout(function(){
                    afterContentArrived();
                },14);
            }
        }
    })()
</script>
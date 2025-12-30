<?php

namespace PixelYourSite\Events;

use PixelYourSite;
use PixelYourSite\CustomEvent;
use function PixelYourSite\Ads;
use function PixelYourSite\GA;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderHiddenInput( &$event, $key ) {

	$attr_name = "pys[event][$key]";
	$attr_value = $event->$key;

	?>

	<input type="hidden" name="<?php esc_attr_e( $attr_name ); ?>"
	       value="<?php esc_attr_e( $attr_value ); ?>">

	<?php

}

/**
 * @param CustomEvent $event
 * @param string      $key
 * @param string      $placeholder
 */
function renderTextInput( &$event, $key, $placeholder = '' ) {

	$attr_name = "pys[event][$key]";
	$attr_id = 'pys_event_' . $key;
	$attr_value = $event->$key;

	?>

	<input type="text" name="<?php esc_attr_e( $attr_name ); ?>"
	       id="<?php esc_attr_e( $attr_id ); ?>"
	       value="<?php esc_attr_e( $attr_value ); ?>"
	       placeholder="<?php esc_attr_e( $placeholder ); ?>"
	       class="form-control">

	<?php

}

/**
 * @param CustomEvent $event
 * @param string      $key
 * @param string      $placeholder
 */
function renderNumberInput( &$event, $key, $placeholder = null, $default = null ) {

	$attr_name = "pys[event][$key]";
	$attr_id = 'pys_event_' . $key;
	$attr_value = $event->$key;

	?>

	<input type="number" name="<?php esc_attr_e( $attr_name ); ?>"
	       id="<?php esc_attr_e( $attr_id ); ?>"
	       value="<?php !empty($attr_value) ? esc_attr_e( $attr_value ) : esc_attr_e($default); ?>"
	       placeholder="<?php esc_attr_e( $placeholder ); ?>"
	       min="0" class="form-control">

	<?php

}

/**
 * @param CustomEvent $event
 * @param string      $key
 * @param string      $placeholder
 */
function renderTriggerNumberInput( $trigger, $key, $placeholder = null, $default = null ) {

    $i = $trigger->getTriggerIndex();
	$attr_name = "pys[event][triggers][$i][$key]";
	$attr_id    = 'pys_event_' . $i . '_' . $key;
	$attr_value = $trigger->getParam( $key );

	?>

    <input type="number" name="<?php esc_attr_e( $attr_name ); ?>"
           id="<?php esc_attr_e( $attr_id ); ?>"
           value="<?php !empty($attr_value) ? esc_attr_e( $attr_value ) : esc_attr_e($default); ?>"
           placeholder="<?php esc_attr_e( $placeholder ); ?>"
           min="0" class="form-control">

	<?php

}

/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderSwitcherInput( &$event, $key ) {

    $disabled = false;

	$attr_name  = "pys[event][$key]";
	$attr_id    = 'pys_event_' . $key;
	$attr_value = $event->$key;

	$classes = array( 'custom-switch' );

    if ( $disabled ) {
	    $attr_value = false;
	    $classes[] = 'disabled';
    }

	$classes = implode( ' ', $classes );

	?>

	<div class="<?php esc_attr_e( $classes ); ?>">

		<?php if ( ! $disabled ) : ?>
            <input type="hidden" name="<?php esc_attr_e( $attr_name ); ?>" value="0">
		<?php endif; ?>

		<input type="checkbox" name="<?php esc_attr_e( $attr_name ); ?>" value="1" <?php checked( $attr_value,
			true ); ?> <?php disabled( $disabled, true ); ?>
               id="<?php esc_attr_e( $attr_id ); ?>" class="custom-switch-input">
		<label class="custom-switch-btn" for="<?php esc_attr_e( $attr_id ); ?>"></label>
	</div>

	<?php

}

/**
 * Output checkbox input
 *
 * @param      $key
 * @param      $label
 * @param bool $disabled
 */
function render_checkbox_input( &$event, $key, $label, $disabled = false ) {

    $attr_name  = "pys[event][$key]";
    $attr_value = $event->$key;

    $classes = array( 'custom-control', 'custom-checkbox' );

    if ( $disabled ) {
        $attr_value = false;
        $classes[] = 'disabled';
    }

    $classes = implode( ' ', $classes );

    ?>

    <label class="<?php esc_attr_e( $classes ); ?>">
        <input type="hidden" name="<?php esc_attr_e( $attr_name ); ?>" value="0">
        <input type="checkbox" name="<?php esc_attr_e( $attr_name ); ?>" value="1"
               class="custom-control-input" <?php disabled( $disabled, true ); ?> <?php checked( $attr_value,
            true ); ?> >
        <span class="custom-control-indicator"></span>
        <span class="custom-control-description"><?php echo wp_kses_post( $label ); ?></span>
    </label>

    <?php

}

/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderSwitcherTriggerFormInput( $trigger, $plugin) {

    $i = $trigger->getTriggerIndex();
    $disabled = false;
    $key = $plugin->getSlug();
    $disabled_form_action = $trigger->getParam( 'disabled_form_action' );
    $attr_name = "pys[event][triggers][$i][".$key."][disabled_form_action]";
    $attr_id = 'pys_event_' . $i . '_' . $key . '_disabled_form_action';

    $classes = array( 'custom-switch' );

    if ( $disabled ) {
        $classes[] = 'disabled';
    }

    $classes = implode( ' ', $classes );
    ?>

    <div class="<?php esc_attr_e( $classes ); ?>">

        <?php if ( ! $disabled ) : ?>
            <input type="hidden" name="<?php esc_attr_e( $attr_name ); ?>" value="0">
        <?php endif; ?>

        <input type="checkbox" name="<?php esc_attr_e( $attr_name ); ?>" value="1" <?php checked( $disabled_form_action,
            true ); ?> <?php disabled( $disabled, true ); ?>
               id="<?php esc_attr_e( $attr_id ); ?>" class="custom-switch-input">
        <label class="custom-switch-btn" for="<?php esc_attr_e( $attr_id ); ?>"></label>
    </div>

    <?php

}

function renderSwitcherTriggerInput( $trigger, $key, $disabled = false, $classes = array() ) {

	$i = $trigger->getTriggerIndex();
	$value = $trigger->getParam( $key );
	$attr_name = "pys[event][triggers][$i][$key]";
	$attr_id = 'pys_event_' . $i . '_' . $key;

	$classes[] = 'custom-switch';

	if ( $disabled ) {
		$classes[] = 'disabled';
	}

	$classes = implode( ' ', $classes );
	?>

    <div class="<?php esc_attr_e( $classes ); ?>">

		<?php if ( ! $disabled ) : ?>
            <input type="hidden" name="<?php esc_attr_e( $attr_name ); ?>" value="0">
		<?php endif; ?>

        <input type="checkbox" name="<?php esc_attr_e( $attr_name ); ?>" value="1" <?php checked( $value,
			true ); ?> <?php disabled( $disabled, true ); ?>
               id="<?php esc_attr_e( $attr_id ); ?>" class="custom-switch-input">
        <label class="custom-switch-btn" for="<?php esc_attr_e( $attr_id ); ?>"></label>
    </div>

	<?php

}

/**
 * @param CustomEvent $event
 * @param string      $key
 * @param array       $options
 */
function renderSelectInput( &$event, $key, $options, $full_width = false ,$classes = '') {

	if ( $key == 'currency' ) {
		
		$attr_name  = "pys[event][facebook_params][$key]";
		$attr_id    = 'pys_event_facebook_params_' . $key;
		$attr_value = $event->getFacebookParam( $key );
        
	} else {

		$attr_name  = "pys[event][$key]";
		$attr_id    = 'pys_event_' . $key;
		$attr_value = $event->$key;
    }

	$attr_width = $full_width ? 'width: 100%;' : '';

	?>

	<select class="form-control-sm <?=$classes?>" id="<?php esc_attr_e( $attr_id ); ?>"
	        name="<?php esc_attr_e( $attr_name ); ?>" autocomplete="off" style="<?php esc_attr_e( $attr_width ); ?>">
		<?php foreach ( $options as $option_key => $option_value ) : ?>
			<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key,
				esc_attr( $attr_value ) ); ?> <?php disabled( $option_key,
				'disabled' ); ?>><?php echo esc_attr( $option_value ); ?></option>
		<?php endforeach; ?>
	</select>

	<?php
}

/**
 * @param CustomEvent $event
 * @param string      $key
 * @param array       $options
 */
function renderTriggerSelectInput( $trigger, $key, $options, $full_width = false , $classes = '') {
    $i = $trigger->getTriggerIndex();
    $attr_name  = "pys[event][triggers][$i][$key]";
    $attr_id    = 'pys_event_' . $i . '_' . $key;
    $attr_value = $trigger->getParam($key);

	$attr_width = $full_width ? 'width: 100%;' : '';

	?>

    <select class="form-control-sm <?=$classes?>" id="<?php esc_attr_e( $attr_id ); ?>"
            name="<?php esc_attr_e( $attr_name ); ?>" autocomplete="off" style="<?php esc_attr_e( $attr_width ); ?>">
		<?php foreach ( $options as $option_key => $option_value ) : ?>
            <option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key,
				esc_attr( $attr_value ) ); ?> <?php disabled( $option_key,
				'disabled' ); ?>><?php echo esc_attr( $option_value ); ?></option>
		<?php endforeach; ?>
    </select>

	<?php
}

/**
 * @param CustomEvent $event
 * @param string      $key
 * @param array       $options
 */
function renderGroupSelectInput( &$event, $key, $groups, $full_width = false,$classes = '' ) {

    $attr_name  = "pys[event][$key]";
    $attr_id    = 'pys_event_' . $key;
    $attr_value = $event->$key;

    $attr_width = $full_width ? 'width: 100%;' : '';

    ?>

    <select class="form-control-sm <?=$classes?>" id="<?php esc_attr_e( $attr_id ); ?>"
            name="<?php esc_attr_e( $attr_name ); ?>" autocomplete="off" style="<?php esc_attr_e( $attr_width ); ?>">

        <?php foreach ($groups as $group => $options) :?>
            <optgroup label="<?=$group?>">
                <?php foreach ( $options as $option_key => $option_value ) : ?>
                    <option group="<?=$group?>" value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key,
                        esc_attr( $attr_value ) ); ?> <?php disabled( $option_key,
                        'disabled' ); ?>><?php echo esc_attr( $option_key ); ?></option>
                <?php endforeach; ?>
            </optgroup>
        <?php endforeach; ?>
    </select>

    <?php
}

function render_multi_select_trigger_form_input( $trigger, $plugin, $disabled = false, $placeholder = "", $pysselect2 = true, $classes = '' ) {
	$key = $plugin->getSlug();

	if ( $key === 'elementor_form' ) {
		$data = $trigger->getElementorFormData();
		$values = !empty( $data ) ? array_merge( ...array_map( function ( $item ) {
			return array( $item[ 'id' ] => $item[ 'title' ] );
		}, $data ) ) : array();
	} else {
		$values = $plugin->getForms();
	}

	$forms = $trigger->getForms();

	$i = $trigger->getTriggerIndex();
	$attr_name = "pys[event][triggers][$i][" . $key . "][forms][]";
	$attr_id = 'pys_event_' . $i . '_' . $key;

	?>

    <select class="form-control trigger_form_select <?php echo esc_attr( $classes ); ?> <?php echo $pysselect2 ? "pys-pysselect2" : "" ?>"
            data-placeholder="<?= $placeholder ?>"
            name="<?php esc_attr_e( $attr_name ); ?>"
            id="<?php esc_attr_e( $attr_id ); ?>" <?php disabled( $disabled ); ?> style="width: 100%;"
            multiple>
		<?php foreach ( $values as $option_key => $option_value ) : ?>
            <option value="<?php echo esc_attr( $option_key ); ?>"
				<?php selected( in_array( $option_key, $forms ) ); ?>
				<?php disabled( $option_key, 'disabled' ); ?>
            >
				<?php echo esc_attr( $option_value ) . ' - ID ' . esc_attr( $option_key ); ?>
            </option>
		<?php endforeach; ?>

    </select>

	<?php
}

function render_multi_select_input(&$event, $key, $options, $disabled = false ,$placeholder = "") {


    $attr_name = "pys[event][".$key."][]";
    $attr_id = 'pys_' . $key . '_event';
    $attr_value = (array)$event->$key;
    ?>

    <select class="form-control pys-pysselect2"
            data-placeholder="<?=$placeholder?>"
            name="<?php esc_attr_e( $attr_name ); ?>"
            id="<?php esc_attr_e( $attr_id ); ?>" <?php disabled( $disabled ); ?> style="width: 100%;"
            multiple>
        <?php foreach ( $options as $option_key => $option_value ) : ?>
            <option value="<?php echo esc_attr( $option_key ); ?>"
                <?php selected(  in_array($option_key, $attr_value)  ); ?>
                <?php disabled( $option_key, 'disabled' ); ?>
            >
                <?php echo esc_attr( $option_value ); ?>
            </option>
        <?php endforeach; ?>

    </select>

    <?php
}

function render_multi_select_trigger_input( $trigger, $key, $options, $selected, $disabled = false, $placeholder = "", $classes = '', $pysselect2 = true ) {

    $i = $trigger->getTriggerIndex();
	$attr_name = "pys[event][triggers][$i][" . $key . "][]";
	$attr_id    = 'pys_event_' . $i . '_' . $key;

	if ( !empty( $classes ) ) {
		$classes = ' ' . $classes;
	} else {
		$classes = $pysselect2 ? 'pys-pysselect2' : '';
	}
	?>

    <select class="form-control <?php esc_attr_e( $classes ); ?>"
            data-placeholder="<?= $placeholder ?>"
            name="<?php esc_attr_e( $attr_name ); ?>"
            id="<?php esc_attr_e( $attr_id ); ?>" <?php disabled( $disabled ); ?> style="width: 100%;"
            multiple>
		<?php foreach ( $options as $option_key => $option_value ) : ?>
            <option value="<?php echo esc_attr( $option_key ); ?>"
				<?php selected( in_array( $option_key, $selected ) ); ?>
				<?php disabled( $option_key, 'disabled' ); ?>
            >
				<?php echo esc_attr( $option_value ); ?>
            </option>
		<?php endforeach; ?>
    </select>

	<?php
}

function render_merged_multi_select_input(&$event, $key, $options, $disabled = false ,$placeholder = "") {


    $attr_name = "pys[event][".$key."][]";
    $attr_id = 'pys_' . $key . '_event';
    if($event->google_ads_enabled && $event->google_ads_conversion_id){
        $attr_value = array_merge($event->ga_pixel_id,$event->google_ads_conversion_id);
    }
    else{
        $attr_value = $event->ga_pixel_id;
    }
    ?>

    <select class="form-control pys-pysselect2"
            data-placeholder="<?=$placeholder?>"
            name="<?php esc_attr_e( $attr_name ); ?>"
            id="<?php esc_attr_e( $attr_id ); ?>" <?php disabled( $disabled ); ?> style="width: 100%;"
            multiple>
        <?php foreach ( $options as $option_key => $option_value ) : ?>
            <option value="<?php echo esc_attr( $option_key ); ?>"
                <?php selected(  in_array($option_key, $attr_value)  ); ?>
                <?php disabled( $option_key, 'disabled' ); ?>
            >
                <?php echo esc_attr( $option_value ); ?>
            </option>
        <?php endforeach; ?>

    </select>

    <?php
}
function renderTriggerConditionalNumberPage( $trigger, $key ) {

	$options = array(
		'equal'     => '=',
		'equal_or_larger'    => '>=',
		'equal_or_less'    => '<=',
		'larger'     => '>',
        'less'     => '<',
	);

	renderTriggerSelectInput( $trigger,$key, $options, false );

}

/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderTriggerTypeInput( $trigger, $key ) {

	$options = array(
		'page_visit'        => 'Page visit',
        'home_page'         => 'Home page',
		'number_page_visit' => 'Number of Page Visits',
		'url_click'         => 'Click on HTML link',
		'css_click'         => 'Click on CSS selector',
		'css_mouseover'     => 'Mouse over CSS selector',
		'scroll_pos'        => 'Page Scroll',
		'post_type'         => 'Post type',
		'video_view'        => 'Embedded Video View',
		'email_link'        => 'Email Link',
		//Default event fires
	);

    $eventsFormFactory = apply_filters("pys_form_event_factory",[]);
    foreach ($eventsFormFactory as $activeFormPlugin) :
        $options[$activeFormPlugin->getSlug()] = $activeFormPlugin->getName();
    endforeach;

	asort( $options );

	renderTriggerSelectInput( $trigger, $key, $options, false, 'pys_event_trigger_type' );

}
function renderPostTypeSelect($trigger,  $key) {
    $types = get_post_types(null,"objects ");

    $options = array();
    foreach ($types as $type) {
        $options[$type->name]=$type->label;
    }

	renderTriggerSelectInput( $trigger, $key, $options );
}
/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderCurrencyParamInput( &$event, $key ) {
	

	
	//@since: 7.0.7
    $currencies = apply_filters( 'pys_currencies_list', CustomEvent::$currencies );
	
	$options['']         = 'Please, select...';
	$options             = array_merge( $options, $currencies );
	$options['disabled'] = '';
	$options['custom']   = 'Custom currency';

	renderSelectInput( $event, $key, $options, true );
	
}

/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderFacebookEventTypeInput( &$event, $key ) {
	
	$options = array(
		'ViewContent'          => 'ViewContent',
		'AddToCart'            => 'AddToCart',
		'AddToWishlist'        => 'AddToWishlist',
		'InitiateCheckout'     => 'InitiateCheckout',
		'AddPaymentInfo'       => 'AddPaymentInfo',
		'Purchase'             => 'Purchase',
		'Lead'                 => 'Lead',
		'CompleteRegistration' => 'CompleteRegistration',
		
		'Subscribe'         => 'Subscribe',
		'CustomizeProduct'  => 'CustomizeProduct',
		'FindLocation'      => 'FindLocation',
		'StartTrial'        => 'StartTrial',
		'SubmitApplication' => 'SubmitApplication',
		'Schedule'          => 'Schedule',
		'Contact'           => 'Contact',
		'Donate'            => 'Donate',
		
		'disabled'    => '',
		'CustomEvent' => 'CustomEvent',
	);

	renderSelectInput( $event, $key, $options );
}

/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderTikTokEventTypeInput( &$event, $key ) {



    $attr_name  = "pys[event][$key]";
    $attr_id    = 'pys_event_' . $key;
    $attr_value = esc_attr($event->$key);

    ?>
    <select class="form-control-sm" id="<?php esc_attr_e( $attr_id ); ?>" name="<?php esc_attr_e( $attr_name ); ?>" autocomplete="off">
        <?php foreach ( CustomEvent::$tikTokEvents as $option_key => $option_value ) :
            $value = esc_attr( $option_key ); ?>

            <option data-fields='<?=json_encode($option_value)?>'
                    value="<?=$value ?>" <?php selected( $value, $attr_value ); ?> >
                <?=$value ?>
            </option>

        <?php endforeach; ?>
    </select>
        <?php
}
/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderTikTokEventId( &$event, $key ) {
    $options = array(
        'all'          => 'All pixels',
    );
    $mainPixels = PixelYourSite\Tiktok()->getPixelIDs();

    foreach ($mainPixels as $mainPixel) {
        $options[$mainPixel] = $mainPixel.'(global)';
    }
//    if(PixelYourSite\isSuperPackActive('3.0.0')){
//        $additionalPixels = PixelYourSite\SuperPack()->getFbAdditionalPixel();
//        foreach ($additionalPixels as $aPixel) {
//            $options[$aPixel->pixel] = $aPixel->pixel.'(conditional)';
//        }
//    }
    renderSelectInput( $event, $key, $options );
}
/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderFacebookEventId( &$event, $key ) {
    $options = array(
        'all'          => 'All pixels',
    );
    $mainPixels = PixelYourSite\Facebook()->getPixelIDs();
    foreach ($mainPixels as $mainPixel) {
        $options[$mainPixel] = $mainPixel.'(global)';
    }
    if(PixelYourSite\isSuperPackActive('3.0.0')){
        $additionalPixels = PixelYourSite\SuperPack()->getFbAdditionalPixel();
        foreach ($additionalPixels as $aPixel) {
            $options[$aPixel->pixel] = $aPixel->pixel.'(conditional)';
        }
    }
    render_multi_select_input( $event, $key, $options );
}

/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderMergedGaEventId( &$event, $key) {
    $options = array(
        'all'          => 'All pixels',
    );
    $mainPixels = GA()->getPixelIDs();
    $mainPixelsGAds = Ads()->getPixelIDs();
    $mainPixels = array_merge($mainPixels, $mainPixelsGAds);

    foreach ($mainPixels as $mainPixel) {
        if(strpos($mainPixel, 'UA-') === false){
            $options[$mainPixel] = $mainPixel.' (global)';
        }
        else{
            $options[$mainPixel] = $mainPixel.' (not supported)';
        }
    }
    if(PixelYourSite\isSuperPackActive('3.0.0')){
        $additionalPixels = PixelYourSite\SuperPack()->getGaAdditionalPixel();
        $additionalPixelsGAds = PixelYourSite\SuperPack()->getAdsAdditionalPixel();
        $additionalPixels = array_merge($additionalPixels,$additionalPixelsGAds);
        foreach ($additionalPixels as $aPixel) {
            if(strpos($aPixel->pixel, 'UA-') === false){
                $options[$aPixel->pixel] = $aPixel->pixel.' (conditional)';
            }
            else{
                $options[$aPixel->pixel] = $aPixel->pixel.' (not supported)';
            }
        }
    }
    render_multi_select_input( $event, $key, $options );

}

/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderGaEventId( &$event, $key) {
    $options = array(
        'all'          => 'All pixels',
    );
    $mainPixels = PixelYourSite\GA()->getPixelIDs();
    foreach ($mainPixels as $mainPixel) {
        if(strpos($mainPixel, 'UA-') === false){
            $options[$mainPixel] = $mainPixel.' (global)';
        }
        else{
            $options[$mainPixel] = $mainPixel.' (not supported)';
        }

    }
    if(PixelYourSite\isSuperPackActive('3.0.0')){
        $additionalPixels = PixelYourSite\SuperPack()->getGaAdditionalPixel();

        foreach ($additionalPixels as $aPixel) {
            if(strpos($aPixel->pixel, 'UA-') === false){
                $options[$aPixel->pixel] = $aPixel->pixel.' (conditional)';
            }
            else{
                $options[$aPixel->pixel] = $aPixel->pixel.' (not supported)';
            }
        }
    }
    render_multi_select_input( $event, $key, $options );

}
/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderGTMEventId( &$event, $key) {
    $options = array();
    $mainPixels = PixelYourSite\GTM()->getPixelIDs();
    foreach ($mainPixels as $mainPixel) {
	    if (strpos($mainPixel, 'GTM-') === 0 && strpos($mainPixel, 'GTM-') !== false) {
            $options[$mainPixel] = $mainPixel.' (global)';
        }
        else{
            $options[$mainPixel] = $mainPixel.' (not supported)';
        }

    }

    render_multi_select_input( $event, $key, $options );

}
/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderBingEventId( &$event, $key ) {
    $options = array(
        'all'          => 'All pixels',
    );
    $mainPixels = PixelYourSite\Bing()->getPixelIDs();
    foreach ($mainPixels as $mainPixel) {
        $options[$mainPixel] = $mainPixel.'(global)';
    }

    renderSelectInput( $event, $key, $options );
}


/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderFacebookParamInput( &$event, $key ) {
	
	$attr_name  = "pys[event][facebook_params][$key]";
	$attr_id    = 'pys_event_facebook_' . $key;
	$attr_value = $event->getFacebookParam( $key );
	
	?>

    <input type="text" name="<?php esc_attr_e( $attr_name ); ?>"
           id="<?php esc_attr_e( $attr_id ); ?>"
           value="<?php esc_attr_e( $attr_value ); ?>"
           placeholder="Enter value"
           class="form-control">
	
	<?php
	
}

/**
 * @param CustomEvent $event
 * @param string      $key
 * @param string      $placeholder
 */
function renderMergedGAParamInput( $key, $val ) {

    $attr_name = "pys[event][ga_ads_params][$key]";
    $attr_id = 'pys_event_ga_ads_' . $key;
    $attr_value = $val;

    ?>

    <input type="text" name="<?php esc_attr_e( $attr_name ); ?>"
           id="<?php esc_attr_e( $attr_id ); ?>"
           value="<?php esc_attr_e( $attr_value ); ?>"
           class="form-control">

    <?php

}

/**
 * @param CustomEvent $event
 * @param string      $key
 * @param string      $placeholder
 */
function renderGAParamInput( $key, $val ) {

    $attr_name = "pys[event][ga_params][$key]";
    $attr_id = 'pys_event_ga_' . $key;
    $attr_value = $val;

    ?>

    <input type="text" name="<?php esc_attr_e( $attr_name ); ?>"
           id="<?php esc_attr_e( $attr_id ); ?>"
           value="<?php esc_attr_e( $attr_value ); ?>"
           class="form-control">

    <?php

}
/**
 * @param CustomEvent $event
 * @param string      $key
 * @param string      $placeholder
 */
function renderGTMParamInput( $key, $val ) {

    $attr_name = "pys[event][gtm_params][$key]";
    $attr_id = 'pys_event_gtm_' . $key;
    $attr_value = $val;

    ?>

    <input type="text" name="<?php esc_attr_e( $attr_name ); ?>"
           id="<?php esc_attr_e( $attr_id ); ?>"
           value="<?php esc_attr_e( $attr_value ); ?>"
           class="form-control">

    <?php

}
/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderGoogleAnalyticsMergedActionInput( &$event, $key ) {
    renderGroupSelectInput( $event, $key, $event->GAEvents, false,'action_merged_g4' );
}
/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderGoogleAnalyticsV4ActionInput( &$event, $key ) {
    renderGroupSelectInput( $event, $key, $event->GAEvents, false,'action_g4' );
}
/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderGTMActionInput( &$event, $key ) {
    renderGroupSelectInput( $event, $key, $event->GAEvents, false,'action_gtm' );
}
/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderGoogleAdsActionInput( &$event, $key ) {
	
	$options = array(
		'_custom'             => 'Custom Action',
		'disabled'            => '',
		'add_payment_info'    => 'add_payment_info',
		'add_to_cart'         => 'add_to_cart',
		'add_to_wishlist'     => 'add_to_wishlist',
		'begin_checkout'      => 'begin_checkout',
		'checkout_progress'   => 'checkout_progress',
		'conversion'          => 'conversion',
		'generate_lead'       => 'generate_lead',
		'login'               => 'login',
		'purchase'            => 'purchase',
		'refund'              => 'refund',
		'remove_from_cart'    => 'remove_from_cart',
		'search'              => 'search',
		'select_content'      => 'select_content',
		'set_checkout_option' => 'set_checkout_option',
		'share'               => 'share',
		'sign_up'             => 'sign_up',
		'view_item'           => 'view_item',
		'view_item_list'      => 'view_item_list',
		'view_promotion'      => 'view_promotion',
		'view_search_results' => 'view_search_results',
	);
	
	renderSelectInput( $event, $key, $options, true );
	
}

/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderGoogleAdsConversionID( &$event, $key ) {
	
	$options = array(
        'all'          => 'All pixels',
	);

    foreach (PixelYourSite\Ads()->getPixelIDs() as $mainPixel) {
        $options[$mainPixel] = $mainPixel.'(global)';
    }
    if(PixelYourSite\isSuperPackActive('3.0.0')){
        $additionalPixels = PixelYourSite\SuperPack()->getAdsAdditionalPixel();
        foreach ($additionalPixels as $aPixel) {
            $options[$aPixel->pixel] = $aPixel->pixel.'(conditional)';
        }
    }

    render_multi_select_input( $event, $key, $options );
	
}

/**
 * @param CustomEvent $event
 * @param string      $key
 */
function renderPinterestEventTypeInput( &$event, $key ) {
	
	$options = array(
		'pagevisit'    => 'PageVisit',
		'viewcategory' => 'ViewCategory',
		'search'       => 'Search',
		'addtocart'    => 'AddToCart',
		'checkout'     => 'Checkout',
		'watchvideo'   => 'WatchVideo',
		'signup'       => 'Signup',
		'lead'         => 'Lead',
		'custom'       => 'Custom',
		'disabled'     => '',
		'CustomEvent'  => 'Partner Defined',
	);
	
	renderSelectInput( $event, $key, $options );
	
}

/**
 * Output checkbox input
 *
 * @param $event
 * @param      $key
 * @param      $label
 * @param bool $disabled
 */
function renderTriggerCheckboxInput( $trigger, $key, $label, $disabled = false ) {

    $i = $trigger->getTriggerIndex();
	$attr_name  = "pys[event][triggers][$i][$key]";
	$attr_value = $trigger->getParam( $key );

	?>

    <label class="custom-control custom-checkbox">
        <input type="hidden" name="<?php esc_attr_e( $attr_name ); ?>" value="0">
        <input type="checkbox" name="<?php esc_attr_e( $attr_name ); ?>" value="1"
               class="custom-control-input" <?php disabled( $disabled, true ); ?> <?php checked( $attr_value,
			true ); ?>>
        <span class="custom-control-indicator"></span>
        <span class="custom-control-description"><?php echo wp_kses_post( $label ); ?></span>
    </label>

	<?php
}

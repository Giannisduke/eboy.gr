<?php

namespace PixelYourSite;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function maybeMigrate() {

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}
	
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	$pys_7_version = get_option( 'pys_core_version', false );

	if ( $pys_7_version && version_compare( $pys_7_version, '11.1.0', '<' ) ) {

		migrate_11_1_0();

		update_option( 'pys_core_version', PYS_VERSION );
		update_option( 'pys_updated_at', time() );
	}

    if (!$pys_7_version || ($pys_7_version && version_compare($pys_7_version, '11.0.1', '<'))) {

        migrate_11_0_0();

        update_option( 'pys_core_version', PYS_VERSION );
        update_option( 'pys_updated_at', time() );
    }

	if (!$pys_7_version || ($pys_7_version && version_compare($pys_7_version, '10.2.2', '<'))) {

		migrate_10_2_2();

		update_option( 'pys_core_version', PYS_VERSION );
		update_option( 'pys_updated_at', time() );
	}

	if (!$pys_7_version || ($pys_7_version && version_compare($pys_7_version, '10.1.3', '<'))) {

		migrate_10_1_3();

		update_option( 'pys_core_version', PYS_VERSION );
		update_option( 'pys_updated_at', time() );
	}


	if (!$pys_7_version || ($pys_7_version && version_compare($pys_7_version, '10.1.1', '<'))) {

		migrate_10_1_0();

		update_option( 'pys_core_version', PYS_VERSION );
		update_option( 'pys_updated_at', time() );
	}

    if (!$pys_7_version || ($pys_7_version && version_compare($pys_7_version, '9.11.1.7', '<') && !get_option( 'pys_custom_event_migrate', false ))) {

        migrate_unify_custom_events();

	    update_option( 'pys_core_version', PYS_VERSION );
	    update_option( 'pys_updated_at', time() );
    }

    if ($pys_7_version && version_compare($pys_7_version, '9.0.0', '<') ) {
        migrate_9_0_0();

        update_option( 'pys_core_version', PYS_VERSION );
        update_option( 'pys_updated_at', time() );
    } elseif ($pys_7_version && version_compare($pys_7_version, '8.6.8', '<') ) {
        migrate_8_6_7();

        update_option( 'pys_core_version', PYS_VERSION );
        update_option( 'pys_updated_at', time() );
    } elseif ($pys_7_version && version_compare($pys_7_version, '8.3.1', '<') ) {
        migrate_8_3_1();

        update_option( 'pys_core_version', PYS_VERSION );
        update_option( 'pys_updated_at', time() );
    } elseif ($pys_7_version && version_compare($pys_7_version, '8.0.0', '<') ) {
        migrate_8_0_0();

        update_option( 'pys_core_version', PYS_VERSION );
        update_option( 'pys_updated_at', time() );
    }
}

function migrate_unify_custom_events(){
    foreach (CustomEventFactory::get() as $event) {
            $event->migrateUnifyGA();
    }
	update_option( 'pys_custom_event_migrate', true );
}

function migrate_11_1_0() {

	$facebook_main_pixel = Facebook()->getOption( 'main_pixel_enabled' );
	$facebook_enabled = Facebook()->getOption( 'enabled' );
	Facebook()->updateOptions( array( 'main_pixel_enabled' => $facebook_enabled && $facebook_main_pixel ) );

	$ga_main_pixel = GA()->getOption( 'main_pixel_enabled' );
	$ga_enabled = GA()->getOption( 'enabled' );
	GA()->updateOptions( array( 'main_pixel_enabled' => $ga_enabled && $ga_main_pixel ) );

	$ads_main_pixel = Ads()->getOption( 'main_pixel_enabled' );
	$ads_enabled = Ads()->getOption( 'enabled' );
	Ads()->updateOptions( array( 'main_pixel_enabled' => $ads_enabled && $ads_main_pixel ) );

	$tiktok_main_pixel = Tiktok()->getOption( 'main_pixel_enabled' );
	$tiktok_enabled = Tiktok()->getOption( 'enabled' );
	Tiktok()->updateOptions( array( 'main_pixel_enabled' => $tiktok_enabled && $tiktok_main_pixel ) );

	$gtm_main_pixel = GTM()->getOption( 'main_pixel_enabled' );
	$gtm_enabled = GTM()->getOption( 'enabled' );
	GTM()->updateOptions( array( 'main_pixel_enabled' => $gtm_enabled && $gtm_main_pixel ) );

}

function migrate_11_0_0()
{
    if(GTM()->getOption('gtm_dataLayer_name') === 'dataLayerPYS'){
        GTM()->updateOptions([
            "gtm_dataLayer_name" => 'dataLayer',
        ]);
    }
}
function migrate_10_2_2() {
	if(!PYS()->getOption('block_robot_enabled')){
		$globalOptions = [
			"block_robot_enabled" => true,
		];
		PYS()->updateOptions($globalOptions);
	}
}
function migrate_10_1_3() {
	$ga_tags_woo_options = [];
	$ga_tags_edd_options = [];
	if(GA()->enabled() && Ads()->enabled()){
		$ga_tags_woo_options = [
			'woo_variable_as_simple' => GATags()->getOption('woo_variable_as_simple') ?? Ads()->getOption('woo_variable_as_simple') ?? GA()->getOption('woo_variable_as_simple'),
			'woo_variable_data_select_product' => GATags()->getOption('woo_variable_data_select_product') ?? Ads()->getOption('woo_variable_data_select_product') ?? GA()->getOption('woo_variable_data_select_product'),
			'woo_variations_use_parent_name' => GATags()->getOption('woo_variations_use_parent_name') ?? GA()->getOption('woo_variations_use_parent_name'),
			'woo_content_id' => GATags()->getOption('woo_content_id') ?? Ads()->getOption('woo_content_id') ?? GA()->getOption('woo_content_id'),
			'woo_content_id_prefix' => GATags()->getOption('woo_content_id_prefix') ?? Ads()->getOption('woo_item_id_prefix') ?? GA()->getOption('woo_content_id_prefix'),
			'woo_content_id_suffix' => GATags()->getOption('woo_content_id_suffix') ?? Ads()->getOption('woo_item_id_suffix') ?? GA()->getOption('woo_content_id_suffix'),
		];

		$ga_tags_edd_options = [
			'edd_content_id' => GATags()->getOption('edd_content_id') ?? Ads()->getOption('edd_content_id') ?? GA()->getOption('edd_content_id'),
			'edd_content_id_prefix' => GATags()->getOption('edd_content_id_prefix') ?? Ads()->getOption('edd_content_id_prefix') ?? GA()->getOption('edd_content_id_prefix'),
			'edd_content_id_suffix' => GATags()->getOption('edd_content_id_suffix') ?? Ads()->getOption('edd_content_id_suffix') ?? GA()->getOption('edd_content_id_suffix'),
		];
	}elseif(Ads()->enabled()){
		$ga_tags_woo_options = [
			'woo_variable_as_simple' => GATags()->getOption('woo_variable_as_simple') ?? Ads()->getOption('woo_variable_as_simple'),
			'woo_variable_data_select_product' => GATags()->getOption('woo_variable_data_select_product') ?? Ads()->getOption('woo_variable_data_select_product'),
			'woo_content_id' => GATags()->getOption('woo_content_id') ?? Ads()->getOption('woo_content_id'),
			'woo_content_id_prefix' => GATags()->getOption('woo_content_id_prefix') ?? Ads()->getOption('woo_item_id_prefix'),
			'woo_content_id_suffix' => GATags()->getOption('woo_content_id_suffix') ?? Ads()->getOption('woo_item_id_suffix'),
		];

		$ga_tags_edd_options = [
			'edd_content_id' => GATags()->getOption('edd_content_id') ?? Ads()->getOption('edd_content_id'),
			'edd_content_id_prefix' => GATags()->getOption('edd_content_id_prefix') ?? Ads()->getOption('edd_content_id_prefix'),
			'edd_content_id_suffix' => GATags()->getOption('edd_content_id_suffix') ?? Ads()->getOption('edd_content_id_suffix'),
		];
	}elseif(GA()->enabled()){
		$ga_tags_woo_options = [
			'woo_variable_as_simple' => GATags()->getOption('woo_variable_as_simple') ?? GA()->getOption('woo_variable_as_simple'),
			'woo_variable_data_select_product' => GATags()->getOption('woo_variable_data_select_product') ?? GA()->getOption('woo_variable_data_select_product'),
			'woo_variations_use_parent_name' => GATags()->getOption('woo_variations_use_parent_name') ?? GA()->getOption('woo_variations_use_parent_name'),
			'woo_content_id' => GATags()->getOption('woo_content_id') ?? GA()->getOption('woo_content_id'),
			'woo_content_id_prefix' => GATags()->getOption('woo_content_id_prefix') ?? GA()->getOption('woo_content_id_prefix'),
			'woo_content_id_suffix' => GATags()->getOption('woo_content_id_suffix') ?? GA()->getOption('woo_content_id_suffix'),
		];

		$ga_tags_edd_options = [
			'edd_content_id' => GATags()->getOption('edd_content_id') ?? GA()->getOption('edd_content_id'),
			'edd_content_id_prefix' => GATags()->getOption('edd_content_id_prefix') ?? GA()->getOption('edd_content_id_prefix'),
			'edd_content_id_suffix' => GATags()->getOption('edd_content_id_suffix') ?? GA()->getOption('edd_content_id_suffix'),
		];
	}
	else{
		return false;
	}
	GATags()->updateOptions($ga_tags_woo_options);
	GATags()->updateOptions($ga_tags_edd_options);
}
function migrate_10_1_0() {
	$globalOptions = [
		'woo_purchase_conversion_track' => 'current_event',
		'woo_initiate_checkout_conversion_track' => 'current_event',
		'woo_add_to_cart_conversion_track' => 'current_event',
		'woo_view_content_conversion_track' => 'current_event',
		'woo_view_category_conversion_track' => 'current_event',
		'edd_purchase_conversion_track' => 'current_event',
		'edd_initiate_checkout_conversion_track' => 'current_event',
		'edd_add_to_cart_conversion_track' => 'current_event',
		'edd_view_content_conversion_track' => 'current_event',
		'edd_view_category_conversion_track' => 'current_event',
	];
	Ads()->updateOptions($globalOptions);
}
function migrate_9_0_0() {
    $globalOptions = [
        "automatic_events_enabled" => PYS()->getOption("signal_events_enabled") || PYS()->getOption("automatic_events_enabled"),
        "automatic_event_internal_link_enabled" => PYS()->getOption("signal_click_enabled"),
        "automatic_event_outbound_link_enabled" => PYS()->getOption("signal_click_enabled"),
        "automatic_event_video_enabled" => PYS()->getOption("signal_watch_video_enabled"),
        "automatic_event_tel_link_enabled" => PYS()->getOption("signal_tel_enabled"),
        "automatic_event_email_link_enabled" => PYS()->getOption("signal_email_enabled"),
        "automatic_event_form_enabled" => PYS()->getOption("signal_form_enabled"),
        "automatic_event_download_enabled" => PYS()->getOption("signal_download_enabled"),
        "automatic_event_comment_enabled" => PYS()->getOption("signal_comment_enabled"),
        "automatic_event_scroll_enabled" => PYS()->getOption("signal_page_scroll_enabled"),
        "automatic_event_time_on_page_enabled" => PYS()->getOption("signal_time_on_page_enabled"),
        "automatic_event_scroll_value" => PYS()->getOption("signal_page_scroll_value"),
        "automatic_event_time_on_page_value" => PYS()->getOption("signal_time_on_page_value"),
        "automatic_event_adsense_enabled" => PYS()->getOption("signal_adsense_enabled"),
        "automatic_event_download_extensions" => PYS()->getOption("download_event_extensions"),
    ];
    PYS()->updateOptions($globalOptions);
}

function migrate_8_6_7() {
    if(PYS()->getOption( 'woo_advance_purchase_enabled' ,true)) {
        $globalOptions = array(
            "woo_advance_purchase_fb_enabled"   => true,
            'woo_advance_purchase_ga_enabled'   => true,
        );
    } else {
        $globalOptions = array(
            "woo_advance_purchase_fb_enabled"   => false,
            'woo_advance_purchase_ga_enabled'   => false,
        );
    }



    PYS()->updateOptions($globalOptions);
}

function migrate_8_3_1() {
    $globalOptions = array(
        "enable_page_title_param"          => !PYS()->getOption( 'enable_remove_page_title_param' ,false),
        'enable_content_name_param'        => !PYS()->getOption( 'enable_remove_content_name_param' ,false),
    );

    PYS()->updateOptions($globalOptions);
}

function migrate_8_0_0() {

    $globalOptions = array(
        "signal_click_enabled"          => isEventEnabled( 'click_event_enabled' ),
        "signal_watch_video_enabled"    => isEventEnabled( 'watchvideo_event_enabled' ),
        "signal_adsense_enabled"        => isEventEnabled( 'adsense_enabled' ),
        "signal_form_enabled"           => isEventEnabled( 'form_event_enabled' ),
        "signal_user_signup_enabled"    => isEventEnabled( 'complete_registration_event_enabled' ),
        "signal_download_enabled"       => isEventEnabled( 'download_event_enabled' ),
        "signal_comment_enabled"        => isEventEnabled( 'comment_event_enabled' )
    );

    PYS()->updateOptions($globalOptions);

    $gaOptions = array(
        'woo_view_item_list_enabled' => GA()->getOption('woo_view_category_enabled')
    );
    GA()->updateOptions($gaOptions);
}

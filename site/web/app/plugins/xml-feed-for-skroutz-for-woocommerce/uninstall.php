<?php

/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'dicha_skroutz_feed_generation', [], 'dicha_feeds_generation' );
	as_unschedule_all_actions( 'dicha_skroutz_feed_monitor', [], 'dicha_feeds_generation' );
}

dicha_skroutz_feed_remove_plugin_options();

/**
 * Deletes from database all options.
 */
function dicha_skroutz_feed_remove_plugin_options() {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'dicha_skroutz_feed_%'" );
}

<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options.
delete_option( 'mindcrafts_ai_disabled_tools' );

// If you have multisite enabled, you might need to loop through sites and delete options for each.
if ( is_multisite() ) {
	global $wpdb;
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	$original_blog_id = get_current_blog_id();

	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
		delete_option( 'mindcrafts_ai_disabled_tools' );
	}

	switch_to_blog( $original_blog_id );
}

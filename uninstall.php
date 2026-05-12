<?php
/**
 * Uninstall — runs on plugin deletion.
 * Drops tables, removes options and transients.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop our custom tables.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}eaic_messages" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}eaic_sessions" );

// Remove rate-limit transients.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_eaic_rl_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_eaic_rl_' ) . '%'
	)
);
// phpcs:enable

delete_option( 'eaic_options' );
delete_option( 'eaic_db_version' );

// Clear any object cache.
wp_cache_flush();

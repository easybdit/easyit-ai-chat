<?php
/**
 * Uninstall — runs on plugin deletion.
 * Drops tables, removes options and transients.
 *
 * @package WPEasyAI
 * @since   1.0.0
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpeasyai_messages" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpeasyai_sessions" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_weai_rl_%' OR option_name LIKE '_transient_timeout_weai_rl_%'" );
// phpcs:enable WordPress.DB.DirectDatabaseQuery

delete_option( 'wpeasyai_options' );
delete_option( 'wpeasyai_db_version' );

<?php
/**
 * Uninstall routine — runs when the plugin is deleted from WordPress admin.
 *
 * Drops database tables, removes options and rate-limit transients.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */

// Only run when WordPress triggers an uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop conversation tables.
// phpcs:disable WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}easyit_ai_messages" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}easyit_ai_sessions" );

// Remove rate-limit transients left by the plugin.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_eai_rl_%' OR option_name LIKE '_transient_timeout_eai_rl_%'" );
// phpcs:enable WordPress.DB.DirectDatabaseQuery

// Remove plugin options.
delete_option( 'easyit_ai_chat_options' );
delete_option( 'easyit_ai_chat_db_version' );

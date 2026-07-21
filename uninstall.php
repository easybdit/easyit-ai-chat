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
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}eaic_feedback" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}eaic_chunks" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}eaic_documents" );
// WooCommerce bots (merged in from Pro in v2.4.0).
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}eaic_order_chats" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}eaic_product_chats" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}eaic_return_chats" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}eaic_return_requests" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}eaic_leads" );

// Remove rate-limit and abuse-alert transients.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_eaic_rl_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_eaic_rl_' ) . '%',
		$wpdb->esc_like( '_transient_eaic_abuse_alerted_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_eaic_abuse_alerted_' ) . '%'
	)
);
// phpcs:enable

delete_option( 'eaic_options' );
delete_option( 'eaic_db_version' );
delete_option( 'eaic_order_db_version' );
delete_option( 'eaic_product_db_version' );

// Remove uploaded RAG documents (knowledge-base source files).
$eaic_upload_dir = wp_upload_dir();
$eaic_docs_dir   = trailingslashit( $eaic_upload_dir['basedir'] ) . 'eaic-docs';
if ( is_dir( $eaic_docs_dir ) ) {
	$eaic_doc_files = glob( trailingslashit( $eaic_docs_dir ) . '*' );
	if ( is_array( $eaic_doc_files ) ) {
		foreach ( $eaic_doc_files as $eaic_doc_file ) {
			if ( is_file( $eaic_doc_file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				unlink( $eaic_doc_file );
			}
		}
	}
	// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rmdir_rmdir
	@rmdir( $eaic_docs_dir );
}

// Unschedule the daily purge cron in case the plugin was deleted without
// first being deactivated (deactivation normally already does this).
$eaic_cron_ts = wp_next_scheduled( 'eaic_daily_purge' );
if ( $eaic_cron_ts ) {
	wp_unschedule_event( $eaic_cron_ts, 'eaic_daily_purge' );
}

// Clear any object cache.
wp_cache_flush();

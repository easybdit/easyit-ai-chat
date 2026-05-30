<?php
/**
 * Plugin Name:       EasyIT AI Chat — Chatbot for OpenAI, Claude, DeepSeek, Gemini & Ollama
 * Plugin URI:        https://github.com/easybdit/easyit-ai-chat
 * Description:       Unified AI chatbot for WordPress. Connect Ollama, OpenAI, Anthropic (Claude), DeepSeek and Google Gemini with one shortcode [eaic_chat]. Free, open-source, no tracking.
 * Version:           1.0.6
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            EasyIT
 * Author URI:        https://easyit.com.bd
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       easyit-ai-chat
 *
 * @package EasyIT_AI_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EAIC_VERSION',  '1.0.6' );
define( 'EAIC_FILE',     __FILE__ );
define( 'EAIC_DIR',      plugin_dir_path( __FILE__ ) );
define( 'EAIC_URL',      plugin_dir_url( __FILE__ ) );
define( 'EAIC_BASENAME', plugin_basename( __FILE__ ) );

require_once EAIC_DIR . 'includes/class-eaic-options.php';
require_once EAIC_DIR . 'includes/class-eaic-provider.php';
require_once EAIC_DIR . 'includes/providers/class-eaic-ollama.php';
require_once EAIC_DIR . 'includes/providers/class-eaic-openai.php';
require_once EAIC_DIR . 'includes/providers/class-eaic-anthropic.php';
require_once EAIC_DIR . 'includes/providers/class-eaic-deepseek.php';
require_once EAIC_DIR . 'includes/providers/class-eaic-gemini.php';
require_once EAIC_DIR . 'includes/class-eaic-db.php';
require_once EAIC_DIR . 'includes/class-eaic-engine.php';
require_once EAIC_DIR . 'admin/class-eaic-admin.php';
require_once EAIC_DIR . 'public/class-eaic-public.php';

/**
 * Initialise plugin after all plugins are loaded.
 *
 * Note: load_plugin_textdomain() is intentionally NOT called.
 * Since WordPress 4.6, translations hosted on WordPress.org are loaded
 * automatically via the "just-in-time" mechanism.
 *
 * @since 1.0.0
 * @return void
 */
function eaic_init() {
	new EAIC_Engine();
	new EAIC_Admin();
	new EAIC_Public();
}
add_action( 'plugins_loaded', 'eaic_init' );

/**
 * Activation hook — create tables and schedule the daily data-purge cron.
 *
 * @since 1.0.0
 * @return void
 */
function eaic_activate() {
	EAIC_DB::create_tables();
	update_option( 'eaic_db_version', EAIC_VERSION );
	if ( ! wp_next_scheduled( 'eaic_daily_purge' ) ) {
		wp_schedule_event( time(), 'daily', 'eaic_daily_purge' );
	}
	EAIC_Order_DB::install();
	if ( ! wp_next_scheduled( EAIC_Order_Bot::CRON_HOOK ) ) {
		wp_schedule_event( time(), 'daily', EAIC_Order_Bot::CRON_HOOK );
	}
	EAIC_Product_DB::install();
	if ( ! wp_next_scheduled( EAIC_Product_Bot::CRON_HOOK ) ) {
		wp_schedule_event( time(), 'daily', EAIC_Product_Bot::CRON_HOOK );
	}
}
register_activation_hook( __FILE__, 'eaic_activate' );

// ── Order Status Bot (WooCommerce) ─────────────────────
require_once plugin_dir_path( __FILE__ ) . 'eaic-order-bot/eaic-order-bot.php';

add_action( 'plugins_loaded', function () {
	if ( class_exists( 'WooCommerce' ) ) {
		EAIC_Order_Bot::boot( __FILE__ );
	}
}, 20 );

// ── Product Q&A Bot (WooCommerce) ──────────────────────
require_once plugin_dir_path( __FILE__ ) . 'eaic-product-bot/eaic-product-bot.php';

add_action( 'plugins_loaded', function () {
	if ( class_exists( 'WooCommerce' ) ) {
		EAIC_Product_Bot::boot( __FILE__ );
	}
}, 20 );

add_filter( 'eaic_send_to_provider', function ( $default, $args ) {
	if ( ! class_exists( 'EAIC_Engine' ) ) {
		return $default;
	}
	$engine = new EAIC_Engine();
	return $engine->run_completion( $args['system'], $args['messages'] );
}, 10, 2 );


/**
 * Deactivation hook — remove the scheduled cron event.
 *
 * @since 1.0.4
 * @return void
 */
function eaic_deactivate() {
	$timestamp = wp_next_scheduled( 'eaic_daily_purge' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'eaic_daily_purge' );
	}
	wp_clear_scheduled_hook( EAIC_Order_Bot::CRON_HOOK );
	wp_clear_scheduled_hook( EAIC_Product_Bot::CRON_HOOK );
}
register_deactivation_hook( __FILE__, 'eaic_deactivate' );

/**
 * Daily cron callback — delete sessions older than the configured retention window.
 *
 * @since 1.0.4
 * @return void
 */
function eaic_purge_old_sessions() {
	$days = (int) EAIC_Options::get( 'data_retention_days', 90 );
	EAIC_DB::delete_expired_sessions( $days );
}
add_action( 'eaic_daily_purge', 'eaic_purge_old_sessions' );

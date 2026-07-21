<?php
/**
 * Plugin Name:       EasyIT AI Chat — Chatbot for OpenAI, Claude, DeepSeek, Gemini & Ollama
 * Plugin URI:        https://github.com/easybdit/easyit-ai-chat
 * Description:       Unified AI chatbot for WordPress. Connect Ollama, OpenAI, Anthropic (Claude), DeepSeek, Google Gemini and Together AI with one shortcode [eaic_chat]. Free, open-source, no tracking.
 * Version:           2.4.0
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

define( 'EAIC_VERSION',  '2.4.0' );
define( 'EAIC_FILE',     __FILE__ );
define( 'EAIC_DIR',      plugin_dir_path( __FILE__ ) );
define( 'EAIC_URL',      plugin_dir_url( __FILE__ ) );
define( 'EAIC_BASENAME', plugin_basename( __FILE__ ) );

// Freemius SDK — must load before plugin init.
if ( ! function_exists( 'eaic_fs' ) ) {
	function eaic_fs() {
		global $eaic_fs;
		if ( ! isset( $eaic_fs ) ) {
			require_once EAIC_DIR . 'vendor/freemius/start.php';
			$eaic_fs = fs_dynamic_init( array(
				'id'                  => '30864',
				'slug'                => 'easyit-ai-chat',
				'type'                => 'plugin',
				'public_key'          => 'pk_ee7766daaf807e36db27264276b58',
				'is_premium'          => false,
				'premium_suffix'      => 'Pro',
				// v2.4.0 — every formerly-Pro feature now ships free; nothing left to upsell.
				'has_premium_version' => false,
				'has_addons'          => false,
				'has_paid_plans'      => false,
				'is_org_compliant'    => true,
				'menu'                => array(
					'support' => false,
				),
			) );
		}
		return $eaic_fs;
	}
	eaic_fs();
	do_action( 'eaic_fs_loaded' );
}

require_once EAIC_DIR . 'includes/class-eaic-options.php';
require_once EAIC_DIR . 'includes/class-eaic-provider.php';
require_once EAIC_DIR . 'includes/providers/class-eaic-ollama.php';
require_once EAIC_DIR . 'includes/providers/class-eaic-openai.php';
require_once EAIC_DIR . 'includes/providers/class-eaic-anthropic.php';
require_once EAIC_DIR . 'includes/providers/class-eaic-deepseek.php';
require_once EAIC_DIR . 'includes/providers/class-eaic-gemini.php';
require_once EAIC_DIR . 'includes/providers/class-eaic-together.php';
require_once EAIC_DIR . 'includes/providers/class-eaic-custom.php';
require_once EAIC_DIR . 'includes/class-eaic-db.php';
require_once EAIC_DIR . 'includes/class-eaic-rag-db.php';
require_once EAIC_DIR . 'includes/class-eaic-rag.php';
require_once EAIC_DIR . 'includes/class-eaic-engine.php';
require_once EAIC_DIR . 'includes/class-eaic-lead-db.php';
require_once EAIC_DIR . 'includes/class-eaic-lead-ajax.php';
require_once EAIC_DIR . 'admin/class-eaic-admin.php';
require_once EAIC_DIR . 'public/class-eaic-public.php';

/**
 * Load the WooCommerce-dependent bot modules (merged in from Pro in v2.4.0).
 * Guarded by class_exists('WooCommerce') exactly like the old Pro bootstrap did —
 * none of these modules re-check WooCommerce availability internally.
 *
 * @since 2.4.0
 * @return void
 */
function eaic_load_woocommerce_bots() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	require_once EAIC_DIR . 'eaic-order-bot/eaic-order-bot.php';
	require_once EAIC_DIR . 'eaic-product-bot/eaic-product-bot.php';
	require_once EAIC_DIR . 'eaic-return-bot/eaic-return-bot.php';
	require_once EAIC_DIR . 'eaic-product-finder/eaic-product-finder.php';

	EAIC_Order_Bot::boot( EAIC_FILE );
	EAIC_Product_Bot::boot( EAIC_FILE );
	// Return Bot and Product Finder self-register their AJAX handler + shortcode
	// on require (matches how they worked in the Pro plugin) — no boot() call needed.

	if ( is_admin() ) {
		require_once EAIC_DIR . 'eaic-store-intelligence/eaic-store-intelligence.php';
	}
}
add_action( 'plugins_loaded', 'eaic_load_woocommerce_bots', 20 );

/**
 * Initialise plugin after all plugins are loaded.
 *
 * @since 1.0.0
 * @return void
 */
function eaic_init() {
	if ( get_option( 'eaic_db_version' ) !== EAIC_VERSION ) {
		EAIC_DB::create_tables();
		EAIC_Lead_DB::install();
		if ( class_exists( 'EAIC_Order_DB' ) )  { EAIC_Order_DB::install(); }
		if ( class_exists( 'EAIC_Product_DB' ) ) { EAIC_Product_DB::install(); }
		if ( class_exists( 'EAIC_Return_DB' ) )  { EAIC_Return_DB::create_tables(); }
		update_option( 'eaic_db_version', EAIC_VERSION );
	}
	EAIC_Lead_Ajax::init();
	new EAIC_Engine();
	new EAIC_Admin();
	new EAIC_Public();
}
add_action( 'plugins_loaded', 'eaic_init', 21 );

/**
 * Activation hook — create tables and schedule the daily data-purge cron.
 *
 * @since 1.0.0
 * @return void
 */
function eaic_activate() {
	EAIC_DB::create_tables();
	EAIC_Lead_DB::install();

	// The WooCommerce bot DB classes are only required by eaic_load_woocommerce_bots()
	// on 'plugins_loaded', which hasn't run yet during activation — require them directly.
	if ( class_exists( 'WooCommerce' ) ) {
		require_once EAIC_DIR . 'eaic-order-bot/includes/class-eaic-order-db.php';
		require_once EAIC_DIR . 'eaic-product-bot/includes/class-eaic-product-db.php';
		require_once EAIC_DIR . 'eaic-return-bot/includes/class-eaic-return-db.php';
		EAIC_Order_DB::install();
		EAIC_Product_DB::install();
		EAIC_Return_DB::create_tables();
	}

	update_option( 'eaic_db_version', EAIC_VERSION );
	if ( ! wp_next_scheduled( 'eaic_daily_purge' ) ) {
		wp_schedule_event( time(), 'daily', 'eaic_daily_purge' );
	}
}
register_activation_hook( __FILE__, 'eaic_activate' );

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
}
register_deactivation_hook( __FILE__, 'eaic_deactivate' );

/**
 * Daily cron callback — delete sessions older than the configured retention window.
 *
 * Also purges the WooCommerce bot chat-log tables (Order/Product/Return) merged in
 * from Pro, using the same single retention setting instead of separate cron events.
 * Return *requests* (the actual return records, not the chat log) are intentionally
 * left un-purged — they're a permanent audit trail.
 *
 * @since 1.0.4
 * @return void
 */
function eaic_purge_old_sessions() {
	$days = (int) EAIC_Options::get( 'data_retention_days', 90 );
	EAIC_DB::delete_expired_sessions( $days );

	if ( class_exists( 'EAIC_Order_DB' ) )  { EAIC_Order_DB::purge_old(); }
	if ( class_exists( 'EAIC_Product_DB' ) ) { EAIC_Product_DB::purge_old(); }
	if ( class_exists( 'EAIC_Return_DB' ) )  { EAIC_Return_DB::purge_old( $days ); }
}
add_action( 'eaic_daily_purge', 'eaic_purge_old_sessions' );

// Order/Product bot purge_old() read their retention window via filters rather than
// a parameter — route both to the same eaic_options['data_retention_days'] setting.
add_filter( 'eaic_log_retention_days', function () {
	return (int) EAIC_Options::get( 'data_retention_days', 90 );
} );
add_filter( 'eaic_product_log_retention_days', function () {
	return (int) EAIC_Options::get( 'data_retention_days', 90 );
} );
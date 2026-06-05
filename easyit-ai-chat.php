<?php
/**
 * Plugin Name:       EasyIT AI Chat — Chatbot for OpenAI, Claude, DeepSeek, Gemini & Ollama
 * Plugin URI:        https://github.com/easybdit/easyit-ai-chat
 * Description:       Unified AI chatbot for WordPress. Connect Ollama, OpenAI, Anthropic (Claude), DeepSeek, Google Gemini and Together AI with one shortcode [eaic_chat]. Free, open-source, no tracking.
 * Version:           2.3.4
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

define( 'EAIC_VERSION',  '2.3.4' );
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
				'has_premium_version' => true,
				'has_addons'          => false,
				'has_paid_plans'      => true,
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
require_once EAIC_DIR . 'admin/class-eaic-admin.php';
require_once EAIC_DIR . 'public/class-eaic-public.php';

/**
 * Initialise plugin after all plugins are loaded.
 *
 * @since 1.0.0
 * @return void
 */
function eaic_init() {
	if ( get_option( 'eaic_db_version' ) !== EAIC_VERSION ) {
		EAIC_DB::create_tables();
		update_option( 'eaic_db_version', EAIC_VERSION );
	}
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
 * @since 1.0.4
 * @return void
 */
function eaic_purge_old_sessions() {
	$days = (int) EAIC_Options::get( 'data_retention_days', 90 );
	EAIC_DB::delete_expired_sessions( $days );
}
add_action( 'eaic_daily_purge', 'eaic_purge_old_sessions' );
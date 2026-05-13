<?php
/**
 * Plugin Name:       EasyIT AI Chat
 * Plugin URI:        https://github.com/easybdit/easyit-ai-chat
 * Description:       Unified AI chatbot connector for WordPress. Configure your own self-hosted or third-party AI model API and embed it with one shortcode [easyai]. Free, open source, no tracking.
 * Version:           1.0.3
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

define( 'EAIC_VERSION',  '1.0.3' );
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
 * Activation hook — create database tables.
 *
 * @since 1.0.0
 * @return void
 */
function eaic_activate() {
	EAIC_DB::create_tables();
	update_option( 'eaic_db_version', EAIC_VERSION );
}
register_activation_hook( __FILE__, 'eaic_activate' );

/**
 * Run dbDelta on plugin upgrade so schema changes (e.g. ENUM -> VARCHAR)
 * are applied to existing installations.
 *
 * @since 1.0.3
 * @return void
 */
function eaic_maybe_upgrade_db() {
	$installed = get_option( 'eaic_db_version', '' );
	if ( $installed !== EAIC_VERSION ) {
		EAIC_DB::create_tables();
		update_option( 'eaic_db_version', EAIC_VERSION );
	}
}
add_action( 'plugins_loaded', 'eaic_maybe_upgrade_db', 5 );

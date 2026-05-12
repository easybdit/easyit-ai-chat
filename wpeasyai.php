<?php
/**
 * Plugin Name:       WP Easy AI Chat
 * Plugin URI:        https://github.com/easybdit/wpeasyai
 * Description:       Unified AI chatbot for WordPress. Connect Ollama, OpenAI, Anthropic (Claude) and DeepSeek with one shortcode [easyai]. Free, open-source, no tracking.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            EasyIT
 * Author URI:        https://easyit.com.bd
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpeasyai
 * Domain Path:       /languages
 *
 * @package WPEasyAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPEASYAI_VERSION',  '1.0.0' );
define( 'WPEASYAI_FILE',     __FILE__ );
define( 'WPEASYAI_DIR',      plugin_dir_path( __FILE__ ) );
define( 'WPEASYAI_URL',      plugin_dir_url( __FILE__ ) );
define( 'WPEASYAI_BASENAME', plugin_basename( __FILE__ ) );

require_once WPEASYAI_DIR . 'includes/class-wpeasyai-options.php';
require_once WPEASYAI_DIR . 'includes/class-wpeasyai-provider.php';
require_once WPEASYAI_DIR . 'includes/providers/class-wpeasyai-ollama.php';
require_once WPEASYAI_DIR . 'includes/providers/class-wpeasyai-openai.php';
require_once WPEASYAI_DIR . 'includes/providers/class-wpeasyai-anthropic.php';
require_once WPEASYAI_DIR . 'includes/providers/class-wpeasyai-deepseek.php';
require_once WPEASYAI_DIR . 'includes/class-wpeasyai-db.php';
require_once WPEASYAI_DIR . 'includes/class-wpeasyai-engine.php';
require_once WPEASYAI_DIR . 'admin/class-wpeasyai-admin.php';
require_once WPEASYAI_DIR . 'public/class-wpeasyai-public.php';

/**
 * Initialise plugin after all plugins are loaded.
 *
 * @since 1.0.0
 */
function wpeasyai_init(): void {
	load_plugin_textdomain(
		'wpeasyai',
		false,
		dirname( WPEASYAI_BASENAME ) . '/languages'
	);
	new WPEasyAI_Engine();
	new WPEasyAI_Admin();
	new WPEasyAI_Public();
}
add_action( 'plugins_loaded', 'wpeasyai_init' );

/**
 * Activation hook — create database tables.
 *
 * @since 1.0.0
 */
function wpeasyai_activate(): void {
	WPEasyAI_DB::create_tables();
	update_option( 'wpeasyai_db_version', '1.0.0' );
}
register_activation_hook( __FILE__, 'wpeasyai_activate' );

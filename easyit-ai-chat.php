<?php
/**
 * Plugin Name:       EasyIT AI Chat
 * Plugin URI:        https://github.com/easybdit/easyit-ai-chat
 * Description:       Unified AI chatbot for WordPress. Connect Ollama, OpenAI, Anthropic (Claude), and DeepSeek from one simple shortcode. Free, open-source, no tracking.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            EasyIT
 * Author URI:        https://github.com/easybdit
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       easyit-ai-chat
 * Domain Path:       /languages
 *
 * @package EasyIT_AI_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'EASYIT_AI_CHAT_VERSION',  '1.0.0' );
define( 'EASYIT_AI_CHAT_FILE',     __FILE__ );
define( 'EASYIT_AI_CHAT_DIR',      plugin_dir_path( __FILE__ ) );
define( 'EASYIT_AI_CHAT_URL',      plugin_dir_url( __FILE__ ) );
define( 'EASYIT_AI_CHAT_BASENAME', plugin_basename( __FILE__ ) );

// Autoload classes.
require_once EASYIT_AI_CHAT_DIR . 'includes/class-easyit-ai-chat-options.php';
require_once EASYIT_AI_CHAT_DIR . 'includes/class-easyit-ai-chat-provider.php';
require_once EASYIT_AI_CHAT_DIR . 'includes/providers/class-easyit-ai-chat-ollama.php';
require_once EASYIT_AI_CHAT_DIR . 'includes/providers/class-easyit-ai-chat-openai.php';
require_once EASYIT_AI_CHAT_DIR . 'includes/providers/class-easyit-ai-chat-anthropic.php';
require_once EASYIT_AI_CHAT_DIR . 'includes/providers/class-easyit-ai-chat-deepseek.php';
require_once EASYIT_AI_CHAT_DIR . 'includes/class-easyit-ai-chat-db.php';
require_once EASYIT_AI_CHAT_DIR . 'includes/class-easyit-ai-chat-engine.php';
require_once EASYIT_AI_CHAT_DIR . 'admin/class-easyit-ai-chat-admin.php';
require_once EASYIT_AI_CHAT_DIR . 'public/class-easyit-ai-chat-public.php';

/**
 * Initialise plugin after all plugins are loaded.
 *
 * @since 1.0.0
 */
function easyit_ai_chat_init(): void {
	// Load plugin textdomain for translations.
	load_plugin_textdomain(
		'easyit-ai-chat',
		false,
		dirname( EASYIT_AI_CHAT_BASENAME ) . '/languages'
	);

	new EasyIT_AI_Chat_Engine();
	new EasyIT_AI_Chat_Admin();
	new EasyIT_AI_Chat_Public();
}
add_action( 'plugins_loaded', 'easyit_ai_chat_init' );

/**
 * Activation hook — create database tables.
 *
 * @since 1.0.0
 */
function easyit_ai_chat_activate(): void {
	EasyIT_AI_Chat_DB::create_tables();
	// Store DB version for future upgrade routines.
	update_option( 'easyit_ai_chat_db_version', '1.0.0' );
}
register_activation_hook( __FILE__, 'easyit_ai_chat_activate' );

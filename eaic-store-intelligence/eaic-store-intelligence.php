<?php
/**
 * Store Intelligence module loader.
 *
 * Adds "Ask Your Store" admin page under EasyIT AI Chat menu.
 *
 * @package EasyIT_AI_Chat
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/includes/class-eaic-store-ai.php';
require_once __DIR__ . '/includes/class-eaic-store-ajax.php';

new EAIC_Store_Ajax();

add_action( 'admin_menu', 'eaic_store_add_menu', 30 );
add_action( 'admin_enqueue_scripts', 'eaic_store_enqueue' );

function eaic_store_add_menu() {
	add_submenu_page(
		'eaic',
		__( 'Ask Your Store', 'easyit-ai-chat' ),
		__( '🏪 Ask Your Store', 'easyit-ai-chat' ),
		'manage_woocommerce',
		'eaic-store-bot',
		'eaic_store_render_page'
	);
}

function eaic_store_render_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
	require __DIR__ . '/views/store-bot-page.php';
}

function eaic_store_enqueue( $hook ) {
	if ( 'easyit-ai-chat_page_eaic-store-bot' !== $hook && 'eaic_page_eaic-store-bot' !== $hook ) { return; }

	wp_enqueue_style(
		'eaic-store-bot',
		plugins_url( 'assets/store-bot.css', __FILE__ ),
		array(),
		EAIC_VERSION
	);
	wp_enqueue_script(
		'eaic-store-bot',
		plugins_url( 'assets/store-bot.js', __FILE__ ),
		array(),
		EAIC_VERSION,
		true
	);
	wp_localize_script( 'eaic-store-bot', 'EAICStorBot', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'eaic_store_nonce' ),
		'i18n'     => array(
			'greeting' => __( "Hello! 👋 I'm your store assistant. Ask me anything about your WooCommerce store — sales, orders, products, customers.", 'easyit-ai-chat' ),
			'error'    => __( 'Something went wrong. Please try again.', 'easyit-ai-chat' ),
		),
	) );
}

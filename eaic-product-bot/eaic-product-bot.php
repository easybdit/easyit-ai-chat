<?php
/**
 * Product Q&A Bot — Module loader.
 *
 * Usage in main plugin file:
 *   require_once EAIC_DIR . 'eaic-product-bot/eaic-product-bot.php';
 *   add_action( 'plugins_loaded', function () {
 *       if ( class_exists( 'WooCommerce' ) ) {
 *           EAIC_Product_Bot::boot( EAIC_FILE );
 *       }
 *   }, 20 );
 *
 * Shortcode: [eaic_product_chat] or [eaic_product_chat product_id="42"]
 *
 * @package EasyIT_AI_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-eaic-product-db.php';
require_once __DIR__ . '/includes/class-eaic-product-ai.php';
require_once __DIR__ . '/includes/class-eaic-product-ajax.php';

class EAIC_Product_Bot {

	const CRON_HOOK = 'eaic_product_purge_logs';

	public static function boot( $plugin_file ) {
		add_action(
			'init',
			function () {
				if ( ! class_exists( 'WooCommerce' ) ) {
					return;
				}
				EAIC_Product_Ajax::init();
				add_shortcode( 'eaic_product_chat', array( __CLASS__, 'shortcode' ) );
			}
		);

		add_action( self::CRON_HOOK, array( 'EAIC_Product_DB', 'purge_old' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function assets() {
		wp_register_style(
			'eaic-product-bot',
			plugins_url( 'assets/product-bot.css', __FILE__ ),
			array(),
			'1.0.0'
		);

		wp_register_script(
			'eaic-product-bot',
			plugins_url( 'assets/product-bot.js', __FILE__ ),
			array(),
			'1.0.0',
			true
		);

		wp_localize_script(
			'eaic-product-bot',
			'EAIC_PRODUCT',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( EAIC_Product_Ajax::NONCE_ACTION ),
				'session_id' => wp_generate_uuid4(),
				'product_id' => 0,
			)
		);
	}

	/**
	 * [eaic_product_chat] or [eaic_product_chat product_id="42"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML.
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'product_id' => 0 ), $atts, 'eaic_product_chat' );
		$pid  = absint( $atts['product_id'] );

		wp_enqueue_style( 'eaic-product-bot' );
		wp_enqueue_script( 'eaic-product-bot' );

		// Override product_id in JS if set via shortcode attribute.
		if ( $pid ) {
			wp_add_inline_script(
				'eaic-product-bot',
				'if(window.EAIC_PRODUCT){window.EAIC_PRODUCT.product_id=' . $pid . ';}',
				'after'
			);
		}

		ob_start();
		?>
		<div class="eaic-product-bot" id="eaic-product-bot">
			<div class="eaic-pb-header">
				<div class="eaic-pb-header-icon">🛍️</div>
				<div>
					<div class="eaic-pb-header-title"><?php esc_html_e( 'Product Q&A', 'easyit-ai-chat' ); ?></div>
					<div class="eaic-pb-header-sub"><?php esc_html_e( 'Powered by AI', 'easyit-ai-chat' ); ?></div>
				</div>
			</div>
			<div class="eaic-pb-log" id="eaic-product-bot-log" aria-live="polite"></div>
			<div class="eaic-pb-input-area">
				<div class="eaic-pb-input-wrap">
					<input type="text" id="eaic-product-msg" autocomplete="off"
						placeholder="<?php esc_attr_e( 'Ask about any product…', 'easyit-ai-chat' ); ?>" />
					<button type="button" id="eaic-product-send" class="eaic-pb-send-btn" aria-label="<?php esc_attr_e( 'Send', 'easyit-ai-chat' ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
					</button>
				</div>
				<p class="eaic-pb-hint"><?php esc_html_e( 'Press Enter or click Send to chat.', 'easyit-ai-chat' ); ?></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

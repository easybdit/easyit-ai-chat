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
			'1.0.5'
		);

		wp_register_script(
			'eaic-product-bot',
			plugins_url( 'assets/product-bot.js', __FILE__ ),
			array(),
			'1.0.5',
			true
		);

		$current_user = wp_get_current_user();
		$opts         = EAIC_Options::all();

		wp_localize_script(
			'eaic-product-bot',
			'EAIC_PRODUCT',
			array(
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( EAIC_Product_Ajax::NONCE_ACTION ),
				'lead_nonce'       => wp_create_nonce( 'eaic_save_lead' ),
				'is_logged_in'     => $current_user->exists() ? '1' : '',
				'user_name'        => $current_user->exists() ? $current_user->display_name : '',
				'lead_form_enabled'=> ! empty( $opts['lead_form_enabled'] ) ? '1' : '',
				'lead_form_title'  => ! empty( $opts['lead_form_title'] ) ? $opts['lead_form_title'] : 'Quick intro',
				'lead_form_sub'    => ! empty( $opts['lead_form_sub'] ) ? $opts['lead_form_sub'] : 'Optional — helps us personalise your experience',
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

		$opts  = EAIC_Options::all();
		$title = ! empty( $opts['product_bot_title'] ) ? $opts['product_bot_title'] : __( 'Product Q&A', 'easyit-ai-chat' );
		$sub   = ! empty( $opts['powered_by_text'] )   ? $opts['powered_by_text']   : __( 'Powered by EasyIT', 'easyit-ai-chat' );

		ob_start();
		?>
		<div class="eaic-product-bot" data-product-id="<?php echo esc_attr( $pid ); ?>">
			<div class="eaic-pb-header">
				<div class="eaic-pb-header-icon">🛍️</div>
				<div>
					<div class="eaic-pb-header-title"><?php echo esc_html( $title ); ?></div>
					<div class="eaic-pb-header-sub"><?php echo esc_html( $sub ); ?></div>
				</div>
			</div>
			<div class="eaic-pb-log" aria-live="polite"></div>
			<div class="eaic-pb-input-area">
				<div class="eaic-pb-input-wrap">
					<input type="text" class="eaic-pb-msg-input" autocomplete="off"
						placeholder="<?php esc_attr_e( 'Ask about any product…', 'easyit-ai-chat' ); ?>" />
					<button type="button" class="eaic-pb-send-btn" aria-label="<?php esc_attr_e( 'Send', 'easyit-ai-chat' ); ?>">
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

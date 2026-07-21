<?php
/**
 * Order Status Bot — Module loader.
 *
 * Include this from your main plugin file (Pro build only):
 *   require_once __DIR__ . '/order-bot/eaic-order-bot.php';
 *   EAIC_Order_Bot::boot( __FILE__ );
 *
 * @package EasyIT_AI_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-eaic-order-db.php';
require_once __DIR__ . '/includes/class-eaic-order-verify.php';
require_once __DIR__ . '/includes/class-eaic-order-ai.php';
require_once __DIR__ . '/includes/class-eaic-order-ajax.php';

class EAIC_Order_Bot {

	const CRON_HOOK = 'eaic_order_purge_logs';

	/**
	 * Wire everything up.
	 *
	 * @param string $plugin_file Path to the main plugin file (for activation hook).
	 */
	public static function boot( $plugin_file ) {
		// WooCommerce required.
	add_action(
    'init',
    function () {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }
        EAIC_Order_Ajax::init();
        add_shortcode( 'eaic_order_chat', array( __CLASS__, 'shortcode' ) );
    }
);

		add_action( self::CRON_HOOK, array( 'EAIC_Order_DB', 'purge_old' ) );

		// Enqueue front-end assets where the shortcode is used.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Front-end script + style; the nonce/session it needs.
	 */
	public static function assets() {
		// Only register; actual enqueue happens in the shortcode.
		wp_register_style(
			'eaic-order-bot',
			plugins_url( 'assets/order-bot.css', __FILE__ ),
			array(),
			'1.0.4'
		);

		wp_register_script(
			'eaic-order-bot',
			plugins_url( 'assets/order-bot.js', __FILE__ ),
			array(),
			'1.0.4',
			true
		);

		$current_user = wp_get_current_user();

		wp_localize_script(
			'eaic-order-bot',
			'EAIC_ORDER',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( EAIC_Order_Ajax::NONCE_ACTION ),
				'lead_nonce'  => wp_create_nonce( 'eaic_save_lead' ),
				'is_logged_in'=> $current_user->exists() ? '1' : '',
				'user_name'   => $current_user->exists() ? $current_user->display_name : '',
			)
		);
	}

	/**
	 * [eaic_order_chat] — renders the chat widget container.
	 */
	public static function shortcode() {
		wp_enqueue_style( 'eaic-order-bot' );
		wp_enqueue_script( 'eaic-order-bot' );

		$opts  = EAIC_Options::all();
		$title = ! empty( $opts['order_bot_title'] ) ? $opts['order_bot_title'] : __( 'Order Status', 'easyit-ai-chat' );
		$sub   = ! empty( $opts['powered_by_text'] ) ? $opts['powered_by_text'] : __( 'Powered by EasyIT', 'easyit-ai-chat' );

		ob_start();
		?>
		<div class="eaic-order-bot">
			<div class="eaic-ob-header">
				<div class="eaic-ob-header-icon">📦</div>
				<div>
					<div class="eaic-ob-header-title"><?php echo esc_html( $title ); ?></div>
					<div class="eaic-ob-header-sub"><?php echo esc_html( $sub ); ?></div>
				</div>
			</div>
			<div class="eaic-ob-verify">
				<input type="number" class="eaic-ob-order-id" min="1"
					placeholder="<?php esc_attr_e( 'Order #', 'easyit-ai-chat' ); ?>" />
				<input type="email" class="eaic-ob-order-email"
					placeholder="<?php esc_attr_e( 'Email on the order', 'easyit-ai-chat' ); ?>" />
			</div>
			<div class="eaic-ob-log" aria-live="polite"></div>
			<div class="eaic-ob-input-area">
				<div class="eaic-ob-input-wrap">
					<input type="text" class="eaic-ob-msg-input" autocomplete="off"
						placeholder="<?php esc_attr_e( 'Ask about your order…', 'easyit-ai-chat' ); ?>" />
					<button type="button" class="eaic-ob-send-btn" aria-label="<?php esc_attr_e( 'Send', 'easyit-ai-chat' ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
					</button>
				</div>
				<p class="eaic-ob-hint"><?php esc_html_e( 'Press Enter or click Send to chat.', 'easyit-ai-chat' ); ?></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

<?php
/**
 * Return Bot module loader.
 *
 * Shortcode: [eaic_return_chat]
 * Optional attr: order_id="123"
 *
 * @package EasyIT_AI_Chat
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/includes/class-eaic-return-db.php';
require_once __DIR__ . '/includes/class-eaic-return-ai.php';
require_once __DIR__ . '/includes/class-eaic-return-ajax.php';

new EAIC_Return_Ajax();

add_shortcode( 'eaic_return_chat', 'eaic_return_chat_shortcode' );

/**
 * Render the return chat widget.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function eaic_return_chat_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'order_id' => 0 ), $atts, 'eaic_return_chat' );
	$order_id = absint( $atts['order_id'] );

	// Auto-detect from WC endpoint.
	if ( ! $order_id && function_exists( 'is_wc_endpoint_url' ) ) {
		if ( is_wc_endpoint_url( 'view-order' ) ) {
			$order_id = absint( get_query_var( 'view-order' ) );
		}
	}

	// Check if logged-in user has orders to return.
	$user_orders = array();
	if ( ! $order_id && is_user_logged_in() ) {
		$user_orders = wc_get_orders( array(
			'customer_id' => get_current_user_id(),
			'status'      => array( 'completed', 'processing' ),
			'limit'       => 5,
		) );
	}

	wp_enqueue_style(
		'eaic-return-bot',
		plugins_url( 'assets/return-bot.css', __FILE__ ),
		array(),
		EAIC_VERSION
	);
	wp_enqueue_script(
		'eaic-return-bot',
		plugins_url( 'assets/return-bot.js', __FILE__ ),
		array(),
		EAIC_VERSION,
		true
	);
	wp_localize_script( 'eaic-return-bot', 'EAICReturnBot', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'eaic_return_nonce' ),
		'i18n'     => array(
			'greeting'      => __( 'Hello! I can help you with a return. What would you like to return and why?', 'easyit-ai-chat' ),
			'ask_order'     => __( 'Hello! To start your return, please enter your order number below.', 'easyit-ai-chat' ),
			'order_found'   => __( 'Got it! Please tell me which item(s) you want to return and why.', 'easyit-ai-chat' ),
			'invalid_order' => __( 'Please enter a valid order number.', 'easyit-ai-chat' ),
			'submitted'     => __( 'Return Request Submitted!', 'easyit-ai-chat' ),
			'ref'           => __( 'Reference', 'easyit-ai-chat' ),
			'error'         => __( 'Something went wrong. Please try again.', 'easyit-ai-chat' ),
		),
	) );

	ob_start();
	?>
	<div class="eaic-return-bot">
		<div class="eaic-return-widget" data-order-id="<?php echo esc_attr( (string) $order_id ); ?>">

			<div class="eaic-return-header">
				<div class="eaic-return-header-icon">↩️</div>
				<div>
					<div class="eaic-return-header-title"><?php esc_html_e( 'Returns & Refunds', 'easyit-ai-chat' ); ?></div>
					<div class="eaic-return-header-sub"><?php esc_html_e( 'AI-powered return assistant', 'easyit-ai-chat' ); ?></div>
				</div>
			</div>

			<?php if ( ! $order_id && ! empty( $user_orders ) ) : ?>
			<div style="padding:10px 14px;background:#fff;border:1px solid #e5e7eb;border-top:none">
				<p style="font-size:12px;color:#6b7280;margin:0 0 8px"><?php esc_html_e( 'Quick select a recent order:', 'easyit-ai-chat' ); ?></p>
				<div style="display:flex;flex-wrap:wrap;gap:6px">
					<?php foreach ( $user_orders as $uo ) : ?>
					<button type="button" class="eaic-return-order-quick"
						data-order="<?php echo esc_attr( $uo->get_id() ); ?>"
						style="padding:4px 12px;border:1px solid #7c3aed;border-radius:16px;background:#fff;color:#7c3aed;font-size:12px;cursor:pointer">
						#<?php echo esc_html( $uo->get_order_number() ); ?>
					</button>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<div class="eaic-return-messages">
				<!-- Messages injected by JS -->
			</div>

			<div class="eaic-return-input-area">
				<?php if ( $order_id ) : ?>
				<div class="eaic-return-order-badge" style="display:inline-flex">
					📦 <?php echo esc_html( sprintf( __( 'Order #%s', 'easyit-ai-chat' ), $order_id ) ); ?>
				</div>
				<?php else : ?>
				<div class="eaic-return-order-row">
					<input type="text" class="eaic-return-order-input"
						placeholder="<?php esc_attr_e( 'Enter order number…', 'easyit-ai-chat' ); ?>">
					<?php if ( ! is_user_logged_in() ) : ?>
					<input type="email" class="eaic-return-order-email" autocomplete="email"
						placeholder="<?php esc_attr_e( 'Email on the order…', 'easyit-ai-chat' ); ?>">
					<?php endif; ?>
					<button type="button" class="eaic-return-order-btn">
						<?php esc_html_e( 'Look up', 'easyit-ai-chat' ); ?>
					</button>
				</div>
				<div class="eaic-return-order-badge" style="display:none"></div>
				<?php endif; ?>

				<div class="eaic-return-input-wrap" <?php if ( ! $order_id ) echo 'style="display:none"'; ?>>
					<textarea class="eaic-return-input" rows="1"
						placeholder="<?php esc_attr_e( 'Describe your return request…', 'easyit-ai-chat' ); ?>"></textarea>
					<button type="button" class="eaic-return-send" disabled>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
					</button>
				</div>
			</div>

		</div>
	</div>
	<?php
	return ob_get_clean();
}

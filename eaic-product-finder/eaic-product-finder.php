<?php
/**
 * Smart Product Finder module loader.
 *
 * Shortcode: [eaic_product_finder]
 * Attrs: suggestions="Red dress,Gift for men,Budget laptop"
 *
 * @package EasyIT_AI_Chat
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/includes/class-eaic-finder-ai.php';
require_once __DIR__ . '/includes/class-eaic-finder-ajax.php';

new EAIC_Finder_Ajax();

add_shortcode( 'eaic_product_finder', 'eaic_product_finder_shortcode' );

/**
 * Render the product finder widget.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function eaic_product_finder_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'title'       => '',
		'placeholder' => '',
		'suggestions' => '',
	), $atts, 'eaic_product_finder' );

	$title       = sanitize_text_field( $atts['title'] ) ?: __( 'Find Your Product', 'easyit-ai-chat' );
	$placeholder = sanitize_text_field( $atts['placeholder'] ) ?: __( 'E.g. "Blue dress under $50" or "Gift for my dad"…', 'easyit-ai-chat' );
	$suggestions = array_filter( array_map( 'sanitize_text_field', explode( ',', $atts['suggestions'] ) ) );

	wp_enqueue_style(
		'eaic-product-finder',
		plugins_url( 'assets/product-finder.css', __FILE__ ),
		array(),
		EAIC_VERSION
	);
	wp_enqueue_script(
		'eaic-product-finder',
		plugins_url( 'assets/product-finder.js', __FILE__ ),
		array(),
		EAIC_VERSION,
		true
	);
	wp_localize_script( 'eaic-product-finder', 'EAICProductFinder', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'eaic_finder_nonce' ),
		'i18n'     => array(
			'greeting'  => __( "Hi! Tell me what you're looking for and I'll find the best products for you. 🛍️", 'easyit-ai-chat' ),
			'add_cart'  => __( 'Add to Cart', 'easyit-ai-chat' ),
			'out_stock' => __( 'Out of Stock', 'easyit-ai-chat' ),
			'error'     => __( 'Something went wrong. Please try again.', 'easyit-ai-chat' ),
		),
	) );

	ob_start();
	?>
	<div class="eaic-finder-bot">
		<div class="eaic-finder-widget">

			<div class="eaic-finder-header">
				<div class="eaic-finder-header-icon">🔍</div>
				<div>
					<div class="eaic-finder-header-title"><?php echo esc_html( $title ); ?></div>
					<div class="eaic-finder-header-sub"><?php esc_html_e( 'AI-powered product search', 'easyit-ai-chat' ); ?></div>
				</div>
			</div>

			<div class="eaic-finder-messages">
				<!-- Messages injected by JS -->
			</div>

			<div class="eaic-finder-input-area">
				<?php if ( ! empty( $suggestions ) ) : ?>
				<div class="eaic-finder-chips">
					<?php foreach ( $suggestions as $s ) : ?>
					<button type="button" class="eaic-finder-chip"><?php echo esc_html( $s ); ?></button>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
				<div class="eaic-finder-input-wrap">
					<textarea class="eaic-finder-input" rows="1"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"></textarea>
					<button type="button" class="eaic-finder-send" disabled>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
					</button>
				</div>
			</div>

		</div>
	</div>
	<?php
	return ob_get_clean();
}

<?php
/**
 * Admin view: Test Chat page.
 * All styles live in admin/assets/admin.css — no inline CSS here.
 *
 * @package WPEasyAI
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! is_array( $opts ) ) $opts = WPEasyAI_Options::defaults();
?>
<div class="wrap weai-test-page">

	<div class="weai-test-hero">
		<div class="weai-test-hero-left">
			<div class="weai-test-hero-icon">💬</div>
			<div>
				<h1><?php esc_html_e( 'Test Chat', 'wpeasyai' ); ?></h1>
				<p><?php esc_html_e( 'Test your AI providers directly from the dashboard', 'wpeasyai' ); ?></p>
			</div>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpeasyai' ) ); ?>" class="weai-back-btn">
			&larr; <?php esc_html_e( 'Settings', 'wpeasyai' ); ?>
		</a>
	</div>

	<?php echo do_shortcode( '[easyai height="540"]' ); ?>

</div>

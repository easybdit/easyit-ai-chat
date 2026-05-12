<?php if ( ! defined( 'ABSPATH' ) ) exit; if ( ! is_array( $opts ) ) $opts = WPEasyAI_Options::defaults(); ?>
<style>
.weai-test-page { max-width: 1000px; }
.weai-test-hero {
	display: flex; align-items: center; justify-content: space-between;
	background: linear-gradient(135deg, #0f3460 0%, #1a1a2e 100%);
	border-radius: 12px; padding: 18px 24px; margin-bottom: 20px; gap: 12px;
	box-shadow: 0 4px 20px rgba(15,52,96,.3);
}
.weai-test-hero-left { display: flex; align-items: center; gap: 14px; }
.weai-test-hero-icon {
	width: 46px; height: 46px; background: rgba(255,255,255,.12); border-radius: 10px;
	display: flex; align-items: center; justify-content: center; font-size: 22px;
}
.weai-test-hero h1 { color: #fff; font-size: 18px; font-weight: 700; margin: 0; }
.weai-test-hero p  { color: rgba(255,255,255,.6); font-size: 12px; margin: 2px 0 0; }
.weai-back-btn {
	display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px;
	background: rgba(255,255,255,.1); color: #fff; border: 1px solid rgba(255,255,255,.2);
	border-radius: 7px; text-decoration: none; font-size: 13px; font-weight: 500;
	transition: background .15s; white-space: nowrap;
}
.weai-back-btn:hover { background: rgba(255,255,255,.18); color: #fff; }
</style>

<div class="wrap weai-test-page">

<div class="weai-test-hero">
	<div class="weai-test-hero-left">
		<div class="weai-test-hero-icon">💬</div>
		<div>
			<h1>Test Chat</h1>
			<p>Test your AI providers directly from the dashboard</p>
		</div>
	</div>
	<a href="<?php echo admin_url('admin.php?page=wpeasyai'); ?>" class="weai-back-btn">
		← Settings
	</a>
</div>

<?php echo do_shortcode( '[wpeasyai height="540"]' ); ?>

</div>

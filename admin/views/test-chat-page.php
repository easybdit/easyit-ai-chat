<?php if ( ! defined( 'ABSPATH' ) ) exit; if ( ! is_array( $opts ) ) $opts = EasyIT_AI_Chat_Options::defaults(); ?>
<style>
.eai-test-page { max-width: 1000px; }
.eai-test-hero {
	display: flex; align-items: center; justify-content: space-between;
	background: linear-gradient(135deg, #0f3460 0%, #1a1a2e 100%);
	border-radius: 12px; padding: 18px 24px; margin-bottom: 20px; gap: 12px;
	box-shadow: 0 4px 20px rgba(15,52,96,.3);
}
.eai-test-hero-left { display: flex; align-items: center; gap: 14px; }
.eai-test-hero-icon {
	width: 46px; height: 46px; background: rgba(255,255,255,.12); border-radius: 10px;
	display: flex; align-items: center; justify-content: center; font-size: 22px;
}
.eai-test-hero h1 { color: #fff; font-size: 18px; font-weight: 700; margin: 0; }
.eai-test-hero p  { color: rgba(255,255,255,.6); font-size: 12px; margin: 2px 0 0; }
.eai-back-btn {
	display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px;
	background: rgba(255,255,255,.1); color: #fff; border: 1px solid rgba(255,255,255,.2);
	border-radius: 7px; text-decoration: none; font-size: 13px; font-weight: 500;
	transition: background .15s; white-space: nowrap;
}
.eai-back-btn:hover { background: rgba(255,255,255,.18); color: #fff; }
</style>

<div class="wrap eai-test-page">

<div class="eai-test-hero">
	<div class="eai-test-hero-left">
		<div class="eai-test-hero-icon">💬</div>
		<div>
			<h1>Test Chat</h1>
			<p>Test your AI providers directly from the dashboard</p>
		</div>
	</div>
	<a href="<?php echo admin_url('admin.php?page=easyit-ai-chat'); ?>" class="eai-back-btn">
		← Settings
	</a>
</div>

<?php echo do_shortcode( '[easyit_ai_chat height="540"]' ); ?>

</div>

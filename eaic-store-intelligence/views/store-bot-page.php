<?php
/**
 * Admin view: Store Intelligence Bot page.
 *
 * @package EasyIT_AI_Chat
 * @since   1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap">
	<div class="eaic-store-bot-wrap">

		<div class="eaic-store-hero">
			<div class="eaic-store-hero-icon">🏪</div>
			<div>
				<div class="eaic-store-hero-title"><?php esc_html_e( 'Ask Your Store', 'easyit-ai-chat' ); ?></div>
				<div class="eaic-store-hero-sub"><?php esc_html_e( 'Ask anything about your WooCommerce store in plain language — powered by AI.', 'easyit-ai-chat' ); ?></div>
			</div>
		</div>

		<div class="eaic-store-chat">
			<div class="eaic-store-messages"></div>

			<div class="eaic-store-input-area">
				<div class="eaic-store-quick-btns">
					<button type="button" class="eaic-store-quick-btn" data-q="<?php esc_attr_e( "What's my revenue today?", 'easyit-ai-chat' ); ?>"><?php esc_html_e( "💰 Today's revenue", 'easyit-ai-chat' ); ?></button>
					<button type="button" class="eaic-store-quick-btn" data-q="<?php esc_attr_e( 'How many orders are pending?', 'easyit-ai-chat' ); ?>"><?php esc_html_e( '📦 Pending orders', 'easyit-ai-chat' ); ?></button>
					<button type="button" class="eaic-store-quick-btn" data-q="<?php esc_attr_e( 'What are my top selling products this month?', 'easyit-ai-chat' ); ?>"><?php esc_html_e( '🔥 Top sellers', 'easyit-ai-chat' ); ?></button>
					<button type="button" class="eaic-store-quick-btn" data-q="<?php esc_attr_e( 'Which products are low on stock?', 'easyit-ai-chat' ); ?>"><?php esc_html_e( '⚠️ Low stock', 'easyit-ai-chat' ); ?></button>
					<button type="button" class="eaic-store-quick-btn" data-q="<?php esc_attr_e( 'Show me a store summary', 'easyit-ai-chat' ); ?>"><?php esc_html_e( '📊 Store summary', 'easyit-ai-chat' ); ?></button>
					<button type="button" class="eaic-store-quick-btn" data-q="<?php esc_attr_e( "How many new customers this week?", 'easyit-ai-chat' ); ?>"><?php esc_html_e( '👥 New customers', 'easyit-ai-chat' ); ?></button>
				</div>
				<div class="eaic-store-input-wrap">
					<textarea class="eaic-store-input" rows="1"
						placeholder="<?php esc_attr_e( "Ask your store anything… e.g. 'What's my revenue this month?'", 'easyit-ai-chat' ); ?>"></textarea>
					<button type="button" class="eaic-store-send" disabled>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
					</button>
				</div>
			</div>
		</div>

	</div>
</div>

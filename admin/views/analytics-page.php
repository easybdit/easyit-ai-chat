<?php
/**
 * Admin view: Analytics page.
 *
 * Variables in scope (provided by EAIC_Admin::render_analytics()):
 *
 * @var array $eaic_stats  Aggregate stats from EAIC_DB::get_stats().
 * @var array $eaic_daily  Messages per day from EAIC_DB::get_messages_per_day().
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.18
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eaic_max_daily = $eaic_daily ? max( array_values( $eaic_daily ) ) : 0;

$eaic_provider_labels = array(
	'ollama'    => '🦙 Ollama',
	'openai'    => '✨ OpenAI',
	'anthropic' => '🎭 Anthropic',
	'deepseek'  => '🔍 DeepSeek',
	'gemini'    => '✦ Gemini',
);
$eaic_top = isset( $eaic_provider_labels[ $eaic_stats['top_provider'] ] )
	? $eaic_provider_labels[ $eaic_stats['top_provider'] ]
	: esc_html( $eaic_stats['top_provider'] );
?>
<div class="wrap eaic-settings-wrap">

	<div class="eaic-hero">
		<div class="eaic-hero-left">
			<div class="eaic-hero-icon">📊</div>
			<div>
				<div class="eaic-hero-title"><?php esc_html_e( 'Analytics', 'easyit-ai-chat' ); ?></div>
				<div class="eaic-hero-sub"><?php esc_html_e( 'Chat usage stats — all data from your own database, no external tracking.', 'easyit-ai-chat' ); ?></div>
			</div>
		</div>
		<div class="eaic-hero-badge">v<?php echo esc_html( EAIC_VERSION ); ?></div>
	</div>

	<!-- Stat cards -->
	<div class="eaic-stats-grid">

		<div class="eaic-stat-card">
			<div class="eaic-stat-icon">💬</div>
			<div class="eaic-stat-number"><?php echo esc_html( number_format_i18n( $eaic_stats['total_sessions'] ) ); ?></div>
			<div class="eaic-stat-label"><?php esc_html_e( 'Total Conversations', 'easyit-ai-chat' ); ?></div>
		</div>

		<div class="eaic-stat-card">
			<div class="eaic-stat-icon">✉️</div>
			<div class="eaic-stat-number"><?php echo esc_html( number_format_i18n( $eaic_stats['total_messages'] ) ); ?></div>
			<div class="eaic-stat-label"><?php esc_html_e( 'Total Messages', 'easyit-ai-chat' ); ?></div>
		</div>

		<div class="eaic-stat-card">
			<div class="eaic-stat-icon">📅</div>
			<div class="eaic-stat-number"><?php echo esc_html( number_format_i18n( $eaic_stats['messages_today'] ) ); ?></div>
			<div class="eaic-stat-label"><?php esc_html_e( 'Messages Today', 'easyit-ai-chat' ); ?></div>
		</div>

		<div class="eaic-stat-card">
			<div class="eaic-stat-icon">🔥</div>
			<div class="eaic-stat-number"><?php echo esc_html( number_format_i18n( $eaic_stats['active_this_week'] ) ); ?></div>
			<div class="eaic-stat-label"><?php esc_html_e( 'Active Chats (7 days)', 'easyit-ai-chat' ); ?></div>
		</div>

	</div>

	<!-- Feedback stat cards -->
	<div class="eaic-stats-grid eaic-stats-grid--2">
		<div class="eaic-stat-card">
			<div class="eaic-stat-icon">👍</div>
			<div class="eaic-stat-number"><?php echo esc_html( number_format_i18n( $eaic_feedback['thumbs_up'] ) ); ?></div>
			<div class="eaic-stat-label"><?php esc_html_e( 'Helpful ratings', 'easyit-ai-chat' ); ?></div>
		</div>
		<div class="eaic-stat-card">
			<div class="eaic-stat-icon">👎</div>
			<div class="eaic-stat-number"><?php echo esc_html( number_format_i18n( $eaic_feedback['thumbs_down'] ) ); ?></div>
			<div class="eaic-stat-label"><?php esc_html_e( 'Not helpful ratings', 'easyit-ai-chat' ); ?></div>
		</div>
	</div>

	<!-- Chart + top provider -->
	<div class="eaic-analytics-row">

		<div class="eaic-card eaic-chart-card">
			<div class="eaic-card-title"><span class="icon">📈</span> <?php esc_html_e( 'Messages — Last 7 Days', 'easyit-ai-chat' ); ?></div>
			<div class="eaic-chart-wrap">
				<?php if ( $eaic_max_daily > 0 ) : ?>
				<div class="eaic-chart">
					<?php foreach ( $eaic_daily as $eaic_date => $eaic_count ) : ?>
					<div class="eaic-chart-col">
						<div class="eaic-chart-count"><?php echo $eaic_count > 0 ? esc_html( $eaic_count ) : ''; ?></div>
						<div class="eaic-chart-bar-track">
							<div class="eaic-chart-bar" style="height:<?php echo esc_attr( (int) round( ( $eaic_count / $eaic_max_daily ) * 100 ) ); ?>%"></div>
						</div>
						<div class="eaic-chart-label"><?php echo esc_html( gmdate( 'D', strtotime( $eaic_date ) ) ); ?></div>
					</div>
					<?php endforeach; ?>
				</div>
				<?php else : ?>
				<p class="eaic-no-data"><?php esc_html_e( 'No messages in the last 7 days yet.', 'easyit-ai-chat' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<div class="eaic-card eaic-provider-card">
			<div class="eaic-card-title"><span class="icon">🏆</span> <?php esc_html_e( 'Most Used Provider', 'easyit-ai-chat' ); ?></div>
			<div class="eaic-top-provider">
				<?php echo esc_html( $eaic_top ); ?>
			</div>
			<div class="eaic-stat-label" style="margin-top:8px"><?php esc_html_e( 'based on total conversations', 'easyit-ai-chat' ); ?></div>
		</div>

	</div>

</div>

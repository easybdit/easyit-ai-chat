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

	<?php if ( $eaic_leads_total > 0 || $eaic_product_chats > 0 || $eaic_order_chats > 0 ) : ?>

	<!-- WooCommerce bots — stat cards -->
	<div class="eaic-stats-grid">
		<div class="eaic-stat-card">
			<div class="eaic-stat-icon">👤</div>
			<div class="eaic-stat-number"><?php echo esc_html( number_format_i18n( $eaic_leads_total ) ); ?></div>
			<div class="eaic-stat-label"><?php esc_html_e( 'Total Leads', 'easyit-ai-chat' ); ?></div>
		</div>
		<div class="eaic-stat-card">
			<div class="eaic-stat-icon">📅</div>
			<div class="eaic-stat-number"><?php echo esc_html( number_format_i18n( $eaic_leads_week ) ); ?></div>
			<div class="eaic-stat-label"><?php esc_html_e( 'New Leads (7 days)', 'easyit-ai-chat' ); ?></div>
		</div>
		<div class="eaic-stat-card">
			<div class="eaic-stat-icon">🛍️</div>
			<div class="eaic-stat-number"><?php echo esc_html( number_format_i18n( $eaic_product_chats ) ); ?></div>
			<div class="eaic-stat-label"><?php esc_html_e( 'Product Questions', 'easyit-ai-chat' ); ?></div>
		</div>
		<div class="eaic-stat-card">
			<div class="eaic-stat-icon">📦</div>
			<div class="eaic-stat-number"><?php echo esc_html( number_format_i18n( $eaic_order_chats ) ); ?></div>
			<div class="eaic-stat-label"><?php esc_html_e( 'Order Queries', 'easyit-ai-chat' ); ?></div>
		</div>
	</div>

	<!-- Top asked products + unanswered queries -->
	<div class="eaic-analytics-row" style="grid-template-columns:1fr 1fr">
		<div class="eaic-card">
			<div class="eaic-card-title"><span class="icon">🔥</span> <?php esc_html_e( 'Top Asked Products', 'easyit-ai-chat' ); ?></div>
			<?php if ( empty( $eaic_top_products ) ) : ?>
				<p class="eaic-no-data"><?php esc_html_e( 'No product questions yet.', 'easyit-ai-chat' ); ?></p>
			<?php else : ?>
				<table class="eaic-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Product', 'easyit-ai-chat' ); ?></th>
							<th class="eaic-col-num"><?php esc_html_e( 'Questions', 'easyit-ai-chat' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					$eaic_max_p = (int) $eaic_top_products[0]['total'];
					foreach ( $eaic_top_products as $eaic_i => $eaic_p ) :
						$eaic_pct = $eaic_max_p > 0 ? round( ( $eaic_p['total'] / $eaic_max_p ) * 100 ) : 0;
					?>
						<tr>
							<td>
								<span class="eaic-rank-badge"><?php echo esc_html( $eaic_i + 1 ); ?></span>
								<?php if ( $eaic_p['edit_url'] ) : ?>
									<a href="<?php echo esc_url( $eaic_p['edit_url'] ); ?>" target="_blank"><?php echo esc_html( $eaic_p['name'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $eaic_p['name'] ); ?>
								<?php endif; ?>
								<div class="eaic-bar-wrap"><div class="eaic-bar" style="width:<?php echo esc_attr( $eaic_pct ); ?>%"></div></div>
							</td>
							<td class="eaic-col-num"><strong><?php echo esc_html( $eaic_p['total'] ); ?></strong></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<div class="eaic-card">
			<div class="eaic-card-title"><span class="icon">❓</span> <?php esc_html_e( 'Unanswered Queries', 'easyit-ai-chat' ); ?></div>
			<?php if ( empty( $eaic_unanswered ) ) : ?>
				<p class="eaic-no-data">🎉 <?php esc_html_e( 'No unanswered queries — great job!', 'easyit-ai-chat' ); ?></p>
			<?php else : ?>
				<ul class="eaic-unanswered-list">
				<?php foreach ( $eaic_unanswered as $eaic_row ) : ?>
					<li>
						<span><?php echo esc_html( wp_trim_words( $eaic_row->message, 18 ) ); ?></span>
						<span class="eaic-muted"><?php echo esc_html( date_i18n( 'M j', strtotime( $eaic_row->created_at ) ) ); ?></span>
					</li>
				<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<!-- Leads table -->
	<div class="eaic-card">
		<div class="eaic-card-title"><span class="icon">📧</span> <?php esc_html_e( 'Leads Collected', 'easyit-ai-chat' ); ?></div>
		<?php if ( empty( $eaic_leads ) ) : ?>
			<p class="eaic-no-data"><?php esc_html_e( 'No leads yet. Leads appear when visitors submit the pre-chat form.', 'easyit-ai-chat' ); ?></p>
		<?php else : ?>
			<table class="eaic-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'easyit-ai-chat' ); ?></th>
						<th><?php esc_html_e( 'Email', 'easyit-ai-chat' ); ?></th>
						<th><?php esc_html_e( 'Context', 'easyit-ai-chat' ); ?></th>
						<th><?php esc_html_e( 'Date', 'easyit-ai-chat' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$eaic_ctx_labels = array(
					'product' => array( 'icon' => '🛍️', 'label' => __( 'Product', 'easyit-ai-chat' ) ),
					'order'   => array( 'icon' => '📦', 'label' => __( 'Order', 'easyit-ai-chat' ) ),
					''        => array( 'icon' => '💬', 'label' => __( 'General', 'easyit-ai-chat' ) ),
				);
				foreach ( $eaic_leads as $eaic_lead ) :
					$eaic_ctx = isset( $eaic_ctx_labels[ $eaic_lead['context'] ] ) ? $eaic_ctx_labels[ $eaic_lead['context'] ] : $eaic_ctx_labels[''];
				?>
					<tr>
						<td><?php echo '' !== $eaic_lead['name'] ? esc_html( $eaic_lead['name'] ) : '<span class="eaic-muted">' . esc_html__( 'Anonymous', 'easyit-ai-chat' ) . '</span>'; ?></td>
						<td>
							<?php if ( '' !== $eaic_lead['email'] ) : ?>
								<a href="mailto:<?php echo esc_attr( $eaic_lead['email'] ); ?>"><?php echo esc_html( $eaic_lead['email'] ); ?></a>
							<?php else : ?>
								<span class="eaic-muted">—</span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $eaic_ctx['icon'] . ' ' . $eaic_ctx['label'] ); ?></td>
						<td><?php echo esc_html( date_i18n( 'M j, Y g:i a', strtotime( $eaic_lead['created_at'] ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $eaic_leads_total > 30 ) : ?>
				<p class="eaic-no-data" style="padding:12px 0 0">
					<?php
					printf(
						/* translators: %d: total number of leads */
						esc_html__( 'Showing 30 of %d leads.', 'easyit-ai-chat' ),
						(int) $eaic_leads_total
					);
					?>
				</p>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<?php endif; ?>

	<?php if ( ! empty( $eaic_return_requests ) ) : ?>
	<!-- Return requests -->
	<div class="eaic-card">
		<div class="eaic-card-title">
			<span class="icon">↩️</span> <?php esc_html_e( 'Return Requests', 'easyit-ai-chat' ); ?>
			<span class="eaic-muted" style="font-weight:400;font-size:12px;margin-left:auto">
				<?php
				printf(
					/* translators: %d: number of pending return requests */
					esc_html__( '%d pending', 'easyit-ai-chat' ),
					(int) $eaic_pending_returns
				);
				?>
			</span>
		</div>
		<table class="eaic-table">
			<thead>
				<tr>
					<th>#</th>
					<th><?php esc_html_e( 'Order', 'easyit-ai-chat' ); ?></th>
					<th><?php esc_html_e( 'Items', 'easyit-ai-chat' ); ?></th>
					<th><?php esc_html_e( 'Reason', 'easyit-ai-chat' ); ?></th>
					<th><?php esc_html_e( 'Status', 'easyit-ai-chat' ); ?></th>
					<th><?php esc_html_e( 'Date', 'easyit-ai-chat' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $eaic_return_requests as $eaic_req ) :
				$eaic_items_arr = json_decode( $eaic_req['items'], true );
				$eaic_items_str = is_array( $eaic_items_arr ) ? implode( ', ', $eaic_items_arr ) : (string) $eaic_req['items'];
				$eaic_wc_order  = function_exists( 'wc_get_order' ) ? wc_get_order( $eaic_req['order_id'] ) : false;
			?>
				<tr>
					<td><?php echo absint( $eaic_req['id'] ); ?></td>
					<td>
						<?php if ( $eaic_wc_order ) : ?>
							<a href="<?php echo esc_url( get_edit_post_link( $eaic_req['order_id'] ) ); ?>" target="_blank">#<?php echo esc_html( $eaic_wc_order->get_order_number() ); ?></a>
						<?php else : ?>
							#<?php echo absint( $eaic_req['order_id'] ); ?>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( wp_trim_words( $eaic_items_str, 8 ) ); ?></td>
					<td><?php echo esc_html( $eaic_req['reason'] ); ?></td>
					<td><span class="eaic-status-badge eaic-status-badge--<?php echo esc_attr( $eaic_req['status'] ); ?>"><?php echo esc_html( ucfirst( $eaic_req['status'] ) ); ?></span></td>
					<td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $eaic_req['created_at'] ) ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

</div>

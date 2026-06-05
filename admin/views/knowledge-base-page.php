<?php
/**
 * Admin view: Knowledge Base (RAG document manager).
 *
 * Available variables:
 *   $eaic_opts      — all plugin options
 *   $eaic_documents — array of eaic_documents rows
 *
 * @package EasyIT_AI_Chat
 * @since   2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rag_enabled    = ! empty( $eaic_opts['rag_enabled'] );
$embed_model    = isset( $eaic_opts['rag_embed_model'] ) ? $eaic_opts['rag_embed_model'] : 'nomic-embed-text';
$ollama_url     = isset( $eaic_opts['ollama_url'] ) ? $eaic_opts['ollama_url'] : 'http://localhost:11434';
$chunk_size     = isset( $eaic_opts['rag_chunk_size'] ) ? (int) $eaic_opts['rag_chunk_size'] : 500;
$chunk_overlap  = isset( $eaic_opts['rag_chunk_overlap'] ) ? (int) $eaic_opts['rag_chunk_overlap'] : 50;
$top_k          = isset( $eaic_opts['rag_top_k'] ) ? (int) $eaic_opts['rag_top_k'] : 3;
$threshold      = isset( $eaic_opts['rag_threshold'] ) ? (float) $eaic_opts['rag_threshold'] : 0.3;
?>
<div class="wrap eaic-admin-wrap">
	<h1>📚 <?php esc_html_e( 'Knowledge Base', 'easyit-ai-chat' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Upload .txt or .pdf documents. The AI will search them to answer user questions (RAG — Retrieval-Augmented Generation).', 'easyit-ai-chat' ); ?></p>

	<div id="eaic-rag-notice" style="display:none;margin:12px 0;"></div>

	<!-- ── RAG Settings ──────────────────────────────────────────────── -->
	<form method="post" action="options.php">
		<?php settings_fields( 'eaic_group' ); ?>
		<input type="hidden" name="eaic_options[_passthrough]" value="1">

		<div class="eaic-card" style="margin-top:18px;">
			<h2 class="eaic-card-title">⚙️ <?php esc_html_e( 'RAG Settings', 'easyit-ai-chat' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th><?php esc_html_e( 'Enable RAG', 'easyit-ai-chat' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="eaic_options[rag_enabled]" value="1" <?php checked( $rag_enabled ); ?>>
							<?php esc_html_e( 'Inject document context into every chat message', 'easyit-ai-chat' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Embedding Model', 'easyit-ai-chat' ); ?></th>
					<td>
						<input type="text" name="eaic_options[rag_embed_model]" value="<?php echo esc_attr( $embed_model ); ?>" class="regular-text" placeholder="nomic-embed-text">
						<p class="description">
							<?php
							printf(
								/* translators: 1: Ollama URL, 2: command to pull the model */
								esc_html__( 'Ollama embedding model. Run %2$s in your terminal, then make sure Ollama is reachable at %1$s.', 'easyit-ai-chat' ),
								'<code>' . esc_html( $ollama_url ) . '</code>',
								'<code>ollama pull nomic-embed-text</code>'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Chunk Size (words)', 'easyit-ai-chat' ); ?></th>
					<td>
						<input type="number" name="eaic_options[rag_chunk_size]" value="<?php echo esc_attr( $chunk_size ); ?>" min="50" max="2000" class="small-text">
						<p class="description"><?php esc_html_e( 'Number of words per chunk. Smaller = more precise, larger = more context.', 'easyit-ai-chat' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Chunk Overlap (words)', 'easyit-ai-chat' ); ?></th>
					<td>
						<input type="number" name="eaic_options[rag_chunk_overlap]" value="<?php echo esc_attr( $chunk_overlap ); ?>" min="0" max="500" class="small-text">
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Top-K Results', 'easyit-ai-chat' ); ?></th>
					<td>
						<input type="number" name="eaic_options[rag_top_k]" value="<?php echo esc_attr( $top_k ); ?>" min="1" max="10" class="small-text">
						<p class="description"><?php esc_html_e( 'How many chunks to inject per query.', 'easyit-ai-chat' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Similarity Threshold', 'easyit-ai-chat' ); ?></th>
					<td>
						<input type="number" name="eaic_options[rag_threshold]" value="<?php echo esc_attr( $threshold ); ?>" min="0" max="1" step="0.05" class="small-text">
						<p class="description"><?php esc_html_e( 'Minimum cosine similarity (0–1). Chunks below this score are ignored. 0.3 is a good default.', 'easyit-ai-chat' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save RAG Settings', 'easyit-ai-chat' ) ); ?>
		</div>
	</form>

	<!-- ── Upload Form ───────────────────────────────────────────────── -->
	<div class="eaic-card" style="margin-top:18px;">
		<h2 class="eaic-card-title">📤 <?php esc_html_e( 'Upload Document', 'easyit-ai-chat' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Accepted formats: .txt, .pdf. The document will be automatically chunked and embedded after upload.', 'easyit-ai-chat' ); ?></p>

		<table class="form-table" role="presentation" style="margin-top:10px;">
			<tr>
				<th><?php esc_html_e( 'Document Title', 'easyit-ai-chat' ); ?></th>
				<td><input type="text" id="eaic-rag-title" class="regular-text" placeholder="<?php esc_attr_e( 'Optional — defaults to filename', 'easyit-ai-chat' ); ?>"></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'File', 'easyit-ai-chat' ); ?></th>
				<td>
					<input type="file" id="eaic-rag-file" accept=".txt,.pdf">
					<button type="button" id="eaic-rag-upload-btn" class="button button-primary" style="margin-left:8px;">
						<?php esc_html_e( 'Upload & Process', 'easyit-ai-chat' ); ?>
					</button>
					<span id="eaic-rag-spinner" class="spinner" style="float:none;margin:0 6px;visibility:hidden;"></span>
				</td>
			</tr>
		</table>
	</div>

	<!-- ── Documents Table ───────────────────────────────────────────── -->
	<div class="eaic-card" style="margin-top:18px;">
		<h2 class="eaic-card-title">📄 <?php esc_html_e( 'Uploaded Documents', 'easyit-ai-chat' ); ?></h2>

		<?php if ( empty( $eaic_documents ) ) : ?>
			<p style="color:#666;"><?php esc_html_e( 'No documents uploaded yet.', 'easyit-ai-chat' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped" id="eaic-rag-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Title', 'easyit-ai-chat' ); ?></th>
						<th><?php esc_html_e( 'File', 'easyit-ai-chat' ); ?></th>
						<th><?php esc_html_e( 'Type', 'easyit-ai-chat' ); ?></th>
						<th><?php esc_html_e( 'Chunks', 'easyit-ai-chat' ); ?></th>
						<th><?php esc_html_e( 'Status', 'easyit-ai-chat' ); ?></th>
						<th><?php esc_html_e( 'Date', 'easyit-ai-chat' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'easyit-ai-chat' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $eaic_documents as $doc ) : ?>
						<tr id="eaic-doc-row-<?php echo esc_attr( $doc['id'] ); ?>">
							<td><?php echo esc_html( $doc['title'] ); ?></td>
							<td><?php echo esc_html( $doc['file_name'] ); ?></td>
							<td><?php echo esc_html( $doc['file_type'] ); ?></td>
							<td class="eaic-chunk-count"><?php echo esc_html( $doc['chunk_count'] ); ?></td>
							<td>
								<?php
								$status_labels = array(
									'pending'    => '⏳ ' . __( 'Pending', 'easyit-ai-chat' ),
									'processing' => '⚙️ ' . __( 'Processing', 'easyit-ai-chat' ),
									'ready'      => '✅ ' . __( 'Ready', 'easyit-ai-chat' ),
									'error'      => '❌ ' . __( 'Error', 'easyit-ai-chat' ),
								);
								$status = isset( $status_labels[ $doc['status'] ] ) ? $status_labels[ $doc['status'] ] : esc_html( $doc['status'] );
								echo esc_html( $status );
								?>
							</td>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $doc['created_at'] ) ) ); ?></td>
							<td>
								<button type="button"
									class="button button-small eaic-rag-process-btn"
									data-id="<?php echo esc_attr( $doc['id'] ); ?>">
									<?php esc_html_e( 'Re-process', 'easyit-ai-chat' ); ?>
								</button>
								<button type="button"
									class="button button-small eaic-rag-delete-btn"
									data-id="<?php echo esc_attr( $doc['id'] ); ?>"
									style="color:#b32d2e;border-color:#b32d2e;margin-left:4px;">
									<?php esc_html_e( 'Delete', 'easyit-ai-chat' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>

<script>
(function($) {
	var nonce  = <?php echo wp_json_encode( wp_create_nonce( 'eaic_admin_nonce' ) ); ?>;
	var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

	function showNotice(msg, type) {
		var cls = 'notice-' + (type || 'success');
		$('#eaic-rag-notice').attr('class', 'notice ' + cls + ' is-dismissible').html('<p>' + msg + '</p>').show();
		$('html,body').animate({ scrollTop: 0 }, 300);
	}

	// Upload
	$('#eaic-rag-upload-btn').on('click', function() {
		var file = $('#eaic-rag-file')[0].files[0];
		if (!file) { showNotice(<?php echo wp_json_encode( __( 'Please select a file first.', 'easyit-ai-chat' ) ); ?>, 'error'); return; }

		var fd = new FormData();
		fd.append('action', 'eaic_rag_upload');
		fd.append('nonce', nonce);
		fd.append('rag_file', file);
		fd.append('rag_title', $('#eaic-rag-title').val());

		$('#eaic-rag-spinner').css('visibility', 'visible');
		$('#eaic-rag-upload-btn').prop('disabled', true);

		$.ajax({
			url: ajaxUrl,
			type: 'POST',
			data: fd,
			processData: false,
			contentType: false,
			success: function(r) {
				if (r.success) {
					showNotice(r.data.message, 'success');
					setTimeout(function() { location.reload(); }, 1500);
				} else {
					showNotice(r.data.message, 'error');
				}
			},
			error: function() { showNotice(<?php echo wp_json_encode( __( 'Upload failed. Please try again.', 'easyit-ai-chat' ) ); ?>, 'error'); },
			complete: function() {
				$('#eaic-rag-spinner').css('visibility', 'hidden');
				$('#eaic-rag-upload-btn').prop('disabled', false);
			}
		});
	});

	// Re-process
	$(document).on('click', '.eaic-rag-process-btn', function() {
		var btn   = $(this);
		var docId = btn.data('id');
		btn.prop('disabled', true).text(<?php echo wp_json_encode( __( 'Processing…', 'easyit-ai-chat' ) ); ?>);

		$.post(ajaxUrl, { action: 'eaic_rag_process', nonce: nonce, doc_id: docId }, function(r) {
			if (r.success) {
				showNotice(r.data.message, 'success');
				var row = $('#eaic-doc-row-' + docId);
				row.find('.eaic-chunk-count').text(r.data.chunk_count);
			} else {
				showNotice(r.data.message, 'error');
			}
			btn.prop('disabled', false).text(<?php echo wp_json_encode( __( 'Re-process', 'easyit-ai-chat' ) ); ?>);
		});
	});

	// Delete
	$(document).on('click', '.eaic-rag-delete-btn', function() {
		if (!confirm(<?php echo wp_json_encode( __( 'Delete this document and all its chunks?', 'easyit-ai-chat' ) ); ?>)) { return; }
		var btn   = $(this);
		var docId = btn.data('id');
		btn.prop('disabled', true);

		$.post(ajaxUrl, { action: 'eaic_rag_delete', nonce: nonce, doc_id: docId }, function(r) {
			if (r.success) {
				$('#eaic-doc-row-' + docId).fadeOut(300, function() { $(this).remove(); });
				showNotice(r.data.message, 'success');
			} else {
				showNotice(r.data.message, 'error');
				btn.prop('disabled', false);
			}
		});
	});
})(jQuery);
</script>

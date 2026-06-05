<?php
/**
 * RAG (Retrieval-Augmented Generation) engine.
 *
 * Handles document text extraction, chunking, embedding via Ollama,
 * vector similarity search, and context injection into the system prompt.
 *
 * @package EasyIT_AI_Chat
 * @since   2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RAG pipeline: embed → store → search → inject.
 */
class EAIC_RAG {

	/** @var array<string,mixed> */
	private $opts;

	public function __construct( array $opts ) {
		$this->opts = $opts;
	}

	// -----------------------------------------------------------------------
	// Public API used by EAIC_Engine
	// -----------------------------------------------------------------------

	/**
	 * True when RAG is enabled and at least one document is processed.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return ! empty( $this->opts['rag_enabled'] );
	}

	/**
	 * Embed a query, search all stored chunks, return a formatted context string.
	 * Falls back to keyword search when Ollama embedding is unavailable.
	 * Returns '' when RAG is off or no relevant chunks are found.
	 *
	 * @param string $query User message.
	 * @return string
	 */
	public function build_context( $query ) {
		if ( ! $this->is_enabled() ) {
			return '';
		}

		$all_chunks = EAIC_RAG_DB::get_all_chunks();
		if ( empty( $all_chunks ) ) {
			return '';
		}

		$top_k     = max( 1, (int) ( $this->opts['rag_top_k'] ?? 8 ) );
		$threshold = isset( $this->opts['rag_threshold'] ) ? (float) $this->opts['rag_threshold'] : 0.1;

		// Try semantic search via Ollama embedding.
		$use_semantic = false;
		$query_embedding = array();
		try {
			$query_embedding = $this->get_embedding( $query );
			$use_semantic    = ! empty( $query_embedding );
		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[EAIC RAG] Embedding failed, using keyword fallback: ' . $e->getMessage() );
		}

		$scored = array();

		if ( $use_semantic ) {
			// Cosine similarity search.
			foreach ( $all_chunks as $chunk ) {
				$embedding = json_decode( $chunk['embedding'], true );
				if ( ! is_array( $embedding ) || empty( $embedding ) ) {
					continue;
				}
				$score    = $this->cosine_similarity( $query_embedding, $embedding );
				$scored[] = array( 'content' => $chunk['content'], 'score' => $score );
			}
		} else {
			// Keyword fallback: score by how many query words appear in the chunk.
			$words = array_filter( explode( ' ', mb_strtolower( $query ) ), function ( $w ) {
				return mb_strlen( $w ) > 2;
			} );
			$word_count = count( $words );
			if ( 0 === $word_count ) {
				return '';
			}
			foreach ( $all_chunks as $chunk ) {
				$content_lower = mb_strtolower( $chunk['content'] );
				$hits          = 0;
				foreach ( $words as $word ) {
					if ( false !== mb_strpos( $content_lower, $word ) ) {
						$hits++;
					}
				}
				if ( $hits > 0 ) {
					$scored[] = array( 'content' => $chunk['content'], 'score' => $hits / $word_count );
				}
			}
			$threshold = 0.1; // Keyword mode uses lower threshold.
		}

		if ( empty( $scored ) ) {
			return '';
		}

		usort( $scored, function ( $a, $b ) {
			return $b['score'] <=> $a['score'];
		} );

		$context_parts = array();
		$count         = 0;
		foreach ( $scored as $item ) {
			if ( $count >= $top_k ) {
				break;
			}
			if ( (float) $item['score'] >= $threshold ) {
				$context_parts[] = trim( $item['content'] );
				$count++;
			}
		}

		if ( empty( $context_parts ) ) {
			return '';
		}

		// For counting queries ("how many X"), scan ALL chunks and inject exact counts.
		$count_result  = $this->build_count_summary( $query, $all_chunks );
		$count_summary = $count_result['summary'];
		$count_chunks  = $count_result['chunks'];

		// When the count query matched keyword chunks, use those as context so the AI
		// lists the actual matching items — not whatever the embedding model returned.
		if ( ! empty( $count_chunks ) ) {
			$context_parts = $count_chunks;
		}

		$context = implode( "\n\n---\n\n", $context_parts );

		$count_block = '';
		if ( $count_summary ) {
			$count_block = "*** VERIFIED COUNT (full document scan — use this as your answer) ***\n" .
				$count_summary . "\n" .
				"*** END VERIFIED COUNT ***\n\n";
		}

		return $count_block .
			"SUPPORTING DOCUMENT DATA:\n---\n" . $context . "\n---\n\n" .
			"Instructions: " .
			( $count_summary
				? "The VERIFIED COUNT above is the authoritative answer — state it directly. List the actual items from the supporting data with their name, room/location, tag number, and status."
				: "Answer using only the data above. Use exact item names and numbers. Format with bullet points or a table." );
	}

	/**
	 * For "how many X" queries, count occurrences of the search term across ALL chunks.
	 * Returns ['summary' => string, 'chunks' => array] so build_context() can use the
	 * exact matched chunks as context (bypassing unreliable semantic search).
	 *
	 * @param string $query      User query.
	 * @param array  $all_chunks All stored chunks.
	 * @return array{summary: string, chunks: array}
	 */
	private function build_count_summary( $query, $all_chunks ) {
		$empty = array( 'summary' => '', 'chunks' => array() );

		// Detect "how many <item> ..." pattern.
		if ( ! preg_match( '/\bhow\s+many\s+(.+)/i', trim( $query ), $m ) ) {
			return $empty;
		}

		// Strip trailing stop words/punctuation one by one to isolate the item name.
		$item      = trim( $m[1], " \t\r\n?." );
		$stop      = array( 'are', 'is', 'do', 'does', 'were', 'available', 'here', 'there', 'in', 'at', 'total', 'exist', 'exists', 'please', 'now' );
		$item_words = explode( ' ', $item );
		while ( ! empty( $item_words ) && in_array( strtolower( end( $item_words ) ), $stop, true ) ) {
			array_pop( $item_words );
		}
		$item = trim( implode( ' ', $item_words ), " \t\r\n?." );

		if ( mb_strlen( $item ) < 2 ) {
			return $empty;
		}

		$item_lower = mb_strtolower( $item );

		// Derive a stem to handle singular/plural mismatch:
		// "projectors" → "projector", "chairs" → "chair", "batteries" → "battery".
		$stem = $item_lower;
		if ( mb_substr( $item_lower, -3 ) === 'ies' && mb_strlen( $item_lower ) > 4 ) {
			$stem = mb_substr( $item_lower, 0, -3 ) . 'y';
		} elseif ( mb_substr( $item_lower, -1 ) === 's' && mb_strlen( $item_lower ) > 3 ) {
			$stem = mb_substr( $item_lower, 0, -1 );
		}

		// Count using stem AND collect the chunks that contain the match.
		$total          = 0;
		$matched_chunks = array();
		foreach ( $all_chunks as $chunk ) {
			$hits = substr_count( mb_strtolower( $chunk['content'] ), $stem );
			if ( $hits > 0 ) {
				$total           += $hits;
				$matched_chunks[] = trim( $chunk['content'] );
			}
		}

		if ( $total > 0 ) {
			return array(
				'summary' => '- "' . $item . '": ' . $total . ' found in the document.',
				'chunks'  => $matched_chunks,
			);
		}

		// No exact match — find similar item names using individual keywords.
		$words = array_filter(
			explode( ' ', $item_lower ),
			function ( $w ) { return mb_strlen( $w ) > 3; }
		);
		if ( empty( $words ) ) {
			return $empty;
		}

		$item_counts = array();
		foreach ( $all_chunks as $chunk ) {
			// Find lines/entries in the chunk that contain any of the search words.
			$lines = preg_split( '/(?<=Active|Inactive)\s+\d+\s+/', $chunk['content'] );
			foreach ( $lines as $line ) {
				$line_lower = mb_strtolower( $line );
				foreach ( $words as $w ) {
					if ( false !== mb_strpos( $line_lower, $w ) ) {
						// Extract the item name (first few words before "Furniture" or "IT" type).
						if ( preg_match( '/^([A-Za-z][A-Za-z0-9 \(\)]{2,40?})\s+(?:Furniture|IT|Equipment)/i', trim( $line ), $nm ) ) {
							$key                  = trim( $nm[1] );
							$item_counts[ $key ]  = ( $item_counts[ $key ] ?? 0 ) + 1;
						}
						break;
					}
				}
			}
		}

		if ( empty( $item_counts ) ) {
			return $empty;
		}

		arsort( $item_counts );
		$lines = array( 'No exact match for "' . $item . '". Similar items found:' );
		foreach ( array_slice( $item_counts, 0, 8, true ) as $name => $cnt ) {
			$lines[] = '- "' . $name . '": ' . $cnt;
		}
		return array( 'summary' => implode( "\n", $lines ), 'chunks' => array() );
	}

	/**
	 * Score all chunks against a query and return top-K results with scores.
	 * Used by the admin Test Query feature.
	 *
	 * @param string $query User query.
	 * @param int    $top_k Number of results to return.
	 * @return array Each item: ['content' => string, 'score' => float, 'method' => string].
	 */
	public function test_query( $query, $top_k = 5 ) {
		$all_chunks = EAIC_RAG_DB::get_all_chunks();
		if ( empty( $all_chunks ) ) {
			return array();
		}

		$scored       = array();
		$method       = 'keyword';
		$query_embedding = array();

		try {
			$query_embedding = $this->get_embedding( $query );
			$method          = 'semantic';
		} catch ( Exception $e ) {
			// Fall through to keyword.
		}

		if ( 'semantic' === $method ) {
			foreach ( $all_chunks as $chunk ) {
				$emb = json_decode( $chunk['embedding'], true );
				if ( ! is_array( $emb ) ) { continue; }
				$scored[] = array(
					'content' => $chunk['content'],
					'score'   => round( $this->cosine_similarity( $query_embedding, $emb ), 4 ),
					'method'  => 'semantic',
				);
			}
		} else {
			$words = array_filter( explode( ' ', mb_strtolower( $query ) ), function ( $w ) { return mb_strlen( $w ) > 2; } );
			$wc    = count( $words );
			if ( 0 === $wc ) { return array(); }
			foreach ( $all_chunks as $chunk ) {
				$cl   = mb_strtolower( $chunk['content'] );
				$hits = 0;
				foreach ( $words as $w ) { if ( false !== mb_strpos( $cl, $w ) ) { $hits++; } }
				if ( $hits > 0 ) {
					$scored[] = array( 'content' => $chunk['content'], 'score' => round( $hits / $wc, 4 ), 'method' => 'keyword' );
				}
			}
		}

		usort( $scored, function ( $a, $b ) { return $b['score'] <=> $a['score']; } );
		return array_slice( $scored, 0, $top_k );
	}

	// -----------------------------------------------------------------------
	// Document processing (called from admin AJAX)
	// -----------------------------------------------------------------------

	/**
	 * Full pipeline: extract text → chunk → embed → store.
	 * Updates document status to 'ready' on success or 'error' on failure.
	 *
	 * @param int    $doc_id    Document ID in eaic_documents.
	 * @param string $file_path Absolute path to the uploaded file.
	 * @param string $file_type MIME type.
	 * @return int Number of chunks stored.
	 * @throws RuntimeException On extraction or embedding failure.
	 */
	public function process_document( $doc_id, $file_path, $file_type ) {
		// Allow long-running processing (many Ollama embed calls).
		if ( function_exists( 'set_time_limit' ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Squiz.PHP.DiscouragedFunctions.Discouraged
			@set_time_limit( 300 );
		}

		EAIC_RAG_DB::update_document_status( $doc_id, 'processing' );

		// 1. Extract text.
		$text = $this->extract_text( $file_path, $file_type );
		if ( '' === trim( $text ) ) {
			EAIC_RAG_DB::update_document_status( $doc_id, 'error' );
			throw new RuntimeException(
				esc_html__( 'No text could be extracted from this document. Check the file is not scanned/image-only.', 'easyit-ai-chat' )
			);
		}

		// 2. Chunk text.
		$chunk_size = max( 100, (int) ( $this->opts['rag_chunk_size'] ?? 500 ) );
		$overlap    = max( 0, (int) ( $this->opts['rag_chunk_overlap'] ?? 50 ) );
		$chunks     = $this->chunk_text( $text, $chunk_size, $overlap );

		// 3. Delete old chunks for this doc, then embed + store fresh.
		EAIC_RAG_DB::delete_document_chunks( $doc_id );

		$count = 0;
		foreach ( $chunks as $i => $chunk ) {
			$embedding = array();
			try {
				$embedding = $this->get_embedding( $chunk );
			} catch ( Exception $e ) {
				// Store chunk without embedding; keyword fallback handles search.
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[EAIC RAG] Chunk ' . $i . ' embedding failed: ' . $e->getMessage() );
			}
			$ok = EAIC_RAG_DB::add_chunk( $doc_id, $i, $chunk, wp_json_encode( $embedding ) );
			if ( $ok ) {
				$count++;
			}
		}

		if ( 0 === $count && ! empty( $chunks ) ) {
			EAIC_RAG_DB::update_document_status( $doc_id, 'error' );
			global $wpdb;
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[EAIC RAG] All chunk inserts failed. Last DB error: ' . $wpdb->last_error );
			throw new RuntimeException(
				esc_html__( 'Failed to save chunks to the database. Check your PHP error log for details (search for "[EAIC RAG]").', 'easyit-ai-chat' )
			);
		}

		EAIC_RAG_DB::update_document_status( $doc_id, 'ready', $count );

		return $count;
	}

	// -----------------------------------------------------------------------
	// Embedding
	// -----------------------------------------------------------------------

	/**
	 * Generate a text embedding vector via the Ollama embed API.
	 *
	 * Supports both the legacy /api/embeddings and current /api/embed endpoints.
	 *
	 * @param string $text Text to embed.
	 * @return float[]
	 * @throws RuntimeException On API or format error.
	 */
	public function get_embedding( $text ) {
		$ollama_url = rtrim( ! empty( $this->opts['ollama_url'] ) ? $this->opts['ollama_url'] : 'http://localhost:11434', '/' );
		$model      = ! empty( $this->opts['rag_embed_model'] ) ? $this->opts['rag_embed_model'] : 'nomic-embed-text';
		$timeout    = 8; // Short timeout so keyword fallback kicks in fast when model is slow/wrong.

		// Try /api/embed first (Ollama ≥ 0.3).
		$url      = $ollama_url . '/api/embed';
		$response = wp_remote_post(
			$url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( array( 'model' => $model, 'input' => (string) $text ) ),
				'timeout' => $timeout,
			)
		);

		if ( ! is_wp_error( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $data['embeddings'][0] ) && is_array( $data['embeddings'][0] ) ) {
				return array_map( 'floatval', $data['embeddings'][0] );
			}
		}

		// Fall back to /api/embeddings (older Ollama).
		$url      = $ollama_url . '/api/embeddings';
		$response = wp_remote_post(
			$url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( array( 'model' => $model, 'prompt' => (string) $text ) ),
				'timeout' => $timeout,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException(
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'Ollama embedding request failed: %s', 'easyit-ai-chat' ),
					esc_html( $response->get_error_message() )
				)
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! empty( $data['embedding'] ) && is_array( $data['embedding'] ) ) {
			return array_map( 'floatval', $data['embedding'] );
		}

		throw new RuntimeException(
			esc_html__( 'No embedding returned from Ollama. Make sure the embedding model (e.g. nomic-embed-text) is pulled.', 'easyit-ai-chat' )
		);
	}

	// -----------------------------------------------------------------------
	// Text extraction
	// -----------------------------------------------------------------------

	/**
	 * Extract plain text from an uploaded file.
	 *
	 * Supports: text/plain (.txt), application/pdf (.pdf).
	 *
	 * @param string $file_path Absolute path to the file.
	 * @param string $file_type MIME type.
	 * @return string
	 * @throws RuntimeException On unsupported type or extraction failure.
	 */
	public function extract_text( $file_path, $file_type ) {
		// Plain text — read directly.
		if ( 'text/plain' === $file_type || str_ends_with( strtolower( $file_path ), '.txt' ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$text = file_get_contents( $file_path );
			return false !== $text ? $text : '';
		}

		// PDF — try pdftotext, then PHP fallback.
		if ( 'application/pdf' === $file_type || str_ends_with( strtolower( $file_path ), '.pdf' ) ) {
			return $this->extract_pdf_text( $file_path );
		}

		throw new RuntimeException(
			esc_html__( 'Unsupported file type. Please upload a .txt or .pdf file.', 'easyit-ai-chat' )
		);
	}

	/**
	 * Extract text from a PDF file.
	 * Tries pdftotext first; falls back to a pure-PHP stream reader.
	 *
	 * @param string $file_path Absolute path to the PDF.
	 * @return string
	 */
	private function extract_pdf_text( $file_path ) {
		// Try system pdftotext (poppler-utils).
		if ( function_exists( 'shell_exec' ) ) {
			$cmd  = 'pdftotext ' . escapeshellarg( $file_path ) . ' - 2>/dev/null';
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec
			$text = shell_exec( $cmd );
			if ( is_string( $text ) && '' !== trim( $text ) ) {
				return $text;
			}
		}

		// PHP fallback — reads uncompressed text streams.
		return $this->extract_pdf_text_php( $file_path );
	}

	/**
	 * Pure-PHP PDF text extraction (works for uncompressed text streams).
	 * Not suitable for scanned/image PDFs; those need pdftotext.
	 *
	 * @param string $file_path PDF file path.
	 * @return string
	 */
	private function extract_pdf_text_php( $file_path ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $file_path );
		if ( false === $content ) {
			return '';
		}

		$text   = '';
		$offset = 0;
		$len    = strlen( $content );

		// Walk the file with strpos instead of a single giant regex (avoids PCRE backtrack limits on large PDFs).
		while ( $offset < $len ) {
			$stream_pos = strpos( $content, 'stream', $offset );
			if ( false === $stream_pos ) {
				break;
			}

			// Verify the 'stream' keyword is followed by \n or \r\n (PDF spec requirement).
			$nl = $stream_pos + 6;
			if ( $nl < $len && "\r" === $content[ $nl ] ) {
				$nl++;
			}
			if ( $nl >= $len || "\n" !== $content[ $nl ] ) {
				$offset = $stream_pos + 6;
				continue;
			}
			$data_start = $nl + 1;

			// Find the matching endstream.
			$end_pos = strpos( $content, 'endstream', $data_start );
			if ( false === $end_pos ) {
				break;
			}

			$raw = substr( $content, $data_start, $end_pos - $data_start );
			// Trim trailing \r\n before endstream.
			$raw    = rtrim( $raw, "\r\n" );
			$offset = $end_pos + 9;

			if ( strlen( $raw ) < 4 ) {
				continue;
			}

			// PDF FlateDecode = zlib (RFC 1950). Try gzuncompress first, then raw gzinflate.
			// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
			$decoded = @gzuncompress( $raw );
			if ( false === $decoded ) {
				$decoded = @gzinflate( $raw );
			}
			// phpcs:enable
			$source = ( false !== $decoded ) ? $decoded : $raw;

			// Extract text from BT...ET blocks.
			preg_match_all( '/BT[\s\S]*?ET/', $source, $bt_blocks );
			foreach ( $bt_blocks[0] as $block ) {
				// Tj — single string literal.
				preg_match_all( '/\(([^()\\\\]*(?:\\\\.[^()\\\\]*)*)\)\s*Tj/', $block, $tj );
				foreach ( $tj[1] as $t ) {
					$text .= stripslashes( $t ) . ' ';
				}
				// TJ — array of strings.
				preg_match_all( '/\[([^\]]*)\]\s*TJ/', $block, $tj_arr );
				foreach ( $tj_arr[1] as $arr_str ) {
					preg_match_all( '/\(([^()]*)\)/', $arr_str, $parts );
					foreach ( $parts[1] as $p ) {
						$text .= $p . ' ';
					}
				}
			}
		}

		return trim( $text );
	}

	// -----------------------------------------------------------------------
	// Chunking
	// -----------------------------------------------------------------------

	/**
	 * Split text into overlapping word-count chunks.
	 *
	 * @param string $text       Source text.
	 * @param int    $chunk_size Target chunk size in words.
	 * @param int    $overlap    Overlap in words between consecutive chunks.
	 * @return string[]
	 */
	public function chunk_text( $text, $chunk_size = 500, $overlap = 50 ) {
		$text  = preg_replace( '/\s+/', ' ', trim( $text ) );
		$words = explode( ' ', $text );
		$total = count( $words );

		if ( 0 === $total ) {
			return array();
		}
		if ( $total <= $chunk_size ) {
			return array( $text );
		}

		$chunks = array();
		$step   = max( 1, $chunk_size - $overlap );
		$offset = 0;

		while ( $offset < $total ) {
			$slice = array_slice( $words, $offset, $chunk_size );
			if ( empty( $slice ) ) {
				break;
			}
			$chunks[] = implode( ' ', $slice );
			$offset  += $step;
		}

		return $chunks;
	}

	// -----------------------------------------------------------------------
	// Math
	// -----------------------------------------------------------------------

	/**
	 * Cosine similarity between two float vectors.
	 *
	 * @param float[] $a Vector A.
	 * @param float[] $b Vector B.
	 * @return float Value in [0, 1].
	 */
	public function cosine_similarity( array $a, array $b ) {
		$dot   = 0.0;
		$mag_a = 0.0;
		$mag_b = 0.0;
		$len   = min( count( $a ), count( $b ) );
		for ( $i = 0; $i < $len; $i++ ) {
			$dot   += $a[ $i ] * $b[ $i ];
			$mag_a += $a[ $i ] * $a[ $i ];
			$mag_b += $b[ $i ] * $b[ $i ];
		}
		$mag = sqrt( $mag_a ) * sqrt( $mag_b );
		return $mag > 0.0 ? (float) ( $dot / $mag ) : 0.0;
	}
}

<?php
/**
 * Database layer for RAG documents and chunks.
 *
 * @package EasyIT_AI_Chat
 * @since   2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRUD operations for the eaic_documents and eaic_chunks tables.
 */
class EAIC_RAG_DB {

	const DOCUMENTS_TABLE = 'eaic_documents';
	const CHUNKS_TABLE    = 'eaic_chunks';

	/**
	 * Insert a new document record and return its ID.
	 *
	 * @param string $title     Display title.
	 * @param string $file_name Stored file name.
	 * @param string $file_type MIME type.
	 * @return int Inserted row ID, or 0 on failure.
	 */
	public static function create_document( $title, $file_name, $file_type ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . self::DOCUMENTS_TABLE,
			array(
				'title'     => mb_substr( (string) $title, 0, 255 ),
				'file_name' => mb_substr( (string) $file_name, 0, 255 ),
				'file_type' => mb_substr( (string) $file_type, 0, 50 ),
				'status'    => 'pending',
			),
			array( '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Fetch a single document by ID.
	 *
	 * @param int $id Document ID.
	 * @return array|null
	 */
	public static function get_document( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::DOCUMENTS_TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$table} WHERE id = %d",
				(int) $id
			),
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * Return all documents, newest first.
	 *
	 * @return array
	 */
	public static function get_all_documents() {
		global $wpdb;
		$table = $wpdb->prefix . self::DOCUMENTS_TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$table} ORDER BY id DESC",
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Update the processing status (and optional chunk count) of a document.
	 *
	 * @param int    $id          Document ID.
	 * @param string $status      'pending' | 'processing' | 'ready' | 'error'.
	 * @param int    $chunk_count Number of stored chunks (0 = unchanged).
	 * @return void
	 */
	public static function update_document_status( $id, $status, $chunk_count = 0 ) {
		global $wpdb;

		$data   = array( 'status' => (string) $status );
		$format = array( '%s' );
		if ( $chunk_count > 0 ) {
			$data['chunk_count'] = (int) $chunk_count;
			$format[]            = '%d';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->prefix . self::DOCUMENTS_TABLE,
			$data,
			array( 'id' => (int) $id ),
			$format,
			array( '%d' )
		);
	}

	/**
	 * Delete a document and all its chunks.
	 *
	 * @param int $id Document ID.
	 * @return void
	 */
	public static function delete_document( $id ) {
		global $wpdb;

		self::delete_document_chunks( $id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->prefix . self::DOCUMENTS_TABLE,
			array( 'id' => (int) $id ),
			array( '%d' )
		);
	}

	/**
	 * Store a single text chunk with its embedding vector.
	 *
	 * @param int    $document_id Document ID.
	 * @param int    $chunk_index Zero-based chunk index.
	 * @param string $content     Plain text of the chunk.
	 * @param string $embedding   JSON-encoded float array.
	 * @return void
	 */
	public static function add_chunk( $document_id, $chunk_index, $content, $embedding ) {
		global $wpdb;

		// Strip null bytes and non-UTF-8 sequences that MySQL rejects.
		$content   = self::sanitize_text( (string) $content );
		$embedding = self::sanitize_text( (string) $embedding );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert(
			$wpdb->prefix . self::CHUNKS_TABLE,
			array(
				'document_id' => (int) $document_id,
				'chunk_index' => (int) $chunk_index,
				'content'     => $content,
				'embedding'   => $embedding,
			),
			array( '%d', '%d', '%s', '%s' )
		);

		if ( false === $result ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[EAIC RAG] add_chunk insert failed (doc=' . $document_id . ' chunk=' . $chunk_index . '): ' . $wpdb->last_error );
		}

		return false !== $result;
	}

	/**
	 * Remove null bytes and ensure valid UTF-8 so MySQL won't reject the text.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private static function sanitize_text( $text ) {
		$text = str_replace( "\0", '', $text );
		if ( function_exists( 'iconv' ) ) {
			$clean = @iconv( 'UTF-8', 'UTF-8//IGNORE', $text ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false !== $clean ) {
				return $clean;
			}
		}
		return wp_check_invalid_utf8( $text, true );
	}

	/**
	 * Return all stored chunks (used for similarity search).
	 *
	 * @return array Each row: document_id, chunk_index, content, embedding.
	 */
	public static function get_all_chunks() {
		global $wpdb;
		$table = $wpdb->prefix . self::CHUNKS_TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT document_id, chunk_index, content, embedding FROM {$table} ORDER BY document_id ASC, chunk_index ASC",
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Delete all chunks for one document (used before re-processing).
	 *
	 * @param int $document_id Document ID.
	 * @return void
	 */
	public static function delete_document_chunks( $document_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->prefix . self::CHUNKS_TABLE,
			array( 'document_id' => (int) $document_id ),
			array( '%d' )
		);
	}
}

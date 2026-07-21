<?php
/**
 * Lead capture — AJAX endpoint.
 *
 * Saves the optional pre-chat name + email submitted from a bot widget.
 * Fire-and-forget from JS (non-blocking, public/nopriv endpoint).
 *
 * @package EasyIT_AI_Chat
 * @since   2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAIC_Lead_Ajax {

	const NONCE_ACTION = 'eaic_save_lead';
	const RATE_MAX      = 10;
	const RATE_WINDOW    = 60;

	/**
	 * Register AJAX hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_ajax_eaic_save_lead',        array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_eaic_save_lead', array( __CLASS__, 'handle' ) );
	}

	/**
	 * Handle a lead-capture submission.
	 *
	 * @return void
	 */
	public static function handle() {
		// Nonce check — basic CSRF guard.
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( array(), 403 );
		}

		// Rate limit — this is a public nopriv endpoint with no other abuse guard,
		// so cap submissions per identity to prevent scripted flooding of the leads table.
		$user_id  = get_current_user_id();
		$rate_key = $user_id > 0 ? 'lead_u' . $user_id : 'lead_ip' . self::client_ip();
		$rl_key   = 'eaic_rl_' . md5( $rate_key );
		$count    = (int) get_transient( $rl_key );
		if ( $count >= self::RATE_MAX ) {
			wp_send_json_error( array(), 429 );
		}
		set_transient( $rl_key, $count + 1, self::RATE_WINDOW );

		$session_id = isset( $_POST['session_id'] )
			? sanitize_text_field( wp_unslash( $_POST['session_id'] ) )
			: '';

		if ( '' === $session_id || strlen( $session_id ) > 64 ) {
			wp_send_json_error( array(), 400 );
		}

		$name       = isset( $_POST['name'] )       ? sanitize_text_field( wp_unslash( $_POST['name'] ) )    : '';
		$email      = isset( $_POST['email'] )      ? sanitize_email( wp_unslash( $_POST['email'] ) )        : '';
		$context    = isset( $_POST['context'] )    ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : '';
		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] )                         : 0;

		// Skip anonymous submissions with no data at all.
		if ( '' === $name && '' === $email && 0 === $user_id ) {
			wp_send_json_success( array( 'saved' => false ) );
		}

		EAIC_Lead_DB::save( $session_id, $user_id, $name, $email, $context, $product_id );

		wp_send_json_success( array( 'saved' => true ) );
	}

	/**
	 * Best-effort client IP for the guest rate-limit key.
	 *
	 * @return string
	 */
	private static function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return (string) apply_filters( 'eaic_client_ip', $ip );
	}
}

<?php
/**
 * Order Status Bot — Verification layer.
 *
 * This is the most security-critical file. It decides whether a
 * given visitor is allowed to see a given order. Three methods are
 * supported, in order of trust:
 *
 *   1. Logged-in user      — order->customer_id matches current user.
 *   2. Order # + email      — billing email matches what the visitor typed.
 *   3. OTP                   — a code emailed to the billing address.
 *
 * Guests can NEVER see order data without passing (2) or (3).
 * Never leak whether an order number exists to an unverified visitor.
 *
 * @package EasyIT_AI_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAIC_Order_Verify {

	const OTP_TRANSIENT = 'eaic_otp_';
	const OTP_TTL       = 600; // 10 minutes.

	/**
	 * Method 1 + 2: verify ownership of a single order.
	 *
	 * @param int    $order_id       Order id.
	 * @param string $claimed_email  Email the guest typed (may be empty).
	 * @return bool True if the visitor owns this order.
	 */
	public static function owns_order( $order_id, $claimed_email = '' ) {
		$order = wc_get_order( absint( $order_id ) );
		if ( ! $order ) {
			return false; // Do not reveal existence.
		}

		// Method 1: logged-in user.
		$uid = get_current_user_id();
		if ( $uid && (int) $order->get_customer_id() === $uid ) {
			return true;
		}

		// Method 2: email match (constant-time, case-insensitive).
		$claimed_email = sanitize_email( $claimed_email );
		if ( $claimed_email && is_email( $claimed_email ) ) {
			$billing = strtolower( (string) $order->get_billing_email() );
			if ( hash_equals( $billing, strtolower( $claimed_email ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Method 3a: generate and email an OTP for an order.
	 * Returns true if an OTP was sent. Always returns the same
	 * outward behaviour whether or not the order/email is valid,
	 * to avoid leaking which combinations exist.
	 *
	 * @param int    $order_id      Order id.
	 * @param string $claimed_email Email the guest typed.
	 * @return bool
	 */
	public static function send_otp( $order_id, $claimed_email ) {
		$order         = wc_get_order( absint( $order_id ) );
		$claimed_email = sanitize_email( $claimed_email );

		// Only send if the email actually matches the order.
		if ( $order && is_email( $claimed_email )
			&& hash_equals(
				strtolower( (string) $order->get_billing_email() ),
				strtolower( $claimed_email )
			)
		) {
			$code = (string) wp_rand( 100000, 999999 );
			set_transient(
				self::OTP_TRANSIENT . self::otp_key( $order_id, $claimed_email ),
				wp_hash_password( $code ),
				self::OTP_TTL
			);

			wp_mail(
				$claimed_email,
				__( 'Your order verification code', 'easyit-ai-chat' ),
				sprintf(
					/* translators: %s: six digit code */
					__( 'Your verification code is: %s', 'easyit-ai-chat' ),
					$code
				)
			);
		}

		// Same return regardless — no enumeration.
		return true;
	}

	/**
	 * Method 3b: check a submitted OTP.
	 *
	 * @param int    $order_id      Order id.
	 * @param string $claimed_email Email used when requesting the code.
	 * @param string $code          Code the visitor typed.
	 * @return bool
	 */
	public static function check_otp( $order_id, $claimed_email, $code ) {
		$claimed_email = sanitize_email( $claimed_email );
		$key           = self::OTP_TRANSIENT . self::otp_key( $order_id, $claimed_email );
		$stored        = get_transient( $key );

		if ( ! $stored ) {
			return false;
		}

		$ok = wp_check_password( preg_replace( '/\D/', '', $code ), $stored );
		if ( $ok ) {
			delete_transient( $key ); // One-time use.
		}
		return $ok;
	}

	/**
	 * Internal: stable transient key per order+email.
	 */
	private static function otp_key( $order_id, $email ) {
		return md5( absint( $order_id ) . '|' . strtolower( $email ) );
	}

	/**
	 * Find orders a visitor is allowed to see, for AI lookup.
	 * Logged-in users get their own recent orders. Guests get
	 * nothing here — they must pass an order number + email first.
	 *
	 * @param int $limit Max orders.
	 * @return int[] Array of order ids the visitor owns.
	 */
	public static function visible_order_ids( $limit = 5 ) {
		$uid = get_current_user_id();
		if ( ! $uid ) {
			return array();
		}

		$orders = wc_get_orders(
			array(
				'customer_id' => $uid,
				'limit'       => absint( $limit ),
				'orderby'     => 'date',
				'order'       => 'DESC',
				'return'      => 'ids',
			)
		);

		return array_map( 'absint', (array) $orders );
	}
}

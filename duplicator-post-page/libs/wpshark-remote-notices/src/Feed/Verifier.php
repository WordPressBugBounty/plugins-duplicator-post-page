<?php
/**
 * Signature verifier.
 *
 * @package WPShark\RemoteNotices
 */

namespace WPShark\RemoteNotices\Feed;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Checks the Ed25519 signature and expiry of a feed envelope.
 */
final class Verifier {

	/**
	 * Trusted public keys: kid => base64. To rotate, ship a new kid in an
	 * SDK release, wait for adoption, then switch the hub signer.
	 */
	const KEYS = array(
		'2026-09' => '0V29oYXIERra79PJNqYE/7YlXtQtbZZEIv9RqXrVeYI=',
	);

	const SCHEMA = 1;

	/**
	 * Verify an envelope and return the decoded payload.
	 *
	 * @param  mixed $envelope Decoded JSON envelope { kid, sig, payload }.
	 * @return array|WP_Error Payload array on success.
	 */
	public static function verify( $envelope ) {
		if ( ! is_array( $envelope ) || ! isset( $envelope['kid'], $envelope['sig'], $envelope['payload'] ) || ! is_string( $envelope['kid'] ) || ! is_string( $envelope['sig'] ) || ! is_string( $envelope['payload'] ) ) {
			return new WP_Error( 'wpshark_sdk_envelope', 'Malformed envelope.' );
		}

		if ( ! isset( self::KEYS[ $envelope['kid'] ] ) ) {
			return new WP_Error( 'wpshark_sdk_kid', 'Unknown key id.' );
		}

		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Key/signature material.
		$sig = base64_decode( $envelope['sig'], true );
		$key = base64_decode( self::KEYS[ $envelope['kid'] ], true );
		// phpcs:enable

		if ( false === $sig || SODIUM_CRYPTO_SIGN_BYTES !== strlen( $sig ) || false === $key || SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES !== strlen( $key ) ) {
			return new WP_Error( 'wpshark_sdk_sig_format', 'Bad signature format.' );
		}

		try {
			$valid = sodium_crypto_sign_verify_detached( $sig, $envelope['payload'], $key );
		} catch ( \Throwable $e ) {
			$valid = false;
		}

		if ( ! $valid ) {
			return new WP_Error( 'wpshark_sdk_sig', 'Signature check failed.' );
		}

		$payload = json_decode( $envelope['payload'], true, 8 );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $payload ) ) {
			return new WP_Error( 'wpshark_sdk_payload', 'Payload is not valid JSON.' );
		}

		if ( ! isset( $payload['schema'] ) || self::SCHEMA !== $payload['schema'] ) {
			return new WP_Error( 'wpshark_sdk_schema', 'Unsupported schema.' );
		}

		if ( ! isset( $payload['expires_at'], $payload['generated_at'] ) || ! is_int( $payload['expires_at'] ) || ! is_int( $payload['generated_at'] ) || $payload['expires_at'] <= time() ) {
			return new WP_Error( 'wpshark_sdk_expired', 'Feed expired.' );
		}

		return $payload;
	}
}

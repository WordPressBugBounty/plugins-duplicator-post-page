<?php
/**
 * Background fetcher (WP-Cron only).
 *
 * @package WPShark\RemoteNotices
 */

namespace WPShark\RemoteNotices\Feed;

use WPShark\RemoteNotices\Sdk;

defined( 'ABSPATH' ) || exit;

/**
 * Fetches, verifies, cleans and stores the feed. Never runs during a page
 * render, and backs off exponentially on failure.
 */
final class Fetcher {

	const FEED_URL  = 'https://wpshark.xyz/wp-json/wpshark-hub/v1/feed';
	const HOOK      = 'wpshark_sdk_refresh';
	const HOOK_ONCE = 'wpshark_sdk_refresh_now';
	const MAX_BYTES = 262144; // 256 KB.
	const SCHEDULE  = 'wpshark_sdk_six_hours';
	const INTERVAL  = 6 * HOUR_IN_SECONDS;

	/**
	 * Add the 6-hour cron schedule (core only has hourly/twicedaily/daily).
	 *
	 * @param  array $schedules Schedules.
	 * @return array
	 */
	public static function add_schedule( $schedules ) {
		$schedules[ self::SCHEDULE ] = array(
			'interval' => self::INTERVAL,
			'display'  => 'Every 6 hours (WPShark news)',
		);

		return $schedules;
	}

	/**
	 * Feed URL. WPSHARK_SDK_FEED_URL (wp-config.php) overrides it for local
	 * testing against a local hub; it is never user-configurable in the UI.
	 *
	 * @return string
	 */
	public static function url() {
		if ( defined( 'WPSHARK_SDK_FEED_URL' ) && is_string( WPSHARK_SDK_FEED_URL ) && '' !== WPSHARK_SDK_FEED_URL ) {
			return WPSHARK_SDK_FEED_URL;
		}

		return self::FEED_URL;
	}

	/**
	 * Host of the local-testing override, or ''.
	 *
	 * @return string
	 */
	public static function dev_host() {
		if ( self::FEED_URL === self::url() ) {
			return '';
		}

		$host = wp_parse_url( self::url(), PHP_URL_HOST );

		return is_string( $host ) ? strtolower( $host ) : '';
	}

	/**
	 * Cron callback for the one-off refresh (right after opt-in).
	 *
	 * @return void
	 */
	public static function run_now() {
		self::run( true );
	}

	/**
	 * Cron callback.
	 *
	 * @param  bool $force Ignore the backoff window.
	 * @return void
	 */
	public static function run( $force = false ) {
		if ( ! Sdk::enabled() ) {
			return;
		}

		$state = Repository::state();
		$now   = time();

		if ( ! $force && $state['next_try'] > $now ) {
			return;
		}

		$args = array(
			'timeout'             => 8,
			'redirection'         => 2,
			'limit_response_size' => self::MAX_BYTES,
			'headers'             => array_filter(
				array(
					'Accept'        => 'application/json',
					'If-None-Match' => $state['etag'],
				)
			),
			// Core's default user-agent contains the home URL; this one does not.
			'user-agent'          => 'WPShark-SDK/' . WPSHARK_SDK_VERSION,
		);

		// wp_safe_remote_get() blocks private/loopback IPs, which is right for
		// the live hub but would block a local test hub.
		$response = '' === self::dev_host() ? wp_safe_remote_get( self::url(), $args ) : wp_remote_get( self::url(), $args );

		if ( is_wp_error( $response ) ) {
			self::backoff( $state, $response->get_error_message() );
			return;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 304 === $code ) {
			$state['checked_at'] = $now;
			$state['fail_count'] = 0;
			$state['next_try']   = 0;
			$state['last_error'] = '';
			Repository::save_state( $state );
			return;
		}

		if ( 200 !== $code ) {
			self::backoff( $state, 'HTTP ' . $code );
			return;
		}

		$body = (string) wp_remote_retrieve_body( $response );

		if ( '' === $body || strlen( $body ) >= self::MAX_BYTES ) {
			self::backoff( $state, 'Empty or oversized body.' );
			return;
		}

		$envelope = json_decode( $body, true, 8 );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			self::backoff( $state, 'Invalid JSON.' );
			return;
		}

		$payload = Verifier::verify( $envelope );

		if ( is_wp_error( $payload ) ) {
			// Keep the old cache; a bad feed never replaces a good one.
			self::backoff( $state, $payload->get_error_message() );
			return;
		}

		if ( $payload['generated_at'] < Repository::generated_at() ) {
			self::backoff( $state, 'Older feed than the cached one.' );
			return;
		}

		Repository::save_feed( Schema::clean( $payload['items'] ?? array() ), $payload['generated_at'], $payload['expires_at'] );

		Repository::save_state(
			array(
				'etag'       => substr( (string) wp_remote_retrieve_header( $response, 'etag' ), 0, 200 ),
				'checked_at' => $now,
				'fail_count' => 0,
				'next_try'   => 0,
				'last_error' => '',
			)
		);
	}

	/**
	 * Record a failure: next_try = now + min( 6h, 15min * 2^fail_count ).
	 *
	 * @param  array  $state State.
	 * @param  string $error Message (stored for debugging).
	 * @return void
	 */
	private static function backoff( array $state, $error ) {
		$fails = min( 10, (int) $state['fail_count'] );

		$state['fail_count'] = $fails + 1;
		$state['next_try']   = time() + (int) min( self::INTERVAL, 15 * MINUTE_IN_SECONDS * ( 2 ** $fails ) );
		$state['checked_at'] = time();
		$state['last_error'] = substr( sanitize_text_field( $error ), 0, 200 );

		Repository::save_state( $state );
	}
}

<?php
/**
 * Local storage of the verified feed.
 *
 * @package WPShark\RemoteNotices
 */

namespace WPShark\RemoteNotices\Feed;

defined( 'ABSPATH' ) || exit;

/**
 * Options are stored with autoload off, so the feed never loads on
 * front-end requests.
 */
final class Repository {

	const FEED_OPTION  = 'wpshark_sdk_feed';
	const STATE_OPTION = 'wpshark_sdk_state';

	/**
	 * Clean cached items.
	 *
	 * @return array[]
	 */
	public static function items() {
		$feed = get_option( self::FEED_OPTION, array() );

		if ( ! is_array( $feed ) || empty( $feed['items'] ) || ! is_array( $feed['items'] ) ) {
			return array();
		}

		// Stop showing a feed that expired (e.g. the hub has been unreachable for a week).
		if ( empty( $feed['expires_at'] ) || (int) $feed['expires_at'] <= time() ) {
			return array();
		}

		return $feed['items'];
	}

	/**
	 * Find one item by ID.
	 *
	 * @param  int $id Item ID.
	 * @return array|null
	 */
	public static function find( $id ) {
		foreach ( self::items() as $item ) {
			if ( (int) $item['id'] === (int) $id ) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * Generation time of the stored feed (rollback protection).
	 *
	 * @return int
	 */
	public static function generated_at() {
		$feed = get_option( self::FEED_OPTION, array() );

		return is_array( $feed ) && isset( $feed['generated_at'] ) ? (int) $feed['generated_at'] : 0;
	}

	/**
	 * Store a verified, cleaned feed.
	 *
	 * @param  array $items        Clean items.
	 * @param  int   $generated_at Payload generation time.
	 * @param  int   $expires_at   Payload expiry.
	 * @return void
	 */
	public static function save_feed( array $items, $generated_at, $expires_at ) {
		update_option(
			self::FEED_OPTION,
			array(
				'items'        => $items,
				'generated_at' => (int) $generated_at,
				'expires_at'   => (int) $expires_at,
			),
			false
		);
	}

	/**
	 * Fetch state.
	 *
	 * @return array { etag: string, checked_at: int, fail_count: int, next_try: int, last_error: string }
	 */
	public static function state() {
		$state = get_option( self::STATE_OPTION, array() );

		return wp_parse_args(
			is_array( $state ) ? $state : array(),
			array(
				'etag'       => '',
				'checked_at' => 0,
				'fail_count' => 0,
				'next_try'   => 0,
				'last_error' => '',
			)
		);
	}

	/**
	 * Save fetch state.
	 *
	 * @param  array $state State.
	 * @return void
	 */
	public static function save_state( array $state ) {
		update_option( self::STATE_OPTION, $state, false );
	}

	/**
	 * Remove everything (last product opted out or uninstalled).
	 *
	 * @return void
	 */
	public static function purge() {
		delete_option( self::FEED_OPTION );
		delete_option( self::STATE_OPTION );
	}
}

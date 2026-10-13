<?php
/**
 * Dismiss endpoint.
 *
 * @package WPShark\RemoteNotices
 */

namespace WPShark\RemoteNotices\Rest;

use WPShark\RemoteNotices\Feed\Repository;
use WPShark\RemoteNotices\Feed\Selector;
use WPShark\RemoteNotices\Sdk;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * POST /wp-json/wpshark-sdk/v1/dismiss { id }
 *
 * Dismissals are permanent, only for items in the verified cache, and are
 * stored in namespaced user meta. Cookie auth requires the wp_rest nonce.
 */
final class DismissController {

	/**
	 * Register the route.
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			'wpshark-sdk/v1',
			'/dismiss',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'dismiss' ),
				'permission_callback' => static function () {
					return current_user_can( Sdk::capability() );
				},
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Dismiss an item for the current user.
	 *
	 * @param  WP_REST_Request $request Request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public static function dismiss( WP_REST_Request $request ) {
		$item = Repository::find( (int) $request['id'] ); // Must exist in the verified cache.

		if ( null === $item ) {
			return new WP_Error( 'wpshark_not_found', __( 'Unknown item.', 'duplicator-post-page' ), array( 'status' => 404 ) );
		}

		$user_id   = get_current_user_id();
		$dismissed = Selector::dismissed();

		$dismissed[ Selector::dismiss_key( $item ) ] = time();

		// Keep the newest 200; feed IDs are not reused, so older ones are stale.
		update_user_meta( $user_id, Selector::DISMISSED_META, array_slice( $dismissed, -200, null, true ) );

		return rest_ensure_response( array( 'dismissed' => true ) );
	}
}

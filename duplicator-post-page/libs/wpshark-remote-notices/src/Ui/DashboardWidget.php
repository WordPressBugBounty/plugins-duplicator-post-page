<?php
/**
 * "WPShark News" dashboard widget.
 *
 * @package WPShark\RemoteNotices
 */

namespace WPShark\RemoteNotices\Ui;

use WPShark\RemoteNotices\Feed\Selector;
use WPShark\RemoteNotices\Sdk;

defined( 'ABSPATH' ) || exit;

/**
 * One widget for all WPShark plugins, registered by the elected SDK only.
 * It exists only after an admin opts in, takes the default dashboard
 * position, and Screen Options can hide it.
 */
final class DashboardWidget {

	const ID = 'wpshark-news';

	/**
	 * Footer links: label => URL. Fixed here, never taken from the feed.
	 *
	 * @return string[]
	 */
	public static function links() {
		$utm = '?utm_source=wp-admin&utm_medium=wpshark-news&utm_campaign=footer';

		return array(
			__( 'Need Help?', 'duplicator-post-page' ) => 'https://wpshark.xyz/support/' . $utm,
			__( 'Blog', 'duplicator-post-page' )       => 'https://wpshark.xyz/blog/' . $utm,
			__( 'Facebook', 'duplicator-post-page' )   => 'https://www.facebook.com/wpshark',
			__( 'Community', 'duplicator-post-page' )  => 'https://www.facebook.com/groups/wpshark',
		);
	}

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'wp_dashboard_setup', array( self::class, 'add' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/**
	 * Whether the widget should exist: only once an admin opted in.
	 *
	 * @return bool
	 */
	private static function visible() {
		return Sdk::can_render() && Sdk::enabled();
	}

	/**
	 * Register the widget.
	 *
	 * @return void
	 */
	public static function add() {
		if ( ! self::visible() ) {
			return;
		}

		wp_add_dashboard_widget( self::ID, esc_html__( 'WPShark News', 'duplicator-post-page' ), array( self::class, 'render' ) );
	}

	/**
	 * Enqueue the stylesheet on the dashboard only.
	 *
	 * @param  string $hook Hook suffix.
	 * @return void
	 */
	public static function enqueue( $hook ) {
		if ( 'index.php' === $hook && self::visible() ) {
			wp_enqueue_style( 'wpshark-sdk', Sdk::asset_url( 'assets/sdk.css' ), array(), WPSHARK_SDK_VERSION );
		}
	}

	/**
	 * Render the widget body.
	 *
	 * @return void
	 */
	public static function render() {
		$stories = Selector::stories();
		$links   = self::links();
		require WPSHARK_SDK_DIR . 'views/story-list.php';
	}
}

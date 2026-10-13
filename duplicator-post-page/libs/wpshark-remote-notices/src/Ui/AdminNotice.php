<?php
/**
 * Banners and notices on a product's own screens.
 *
 * @package WPShark\RemoteNotices
 */

namespace WPShark\RemoteNotices\Ui;

use WPShark\RemoteNotices\Feed\Selector;
use WPShark\RemoteNotices\Sdk;

defined( 'ABSPATH' ) || exit;

/**
 * Renders at most one item per screen, only on the owning product's
 * screens (or the dashboard when the item says so).
 */
final class AdminNotice {

	/**
	 * Item chosen for this request (false = not computed yet).
	 *
	 * @var array|null|false
	 */
	private static $item = false;

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_action( 'admin_notices', array( self::class, 'render' ) );
	}

	/**
	 * Item for the current screen, or null.
	 *
	 * @return array|null
	 */
	private static function item() {
		if ( false !== self::$item ) {
			return self::$item;
		}

		self::$item = null;

		if ( ! Sdk::can_render() || ! Sdk::enabled() ) {
			return null;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ( method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) ) {
			return null;
		}

		self::$item = Selector::notice_for_screen( $screen->id );

		return self::$item;
	}

	/**
	 * Enqueue assets only when an item renders on this screen.
	 *
	 * @return void
	 */
	public static function enqueue() {
		if ( null === self::item() ) {
			return;
		}

		wp_enqueue_style( 'wpshark-sdk', Sdk::asset_url( 'assets/sdk.css' ), array(), WPSHARK_SDK_VERSION );
		wp_enqueue_script( 'wpshark-sdk', Sdk::asset_url( 'assets/sdk.js' ), array( 'wp-api-fetch' ), WPSHARK_SDK_VERSION, true );
	}

	/**
	 * Print the notice.
	 *
	 * @return void
	 */
	public static function render() {
		$item = self::item();

		if ( null === $item ) {
			return;
		}

		require WPSHARK_SDK_DIR . 'views/notice.php';
	}
}

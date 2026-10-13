<?php
/**
 * Deactivation / uninstall cleanup.
 *
 * Standalone: loaded directly from a plugin's uninstall.php, where the SDK
 * bootstrap never runs.
 *
 * @package WPShark\RemoteNotices
 */

namespace WPShark\RemoteNotices;

defined( 'ABSPATH' ) || exit;

/**
 * Shared data is removed only when no other WPShark plugin still needs it.
 */
final class Uninstaller {

	const LOADER = 'libs/wpshark-remote-notices/loader.php';

	/**
	 * Call from a product's uninstall.php.
	 *
	 * @param  string $slug           Product slug (plugin directory).
	 * @param  string $consent_option Product consent option name.
	 * @return void
	 */
	public static function maybe_cleanup( $slug, $consent_option ) {
		delete_option( sanitize_key( $consent_option ) );

		if ( self::other_sdk_plugins( $slug, false ) ) {
			return;
		}

		delete_option( 'wpshark_sdk_feed' );
		delete_option( 'wpshark_sdk_state' );
		wp_clear_scheduled_hook( 'wpshark_sdk_refresh' );
		wp_clear_scheduled_hook( 'wpshark_sdk_refresh_now' );
		delete_metadata( 'user', 0, 'wpshark_sdk_dismissed', '', true );
	}

	/**
	 * Call from a product's deactivation hook: stop the cron event when this
	 * was the last active WPShark product.
	 *
	 * @param  string $slug Product slug.
	 * @return void
	 */
	public static function on_deactivate( $slug ) {
		if ( self::other_sdk_plugins( $slug, true ) ) {
			return;
		}

		wp_clear_scheduled_hook( 'wpshark_sdk_refresh' );
		wp_clear_scheduled_hook( 'wpshark_sdk_refresh_now' );
	}

	/**
	 * Whether another installed (or active) plugin bundles the SDK.
	 *
	 * @param  string $slug        This product's slug.
	 * @param  bool   $active_only Only count active plugins.
	 * @return bool
	 */
	private static function other_sdk_plugins( $slug, $active_only ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( array_keys( get_plugins() ) as $file ) {
			$dir = dirname( $file );

			if ( '.' === $dir || $dir === $slug ) {
				continue;
			}

			if ( ! file_exists( WP_PLUGIN_DIR . '/' . $dir . '/' . self::LOADER ) ) {
				continue;
			}

			if ( ! $active_only || is_plugin_active( $file ) ) {
				return true;
			}
		}

		return false;
	}
}

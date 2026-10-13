<?php
/**
 * WPShark Remote Notices SDK: version-election loader.
 *
 * Every WPShark plugin bundles a copy of this SDK and requires this file at
 * load time. Each copy registers its version; on plugins_loaded the highest
 * version is loaded exactly once, so all WPShark plugins on a site share one
 * widget, one cron event and one cache.
 *
 * @package WPShark\RemoteNotices
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wpshark_sdk_register' ) ) {
	/**
	 * Register a bundled SDK copy.
	 *
	 * @param string $version        SDK version.
	 * @param string $bootstrap_file Absolute path to that copy's bootstrap.
	 * @return void
	 */
	function wpshark_sdk_register( $version, $bootstrap_file ) {
		if ( ! isset( $GLOBALS['wpshark_sdk_candidates'] ) || ! is_array( $GLOBALS['wpshark_sdk_candidates'] ) ) {
			$GLOBALS['wpshark_sdk_candidates'] = array();
		}

		if ( ! isset( $GLOBALS['wpshark_sdk_candidates'][ $version ] ) ) {
			$GLOBALS['wpshark_sdk_candidates'][ $version ] = $bootstrap_file;
		}
	}

	/**
	 * Load the highest registered SDK version, once.
	 *
	 * @return void
	 */
	function wpshark_sdk_elect() {
		if ( defined( 'WPSHARK_SDK_VERSION' ) || empty( $GLOBALS['wpshark_sdk_candidates'] ) ) {
			return;
		}

		$candidates = $GLOBALS['wpshark_sdk_candidates'];
		uksort( $candidates, 'version_compare' );

		require_once end( $candidates ); // Highest version wins.

		/**
		 * Fires once the elected SDK is loaded. Products register here.
		 */
		do_action( 'wpshark_sdk_loaded' );
	}

	add_action( 'plugins_loaded', 'wpshark_sdk_elect', -100 );
}

wpshark_sdk_register( '1.3.0', __DIR__ . '/src/bootstrap.php' );

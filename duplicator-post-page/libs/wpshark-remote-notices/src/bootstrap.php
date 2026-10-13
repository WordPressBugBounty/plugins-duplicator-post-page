<?php
/**
 * Bootstrap of the elected SDK copy.
 *
 * @package WPShark\RemoteNotices
 */

defined( 'ABSPATH' ) || exit;

define( 'WPSHARK_SDK_VERSION', '1.3.0' );
define( 'WPSHARK_SDK_DIR', dirname( __DIR__ ) . '/' );

require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/Consent.php';
require_once __DIR__ . '/Feed/Schema.php';
require_once __DIR__ . '/Feed/Verifier.php';
require_once __DIR__ . '/Feed/Repository.php';
require_once __DIR__ . '/Feed/Fetcher.php';
require_once __DIR__ . '/Feed/Selector.php';
require_once __DIR__ . '/Feed/Defaults.php';
require_once __DIR__ . '/Ui/DashboardWidget.php';
require_once __DIR__ . '/Ui/AdminNotice.php';
require_once __DIR__ . '/Rest/DismissController.php';
if ( ! class_exists( '\WPShark\RemoteNotices\Uninstaller', false ) ) {
	require_once __DIR__ . '/Uninstaller.php';
}
require_once __DIR__ . '/Sdk.php';

\WPShark\RemoteNotices\Sdk::boot();

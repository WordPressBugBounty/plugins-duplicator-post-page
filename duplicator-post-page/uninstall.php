<?php
/**
 * Uninstall cleanup.
 *
 * @package DuplicatorPostPage
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'duplicator_post_page_installed' );
delete_option( 'duplicator_post_page_version' );

// WPShark news: removes this plugin's consent, and the shared feed cache,
// cron event and dismissals only when no other WPShark plugin is installed.
if ( ! class_exists( '\WPShark\RemoteNotices\Uninstaller', false ) ) {
	require_once __DIR__ . '/libs/wpshark-remote-notices/src/Uninstaller.php';
}

\WPShark\RemoteNotices\Uninstaller::maybe_cleanup( 'duplicator-post-page', 'duplicator_post_page_remote_notices' );

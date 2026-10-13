<?php
/**
 * Built-in stories.
 *
 * @package WPShark\RemoteNotices
 */

namespace WPShark\RemoteNotices\Feed;

defined( 'ABSPATH' ) || exit;

/**
 * Stories shipped with the SDK, shown in the widget without any remote
 * request. Replaced by the hub's stories once the admin opts in and a
 * feed has been fetched. No images, so the admin's browser makes no
 * third-party request before consent either.
 */
final class Defaults {

	/**
	 * Built-in stories, run through the same schema as remote items.
	 *
	 * @return array[]
	 */
	public static function stories() {
		$utm = '?utm_source=wp-admin&utm_medium=wpshark-news&utm_campaign=builtin';

		return Schema::clean(
			array(
				array(
					'id'          => 900000001,
					'type'        => 'story',
					'title'       => 'How to Display YouTube Feed on Your Website: Ultimate Easy Guide (2026)',
					'description' => 'Step-by-step guide to embed videos, Shorts, and live streams with Ohara Feed Builder. No coding needed.',
					'link'        => 'https://wpshark.xyz/blog/display-youtube-feed-on-your-website/' . $utm,
				),
				array(
					'id'          => 900000002,
					'type'        => 'story',
					'title'       => 'Best WordPress MCP Plugins for AI Agents in 2026',
					'description' => 'Compare Saddle, WPVibe, Novamira, Royal MCP, and more to connect AI agents like Claude and Cursor to WordPress.',
					'link'        => 'https://wpshark.xyz/blog/best-wordpress-mcp-plugins/' . $utm,
				),
				array(
					'id'          => 900000003,
					'type'        => 'story',
					'title'       => 'WordPress Page Speed Optimization: Complete Guide for 2026',
					'description' => 'Server tuning, caching, image compression, CDN setup, and Core Web Vitals fixes, step by step.',
					'link'        => 'https://wpshark.xyz/blog/wordpress-page-speed-optimization/' . $utm,
				),
			)
		);
	}
}

<?php
/**
 * Picks which cached items to show (render time, cache only).
 *
 * @package WPShark\RemoteNotices
 */

namespace WPShark\RemoteNotices\Feed;

use WPShark\RemoteNotices\Product;
use WPShark\RemoteNotices\Sdk;

defined( 'ABSPATH' ) || exit;

/**
 * Applies the display rules: time window, products, active plugins,
 * version range, per-user dismissal and screen.
 */
final class Selector {

	const DISMISSED_META = 'wpshark_sdk_dismissed';

	/**
	 * Active plugin directory slugs (memoized per request).
	 *
	 * @var string[]|null
	 */
	private static $active = null;

	/**
	 * Stories for the dashboard widget: the hub's stories when opted in and
	 * fetched, otherwise the built-in ones.
	 *
	 * @param  int $limit Max stories.
	 * @return array[]
	 */
	public static function stories( $limit = 5 ) {
		$out = array();

		if ( Sdk::enabled() ) {
			foreach ( self::eligible() as $item ) {
				if ( 'story' === $item['type'] ) {
					$out[] = $item;
				}
			}
		}

		if ( ! $out ) {
			$out = Defaults::stories();
		}

		return array_slice( $out, 0, $limit );
	}

	/**
	 * The single highest-priority banner/notice for a screen, or null.
	 *
	 * Only a product's own screens show banners/notices; the main Dashboard
	 * never does (items targeted at it are skipped), and a product that
	 * registers no screens never shows any.
	 *
	 * @param  string $screen_id Current screen ID.
	 * @return array|null
	 */
	public static function notice_for_screen( $screen_id ) {
		$dismissed = self::dismissed();

		foreach ( self::eligible() as $item ) {
			if ( 'story' === $item['type'] || 'dashboard' === $item['screen'] || isset( $dismissed[ self::dismiss_key( $item ) ] ) ) {
				continue;
			}

			foreach ( self::target_products( $item ) as $product ) {
				if ( in_array( $screen_id, $product->screens, true ) ) {
					return $item;
				}
			}
		}

		return null;
	}

	/**
	 * Items that pass every non-screen rule, sorted by priority.
	 *
	 * @return array[]
	 */
	private static function eligible() {
		$now = time();
		$out = array();

		foreach ( Repository::items() as $item ) {
			if ( $item['starts_at'] > $now || ( $item['ends_at'] && $item['ends_at'] <= $now ) ) {
				continue;
			}

			if ( ! self::target_products( $item ) ) {
				continue;
			}

			$active = self::active_slugs();

			if ( array_diff( $item['requires_active'], $active ) || array_intersect( $item['excludes_active'], $active ) ) {
				continue;
			}

			$out[] = $item;
		}

		usort(
			$out,
			static function ( $a, $b ) {
				return array( $b['priority'], $b['starts_at'] ) <=> array( $a['priority'], $a['starts_at'] );
			}
		);

		return $out;
	}

	/**
	 * Consented products this item targets and whose version is in range.
	 * An empty `products` list targets every consented product.
	 *
	 * @param  array $item Item.
	 * @return Product[]
	 */
	private static function target_products( array $item ) {
		$out = array();

		foreach ( Sdk::consented_products() as $product ) {
			if ( $item['products'] && ! in_array( $product->slug, $item['products'], true ) ) {
				continue;
			}

			if ( $item['min_version'] && version_compare( $product->version, $item['min_version'], '<' ) ) {
				continue;
			}

			if ( $item['max_version'] && version_compare( $product->version, $item['max_version'], '>' ) ) {
				continue;
			}

			$out[] = $product;
		}

		return $out;
	}

	/**
	 * Active plugin directory slugs.
	 *
	 * @return string[]
	 */
	private static function active_slugs() {
		if ( null !== self::$active ) {
			return self::$active;
		}

		$files = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$files = array_merge( $files, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		$slugs = array();

		foreach ( $files as $file ) {
			$file    = (string) $file;
			$dir     = dirname( $file );
			$slugs[] = '.' === $dir ? basename( $file, '.php' ) : $dir;
		}

		self::$active = array_values( array_unique( $slugs ) );

		return self::$active;
	}

	/**
	 * Dismissal key: the item ID. A dismissal is permanent, so the hub
	 * cannot re-show an item by editing it.
	 *
	 * @param  array $item Item.
	 * @return string
	 */
	public static function dismiss_key( array $item ) {
		return (string) (int) $item['id'];
	}

	/**
	 * Current user's dismissals: item ID => time dismissed.
	 *
	 * @return array
	 */
	public static function dismissed() {
		$dismissed = get_user_meta( get_current_user_id(), self::DISMISSED_META, true );

		return is_array( $dismissed ) ? $dismissed : array();
	}
}

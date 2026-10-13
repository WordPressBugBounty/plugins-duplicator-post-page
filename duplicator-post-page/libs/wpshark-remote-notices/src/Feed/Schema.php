<?php
/**
 * Strict feed schema.
 *
 * @package WPShark\RemoteNotices
 */

namespace WPShark\RemoteNotices\Feed;

defined( 'ABSPATH' ) || exit;

/**
 * Validates remote items before anything is stored. Items that fail are
 * dropped, so only clean arrays ever reach the cache or the templates.
 */
final class Schema {

	const MAX_ITEMS = 50;

	/**
	 * Hosts that links and images may point to. Checked even after the
	 * signature, so a leaked key still cannot link to a look-alike domain.
	 */
	const HOSTS = array( 'wpshark.xyz', 'wordpress.org', 'ps.w.org' );

	/**
	 * Clean a list of raw items.
	 *
	 * @param  mixed $items Raw items.
	 * @return array[]
	 */
	public static function clean( $items ) {
		if ( ! is_array( $items ) ) {
			return array();
		}

		$out  = array();
		$seen = array();

		foreach ( array_slice( $items, 0, self::MAX_ITEMS ) as $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}

			$item = self::clean_item( $raw );

			if ( null === $item || isset( $seen[ $item['id'] ] ) ) {
				continue;
			}

			$seen[ $item['id'] ] = true;
			$out[]               = $item;
		}

		return $out;
	}

	/**
	 * Clean one item, or null when it is invalid.
	 *
	 * @param  array $raw Raw item.
	 * @return array|null
	 */
	private static function clean_item( array $raw ) {
		$type = isset( $raw['type'] ) && is_string( $raw['type'] ) ? $raw['type'] : '';
		if ( ! in_array( $type, array( 'story', 'banner', 'notice' ), true ) ) {
			return null;
		}

		$id = self::int( $raw, 'id' );
		if ( $id < 1 ) {
			return null;
		}

		$link = self::url( $raw['link'] ?? '' );
		if ( '' === $link ) {
			return null;
		}

		$title = self::text( $raw['title'] ?? '', 120 );
		if ( '' === $title ) {
			return null;
		}

		$starts = self::int( $raw, 'starts_at' );
		$ends   = self::int( $raw, 'ends_at' );

		return array(
			'id'              => $id,
			'type'            => $type,
			'title'           => $title,
			'description'     => self::text( $raw['description'] ?? '', 300, true ),
			'link'            => $link,
			'cta_text'        => self::text( $raw['cta_text'] ?? '', 40 ),
			'badge'           => self::text( $raw['badge'] ?? '', 20 ),
			'image'           => self::url( $raw['image'] ?? '' ),
			'variant'         => self::enum( $raw['variant'] ?? '', array( 'info', 'success', 'warning', 'promo' ), 'info' ),
			'products'        => self::slugs( $raw['products'] ?? array() ),
			'requires_active' => self::slugs( $raw['requires_active'] ?? array() ),
			'excludes_active' => self::slugs( $raw['excludes_active'] ?? array() ),
			'screen'          => self::enum( $raw['screen'] ?? '', array( 'dashboard', 'plugin_page' ), 'plugin_page' ),
			'min_version'     => self::version( $raw['min_version'] ?? '' ),
			'max_version'     => self::version( $raw['max_version'] ?? '' ),
			'starts_at'       => $starts,
			'ends_at'         => ( $ends > $starts ) ? $ends : 0,
			'priority'        => min( 100, self::int( $raw, 'priority', 50 ) ),
		);
	}

	/**
	 * Non-negative int from a raw field (rejects arrays/objects).
	 *
	 * @param  array  $raw     Raw item.
	 * @param  string $key     Key.
	 * @param  int    $default Default.
	 * @return int
	 */
	private static function int( array $raw, $key, $default = 0 ) {
		if ( ! isset( $raw[ $key ] ) || ! is_scalar( $raw[ $key ] ) ) {
			return $default;
		}

		return absint( $raw[ $key ] );
	}

	/**
	 * Plain text with a length cap.
	 *
	 * @param  mixed $value     Raw.
	 * @param  int   $max       Max characters.
	 * @param  bool  $multiline Keep line breaks.
	 * @return string
	 */
	private static function text( $value, $max, $multiline = false ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = $multiline ? sanitize_textarea_field( (string) $value ) : sanitize_text_field( (string) $value );

		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
	}

	/**
	 * Allow-listed enum.
	 *
	 * @param  mixed  $value    Raw.
	 * @param  array  $allowed  Allowed values.
	 * @param  string $fallback Fallback.
	 * @return string
	 */
	private static function enum( $value, array $allowed, $fallback ) {
		return ( is_string( $value ) && in_array( $value, $allowed, true ) ) ? $value : $fallback;
	}

	/**
	 * Slug list: ^[a-z0-9-]{1,64}$, max 20.
	 *
	 * @param  mixed $value Raw.
	 * @return string[]
	 */
	private static function slugs( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$out = array();

		foreach ( $value as $slug ) {
			if ( is_string( $slug ) && preg_match( '/^[a-z0-9-]{1,64}$/', $slug ) ) {
				$out[] = $slug;
			}
		}

		return array_slice( array_values( array_unique( $out ) ), 0, 20 );
	}

	/**
	 * Version string or empty.
	 *
	 * @param  mixed $value Raw.
	 * @return string
	 */
	private static function version( $value ) {
		return ( is_string( $value ) && preg_match( '/^\d+(\.\d+){0,3}$/', $value ) ) ? $value : '';
	}

	/**
	 * URL on an allowed host, https only.
	 *
	 * The local-testing host set by WPSHARK_SDK_FEED_URL is also allowed,
	 * and may use http.
	 *
	 * @param  mixed $value Raw.
	 * @return string Clean URL or ''.
	 */
	public static function url( $value ) {
		if ( ! is_string( $value ) || '' === $value || strlen( $value ) > 2048 ) {
			return '';
		}

		$parts = wp_parse_url( $value );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '';
		}

		$scheme   = strtolower( $parts['scheme'] );
		$host     = strtolower( $parts['host'] );
		$dev_host = Fetcher::dev_host();

		if ( '' !== $dev_host && $host === $dev_host ) {
			if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
				return '';
			}
		} else {
			if ( 'https' !== $scheme || ! self::host_allowed( $host ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
				return '';
			}
		}

		return esc_url_raw( $value, array( 'http', 'https' ) );
	}

	/**
	 * Host is on the allowlist, or a subdomain of an allowed host.
	 *
	 * @param  string $host Lower-case host.
	 * @return bool
	 */
	private static function host_allowed( $host ) {
		foreach ( self::HOSTS as $allowed ) {
			if ( $host === $allowed || substr( $host, -strlen( '.' . $allowed ) ) === '.' . $allowed ) {
				return true;
			}
		}

		return false;
	}
}

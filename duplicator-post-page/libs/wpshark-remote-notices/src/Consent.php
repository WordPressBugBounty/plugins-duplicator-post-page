<?php
/**
 * Opt-in consent.
 *
 * @package WPShark\RemoteNotices
 */

namespace WPShark\RemoteNotices;

defined( 'ABSPATH' ) || exit;

/**
 * Per-product opt-in. Nothing is fetched until at least one product is 'yes'.
 */
final class Consent {

	const YES = 'yes';
	const NO  = 'no';

	/**
	 * Current state: 'yes', 'no' or '' (not decided yet).
	 *
	 * @param  Product $product Product.
	 * @return string
	 */
	public static function get( Product $product ) {
		$value = get_option( $product->consent_option, '' );

		return in_array( $value, array( self::YES, self::NO ), true ) ? $value : '';
	}

	/**
	 * Whether the product opted in.
	 *
	 * @param  Product $product Product.
	 * @return bool
	 */
	public static function granted( Product $product ) {
		return self::YES === self::get( $product );
	}

	/**
	 * Store a decision.
	 *
	 * @param  Product $product Product.
	 * @param  bool    $granted Opt in or out.
	 * @return void
	 */
	public static function set( Product $product, $granted ) {
		// Autoloaded: tiny, and read on every request by Sdk::enabled().
		update_option( $product->consent_option, $granted ? self::YES : self::NO, true );
	}
}

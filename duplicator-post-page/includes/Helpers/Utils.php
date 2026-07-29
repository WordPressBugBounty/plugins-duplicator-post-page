<?php

namespace DuplicatorPostPage\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Global helper class.
 *
 * @since 1.0.3
 */

class Utils { 
	/**
	 * Adds slashes only to strings.
	 *
	 * @param mixed $value Value to slash only if string.
	 * @return string|mixed
	 */
	public static function addslashes_to_strings_only( $value ) {
		return is_string( $value ) ? addslashes( $value ) : $value;
	}

	/**
	 * Replaces faulty core wp_slash().
	 *
	 * @param mixed $value What to add slashes to.
	 * @return mixed
	 */
	public static function recursively_slash_strings( $value ) {
		return map_deep( $value, [ self::class, 'addslashes_to_strings_only' ] );
	}
}
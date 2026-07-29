<?php

namespace DuplicatorPostPage\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Contract for a single ability definition.
 *
 * Each implementation describes one ability: its namespaced name and the
 * argument array handed to `wp_register_ability()`.
 *
 * @since 1.2.45
 */
interface AbilityDefinition {

	/**
	 * The namespaced ability name, e.g. `duplicator-post-page/duplicate-post`.
	 *
	 * @since 1.2.45
	 *
	 * @return string
	 */
	public static function get_name();

	/**
	 * The argument array passed to `wp_register_ability()`.
	 *
	 * @since 1.2.45
	 *
	 * @return array
	 */
	public static function get_registration_args();
}

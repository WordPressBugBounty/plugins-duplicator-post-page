<?php

namespace DuplicatorPostPage\Abilities;

use DuplicatorPostPage\Abilities\Domain\DuplicatePost;
use DuplicatorPostPage\Abilities\Domain\ListDuplicatablePosts;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks this plugin's ability definitions into the WordPress Abilities API.
 *
 * The Abilities API landed in WordPress 6.9. On older versions the registration
 * functions simply do not exist and this loader stays inert, so the plugin keeps
 * working exactly as before.
 *
 * @since 1.2.45
 */
class AbilitiesLoader {

	/**
	 * Ability name namespace reserved by this plugin.
	 *
	 * @var string
	 */
	const NAMESPACE_PREFIX = 'duplicator-post-page/';

	/**
	 * Whether the loader hooks have been registered.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Ability definition classes shipped with this plugin.
	 *
	 * @var array
	 */
	private static $core_ability_definition_classes = [
		DuplicatePost::class,
		ListDuplicatablePosts::class,
	];

	/**
	 * Register the ability registration hooks.
	 *
	 * @since 1.2.45
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		AbilitiesCategories::init();

		// Support both the pre-6.9 Abilities API plugin and the 6.9+ core action names.
		add_action( 'abilities_api_init', [ __CLASS__, 'register_abilities' ] );
		add_action( 'wp_abilities_api_init', [ __CLASS__, 'register_abilities' ] );

		self::$initialized = true;
	}

	/**
	 * Register every configured ability definition.
	 *
	 * @since 1.2.45
	 *
	 * @return void
	 */
	public static function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		foreach ( self::get_ability_definition_classes() as $class_name ) {
			if ( ! is_string( $class_name ) || ! class_exists( $class_name ) ) {
				continue;
			}

			if ( ! is_a( $class_name, AbilityDefinition::class, true ) ) {
				continue;
			}

			$ability_name = call_user_func( [ $class_name, 'get_name' ] );

			if ( ! is_string( $ability_name ) || '' === $ability_name ) {
				continue;
			}

			// Never clobber an ability another plugin already claimed under this name.
			if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $ability_name ) ) {
				continue;
			}

			wp_register_ability( $ability_name, call_user_func( [ $class_name, 'get_registration_args' ] ) );
		}
	}

	/**
	 * Get all ability definition classes that should be registered.
	 *
	 * @since 1.2.45
	 *
	 * @return array List of class names.
	 */
	private static function get_ability_definition_classes() {
		/**
		 * Filters the ability definition classes registered by this plugin.
		 *
		 * Extensions may append autoloadable classes implementing
		 * {@see AbilityDefinition}. Returning a subset does not unregister the
		 * plugin's own abilities; those are always retained.
		 *
		 * @since 1.2.45
		 *
		 * @param array $classes Ability definition class names.
		 */
		$classes = apply_filters( 'duplicator_post_page/ability_definition_classes', self::$core_ability_definition_classes );

		if ( ! is_array( $classes ) ) {
			$classes = [];
		}

		return array_values(
			array_unique(
				array_filter(
					array_merge( self::$core_ability_definition_classes, $classes ),
					'is_string'
				)
			)
		);
	}
}

<?php

namespace DuplicatorPostPage\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the ability category this plugin's abilities belong to.
 *
 * Categories must exist before the abilities that reference them, so this
 * hooks the dedicated categories action that fires first.
 *
 * @since 1.2.45
 */
class AbilitiesCategories {

	/**
	 * Category slug shared by all abilities in this plugin.
	 *
	 * @var string
	 */
	const SLUG = 'content-duplication';

	/**
	 * Whether the hooks have been registered.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Register the category hooks.
	 *
	 * @since 1.2.45
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		// Support both the pre-6.9 Abilities API plugin and the 6.9+ core action names.
		add_action( 'abilities_api_categories_init', [ __CLASS__, 'register_categories' ] );
		add_action( 'wp_abilities_api_categories_init', [ __CLASS__, 'register_categories' ] );

		self::$initialized = true;
	}

	/**
	 * Register the category.
	 *
	 * @since 1.2.45
	 *
	 * @return void
	 */
	public static function register_categories() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( self::SLUG ) ) {
			return;
		}

		wp_register_ability_category(
			self::SLUG,
			[
				'label'       => __( 'Content Duplication', 'duplicator-post-page' ),
				'description' => __( 'Abilities that copy existing posts and pages into new drafts.', 'duplicator-post-page' ),
			]
		);
	}
}

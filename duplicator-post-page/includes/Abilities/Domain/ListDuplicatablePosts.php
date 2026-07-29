<?php

namespace DuplicatorPostPage\Abilities\Domain;

use DuplicatorPostPage\Abilities\AbilitiesCategories;
use DuplicatorPostPage\Abilities\AbilitiesLoader;
use DuplicatorPostPage\Abilities\AbilityDefinition;
use DuplicatorPostPage\Duplicator;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Ability: find posts and pages the current user is allowed to duplicate.
 *
 * This is the lookup step for agents that know a title but not an ID.
 *
 * @since 1.2.45
 */
class ListDuplicatablePosts implements AbilityDefinition {

	/**
	 * Maximum number of results that can be requested at once.
	 *
	 * @var int
	 */
	const MAX_PER_PAGE = 50;

	/**
	 * The namespaced ability name.
	 *
	 * @since 1.2.45
	 *
	 * @return string
	 */
	public static function get_name() {
		return AbilitiesLoader::NAMESPACE_PREFIX . 'list-duplicatable-posts';
	}

	/**
	 * The argument array passed to `wp_register_ability()`.
	 *
	 * @since 1.2.45
	 *
	 * @return array
	 */
	public static function get_registration_args() {
		return [
			'label'               => __( 'List Duplicatable Posts and Pages', 'duplicator-post-page' ),
			'description'         => __( 'Returns posts and pages the current user is allowed to duplicate, optionally filtered by a search term or post type. Use this to resolve a title into the post ID required by the duplicate-post ability.', 'duplicator-post-page' ),
			'category'            => AbilitiesCategories::SLUG,
			'input_schema'        => [
				'type'                 => 'object',
				'properties'           => [
					'search'    => [
						'type'        => 'string',
						'description' => __( 'Optional search term matched against the title and content.', 'duplicator-post-page' ),
					],
					'post_type' => [
						'type'        => 'string',
						'description' => __( 'Optional post type to filter by. Defaults to all duplicatable post types.', 'duplicator-post-page' ),
					],
					'per_page'  => [
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => self::MAX_PER_PAGE,
						'default'     => 10,
						'description' => __( 'Maximum number of results to return.', 'duplicator-post-page' ),
					],
				],
				'additionalProperties' => false,
				'default'              => [],
			],
			'output_schema'       => [
				'type'                 => 'object',
				'properties'           => [
					'posts' => [
						'type'        => 'array',
						'description' => __( 'The matching posts and pages.', 'duplicator-post-page' ),
						'items'       => [
							'type'                 => 'object',
							'properties'           => [
								'post_id'   => [
									'type'        => 'integer',
									'description' => __( 'The post ID, for use with the duplicate-post ability.', 'duplicator-post-page' ),
								],
								'title'     => [
									'type'        => 'string',
									'description' => __( 'The post title.', 'duplicator-post-page' ),
								],
								'post_type' => [
									'type'        => 'string',
									'description' => __( 'The post type.', 'duplicator-post-page' ),
								],
								'status'    => [
									'type'        => 'string',
									'description' => __( 'The post status.', 'duplicator-post-page' ),
								],
								'modified'  => [
									'type'        => 'string',
									'description' => __( 'Date the post was last modified, in GMT.', 'duplicator-post-page' ),
								],
							],
							'additionalProperties' => false,
						],
					],
					'total' => [
						'type'        => 'integer',
						'description' => __( 'Number of posts returned.', 'duplicator-post-page' ),
					],
				],
				'additionalProperties' => false,
			],
			'execute_callback'    => [ __CLASS__, 'execute' ],
			'permission_callback' => [ __CLASS__, 'has_permission' ],
			'meta'                => [
				'annotations'  => [
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				],
				'show_in_rest' => true,
			],
		];
	}

	/**
	 * Permission check for the ability.
	 *
	 * Per-post capabilities are checked again while building the results, so
	 * this only gates the lookup itself.
	 *
	 * @since 1.2.45
	 *
	 * @param array $input Validated ability input.
	 * @return bool
	 */
	public static function has_permission( $input = [] ) {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Query posts the current user may duplicate.
	 *
	 * @since 1.2.45
	 *
	 * @param array $input Validated ability input.
	 * @return array
	 */
	public static function execute( $input = [] ) {
		$input = is_array( $input ) ? $input : [];

		$post_types = Duplicator::get_duplicatable_post_types();

		if ( ! empty( $input['post_type'] ) ) {
			$post_types = array_values( array_intersect( $post_types, [ $input['post_type'] ] ) );
		}

		if ( empty( $post_types ) ) {
			return [
				'posts' => [],
				'total' => 0,
			];
		}

		$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 10;
		$per_page = max( 1, min( self::MAX_PER_PAGE, $per_page ) );

		$query_args = [
			'post_type'           => $post_types,
			'post_status'         => [ 'publish', 'draft', 'pending', 'private', 'future' ],
			'posts_per_page'      => $per_page,
			'orderby'             => 'modified',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'suppress_filters'    => false,
		];

		if ( ! empty( $input['search'] ) ) {
			$query_args['s'] = $input['search'];
		}

		$results = [];

		foreach ( ( new WP_Query( $query_args ) )->posts as $post ) {
			// Skip anything this user cannot actually duplicate.
			if ( is_wp_error( Duplicator::can_duplicate( $post->ID ) ) ) {
				continue;
			}

			$results[] = [
				'post_id'   => (int) $post->ID,
				'title'     => $post->post_title,
				'post_type' => $post->post_type,
				'status'    => $post->post_status,
				'modified'  => $post->post_modified_gmt,
			];
		}

		return [
			'posts' => $results,
			'total' => count( $results ),
		];
	}
}

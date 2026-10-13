<?php

namespace DuplicatorPostPage\Abilities\Domain;

use DuplicatorPostPage\Abilities\AbilitiesCategories;
use DuplicatorPostPage\Abilities\AbilitiesLoader;
use DuplicatorPostPage\Abilities\AbilityDefinition;
use DuplicatorPostPage\Duplicator;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Ability: duplicate a single post or page into a new draft.
 *
 * @since 1.2.45
 */
class DuplicatePost implements AbilityDefinition {

	/**
	 * Statuses the copy may be created with.
	 *
	 * Publishing is deliberately excluded: an agent should never push a copy
	 * live without a human reviewing it first.
	 *
	 * @var array
	 */
	const ALLOWED_STATUSES = [ 'draft', 'pending', 'private' ];

	/**
	 * The namespaced ability name.
	 *
	 * @since 1.2.45
	 *
	 * @return string
	 */
	public static function get_name() {
		return AbilitiesLoader::NAMESPACE_PREFIX . 'duplicate-post';
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
			'label'               => __( 'Duplicate Post or Page', 'duplicator-post-page' ),
			'description'         => __( 'Creates a copy of an existing post or page, including its content, custom fields and taxonomy terms. The copy is saved as a draft (never published) and its ID and edit URL are returned. Use the list-duplicatable-posts ability first if you only know the title and need the ID.', 'duplicator-post-page' ),
			'category'            => AbilitiesCategories::SLUG,
			'input_schema'        => [
				'type'                 => 'object',
				'properties'           => [
					'post_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The ID of the post or page to duplicate.', 'duplicator-post-page' ),
					],
					'title'   => [
						'type'        => 'string',
						'description' => __( 'Optional title for the copy. Defaults to the original title with " (Copy)" appended.', 'duplicator-post-page' ),
					],
					'status'  => [
						'type'        => 'string',
						'enum'        => self::ALLOWED_STATUSES,
						'default'     => 'draft',
						'description' => __( 'Status for the copy. Defaults to draft.', 'duplicator-post-page' ),
					],
				],
				'required'             => [ 'post_id' ],
				'additionalProperties' => false,
			],
			'output_schema'       => [
				'type'                 => 'object',
				'properties'           => [
					'post_id'          => [
						'type'        => 'integer',
						'description' => __( 'The ID of the newly created copy.', 'duplicator-post-page' ),
					],
					'original_post_id' => [
						'type'        => 'integer',
						'description' => __( 'The ID of the post that was duplicated.', 'duplicator-post-page' ),
					],
					'title'            => [
						'type'        => 'string',
						'description' => __( 'The title of the copy.', 'duplicator-post-page' ),
					],
					'post_type'        => [
						'type'        => 'string',
						'description' => __( 'The post type of the copy.', 'duplicator-post-page' ),
					],
					'status'           => [
						'type'        => 'string',
						'description' => __( 'The status of the copy.', 'duplicator-post-page' ),
					],
					'edit_url'         => [
						'type'        => 'string',
						'description' => __( 'Admin URL for editing the copy.', 'duplicator-post-page' ),
					],
				],
				'additionalProperties' => false,
			],
			'execute_callback'    => [ __CLASS__, 'execute' ],
			'permission_callback' => [ __CLASS__, 'has_permission' ],
			'meta'                => [
				'annotations'  => [
					'readonly'    => false,
					// Only ever adds a new draft; the original is left untouched.
					'destructive' => false,
					// Calling twice yields two copies.
					'idempotent'  => false,
				],
				'show_in_rest' => true,
			],
		];
	}

	/**
	 * Permission check for the ability.
	 *
	 * @since 1.2.45
	 *
	 * @param array $input Validated ability input.
	 * @return bool|WP_Error
	 */
	public static function has_permission( $input = [] ) {
		$input = is_array( $input ) ? $input : [];

		if ( empty( $input['post_id'] ) ) {
			return new WP_Error(
				'duplicator_post_page_missing_post_id',
				__( 'A post ID is required.', 'duplicator-post-page' ),
				[ 'status' => 400 ]
			);
		}

		return Duplicator::can_duplicate( $input['post_id'] );
	}

	/**
	 * Duplicate the requested post.
	 *
	 * @since 1.2.45
	 *
	 * @param array $input Validated ability input.
	 * @return array|WP_Error Details of the new copy, or WP_Error on failure.
	 */
	public static function execute( $input = [] ) {
		$input = is_array( $input ) ? $input : [];

		$overrides = [];

		if ( ! empty( $input['title'] ) ) {
			$overrides['post_title'] = $input['title'];
		}

		if ( ! empty( $input['status'] ) && in_array( $input['status'], self::ALLOWED_STATUSES, true ) ) {
			$overrides['post_status'] = $input['status'];
		}

		$new_post_id = Duplicator::duplicate( $input['post_id'], $overrides );

		if ( is_wp_error( $new_post_id ) ) {
			return $new_post_id;
		}

		$new_post = get_post( $new_post_id );

		return [
			'post_id'          => (int) $new_post_id,
			'original_post_id' => (int) $input['post_id'],
			'title'            => $new_post->post_title,
			'post_type'        => $new_post->post_type,
			'status'           => $new_post->post_status,
			'edit_url'         => get_edit_post_link( $new_post_id, 'raw' ),
		];
	}
}

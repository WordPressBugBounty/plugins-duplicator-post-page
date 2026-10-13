<?php

namespace DuplicatorPostPage;

use DuplicatorPostPage\Helpers\Utils as Duplicator_Post_Page_Helper;
use WP_Error;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class Duplicator
 *
 * Shared duplication service. Holds the copy logic used by both the admin
 * row action and the Abilities API, so both entry points behave identically.
 *
 * @since 1.2.45
 */
class Duplicator {

	/**
	 * Meta keys that are never carried over to the copy.
	 *
	 * @var array
	 */
	const SKIPPED_META_KEYS = [ '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date' ];

	/**
	 * Post types that may be duplicated.
	 *
	 * @since 1.2.45
	 *
	 * @return array List of post type slugs.
	 */
	public static function get_duplicatable_post_types() {
		/**
		 * Filters the post types that can be duplicated.
		 *
		 * @since 1.2.45
		 *
		 * @param array $post_types List of post type slugs.
		 */
		$post_types = apply_filters( 'duplicator_post_page/duplicatable_post_types', [ 'post', 'page' ] );

		return array_values( array_filter( array_map( 'strval', (array) $post_types ) ) );
	}

	/**
	 * Checks whether the current user may duplicate the given post.
	 *
	 * Unlike the admin row action, this also verifies the user is allowed to
	 * create a new post of the same type, since duplication always inserts one.
	 *
	 * @since 1.2.45
	 *
	 * @param int $post_id The post to duplicate.
	 * @return true|WP_Error True when allowed, WP_Error otherwise.
	 */
	public static function can_duplicate( $post_id ) {
		$post = get_post( absint( $post_id ) );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error(
				'duplicator_post_page_post_not_found',
				__( 'No post or page was found with the given ID.', 'duplicator-post-page' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! in_array( $post->post_type, self::get_duplicatable_post_types(), true ) ) {
			return new WP_Error(
				'duplicator_post_page_unsupported_post_type',
				sprintf(
					/* translators: %s: post type slug. */
					__( 'The "%s" post type cannot be duplicated.', 'duplicator-post-page' ),
					$post->post_type
				),
				[ 'status' => 400 ]
			);
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return new WP_Error(
				'duplicator_post_page_cannot_read',
				__( 'You are not allowed to duplicate this post.', 'duplicator-post-page' ),
				[ 'status' => 403 ]
			);
		}

		$post_type_object = get_post_type_object( $post->post_type );

		if ( ! $post_type_object || ! current_user_can( $post_type_object->cap->create_posts ) ) {
			return new WP_Error(
				'duplicator_post_page_cannot_create',
				__( 'You are not allowed to create new content of this type.', 'duplicator-post-page' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Duplicates a post or page as a new draft.
	 *
	 * Copies the post fields, all post meta (minus editor bookkeeping keys) and
	 * every taxonomy term attached to the original.
	 *
	 * @since 1.2.45
	 *
	 * @param int   $post_id   The post to duplicate.
	 * @param array $overrides {
	 *     Optional. Field overrides applied to the copy.
	 *
	 *     @type string $post_title  Title for the copy. Defaults to the original title with " (Copy)" appended.
	 *     @type string $post_status Status for the copy. Default 'draft'.
	 * }
	 * @return int|WP_Error The new post ID, or WP_Error on failure.
	 */
	public static function duplicate( $post_id, $overrides = [] ) {
		$post_id = absint( $post_id );
		$allowed = self::can_duplicate( $post_id );

		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$post = get_post( $post_id );

		$new_post = [
			'post_title'     => Duplicator_Post_Page_Helper::recursively_slash_strings( $post->post_title . ' (Copy)' ),
			'post_content'   => Duplicator_Post_Page_Helper::recursively_slash_strings( $post->post_content ),
			'post_excerpt'   => Duplicator_Post_Page_Helper::recursively_slash_strings( $post->post_excerpt ),
			'post_status'    => 'draft',
			'post_type'      => $post->post_type,
			'post_author'    => $post->post_author,
			'post_parent'    => $post->post_parent,
			'menu_order'     => $post->menu_order,
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
		];

		if ( ! empty( $overrides['post_title'] ) ) {
			$new_post['post_title'] = Duplicator_Post_Page_Helper::recursively_slash_strings( $overrides['post_title'] );
		}

		if ( ! empty( $overrides['post_status'] ) ) {
			$new_post['post_status'] = $overrides['post_status'];
		}

		/**
		 * Filters the arguments used to create the duplicated post.
		 *
		 * @since 1.2.45
		 *
		 * @param array   $new_post Arguments passed to wp_insert_post().
		 * @param WP_Post $post     The post being duplicated.
		 */
		$new_post = apply_filters( 'duplicator_post_page/new_post_args', $new_post, $post );

		$new_post_id = wp_insert_post( $new_post, true );

		if ( is_wp_error( $new_post_id ) ) {
			return $new_post_id;
		}

		if ( ! $new_post_id ) {
			return new WP_Error(
				'duplicator_post_page_insert_failed',
				__( 'The duplicate could not be created.', 'duplicator-post-page' ),
				[ 'status' => 500 ]
			);
		}

		self::copy_meta( $post_id, $new_post_id );
		self::copy_taxonomies( $post, $new_post_id );

		/**
		 * Fires after a post has been duplicated.
		 *
		 * @since 1.2.45
		 *
		 * @param int     $new_post_id The ID of the newly created copy.
		 * @param WP_Post $post        The post that was duplicated.
		 */
		do_action( 'duplicator_post_page/duplicated', $new_post_id, $post );

		return (int) $new_post_id;
	}

	/**
	 * Copies post meta from the original to the copy.
	 *
	 * @since 1.2.45
	 *
	 * @param int $post_id     Source post ID.
	 * @param int $new_post_id Destination post ID.
	 * @return void
	 */
	private static function copy_meta( $post_id, $new_post_id ) {
		/**
		 * Filters the meta keys skipped when duplicating a post.
		 *
		 * @since 1.2.45
		 *
		 * @param array $skipped_keys Meta keys that are not copied.
		 * @param int   $post_id      Source post ID.
		 */
		$skipped_keys = apply_filters( 'duplicator_post_page/skipped_meta_keys', self::SKIPPED_META_KEYS, $post_id );

		foreach ( get_post_meta( $post_id ) as $meta_key => $meta_values ) {
			if ( in_array( $meta_key, (array) $skipped_keys, true ) ) {
				continue;
			}

			foreach ( $meta_values as $meta_value ) {
				/*
				 * get_post_meta() without a key returns raw, still-serialized values, so
				 * unserialize first and slash afterwards. Slashing first would escape the
				 * quotes inside the serialized string, making maybe_unserialize() fail and
				 * silently store an empty value. The slashing itself is required because
				 * add_post_meta() runs wp_unslash() on whatever it is given.
				 */
				add_post_meta( $new_post_id, $meta_key, Duplicator_Post_Page_Helper::recursively_slash_strings( maybe_unserialize( $meta_value ) ) );
			}
		}
	}

	/**
	 * Copies every taxonomy term from the original to the copy.
	 *
	 * @since 1.2.45
	 *
	 * @param WP_Post $post        Source post.
	 * @param int     $new_post_id Destination post ID.
	 * @return void
	 */
	private static function copy_taxonomies( $post, $new_post_id ) {
		foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
			$terms = wp_get_object_terms( $post->ID, $taxonomy, [ 'fields' => 'ids' ] );

			if ( is_wp_error( $terms ) ) {
				continue;
			}

			wp_set_object_terms( $new_post_id, $terms, $taxonomy );
		}
	}
}

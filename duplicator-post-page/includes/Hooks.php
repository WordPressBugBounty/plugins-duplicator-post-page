<?php

namespace DuplicatorPostPage;
use DuplicatorPostPage\Helpers\Utils as Duplicator_Post_Page_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Class Hooks
 * Handles the duplication of posts/pages and custom actions/filters.
 *
 * @since 1.1.1
 */
class Hooks {

	/**
	 * Constructor to register hooks.
	 */
	public function __construct() {
		add_action( 'admin_action_duplicator_post_page', [ $this, 'duplicator_post_page' ] );
		add_filter( 'post_row_actions', [ $this, 'duplicator_post_page_link' ], 10, 2 );
		add_filter( 'page_row_actions', [ $this, 'duplicator_post_page_link' ], 10, 2 );
	}

	/**
	 * Handles duplication of posts/pages.
	 */
	public function duplicator_post_page() {
		if ( isset( $_GET['action'], $_GET['post'] ) && $_GET['action'] === 'duplicator_post_page' ) {
			// Verify the nonce.
			if ( ! check_admin_referer( 'duplicator_post_page_nonce' ) ) {
				wp_die( esc_html__( 'Nonce verification failed.', 'duplicator-post-page' ) );
			}

			// Sanitize the post ID.
			$post_id = absint( wp_unslash( $_GET['post'] ) );

			// Check permissions.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				wp_die( esc_html__( 'You are not allowed to duplicate this post.', 'duplicator-post-page' ) );
			}

			$post = get_post( $post_id );

			if ( $post ) {
				$new_post = [
					'post_title'    => Duplicator_Post_Page_Helper::recursively_slash_strings( $post->post_title . ' (Copy)' ),
					'post_content'  => Duplicator_Post_Page_Helper::recursively_slash_strings( $post->post_content ),
					'post_status'   => 'draft',
					'post_type'     => $post->post_type,
					'post_author'   => $post->post_author,
				];

				$new_post_id = wp_insert_post( $new_post );

				if ( $new_post_id ) {
					// Copy metadata.
					$post_meta = get_post_meta( $post_id );
					foreach ( $post_meta as $meta_key => $meta_values ) {
						foreach ( $meta_values as $meta_value ) {
							add_post_meta( $new_post_id, $meta_key, maybe_unserialize( Duplicator_Post_Page_Helper::recursively_slash_strings( $meta_value ) ) );
						}
					}

					// Copy taxonomies.
					$taxonomies = get_object_taxonomies( $post->post_type );
					foreach ( $taxonomies as $taxonomy ) {
						$terms = wp_get_object_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );
						wp_set_object_terms( $new_post_id, $terms, $taxonomy );
					}

					// Redirect to the edit screen for the new post/page.
					wp_redirect( admin_url( 'edit.php?post_type=' . $post->post_type ) );
					exit;
				}
			}
		}
	}

	/**
	 * Adds a "Duplicate" link to the post/page actions.
	 *
	 * @param array $actions List of row actions.
	 * @param WP_Post $post The current post object.
	 * @return array Modified list of row actions.
	 */
	public function duplicator_post_page_link( $actions, $post ) {
		if ( in_array( $post->post_type, [ 'post', 'page' ], true ) && current_user_can( 'edit_posts' ) ) {
			$actions['duplicate'] = sprintf(
				'<a href="%s" title="%s" rel="permalink">%s</a>',
				esc_url( wp_nonce_url( 'admin.php?action=duplicator_post_page&post=' . $post->ID, 'duplicator_post_page_nonce' ) ),
				esc_attr__( 'Duplicate this', 'duplicator-post-page' ),
				esc_html__( 'Duplicate', 'duplicator-post-page' )
			);
		}
		return $actions;
	}
}
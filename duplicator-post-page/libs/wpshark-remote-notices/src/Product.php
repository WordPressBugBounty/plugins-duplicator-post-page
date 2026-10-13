<?php
/**
 * Registered product value object.
 *
 * @package WPShark\RemoteNotices
 */

namespace WPShark\RemoteNotices;

defined( 'ABSPATH' ) || exit;

/**
 * One WPShark plugin that uses the SDK.
 */
final class Product {

	/**
	 * Plugin directory slug.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Display name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Installed version.
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Screen IDs where this product's banners/notices may appear.
	 *
	 * @var string[]
	 */
	public $screens;

	/**
	 * Option that stores this product's consent ('yes' | 'no').
	 *
	 * @var string
	 */
	public $consent_option;

	/**
	 * Plugin basename (dir/file.php), for the Plugins screen link.
	 *
	 * @var string
	 */
	public $plugin_file;

	/**
	 * Build from registration args.
	 *
	 * @param array $args {
	 *     @type string   $slug           Required. Plugin directory slug.
	 *     @type string   $name           Required. Display name.
	 *     @type string   $version        Plugin version.
	 *     @type string[] $screens        Screen IDs for banners/notices.
	 *     @type string   $consent_option Option name for consent.
	 *     @type string   $plugin_file    Main plugin file (absolute path or basename).
	 * }
	 * @throws \InvalidArgumentException On an invalid slug.
	 */
	public function __construct( array $args ) {
		$slug = isset( $args['slug'] ) ? (string) $args['slug'] : '';

		if ( ! preg_match( '/^[a-z0-9-]{1,64}$/', $slug ) ) {
			throw new \InvalidArgumentException( 'Invalid WPShark product slug.' );
		}

		$this->slug           = $slug;
		$this->name           = isset( $args['name'] ) ? sanitize_text_field( (string) $args['name'] ) : $slug;
		$this->version        = isset( $args['version'] ) ? (string) $args['version'] : '0';
		$this->screens        = isset( $args['screens'] ) ? array_values( array_filter( array_map( 'strval', (array) $args['screens'] ) ) ) : array();
		$this->consent_option = ! empty( $args['consent_option'] ) ? sanitize_key( $args['consent_option'] ) : str_replace( '-', '_', $slug ) . '_remote_notices';
		$this->plugin_file    = '';

		if ( ! empty( $args['plugin_file'] ) ) {
			$file              = (string) $args['plugin_file'];
			$this->plugin_file = ( false !== strpos( $file, WP_PLUGIN_DIR ) || file_exists( $file ) ) ? plugin_basename( $file ) : $file;
		}
	}
}

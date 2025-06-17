<?php

/**
 * Plugin Name:       Duplicator Post Page - One-Click Duplicator, Page & Post Copier, Content Duplicator
 * Description:       Duplicate posts and pages with a single click.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           1.2.14
 * Author:            Iqbal Hossain
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       duplicator-post-page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Final class for the Duplicator Post Page plugin.
 *
 * @since 1.1.1
 */
final class Duplicator_Post_Page {
	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.2.14';

	/**
	 * Plugin instance.
	 *
	 * @var Duplicator_Post_Page|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance of the plugin.
	 *
	 * @return Duplicator_Post_Page
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->define_constants();
		$this->includes();
		$this->register_activation_hooks();

		// Load translation
		add_action( 'init', array( $this, 'i18n' ) );

		// Initialize the plugin after all plugins are loaded.
		add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );

		// Hook into 'plugin_action_links' filter
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [ $this, 'plugin_action_links' ] );

		// Add custom row meta for plugin description.
		add_filter( 'plugin_row_meta', [ $this, 'duplicator_post_page_plugin_row_meta' ], 10, 2 );
	}

	/**
	 * Define plugin constants.
	 */
	private function define_constants() {
		define( 'DUPLICATOR_POST_PAGE_VERSION', self::VERSION );
		define( 'DUPLICATOR_POST_PAGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'DUPLICATOR_POST_PAGE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Register activation hook.
	 */
	private function register_activation_hooks() {
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once DUPLICATOR_POST_PAGE_PLUGIN_DIR . '/vendor/autoload.php';
	}

	/**
	 * Loads the plugin text domain for the Duplicator Post Page.
	 *
	 * This function is responsible for loading the translation files for the plugin.
	 * It sets the text domain to 'duplicator-post-page' and specifies the directory
	 * where the translation files are located.
	 *
	 * @param string $domain   The text domain for the plugin.
	 * @param bool   $network  Whether the plugin is network activated.
	 * @param string $directory The directory where the translation files are located.
	 * @return bool True on success, false on failure.
	 * @since 1.0.0
	 */
	public function i18n() {
		load_plugin_textdomain( 'duplicator-post-page', false, DUPLICATOR_POST_PAGE_PLUGIN_DIR . 'languages/' );
	}

	/**
	 * Initialize the plugin functionality.
	 */
	public function init_plugin() {
		/**
		 * Fires before the initialization of the Duplicator Post Page plugin.
		 *
		 * This action hook allows developers to perform additional tasks before the Duplicator Post Page plugin has been initialized.
		 * @since 1.1.1
		 */
		do_action( 'duplicator_post_page/before_init' );

		/**
		 * Register hooks for the plugin.
		 *
		 * @since 1.1.1
		 */
		new DuplicatorPostPage\Hooks();

		/**
		 * Register Utils for the plugin.
		 *
		 * @since 1.1.1
		 */
		new DuplicatorPostPage\Helpers\Utils();

		/**
		 * Fires after the initialization of the Duplicator Post Page plugin.
		 *
		 * This action hook allows developers to perform additional tasks after the Duplicator Post Page plugin has been initialized.
		 * @since 1.0.0
		 */
		do_action( 'duplicator_post_page/after_init' );
	}

	/**
	 * Perform actions upon plugin activation.
	 */
	public function activate() {
		$installed = get_option( 'duplicator_post_page_installed' );

		if ( ! $installed ) {
			update_option( 'duplicator_post_page_installed', time() );
		}

		update_option( 'duplicator_post_page_version', DUPLICATOR_POST_PAGE_VERSION );
	}

	/**
	 * Add custom row meta to the plugin description in the Plugins page.
	 *
	 * @param array  $plugin_meta Meta information about the plugin.
	 * @param string $plugin_file Plugin file path.
	 * @return array Modified plugin meta.
	 */
	public function duplicator_post_page_plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( plugin_basename( __FILE__ ) === $plugin_file ) {
			$row_meta = [
				'video' => '<a href="https://www.youtube.com/watch?v=GzBJW-NE1l8" aria-label="' . esc_attr__( 'View Video Tutorials', 'duplicator-post-page' ) . '" target="_blank">' . esc_html__( 'Video Tutorials', 'duplicator-post-page' ) . '</a>',
			];

			// Merge the custom meta with existing plugin meta.
			$plugin_meta = array_merge( $plugin_meta, $row_meta );
		}

		return $plugin_meta;
	}

	/**
	 * Adds action links to the plugin list table.
	 *
	 * This adds a "Settings" plugin's action links on the Plugins page.
	 *
	 * @since 2.0.2
	*/
	public function plugin_action_links( $links ) {
		// Add the Duplicator Pro link
		$duplicator_pro_text = esc_html__( 'Get Pro', 'duplicator-post-page' );
		$duplicator_pro_link = sprintf(
			'<a href="%1$s" target="_blank" style="font-weight: 700; color: #b32d2e;">%2$s</a>',
			'https://wp-staging.com/?ref=453',
			$duplicator_pro_text
		);
	
		// Append the link to the action links array
		$links['duplicator-post-page'] = $duplicator_pro_link;
	
		return $links;
	}
}

/**
 * Initialize the main plugin instance.
 *
 * @return Duplicator_Post_Page
 */
function duplicator_post_page() {
	return Duplicator_Post_Page::instance();
}

// Kick off the plugin.
duplicator_post_page();
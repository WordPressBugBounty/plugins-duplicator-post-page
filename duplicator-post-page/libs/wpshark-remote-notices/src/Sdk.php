<?php
/**
 * SDK registry and wiring.
 *
 * @package WPShark\RemoteNotices
 */

namespace WPShark\RemoteNotices;

use WPShark\RemoteNotices\Feed\Fetcher;
use WPShark\RemoteNotices\Feed\Repository;
use WPShark\RemoteNotices\Rest\DismissController;
use WPShark\RemoteNotices\Ui\AdminNotice;
use WPShark\RemoteNotices\Ui\DashboardWidget;

defined( 'ABSPATH' ) || exit;

/**
 * Public API: Sdk::register_product(). Everything else is internal.
 *
 * Backward compatibility rule: public methods are never removed within a
 * major version, because one plugin may run another plugin's newer copy.
 */
final class Sdk {

	const CONSENT_ACTION = 'wpshark_sdk_consent';

	/**
	 * Registered products, keyed by slug.
	 *
	 * @var Product[]
	 */
	private static $products = array();

	/**
	 * Register a product. Call on the `wpshark_sdk_loaded` action.
	 *
	 * @param  array $args See Product::__construct().
	 * @return void
	 */
	public static function register_product( array $args ) {
		try {
			$product = new Product( $args );
		} catch ( \InvalidArgumentException $e ) {
			return;
		}

		self::$products[ $product->slug ] = $product;
	}

	/**
	 * All registered products.
	 *
	 * @return Product[]
	 */
	public static function products() {
		return self::$products;
	}

	/**
	 * Products whose admin opted in.
	 *
	 * @return Product[]
	 */
	public static function consented_products() {
		return array_filter( self::$products, array( Consent::class, 'granted' ) );
	}

	/**
	 * Products with no decision yet.
	 *
	 * @return Product[]
	 */
	public static function undecided_products() {
		return array_filter(
			self::$products,
			static function ( Product $product ) {
				return '' === Consent::get( $product );
			}
		);
	}

	/**
	 * Kill switch: constant, filter, and at least one consented product.
	 *
	 * @return bool
	 */
	public static function enabled() {
		if ( defined( 'WPSHARK_DISABLE_REMOTE_NOTICES' ) && WPSHARK_DISABLE_REMOTE_NOTICES ) {
			return false;
		}

		if ( ! apply_filters( 'wpshark_sdk_enabled', true ) ) {
			return false;
		}

		return (bool) self::consented_products();
	}

	/**
	 * Capability needed to see and manage items.
	 *
	 * @return string
	 */
	public static function capability() {
		return (string) apply_filters( 'wpshark_sdk_capability', 'manage_options' );
	}

	/**
	 * Whether the SDK UI may render for the current request/user.
	 *
	 * @return bool
	 */
	public static function can_render() {
		if ( defined( 'WPSHARK_DISABLE_REMOTE_NOTICES' ) && WPSHARK_DISABLE_REMOTE_NOTICES ) {
			return false;
		}

		return is_admin() && ! is_network_admin() && current_user_can( self::capability() ) && apply_filters( 'wpshark_sdk_enabled', true );
	}

	/**
	 * Public URL of an SDK asset.
	 *
	 * @param  string $path Relative path.
	 * @return string
	 */
	public static function asset_url( $path ) {
		return plugins_url( $path, WPSHARK_SDK_DIR . 'loader.php' );
	}

	/**
	 * Wire hooks. Called once by the elected bootstrap.
	 *
	 * @return void
	 */
	public static function boot() {
		add_filter( 'cron_schedules', array( Fetcher::class, 'add_schedule' ) ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected -- 6 hours.
		add_action( Fetcher::HOOK, array( Fetcher::class, 'run' ) );
		add_action( Fetcher::HOOK_ONCE, array( Fetcher::class, 'run_now' ) );
		add_action( 'init', array( self::class, 'sync_schedule' ), 20 );
		add_action( 'rest_api_init', array( DismissController::class, 'register_routes' ) );

		if ( is_admin() ) {
			add_action( 'admin_post_' . self::CONSENT_ACTION, array( self::class, 'handle_consent' ) );
			add_filter( 'plugin_row_meta', array( self::class, 'plugin_row_meta' ), 10, 2 );
			DashboardWidget::register();
			AdminNotice::register();
		}
	}

	/**
	 * Keep exactly one recurring event while enabled, none otherwise.
	 *
	 * @return void
	 */
	public static function sync_schedule() {
		$next = wp_next_scheduled( Fetcher::HOOK );

		if ( self::enabled() ) {
			// An event from an older SDK (twice daily) is moved to the 6-hour schedule.
			$event = $next ? wp_get_scheduled_event( Fetcher::HOOK ) : false;
			if ( $event && Fetcher::SCHEDULE !== $event->schedule ) {
				wp_clear_scheduled_hook( Fetcher::HOOK );
				$next = false;
			}

			if ( ! $next ) {
				// Up to 30 min jitter so sites do not all hit the hub at once.
				wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS + wp_rand( 0, 30 * MINUTE_IN_SECONDS ), Fetcher::SCHEDULE, Fetcher::HOOK );
			}
		} elseif ( $next ) {
			wp_clear_scheduled_hook( Fetcher::HOOK );
		}
	}

	/**
	 * Handle the opt-in / opt-out form (admin-post.php).
	 *
	 * @return void
	 */
	public static function handle_consent() {
		check_admin_referer( self::CONSENT_ACTION );

		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'duplicator-post-page' ), '', array( 'response' => 403 ) );
		}

		$choice  = isset( $_REQUEST['choice'] ) ? sanitize_key( wp_unslash( $_REQUEST['choice'] ) ) : '';
		$slug    = isset( $_REQUEST['product'] ) ? sanitize_key( wp_unslash( $_REQUEST['product'] ) ) : '';
		$granted = ( 'yes' === $choice );

		if ( ! in_array( $choice, array( 'yes', 'no' ), true ) ) {
			wp_die( esc_html__( 'Invalid request.', 'duplicator-post-page' ), '', array( 'response' => 400 ) );
		}

		$targets = ( '' !== $slug && isset( self::$products[ $slug ] ) ) ? array( self::$products[ $slug ] ) : self::$products;

		foreach ( $targets as $product ) {
			Consent::set( $product, $granted );
		}

		self::after_consent_change();

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	/**
	 * React to a consent change: fetch soon after opt-in, purge after the
	 * last opt-out. Also used by products with their own settings toggle.
	 *
	 * @return void
	 */
	public static function after_consent_change() {
		if ( self::enabled() ) {
			if ( ! wp_next_scheduled( Fetcher::HOOK_ONCE ) ) {
				wp_schedule_single_event( time(), Fetcher::HOOK_ONCE );
			}
			return;
		}

		wp_clear_scheduled_hook( Fetcher::HOOK );
		wp_clear_scheduled_hook( Fetcher::HOOK_ONCE );
		Repository::purge();
	}

	/**
	 * "Enable/Disable WPShark news" link under each product on Plugins.
	 *
	 * @param  string[] $meta        Row meta.
	 * @param  string   $plugin_file Plugin basename.
	 * @return string[]
	 */
	public static function plugin_row_meta( $meta, $plugin_file ) {
		if ( ! current_user_can( self::capability() ) || is_network_admin() ) {
			return $meta;
		}

		foreach ( self::$products as $product ) {
			if ( $product->plugin_file !== $plugin_file ) {
				continue;
			}

			$granted = Consent::granted( $product );
			$url     = wp_nonce_url(
				add_query_arg(
					array(
						'action'  => self::CONSENT_ACTION,
						'choice'  => $granted ? 'no' : 'yes',
						'product' => $product->slug,
					),
					admin_url( 'admin-post.php' )
				),
				self::CONSENT_ACTION
			);

			$meta['wpshark-news'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $url ),
				$granted ? esc_html__( 'Disable WPShark news', 'duplicator-post-page' ) : esc_html__( 'Enable WPShark news', 'duplicator-post-page' )
			);
		}

		return $meta;
	}
}

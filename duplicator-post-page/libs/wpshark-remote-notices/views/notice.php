<?php
/**
 * Banner / notice. All values come from the verified, schema-cleaned cache
 * and are escaped here; no remote HTML or CSS is ever rendered. The only
 * markup allowed in a description is **bold**, applied after escaping.
 *
 * @package WPShark\RemoteNotices
 *
 * @var array $item Clean item.
 */

defined( 'ABSPATH' ) || exit;

$wpshark_desc   = preg_replace( '/\*\*(.+?)\*\*/u', '<strong>$1</strong>', esc_html( $item['description'] ) );
$wpshark_badge  = isset( $item['badge'] ) ? $item['badge'] : '';
$wpshark_banner = ( 'banner' === $item['type'] );
?>
<div class="notice wpshark-notice wpshark-notice--<?php echo esc_attr( $item['variant'] ); ?> wpshark-notice--<?php echo esc_attr( $item['type'] ); ?> is-dismissible" data-wpshark-id="<?php echo esc_attr( (string) $item['id'] ); ?>">
	<?php if ( $wpshark_banner && '' !== $wpshark_badge ) : ?>
		<div class="wpshark-notice__badge" aria-hidden="true">
			<?php echo implode( '<br />', array_map( 'esc_html', preg_split( '/\s+/', $wpshark_badge ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each word escaped. ?>
		</div>
	<?php elseif ( $wpshark_banner && $item['image'] ) : ?>
		<img class="wpshark-notice__image" src="<?php echo esc_url( $item['image'] ); ?>" alt="" width="80" height="80" loading="lazy" referrerpolicy="no-referrer" />
	<?php endif; ?>
	<div class="wpshark-notice__body">
		<?php if ( $wpshark_banner ) : ?>
			<p class="wpshark-notice__title">
				<?php if ( '' !== $wpshark_badge ) : ?>
					<span class="screen-reader-text"><?php echo esc_html( $wpshark_badge ); ?>:</span>
				<?php endif; ?>
				<?php echo esc_html( $item['title'] ); ?>
			</p>
			<?php if ( $item['description'] ) : ?>
				<p class="wpshark-notice__desc"><?php echo wp_kses( $wpshark_desc, array( 'strong' => array() ) ); ?></p>
			<?php endif; ?>
		<?php else : ?>
			<p><strong><?php echo esc_html( $item['title'] ); ?></strong> <?php echo wp_kses( $wpshark_desc, array( 'strong' => array() ) ); ?></p>
		<?php endif; ?>
		<?php if ( $item['cta_text'] ) : ?>
			<p>
				<a class="button <?php echo $wpshark_banner ? 'wpshark-notice__cta' : 'button-primary'; ?>" href="<?php echo esc_url( $item['link'] ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( $item['cta_text'] ); ?>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'duplicator-post-page' ); ?></span>
				</a>
			</p>
		<?php endif; ?>
	</div>
</div>

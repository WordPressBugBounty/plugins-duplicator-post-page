<?php
/**
 * Stories inside the dashboard widget.
 *
 * @package WPShark\RemoteNotices
 *
 * @var array[]  $stories Stories to show.
 * @var string[] $links   Footer links: label => URL.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wpshark-news">
	<ul class="wpshark-news__list">
		<?php foreach ( $stories as $wpshark_story ) : ?>
			<li class="wpshark-news__item">
				<?php if ( $wpshark_story['image'] ) : ?>
					<img class="wpshark-news__thumb" src="<?php echo esc_url( $wpshark_story['image'] ); ?>" alt="" width="48" height="48" loading="lazy" referrerpolicy="no-referrer" />
				<?php endif; ?>
				<div>
					<a class="wpshark-news__title" href="<?php echo esc_url( $wpshark_story['link'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $wpshark_story['title'] ); ?>
						<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'duplicator-post-page' ); ?></span>
					</a>
					<?php if ( $wpshark_story['description'] ) : ?>
						<p class="wpshark-news__desc"><?php echo esc_html( $wpshark_story['description'] ); ?></p>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php if ( $links ) : ?>
		<ul class="wpshark-news__footer">
			<?php foreach ( $links as $wpshark_label => $wpshark_url ) : ?>
				<li>
					<a href="<?php echo esc_url( $wpshark_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $wpshark_label ); ?>
						<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'duplicator-post-page' ); ?></span>
						<span aria-hidden="true" class="dashicons dashicons-external"></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>

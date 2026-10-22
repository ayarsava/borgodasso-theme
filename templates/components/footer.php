<?php
/**
 * Site footer — three columns from Theme Options.
 *
 * @package Borgodasso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$footer = borgodasso_footer_options();
$col1   = $footer['column_1'];
$col2   = $footer['column_2'];
$col3   = $footer['column_3'];

$has_col1           = '' !== $col1['title'] || '' !== $col1['address'] || $col1['maps'] || '' !== $col1['vat'];
$has_col2           = '' !== $col2['title'] || '' !== $col2['email'] || '' !== $col2['phone'] || $col2['instagram'] || $col2['terms'];
$has_col3           = '' !== $col3['title'] || '' !== $col3['text'] || $col3['website'] || $col3['privacy'] || $col3['cookies'];
$has_desktop_footer = $has_col1 || $has_col2 || $has_col3;
$mobile_footer_cols = borgodasso_mobile_footer_link_columns();

if ( ! $has_desktop_footer && null === $mobile_footer_cols ) {
	return;
}

/**
 * Render an ACF link as an anchor.
 *
 * @param array{title: string, url: string, target: string} $link    Link data.
 * @param string                                             $link_class CSS class.
 * @param string                                             $fallback   Fallback label when title empty.
 */
$render_link = static function ( array $link, string $link_class, string $fallback = '' ): void {
	$label  = '' !== $link['title'] ? $link['title'] : $fallback;
	$target = $link['target'];
	?>
	<a
		class="<?php echo esc_attr( $link_class ); ?>"
		href="<?php echo esc_url( $link['url'] ); ?>"
		<?php echo '_blank' === $target ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
	><?php echo esc_html( $label ); ?></a>
	<?php
};
?>
<footer class="site-footer">
	<div class="site-footer__inner">
		<?php if ( $has_desktop_footer ) : ?>
		<div class="site-footer__grid site-footer__grid--desktop">
			<?php if ( $has_col1 ) : ?>
				<div class="site-footer__column">
					<div class="site-footer__body">
						<?php if ( '' !== $col1['title'] ) : ?>
							<h2 class="site-footer__title text-h5"><?php echo esc_html( $col1['title'] ); ?></h2>
						<?php endif; ?>

						<?php if ( '' !== $col1['address'] ) : ?>
							<p class="site-footer__text">
								<?php echo wp_kses_post( $col1['address'] ); ?>
							</p>
						<?php endif; ?>

						<?php if ( $col1['maps'] ) : ?>
							<p class="site-footer__text">
								<?php $render_link( $col1['maps'], 'site-footer__link', __( 'Google Maps', 'borgodasso' ) ); ?>
							</p>
						<?php endif; ?>
					</div>

					<?php if ( '' !== $col1['vat'] ) : ?>
						<p class="site-footer__meta site-footer__meta-text"><?php echo esc_html( $col1['vat'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $has_col2 ) : ?>
				<div class="site-footer__column">
					<div class="site-footer__body">
						<?php if ( '' !== $col2['title'] ) : ?>
							<h2 class="site-footer__title text-h5"><?php echo esc_html( $col2['title'] ); ?></h2>
						<?php endif; ?>

						<?php if ( '' !== $col2['email'] ) : ?>
							<p class="site-footer__text">
								<span class="site-footer__label"><?php esc_html_e( 'Email:', 'borgodasso' ); ?></span>
								<a class="site-footer__link" href="<?php echo esc_url( 'mailto:' . $col2['email'] ); ?>"><?php echo esc_html( $col2['email'] ); ?></a>
							</p>
						<?php endif; ?>

						<?php if ( '' !== $col2['phone'] ) : ?>
							<?php
							$phone_href = preg_replace( '/[^\d+]/', '', $col2['phone'] );
							$phone_href = is_string( $phone_href ) ? $phone_href : $col2['phone'];
							?>
							<p class="site-footer__text">
								<span class="site-footer__label"><?php esc_html_e( 'Telefono:', 'borgodasso' ); ?></span>
								<a class="site-footer__link" href="<?php echo esc_url( 'tel:' . $phone_href ); ?>"><?php echo esc_html( $col2['phone'] ); ?></a>
							</p>
						<?php endif; ?>

						<?php if ( $col2['instagram'] ) : ?>
							<p class="site-footer__text">
								<span class="site-footer__label"><?php esc_html_e( 'Instagram:', 'borgodasso' ); ?></span>
								<?php $render_link( $col2['instagram'], 'site-footer__link', '@borgodasso' ); ?>
							</p>
						<?php endif; ?>
					</div>

					<?php if ( $col2['terms'] ) : ?>
						<p class="site-footer__meta">
							<?php $render_link( $col2['terms'], 'site-footer__meta-link', __( 'Condizioni di vendita', 'borgodasso' ) ); ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $has_col3 ) : ?>
				<div class="site-footer__column">
					<div class="site-footer__body">
						<?php if ( '' !== $col3['title'] ) : ?>
							<h2 class="site-footer__title text-h5"><?php echo esc_html( $col3['title'] ); ?></h2>
						<?php endif; ?>

						<?php if ( '' !== $col3['text'] || $col3['website'] ) : ?>
							<p class="site-footer__text">
								<?php if ( '' !== $col3['text'] ) : ?>
									<?php echo esc_html( $col3['text'] ); ?>
									<?php if ( $col3['website'] ) : ?>
										<?php echo ' '; ?>
									<?php endif; ?>
								<?php endif; ?>
								<?php if ( $col3['website'] ) : ?>
									<?php $render_link( $col3['website'], 'site-footer__link', $col3['website']['url'] ); ?>
								<?php endif; ?>
							</p>
						<?php endif; ?>
					</div>

					<?php if ( $col3['privacy'] || $col3['cookies'] ) : ?>
						<p class="site-footer__meta site-footer__meta--row">
							<?php if ( $col3['privacy'] ) : ?>
								<?php $render_link( $col3['privacy'], 'site-footer__meta-link', __( 'Privacy policy', 'borgodasso' ) ); ?>
							<?php endif; ?>
							<?php if ( $col3['cookies'] ) : ?>
								<?php $render_link( $col3['cookies'], 'site-footer__meta-link', __( 'Cookies', 'borgodasso' ) ); ?>
							<?php endif; ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( null !== $mobile_footer_cols ) : ?>
			<nav class="site-footer__mobile" aria-label="<?php esc_attr_e( 'Mobile footer', 'borgodasso' ); ?>">
				<div class="site-footer__mobile-grid">
					<?php foreach ( $mobile_footer_cols as $column_items ) : ?>
						<?php if ( array() === $column_items ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<ul class="site-footer__mobile-col">
							<?php foreach ( $column_items as $item ) : ?>
								<li class="site-footer__mobile-item">
									<a
										class="site-footer__mobile-link"
										href="<?php echo esc_url( $item['url'] ); ?>"
										<?php echo '' !== $item['target'] ? 'target="' . esc_attr( $item['target'] ) . '" rel="noopener noreferrer"' : ''; ?>
									><?php echo esc_html( $item['label'] ); ?></a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endforeach; ?>
				</div>
			</nav>
		<?php endif; ?>
	</div>
</footer>

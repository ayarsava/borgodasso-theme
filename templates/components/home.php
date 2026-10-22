<?php
/**
 * Homepage — full-viewport Swiper backgrounds + fixed title/CTA layers.
 *
 * @package Borgodasso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$panels = array();

if ( function_exists( 'get_field' ) ) {
	$acf_panels = get_field( 'home_panels' );
	if ( is_array( $acf_panels ) ) {
		$panels = $acf_panels;
	}
}

if ( empty( $panels ) ) {
	$panels = array(
		array(
			'title' => __( 'San Giuseppe', 'borgodasso' ),
			'link'  => array( 'url' => '#' ),
		),
		array(
			'title' => __( 'Wine', 'borgodasso' ),
			'link'  => array( 'url' => '#' ),
		),
		array(
			'title' => __( 'Soil', 'borgodasso' ),
			'link'  => array( 'url' => '#' ),
		),
		array(
			'title' => __( 'Bottles', 'borgodasso' ),
			'link'  => array( 'url' => '#' ),
		),
		array(
			'title' => __( 'Visits', 'borgodasso' ),
			'link'  => array( 'url' => '#' ),
		),
	);
}
?>
<div class="home" data-home>
	<div class="swiper home-swiper" data-home-swiper>
		<div class="swiper-wrapper">
			<?php foreach ( $panels as $index => $panel ) : ?>
				<?php
				if ( ! is_array( $panel ) ) {
					continue;
				}

				$image_id        = isset( $panel['image'] ) ? (int) $panel['image'] : null;
				$image_mobile_id = isset( $panel['image_mobile'] ) ? (int) $panel['image_mobile'] : null;
				$is_first        = 0 === $index;
				$desktop_src     = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
				$mobile_src      = $image_mobile_id ? wp_get_attachment_image_url( $image_mobile_id, 'large' ) : $desktop_src;
				?>
				<div class="swiper-slide home-panel">
					<div class="home-panel__media" aria-hidden="true">
						<?php if ( $desktop_src || $mobile_src ) : ?>
							<picture class="home-panel__picture">
								<?php if ( $mobile_src && $mobile_src !== $desktop_src ) : ?>
									<source media="(max-width: 767px)" srcset="<?php echo esc_url( $mobile_src ); ?>">
								<?php endif; ?>
								<?php if ( $desktop_src ) : ?>
									<img
										class="home-panel__img"
										src="<?php echo esc_url( $desktop_src ); ?>"
										alt=""
										loading="<?php echo $is_first ? 'eager' : 'lazy'; ?>"
										decoding="async"
									>
								<?php elseif ( $mobile_src ) : ?>
									<img
										class="home-panel__img"
										src="<?php echo esc_url( $mobile_src ); ?>"
										alt=""
										loading="lazy"
										decoding="async"
									>
								<?php endif; ?>
							</picture>
						<?php endif; ?>
					</div>
					<div class="home-panel__overlay" aria-hidden="true"></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="home-layers" data-home-layers>
		<?php foreach ( $panels as $index => $panel ) : ?>
			<?php
			if ( ! is_array( $panel ) ) {
				continue;
			}

			$title = (string) ( $panel['title'] ?? '' );
			if ( '' === $title ) {
				continue;
			}

			$link = $panel['link'] ?? array();
			$url  = is_array( $link ) && ! empty( $link['url'] ) ? (string) $link['url'] : '#';

			$button_label = '';
			if ( ! empty( $panel['button_label'] ) ) {
				$button_label = (string) $panel['button_label'];
			} elseif ( is_array( $link ) && ! empty( $link['title'] ) ) {
				$button_label = (string) $link['title'];
			}
			if ( '' === $button_label ) {
				$button_label = __( 'Dettagli', 'borgodasso' );
			}

			$external = is_array( $link ) && ! empty( $link['target'] ) && '_blank' === $link['target'];
			?>
			<div class="home-layer" data-home-layer data-layer-index="<?php echo esc_attr( (string) $index ); ?>">
				<div class="home-layer__content">
					<h2 class="home-layer__title split-chars">
						<a
							class="home-layer__title-link"
							href="<?php echo esc_url( $url ); ?>"
							aria-label="<?php echo esc_attr( $title ); ?>"
						>
							<span class="split-chars__text" data-split-chars-text data-no-dynamic-translation><?php echo esc_html( $title ); ?></span>
							<span class="split-chars__stage" data-split-chars data-no-translation aria-hidden="true"></span>
						</a>
					</h2>
				</div>

				<div class="home-layer__actions">
					<div class="home-layer__actions-inner">
						<?php
						borgodasso_template(
							'objects/button',
							array(
								'label'    => $button_label,
								'url'      => $url,
								'variant'  => 'ghost',
								'icon'     => 'arrow',
								'line'     => true,
								'external' => $external,
							)
						);
						?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="home-arrows" aria-label="<?php esc_attr_e( 'Home sections', 'borgodasso' ); ?>">
		<button
			type="button"
			class="home-arrows__btn home-arrows__btn--prev"
			data-home-prev
			aria-label="<?php esc_attr_e( 'Previous section', 'borgodasso' ); ?>"
		>
			<?php
			borgodasso_template(
				'objects/svg',
				array(
					'name'  => 'scroll',
					'class' => 'home-arrows__icon home-arrows__icon--up',
				)
			);
			?>
		</button>
		<button
			type="button"
			class="home-arrows__btn home-arrows__btn--next"
			data-home-next
			aria-label="<?php esc_attr_e( 'Next section', 'borgodasso' ); ?>"
		>
			<?php
			borgodasso_template(
				'objects/svg',
				array(
					'name'  => 'scroll',
					'class' => 'home-arrows__icon',
				)
			);
			?>
		</button>
	</div>
</div>

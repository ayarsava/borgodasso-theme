<?php
/**
 * Casa page layout — desktop + mobile compositions from ACF Casa fields.
 *
 * @package Borgodasso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data = borgodasso_casa_data();

if ( null === $data ) {
	return;
}

$related           = borgodasso_casa_related();
$gallery_ids       = $data['gallery_ids'];
$slide_count       = count( $gallery_ids );
$cta               = $data['cta'];
$cta_image         = (int) ( $cta['image_id'] ?? 0 );
$prenota           = $data['prenota'];
$floor             = $data['floor_file'];
$f1                = (int) $data['mobile_landscape_ids'][0];
$f4                = (int) $data['mobile_landscape_ids'][1];
$f3                = (int) ( $data['mobile_landscape_ids'][2] ?? 0 );
$p1                = (int) $data['mobile_portrait_ids'][0];
$p2                = (int) $data['mobile_portrait_ids'][1];
$amenities_split   = ! empty( $data['amenities_split'] );
$amenities_image_2 = (int) ( $data['amenities_image_2_id'] ?? 0 );

if ( $amenities_split ) {
	[$amenities_first, $amenities_second] = borgodasso_casa_amenities_halves( $data['amenities'] );
} else {
	$amenities_first  = $data['amenities'];
	$amenities_second = array();
}
?>
<div class="casa" data-casa>
	<?php
	// —— Desktop ——
	?>
	<div class="casa__desktop">
		<section class="casa-intro section" aria-labelledby="casa-title-desktop">
			<div class="casa-intro__rail">
				<div class="casa-intro__details">
					<?php
					borgodasso_template(
						'components/casa-details',
						array(
							'data'       => $data,
							'id'         => 'casa-title-desktop',
							'show_floor' => true,
						)
					);
					?>
				</div>
				<div class="casa-intro__line"></div>
				<?php if ( array() !== $data['amenities'] ) : ?>
					<aside class="casa-intro__amenities" aria-label="<?php echo esc_attr__( 'Amenities', 'borgodasso' ); ?>">
						<?php borgodasso_template( 'components/casa-amenities', array( 'amenities' => $data['amenities'] ) ); ?>
					</aside>
				<?php endif; ?>
			</div>
		</section>

		<?php if ( $slide_count >= 2 ) : ?>
			<?php
			borgodasso_template(
				'page-builder-components/media-gallery',
				array(
					'image_ids'  => $gallery_ids,
					'height'     => '758',
					'pagination' => 'counter',
					'section_id' => 'casa-gallery',
				)
			);
			?>
		<?php endif; ?>

		<?php if ( '' !== $cta['title'] ) : ?>
			<section class="casa-cta section casa-cta--desktop" aria-labelledby="casa-cta-title-desktop">
				<div class="casa-cta__rail">
					<?php
					borgodasso_template(
						'components/casa-cta',
						array(
							'cta' => $cta,
							'id'  => 'casa-cta-title-desktop',
						)
					);
					?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( array() !== $related ) : ?>
			<section class="casa-related section" aria-labelledby="casa-related-title">
				<div class="casa-related__inner">
					<h2 id="casa-related-title" class="casa-related__heading text-h2">
						<?php echo esc_html__( 'Le case del Borgo', 'borgodasso' ); ?>
					</h2>
					<ul class="casa-related__grid">
						<?php foreach ( $related as $card ) : ?>
							<li class="casa-related__item">
								<?php if ( $card['image_id'] > 0 ) : ?>
									<a class="casa-related__media" href="<?php echo esc_url( $card['url'] ); ?>">
										<figure class="casa-related__figure">
											<?php
											echo wp_get_attachment_image(
												$card['image_id'],
												'borgodasso_casa_card',
												false,
												array(
													'class' => 'casa-related__img',
													'loading' => 'lazy',
													'decoding' => 'async',
													'alt' => borgodasso_attachment_alt( $card['image_id'], $card['title'] ),
												)
											);
											?>
										</figure>
									</a>
								<?php endif; ?>
								<h3 class="casa-related__title text-h3">
									<a href="<?php echo esc_url( $card['url'] ); ?>"><?php echo esc_html( $card['title'] ); ?></a>
								</h3>
								<?php
								borgodasso_template(
									'objects/button',
									array(
										'label'   => __( 'Dettagli', 'borgodasso' ),
										'url'     => $card['url'],
										'variant' => '',
										'class'   => 'casa-related__btn',
										'icon'    => 'arrow',
										'line'    => true,
									)
								);
								?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>
	</div>

	<?php
	// —— Mobile ——
	?>
	<div class="casa__mobile">
		<section class="casa-details section" aria-labelledby="casa-title-mobile">
			<div class="casa-details__inner">
				<?php
				borgodasso_template(
					'components/casa-details',
					array(
						'data'       => $data,
						'id'         => 'casa-title-mobile',
						'show_floor' => false,
					)
				);
				?>
			</div>
		</section>

		<?php if ( $f1 > 0 ) : ?>
			<figure class="casa-bleed">
				<?php
				echo wp_get_attachment_image(
					$f1,
					'large',
					false,
					array(
						'class'    => 'casa-bleed__img',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'alt'      => '',
					)
				);
				?>
			</figure>
		<?php endif; ?>

		<?php
		$more_info       = $data['more_info'] ?? array(
			'text'     => '',
			'image_id' => 0,
		);
		$more_info_text  = is_string( $more_info['text'] ?? null ) ? $more_info['text'] : '';
		$more_info_image = (int) ( $more_info['image_id'] ?? 0 );
		?>
		<?php if ( '' !== $more_info_text || $more_info_image > 0 ) : ?>
			<section class="casa-more-info section" aria-label="<?php echo esc_attr__( 'More info', 'borgodasso' ); ?>">
				<?php if ( '' !== $more_info_text ) : ?>
					<div class="casa-more-info__text text-body">
						<?php echo wp_kses_post( $more_info_text ); ?>
					</div>
				<?php endif; ?>
				<?php if ( $more_info_image > 0 ) : ?>
					<figure class="casa-more-info__media">
						<?php
						echo wp_get_attachment_image(
							$more_info_image,
							'large',
							false,
							array(
								'class'    => 'casa-more-info__img',
								'loading'  => 'lazy',
								'decoding' => 'async',
								'alt'      => '',
							)
						);
						?>
					</figure>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php if ( array() !== $amenities_first || $data['amenities_image_id'] > 0 ) : ?>
			<section class="casa-amenities-split section" aria-label="<?php echo esc_attr__( 'Amenities', 'borgodasso' ); ?>">
				<div class="casa-amenities-split__list">
					<?php borgodasso_template( 'components/casa-amenities', array( 'amenities' => $amenities_first ) ); ?>
				</div>
				<?php if ( $data['amenities_image_id'] > 0 ) : ?>
					<figure class="casa-amenities-split__media">
						<?php
						echo wp_get_attachment_image(
							$data['amenities_image_id'],
							'large',
							false,
							array(
								'class'    => 'casa-amenities-split__img',
								'loading'  => 'lazy',
								'decoding' => 'async',
								'alt'      => '',
							)
						);
						?>
					</figure>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php if ( $amenities_split && $f3 > 0 ) : ?>
			<figure class="casa-bleed">
				<?php
				echo wp_get_attachment_image(
					$f3,
					'large',
					false,
					array(
						'class'    => 'casa-bleed__img',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'alt'      => '',
					)
				);
				?>
			</figure>
		<?php endif; ?>

		<?php if ( $amenities_split && ( array() !== $amenities_second || $amenities_image_2 > 0 ) ) : ?>
			<section class="casa-amenities-split section" aria-label="<?php echo esc_attr__( 'More amenities', 'borgodasso' ); ?>">
				<div class="casa-amenities-split__list">
					<?php borgodasso_template( 'components/casa-amenities', array( 'amenities' => $amenities_second ) ); ?>
				</div>
				<?php if ( $amenities_image_2 > 0 ) : ?>
					<figure class="casa-amenities-split__media">
						<?php
						echo wp_get_attachment_image(
							$amenities_image_2,
							'large',
							false,
							array(
								'class'    => 'casa-amenities-split__img',
								'loading'  => 'lazy',
								'decoding' => 'async',
								'alt'      => '',
							)
						);
						?>
					</figure>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php if ( $f4 > 0 ) : ?>
			<figure class="casa-bleed">
				<?php
				echo wp_get_attachment_image(
					$f4,
					'large',
					false,
					array(
						'class'    => 'casa-bleed__img',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'alt'      => '',
					)
				);
				?>
			</figure>
		<?php endif; ?>

		<?php if ( $slide_count > 0 ) : ?>
			<section class="casa-action-link section">
				<div class="casa-cta__actions">
					<?php
					borgodasso_template(
						'objects/button',
						array(
							'label'   => __( 'Galleria', 'borgodasso' ),
							'variant' => '',
							'class'   => 'casa-cta__btn',
							'icon'    => 'arrow',
							'line'    => true,
							'tag'     => 'button',
							'attrs'   => array(
								'data-casa-gallery-open' => '',
								'aria-haspopup'          => 'dialog',
								'aria-controls'          => 'casa-fullscreen-gallery',
							),
						)
					);
					?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $p1 > 0 || $p2 > 0 ) : ?>
			<section class="casa-portraits section">
				<?php if ( $p1 > 0 ) : ?>
					<figure class="casa-portraits__item">
						<?php
						echo wp_get_attachment_image(
							$p1,
							'large',
							false,
							array(
								'class'    => 'casa-portraits__img',
								'loading'  => 'lazy',
								'decoding' => 'async',
								'alt'      => '',
							)
						);
						?>
					</figure>
				<?php endif; ?>
				<?php if ( $p2 > 0 ) : ?>
					<figure class="casa-portraits__item">
						<?php
						echo wp_get_attachment_image(
							$p2,
							'large',
							false,
							array(
								'class'    => 'casa-portraits__img',
								'loading'  => 'lazy',
								'decoding' => 'async',
								'alt'      => '',
							)
						);
						?>
					</figure>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php if ( null !== $floor ) : ?>
			<section class="casa-action-link section">
				<div class="casa-cta__actions">
					<?php
					borgodasso_template(
						'objects/button',
						array(
							'label'    => __( 'Mappa', 'borgodasso' ),
							'url'      => $floor['url'],
							'variant'  => '',
							'class'    => 'casa-cta__btn',
							'icon'     => 'arrow',
							'line'     => true,
							'external' => true,
						)
					);
					?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( '' !== $cta['title'] ) : ?>
			<section class="casa-cta section casa-cta--mobile <?php echo $cta_image > 0 ? 'casa-cta--has-media' : ''; ?>" aria-labelledby="casa-cta-title-mobile">
				<?php if ( $cta_image > 0 ) : ?>
					<figure class="casa-cta__media casa-bleed">
						<?php
						echo wp_get_attachment_image(
							$cta_image,
							'large',
							false,
							array(
								'class'    => 'casa-bleed__img',
								'loading'  => 'lazy',
								'decoding' => 'async',
								'alt'      => '',
							)
						);
						?>
					</figure>
				<?php endif; ?>
				<div class="casa-cta__inner">
					<?php
					borgodasso_template(
						'components/casa-cta',
						array(
							'cta' => $cta,
							'id'  => 'casa-cta-title-mobile',
						)
					);
					?>
				</div>
			</section>
		<?php endif; ?>
	</div>

	<?php if ( $slide_count > 0 ) : ?>
		<dialog
			id="casa-fullscreen-gallery"
			class="casa-fs-gallery"
			data-casa-gallery
			aria-label="<?php echo esc_attr__( 'Apartment gallery', 'borgodasso' ); ?>">
			<div class="casa-fs-gallery__bar">
				<span class="casa-fs-gallery__counter" aria-live="polite">
					<span data-casa-gallery-current>01</span>/<span data-casa-gallery-total><?php echo esc_html( str_pad( (string) $slide_count, 2, '0', STR_PAD_LEFT ) ); ?></span>
				</span>
				<button
					type="button"
					class="casa-fs-gallery__close"
					data-casa-gallery-close
					aria-label="<?php echo esc_attr__( 'Close gallery', 'borgodasso' ); ?>">
					×
				</button>
			</div>
			<?php if ( $slide_count > 1 ) : ?>
				<div class="casa-fs-gallery__hint" data-casa-gallery-hint aria-hidden="true">
					<span class="casa-fs-gallery__hint-arrow">
						<?php
						borgodasso_template(
							'objects/svg',
							array(
								'name'  => 'scroll',
								'class' => 'casa-fs-gallery__hint-icon',
							)
						);
						?>
					</span>
					<span class="casa-fs-gallery__hint-arrow casa-fs-gallery__hint-arrow--fade">
						<?php
						borgodasso_template(
							'objects/svg',
							array(
								'name'  => 'scroll',
								'class' => 'casa-fs-gallery__hint-icon',
							)
						);
						?>
					</span>
				</div>
			<?php endif; ?>
			<div class="casa-fs-gallery__scroller" data-casa-gallery-scroller>
				<?php foreach ( $gallery_ids as $index => $image_id ) : ?>
					<figure class="casa-fs-gallery__slide" data-casa-gallery-slide>
						<?php
						echo wp_get_attachment_image(
							$image_id,
							'full',
							false,
							array(
								'class'    => 'casa-fs-gallery__img',
								'loading'  => $index < 2 ? 'eager' : 'lazy',
								'decoding' => 'async',
								'alt'      => borgodasso_attachment_alt(
									$image_id,
									sprintf(
										/* translators: %d: slide number */
										__( 'Gallery image %d', 'borgodasso' ),
										$index + 1
									)
								),
							)
						);
						?>
					</figure>
				<?php endforeach; ?>
			</div>
		</dialog>
	<?php endif; ?>
</div>
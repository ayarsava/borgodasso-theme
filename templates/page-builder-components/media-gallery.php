<?php
/**
 * Media gallery — centered Embla carousel with height + pagination variants.
 *
 * @package Borgodasso
 *
 * @var int[]       $image_ids   Attachment IDs.
 * @var string      $height      660 | 595 | 758.
 * @var string      $pagination  none | numbers | counter.
 * @var string|null $section_id  Unique ID for aria labelling.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$image_ids  = isset( $image_ids ) && is_array( $image_ids ) ? array_values( array_filter( array_map( 'intval', $image_ids ) ) ) : array();
$height     = $height ?? '660';
$pagination = $pagination ?? 'none';
$section_id = $section_id ?? wp_unique_id( 'media-gallery-' );

$allowed_heights     = array( '660', '595', '758' );
$allowed_paginations = array( 'none', 'numbers', 'counter' );

if ( ! in_array( $height, $allowed_heights, true ) ) {
	$height = '660';
}

if ( ! in_array( $pagination, $allowed_paginations, true ) ) {
	$pagination = 'none';
}

if ( count( $image_ids ) < 2 ) {
	return;
}

$slide_count      = count( $image_ids );
$height_class     = 'media-gallery--height-' . $height;
$pagination_class = 'media-gallery--pagination-' . $pagination;
$show_pagination  = 'none' !== $pagination;
?>
<section
	class="media-gallery section <?php echo esc_attr( $height_class ); ?> <?php echo esc_attr( $pagination_class ); ?>"
	data-media-gallery
	data-media-gallery-pagination="<?php echo esc_attr( $pagination ); ?>"
	aria-roledescription="<?php echo esc_attr__( 'carousel', 'borgodasso' ); ?>"
	aria-label="<?php echo esc_attr__( 'Media gallery', 'borgodasso' ); ?>"
	id="<?php echo esc_attr( $section_id ); ?>">
	<div class="media-gallery__frame">
		<div class="media-gallery__viewport" data-media-gallery-viewport>
			<div class="media-gallery__container">
				<?php foreach ( $image_ids as $index => $image_id ) : ?>
					<?php
					$alt = borgodasso_attachment_alt(
						$image_id,
						sprintf(
							/* translators: %d: slide number */
							__( 'Gallery image %d', 'borgodasso' ),
							$index + 1
						)
					);
					?>
					<div class="media-gallery__slide">
						<figure class="media-gallery__media">
							<?php
							echo wp_get_attachment_image(
								$image_id,
								'borgodasso_gallery',
								false,
								array(
									'class'    => 'media-gallery__img',
									'alt'      => $alt,
									'loading'  => $index < 2 ? 'eager' : 'lazy',
									'decoding' => 'async',
									'sizes'    => '(min-width: 768px) 440px, 70vw',
								)
							);
							?>
						</figure>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<button
			type="button"
			class="media-gallery__arrow media-gallery__arrow--prev"
			data-media-gallery-prev
			aria-controls="<?php echo esc_attr( $section_id ); ?>"
			aria-label="<?php echo esc_attr__( 'Previous image', 'borgodasso' ); ?>">
			<?php
			borgodasso_template(
				'objects/svg',
				array(
					'name'  => 'arrow',
					'class' => 'media-gallery__arrow-icon media-gallery__arrow-icon--prev',
				)
			);
			?>
		</button>

		<button
			type="button"
			class="media-gallery__arrow media-gallery__arrow--next"
			data-media-gallery-next
			aria-controls="<?php echo esc_attr( $section_id ); ?>"
			aria-label="<?php echo esc_attr__( 'Next image', 'borgodasso' ); ?>">
			<?php
			borgodasso_template(
				'objects/svg',
				array(
					'name'  => 'arrow',
					'class' => 'media-gallery__arrow-icon media-gallery__arrow-icon--next',
				)
			);
			?>
		</button>

		<?php if ( $show_pagination ) : ?>
			<div
				class="media-gallery__pagination"
				data-media-gallery-pagination-el
				<?php if ( 'counter' === $pagination ) : ?>
				aria-live="polite"
				<?php else : ?>
				role="tablist"
				aria-label="<?php echo esc_attr__( 'Gallery slides', 'borgodasso' ); ?>"
				<?php endif; ?>>
				<?php if ( 'counter' === $pagination ) : ?>
					<span class="media-gallery__counter">
						<span data-media-gallery-current>01</span>/<span data-media-gallery-total><?php echo esc_html( str_pad( (string) $slide_count, 2, '0', STR_PAD_LEFT ) ); ?></span>
					</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php
/**
 * Story mosaic page-builder component.
 *
 * Left: two stacked landscape images. Right: text + tall portrait image.
 * (Any numeral is part of the uploaded artwork — not rendered in markup.)
 *
 * @package Borgodasso
 *
 * @var string $text                Body copy (HTML allowed).
 * @var int    $image_left_top_id
 * @var int    $image_left_bottom_id
 * @var int    $image_right_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text                 = $text ?? '';
$image_left_top_id    = isset( $image_left_top_id ) ? (int) $image_left_top_id : 0;
$image_left_bottom_id = isset( $image_left_bottom_id ) ? (int) $image_left_bottom_id : 0;
$image_right_id       = isset( $image_right_id ) ? (int) $image_right_id : 0;

if (
	'' === $text
	|| $image_left_top_id <= 0
	|| $image_left_bottom_id <= 0
	|| $image_right_id <= 0
) {
	return;
}

$alt_left_top    = borgodasso_attachment_alt( $image_left_top_id );
$alt_left_bottom = borgodasso_attachment_alt( $image_left_bottom_id );
$alt_right       = borgodasso_attachment_alt( $image_right_id );
?>
<section class="story-mosaic section">
	<div class="story-mosaic__inner">
		<div class="story-mosaic__left">
			<figure class="story-mosaic__media story-mosaic__media--left-top">
				<?php
				echo wp_get_attachment_image(
					$image_left_top_id,
					'borgodasso_mosaic_landscape',
					false,
					array(
						'class'    => 'story-mosaic__img',
						'alt'      => $alt_left_top,
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '(min-width: 1024px) 50vw, 100vw',
					)
				);
				?>
			</figure>

			<figure class="story-mosaic__media story-mosaic__media--left-bottom">
				<?php
				echo wp_get_attachment_image(
					$image_left_bottom_id,
					'borgodasso_mosaic_landscape',
					false,
					array(
						'class'    => 'story-mosaic__img',
						'alt'      => $alt_left_bottom,
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '(min-width: 1024px) 50vw, 100vw',
					)
				);
				?>
			</figure>
		</div>

		<div class="story-mosaic__right">
			<div class="story-mosaic__content text-body">
				<?php echo wp_kses_post( $text ); ?>
			</div>

			<figure class="story-mosaic__media story-mosaic__media--right">
				<?php
				echo wp_get_attachment_image(
					$image_right_id,
					'borgodasso_mosaic_portrait',
					false,
					array(
						'class'    => 'story-mosaic__img',
						'alt'      => $alt_right,
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '(min-width: 1024px) 40vw, 100vw',
					)
				);
				?>
			</figure>
		</div>
	</div>
</section>

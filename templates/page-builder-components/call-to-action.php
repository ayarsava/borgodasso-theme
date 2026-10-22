<?php
/**
 * Call-to-action page-builder component.
 *
 * @package Borgodasso
 *
 * @var string      $title
 * @var string|null $text
 * @var string      $button_label
 * @var string      $button_url
 * @var bool        $external
 * @var string      $variant                 primary panel style modifier.
 * @var int         $image_id                Optional image attachment ID.
 * @var string      $visibility              Image visibility: both | desktop | mobile.
 * @var string      $desktop_image_position  left | right.
 * @var string      $mobile_image_position   above | below.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$title                  = $title ?? '';
$text                   = $text ?? null;
$button_label           = $button_label ?? '';
$button_url             = $button_url ?? '';
$external               = ! empty( $external );
$variant                = $variant ?? 'default';
$image_id               = isset( $image_id ) ? (int) $image_id : 0;
$visibility             = $visibility ?? 'both';
$desktop_image_position = $desktop_image_position ?? 'right';
$mobile_image_position  = $mobile_image_position ?? 'above';

if ( '' === $title ) {
	return;
}

$visibility             = in_array( $visibility, array( 'both', 'desktop', 'mobile' ), true ) ? $visibility : 'both';
$desktop_image_position = 'left' === $desktop_image_position ? 'left' : 'right';
$mobile_image_position  = 'below' === $mobile_image_position ? 'below' : 'above';

$has_image = $image_id > 0 && wp_attachment_is_image( $image_id );

$classes = array(
	'cta',
	'section',
	'cta--' . sanitize_html_class( $variant ),
);

if ( $has_image ) {
	$classes[] = 'cta--has-media';
	$classes[] = 'cta--image-on-' . sanitize_html_class( $visibility );
	$classes[] = 'cta--desktop-image-' . sanitize_html_class( $desktop_image_position );
	$classes[] = 'cta--mobile-image-' . sanitize_html_class( $mobile_image_position );
}

$section_id = wp_unique_id( 'cta-title-' );
?>
<section
	class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
	aria-labelledby="<?php echo esc_attr( $section_id ); ?>"
>
	<div class="cta__inner">
		<div class="cta__content">
			<h2 id="<?php echo esc_attr( $section_id ); ?>" class="cta__title"><?php echo esc_html( $title ); ?></h2>

			<?php if ( $text ) : ?>
				<p class="cta__text"><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $button_label && '' !== $button_url ) : ?>
				<div class="cta__actions">
					<?php
					borgodasso_template(
						'objects/button',
						array(
							'label'    => $button_label,
							'url'      => $button_url,
							'variant'  => 'ghost',
							'class'    => 'cta__btn',
							'icon'     => 'arrow',
							'line'     => true,
							'external' => $external,
						)
					);
					?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $has_image ) : ?>
			<figure class="cta__media">
				<?php
				echo wp_get_attachment_image(
					$image_id,
					'large',
					false,
					array(
						'class'    => 'cta__img',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'alt'      => borgodasso_attachment_alt( $image_id, $title ),
					)
				);
				?>
			</figure>
		<?php endif; ?>
	</div>
</section>

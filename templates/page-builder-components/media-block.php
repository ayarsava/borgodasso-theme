<?php
/**
 * Media block page-builder component — text + image split layout.
 *
 * @package Borgodasso
 *
 * @var string      $title            Section heading.
 * @var string      $description      Body copy (HTML allowed via wp_kses_post).
 * @var array       $specs            Optional room spec items (icon + text).
 * @var string      $button_label     CTA label.
 * @var string      $button_url       CTA URL.
 * @var bool        $external         Open CTA in a new tab.
 * @var int         $image_id               Desktop image attachment ID.
 * @var int|null    $image_mobile_id        Optional mobile image attachment ID.
 * @var string      $image_position         left | right (desktop).
 * @var string      $image_layout           container | edge (desktop).
 * @var string      $mobile_image_position  above | below (mobile).
 * @var string|null $section_id             Unique ID for aria-labelledby.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$title                 = $title ?? '';
$description           = $description ?? '';
$specs                 = is_array( $specs ?? null ) ? $specs : array();
$button_label          = $button_label ?? '';
$button_url            = $button_url ?? '';
$external              = ! empty( $external );
$image_id              = isset( $image_id ) ? (int) $image_id : 0;
$image_mobile_id       = isset( $image_mobile_id ) ? (int) $image_mobile_id : 0;
$image_position        = $image_position ?? 'right';
$image_layout          = $image_layout ?? 'edge';
$mobile_image_position = $mobile_image_position ?? 'above';
$section_id            = $section_id ?? wp_unique_id( 'media-block-' );

if ( '' === $title || '' === $description || $image_id <= 0 ) {
	return;
}

$image_position        = 'left' === $image_position ? 'left' : 'right';
$image_layout          = 'container' === $image_layout ? 'container' : 'edge';
$mobile_image_position = 'below' === $mobile_image_position ? 'below' : 'above';

$position_class        = 'media-block--image-' . $image_position;
$layout_class          = 'media-block--image-' . ( 'edge' === $image_layout ? 'edge' : 'contained' );
$mobile_position_class = 'media-block--mobile-image-' . $mobile_image_position;

$alt         = borgodasso_attachment_alt( $image_id, $title );
$desktop_src = wp_get_attachment_image_src( $image_id, 'borgodasso_media_lg' );

if ( ! $desktop_src ) {
	return;
}

// Only emit a mobile <source> when a dedicated mobile image is set.
// Do not reuse a different hard-cropped size of the desktop attachment —
// that forced a 768×960 portrait crop on mobile.
$has_mobile = $image_mobile_id > 0;
$mobile_src = $has_mobile ? wp_get_attachment_image_src( $image_mobile_id, 'large' ) : false;
$mobile_url = is_array( $mobile_src ) ? $mobile_src[0] : '';
$has_mobile = $has_mobile && '' !== $mobile_url;

// Picture keeps desktop width/height on <img>; pass the mobile file's real
// ratio so CSS can size the frame correctly under md.
$media_style = '';
if ( $has_mobile && ! empty( $mobile_src[1] ) && ! empty( $mobile_src[2] ) ) {
	$media_style = sprintf(
		'--media-block-mobile-aspect: %d / %d',
		(int) $mobile_src[1],
		(int) $mobile_src[2]
	);
}
?>
<section
	class="media-block section <?php echo esc_attr( $position_class ); ?> <?php echo esc_attr( $layout_class ); ?> <?php echo esc_attr( $mobile_position_class ); ?>"
	aria-labelledby="<?php echo esc_attr( $section_id ); ?>">
	<div class="media-block__inner">
		<div class="media-block__content">
			<h2 id="<?php echo esc_attr( $section_id ); ?>" class="media-block__title text-h2">
				<?php echo esc_html( $title ); ?>
			</h2>

			<?php if ( ! empty( $specs ) ) : ?>
				<ul class="media-block__specs">
					<?php foreach ( $specs as $spec ) : ?>
						<?php
						$spec_key   = isset( $spec['key'] ) ? (string) $spec['key'] : '';
						$spec_class = 'media-block__spec';
						if ( '' !== $spec_key ) {
							$spec_class .= ' media-block__spec--' . sanitize_html_class( $spec_key );
						}
						?>
						<li class="<?php echo esc_attr( $spec_class ); ?>">
							<span class="media-block__spec-icon" aria-hidden="true">
								<?php
								borgodasso_template(
									'objects/svg',
									array(
										'name'  => $spec['icon'],
										'class' => 'media-block__spec-svg',
									)
								);
								?>
							</span>
							<span class="media-block__spec-text"><?php echo esc_html( $spec['text'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<div class="media-block__description text-body">
				<?php echo wp_kses_post( $description ); ?>
			</div>

			<?php if ( '' !== $button_label && '' !== $button_url ) : ?>
				<div class="media-block__actions">
					<?php
					borgodasso_template(
						'objects/button',
						array(
							'label'    => $button_label,
							'url'      => $button_url,
							'variant'  => 'ghost',
							'class'    => 'media-block__cta',
							'icon'     => 'arrow',
							'line'     => true,
							'external' => $external,
						)
					);
					?>
				</div>
			<?php endif; ?>
		</div>

		<figure
			class="media-block__media"
			<?php echo $media_style ? ' style="' . esc_attr( $media_style ) . '"' : ''; ?>>
			<picture class="media-block__picture">
				<?php if ( $has_mobile ) : ?>
					<source
						media="(max-width: 767px)"
						srcset="<?php echo esc_url( $mobile_url ); ?>"
						width="<?php echo esc_attr( (string) $mobile_src[1] ); ?>"
						height="<?php echo esc_attr( (string) $mobile_src[2] ); ?>">
				<?php endif; ?>
				<?php
				echo wp_get_attachment_image(
					$image_id,
					'borgodasso_media_lg',
					false,
					array(
						'class'    => 'media-block__img',
						'alt'      => $alt,
						'sizes'    => '(min-width: 768px) 58vw, 100vw',
						'loading'  => 'lazy',
						'decoding' => 'async',
					)
				);
				?>
			</picture>
		</figure>
	</div>
</section>
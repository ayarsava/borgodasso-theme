<?php
/**
 * Contact page layout — full-viewport Page header image + three centered info blocks.
 *
 * @package Borgodasso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data = borgodasso_contact_data();

if ( null === $data ) {
	return;
}

$title           = $data['title'];
$image_id        = $data['image_id'];
$image_mobile_id = $data['image_mobile_id'];
$blocks          = $data['blocks'];
$alt             = borgodasso_attachment_alt( $image_id, '' !== $title ? $title : get_the_title() );
$mobile_src      = wp_get_attachment_image_src( $image_mobile_id, 'full' );
?>


<section
	class="contact"
	data-contact
	data-page-header
	<?php if ( '' !== $title ) : ?>
	aria-labelledby="contact-page-title"
	<?php else : ?>
	aria-label="<?php echo esc_attr__( 'Contact', 'borgodasso' ); ?>"
	<?php endif; ?>>
	<div class="contact__media" aria-hidden="true">
		<picture class="contact__picture">
			<?php if ( is_array( $mobile_src ) ) : ?>
				<source
					media="(max-width: 767px)"
					srcset="<?php echo esc_url( $mobile_src[0] ); ?>"
					width="<?php echo absint( $mobile_src[1] > 0 ? $mobile_src[1] : 768 ); ?>"
					height="<?php echo absint( $mobile_src[2] > 0 ? $mobile_src[2] : 1024 ); ?>">
			<?php endif; ?>
			<?php
			echo wp_get_attachment_image(
				$image_id,
				'full',
				false,
				array(
					'class'    => 'contact__img',
					'loading'  => 'eager',
					'decoding' => 'async',
					'alt'      => $alt,
					'sizes'    => '100vw',
				)
			);
			?>
		</picture>
	</div>

	<div class="contact__content">

		<h1 class="contact__page-title"><?php the_title(); ?></h1>
		<?php if ( '' !== $title ) : ?>
			<h1 id="contact-page-title" class="contact__page-title"><?php echo esc_html( $title ); ?></h1>
		<?php endif; ?>

		<div class="contact__blocks">
			<?php foreach ( $blocks as $block ) : ?>
				<?php if ( '' === $block['title'] && '' === $block['content'] ) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<div class="contact__block">
					<?php if ( '' !== $block['title'] ) : ?>
						<h2 class="contact__title"><?php echo esc_html( $block['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( '' !== $block['content'] ) : ?>
						<div class="contact__body text-body">
							<?php echo wp_kses_post( $block['content'] ); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
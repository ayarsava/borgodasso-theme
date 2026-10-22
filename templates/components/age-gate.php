<?php
/**
 * Legal drinking age gate — full-bleed image + Yes/No panel.
 *
 * @package Borgodasso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data = function_exists( 'borgodasso_age_gate_data' ) ? borgodasso_age_gate_data() : null;

if ( null === $data ) {
	return;
}

$question        = $data['question'];
$yes_label       = $data['yes_label'];
$no_label        = $data['no_label'];
$image_id        = $data['image_id'];
$image_mobile_id = $data['image_mobile_id'];
$redirect_to     = $data['redirect_to'];
$section_id      = 'age-gate-question';
?>
<section
	class="age-gate"
	data-age-gate
	aria-labelledby="<?php echo esc_attr( $section_id ); ?>"
>
	<?php if ( $image_id > 0 ) : ?>
		<div class="age-gate__media" aria-hidden="true">
			<picture class="age-gate__picture">
				<?php if ( $image_mobile_id > 0 && $image_mobile_id !== $image_id ) : ?>
					<source
						media="(max-width: 767px)"
						srcset="<?php echo esc_url( (string) wp_get_attachment_image_url( $image_mobile_id, 'full' ) ); ?>"
					/>
				<?php endif; ?>
				<?php
				echo wp_get_attachment_image(
					$image_id,
					'full',
					false,
					array(
						'class'    => 'age-gate__img',
						'loading'  => 'eager',
						'decoding' => 'async',
						'alt'      => '',
					)
				);
				?>
			</picture>
		</div>
	<?php endif; ?>

	<div class="age-gate__panel">
		<h1 id="<?php echo esc_attr( $section_id ); ?>" class="age-gate__question">
			<?php echo esc_html( $question ); ?>
		</h1>

		<form class="age-gate__form" method="post" action="">
			<?php wp_nonce_field( 'borgodasso_age_gate', 'borgodasso_age_gate_nonce' ); ?>
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>" />

			<div class="age-gate__actions">
				<button
					type="submit"
					class="age-gate__btn age-gate__btn--yes"
					name="borgodasso_age_gate"
					value="yes"
				>
					<?php echo esc_html( $yes_label ); ?>
				</button>
				<button
					type="submit"
					class="age-gate__btn age-gate__btn--no"
					name="borgodasso_age_gate"
					value="no"
				>
					<?php echo esc_html( $no_label ); ?>
				</button>
			</div>
		</form>
	</div>
</section>

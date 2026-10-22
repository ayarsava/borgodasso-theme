<?php
/**
 * Text block page-builder component.
 *
 * @package Borgodasso
 *
 * @var string|null $eyebrow  Small label above title.
 * @var string|null $title
 * @var string|null $content  HTML allowed (wp_kses_post).
 * @var string|null $align    left | center (default left).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eyebrow = $eyebrow ?? null;
$title   = $title ?? null;
$content = $content ?? null;
$align   = $align ?? 'left';

if ( ! $title && ! $content ) {
	return;
}
?>
<section class="text-block section text-block--<?php echo esc_attr( sanitize_html_class( $align ) ); ?>  o-content-from-editor">
	<div class="text-block__inner">
		<?php if ( $eyebrow ) : ?>
			<p class="text-block__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>

		<?php if ( $title ) : ?>
			<h2 class="text-block__title"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>

		<?php if ( $content ) : ?>
			<div class="text-block__content">
				<?php echo wp_kses_post( $content ); ?>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php
/**
 * Card block object (title, text, optional media, optional CTA).
 *
 * @package Borgodasso
 *
 * @var string      $title
 * @var string|null $text
 * @var int|null    $image_id
 * @var string|null $url      Optional link wrapping the card.
 * @var string|null $class    Extra classes on .card-block.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$title    = $title ?? '';
$text     = $text ?? null;
$image_id = isset( $image_id ) ? (int) $image_id : null;
$url      = $url ?? null;
$class    = trim( 'card-block ' . ( $class ?? '' ) );

$tag = $url ? 'a' : 'article';
?>
<<?php echo esc_html( $tag ); ?>
	class="<?php echo esc_attr( $class ); ?>"
	<?php echo $url ? 'href="' . esc_url( $url ) . '"' : ''; ?>
>
	<?php if ( $image_id ) : ?>
		<div class="card-block__media">
			<?php
			borgodasso_template(
				'objects/image',
				array(
					'attachment_id' => $image_id,
					'class'         => 'card-block__figure',
				)
			);
			?>
		</div>
	<?php endif; ?>

	<?php if ( $title ) : ?>
		<h3 class="card-block__title"><?php echo esc_html( $title ); ?></h3>
	<?php endif; ?>

	<?php if ( $text ) : ?>
		<p class="card-block__text"><?php echo esc_html( $text ); ?></p>
	<?php endif; ?>
</<?php echo esc_html( $tag ); ?>>

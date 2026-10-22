<?php
/**
 * Card list page-builder component — 3-column grid of image + title + description.
 *
 * @package Borgodasso
 *
 * @var array<int, array{image_id: int, title: string, description: string}> $cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cards = isset( $cards ) && is_array( $cards ) ? $cards : array();

if ( array() === $cards ) {
	return;
}
?>
<section class="card-list section">
	<div class="card-list__inner">
		<ul class="card-list__grid">
			<?php foreach ( $cards as $card ) : ?>
				<li class="card-list__item">
					<figure class="card-list__figure">
						<?php
						echo wp_get_attachment_image(
							(int) $card['image_id'],
							'borgodasso_card',
							false,
							array(
								'class'    => 'card-list__img',
								'loading'  => 'lazy',
								'decoding' => 'async',
								'alt'      => borgodasso_attachment_alt( (int) $card['image_id'], (string) $card['title'] ),
							)
						);
						?>
					</figure>
					<h4 class="card-list__title"><?php echo esc_html( (string) $card['title'] ); ?></h4>
					<?php if ( '' !== $card['description'] ) : ?>
						<div class="card-list__description">
							<?php echo $card['description']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post in page-builder. ?>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

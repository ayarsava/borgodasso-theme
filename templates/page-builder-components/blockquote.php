<?php
/**
 * Blockquote page-builder component.
 *
 * @package Borgodasso
 *
 * @var string      $quote  The quotation text.
 * @var string|null $source Optional attribution / source.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quote  = $quote ?? '';
$source = $source ?? null;

if ( '' === $quote ) {
	return;
}
?>
<section class="blockquote section">
	<figure class="blockquote__inner">
		<blockquote class="blockquote__quote text-h3">
			<?php echo esc_html( $quote ); ?>
		</blockquote>

		<?php if ( $source ) : ?>
			<figcaption class="blockquote__source text-body"><?php echo esc_html( $source ); ?></figcaption>
		<?php endif; ?>
	</figure>
</section>

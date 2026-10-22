<?php
/**
 * Default content template part.
 *
 * @package Borgodasso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article <?php post_class( 'entry' ); ?>>
	<header class="entry__header">
		<?php
		if ( is_singular() ) {
			the_title( '<h1 class="entry__title">', '</h1>' );
		} else {
			the_title( '<h2 class="entry__title"><a href="' . esc_url( get_permalink() ) . '">', '</a></h2>' );
		}
		?>
	</header>
	<div class="entry__content">
		<?php
		if ( is_singular() ) {
			the_content();
		} else {
			the_excerpt();
		}
		?>
	</div>
</article>

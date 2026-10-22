<?php
/**
 * Page content template part.
 *
 * @package Borgodasso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article <?php post_class( 'page' ); ?>>
	<header class="page__header">
		<?php the_title( '<h1 class="page__title">', '</h1>' ); ?>
	</header>
	<div class="page__content">
		<?php the_content(); ?>
	</div>
</article>

<?php
/**
 * Template Name: Homage
 * Template Post Type: page
 *
 * Homepage — full-viewport story panels from the Homepage field group.
 *
 * When this page is also the site front page, front-page.php renders it.
 * The template slug is still stored so ACF can target it.
 *
 * @package Borgodasso
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="content-area content-area--front">
		<?php borgodasso_template( 'components/home' ); ?>
	</div>
	<?php
endwhile;

get_footer();

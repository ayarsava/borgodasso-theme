<?php
/**
 * Template Name: Casa
 * Template Post Type: page
 *
 * Apartment / casa pages — content from the Casa ACF group (no page builder).
 *
 * @package Borgodasso
 */

get_header();

while ( have_posts() ) :
	the_post();

	if ( borgodasso_page_has_header() ) {
		borgodasso_template( 'components/page-header' );
	}
	?>
	<div class="content-area content-area--page content-area--casa">
		<article <?php post_class( 'page page--casa' ); ?>>
			<?php borgodasso_template( 'components/casa' ); ?>
		</article>
	</div>
	<?php
endwhile;

get_footer();

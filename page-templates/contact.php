<?php
/**
 * Template Name: Contact
 * Template Post Type: page
 *
 * Full-viewport contact page. Background + optional title from Page header;
 * three centered info blocks from the Contact field group. No page builder / footer.
 *
 * @package Borgodasso
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="content-area content-area--page content-area--contact">
		<article <?php post_class( 'page page--contact' ); ?>>
			<?php borgodasso_template( 'components/contact' ); ?>
		</article>
	</div>
	<?php
endwhile;

get_footer();

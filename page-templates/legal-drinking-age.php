<?php
/**
 * Template Name: Legal Drinking Age Page
 * Template Post Type: page
 *
 * Full-viewport age gate before WooCommerce. Background + copy from ACF.
 *
 * @package Borgodasso
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="content-area content-area--page content-area--age-gate">
		<article <?php post_class( 'page page--age-gate' ); ?>>
			<?php borgodasso_template( 'components/age-gate' ); ?>
		</article>
	</div>
	<?php
endwhile;

get_footer();

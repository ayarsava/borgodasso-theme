<?php
/**
 * Main template.
 *
 * @package Borgodasso
 */

get_header();
?>
<div class="content-area">
	<?php if ( have_posts() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content', get_post_type() );
		endwhile;

		the_posts_navigation();
		?>
	<?php else : ?>
		<?php get_template_part( 'template-parts/content', 'none' ); ?>
	<?php endif; ?>
</div>
<?php
get_footer();

<?php
/**
 * Archive template.
 *
 * @package Borgodasso
 */

get_header();
?>
<div class="content-area">
	<header class="archive-header">
		<?php the_archive_title( '<h1 class="archive-header__title">', '</h1>' ); ?>
		<?php the_archive_description( '<div class="archive-header__description">', '</div>' ); ?>
	</header>
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

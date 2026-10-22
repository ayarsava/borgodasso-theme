<?php
/**
 * Search results template.
 *
 * @package Borgodasso
 */

get_header();
?>
<div class="content-area">
	<header class="search-header">
		<h1 class="search-header__title">
			<?php
			printf(
				/* translators: %s: search query */
				esc_html__( 'Search results for: %s', 'borgodasso' ),
				'<span>' . esc_html( get_search_query() ) . '</span>'
			);
			?>
		</h1>
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

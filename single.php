<?php
/**
 * Single post template.
 *
 * @package Borgodasso
 */

get_header();
?>
<div class="content-area">
	<?php
	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/content', get_post_type() );
	endwhile;
	?>
</div>
<?php
get_footer();

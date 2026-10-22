<?php
/**
 * 404 template.
 *
 * @package Borgodasso
 */

get_header();
?>
<div class="content-area">
	<section class="error-404">
		<header class="error-404__header">
			<h1 class="error-404__title"><?php esc_html_e( 'Page not found', 'borgodasso' ); ?></h1>
		</header>
		<div class="error-404__content">
			<p><?php esc_html_e( 'The page you are looking for could not be found.', 'borgodasso' ); ?></p>
			<?php get_search_form(); ?>
		</div>
	</section>
</div>
<?php
get_footer();

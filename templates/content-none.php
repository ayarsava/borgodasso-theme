<?php
/**
 * No content template part.
 *
 * @package Borgodasso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="no-results">
	<header class="no-results__header">
		<h1 class="no-results__title"><?php esc_html_e( 'Nothing found', 'borgodasso' ); ?></h1>
	</header>
	<div class="no-results__content">
		<p><?php esc_html_e( 'It seems we cannot find what you are looking for.', 'borgodasso' ); ?></p>
		<?php get_search_form(); ?>
	</div>
</section>

<?php
/**
 * Page template.
 *
 * Default page template (Page Builder + optional page header).
 * Casa pages use page-templates/casa.php.
 * Contact pages use page-templates/contact.php.
 *
 * @package Borgodasso
 */

get_header();

while ( have_posts() ) :
	the_post();

	$is_account  = function_exists( 'is_account_page' ) && is_account_page();
	$is_commerce = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() );

	if ( borgodasso_page_has_header() ) :
		borgodasso_template( 'components/page-header' );
		?>
		<div class="content-area content-area--page">
			<article <?php post_class( 'page' ); ?>>
				<?php if ( borgodasso_has_page_builder() ) : ?>
					<?php borgodasso_render_page_builder(); ?>
				<?php elseif ( get_the_content() ) : ?>
					<div class="page__content">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>
			</article>
		</div>
		<?php
	elseif ( $is_account ) :
		?>
		<div class="content-area content-area--account">
			<article <?php post_class( 'page page--account' ); ?>>
				<div class="page__content">
					<?php the_content(); ?>
				</div>
			</article>
		</div>
		<?php
	elseif ( $is_commerce ) :
		?>
		<div class="content-area content-area--commerce">
			<article <?php post_class( 'page page--commerce' ); ?>>
				<div class="commerce-shell">
					<div class="commerce-shell__inner page__content">
						<?php the_content(); ?>
					</div>
				</div>
			</article>
		</div>
		<?php
	else :
		?>
		<div class="content-area">
			<article <?php post_class( 'page' ); ?>>
				<header class="page__header">
					<?php the_title( '<h1 class="page__title">', '</h1>' ); ?>
				</header>
				<?php if ( borgodasso_has_page_builder() ) : ?>
					<?php borgodasso_render_page_builder(); ?>
				<?php else : ?>
					<div class="page__content">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>
			</article>
		</div>
		<?php
	endif;
endwhile;

get_footer();

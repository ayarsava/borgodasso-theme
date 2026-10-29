<?php
/**
 * Product archives, including the main shop page.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'is_shop' ) && is_shop() && borgodasso_page_has_header() ) {
	borgodasso_template( 'components/page-header' );
}

$catalog_products = array();

if ( function_exists( 'is_shop' ) && is_shop() ) {
	$catalog_products = borgodasso_get_catalog_products();
} elseif ( woocommerce_product_loop() && wc_get_loop_prop( 'total' ) ) {
	while ( have_posts() ) {
		the_post();
		$product = wc_get_product( get_the_ID() );
		if ( $product instanceof WC_Product && $product->is_visible() ) {
			$catalog_products[] = $product;
		}
	}
	rewind_posts();
}
?>

	<?php
	/**
	 * Hook: woocommerce_before_main_content.
	 */
	do_action( 'woocommerce_before_main_content' );
	?>

	<div class="content-area content-area--page content-area--shop">
		<div class="shop-catalog">
			<div class="shop-catalog__inner">
				<?php
				if ( function_exists( 'is_shop' ) && is_shop() ) {
					borgodasso_template( 'components/shop-nav' );
				}
				?>
				<?php
				/**
				 * Hook: woocommerce_shop_loop_header.
				 *
				 * @since 8.6.0
				 * @hooked woocommerce_product_taxonomy_archive_header - 10
				 */
				do_action( 'woocommerce_shop_loop_header' );

				/**
				 * Hook: woocommerce_before_shop_loop.
				 *
				 * @hooked woocommerce_output_all_notices - 10
				 */
				do_action( 'woocommerce_before_shop_loop' );

				if ( array() !== $catalog_products ) {
					borgodasso_template(
						'components/product-catalog-list',
						array(
							'products' => $catalog_products,
						)
					);
				} else {
					/**
					 * Hook: woocommerce_no_products_found.
					 *
					 * @hooked wc_no_products_found - 10
					 */
					do_action( 'woocommerce_no_products_found' );
				}
				?>
			</div>
		</div>
	</div>

	<?php
	/**
	 * Hook: woocommerce_after_main_content.
	 */
	do_action( 'woocommerce_after_main_content' );
	?>

<?php
get_footer();

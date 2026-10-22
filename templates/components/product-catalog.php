<?php
/**
 * Product catalog grid — all shop products below the PDP.
 *
 * Expected $args from borgodasso_template():
 * - products: array<int, WC_Product>
 *
 * @package Borgodasso
 *
 * @var array<int, WC_Product> $products
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$products = isset( $products ) && is_array( $products ) ? $products : array();

if ( array() === $products ) {
	return;
}
?>
<section class="product-catalog" aria-labelledby="product-catalog-title">
	<div class="product-catalog__inner">
		<h2 id="product-catalog-title" class="product-catalog__title">
			<?php echo esc_html__( 'Tutti i prodotti', 'borgodasso' ); ?>
		</h2>

		<?php
		borgodasso_template(
			'components/product-catalog-list',
			array(
				'products' => $products,
			)
		);
		?>
	</div>
</section>

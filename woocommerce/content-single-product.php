<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked woocommerce_output_all_notices - 10
 */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'product-pdp', $product ); ?> data-product-pdp>

	<div class="product-pdp__media">
		<?php
		/**
		 * Hook: woocommerce_before_single_product_summary.
		 *
		 * @hooked woocommerce_show_product_images - 20
		 */
		do_action( 'woocommerce_before_single_product_summary' );
		?>
	</div>

	<div class="product-pdp__summary summary entry-summary">
		<div class="product-pdp__summary-inner">
			<?php
			/**
			 * Hook: woocommerce_single_product_summary.
			 *
			 * @hooked woocommerce_template_single_title - 5
			 * @hooked borgodasso_template_single_sale_line - 10
			 * @hooked borgodasso_template_single_description - 20
			 * @hooked woocommerce_template_single_add_to_cart - 30
			 * @hooked borgodasso_template_single_info_rows - 40
			 * @hooked WC_Structured_Data::generate_product_data() - 60
			 */
			do_action( 'woocommerce_single_product_summary' );
			?>
		</div>
	</div>

	<?php
	/**
	 * Hook: woocommerce_after_single_product_summary.
	 */
	do_action( 'woocommerce_after_single_product_summary' );
	?>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>

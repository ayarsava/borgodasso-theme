<?php
/**
 * Single variation cart button
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 10.5.2
 */

defined( 'ABSPATH' ) || exit;

global $product;

$price_label = borgodasso_format_add_button_price( $product->get_price() );
?>
<div class="woocommerce-variation-add-to-cart variations_button product-pdp__actions">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<?php
	do_action( 'woocommerce_before_add_to_cart_quantity' );

	woocommerce_quantity_input(
		array(
			'min_value'   => $product->get_min_purchase_quantity(),
			'max_value'   => $product->get_max_purchase_quantity(),
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- WC quantity field.
			'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(),
			'classes'     => array( 'input-text', 'qty', 'text', 'screen-reader-text' ),
		)
	);

	do_action( 'woocommerce_after_add_to_cart_quantity' );
	?>

	<button
		type="submit"
		class="single_add_to_cart_button button alt product-pdp__add<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"
		data-add-label="<?php echo esc_attr( __( 'AGGIUNGI', 'borgodasso' ) ); ?>"
		data-adding-label="<?php echo esc_attr__( 'Aggiunta in corso', 'borgodasso' ); ?>"
		data-added-label="<?php echo esc_attr__( 'Aggiunto al carrello', 'borgodasso' ); ?>"
		data-product-add
	>
		<span class="product-pdp__add-label" aria-live="polite"><?php echo esc_html( __( 'AGGIUNGI', 'borgodasso' ) ); ?></span>
		<?php if ( '' !== $price_label ) : ?>
			<span class="product-pdp__add-sep" aria-hidden="true"> — </span>
			<span class="product-pdp__add-price" data-add-price><?php echo esc_html( $price_label ); ?></span>
		<?php else : ?>
			<span class="product-pdp__add-sep" aria-hidden="true" hidden> — </span>
			<span class="product-pdp__add-price" data-add-price hidden></span>
		<?php endif; ?>
	</button>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

	<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="variation_id" class="variation_id" value="0" />
</div>

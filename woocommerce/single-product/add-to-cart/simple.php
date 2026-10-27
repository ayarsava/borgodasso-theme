<?php
/**
 * Simple product add to cart
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 10.2.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

$attribute_rows = borgodasso_product_attribute_rows( $product );
$formato_static = borgodasso_product_formato_display( $product );
$price_label    = borgodasso_format_add_button_price( $product->get_price() );

echo wc_get_stock_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

if ( $product->is_in_stock() ) :
	do_action( 'woocommerce_before_add_to_cart_form' );
	?>

	<form class="cart product-pdp__form" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data">
		<?php if ( array() !== $attribute_rows || '' !== $formato_static ) : ?>
			<div class="product-pdp__options" role="group" aria-label="<?php esc_attr_e( 'Opzioni prodotto', 'borgodasso' ); ?>">
				<?php foreach ( $attribute_rows as $row ) : ?>
					<div class="product-pdp__option product-pdp__option--static product-pdp__option--single">
						<div class="product-pdp__option-trigger" aria-hidden="true">
							<span class="product-pdp__option-prefix"><?php echo esc_html( strtoupper( $row['label'] ) ); ?>:</span>
							<span class="product-pdp__option-value"><?php echo esc_html( $row['value'] ); ?></span>
						</div>
						<span class="screen-reader-text">
							<?php echo esc_html( $row['label'] . ': ' . $row['value'] ); ?>
						</span>
					</div>
				<?php endforeach; ?>

				<?php if ( '' !== $formato_static ) : ?>
					<div class="product-pdp__option product-pdp__option--static">
						<div class="product-pdp__option-trigger" aria-hidden="true">
							<span class="product-pdp__option-prefix"><?php echo esc_html( strtoupper( __( 'Formato', 'borgodasso' ) ) ); ?>:</span>
							<span class="product-pdp__option-value"><?php echo esc_html( $formato_static ); ?></span>
						</div>
						<span class="screen-reader-text">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: bottle/pack size */
									__( 'Formato: %s', 'borgodasso' ),
									$formato_static
								)
							);
							?>
						</span>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<div class="product-pdp__actions">
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
				name="add-to-cart"
				value="<?php echo esc_attr( $product->get_id() ); ?>"
				class="single_add_to_cart_button button alt product-pdp__add<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"
				data-product-add
			>
				<span class="product-pdp__add-label"><?php echo esc_html( __( 'AGGIUNGI', 'borgodasso' ) ); ?></span>
				<?php if ( '' !== $price_label ) : ?>
					<span class="product-pdp__add-sep" aria-hidden="true"> — </span>
					<span class="product-pdp__add-price"><?php echo esc_html( $price_label ); ?></span>
				<?php endif; ?>
			</button>

			<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
		</div>
	</form>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php endif; ?>

<?php
/**
 * Checkout shipping address — always visible (no “ship to different” toggle).
 *
 * @package Borgodasso
 * @version 11.2.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-shipping-fields">
	<?php if ( true === WC()->cart->needs_shipping_address() ) : ?>

		<?php // Checked + matching ID so WC checkout.js keeps .shipping_address visible. ?>
		<p id="ship-to-different-address" class="checkout-ship-to-different">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
				<input
					id="ship-to-different-address-checkbox"
					class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
					type="checkbox"
					name="ship_to_different_address"
					value="1"
					checked="checked"
				/>
				<span><?php esc_html_e( 'Ship to a different address?', 'woocommerce' ); ?></span>
			</label>
		</p>

		<div class="shipping_address">
			<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>

			<div class="woocommerce-shipping-fields__field-wrapper">
				<?php
				$fields = $checkout->get_checkout_fields( 'shipping' );

				foreach ( $fields as $key => $field ) {
					woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				}
				?>
			</div>

			<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>
		</div>

	<?php endif; ?>
</div>
<?php
// Order notes intentionally omitted — theme checkout has no “Additional information” block.
?>

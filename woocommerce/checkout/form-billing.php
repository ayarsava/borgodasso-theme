<?php
/**
 * Checkout billing address (contact fields rendered separately under Contatti).
 *
 * Radios + optional address fields live in one bordered panel (design mock).
 *
 * @package Borgodasso
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;

$contact_keys = function_exists( 'borgodasso_checkout_contact_field_keys' )
	? borgodasso_checkout_contact_field_keys()
	: array( 'billing_email' );

$has_shipping = WC()->cart && WC()->cart->needs_shipping_address();
$billing_same = true;

// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Re-render after validation; WC nonce; sanitized below.
if ( isset( $_POST['borgodasso_billing_same'] ) ) {
	$billing_same = 'different' !== wc_clean( wp_unslash( (string) $_POST['borgodasso_billing_same'] ) );
}
// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
?>
<div class="woocommerce-billing-fields">
	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<?php if ( $has_shipping ) : ?>
		<div class="checkout-billing-panel" data-billing-same>
			<fieldset class="checkout-billing-choice">
				<legend class="screen-reader-text"><?php echo esc_html__( 'Indirizzo di fatturazione', 'borgodasso' ); ?></legend>
				<p class="checkout-billing-choice__option form-row">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="borgodasso_billing_same">
						<input
							type="radio"
							class="woocommerce-form__input woocommerce-form__input-radio input-radio"
							name="borgodasso_billing_same"
							id="borgodasso_billing_same"
							value="same"
							<?php checked( $billing_same, true ); ?>
						/>
						<span><?php echo esc_html__( 'Uguale all’indirizzo di spedizione', 'borgodasso' ); ?></span>
					</label>
				</p>
				<p class="checkout-billing-choice__option form-row">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="borgodasso_billing_different">
						<input
							type="radio"
							class="woocommerce-form__input woocommerce-form__input-radio input-radio"
							name="borgodasso_billing_same"
							id="borgodasso_billing_different"
							value="different"
							<?php checked( $billing_same, false ); ?>
						/>
						<span><?php echo esc_html__( 'Diverso dall’indirizzo di spedizione', 'borgodasso' ); ?></span>
					</label>
				</p>
			</fieldset>

			<div
				class="woocommerce-billing-fields__field-wrapper checkout-billing-panel__fields"
				data-billing-fields
				<?php echo $billing_same ? ' hidden' : ''; ?>
				<?php echo $billing_same ? ' aria-hidden="true"' : ''; ?>
			>
				<?php
				$fields = $checkout->get_checkout_fields( 'billing' );

				foreach ( $fields as $key => $field ) {
					if ( in_array( $key, $contact_keys, true ) ) {
						continue;
					}
					woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				}
				?>
			</div>
		</div>
	<?php else : ?>
		<div class="woocommerce-billing-fields__field-wrapper" data-billing-fields>
			<?php
			$fields = $checkout->get_checkout_fields( 'billing' );

			foreach ( $fields as $key => $field ) {
				if ( in_array( $key, $contact_keys, true ) ) {
					continue;
				}
				woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
			}
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>

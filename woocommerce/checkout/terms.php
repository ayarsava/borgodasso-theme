<?php
/**
 * Checkout legal checkboxes — Condizioni di vendita + Privacy Policy.
 *
 * @package Borgodasso
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

$terms_url   = function_exists( 'borgodasso_checkout_terms_url' ) ? borgodasso_checkout_terms_url() : '';
$privacy_url = function_exists( 'borgodasso_checkout_privacy_url' ) ? borgodasso_checkout_privacy_url() : '';

$terms_label = $terms_url
	? sprintf(
		/* translators: %s: terms of sale URL */
		__( 'Accetto le <a href="%s" target="_blank" rel="noopener noreferrer">Condizioni di vendita</a> di Borgo d’Asso.', 'borgodasso' ),
		esc_url( $terms_url )
	)
	: __( 'Accetto le Condizioni di vendita di Borgo d’Asso.', 'borgodasso' );

$privacy_label = $privacy_url
	? sprintf(
		/* translators: %s: privacy policy URL */
		__( 'Accetto la <a href="%s" target="_blank" rel="noopener noreferrer">Privacy Policy</a> di Borgo d’Asso.', 'borgodasso' ),
		esc_url( $privacy_url )
	)
	: __( 'Accetto la Privacy Policy di Borgo d’Asso.', 'borgodasso' );

// phpcs:disable WordPress.Security.NonceVerification.Missing -- Presence check only while re-rendering after validation errors.
$terms_checked   = ! empty( $_POST['terms'] );
$privacy_checked = ! empty( $_POST['borgodasso_privacy'] );
// phpcs:enable WordPress.Security.NonceVerification.Missing
?>
<div class="woocommerce-terms-and-conditions-wrapper checkout-legal">
	<p class="form-row validate-required checkout-legal__row">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
			<input
				type="checkbox"
				class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
				name="terms"
				id="terms"
				<?php checked( $terms_checked, true ); ?>
			/>
			<span class="woocommerce-terms-and-conditions-checkbox-text"><?php echo wp_kses_post( $terms_label ); ?></span>
			&nbsp;<abbr class="required" title="<?php esc_attr_e( 'required', 'woocommerce' ); ?>">*</abbr>
		</label>
		<input type="hidden" name="terms-field" value="1" />
	</p>

	<p class="form-row validate-required checkout-legal__row">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
			<input
				type="checkbox"
				class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
				name="borgodasso_privacy"
				id="borgodasso_privacy"
				<?php checked( $privacy_checked, true ); ?>
			/>
			<span class="checkout-privacy-checkbox-text"><?php echo wp_kses_post( $privacy_label ); ?></span>
			&nbsp;<abbr class="required" title="<?php esc_attr_e( 'required', 'woocommerce' ); ?>">*</abbr>
		</label>
	</p>
</div>

<?php
/**
 * Checkout coupon — always visible in the summary column.
 *
 * @package Borgodasso
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! wc_coupons_enabled() ) {
	return;
}
?>
<form class="checkout_coupon woocommerce-form-coupon checkout-coupon" method="post" id="woocommerce-checkout-form-coupon">
	<p class="form-row checkout-coupon__row">
		<label for="coupon_code" class="checkout-coupon__label"><?php echo esc_html__( 'Codice promozionale', 'borgodasso' ); ?></label>
		<span class="checkout-coupon__controls">
			<input
				type="text"
				name="coupon_code"
				class="input-text checkout-coupon__input"
				placeholder="<?php esc_attr_e( 'Codice promozionale', 'borgodasso' ); ?>"
				id="coupon_code"
				value=""
			/>
			<button
				type="submit"
				class="button checkout-coupon__apply<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"
				name="apply_coupon"
				value="<?php esc_attr_e( 'Applica', 'borgodasso' ); ?>"
			>
				<?php echo esc_html__( 'Applica', 'borgodasso' ); ?>
			</button>
		</span>
	</p>
</form>

<?php
/**
 * Checkout login reminder — link to My Account (with return to checkout).
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 10.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( is_user_logged_in() ) {
	return;
}

if ( 'yes' !== get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
	return;
}

$checkout_url = function_exists( 'borgodasso_checkout_url' )
	? borgodasso_checkout_url()
	: wc_get_checkout_url();

$login_url = function_exists( 'borgodasso_account_login_url' )
	? borgodasso_account_login_url( $checkout_url )
	: (string) add_query_arg( 'redirect', $checkout_url, wc_get_page_permalink( 'myaccount' ) );
?>
<div class="woocommerce-form-login-toggle">
	<?php
	wc_print_notice(
		apply_filters( 'woocommerce_checkout_login_message', esc_html__( 'Returning customer?', 'woocommerce' ) )
		. ' <a href="' . esc_url( $login_url ) . '">' . esc_html__( 'Click here to login', 'woocommerce' ) . '</a>',
		'notice'
	);
	?>
</div>

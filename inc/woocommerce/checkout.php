<?php
/**
 * WooCommerce checkout hooks.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mark an order so the first post-checkout view can clear the cart even if
 * the WC session key `order_awaiting_payment` was lost (Stripe / 3DS).
 *
 * @param int $order_id New order id.
 */
function borgodasso_mark_order_cart_clear_pending( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$order->update_meta_data( '_borgodasso_clear_cart_pending', 'yes' );
	$order->save();
}
add_action( 'woocommerce_checkout_order_processed', 'borgodasso_mark_order_cart_clear_pending', 10, 1 );

/**
 * Whether the current cart is empty or only contains lines from this order.
 *
 * Protects an unrelated in-progress cart (e.g. paying an older order-pay).
 *
 * @param WC_Order $order Order being viewed after checkout / pay.
 */
function borgodasso_cart_is_empty_or_matches_order( WC_Order $order ): bool {
	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		return true;
	}

	$order_ids = array();
	foreach ( $order->get_items() as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}
		$variation_id = (int) $item->get_variation_id();
		$order_ids[]  = $variation_id > 0 ? $variation_id : (int) $item->get_product_id();
	}

	if ( array() === $order_ids ) {
		return false;
	}

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$variation_id = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
		$id           = $variation_id > 0 ? $variation_id : (int) ( $cart_item['product_id'] ?? 0 );
		if ( ! in_array( $id, $order_ids, true ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Decide if this order view should empty the shopper's cart.
 *
 * Never clear on a bare revisit of thank-you / view-order — that can wipe a
 * new shopping session. Clear only when the session still marks this order as
 * awaiting payment, or on a short-lived pending-meta backup when the cart
 * still matches the order lines.
 *
 * @param int $order_id Order id from the current endpoint.
 */
function borgodasso_should_clear_cart_for_order( int $order_id ): bool {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return false;
	}

	$order_user = (int) $order->get_user_id();
	if ( $order_user > 0 && ( ! is_user_logged_in() || get_current_user_id() !== $order_user ) ) {
		return false;
	}

	$awaiting = ( WC()->session ) ? absint( WC()->session->get( 'order_awaiting_payment' ) ) : 0;
	$pending  = ( 'yes' === $order->get_meta( '_borgodasso_clear_cart_pending' ) );

	if ( $order_id !== $awaiting && ! $pending ) {
		return false;
	}

	// Unrelated cart (new items while paying an older order) — leave it alone.
	if ( ! borgodasso_cart_is_empty_or_matches_order( $order ) ) {
		if ( $pending ) {
			$order->delete_meta_data( '_borgodasso_clear_cart_pending' );
			$order->save();
		}
		if ( $awaiting === $order_id && WC()->session ) {
			unset( WC()->session->order_awaiting_payment );
		}
		return false;
	}

	if ( $awaiting === $order_id ) {
		return true;
	}

	// Pending-meta backup: only for recently placed orders.
	$created = $order->get_date_created();
	if ( ! $created || $created->getTimestamp() < ( time() - 2 * HOUR_IN_SECONDS ) ) {
		$order->delete_meta_data( '_borgodasso_clear_cart_pending' );
		$order->save();
		return false;
	}

	return true;
}

/**
 * Empty the cart after checkout (logged-in path skips WC thankyou shortcode).
 *
 * Uses empty_cart( true ) so persistent cart usermeta is destroyed too —
 * otherwise purchased items reappear on the next add-to-cart / session load.
 *
 * @param int $order_id Order that just finished checkout.
 */
function borgodasso_checkout_clear_cart_after_order( int $order_id ): void {
	if ( $order_id <= 0 || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	if ( ! borgodasso_should_clear_cart_for_order( $order_id ) ) {
		return;
	}

	WC()->cart->empty_cart( true );

	if ( WC()->session ) {
		unset( WC()->session->order_awaiting_payment );
	}

	$order = wc_get_order( $order_id );
	if ( $order instanceof WC_Order ) {
		$order->delete_meta_data( '_borgodasso_clear_cart_pending' );
		$order->save();
	}
}

/**
 * Order-received → My Account view-order when the buyer is logged in.
 *
 * Keeps thank-you and account order detail on the same designed template
 * (Account sidebar + order-detail). Guests still use checkout/thankyou.php.
 */
function borgodasso_redirect_order_received_to_account(): void {
	if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	$order_id = absint( get_query_var( 'order-received' ) );
	if ( $order_id <= 0 ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	if ( get_current_user_id() !== (int) $order->get_user_id() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Order key from redirect query; sanitized below.
	$key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( (string) $_GET['key'] ) ) : '';
	if ( '' !== $key && ! hash_equals( $order->get_order_key(), $key ) ) {
		return;
	}

	// Thankyou shortcode never runs on this redirect — clear cart when safe.
	borgodasso_checkout_clear_cart_after_order( $order_id );

	wp_safe_redirect( $order->get_view_order_url() );
	exit;
}
add_action( 'template_redirect', 'borgodasso_redirect_order_received_to_account', 5 );

/**
 * Prefer classic checkout shortcode thankyou (theme override) over block confirmation.
 *
 * @param string   $url   Order received URL.
 * @param WC_Order $order Order.
 */
function borgodasso_order_received_url_for_customers( string $url, $order ): string {
	if ( ! $order instanceof WC_Order || ! is_user_logged_in() ) {
		return $url;
	}

	if ( (int) $order->get_user_id() !== get_current_user_id() ) {
		return $url;
	}

	return $order->get_view_order_url();
}
add_filter( 'woocommerce_get_checkout_order_received_url', 'borgodasso_order_received_url_for_customers', 10, 2 );

/**
 * Clear cart when checkout redirects straight to view-order (skips order-received).
 */
function borgodasso_empty_cart_on_view_order_after_checkout(): void {
	if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'view-order' ) ) {
		return;
	}

	$order_id = absint( get_query_var( 'view-order' ) );
	borgodasso_checkout_clear_cart_after_order( $order_id );
}
add_action( 'template_redirect', 'borgodasso_empty_cart_on_view_order_after_checkout', 6 );

/**
 * Force shipping-first destination so shipping + billing are separate sections.
 *
 * @return string
 */
function borgodasso_force_ship_to_destination(): string {
	return 'shipping';
}
add_filter( 'pre_option_woocommerce_ship_to_destination', 'borgodasso_force_ship_to_destination' );

/**
 * Keep promotional codes available on checkout.
 *
 * @return string
 */
function borgodasso_force_coupons_enabled(): string {
	return 'yes';
}
add_filter( 'pre_option_woocommerce_enable_coupons', 'borgodasso_force_coupons_enabled' );

/**
 * Checkout place-order label.
 *
 * @return string
 */
function borgodasso_order_button_text(): string {
	return __( 'Paga ora', 'borgodasso' );
}
add_filter( 'woocommerce_order_button_text', 'borgodasso_order_button_text' );

/**
 * Pay-for-order submit label (same as checkout).
 *
 * @return string
 */
function borgodasso_pay_order_button_text(): string {
	return __( 'Paga ora', 'borgodasso' );
}
add_filter( 'woocommerce_pay_order_button_text', 'borgodasso_pay_order_button_text' );

/**
 * Italian guest-order warning on the pay-for-order page.
 *
 * @param string $translation Translated text.
 * @param string $text        Original text.
 * @param string $domain      Text domain.
 */
function borgodasso_order_pay_guest_notice_gettext( string $translation, string $text, string $domain ): string {
	if ( 'woocommerce' !== $domain ) {
		return $translation;
	}

	if ( 'You are paying for a guest order. Please continue with payment only if you recognize this order.' === $text ) {
		return __( 'Stai pagando un ordine ospite. Continua solo se riconosci questo ordine.', 'borgodasso' );
	}

	return $translation;
}
add_filter( 'gettext', 'borgodasso_order_pay_guest_notice_gettext', 10, 3 );

/**
 * Re-wire classic checkout: payment + shipping methods on the left; summary on the right.
 */
function borgodasso_checkout_layout_hooks(): void {
	remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
	remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
	remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );

	// Contact lives in Contatti; don’t duplicate create-account under billing.
	remove_action( 'woocommerce_before_checkout_registration_form', 'woocommerce_checkout_login_form', 10 );
}
add_action( 'wp', 'borgodasso_checkout_layout_hooks' );

/**
 * Whether a shipping location looks like Google Pay’s sandbox / placeholder address
 * (Mountain View, CA 94043) left in the WC customer session by Stripe ECE.
 *
 * @param WC_Customer $customer Customer (usually session-backed).
 */
function borgodasso_customer_shipping_looks_like_google_placeholder( WC_Customer $customer ): bool {
	$country  = strtoupper( (string) $customer->get_shipping_country() );
	$postcode = preg_replace( '/\s+/', '', (string) $customer->get_shipping_postcode() );
	$city     = strtolower( (string) $customer->get_shipping_city() );

	if ( 'US' !== $country ) {
		return false;
	}

	if ( '94043' === $postcode ) {
		return true;
	}

	return false !== strpos( $city, 'mountain view' );
}

/**
 * On full checkout page load, restore logged-in account addresses into the session
 * when Stripe/Google Pay express checkout has polluted them (e.g. Mountain View 94043).
 *
 * Skips AJAX `update_checkout` so in-form edits still stick for the visit.
 */
function borgodasso_checkout_restore_account_address_from_session_pollution(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}

	if ( is_order_received_page() || is_wc_endpoint_url( 'order-pay' ) ) {
		return;
	}

	if ( wp_doing_ajax() || isset( $_GET['wc-ajax'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	if ( ! is_user_logged_in() || ! function_exists( 'WC' ) || ! WC()->customer ) {
		return;
	}

	$session_customer = WC()->customer;

	if ( ! borgodasso_customer_shipping_looks_like_google_placeholder( $session_customer ) ) {
		return;
	}

	$account = new WC_Customer( get_current_user_id(), false );

	if ( '' === (string) $account->get_shipping_country() && '' === (string) $account->get_shipping_address_1() ) {
		return;
	}

	$suffixes = array(
		'first_name',
		'last_name',
		'company',
		'address_1',
		'address_2',
		'city',
		'state',
		'postcode',
		'country',
		'phone',
	);

	foreach ( array( 'shipping', 'billing' ) as $type ) {
		foreach ( $suffixes as $suffix ) {
			$getter = "get_{$type}_{$suffix}";
			$setter = "set_{$type}_{$suffix}";

			if ( ! is_callable( array( $account, $getter ) ) || ! is_callable( array( $session_customer, $setter ) ) ) {
				continue;
			}

			$session_customer->{$setter}( $account->{$getter}() );
		}
	}

	// Billing email stays on the account customer object.
	if ( is_callable( array( $account, 'get_billing_email' ) ) && is_callable( array( $session_customer, 'set_billing_email' ) ) ) {
		$email = (string) $account->get_billing_email();
		if ( '' !== $email ) {
			$session_customer->set_billing_email( $email );
		}
	}

	$session_customer->set_calculated_shipping( false );
	$session_customer->save();
}
add_action( 'template_redirect', 'borgodasso_checkout_restore_account_address_from_session_pollution', 20 );

/**
 * Checkout field values: for logged-in users, prefer account meta over a session
 * still holding the Google Pay placeholder shipping address.
 *
 * @param mixed  $value Default value (null to continue).
 * @param string $input Field key.
 * @return mixed
 */
function borgodasso_checkout_get_value_prefer_account( $value, string $input ) {
	if ( null !== $value || ! is_user_logged_in() || ! function_exists( 'WC' ) || ! WC()->customer ) {
		return $value;
	}

	if ( 0 !== strpos( $input, 'shipping_' ) && 0 !== strpos( $input, 'billing_' ) ) {
		return $value;
	}

	if ( ! borgodasso_customer_shipping_looks_like_google_placeholder( WC()->customer ) ) {
		return $value;
	}

	$account = new WC_Customer( get_current_user_id(), false );
	$getter  = 'get_' . $input;

	if ( ! is_callable( array( $account, $getter ) ) ) {
		return $value;
	}

	$account_value = $account->{$getter}();

	return '' === $account_value ? $value : $account_value;
}
add_filter( 'woocommerce_checkout_get_value', 'borgodasso_checkout_get_value_prefer_account', 10, 2 );

/**
 * Open express-checkout wrapper + “Express checkout” label (before Stripe / PayPal).
 */
function borgodasso_checkout_express_start(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
		return;
	}
	?>
	<div class="checkout-express">
		<p class="checkout-express__label">
			<span class="checkout-express__label-text"><?php echo esc_html__( 'Express checkout', 'borgodasso' ); ?></span>
		</p>
	<?php
}
add_action( 'woocommerce_checkout_before_customer_details', 'borgodasso_checkout_express_start', 0 );

/**
 * Custom separator under express buttons (Stripe’s default “OR” is hidden in CSS).
 */
function borgodasso_checkout_express_separator(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
		return;
	}
	?>
		<p class="checkout-express__separator">
			<span class="checkout-express__separator-text"><?php echo esc_html__( 'Or continue below', 'borgodasso' ); ?></span>
		</p>
	<?php
}
add_action( 'woocommerce_checkout_before_customer_details', 'borgodasso_checkout_express_separator', 15 );

/**
 * Close express-checkout wrapper.
 */
function borgodasso_checkout_express_end(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
		return;
	}

	echo '</div>';
}
add_action( 'woocommerce_checkout_before_customer_details', 'borgodasso_checkout_express_end', 20 );

/**
 * Render PayPal smart buttons in the express block (next to Stripe ECE).
 *
 * @param string $hook Default PayPal checkout button hook.
 */
function borgodasso_checkout_paypal_express_hook( string $hook ): string {
	return 'woocommerce_checkout_before_customer_details';
}
add_filter( 'woocommerce_paypal_payments_checkout_button_renderer_hook', 'borgodasso_checkout_paypal_express_hook' );

/**
 * Prefer black rectangular PayPal buttons on classic checkout.
 *
 * @param array<string, mixed> $data Localized PayPal button script data.
 * @return array<string, mixed>
 */
function borgodasso_checkout_paypal_button_style( array $data ): array {
	if ( empty( $data['button']['style'] ) || ! is_array( $data['button']['style'] ) ) {
		return $data;
	}

	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return $data;
	}

	$data['button']['style']['color']  = 'black';
	$data['button']['style']['shape']  = 'rect';
	$data['button']['style']['label']  = 'paypal';
	$data['button']['style']['layout'] = 'horizontal';
	$data['button']['style']['tagline'] = false;
	$data['button']['style']['height'] = 48;

	return $data;
}
add_filter( 'woocommerce_paypal_payments_localized_script_data', 'borgodasso_checkout_paypal_button_style' );

/**
 * Contact field keys shown under Contatti.
 *
 * @return string[]
 */
function borgodasso_checkout_contact_field_keys(): array {
	// Design: Contatti = email only; phone lives under shipping address.
	return array( 'billing_email' );
}

/**
 * Address field suffixes copied when billing = shipping.
 *
 * @return string[]
 */
function borgodasso_checkout_address_field_keys(): array {
	return array( 'first_name', 'last_name', 'company', 'country', 'address_1', 'city', 'state', 'postcode', 'phone' );
}

/**
 * Whether the shopper chose a billing address different from shipping.
 */
function borgodasso_checkout_billing_is_different(): bool {
	// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Checkout field filters; WC verifies nonce; value sanitized below.
	$value = isset( $_POST['borgodasso_billing_same'] )
		? wc_clean( wp_unslash( (string) $_POST['borgodasso_billing_same'] ) )
		: 'same';
	// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	return 'different' === $value;
}

/**
 * When billing matches shipping, copy shipping address into billing posted data.
 *
 * @param array<string, mixed> $data Posted checkout data.
 * @return array<string, mixed>
 */
function borgodasso_checkout_copy_shipping_to_billing( array $data ): array {
	if ( borgodasso_checkout_billing_is_different() ) {
		return $data;
	}

	if ( ! WC()->cart || ! WC()->cart->needs_shipping_address() ) {
		return $data;
	}

	foreach ( borgodasso_checkout_address_field_keys() as $key ) {
		$shipping_key = 'shipping_' . $key;
		$billing_key  = 'billing_' . $key;
		if ( isset( $data[ $shipping_key ] ) ) {
			$data[ $billing_key ] = $data[ $shipping_key ];
		}
	}

	return $data;
}
add_filter( 'woocommerce_checkout_posted_data', 'borgodasso_checkout_copy_shipping_to_billing', 20 );

/**
 * Don’t require billing address fields when “same as shipping” is selected.
 *
 * @param array<string, array<string, mixed>> $fields Checkout fields.
 * @return array<string, array<string, mixed>>
 */
function borgodasso_checkout_billing_optional_when_same( array $fields ): array {
	if ( borgodasso_checkout_billing_is_different() ) {
		return $fields;
	}

	if ( ! WC()->cart || ! WC()->cart->needs_shipping_address() ) {
		return $fields;
	}

	$contact = borgodasso_checkout_contact_field_keys();

	if ( empty( $fields['billing'] ) || ! is_array( $fields['billing'] ) ) {
		return $fields;
	}

	foreach ( $fields['billing'] as $key => $field ) {
		if ( in_array( $key, $contact, true ) ) {
			continue;
		}
		if ( is_array( $field ) ) {
			$fields['billing'][ $key ]['required'] = false;
		}
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'borgodasso_checkout_billing_optional_when_same', 100 );

/**
 * Italian labels for default address fields (site copy is IT even if WP locale is en).
 *
 * @param array<string, array<string, mixed>> $fields Address fields.
 * @return array<string, array<string, mixed>>
 */
function borgodasso_default_address_fields( array $fields ): array {
	$map = array(
		'first_name' => array(
			'label'       => __( 'Nome', 'borgodasso' ),
			'placeholder' => __( 'Nome', 'borgodasso' ),
		),
		'last_name'  => array(
			'label'       => __( 'Cognome', 'borgodasso' ),
			'placeholder' => __( 'Cognome', 'borgodasso' ),
		),
		'company'    => array(
			'label'       => __( 'Azienda', 'borgodasso' ),
			'placeholder' => __( 'Azienda (opzionale)', 'borgodasso' ),
		),
		'country'    => array(
			'label' => __( 'Paese', 'borgodasso' ),
		),
		'address_1'  => array(
			'label'       => __( 'Indirizzo', 'borgodasso' ),
			'placeholder' => __( 'Numero civico e via', 'borgodasso' ),
		),
		'city'       => array(
			'label'       => __( 'Città', 'borgodasso' ),
			'placeholder' => __( 'Città', 'borgodasso' ),
		),
		'state'      => array(
			'label'       => __( 'Provincia', 'borgodasso' ),
			'placeholder' => __( 'Provincia', 'borgodasso' ),
		),
		'postcode'   => array(
			'label'       => __( 'CAP', 'borgodasso' ),
			'placeholder' => __( 'CAP', 'borgodasso' ),
		),
	);

	foreach ( $map as $key => $labels ) {
		if ( ! isset( $fields[ $key ] ) || ! is_array( $fields[ $key ] ) ) {
			continue;
		}
		foreach ( $labels as $prop => $value ) {
			$fields[ $key ][ $prop ] = $value;
		}
	}

	// Drop optional line 2 — not used in account / checkout address forms.
	unset( $fields['address_2'] );

	// Nome → Cognome → Paese → … (shared by shipping + billing).
	$priorities = array(
		'first_name' => 10,
		'last_name'  => 20,
		'country'    => 30,
		'company'    => 40,
		'address_1'  => 50,
		'city'       => 60,
		'postcode'   => 70,
		'state'      => 80,
	);
	foreach ( $priorities as $key => $priority ) {
		if ( isset( $fields[ $key ] ) && is_array( $fields[ $key ] ) ) {
			$fields[ $key ]['priority'] = $priority;
		}
	}

	return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'borgodasso_default_address_fields', 20 );

/**
 * Customize checkout fields: Italian section labels + Contatti split.
 *
 * @param array<string, array<string, mixed>> $fields Checkout fields.
 * @return array<string, array<string, mixed>>
 */
function borgodasso_checkout_fields( array $fields ): array {
	$contact_keys = borgodasso_checkout_contact_field_keys();

	if ( isset( $fields['billing'] ) && is_array( $fields['billing'] ) ) {
		foreach ( $contact_keys as $key ) {
			if ( isset( $fields['billing'][ $key ] ) && is_array( $fields['billing'][ $key ] ) ) {
				$fields['billing'][ $key ]['class'] = array( 'form-row-wide', 'checkout-contact-field' );
			}
		}

		if ( isset( $fields['billing']['billing_email'] ) && is_array( $fields['billing']['billing_email'] ) ) {
			$fields['billing']['billing_email']['label']       = __( 'Email', 'borgodasso' );
			$fields['billing']['billing_email']['placeholder'] = __( 'Email', 'borgodasso' );
			$fields['billing']['billing_email']['priority']    = 5;
		}

		if ( isset( $fields['billing']['billing_phone'] ) && is_array( $fields['billing']['billing_phone'] ) ) {
			$fields['billing']['billing_phone']['label']       = __( 'Numero di telefono', 'borgodasso' );
			$fields['billing']['billing_phone']['placeholder'] = __( 'Numero di telefono', 'borgodasso' );
			$fields['billing']['billing_phone']['priority']    = 100;
			$fields['billing']['billing_phone']['required']    = false;
			$fields['billing']['billing_phone']['class']       = array( 'form-row-wide' );
		}

		unset( $fields['billing']['billing_address_2'] );

		// Nome → Cognome → Paese → Address → City → Postcode/State → Phone.
		$billing_priorities = array(
			'billing_first_name' => 10,
			'billing_last_name'  => 20,
			'billing_country'    => 30,
			'billing_company'    => 40,
			'billing_address_1'  => 50,
			'billing_city'       => 60,
			'billing_postcode'   => 70,
			'billing_state'      => 80,
			'billing_phone'      => 100,
		);
		foreach ( $billing_priorities as $key => $priority ) {
			if ( isset( $fields['billing'][ $key ] ) && is_array( $fields['billing'][ $key ] ) ) {
				$fields['billing'][ $key ]['priority'] = $priority;
			}
		}
	}

	if ( isset( $fields['shipping'] ) && is_array( $fields['shipping'] ) ) {
		unset( $fields['shipping']['shipping_address_2'] );

		if ( ! isset( $fields['shipping']['shipping_phone'] ) ) {
			$fields['shipping']['shipping_phone'] = array(
				'label'        => __( 'Numero di telefono', 'borgodasso' ),
				'placeholder'  => __( 'Numero di telefono', 'borgodasso' ),
				'required'     => false,
				'type'         => 'tel',
				'class'        => array( 'form-row-wide' ),
				'validate'     => array( 'phone' ),
				'autocomplete' => 'tel',
				'priority'     => 100,
			);
		} else {
			$fields['shipping']['shipping_phone']['label']       = __( 'Numero di telefono', 'borgodasso' );
			$fields['shipping']['shipping_phone']['placeholder'] = __( 'Numero di telefono', 'borgodasso' );
			$fields['shipping']['shipping_phone']['priority']    = 100;
			$fields['shipping']['shipping_phone']['required']    = false;
		}

		// Nome → Cognome → Paese → ….
		$shipping_priorities = array(
			'shipping_first_name' => 10,
			'shipping_last_name'  => 20,
			'shipping_country'    => 30,
			'shipping_company'    => 40,
			'shipping_address_1'  => 50,
			'shipping_city'       => 60,
			'shipping_postcode'   => 70,
			'shipping_state'      => 80,
			'shipping_phone'      => 100,
		);
		foreach ( $shipping_priorities as $key => $priority ) {
			if ( isset( $fields['shipping'][ $key ] ) && is_array( $fields['shipping'][ $key ] ) ) {
				$fields['shipping'][ $key ]['priority'] = $priority;
			}
		}
	}

	if ( isset( $fields['order']['order_comments'] ) ) {
		unset( $fields['order']['order_comments'] );
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'borgodasso_checkout_fields', 20 );

/**
 * Terms of sale URL (Theme Options → footer, else WC terms page).
 */
function borgodasso_checkout_terms_url(): string {
	$footer = function_exists( 'borgodasso_footer_options' ) ? borgodasso_footer_options() : null;
	if ( is_array( $footer ) && ! empty( $footer['column_2']['terms']['url'] ) ) {
		return (string) $footer['column_2']['terms']['url'];
	}

	$terms_id = (int) get_option( 'woocommerce_terms_page_id' );
	if ( $terms_id > 0 ) {
		$url = get_permalink( $terms_id );
		return $url ? (string) $url : '';
	}

	return '';
}

/**
 * Privacy policy URL (Theme Options → footer, else WP privacy page).
 */
function borgodasso_checkout_privacy_url(): string {
	$footer = function_exists( 'borgodasso_footer_options' ) ? borgodasso_footer_options() : null;
	if ( is_array( $footer ) && ! empty( $footer['column_3']['privacy']['url'] ) ) {
		return (string) $footer['column_3']['privacy']['url'];
	}

	$privacy_id = (int) get_option( 'wp_page_for_privacy_policy' );
	if ( $privacy_id > 0 ) {
		$url = get_permalink( $privacy_id );
		return $url ? (string) $url : '';
	}

	return '';
}

/**
 * Require terms + privacy checkboxes.
 */
function borgodasso_checkout_validate_legal(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verifies checkout nonce upstream.
	if ( empty( $_POST['terms'] ) ) {
		wc_add_notice(
			__( 'Devi accettare le Condizioni di vendita di Borgo d’Asso.', 'borgodasso' ),
			'error'
		);
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verifies checkout nonce upstream.
	if ( empty( $_POST['borgodasso_privacy'] ) ) {
		wc_add_notice(
			__( 'Devi accettare la Privacy Policy di Borgo d’Asso.', 'borgodasso' ),
			'error'
		);
	}
}
add_action( 'woocommerce_checkout_process', 'borgodasso_checkout_validate_legal', 20 );

/**
 * Render Contatti fields (email + phone).
 *
 * @param WC_Checkout $checkout Checkout object.
 */
function borgodasso_checkout_render_contact_fields( WC_Checkout $checkout ): void {
	$fields = $checkout->get_checkout_fields( 'billing' );

	foreach ( borgodasso_checkout_contact_field_keys() as $key ) {
		if ( ! isset( $fields[ $key ] ) ) {
			continue;
		}
		woocommerce_form_field( $key, $fields[ $key ], $checkout->get_value( $key ) );
	}
}

/**
 * Whether checkout needs a shipping address / methods.
 */
function borgodasso_checkout_needs_shipping(): bool {
	return (bool) ( WC()->cart && WC()->cart->needs_shipping() && WC()->cart->show_shipping() );
}

/**
 * Render shipping method radios for the left column (AJAX fragment root).
 */
function borgodasso_checkout_render_shipping_methods(): void {
	if ( ! borgodasso_checkout_needs_shipping() ) {
		echo '<p class="checkout-shipping-methods__empty">' . esc_html__( 'Nessuna spedizione richiesta.', 'borgodasso' ) . '</p>';
		return;
	}

	$packages = WC()->shipping()->get_packages();
	$chosen   = WC()->session ? WC()->session->get( 'chosen_shipping_methods' ) : array();

	if ( empty( $packages ) ) {
		echo '<p class="checkout-shipping-methods__empty">' . esc_html__( 'Inserisci l’indirizzo di spedizione per vedere i metodi disponibili.', 'borgodasso' ) . '</p>';
		return;
	}

	foreach ( $packages as $index => $package ) {
		$available = $package['rates'] ?? array();
		$chosen_id = isset( $chosen[ $index ] ) ? (string) $chosen[ $index ] : '';

		if ( empty( $available ) || ! is_array( $available ) ) {
			$destination = WC()->countries->get_formatted_address( $package['destination'] ?? array(), ', ' );
			if ( '' === trim( wp_strip_all_tags( (string) $destination ) ) ) {
				echo '<p class="checkout-shipping-methods__empty">' . esc_html__( 'Inserisci l’indirizzo di spedizione per vedere i metodi disponibili.', 'borgodasso' ) . '</p>';
			} else {
				echo '<p class="checkout-shipping-methods__empty">' . esc_html__( 'Non ci sono metodi di spedizione disponibili per questo indirizzo.', 'borgodasso' ) . '</p>';
			}
			continue;
		}

		echo '<ul class="checkout-shipping-methods__list woocommerce-shipping-methods">';
		foreach ( $available as $method ) {
			if ( ! $method instanceof WC_Shipping_Rate ) {
				continue;
			}
			$input_id = 'shipping_method_' . $index . '_' . sanitize_title( $method->get_id() );
			$checked  = checked( $method->get_id(), $chosen_id, false );
			echo '<li class="checkout-shipping-methods__item">';
			if ( count( $available ) > 1 ) {
				printf(
					'<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="%2$s" value="%3$s" class="shipping_method" %4$s />',
					(int) $index,
					esc_attr( $input_id ),
					esc_attr( $method->get_id() ),
					$checked // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- checked() is safe.
				);
			} else {
				printf(
					'<input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="%2$s" value="%3$s" class="shipping_method" />',
					(int) $index,
					esc_attr( $input_id ),
					esc_attr( $method->get_id() )
				);
			}
			printf(
				'<label for="%1$s">%2$s</label>',
				esc_attr( $input_id ),
				wp_kses_post( wc_cart_totals_shipping_method_label( $method ) )
			);
			do_action( 'woocommerce_after_shipping_rate', $method, $index );
			echo '</li>';
		}
		echo '</ul>';
	}
}

/**
 * Shipping methods HTML for AJAX fragments (includes wrapper).
 */
function borgodasso_checkout_shipping_methods_fragment_html(): string {
	ob_start();
	echo '<div class="checkout-shipping-methods">';
	borgodasso_checkout_render_shipping_methods();
	echo '</div>';
	return (string) ob_get_clean();
}

/**
 * Refresh shipping methods when the checkout address updates.
 *
 * @param array<string, string> $fragments Checkout fragments.
 * @return array<string, string>
 */
function borgodasso_checkout_order_review_fragments( array $fragments ): array {
	$fragments['.checkout-shipping-methods'] = borgodasso_checkout_shipping_methods_fragment_html();
	return $fragments;
}
add_filter( 'woocommerce_update_order_review_fragments', 'borgodasso_checkout_order_review_fragments' );

/**
 * Line items for the order-pay summary “Articoli” list.
 *
 * @param WC_Order $order Order being paid.
 * @return array<int, array{name: string, url: string, image_html: string, price_html: string, meta: string, quantity: int}>
 */
function borgodasso_order_pay_summary_items( WC_Order $order ): array {
	$items = array();

	if ( ! function_exists( 'borgodasso_order_detail_items' ) ) {
		return $items;
	}

	foreach ( borgodasso_order_detail_items( $order ) as $row ) {
		$image_html = '';
		if ( ! empty( $row['image_id'] ) ) {
			$image_html = wp_get_attachment_image(
				(int) $row['image_id'],
				'borgodasso_cart_thumb',
				false,
				array( 'class' => 'checkout-summary__img' )
			);
		}
		if ( '' === $image_html ) {
			$image_html = wc_placeholder_img( 'borgodasso_cart_thumb', array( 'class' => 'checkout-summary__img' ) );
		}

		$meta_lines = isset( $row['meta_lines'] ) && is_array( $row['meta_lines'] ) ? $row['meta_lines'] : array();

		$items[] = array(
			'name'       => (string) $row['name'],
			'url'        => (string) $row['url'],
			'image_html' => $image_html,
			'price_html' => (string) $row['price_html'],
			'meta'       => implode( ', ', array_map( 'strval', $meta_lines ) ),
			'quantity'   => (int) $row['quantity'],
		);
	}

	return $items;
}

/**
 * Line items for the checkout summary “Articoli” list.
 *
 * @return array<int, array{name: string, url: string, image_html: string, price_html: string, format: string, vintage: string, quantity: int}>
 */
function borgodasso_checkout_summary_items(): array {
	$items = array();

	if ( ! WC()->cart ) {
		return $items;
	}

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$product = isset( $cart_item['data'] ) && $cart_item['data'] instanceof WC_Product
			? $cart_item['data']
			: null;

		if ( ! $product || ! $product->exists() || (int) $cart_item['quantity'] <= 0 ) {
			continue;
		}

		$parent_id = ! empty( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : $product->get_id();
		$parent    = wc_get_product( $parent_id );
		$name      = $parent instanceof WC_Product ? $parent->get_name() : $product->get_name();
		$url       = $product->is_visible() ? $product->get_permalink( $cart_item ) : '';
		$meta      = function_exists( 'borgodasso_cart_item_meta_lines' )
			? borgodasso_cart_item_meta_lines( $cart_item )
			: array(
				'format'  => '',
				'vintage' => '',
			);

		$items[] = array(
			'name'       => $name,
			'url'        => $url ? (string) $url : '',
			'image_html' => $product->get_image( 'borgodasso_cart_thumb', array( 'class' => 'checkout-summary__img' ) ),
			// Unit price — quantity is shown on its own line.
			'price_html' => WC()->cart->get_product_price( $product ),
			'format'     => $meta['format'],
			'vintage'    => $meta['vintage'],
			'quantity'   => (int) $cart_item['quantity'],
		);
	}

	return $items;
}

/**
 * Shipping total label for the summary row.
 */
function borgodasso_checkout_summary_shipping_value(): string {
	if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
		return esc_html__( 'Gratuita', 'borgodasso' );
	}

	if ( ! WC()->cart->show_shipping() ) {
		return esc_html__( 'Inserisci l’indirizzo di spedizione', 'borgodasso' );
	}

	$packages = WC()->shipping()->get_packages();
	$chosen   = WC()->session ? WC()->session->get( 'chosen_shipping_methods' ) : array();
	$has_dest = false;

	foreach ( $packages as $package ) {
		$destination = $package['destination'] ?? array();
		$country     = isset( $destination['country'] ) ? trim( (string) $destination['country'] ) : '';
		$city        = isset( $destination['city'] ) ? trim( (string) $destination['city'] ) : '';
		$postcode    = isset( $destination['postcode'] ) ? trim( (string) $destination['postcode'] ) : '';
		if ( '' !== $country || '' !== $city || '' !== $postcode ) {
			$has_dest = true;
			break;
		}
	}

	if ( ! $has_dest ) {
		return esc_html__( 'Inserisci l’indirizzo di spedizione', 'borgodasso' );
	}

	$total = WC()->cart->get_shipping_total();
	$tax   = WC()->cart->get_shipping_tax();

	if ( '' === (string) $total && empty( $chosen ) ) {
		return esc_html__( 'Inserisci l’indirizzo di spedizione', 'borgodasso' );
	}

	if ( WC()->cart->display_prices_including_tax() ) {
		return wc_price( (float) $total + (float) $tax );
	}

	return wc_price( (float) $total );
}

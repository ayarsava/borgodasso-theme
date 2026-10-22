<?php
/**
 * WooCommerce My Account — orders helpers.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce customer email-verification controller, when available.
 *
 * @return object|null
 */
function borgodasso_orders_email_verification_controller() {
	if ( ! function_exists( 'wc_get_container' ) ) {
		return null;
	}

	$class = '\\Automattic\\WooCommerce\\Internal\\CustomerEmailVerification\\VerificationController';

	if ( ! class_exists( $class ) ) {
		return null;
	}

	try {
		return wc_get_container()->get( $class );
	} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		return null;
	}
}

/**
 * Replace WooCommerce’s default email-verify notice with theme markup on Orders.
 */
function borgodasso_orders_replace_email_verification_prompt(): void {
	$controller = borgodasso_orders_email_verification_controller();

	if ( ! $controller || ! method_exists( $controller, 'render_prompt' ) ) {
		return;
	}

	remove_action( 'woocommerce_before_account_orders', array( $controller, 'render_prompt' ) );
}

/**
 * Whether the confirm-email CTA should show on the Orders endpoint.
 */
function borgodasso_orders_should_show_email_verification(): bool {
	$controller = borgodasso_orders_email_verification_controller();

	return $controller
		&& method_exists( $controller, 'should_show_prompt' )
		&& (bool) $controller->should_show_prompt();
}

/**
 * Nonced URL that triggers WooCommerce’s send-verification flow.
 */
function borgodasso_orders_email_verification_url(): string {
	return (string) wp_nonce_url(
		add_query_arg( 'wc_send_verification', '1', wc_get_account_endpoint_url( 'orders' ) ),
		'woocommerce-send-verification-email'
	);
}

/**
 * Register custom order status used by the order-detail stepper (“Inviato”).
 *
 * @see woocommerce/README.md — Order status stepper
 */
function borgodasso_register_order_statuses(): void {
	register_post_status(
		'wc-shipped',
		array(
			'label'                     => _x( 'Inviato', 'Order status', 'borgodasso' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: number of orders. */
			'label_count'               => _n_noop(
				'Inviato <span class="count">(%s)</span>',
				'Inviato <span class="count">(%s)</span>',
				'borgodasso'
			),
		)
	);
}
add_action( 'init', 'borgodasso_register_order_statuses', 20 );

/**
 * Expose “Inviato” (shipped) in WooCommerce status lists / dropdowns.
 *
 * @param array<string, string> $statuses Statuses.
 * @return array<string, string>
 */
function borgodasso_order_statuses( array $statuses ): array {
	$new = array();
	foreach ( $statuses as $key => $label ) {
		$new[ $key ] = $label;
		// Place after processing so the fulfilment flow reads naturally in admin.
		if ( 'wc-processing' === $key ) {
			$new['wc-shipped'] = _x( 'Inviato', 'Order status', 'borgodasso' );
		}
	}

	if ( ! isset( $new['wc-shipped'] ) ) {
		$new['wc-shipped'] = _x( 'Inviato', 'Order status', 'borgodasso' );
	}

	return $new;
}
add_filter( 'wc_order_statuses', 'borgodasso_order_statuses' );

/**
 * Display label for an order status (Ordini list + stepper).
 *
 * @param WC_Order $order Order.
 */
function borgodasso_order_status_label( WC_Order $order ): string {
	$status = $order->get_status();

	$labels = array(
		'processing' => __( 'In preparazione', 'borgodasso' ),
		'shipped'    => __( 'Inviato', 'borgodasso' ),
		'completed'  => __( 'Consegnato', 'borgodasso' ),
	);

	if ( isset( $labels[ $status ] ) ) {
		return $labels[ $status ];
	}

	return wc_get_order_status_name( $status );
}

/**
 * Order-detail fulfilment stepper steps (design mock).
 *
 * @return array<int, array{key: string, label: string}>
 */
function borgodasso_order_stepper_steps(): array {
	return array(
		array(
			'key'   => 'accepted',
			'label' => __( 'Ordine accettato', 'borgodasso' ),
		),
		array(
			'key'   => 'preparing',
			'label' => __( 'In preparazione', 'borgodasso' ),
		),
		array(
			'key'   => 'shipped',
			'label' => __( 'Inviato', 'borgodasso' ),
		),
		array(
			'key'   => 'delivered',
			'label' => __( 'Consegnato', 'borgodasso' ),
		),
	);
}

/**
 * Highest reached stepper index for an order (0-based), or -1 if not started.
 *
 * Mapping (see woocommerce/README.md for gaps):
 * - processing → 1 (fills “Ordine accettato” + “In preparazione”)
 * - shipped    → 2
 * - completed  → 3
 * - pending / on-hold / cancelled / refunded / failed → -1
 *
 * @param WC_Order $order Order.
 */
function borgodasso_order_stepper_index( WC_Order $order ): int {
	$status = $order->get_status();

	$map = array(
		'processing' => 1,
		'shipped'    => 2,
		'completed'  => 3,
	);

	/**
	 * Filter the active stepper step index for an order (-1 = none reached).
	 *
	 * @param int      $index  Step index.
	 * @param WC_Order $order  Order.
	 * @param string   $status Order status slug (without wc-).
	 */
	return (int) apply_filters(
		'borgodasso_order_stepper_index',
		$map[ $status ] ?? -1,
		$order,
		$status
	);
}

/**
 * Whether the fulfilment stepper should render for this order.
 *
 * Hidden for terminal / unpaid statuses that are not part of the fulfilment path.
 *
 * @param WC_Order $order Order.
 */
function borgodasso_order_stepper_should_show( WC_Order $order ): bool {
	$status = $order->get_status();
	$hide   = array( 'cancelled', 'refunded', 'failed', 'checkout-draft' );

	/**
	 * Filter whether the order-detail stepper is shown.
	 *
	 * @param bool     $show   Whether to show.
	 * @param WC_Order $order  Order.
	 * @param string   $status Status slug.
	 */
	return (bool) apply_filters(
		'borgodasso_order_stepper_should_show',
		! in_array( $status, $hide, true ),
		$order,
		$status
	);
}

/**
 * Format a WC date as d/m/Y for the orders list.
 *
 * @param WC_DateTime|null $date Date.
 */
function borgodasso_order_list_date_label( $date ): string {
	if ( ! $date ) {
		return '';
	}

	return $date->date_i18n( 'd/m/Y' );
}

/**
 * Meta line under the status (expected delivery or completed date).
 *
 * Non-completed orders only show “Consegna prevista” when a real estimate
 * exists (meta `_borgodasso_expected_delivery` or the filter below) — never
 * fall back to the order created date.
 *
 * @param WC_Order $order Order.
 */
function borgodasso_order_list_meta( WC_Order $order ): string {
	$status = $order->get_status();

	if ( 'completed' === $status ) {
		$date = $order->get_date_completed() ? $order->get_date_completed() : $order->get_date_created();
		return borgodasso_order_list_date_label( $date );
	}

	$raw = $order->get_meta( '_borgodasso_expected_delivery' );

	/**
	 * Filter the expected-delivery date string (d/m/Y) for non-completed orders.
	 *
	 * @param string   $formatted Formatted date, or empty.
	 * @param WC_Order $order     Order.
	 */
	$formatted = (string) apply_filters( 'borgodasso_order_expected_delivery_label', '', $order );

	if ( '' === $formatted && is_string( $raw ) && '' !== $raw ) {
		$timestamp = strtotime( $raw );
		if ( $timestamp ) {
			$formatted = wp_date( 'd/m/Y', $timestamp );
		} else {
			$formatted = $raw;
		}
	}

	if ( '' === $formatted ) {
		return '';
	}

	return sprintf(
		/* translators: %s: expected delivery date (d/m/Y). */
		__( 'Consegna prevista: %s', 'borgodasso' ),
		$formatted
	);
}

/**
 * Prefer the parent product title for variations (order detail / list cards).
 *
 * @param WC_Order_Item_Product $item Line item.
 */
function borgodasso_order_item_product_name( WC_Order_Item_Product $item ): string {
	$product = $item->get_product();

	if ( $product instanceof WC_Product && $product->is_type( 'variation' ) ) {
		$parent = wc_get_product( $product->get_parent_id() );
		if ( $parent instanceof WC_Product ) {
			return $parent->get_name();
		}
	}

	return $item->get_name();
}

/**
 * Sale-format + variation lines for an order item (excluding name).
 *
 * @param WC_Order_Item_Product $item Line item.
 * @return string[]
 */
function borgodasso_order_item_meta_lines( WC_Order_Item_Product $item ): array {
	$lines   = array();
	$product = $item->get_product();

	if ( $product instanceof WC_Product ) {
		$own = function_exists( 'borgodasso_product_sale_format_label_own' )
			? borgodasso_product_sale_format_label_own( $product )
			: '';

		if ( '' !== $own ) {
			$lines[] = $own;
		} else {
			$parent_id = $product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id();
			$parent    = wc_get_product( $parent_id );
			$format    = function_exists( 'borgodasso_product_sale_format_label' )
				? borgodasso_product_sale_format_label( $parent instanceof WC_Product ? $parent : $product )
				: '';
			if ( '' !== $format ) {
				$lines[] = $format;
			}
		}
	}

	$meta = $item->get_formatted_meta_data( '_', true );
	foreach ( $meta as $meta_item ) {
		$value = isset( $meta_item->display_value )
			? trim( wp_strip_all_tags( (string) $meta_item->display_value ) )
			: '';
		if ( '' === $value ) {
			continue;
		}
		// Avoid duplicating the sale-format string when WC also stores size attrs.
		if ( in_array( $value, $lines, true ) ) {
			continue;
		}
		$lines[] = $value;
	}

	return $lines;
}

/**
 * Line items prepared for the order-detail template.
 *
 * @param WC_Order $order Order.
 * @return array<int, array{name: string, url: string, image_id: int, price_html: string, meta_lines: string[], quantity: int}>
 */
function borgodasso_order_detail_items( WC_Order $order ): array {
	$items = array();

	foreach ( $order->get_items() as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}

		$product  = $item->get_product();
		$name     = borgodasso_order_item_product_name( $item );
		$url      = '';
		$image_id = 0;

		if ( $product instanceof WC_Product ) {
			$image_id  = (int) $product->get_image_id();
			$permalink = $product->get_permalink();
			if ( $permalink ) {
				$url = $permalink;
			}
		}

		if ( ! $image_id && $item->get_product_id() ) {
			$image_id = (int) get_post_thumbnail_id( $item->get_product_id() );
		}

		$items[] = array(
			'name'       => $name,
			'url'        => $url,
			'image_id'   => $image_id,
			'price_html' => $order->get_formatted_line_subtotal( $item ),
			'meta_lines' => borgodasso_order_item_meta_lines( $item ),
			'quantity'   => (int) $item->get_quantity(),
		);
	}

	return $items;
}

/**
 * Drop default WC order-details table — theme renders a custom layout.
 */
function borgodasso_order_detail_remove_default_table(): void {
	remove_action( 'woocommerce_view_order', 'woocommerce_order_details_table', 10 );
	remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
}
add_action( 'wp', 'borgodasso_order_detail_remove_default_table' );

/**
 * Whether the order uses a Stripe gateway.
 *
 * @param WC_Order $order Order.
 */
function borgodasso_order_is_stripe( WC_Order $order ): bool {
	$method = (string) $order->get_payment_method();

	return 'stripe' === $method || 0 === strpos( $method, 'stripe' );
}

/**
 * Stripe PaymentIntent id stored on the order, if any.
 *
 * @param WC_Order $order Order.
 */
function borgodasso_order_stripe_intent_id( WC_Order $order ): string {
	$id = (string) $order->get_meta( '_stripe_intent_id' );
	if ( '' !== $id ) {
		return $id;
	}

	return (string) $order->get_meta( '_payment_intent_id' );
}

/**
 * Whether the current request is a Stripe return after the shopper paid
 * (`pay_for_order=no` + Stripe payment method query args).
 *
 * @param WC_Order $order Order being viewed.
 */
function borgodasso_order_request_is_stripe_payment_return( WC_Order $order ): bool {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Public gateway return query; presence only.
	$pay_for = isset( $_GET['pay_for_order'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['pay_for_order'] ) ) : '';
	$method  = isset( $_GET['wc_payment_method'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['wc_payment_method'] ) ) : '';
	$qid     = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( 'no' !== $pay_for ) {
		return false;
	}

	if ( '' === $method || ( 'stripe' !== $method && 0 !== strpos( $method, 'stripe' ) ) ) {
		return false;
	}

	if ( $qid > 0 && $qid !== $order->get_id() ) {
		return false;
	}

	return true;
}

/**
 * Persist “payment submitted” when Stripe returns the customer before the webhook
 * has flipped the order out of Pending.
 *
 * @param WC_Order $order Order.
 */
function borgodasso_order_maybe_mark_payment_submitted( WC_Order $order ): void {
	if ( ! borgodasso_order_request_is_stripe_payment_return( $order ) ) {
		return;
	}

	if ( 'yes' === $order->get_meta( '_borgodasso_payment_submitted' ) ) {
		return;
	}

	$order->update_meta_data( '_borgodasso_payment_submitted', 'yes' );
	$order->update_meta_data( '_borgodasso_payment_submitted_at', (string) time() );
	$order->save();
}

/**
 * Cached Stripe PaymentIntent status for a pending order (refreshed every 5 minutes).
 *
 * @param WC_Order $order Order.
 * @return string Stripe status slug, or empty when unknown / unavailable.
 */
function borgodasso_order_get_stripe_intent_status( WC_Order $order ): string {
	if ( ! borgodasso_order_is_stripe( $order ) || ! class_exists( 'WC_Stripe_API' ) ) {
		return '';
	}

	$intent_id = borgodasso_order_stripe_intent_id( $order );
	if ( '' === $intent_id ) {
		return '';
	}

	$cached    = (string) $order->get_meta( '_borgodasso_stripe_intent_status' );
	$cached_at = (int) $order->get_meta( '_borgodasso_stripe_intent_status_at' );
	$fresh     = $cached_at > 0 && ( time() - $cached_at ) < ( 5 * MINUTE_IN_SECONDS );

	if ( $fresh && '' !== $cached ) {
		return $cached;
	}

	try {
		$intent = WC_Stripe_API::request( array(), 'payment_intents/' . rawurlencode( $intent_id ), 'GET' );
	} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		return $cached;
	}

	if ( ! is_object( $intent ) || empty( $intent->status ) || ! is_string( $intent->status ) ) {
		return $cached;
	}

	$status = sanitize_key( $intent->status );
	$order->update_meta_data( '_borgodasso_stripe_intent_status', $status );
	$order->update_meta_data( '_borgodasso_stripe_intent_status_at', (string) time() );
	$order->save();

	return $status;
}

/**
 * UI state for unpaid orders: none | pay | processing.
 *
 * Stripe + Pending: prefer “processing” (webhook lag / local without webhooks)
 * unless Stripe clearly says the payment must be retried. Failed WC status → pay.
 *
 * @param WC_Order $order Order.
 * @return string none|pay|processing
 */
function borgodasso_order_payment_ui_state( WC_Order $order ): string {
	if ( ! $order->needs_payment() ) {
		return 'none';
	}

	if ( $order->has_status( 'failed' ) ) {
		return 'pay';
	}

	if ( ! borgodasso_order_is_stripe( $order ) ) {
		return 'pay';
	}

	borgodasso_order_maybe_mark_payment_submitted( $order );

	$intent_status = borgodasso_order_get_stripe_intent_status( $order );

	// Shopper must start / retry payment.
	if ( in_array( $intent_status, array( 'canceled', 'requires_payment_method' ), true ) ) {
		return 'pay';
	}

	// Known success / in-flight on Stripe while WC is still Pending.
	if ( in_array( $intent_status, array( 'succeeded', 'processing', 'requires_capture', 'requires_action' ), true ) ) {
		return 'processing';
	}

	if ( '' !== (string) $order->get_transaction_id() ) {
		return 'processing';
	}

	if ( '' !== (string) $order->get_meta( '_stripe_charge_captured' ) ) {
		return 'processing';
	}

	if ( 'yes' === $order->get_meta( '_borgodasso_payment_submitted' ) ) {
		return 'processing';
	}

	if ( borgodasso_order_request_is_stripe_payment_return( $order ) ) {
		return 'processing';
	}

	// Intent exists but status unknown (API down / keys) — do not ask to pay again.
	if ( '' !== borgodasso_order_stripe_intent_id( $order ) ) {
		return 'processing';
	}

	// Pending Stripe with no intent yet: treat recent checkouts as processing;
	// older abandoned checkouts can still offer Paga ora.
	$created = $order->get_date_created();
	if ( $created && $created->getTimestamp() >= ( time() - DAY_IN_SECONDS ) ) {
		return 'processing';
	}

	return 'pay';
}

/**
 * Whether payment looks submitted / in flight while WC may still be Pending.
 *
 * @param WC_Order $order Order.
 */
function borgodasso_order_payment_is_awaiting_confirmation( WC_Order $order ): bool {
	return 'processing' === borgodasso_order_payment_ui_state( $order );
}

/**
 * Whether to show the “Paga ora” action on account / order-detail.
 *
 * @param WC_Order $order Order.
 */
function borgodasso_order_should_show_pay_action( WC_Order $order ): bool {
	return 'pay' === borgodasso_order_payment_ui_state( $order );
}

/**
 * Customer-facing status copy while payment is processing / awaiting WC update.
 *
 * @param WC_Order $order   Order.
 * @param string   $context `list` (orders cards) or `detail` (view-order).
 */
function borgodasso_order_payment_status_label( WC_Order $order, string $context = 'list' ): string {
	$status = borgodasso_order_get_stripe_intent_status( $order );

	switch ( $status ) {
		case 'succeeded':
			return 'detail' === $context
				? __( 'Pagamento ricevuto. Stiamo aggiornando l’ordine. Riceverai una email a breve.', 'borgodasso' )
				: __( 'Pagamento ricevuto. Stiamo aggiornando l’ordine…', 'borgodasso' );
		case 'requires_capture':
			return __( 'Pagamento autorizzato. In attesa di conferma…', 'borgodasso' );
		case 'processing':
		case 'requires_action':
			return __( 'Pagamento in elaborazione…', 'borgodasso' );
		default:
			return __( 'Pagamento in elaborazione…', 'borgodasso' );
	}
}

/**
 * Clear temporary payment-tracking meta once WC leaves Pending/Failed.
 *
 * @param int $order_id Order id.
 */
function borgodasso_order_clear_payment_submitted_meta( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
		return;
	}

	$dirty = false;
	foreach ( array( '_borgodasso_payment_submitted', '_borgodasso_payment_submitted_at', '_borgodasso_stripe_intent_status', '_borgodasso_stripe_intent_status_at' ) as $key ) {
		if ( '' !== $order->get_meta( $key ) ) {
			$order->delete_meta_data( $key );
			$dirty = true;
		}
	}

	if ( $dirty ) {
		$order->save();
	}
}
add_action( 'woocommerce_order_status_changed', 'borgodasso_order_clear_payment_submitted_meta', 20 );

/**
 * Product image slides for an order (one per line item).
 *
 * @param WC_Order $order Order.
 * @return array<int, array{name: string, url: string, image_id: int}>
 */
function borgodasso_order_product_slides( WC_Order $order ): array {
	$slides = array();

	foreach ( $order->get_items() as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}

		$product  = $item->get_product();
		$name     = $item->get_name();
		$url      = $order->get_view_order_url();
		$image_id = 0;

		if ( $product instanceof WC_Product ) {
			$image_id  = (int) $product->get_image_id();
			$permalink = $product->get_permalink();
			if ( $permalink ) {
				$url = $permalink;
			}
		}

		if ( ! $image_id && $item->get_product_id() ) {
			$image_id = (int) get_post_thumbnail_id( $item->get_product_id() );
		}

		$slides[] = array(
			'name'     => $name,
			'url'      => $url,
			'image_id' => $image_id,
		);
	}

	return $slides;
}

/**
 * Credit-card brand icon URL (Stripe plugin assets, then WooCommerce).
 *
 * @param string $brand Brand slug (visa, mastercard, …).
 */
function borgodasso_card_brand_icon_url( string $brand ): string {
	$brand = strtolower( preg_replace( '/[^a-z0-9]+/', '', $brand ) ?? '' );
	if ( '' === $brand ) {
		$brand = 'credit-card';
	}

	$map  = array(
		'americanexpress' => 'amex',
		'amex'            => 'amex',
		'visa'            => 'visa',
		'mastercard'      => 'mastercard',
		'mc'              => 'mastercard',
		'discover'        => 'discover',
		'diners'          => 'diners',
		'dinersclub'      => 'diners',
		'jcb'             => 'jcb',
		'maestro'         => 'maestro',
	);
	$slug = $map[ $brand ] ?? $brand;

	$candidates = array();
	if ( defined( 'WC_STRIPE_PLUGIN_PATH' ) && defined( 'WC_STRIPE_PLUGIN_URL' ) ) {
		$base = WC_STRIPE_PLUGIN_PATH . '/assets/images/';
		$url  = WC_STRIPE_PLUGIN_URL . '/assets/images/';
		foreach ( array( $slug . '.svg', $slug . '.png', 'credit-card.svg' ) as $file ) {
			if ( is_readable( $base . $file ) ) {
				$candidates[] = $url . $file;
				break;
			}
		}
	}

	if ( array() === $candidates && defined( 'WC_PLUGIN_FILE' ) ) {
		$base = plugin_dir_path( WC_PLUGIN_FILE ) . 'assets/images/icons/credit-cards/';
		$url  = plugins_url( 'assets/images/icons/credit-cards/', WC_PLUGIN_FILE );
		foreach ( array( $slug . '.svg', $slug . '.png', 'visa.svg' ) as $file ) {
			if ( is_readable( $base . $file ) ) {
				$candidates[] = $url . $file;
				break;
			}
		}
	}

	return $candidates[0] ?? '';
}

/**
 * Persist Stripe card display meta on the order when missing.
 *
 * @param WC_Order $order Order.
 */
function borgodasso_order_hydrate_card_payment_meta( WC_Order $order ): void {
	if ( '' !== (string) $order->get_meta( '_borgodasso_card_last4' ) ) {
		return;
	}

	$method = $order->get_payment_method();
	if ( ! in_array( $method, array( 'stripe', 'stripe_cc' ), true ) && 0 !== strpos( $method, 'stripe_' ) ) {
		return;
	}

	if ( ! class_exists( 'WC_Stripe_API' ) ) {
		return;
	}

	$brand  = '';
	$last4  = '';
	$holder = '';

	$session_id = (string) $order->get_meta( '_stripe_checkout_session_id' );
	$intent_id  = (string) $order->get_meta( '_stripe_intent_id' );

	try {
		if ( '' === $intent_id && '' !== $session_id ) {
			$session = WC_Stripe_API::request( array(), 'checkout/sessions/' . $session_id, 'GET' );
			if ( is_object( $session ) && ! empty( $session->payment_intent ) && is_string( $session->payment_intent ) ) {
				$intent_id = $session->payment_intent;
			}
		}

		if ( '' !== $intent_id ) {
			$intent = WC_Stripe_API::request(
				array(),
				'payment_intents/' . rawurlencode( $intent_id ) . '?expand[]=payment_method',
				'GET'
			);
			$pm     = is_object( $intent ) ? ( $intent->payment_method ?? null ) : null;
			if ( is_string( $pm ) && '' !== $pm ) {
				$pm = WC_Stripe_API::request( array(), 'payment_methods/' . rawurlencode( $pm ), 'GET' );
			}
			if ( is_object( $pm ) ) {
				if ( ! empty( $pm->card->brand ) ) {
					$brand = (string) $pm->card->brand;
				}
				if ( ! empty( $pm->card->last4 ) ) {
					$last4 = (string) $pm->card->last4;
				}
				if ( ! empty( $pm->billing_details->name ) ) {
					$holder = (string) $pm->billing_details->name;
				}
			}
		}
	} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		return;
	}

	if ( '' === $brand ) {
		$brand = (string) $order->get_meta( '_stripe_card_brand' );
	}

	if ( '' === $last4 && '' === $brand ) {
		return;
	}

	if ( '' !== $brand ) {
		$order->update_meta_data( '_borgodasso_card_brand', $brand );
	}
	if ( '' !== $last4 ) {
		$order->update_meta_data( '_borgodasso_card_last4', $last4 );
	}
	if ( '' !== $holder ) {
		$order->update_meta_data( '_borgodasso_card_holder', $holder );
	}
	$order->save();
}
add_action(
	'woocommerce_payment_complete',
	static function ( $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( $order instanceof WC_Order ) {
			borgodasso_order_hydrate_card_payment_meta( $order );
		}
	},
	20
);

/**
 * Payment display data for order-detail (card logo + holder + masked number).
 *
 * @param WC_Order $order Order.
 * @return array{title: string, brand: string, last4: string, holder: string, icon_url: string, is_card: bool}
 */
function borgodasso_order_payment_display( WC_Order $order ): array {
	borgodasso_order_hydrate_card_payment_meta( $order );

	$brand  = (string) $order->get_meta( '_borgodasso_card_brand' );
	$last4  = (string) $order->get_meta( '_borgodasso_card_last4' );
	$holder = (string) $order->get_meta( '_borgodasso_card_holder' );

	if ( '' === $brand ) {
		$brand = (string) $order->get_meta( '_stripe_card_brand' );
	}

	if ( '' === $holder ) {
		$holder = trim( $order->get_formatted_billing_full_name() );
	}

	$is_card = '' !== $last4 || '' !== $brand;

	return array(
		'title'    => (string) $order->get_payment_method_title(),
		'brand'    => $brand,
		'last4'    => $last4,
		'holder'   => $holder,
		'icon_url' => $is_card ? borgodasso_card_brand_icon_url( '' !== $brand ? $brand : 'credit-card' ) : '',
		'is_card'  => $is_card,
	);
}

/**
 * Known carrier → tracking URL templates (`{code}` placeholder).
 *
 * @return array<string, string> Lowercase carrier key => URL template.
 */
function borgodasso_tracking_carrier_url_templates(): array {
	/**
	 * Filter carrier tracking URL templates.
	 *
	 * @param array<string, string> $templates Carrier key => URL with `{code}`.
	 */
	return (array) apply_filters(
		'borgodasso_tracking_carrier_url_templates',
		array(
			'brt'            => 'https://www.brt.it/it/tracking?id={code}',
			'gls'            => 'https://www.gls-italy.com/?option=track&tracking_number_list={code}',
			'sda'            => 'https://www.poste.it/cerca/index.html#/risultati-spedizioni/{code}',
			'poste'          => 'https://www.poste.it/cerca/index.html#/risultati-spedizioni/{code}',
			'poste italiane' => 'https://www.poste.it/cerca/index.html#/risultati-spedizioni/{code}',
			'dhl'            => 'https://www.dhl.com/it-it/home/tracking.html?tracking-id={code}',
			'ups'            => 'https://www.ups.com/track?tracknum={code}',
			'fedex'          => 'https://www.fedex.com/fedextrack/?trknbr={code}',
		)
	);
}

/**
 * Tracking data for an order.
 *
 * @param WC_Order $order Order.
 * @return array{carrier: string, code: string, url: string}
 */
function borgodasso_order_tracking( WC_Order $order ): array {
	$carrier = trim( (string) $order->get_meta( '_borgodasso_tracking_carrier' ) );
	$code    = trim( (string) $order->get_meta( '_borgodasso_tracking_code' ) );
	$url     = trim( (string) $order->get_meta( '_borgodasso_tracking_url' ) );

	if ( '' === $url && '' !== $code ) {
		$key       = strtolower( $carrier );
		$templates = borgodasso_tracking_carrier_url_templates();
		if ( isset( $templates[ $key ] ) && is_string( $templates[ $key ] ) ) {
			$url = str_replace( '{code}', rawurlencode( $code ), $templates[ $key ] );
		}
	}

	return array(
		'carrier' => $carrier,
		'code'    => $code,
		'url'     => esc_url_raw( $url ),
	);
}

/**
 * Whether the order has a tracking code to show the buyer.
 *
 * @param WC_Order $order Order.
 */
function borgodasso_order_has_tracking( WC_Order $order ): bool {
	$tracking = borgodasso_order_tracking( $order );

	return '' !== $tracking['code'];
}

/**
 * Admin: tracking fields under the shipping address.
 *
 * @param WC_Order $order Order.
 */
function borgodasso_admin_order_tracking_fields( $order ): void {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$carrier = (string) $order->get_meta( '_borgodasso_tracking_carrier' );
	$code    = (string) $order->get_meta( '_borgodasso_tracking_code' );
	$url     = (string) $order->get_meta( '_borgodasso_tracking_url' );
	?>
	<div class="order_data_column" style="clear:both; width:100%; padding-top:1em;">
		<h3><?php echo esc_html__( 'Tracking spedizione', 'borgodasso' ); ?></h3>
		<p class="form-field form-field-wide">
			<label for="borgodasso_tracking_carrier"><?php echo esc_html__( 'Corriere', 'borgodasso' ); ?></label>
			<input
				type="text"
				class="short"
				name="borgodasso_tracking_carrier"
				id="borgodasso_tracking_carrier"
				value="<?php echo esc_attr( $carrier ); ?>"
				placeholder="<?php echo esc_attr__( 'es. BRT, GLS, SDA…', 'borgodasso' ); ?>"
			/>
		</p>
		<p class="form-field form-field-wide">
			<label for="borgodasso_tracking_code"><?php echo esc_html__( 'Codice tracking', 'borgodasso' ); ?></label>
			<input
				type="text"
				class="short"
				name="borgodasso_tracking_code"
				id="borgodasso_tracking_code"
				value="<?php echo esc_attr( $code ); ?>"
			/>
		</p>
		<p class="form-field form-field-wide">
			<label for="borgodasso_tracking_url"><?php echo esc_html__( 'URL tracking (opzionale)', 'borgodasso' ); ?></label>
			<input
				type="url"
				class="short"
				name="borgodasso_tracking_url"
				id="borgodasso_tracking_url"
				value="<?php echo esc_attr( $url ); ?>"
				placeholder="<?php echo esc_attr__( 'Lascia vuoto per usare il link del corriere noto', 'borgodasso' ); ?>"
			/>
		</p>
	</div>
	<?php
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'borgodasso_admin_order_tracking_fields' );

/**
 * Save tracking fields from the order edit screen.
 *
 * @param int $order_id Order id.
 */
function borgodasso_save_admin_order_tracking( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WC order save verifies nonce.
	$carrier = isset( $_POST['borgodasso_tracking_carrier'] )
		? sanitize_text_field( wp_unslash( (string) $_POST['borgodasso_tracking_carrier'] ) )
		: '';
	$code    = isset( $_POST['borgodasso_tracking_code'] )
		? sanitize_text_field( wp_unslash( (string) $_POST['borgodasso_tracking_code'] ) )
		: '';
	$url     = isset( $_POST['borgodasso_tracking_url'] )
		? esc_url_raw( wp_unslash( (string) $_POST['borgodasso_tracking_url'] ) )
		: '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	$previous = (string) $order->get_meta( '_borgodasso_tracking_code' );

	$order->update_meta_data( '_borgodasso_tracking_carrier', $carrier );
	$order->update_meta_data( '_borgodasso_tracking_code', $code );
	$order->update_meta_data( '_borgodasso_tracking_url', $url );
	$order->save();

	// New tracking on an already-shipped / completed order → notify the buyer.
	if ( '' !== $code && $code !== $previous && $order->has_status( array( 'shipped', 'completed' ) ) ) {
		$order->delete_meta_data( '_borgodasso_tracking_email_sent' );
		$order->save();
		borgodasso_email_customer_tracking( $order );
	}
}
add_action( 'woocommerce_process_shop_order_meta', 'borgodasso_save_admin_order_tracking', 60 );

/**
 * When an order becomes Inviato, email tracking if present.
 *
 * @param int $order_id Order id.
 */
function borgodasso_email_tracking_on_shipped( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( $order instanceof WC_Order ) {
		borgodasso_email_customer_tracking( $order );
	}
}
add_action( 'woocommerce_order_status_shipped', 'borgodasso_email_tracking_on_shipped', 20 );

/**
 * Send a simple “ordine inviato + tracking” email to the customer (once per code).
 *
 * @param WC_Order $order Order.
 */
function borgodasso_email_customer_tracking( WC_Order $order ): void {
	if ( ! borgodasso_order_has_tracking( $order ) || ! function_exists( 'WC' ) || ! WC()->mailer() ) {
		return;
	}

	$code = borgodasso_order_tracking( $order )['code'];
	$sent = (string) $order->get_meta( '_borgodasso_tracking_email_sent' );
	if ( $sent === $code ) {
		return;
	}

	$to = $order->get_billing_email();
	if ( '' === $to || ! is_email( $to ) ) {
		return;
	}

	$tracking = borgodasso_order_tracking( $order );
	$mailer   = WC()->mailer();

	/* translators: %s: order number. */
	$heading = sprintf( __( 'Il tuo ordine n° %s è stato inviato', 'borgodasso' ), $order->get_order_number() );
	/* translators: %s: order number. */
	$subject = sprintf( __( 'Ordine n° %s inviato — tracking', 'borgodasso' ), $order->get_order_number() );

	$lines = array();
	if ( '' !== $tracking['carrier'] ) {
		$lines[] = '<p><strong>' . esc_html__( 'Corriere', 'borgodasso' ) . ':</strong> ' . esc_html( $tracking['carrier'] ) . '</p>';
	}
	$lines[] = '<p><strong>' . esc_html__( 'Codice tracking', 'borgodasso' ) . ':</strong> ' . esc_html( $tracking['code'] ) . '</p>';
	if ( '' !== $tracking['url'] ) {
		$lines[] = '<p><a href="' . esc_url( $tracking['url'] ) . '">' . esc_html__( 'Segui la spedizione', 'borgodasso' ) . '</a></p>';
	}
	$lines[] = '<p><a href="' . esc_url( $order->get_view_order_url() ) . '">' . esc_html__( 'Vedi ordine', 'borgodasso' ) . '</a></p>';

	$message = $mailer->wrap_message( $heading, implode( '', $lines ) );
	$sent_ok = $mailer->send( $to, $subject, $message );

	if ( $sent_ok ) {
		$order->update_meta_data( '_borgodasso_tracking_email_sent', $code );
		$order->save();
	}
}

/**
 * Expose tracking in WooCommerce customer order emails (meta table).
 *
 * @param array<string, array{label: string, value: string}> $fields        Fields.
 * @param bool                                               $sent_to_admin To admin.
 * @param WC_Order                                           $order         Order.
 * @return array<string, array{label: string, value: string}>
 */
function borgodasso_email_order_tracking_meta_fields( array $fields, bool $sent_to_admin, $order ): array {
	if ( $sent_to_admin || ! $order instanceof WC_Order || ! borgodasso_order_has_tracking( $order ) ) {
		return $fields;
	}

	$tracking = borgodasso_order_tracking( $order );

	if ( '' !== $tracking['carrier'] ) {
		$fields['borgodasso_tracking_carrier'] = array(
			'label' => __( 'Corriere', 'borgodasso' ),
			'value' => $tracking['carrier'],
		);
	}

	$value = $tracking['code'];
	if ( '' !== $tracking['url'] ) {
		$value = '<a href="' . esc_url( $tracking['url'] ) . '">' . esc_html( $tracking['code'] ) . '</a>';
	}

	$fields['borgodasso_tracking_code'] = array(
		'label' => __( 'Tracking', 'borgodasso' ),
		'value' => $value,
	);

	return $fields;
}
add_filter( 'woocommerce_email_order_meta_fields', 'borgodasso_email_order_tracking_meta_fields', 10, 3 );

<?php
/**
 * View Order — My Account order detail.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 10.6.0
 *
 * @var WC_Order $order
 * @var int      $order_id
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $order ) || ! $order instanceof WC_Order ) {
	return;
}

$notes = $order->get_customer_order_notes();

borgodasso_template(
	'components/order-detail',
	array(
		'order'      => $order,
		'notes'      => $notes,
		'show_title' => true,
	)
);

/**
 * Plugins listening on view-order (default details table is removed in orders.php).
 *
 * @since 2.6.0
 */
do_action( 'woocommerce_view_order', $order_id );

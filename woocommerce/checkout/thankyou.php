<?php
/**
 * Thankyou / order-received page.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 11.2.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order order-received">

	<?php if ( $order ) : ?>

		<?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<?php
			$default_order_failed_text = esc_html__( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' );
			$order_failed_text         = apply_filters( 'woocommerce_thankyou_order_failed_text', $default_order_failed_text, $order );
			$order_failed_text         = is_string( $order_failed_text ) ? $order_failed_text : $default_order_failed_text;
			?>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php echo wp_kses_post( $order_failed_text ); ?></p>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
				<?php endif; ?>
			</p>

		<?php else : ?>

			<?php
			borgodasso_template(
				'components/order-detail',
				array(
					'order'      => $order,
					'notes'      => array(),
					'show_title' => true,
				)
			);
			?>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

	<?php else : ?>

		<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>

	<?php endif; ?>

</div>

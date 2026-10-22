<?php
/**
 * Pay for order — layout aligned with classic checkout.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 10.9.0
 */

defined( 'ABSPATH' ) || exit;

$totals = $order->get_order_item_totals(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$items  = function_exists( 'borgodasso_order_pay_summary_items' )
	? borgodasso_order_pay_summary_items( $order )
	: array();

$qty_count = 0;
foreach ( $items as $row ) {
	$qty_count += max( 1, (int) $row['quantity'] );
}

$order_number = $order->get_order_number();
?>
<form id="order_review" class="checkout-pay" method="post">

	<div class="checkout-layout">
		<div class="checkout-layout__primary">
			<section class="checkout-section checkout-section--payment" aria-labelledby="checkout-pay-title">
				<div class="checkout-section__heading">
					<h2 id="checkout-pay-title" class="checkout-section__title">
						<?php echo esc_html__( 'Pagamento', 'borgodasso' ); ?>
					</h2>
					<?php if ( '' !== (string) $order_number ) : ?>
						<span class="checkout-section__action checkout-section__action--static">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: order number */
									__( 'Ordine n° %s', 'borgodasso' ),
									(string) $order_number
								)
							);
							?>
						</span>
					<?php endif; ?>
				</div>

				<?php
				/**
				 * Triggered from within the checkout/form-pay.php template, immediately before the payment section.
				 *
				 * @since 8.2.0
				 */
				do_action( 'woocommerce_pay_order_before_payment' );
				?>

				<div id="payment" class="woocommerce-checkout-payment">
					<?php if ( $order->needs_payment() ) : ?>
						<ul class="wc_payment_methods payment_methods methods" aria-label="<?php esc_attr_e( 'Payment methods', 'woocommerce' ); ?>">
							<?php
							if ( ! empty( $available_gateways ) ) {
								foreach ( $available_gateways as $gateway ) {
									wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
								}
							} else {
								echo '<li class="checkout-payment-empty">';
								wc_print_notice(
									apply_filters(
										'woocommerce_no_available_payment_methods_message',
										esc_html__( 'Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' )
									),
									'notice'
								);
								echo '</li>';
							}
							?>
						</ul>
					<?php endif; ?>
				</div>
			</section>

			<section class="checkout-section checkout-section--legal" aria-label="<?php echo esc_attr__( 'Condizioni', 'borgodasso' ); ?>">
				<?php wc_get_template( 'checkout/terms.php' ); ?>
			</section>

			<p class="checkout-secure">
				<?php echo esc_html__( 'Tutte le transazioni sono sicure e crittografate.', 'borgodasso' ); ?>
			</p>

			<div class="checkout-pay__submit form-row">
				<input type="hidden" name="woocommerce_pay" value="1" />

				<?php do_action( 'woocommerce_pay_order_before_submit' ); ?>

				<?php
				echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC filter returns button HTML.
					'woocommerce_pay_order_button_html',
					'<button type="submit" class="button alt checkout-place-order' . esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ) . '" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>'
				);
				?>

				<?php do_action( 'woocommerce_pay_order_after_submit' ); ?>

				<?php wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' ); ?>
			</div>
		</div>

		<aside class="checkout-layout__summary" aria-labelledby="checkout-pay-summary-title">
			<div class="checkout-summary">
				<h2 id="checkout-pay-summary-title" class="checkout-summary__title">
					<?php echo esc_html__( 'Riepilogo', 'borgodasso' ); ?>
				</h2>

				<div class="checkout-summary__totals">
					<?php if ( $totals ) : ?>
						<?php foreach ( $totals as $key => $total ) : ?>
							<?php
							$label     = isset( $total['label'] ) ? wp_strip_all_tags( (string) $total['label'] ) : '';
							$value     = isset( $total['value'] ) ? (string) $total['value'] : '';
							$row_class = 'checkout-summary__row';

							if ( 'cart_subtotal' === $key || 'order_subtotal' === $key ) {
								$row_class .= ' checkout-summary__row--subtotal';
								$label      = sprintf(
									/* translators: %d: total quantity of articles. */
									_n( 'Subtotale · %d articolo', 'Subtotale · %d articoli', $qty_count, 'borgodasso' ),
									$qty_count
								);
							} elseif ( 'shipping' === $key ) {
								$row_class .= ' checkout-summary__row--shipping';
								$label      = __( 'Spedizione', 'borgodasso' );
							} elseif ( 'order_total' === $key || 'payment_method' === $key ) {
								if ( 'payment_method' === $key ) {
									continue;
								}
								$row_class .= ' checkout-summary__row--total order-total';
								$label      = __( 'Totale', 'borgodasso' );
							}
							?>
							<div class="<?php echo esc_attr( $row_class ); ?>">
								<span class="checkout-summary__label"><?php echo esc_html( $label ); ?></span>
								<span class="checkout-summary__value"><?php echo wp_kses_post( $value ); ?></span>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $items ) ) : ?>
					<div class="checkout-summary__articles">
						<h3 class="checkout-summary__articles-title"><?php echo esc_html__( 'Articoli', 'borgodasso' ); ?></h3>
						<ul class="checkout-summary__list">
							<?php foreach ( $items as $row ) : ?>
								<li class="checkout-summary__item">
									<div class="checkout-summary__thumb">
										<?php if ( '' !== $row['url'] ) : ?>
											<a href="<?php echo esc_url( $row['url'] ); ?>" class="checkout-summary__thumb-link">
												<?php echo $row['image_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Product image HTML. ?>
											</a>
										<?php else : ?>
											<?php echo $row['image_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Product image HTML. ?>
										<?php endif; ?>
									</div>
									<div class="checkout-summary__details">
										<?php if ( '' !== $row['url'] ) : ?>
											<a class="checkout-summary__name" href="<?php echo esc_url( $row['url'] ); ?>"><?php echo esc_html( $row['name'] ); ?></a>
										<?php else : ?>
											<span class="checkout-summary__name"><?php echo esc_html( $row['name'] ); ?></span>
										<?php endif; ?>

										<div class="checkout-summary__price"><?php echo wp_kses_post( $row['price_html'] ); ?></div>

										<?php if ( '' !== $row['meta'] ) : ?>
											<div class="checkout-summary__meta"><?php echo esc_html( $row['meta'] ); ?></div>
										<?php endif; ?>

										<div class="checkout-summary__qty">
											<?php
											echo esc_html(
												sprintf(
													/* translators: %d: product quantity. */
													__( 'Quantità: %d', 'borgodasso' ),
													$row['quantity']
												)
											);
											?>
										</div>
									</div>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		</aside>
	</div>
</form>

<?php
/**
 * Checkout order summary — totals + articoli (right column).
 *
 * Root class `woocommerce-checkout-review-order-table` is required for WC AJAX fragments.
 *
 * @package Borgodasso
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

$qty_count = WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
$items     = function_exists( 'borgodasso_checkout_summary_items' ) ? borgodasso_checkout_summary_items() : array();
$shipping  = function_exists( 'borgodasso_checkout_summary_shipping_value' )
	? borgodasso_checkout_summary_shipping_value()
	: '';
?>
<div class="checkout-summary woocommerce-checkout-review-order-table">
	<div class="checkout-summary__totals">
		<div class="checkout-summary__row checkout-summary__row--subtotal">
			<span class="checkout-summary__label">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: total quantity of articles. */
						_n( 'Subtotale · %d articolo', 'Subtotale · %d articoli', $qty_count, 'borgodasso' ),
						$qty_count
					)
				);
				?>
			</span>
			<span class="checkout-summary__value"><?php wc_cart_totals_subtotal_html(); ?></span>
		</div>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<div class="checkout-summary__row cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<span class="checkout-summary__label"><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
				<span class="checkout-summary__value"><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
			</div>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<div class="checkout-summary__row checkout-summary__row--shipping">
				<span class="checkout-summary__label"><?php echo esc_html__( 'Spedizione', 'borgodasso' ); ?></span>
				<span class="checkout-summary__value checkout-summary__value--shipping"><?php echo wp_kses_post( $shipping ); ?></span>
			</div>
		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<div class="checkout-summary__row fee">
				<span class="checkout-summary__label"><?php echo esc_html( $fee->name ); ?></span>
				<span class="checkout-summary__value"><?php wc_cart_totals_fee_html( $fee ); ?></span>
			</div>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
					<div class="checkout-summary__row tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<span class="checkout-summary__label"><?php echo esc_html( $tax->label ); ?></span>
						<span class="checkout-summary__value"><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="checkout-summary__row tax-total">
					<span class="checkout-summary__label"><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></span>
					<span class="checkout-summary__value"><?php wc_cart_totals_taxes_total_html(); ?></span>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<div class="checkout-summary__row checkout-summary__row--total order-total">
			<span class="checkout-summary__label"><?php echo esc_html__( 'Totale', 'borgodasso' ); ?></span>
			<span class="checkout-summary__value"><?php wc_cart_totals_order_total_html(); ?></span>
		</div>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
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
									<?php echo $row['image_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC product image. ?>
								</a>
							<?php else : ?>
								<?php echo $row['image_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC product image. ?>
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

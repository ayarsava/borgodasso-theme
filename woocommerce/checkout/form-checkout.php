<?php
/**
 * Checkout Form — two-column layout (form + summary).
 *
 * @package Borgodasso
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

$show_shipping = function_exists( 'borgodasso_checkout_needs_shipping' )
	? borgodasso_checkout_needs_shipping()
	: ( WC()->cart && WC()->cart->needs_shipping() );
?>

<div class="checkout-layout">
	<div class="checkout-layout__primary">
		<form
			name="checkout"
			method="post"
			class="checkout woocommerce-checkout checkout-form"
			action="<?php echo esc_url( wc_get_checkout_url() ); ?>"
			enctype="multipart/form-data"
			aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>"
		>
			<?php if ( $checkout->get_checkout_fields() ) : ?>

				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

				<section class="checkout-section checkout-section--contact" aria-labelledby="checkout-contact-title">
					<div class="checkout-section__heading">
						<h2 id="checkout-contact-title" class="checkout-section__title">
							<?php echo esc_html__( 'Contatti', 'borgodasso' ); ?>
						</h2>
						<?php if ( ! is_user_logged_in() ) : ?>
							<?php
							$checkout_login_url = function_exists( 'borgodasso_account_login_url' )
								? borgodasso_account_login_url(
									function_exists( 'borgodasso_checkout_url' ) ? borgodasso_checkout_url() : wc_get_checkout_url()
								)
								: wc_get_page_permalink( 'myaccount' );
							?>
							<a class="checkout-section__action" href="<?php echo esc_url( $checkout_login_url ); ?>">
								<?php echo esc_html__( 'Accedi', 'borgodasso' ); ?>
							</a>
						<?php endif; ?>
					</div>
					<div class="checkout-section__fields">
						<?php borgodasso_checkout_render_contact_fields( $checkout ); ?>
					</div>
				</section>

				<?php if ( $show_shipping ) : ?>
					<section class="checkout-section checkout-section--shipping" aria-labelledby="checkout-shipping-title">
						<h2 id="checkout-shipping-title" class="checkout-section__title">
							<?php echo esc_html__( 'Indirizzo di spedizione', 'borgodasso' ); ?>
						</h2>
						<div class="checkout-section__fields">
							<?php do_action( 'woocommerce_checkout_shipping' ); ?>
						</div>
					</section>

					<section class="checkout-section checkout-section--shipping-method" aria-labelledby="checkout-shipping-method-title">
						<h2 id="checkout-shipping-method-title" class="checkout-section__title">
							<?php echo esc_html__( 'Metodo di spedizione', 'borgodasso' ); ?>
						</h2>
						<div class="checkout-shipping-methods">
							<?php borgodasso_checkout_render_shipping_methods(); ?>
						</div>
					</section>
				<?php endif; ?>

				<section class="checkout-section checkout-section--payment-method" aria-labelledby="checkout-payment-method-title">
					<h2 id="checkout-payment-method-title" class="checkout-section__title">
						<?php echo esc_html__( 'Metodo di pagamento', 'borgodasso' ); ?>
					</h2>
					<?php woocommerce_checkout_payment(); ?>
				</section>

				<section class="checkout-section checkout-section--billing" aria-labelledby="checkout-billing-title">
					<h2 id="checkout-billing-title" class="checkout-section__title">
						<?php echo esc_html__( 'Indirizzo di fatturazione', 'borgodasso' ); ?>
					</h2>
					<div class="checkout-section__fields">
						<?php do_action( 'woocommerce_checkout_billing' ); ?>
					</div>
				</section>

				<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

			<?php endif; ?>

			<section class="checkout-section checkout-section--legal" aria-label="<?php echo esc_attr__( 'Condizioni', 'borgodasso' ); ?>">
				<?php wc_get_template( 'checkout/terms.php' ); ?>
			</section>

			<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
				<section class="checkout-section checkout-section--remember" aria-labelledby="checkout-remember-title">
					<h2 id="checkout-remember-title" class="checkout-section__title">
						<?php echo esc_html__( 'Ricordami', 'borgodasso' ); ?>
					</h2>
					<div class="checkout-remember-panel">
						<?php if ( ! $checkout->is_registration_required() ) : ?>
							<p class="form-row form-row-wide create-account checkout-remember">
								<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
									<input
										class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
										id="createaccount"
										<?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?>
										type="checkbox"
										name="createaccount"
										value="1"
									/>
									<span><?php echo esc_html__( 'Salva i miei dati per un check-out più rapido', 'borgodasso' ); ?></span>
								</label>
							</p>
						<?php endif; ?>

						<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

						<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>
							<div class="create-account checkout-remember__fields">
								<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
									<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
					</div>
				</section>
			<?php endif; ?>

			<p class="checkout-secure">
				<?php echo esc_html__( 'Tutte le transazioni sono sicure e crittografate.', 'borgodasso' ); ?>
			</p>

			<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

			<?php
			$order_button_text = apply_filters( 'woocommerce_order_button_text', __( 'Paga ora', 'borgodasso' ) );
			echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC filter returns button HTML.
				'woocommerce_order_button_html',
				'<button type="submit" class="button alt checkout-place-order' . esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ) . '" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>'
			);
			?>

			<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

			<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
		</form>
	</div>

	<aside class="checkout-layout__summary" aria-labelledby="checkout-summary-title">
		<h2 id="checkout-summary-title" class="checkout-summary__title">
			<?php echo esc_html__( 'Pagamento', 'borgodasso' ); ?>
		</h2>

		<?php woocommerce_checkout_coupon_form(); ?>

		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php woocommerce_order_review(); ?>
		</div>
	</aside>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>

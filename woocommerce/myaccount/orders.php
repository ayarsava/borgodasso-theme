<?php
/**
 * Orders — list + empty state (Ordini).
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 9.5.0
 */

defined( 'ABSPATH' ) || exit;

// Theme owns the confirm-email CTA markup — drop WooCommerce’s default notice.
if ( function_exists( 'borgodasso_orders_replace_email_verification_prompt' ) ) {
	borgodasso_orders_replace_email_verification_prompt();
}

$show_email_verify = function_exists( 'borgodasso_orders_should_show_email_verification' )
	&& borgodasso_orders_should_show_email_verification();
$shop_url          = apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) );

do_action( 'woocommerce_before_account_orders', $has_orders );
?>

<section class="account-section account-section--orders" aria-labelledby="account-orders-title">
	<h2 id="account-orders-title" class="account-section__title"><?php echo esc_html__( 'Ordini', 'borgodasso' ); ?></h2>

	<?php if ( $has_orders ) : ?>

		<div class="account-orders-list">
			<?php
			foreach ( $customer_orders->orders as $customer_order ) {
				$order = wc_get_order( $customer_order ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				if ( ! $order ) {
					continue;
				}
				borgodasso_template( 'components/account-order', array( 'order' => $order ) );
			}
			?>
		</div>

		<?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

		<?php if ( 1 < $customer_orders->max_num_pages ) : ?>
			<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination account-orders-pagination">
				<?php if ( 1 !== $current_page ) : ?>
					<a class="<?php echo esc_attr( borgodasso_account_button_class( 'woocommerce-button--previous' ) ); ?>" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>"><?php esc_html_e( 'Previous', 'woocommerce' ); ?></a>
				<?php endif; ?>

				<?php if ( intval( $customer_orders->max_num_pages ) !== $current_page ) : ?>
					<a class="<?php echo esc_attr( borgodasso_account_button_class( 'woocommerce-button--next' ) ); ?>" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'woocommerce' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $show_email_verify ) : ?>
			<div class="account-orders-verify">
				<p class="account-orders-verify__text">
					<?php echo esc_html__( 'Confirm your email address to check for past orders and link them to your account.', 'woocommerce' ); ?>
				</p>
				<p class="account-form__actions">
					<a class="<?php echo esc_attr( borgodasso_account_button_class() ); ?>" href="<?php echo esc_url( borgodasso_orders_email_verification_url() ); ?>">
						<?php echo esc_html__( 'Confirm email address', 'woocommerce' ); ?>
					</a>
				</p>
			</div>
		<?php endif; ?>

	<?php else : ?>

		<div class="account-orders-empty">
			<h3 class="account-orders-empty__title"><?php echo esc_html__( 'Non hai ancora effettuato alcun acquisto.', 'borgodasso' ); ?></h3>
			<p class="account-orders-empty__text"><?php echo esc_html__( 'In questa sezione potrai consultare lo stato dei tuoi ordini.', 'borgodasso' ); ?></p>

			<p class="account-form__actions">
				<a class="<?php echo esc_attr( borgodasso_account_button_class() ); ?>" href="<?php echo esc_url( $shop_url ); ?>">
					<?php echo esc_html__( 'Vai allo shop', 'borgodasso' ); ?>
				</a>
			</p>

			<?php if ( $show_email_verify ) : ?>
				<div class="account-form__divider" role="separator">
					<span><?php echo esc_html__( 'oppure', 'borgodasso' ); ?></span>
				</div>

				<div class="account-orders-verify">
					<p class="account-form__actions">
						<a class="<?php echo esc_attr( borgodasso_account_button_class() ); ?>" href="<?php echo esc_url( borgodasso_orders_email_verification_url() ); ?>">
							<?php echo esc_html__( 'Confirm email address', 'woocommerce' ); ?>
						</a>
					</p>
					<p class="account-orders-verify__text">
						<?php echo esc_html__( 'Confirm your email address to check for past orders and link them to your account.', 'woocommerce' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>

	<?php endif; ?>
</section>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>

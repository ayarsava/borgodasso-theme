<?php
/**
 * Single My Account order card — status, meta, Embla product slider.
 *
 * @package Borgodasso
 *
 * @var WC_Order $order Order.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $order ) || ! $order instanceof WC_Order ) {
	return;
}

$status_label = borgodasso_order_status_label( $order );
$meta         = borgodasso_order_list_meta( $order );
$slides       = borgodasso_order_product_slides( $order );
$slide_count  = count( $slides );
$order_id     = $order->get_id();
$status_slug  = $order->get_status();
?>
<article
	class="account-order account-order--<?php echo esc_attr( $status_slug ); ?>"
	data-account-order
	aria-labelledby="account-order-<?php echo esc_attr( (string) $order_id ); ?>-status"
>
	<header class="account-order__header">
		<div class="account-order__heading">
			<h3 id="account-order-<?php echo esc_attr( (string) $order_id ); ?>-status" class="account-order__status">
				<?php echo esc_html( $status_label ); ?>
			</h3>
			<?php if ( '' !== $meta ) : ?>
				<p class="account-order__meta"><?php echo esc_html( $meta ); ?></p>
			<?php endif; ?>
			<p class="account-order__view">
				<a class="account-order__view-link" href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: order number. */
							__( 'Vedi ordine n° %s', 'borgodasso' ),
							$order->get_order_number()
						)
					);
					?>
				</a>
			</p>
			<?php
			$tracking = function_exists( 'borgodasso_order_tracking' ) ? borgodasso_order_tracking( $order ) : array();
			if ( ! empty( $tracking['code'] ) ) :
				?>
				<p class="account-order__tracking">
					<?php if ( '' !== $tracking['carrier'] ) : ?>
						<span class="account-order__tracking-carrier"><?php echo esc_html( $tracking['carrier'] ); ?></span>
						<span class="account-order__tracking-sep" aria-hidden="true"> · </span>
					<?php endif; ?>
					<?php if ( '' !== $tracking['url'] ) : ?>
						<a class="account-order__tracking-link" href="<?php echo esc_url( $tracking['url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: tracking code. */
									__( 'Tracking %s', 'borgodasso' ),
									$tracking['code']
								)
							);
							?>
						</a>
					<?php else : ?>
						<span class="account-order__tracking-code">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: tracking code. */
									__( 'Tracking %s', 'borgodasso' ),
									$tracking['code']
								)
							);
							?>
						</span>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>

		<?php if ( $slide_count > 1 ) : ?>
			<div class="account-order__arrows" data-account-order-arrows>
				<button
					type="button"
					class="account-order__arrow account-order__arrow--prev"
					data-account-order-prev
					aria-label="<?php esc_attr_e( 'Prodotti precedenti', 'borgodasso' ); ?>"
				>
					<?php
					borgodasso_template(
						'objects/svg',
						array(
							'name'  => 'arrow',
							'class' => 'account-order__arrow-icon account-order__arrow-icon--prev',
						)
					);
					?>
				</button>
				<button
					type="button"
					class="account-order__arrow account-order__arrow--next"
					data-account-order-next
					aria-label="<?php esc_attr_e( 'Prodotti successivi', 'borgodasso' ); ?>"
				>
					<?php
					borgodasso_template(
						'objects/svg',
						array(
							'name'  => 'arrow',
							'class' => 'account-order__arrow-icon',
						)
					);
					?>
				</button>
			</div>
		<?php endif; ?>
	</header>

	<?php
	$pay_state = function_exists( 'borgodasso_order_payment_ui_state' )
		? borgodasso_order_payment_ui_state( $order )
		: ( $order->needs_payment() ? 'pay' : 'none' );
	?>
	<?php if ( 'pay' === $pay_state ) : ?>
		<div class="account-order__actions">
			<?php
			borgodasso_template(
				'objects/button',
				array(
					'label'   => __( 'Paga ora', 'borgodasso' ),
					'url'     => $order->get_checkout_payment_url(),
					'variant' => 'solid',
					'class'   => 'account-order__pay',
					'once'    => true,
				)
			);
			?>
		</div>
	<?php elseif ( 'processing' === $pay_state ) : ?>
		<p class="account-order__pay-status" role="status">
			<?php
			echo esc_html(
				function_exists( 'borgodasso_order_payment_status_label' )
					? borgodasso_order_payment_status_label( $order )
					: __( 'Pagamento in elaborazione…', 'borgodasso' )
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( $slide_count > 0 ) : ?>
		<div class="account-order__slider<?php echo $slide_count > 1 ? '' : ' account-order__slider--single'; ?>" data-account-order-slider>
			<div class="account-order__viewport" data-account-order-viewport>
				<div class="account-order__track">
					<?php foreach ( $slides as $slide ) : ?>
						<div class="account-order__slide">
							<a class="account-order__product" href="<?php echo esc_url( $slide['url'] ); ?>">
								<?php if ( $slide['image_id'] ) : ?>
									<?php
									echo wp_get_attachment_image(
										$slide['image_id'],
										'medium_large',
										false,
										array(
											'class'    => 'account-order__img',
											'alt'      => $slide['name'],
											'loading'  => 'lazy',
											'decoding' => 'async',
										)
									);
									?>
								<?php else : ?>
									<span class="account-order__img account-order__img--placeholder" aria-hidden="true"></span>
									<span class="screen-reader-text"><?php echo esc_html( $slide['name'] ); ?></span>
								<?php endif; ?>
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</article>

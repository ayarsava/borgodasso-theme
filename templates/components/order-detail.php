<?php
/**
 * Order detail — shared by My Account view-order + checkout thankyou.
 *
 * Expected $args from borgodasso_template():
 * - order: WC_Order
 * - notes: array (optional customer notes)
 * - show_title: bool (default true)
 *
 * @package Borgodasso
 *
 * @var WC_Order $order
 * @var array    $notes
 * @var bool     $show_title
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $order ) || ! $order instanceof WC_Order ) {
	return;
}

$notes      = isset( $notes ) && is_array( $notes ) ? $notes : array();
$show_title = ! isset( $show_title ) || (bool) $show_title;
$items      = function_exists( 'borgodasso_order_detail_items' ) ? borgodasso_order_detail_items( $order ) : array();
$item_count = 0;
foreach ( $items as $row ) {
	$item_count += max( 1, (int) $row['quantity'] );
}

$order_number = $order->get_order_number();
$order_date   = function_exists( 'borgodasso_order_list_date_label' )
	? borgodasso_order_list_date_label( $order->get_date_created() )
	: ( $order->get_date_created() ? $order->get_date_created()->date_i18n( 'd/m/Y' ) : '' );

$shipping_html   = $order->get_formatted_shipping_address();
$billing_html    = $order->get_formatted_billing_address();
$payment_id      = $order->get_payment_method();
$payment_display = function_exists( 'borgodasso_order_payment_display' )
	? borgodasso_order_payment_display( $order )
	: array(
		'title'    => (string) $order->get_payment_method_title(),
		'brand'    => '',
		'last4'    => '',
		'holder'   => '',
		'icon_url' => '',
		'is_card'  => false,
	);
$payment_title   = $payment_display['title'];

$subtotal = $order->get_subtotal_to_display();
$shipping = $order->get_shipping_to_display();
$total    = $order->get_formatted_order_total();

$show_stepper  = function_exists( 'borgodasso_order_stepper_should_show' )
	? borgodasso_order_stepper_should_show( $order )
	: false;
$stepper_steps = function_exists( 'borgodasso_order_stepper_steps' )
	? borgodasso_order_stepper_steps()
	: array();
$stepper_index = function_exists( 'borgodasso_order_stepper_index' )
	? borgodasso_order_stepper_index( $order )
	: -1;
?>
<section class="order-detail" aria-labelledby="order-detail-title">
	<?php if ( $show_title ) : ?>
		<h2 id="order-detail-title" class="order-detail__title">
			<?php echo esc_html__( "Dettagli dell'ordine", 'borgodasso' ); ?>
		</h2>
	<?php endif; ?>

	<?php if ( $show_stepper && array() !== $stepper_steps ) : ?>
		<ol
			class="order-detail__stepper"
			aria-label="<?php echo esc_attr__( 'Stato ordine', 'borgodasso' ); ?>"
		>
			<?php foreach ( $stepper_steps as $i => $step ) : ?>
				<?php
				$done    = $i <= $stepper_index;
				$current = $i === $stepper_index;
				?>
				<li
					class="order-detail__step<?php echo $done ? ' is-done' : ''; ?><?php echo $current ? ' is-current' : ''; ?>"
					<?php echo $current ? ' aria-current="step"' : ''; ?>
				>
					<span class="order-detail__step-dot" aria-hidden="true"></span>
					<span class="order-detail__step-label"><?php echo esc_html( $step['label'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php endif; ?>

	<div class="order-detail__meta">
		<div class="order-detail__meta-item">
			<span class="order-detail__meta-label"><?php echo esc_html__( 'Ordine n°', 'borgodasso' ); ?></span>
			<span class="order-detail__meta-value"><?php echo esc_html( (string) $order_number ); ?></span>
		</div>
		<?php if ( '' !== $order_date ) : ?>
			<div class="order-detail__meta-item order-detail__meta-item--end">
				<span class="order-detail__meta-label"><?php echo esc_html__( 'Data', 'borgodasso' ); ?></span>
				<span class="order-detail__meta-value"><?php echo esc_html( $order_date ); ?></span>
			</div>
		<?php endif; ?>
	</div>

	<?php
	$tracking = function_exists( 'borgodasso_order_tracking' ) ? borgodasso_order_tracking( $order ) : array();
	if ( ! empty( $tracking['code'] ) ) :
		?>
		<div class="order-detail__tracking">
			<h3 class="order-detail__tracking-title"><?php echo esc_html__( 'Spedizione', 'borgodasso' ); ?></h3>
			<?php if ( '' !== $tracking['carrier'] ) : ?>
				<p class="order-detail__tracking-line">
					<span class="order-detail__tracking-label"><?php echo esc_html__( 'Corriere', 'borgodasso' ); ?></span>
					<span class="order-detail__tracking-value"><?php echo esc_html( $tracking['carrier'] ); ?></span>
				</p>
			<?php endif; ?>
			<p class="order-detail__tracking-line">
				<span class="order-detail__tracking-label"><?php echo esc_html__( 'Tracking', 'borgodasso' ); ?></span>
				<span class="order-detail__tracking-value"><?php echo esc_html( $tracking['code'] ); ?></span>
			</p>
			<?php if ( '' !== $tracking['url'] ) : ?>
				<p class="order-detail__tracking-action">
					<a class="order-detail__tracking-link" href="<?php echo esc_url( $tracking['url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html__( 'Segui la spedizione', 'borgodasso' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php
	$pay_state = function_exists( 'borgodasso_order_payment_ui_state' )
		? borgodasso_order_payment_ui_state( $order )
		: ( $order->needs_payment() ? 'pay' : 'none' );
	?>
	<?php if ( 'pay' === $pay_state ) : ?>
		<div class="order-detail__actions">
			<?php
			borgodasso_template(
				'objects/button',
				array(
					'label'   => __( 'Paga ora', 'borgodasso' ),
					'url'     => $order->get_checkout_payment_url(),
					'variant' => 'solid',
					'class'   => 'order-detail__pay',
					'once'    => true,
				)
			);
			?>
		</div>
	<?php elseif ( 'processing' === $pay_state ) : ?>
		<p class="order-detail__pay-status" role="status">
			<?php
			echo esc_html(
				function_exists( 'borgodasso_order_payment_status_label' )
					? borgodasso_order_payment_status_label( $order, 'detail' )
					: __( 'Pagamento in elaborazione…', 'borgodasso' )
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( array() !== $items ) : ?>
		<ul class="order-detail__items">
			<?php foreach ( $items as $row ) : ?>
				<li class="order-detail__item">
					<div class="order-detail__thumb">
						<?php if ( $row['image_id'] ) : ?>
							<?php
							$image = wp_get_attachment_image(
								$row['image_id'],
								'woocommerce_thumbnail',
								false,
								array(
									'class'    => 'order-detail__img',
									'alt'      => $row['name'],
									'loading'  => 'lazy',
									'decoding' => 'async',
								)
							);
							if ( '' !== $row['url'] ) {
								echo '<a class="order-detail__thumb-link" href="' . esc_url( $row['url'] ) . '">' . $image . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} else {
								echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						<?php else : ?>
							<span class="order-detail__img order-detail__img--placeholder" aria-hidden="true"></span>
						<?php endif; ?>
					</div>

					<div class="order-detail__info">
						<?php if ( '' !== $row['url'] ) : ?>
							<a class="order-detail__name" href="<?php echo esc_url( $row['url'] ); ?>"><?php echo esc_html( $row['name'] ); ?></a>
						<?php else : ?>
							<span class="order-detail__name"><?php echo esc_html( $row['name'] ); ?></span>
						<?php endif; ?>

						<?php if ( '' !== $row['price_html'] ) : ?>
							<div class="order-detail__price"><?php echo wp_kses_post( $row['price_html'] ); ?></div>
						<?php endif; ?>

						<?php foreach ( $row['meta_lines'] as $meta_line ) : ?>
							<div class="order-detail__attr"><?php echo esc_html( $meta_line ); ?></div>
						<?php endforeach; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="order-detail__totals">
		<div class="order-detail__total-row">
			<span class="order-detail__total-label">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of products in the order. */
						_n( 'Subtotale · %d articolo', 'Subtotale · %d articoli', $item_count, 'borgodasso' ),
						$item_count
					)
				);
				?>
			</span>
			<span class="order-detail__total-value"><?php echo wp_kses_post( $subtotal ); ?></span>
		</div>
		<?php if ( $order->get_shipping_method() || '' !== $shipping ) : ?>
			<div class="order-detail__total-row">
				<span class="order-detail__total-label"><?php echo esc_html__( 'Spedizione', 'borgodasso' ); ?></span>
				<span class="order-detail__total-value"><?php echo wp_kses_post( $shipping ); ?></span>
			</div>
		<?php endif; ?>
		<div class="order-detail__total-row order-detail__total-row--grand">
			<span class="order-detail__total-label"><?php echo esc_html__( 'Totale', 'borgodasso' ); ?></span>
			<span class="order-detail__total-value"><?php echo wp_kses_post( $total ); ?></span>
		</div>
	</div>

	<details class="order-detail__data">
		<summary class="order-detail__data-summary">
			<span class="order-detail__data-title"><?php echo esc_html__( "Dati dell'ordine", 'borgodasso' ); ?></span>
			<span class="order-detail__data-icon" aria-hidden="true"></span>
		</summary>

		<div class="order-detail__data-body">
			<?php if ( $shipping_html ) : ?>
				<div class="order-detail__block">
					<h3 class="order-detail__block-label"><?php echo esc_html__( 'Indirizzo di spedizione', 'borgodasso' ); ?></h3>
					<address class="order-detail__address"><?php echo wp_kses_post( $shipping_html ); ?></address>
				</div>
			<?php endif; ?>

			<?php if ( $billing_html ) : ?>
				<div class="order-detail__block">
					<h3 class="order-detail__block-label"><?php echo esc_html__( 'Indirizzo di fatturazione', 'borgodasso' ); ?></h3>
					<address class="order-detail__address"><?php echo wp_kses_post( $billing_html ); ?></address>
				</div>
			<?php endif; ?>

			<?php if ( $payment_title || ! empty( $payment_display['is_card'] ) ) : ?>
				<div class="order-detail__block">
					<h3 class="order-detail__block-label"><?php echo esc_html__( 'Metodo di pagamento', 'borgodasso' ); ?></h3>
					<?php if ( ! empty( $payment_display['is_card'] ) ) : ?>
						<div class="order-detail__payment order-detail__payment--card order-detail__payment--<?php echo esc_attr( sanitize_html_class( (string) $payment_id ) ); ?>">
							<div class="order-detail__payment-row">
								<?php if ( ! empty( $payment_display['icon_url'] ) ) : ?>
									<img
										class="order-detail__payment-icon"
										src="<?php echo esc_url( $payment_display['icon_url'] ); ?>"
										alt="<?php echo esc_attr( '' !== $payment_display['brand'] ? $payment_display['brand'] : __( 'Carta', 'borgodasso' ) ); ?>"
										width="40"
										height="26"
										loading="lazy"
										decoding="async"
									/>
								<?php endif; ?>
								<?php if ( '' !== $payment_display['holder'] ) : ?>
									<span class="order-detail__payment-holder"><?php echo esc_html( $payment_display['holder'] ); ?></span>
								<?php endif; ?>
							</div>
							<?php if ( '' !== $payment_display['last4'] ) : ?>
								<p class="order-detail__payment-mask">****<?php echo esc_html( $payment_display['last4'] ); ?></p>
							<?php endif; ?>
						</div>
					<?php else : ?>
						<p class="order-detail__payment order-detail__payment--<?php echo esc_attr( sanitize_html_class( (string) $payment_id ) ); ?>">
							<?php echo esc_html( $payment_title ); ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</details>

	<?php if ( array() !== $notes ) : ?>
		<div class="order-detail__notes">
			<h3 class="order-detail__notes-title"><?php echo esc_html__( 'Aggiornamenti ordine', 'borgodasso' ); ?></h3>
			<ol class="order-detail__notes-list">
				<?php foreach ( $notes as $note ) : ?>
					<li class="order-detail__note">
						<?php if ( ! empty( $note->comment_date ) ) : ?>
							<p class="order-detail__note-meta">
								<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $note->comment_date ) ) ); ?>
							</p>
						<?php endif; ?>
						<div class="order-detail__note-body">
							<?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	<?php endif; ?>
</section>

<?php
/**
 * Mini-cart side drawer — filled + empty states.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	return;
}

$cart      = WC()->cart;
$qty_count = (int) $cart->get_cart_contents_count();
$is_empty  = $cart->is_empty();
$shop_url  = borgodasso_shop_url();
$checkout  = function_exists( 'borgodasso_checkout_url' ) ? borgodasso_checkout_url() : home_url( '/checkout/' );
$title_id  = 'cart-drawer-title';
?>
<div class="cart-drawer<?php echo $is_empty ? ' cart-drawer--empty' : ''; ?>" data-cart-drawer hidden>
	<button
		type="button"
		class="cart-drawer__backdrop"
		data-cart-close
		tabindex="-1"
		aria-label="<?php esc_attr_e( 'Chiudi carrello', 'borgodasso' ); ?>"
	></button>

	<aside
		class="cart-drawer__panel"
		data-cart-panel
		role="dialog"
		aria-modal="true"
		aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
		tabindex="-1"
	>
		<header class="cart-drawer__header">
			<h2 id="<?php echo esc_attr( $title_id ); ?>" class="cart-drawer__title">
				<?php
				echo esc_html(
					function_exists( 'borgodasso_cart_count_label' )
						? borgodasso_cart_count_label()
						: sprintf(
							/* translators: %d: number of products in the cart (summed quantity). */
							__( 'Carrello (%d)', 'borgodasso' ),
							$qty_count
						)
				);
				?>
			</h2>
			<button
				type="button"
				class="cart-drawer__close"
				data-cart-close
				aria-label="<?php esc_attr_e( 'Chiudi', 'borgodasso' ); ?>"
			>
				<?php
				borgodasso_template(
					'objects/svg',
					array(
						'name'  => 'close',
						'class' => 'cart-drawer__close-icon',
					)
				);
				?>
			</button>
		</header>

		<?php if ( $is_empty ) : ?>

			<div class="cart-drawer__empty">
				<p class="cart-drawer__empty-text">
					<?php echo esc_html__( 'Il tuo carrello è vuoto.', 'borgodasso' ); ?>
					<br>
					<?php echo esc_html__( 'Aggiungi il tuo primo prodotto.', 'borgodasso' ); ?>
				</p>
			</div>

			<p class="cart-drawer__empty-actions">
					<a class="cart-drawer__button account-button" href="<?php echo esc_url( $shop_url ); ?>">
						<?php echo esc_html__( 'Vai allo shop', 'borgodasso' ); ?>
					</a>
				</p>
		<?php else : ?>

			<div class="cart-drawer__body" data-cart-body>
				<ul class="cart-drawer__list">
					<?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) : ?>
						<?php
						$product = isset( $cart_item['data'] ) && $cart_item['data'] instanceof WC_Product
							? $cart_item['data']
							: null;

						if ( ! $product || ! $product->exists() || (int) $cart_item['quantity'] <= 0 ) {
							continue;
						}

						$parent_id = ! empty( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : $product->get_id();
						$parent    = wc_get_product( $parent_id );
						$permalink = $product->is_visible() ? $product->get_permalink( $cart_item ) : '';
						// Prefer parent title so variation attrs stay in the meta line.
						$name       = $parent instanceof WC_Product ? $parent->get_name() : $product->get_name();
						$thumbnail  = $product->get_image( 'borgodasso_cart_thumb', array( 'class' => 'cart-drawer__img' ) );
						$quantity   = (int) $cart_item['quantity'];
						$price_html = WC()->cart->get_product_subtotal( $product, $quantity );
						$meta_line  = borgodasso_cart_item_meta_line( $cart_item );
						$max_qty    = $product->get_max_purchase_quantity();
						$min_qty    = max( 1, (int) $product->get_min_purchase_quantity() );
						?>
						<li class="cart-drawer__item" data-cart-item data-key="<?php echo esc_attr( $cart_item_key ); ?>">
							<div class="cart-drawer__thumb">
								<?php if ( $permalink ) : ?>
									<a href="<?php echo esc_url( $permalink ); ?>" class="cart-drawer__thumb-link">
										<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC product image. ?>
									</a>
								<?php else : ?>
									<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC product image. ?>
								<?php endif; ?>
							</div>

							<div class="cart-drawer__details">
								<div class="cart-drawer__row">
									<?php if ( $permalink ) : ?>
										<a class="cart-drawer__name" href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $name ); ?></a>
									<?php else : ?>
										<span class="cart-drawer__name"><?php echo esc_html( $name ); ?></span>
									<?php endif; ?>

									<div class="cart-drawer__controls">
										<div class="cart-drawer__qty" data-cart-qty>
											<button
												type="button"
												class="cart-drawer__qty-btn cart-drawer__qty-btn--minus"
												data-cart-qty-minus
												aria-label="<?php esc_attr_e( 'Diminuisci quantità', 'borgodasso' ); ?>"
												<?php echo $quantity <= $min_qty ? ' disabled' : ''; ?>
											>−</button>
											<span class="cart-drawer__qty-value" data-cart-qty-value><?php echo esc_html( (string) $quantity ); ?></span>
											<button
												type="button"
												class="cart-drawer__qty-btn cart-drawer__qty-btn--plus"
												data-cart-qty-plus
												aria-label="<?php esc_attr_e( 'Aumenta quantità', 'borgodasso' ); ?>"
												<?php echo ( $max_qty > 0 && $quantity >= $max_qty ) ? ' disabled' : ''; ?>
											>+</button>
										</div>

										<button
											type="button"
											class="cart-drawer__remove"
											data-cart-remove
											aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Rimuovi %s', 'borgodasso' ), $name ) ); ?>"
										>
											<?php
											borgodasso_template(
												'objects/svg',
												array(
													'name' => 'trash',
													'class' => 'cart-drawer__remove-icon',
												)
											);
											?>
										</button>
									</div>
								</div>

								<div class="cart-drawer__price"><?php echo wp_kses_post( $price_html ); ?></div>

								<?php if ( '' !== $meta_line ) : ?>
									<div class="cart-drawer__meta"><?php echo esc_html( $meta_line ); ?></div>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<footer class="cart-drawer__footer">
				<div class="cart-drawer__summary">
					<div class="cart-drawer__summary-row">
						<span class="cart-drawer__summary-label">
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
						<span class="cart-drawer__summary-value"><?php echo wp_kses_post( $cart->get_cart_subtotal() ); ?></span>
					</div>
					<div class="cart-drawer__summary-row cart-drawer__summary-row--shipping">
						<span class="cart-drawer__summary-label"><?php echo esc_html__( 'Spedizione', 'borgodasso' ); ?></span>
						<span class="cart-drawer__summary-value cart-drawer__summary-value--muted"><?php echo esc_html__( 'Calcolata al checkout', 'borgodasso' ); ?></span>
					</div>
				</div>

				<div class="cart-drawer__actions">
					<a class="cart-drawer__button account-button" href="<?php echo esc_url( $checkout ); ?>">
						<?php echo esc_html__( 'Procedi al checkout', 'borgodasso' ); ?>
					</a>
					<a class="cart-drawer__button account-button account-button--outline" href="<?php echo esc_url( $shop_url ); ?>">
						<?php echo esc_html__( 'Continua lo shopping', 'borgodasso' ); ?>
					</a>
				</div>
			</footer>

		<?php endif; ?>
	</aside>
</div>

<?php
/**
 * WooCommerce mini-cart drawer.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta line under the cart item price (format + variation attrs).
 *
 * @param array<string, mixed> $cart_item Cart item.
 */
function borgodasso_cart_item_meta_line( array $cart_item ): string {
	$product = isset( $cart_item['data'] ) && $cart_item['data'] instanceof WC_Product
		? $cart_item['data']
		: null;

	if ( ! $product ) {
		return '';
	}

	$parts = array();

	$parent_id    = ! empty( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : $product->get_id();
	$variation_id = ! empty( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
	$own_format   = function_exists( 'borgodasso_product_sale_format_label_own' )
		? borgodasso_product_sale_format_label_own( $product )
		: '';

	// Variation-specific label is complete (e.g. “Cassa da 6 (6 × 250 ml)”).
	if ( '' !== $own_format && $variation_id > 0 ) {
		return $own_format;
	}

	$parent = wc_get_product( $parent_id );
	$format = borgodasso_product_sale_format_label( $parent instanceof WC_Product ? $parent : $product );

	if ( '' !== $format ) {
		$parts[] = $format;
	}

	if ( ! empty( $cart_item['variation'] ) && is_array( $cart_item['variation'] ) ) {
		foreach ( $cart_item['variation'] as $value ) {
			$value = is_string( $value ) ? trim( $value ) : '';
			if ( '' !== $value ) {
				$parts[] = $value;
			}
		}
	}

	return implode( ' ', $parts );
}

/**
 * Render the cart drawer markup (for templates + AJAX refresh).
 */
function borgodasso_render_cart_drawer(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	borgodasso_template( 'components/cart-drawer' );
}

/**
 * Cart drawer HTML string for fragments / AJAX.
 */
function borgodasso_get_cart_drawer_html(): string {
	ob_start();
	borgodasso_render_cart_drawer();
	return (string) ob_get_clean();
}

/**
 * Number of distinct cart lines (not summed quantity).
 */
function borgodasso_cart_lines_count(): int {
	if ( function_exists( 'WC' ) && WC()->cart ) {
		return count( WC()->cart->get_cart() );
	}

	return 0;
}

/**
 * Header / drawer title count label — “Carrello (N)” (summed quantity).
 */
function borgodasso_cart_count_label(): string {
	/* translators: %d: number of products in the cart (summed quantity). */
	return sprintf( __( 'Carrello (%d)', 'borgodasso' ), borgodasso_cart_count() );
}

/**
 * Render the mobile header shop / checkout icon (badge when cart has items).
 *
 * @param array{label?: string, url?: string, target?: string} $icon_item Icon row from theme options.
 */
function borgodasso_render_header_shop_icon( array $icon_item = array() ): void {
	$count     = borgodasso_cart_count();
	$has_items = $count > 0;
	$shop_url  = isset( $icon_item['url'] ) && '' !== (string) $icon_item['url']
		? (string) $icon_item['url']
		: borgodasso_shop_url();
	$url       = $has_items ? borgodasso_checkout_url() : $shop_url;
	$target    = isset( $icon_item['target'] ) ? (string) $icon_item['target'] : '';
	$label     = isset( $icon_item['label'] ) ? trim( (string) $icon_item['label'] ) : '';

	if ( '' === $label ) {
		$label = __( 'Shop', 'borgodasso' );
	}

	if ( $has_items ) {
		/* translators: %d: number of items in the cart. */
		$label = sprintf( __( 'Checkout (%d)', 'borgodasso' ), $count );
	}

	$classes = 'site-header__mobile-icon site-header__mobile-icon--shop';
	if ( $has_items ) {
		$classes .= ' site-header__mobile-icon--checkout has-cart-items';
	}
	?>
	<a
		class="<?php echo esc_attr( $classes ); ?>"
		href="<?php echo esc_url( $url ); ?>"
		aria-label="<?php echo esc_attr( $label ); ?>"
		data-header-shop
		data-cart-fly-target
		<?php echo $has_items ? ' data-cart-open' : ''; ?>
		<?php echo '' !== $target ? ' target="' . esc_attr( $target ) . '"' : ''; ?>
		<?php echo '_blank' === $target ? ' rel="noopener noreferrer"' : ''; ?>
	>
		<?php
		borgodasso_template(
			'objects/svg',
			array(
				'name'  => 'shop',
				'class' => 'site-header__mobile-icon-svg',
			)
		);
		?>
		<?php if ( $has_items ) : ?>
			<span class="site-header__cart-badge" data-cart-badge aria-hidden="true"><?php echo esc_html( (string) $count ); ?></span>
		<?php endif; ?>
	</a>
	<?php
}

/**
 * Header shop icon HTML for fragments / AJAX.
 *
 * @param array{label?: string, url?: string, target?: string} $icon_item Icon row.
 */
function borgodasso_get_header_shop_icon_html( array $icon_item = array() ): string {
	ob_start();
	borgodasso_render_header_shop_icon( $icon_item );
	return (string) ob_get_clean();
}

/**
 * After a non-AJAX add-to-cart POST, redirect to a GET URL (PRG).
 * Prevents browser refresh from re-submitting and adding the product again.
 *
 * @param string          $url     Redirect URL from other filters (may be empty).
 * @param WC_Product|null $product Product that was added.
 * @return string
 */
function borgodasso_add_to_cart_redirect( string $url, $product = null ): string {
	if ( '' !== $url ) {
		return $url;
	}

	// Honour WC “Redirect to the cart page after successful addition”.
	if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
		return $url;
	}

	if ( $product instanceof WC_Product ) {
		$permalink = $product->is_type( 'variation' )
			? get_permalink( $product->get_parent_id() )
			: $product->get_permalink();

		if ( is_string( $permalink ) && '' !== $permalink ) {
			return $permalink;
		}
	}

	$referer = wp_get_referer();
	return is_string( $referer ) && '' !== $referer ? $referer : $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'borgodasso_add_to_cart_redirect', 10, 2 );

/**
 * Refresh cart drawer + header count via WC fragments.
 *
 * @param array<string, string> $fragments Fragments.
 * @return array<string, string>
 */
function borgodasso_cart_fragments( array $fragments ): array {
	$fragments['div.cart-drawer']         = borgodasso_get_cart_drawer_html();
	$fragments['[data-cart-count-label]'] = '<span data-cart-count-label>' . esc_html( borgodasso_cart_count_label() ) . '</span>';
	$fragments['[data-cart-count]']       = '<span data-cart-count>' . esc_html( (string) borgodasso_cart_count() ) . '</span>';
	$fragments['[data-header-shop]']      = borgodasso_get_header_shop_icon_html();

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'borgodasso_cart_fragments' );

/**
 * AJAX: set cart item quantity (0 removes).
 */
function borgodasso_ajax_cart_update_item(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json_error( array( 'message' => 'Cart unavailable' ), 400 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'borgodasso_cart' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
	}

	$key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['cart_item_key'] ) ) : '';
	$qty = isset( $_POST['quantity'] ) ? (int) sanitize_text_field( wp_unslash( (string) $_POST['quantity'] ) ) : -1;

	if ( '' === $key || $qty < 0 ) {
		wp_send_json_error( array( 'message' => 'Invalid item' ), 400 );
	}

	if ( 0 === $qty ) {
		WC()->cart->remove_cart_item( $key );
	} else {
		WC()->cart->set_quantity( $key, $qty, true );
	}

	WC()->cart->calculate_totals();

	wp_send_json_success(
		array(
			'count'     => borgodasso_cart_count(),
			'label'     => borgodasso_cart_count_label(),
			'cart_hash' => WC()->cart->get_cart_hash(),
			'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ),
		)
	);
}
add_action( 'wc_ajax_borgodasso_cart_update_item', 'borgodasso_ajax_cart_update_item' );
add_action( 'wc_ajax_nopriv_borgodasso_cart_update_item', 'borgodasso_ajax_cart_update_item' );

/**
 * AJAX: remove cart item.
 */
function borgodasso_ajax_cart_remove_item(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json_error( array( 'message' => 'Cart unavailable' ), 400 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'borgodasso_cart' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
	}

	$key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['cart_item_key'] ) ) : '';
	if ( '' === $key ) {
		wp_send_json_error( array( 'message' => 'Invalid item' ), 400 );
	}

	WC()->cart->remove_cart_item( $key );
	WC()->cart->calculate_totals();

	wp_send_json_success(
		array(
			'count'     => borgodasso_cart_count(),
			'label'     => borgodasso_cart_count_label(),
			'cart_hash' => WC()->cart->get_cart_hash(),
			'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ),
		)
	);
}
add_action( 'wc_ajax_borgodasso_cart_remove_item', 'borgodasso_ajax_cart_remove_item' );
add_action( 'wc_ajax_nopriv_borgodasso_cart_remove_item', 'borgodasso_ajax_cart_remove_item' );

/**
 * Expose cart drawer config to the front end.
 */
function borgodasso_cart_drawer_script_data(): void {
	if ( ! function_exists( 'WC' ) || ! class_exists( 'WC_AJAX' ) ) {
		return;
	}

	if ( ! wp_script_is( 'borgodasso-main', 'enqueued' ) && ! wp_script_is( 'borgodasso-main', 'registered' ) ) {
		return;
	}

	wp_add_inline_script(
		'borgodasso-main',
		'window.borgodassoCart = ' . wp_json_encode(
			array(
				'nonce'        => wp_create_nonce( 'borgodasso_cart' ),
				'updateUrl'    => \WC_AJAX::get_endpoint( 'borgodasso_cart_update_item' ),
				'removeUrl'    => \WC_AJAX::get_endpoint( 'borgodasso_cart_remove_item' ),
				'addToCartUrl' => \WC_AJAX::get_endpoint( 'add_to_cart' ),
				'fragmentsUrl' => \WC_AJAX::get_endpoint( 'get_refreshed_fragments' ),
				'shopUrl'      => borgodasso_shop_url(),
				'checkoutUrl'  => borgodasso_checkout_url(),
				'cartHash'     => WC()->cart ? WC()->cart->get_cart_hash() : '',
			)
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'borgodasso_cart_drawer_script_data', 30 );

/**
 * Skip the Cart block page — drawer is the cart UI; checkout holds coupons / payment.
 *
 * Theme hook only (not a WC template override), so WooCommerce updates won't overwrite it.
 */
function borgodasso_redirect_cart_page(): void {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}

	// Allow WC/store API and explicit bypass for debugging.
	if ( isset( $_GET['borgodasso_cart_page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$has_items = function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty();

	if ( $has_items ) {
		wp_safe_redirect( borgodasso_checkout_url() );
		exit;
	}

	// Empty cart → shop, open the empty drawer.
	wp_safe_redirect( add_query_arg( 'cart', 'open', borgodasso_shop_url() ) );
	exit;
}
add_action( 'template_redirect', 'borgodasso_redirect_cart_page', 1 );

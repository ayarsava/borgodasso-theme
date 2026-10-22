<?php
/**
 * Product catalog grid list (shared by PDP catalog + shop archive).
 *
 * Expected $args from borgodasso_template():
 * - products: array<int, WC_Product>
 *
 * @package Borgodasso
 *
 * @var array<int, WC_Product> $products
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$products = isset( $products ) && is_array( $products ) ? $products : array();

if ( array() === $products ) {
	return;
}
?>
<ul class="product-catalog__list">
	<?php foreach ( $products as $catalog_product ) : ?>
		<?php
		if ( ! $catalog_product instanceof WC_Product ) {
			continue;
		}

		$product_id   = $catalog_product->get_id();
		$permalink    = get_permalink( $product_id );
		$title        = $catalog_product->get_name();
		$format_label = borgodasso_product_sale_format_label( $catalog_product );
		$price_html   = borgodasso_product_catalog_price_html( $catalog_product );
		$image_id     = (int) $catalog_product->get_image_id();
		$image_alt    = $image_id ? (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '';
		if ( '' === $image_alt ) {
			$image_alt = $title;
		}
		?>
		<li class="product-catalog__item">
			<a class="product-card" href="<?php echo esc_url( $permalink ); ?>">
				<span class="product-card__media">
					<?php if ( $image_id ) : ?>
						<?php
						echo wp_get_attachment_image(
							$image_id,
							'large',
							false,
							array(
								'class'    => 'product-card__img',
								'alt'      => $image_alt,
								'loading'  => 'lazy',
								'decoding' => 'async',
								'sizes'    => '(min-width: 1024px) 25vw, 50vw',
							)
						);
						?>
					<?php else : ?>
						<img
							class="product-card__img"
							src="<?php echo esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ); ?>"
							alt=""
							loading="lazy"
							decoding="async"
						/>
					<?php endif; ?>
				</span>

				<span class="product-card__title"><?php echo esc_html( $title ); ?></span>

				<?php if ( '' !== $format_label ) : ?>
					<span class="product-card__format"><?php echo esc_html( $format_label ); ?></span>
				<?php endif; ?>

				<?php if ( '' !== $price_html ) : ?>
					<span class="product-card__price"><?php echo wp_kses_post( $price_html ); ?></span>
				<?php endif; ?>
			</a>
		</li>
	<?php endforeach; ?>
</ul>

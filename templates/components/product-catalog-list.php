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

		$hover_id = 0;
		foreach ( $catalog_product->get_gallery_image_ids() as $gallery_id ) {
			$gallery_id = (int) $gallery_id;
			if ( $gallery_id > 0 && $gallery_id !== $image_id ) {
				$hover_id = $gallery_id;
				break;
			}
		}

		$category_slug = borgodasso_product_shop_category_slug( $catalog_product );

		$media_class = 'product-card__media';
		if ( $hover_id > 0 ) {
			$media_class .= ' product-card__media--swap';
		}
		?>
		<li class="product-catalog__item"<?php echo '' !== $category_slug ? ' data-shop-category="' . esc_attr( $category_slug ) . '"' : ''; ?>>
			<a class="product-card" href="<?php echo esc_url( $permalink ); ?>">
				<span class="<?php echo esc_attr( $media_class ); ?>">
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

					<?php if ( $hover_id > 0 ) : ?>
						<?php
						echo wp_get_attachment_image(
							$hover_id,
							'large',
							false,
							array(
								'class'       => 'product-card__img product-card__img--swap',
								'alt'         => '',
								'aria-hidden' => 'true',
								'loading'     => 'lazy',
								'decoding'    => 'async',
								'sizes'       => '(min-width: 1024px) 25vw, 50vw',
							)
						);
						?>
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

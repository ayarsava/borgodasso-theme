<?php
/**
 * Single Product Image — featured + gallery for the PDP split layout.
 *
 * Markup keeps featured and gallery as siblings so mobile can reorder
 * (featured → summary → gallery) via CSS.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 10.5.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$featured_id = 0;
$gallery_ids = array();

if ( $product instanceof WC_Product ) {
	$featured_id = (int) $product->get_image_id();

	foreach ( $product->get_gallery_image_ids() as $gallery_id ) {
		$gallery_id = (int) $gallery_id;
		if ( $gallery_id > 0 && $gallery_id !== $featured_id ) {
			$gallery_ids[] = $gallery_id;
		}
	}
}

/**
 * Render one product image figure.
 *
 * @param int    $image_id Attachment ID (0 = placeholder).
 * @param string $alt      Alt text.
 * @param bool   $is_main  Whether this is the featured image.
 */
$borgodasso_render_figure = static function ( int $image_id, string $alt, bool $is_main = false ): void {
	?>
	<figure class="product-pdp__figure">
		<?php if ( $image_id > 0 ) : ?>
			<?php
			echo wp_get_attachment_image(
				$image_id,
				'full',
				false,
				array(
					'class'    => 'product-pdp__img' . ( $is_main ? ' wp-post-image' : '' ),
					'alt'      => $alt,
					'loading'  => $is_main ? 'eager' : 'lazy',
					'decoding' => 'async',
					'sizes'    => '(min-width: 1024px) 55vw, 100vw',
				)
			);
			?>
		<?php else : ?>
			<img
				class="product-pdp__img<?php echo $is_main ? ' wp-post-image' : ''; ?>"
				src="<?php echo esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ); ?>"
				alt="<?php echo esc_attr( $alt ); ?>"
			/>
		<?php endif; ?>
	</figure>
	<?php
};
?>
<div class="product-pdp__featured">
	<?php
	$featured_alt = '';
	if ( $featured_id > 0 ) {
		$featured_alt = (string) get_post_meta( $featured_id, '_wp_attachment_image_alt', true );
	}
	if ( '' === $featured_alt && $product instanceof WC_Product ) {
		$featured_alt = $product->get_name();
	}
	if ( '' === $featured_alt ) {
		$featured_alt = __( 'Awaiting product image', 'borgodasso' );
	}
	$borgodasso_render_figure( $featured_id, $featured_alt, true );
	?>
</div>

<?php if ( array() !== $gallery_ids ) : ?>
	<div class="product-pdp__gallery">
		<?php foreach ( $gallery_ids as $gallery_id ) : ?>
			<?php
			$alt = (string) get_post_meta( $gallery_id, '_wp_attachment_image_alt', true );
			if ( '' === $alt && $product instanceof WC_Product ) {
				$alt = $product->get_name();
			}
			$borgodasso_render_figure( $gallery_id, $alt, false );
			?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

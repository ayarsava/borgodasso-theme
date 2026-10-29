<?php
/**
 * Mobile category filter for the main shop page.
 *
 * @package Borgodasso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = borgodasso_shop_nav_items();

if ( count( $items ) < 2 ) {
	return;
}
?>
<nav class="shop-nav" data-shop-nav aria-label="<?php echo esc_attr__( 'Categorie', 'borgodasso' ); ?>">
	<ul class="shop-nav__list">
		<?php foreach ( $items as $item ) : ?>
			<?php $is_all = 'all' === $item['slug']; ?>
			<li class="shop-nav__entry">
				<button
					type="button"
					class="shop-nav__item<?php echo $is_all ? ' is-active' : ''; ?>"
					data-shop-filter="<?php echo esc_attr( $item['slug'] ); ?>"
					aria-pressed="<?php echo $is_all ? 'true' : 'false'; ?>">
					<?php echo esc_html( $item['label'] ); ?>
				</button>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

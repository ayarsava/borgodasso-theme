<?php
/**
 * My Account navigation
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 9.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_account_navigation' );
?>

<nav class="account-nav woocommerce-MyAccount-navigation" aria-label="<?php esc_attr_e( 'Account', 'borgodasso' ); ?>">
	<h2 class="account-nav__title"><?php echo esc_html__( 'Account', 'borgodasso' ); ?></h2>
	<ul class="account-nav__list">
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
			<li class="account-nav__item <?php echo esc_attr( wc_get_account_menu_item_classes( $endpoint ) ); ?>">
				<a
					class="account-nav__link"
					href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"
					<?php echo wc_is_current_account_menu_item( $endpoint ) ? 'aria-current="page"' : ''; ?>
				>
					<span class="account-nav__label"><?php echo esc_html( $label ); ?></span>
					<?php
					borgodasso_template(
						'objects/svg',
						array(
							'name'  => 'arrow',
							'class' => 'account-nav__icon',
						)
					);
					?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>

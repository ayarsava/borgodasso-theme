<?php
/**
 * My Account page
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;

$logged_in = is_user_logged_in();
$is_hub    = $logged_in && function_exists( 'borgodasso_is_account_hub' ) && borgodasso_is_account_hub();

$account_classes = array( 'account' );
if ( $logged_in ) {
	$account_classes[] = 'account--logged-in';
} else {
	$account_classes[] = 'account--logged-out';
}
if ( $is_hub ) {
	$account_classes[] = 'account--hub';
}
?>
<div class="<?php echo esc_attr( implode( ' ', $account_classes ) ); ?>">
	<div class="account__inner">
		<?php if ( $logged_in ) : ?>
			<?php
			/**
			 * My Account navigation.
			 *
			 * @since 2.6.0
			 */
			do_action( 'woocommerce_account_navigation' );
			?>
		<?php endif; ?>

		<?php if ( ! $is_hub ) : ?>
			<div class="account__content woocommerce-MyAccount-content">
				<?php
				/**
				 * My Account content.
				 *
				 * @since 2.6.0
				 */
				do_action( 'woocommerce_account_content' );
				?>
			</div>
		<?php endif; ?>
	</div>
</div>

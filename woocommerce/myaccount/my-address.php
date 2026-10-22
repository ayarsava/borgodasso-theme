<?php
/**
 * My Addresses
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();

if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing'  => __( 'Indirizzo di fatturazione', 'borgodasso' ),
			'shipping' => __( 'Indirizzo di spedizione', 'borgodasso' ),
		),
		$customer_id
	);
} else {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing' => __( 'Indirizzo di fatturazione', 'borgodasso' ),
		),
		$customer_id
	);
}
?>

<div class="woocommerce-Addresses addresses">
	<?php foreach ( $get_addresses as $name => $address_title ) : ?>
		<?php
		$address  = wc_get_account_formatted_address( $name );
		$title_id = 'account-address-' . $name . '-title';
		?>

		<section
			class="woocommerce-Address account-section"
			aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
		>
			<header class="woocommerce-Address-title title">
				<h2 id="<?php echo esc_attr( $title_id ); ?>" class="account-section__title"><?php echo esc_html( $address_title ); ?></h2>
			</header>

			<address>
				<?php
				echo $address
					? wp_kses_post( $address )
					: esc_html__( 'Non hai ancora impostato questo indirizzo.', 'borgodasso' );

				/**
				 * Used to output content after core address fields.
				 *
				 * @param string $name Address type.
				 * @since 8.7.0
				 */
				do_action( 'woocommerce_my_account_after_my_address', $name );
				?>
			</address>

			<p class="account-form__actions">
				<a
					href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', $name ) ); ?>"
					class="<?php echo esc_attr( borgodasso_account_button_class( 'edit' ) ); ?>"
				>
					<?php
					echo $address
						? esc_html(
							sprintf(
								/* translators: %s: address title (billing/shipping). */
								__( 'Modifica %s', 'borgodasso' ),
								$address_title
							)
						)
						: esc_html(
							sprintf(
								/* translators: %s: address title (billing/shipping). */
								__( 'Aggiungi %s', 'borgodasso' ),
								$address_title
							)
						);
					?>
				</a>
			</p>
		</section>
	<?php endforeach; ?>
</div>

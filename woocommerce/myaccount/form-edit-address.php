<?php
/**
 * Edit address form
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$page_title = ( 'billing' === $load_address )
	? __( 'Indirizzo di fatturazione', 'borgodasso' )
	: __( 'Indirizzo di spedizione', 'borgodasso' );

/**
 * Hook: woocommerce_before_edit_account_address_form.
 */
do_action( 'woocommerce_before_edit_account_address_form' );
?>

<?php if ( ! $load_address ) : ?>
	<?php wc_get_template( 'myaccount/my-address.php' ); ?>
<?php else : ?>

	<form method="post" class="account-form" novalidate>

		<section class="account-section" aria-labelledby="account-edit-address-title">
			<h2 id="account-edit-address-title" class="account-section__title">
				<?php echo esc_html( apply_filters( 'woocommerce_my_account_edit_address_title', $page_title, $load_address ) ); ?>
			</h2>

			<div class="woocommerce-address-fields">
				<?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

				<div class="woocommerce-address-fields__field-wrapper">
					<?php
					foreach ( $address as $key => $field ) {
						woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ) );
					}
					?>
				</div>

				<?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

				<p class="account-form__actions">
					<button
						type="submit"
						class="<?php echo esc_attr( borgodasso_account_button_class() ); ?>"
						name="save_address"
						value="<?php esc_attr_e( 'Salva indirizzo', 'borgodasso' ); ?>"
					>
						<?php echo esc_html__( 'Salva indirizzo', 'borgodasso' ); ?>
					</button>
					<?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
					<input type="hidden" name="action" value="edit_address" />
				</p>
			</div>
		</section>

	</form>

<?php endif; ?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>

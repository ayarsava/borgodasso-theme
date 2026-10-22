<?php
/**
 * Lost password form
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 9.2.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_lost_password_form' );
?>

<div class="account account--logged-out">
	<div class="account__inner">
		<div class="account__content">
			<section class="account-section account-auth__lost" aria-labelledby="account-lost-title">
				<h2 id="account-lost-title" class="account-section__title"><?php echo esc_html__( 'Password dimenticata', 'borgodasso' ); ?></h2>

				<form method="post" class="woocommerce-ResetPassword lost_reset_password account-form">

					<p class="account-form__note"><?php echo esc_html__( 'Inserisci il tuo username o indirizzo email. Riceverai un link per creare una nuova password.', 'borgodasso' ); ?></p>

					<p class="account-field form-row">
						<label class="account-field__label" for="user_login"><?php echo esc_html__( 'Email', 'borgodasso' ); ?></label>
						<input class="account-field__input woocommerce-Input input-text" type="text" name="user_login" id="user_login" autocomplete="username" required aria-required="true" />
					</p>

					<?php do_action( 'woocommerce_lostpassword_form' ); ?>

					<p class="account-form__actions">
						<input type="hidden" name="wc_reset_password" value="true" />
						<button type="submit" class="<?php echo esc_attr( borgodasso_account_button_class() ); ?>" value="<?php esc_attr_e( 'Reimposta password', 'borgodasso' ); ?>">
							<?php echo esc_html__( 'Reimposta password', 'borgodasso' ); ?>
						</button>
					</p>

					<?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>

				</form>
			</section>
		</div>
	</div>
</div>

<?php
do_action( 'woocommerce_after_lost_password_form' );

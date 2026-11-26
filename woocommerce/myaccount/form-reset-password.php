<?php
/**
 * Lost password reset form — set a new password.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 9.2.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_reset_password_form' );

$min_length = borgodasso_account_password_min_length();
?>

<div class="account account--logged-out">
	<div class="account__inner">
		<div class="account__content">
			<section class="account-section account-auth__reset" aria-labelledby="account-reset-title">
				<h2 id="account-reset-title" class="account-section__title"><?php echo esc_html__( 'Reimposta la password', 'borgodasso' ); ?></h2>

				<form method="post" class="woocommerce-ResetPassword lost_reset_password account-form">

					<p class="account-form__note"><?php echo esc_html__( 'Inserisci una nuova password qui sotto.', 'borgodasso' ); ?></p>

					<p class="account-field form-row account-field--password">
						<label class="account-field__label" for="password_1"><?php echo esc_html__( 'Nuova password', 'borgodasso' ); ?></label>
						<span class="account-field__control">
							<input
								type="password"
								class="account-field__input woocommerce-Input input-text"
								name="password_1"
								id="password_1"
								autocomplete="new-password"
								required
								aria-required="true"
								minlength="<?php echo esc_attr( (string) $min_length ); ?>"
								data-account-password
								data-account-password-min
								aria-describedby="password_1_error"
							/>
							<button
								type="button"
								class="account-field__toggle"
								data-account-password-toggle
								data-label-show="<?php esc_attr_e( 'Mostra password', 'borgodasso' ); ?>"
								data-label-hide="<?php esc_attr_e( 'Nascondi password', 'borgodasso' ); ?>"
								aria-label="<?php esc_attr_e( 'Mostra password', 'borgodasso' ); ?>"
							>
								<span class="account-field__toggle-icon account-field__toggle-icon--masked" aria-hidden="true">
									<?php
									borgodasso_template(
										'objects/svg',
										array(
											'name'  => 'hide',
											'class' => '',
										)
									);
									?>
								</span>
								<span class="account-field__toggle-icon account-field__toggle-icon--visible" aria-hidden="true">
									<?php
									borgodasso_template(
										'objects/svg',
										array(
											'name'  => 'see',
											'class' => '',
										)
									);
									?>
								</span>
							</button>
						</span>
						<span class="account-field__error" id="password_1_error" data-account-password-error hidden>
							<?php echo esc_html( borgodasso_account_password_min_message() ); ?>
						</span>
					</p>

					<input type="hidden" name="password_2" id="password_2" value="" autocomplete="new-password" data-account-password-confirm />
					<input type="hidden" name="reset_key" value="<?php echo esc_attr( $args['key'] ); ?>" />
					<input type="hidden" name="reset_login" value="<?php echo esc_attr( $args['login'] ); ?>" />

					<?php do_action( 'woocommerce_resetpassword_form' ); ?>

					<p class="account-form__actions">
						<input type="hidden" name="wc_reset_password" value="true" />
						<button type="submit" class="<?php echo esc_attr( borgodasso_account_button_class() ); ?>" value="<?php esc_attr_e( 'Salva', 'borgodasso' ); ?>">
							<?php echo esc_html__( 'Salva', 'borgodasso' ); ?>
						</button>
					</p>

					<?php wp_nonce_field( 'reset_password', 'woocommerce-reset-password-nonce' ); ?>

				</form>
			</section>
		</div>
	</div>
</div>

<?php
do_action( 'woocommerce_after_reset_password_form' );

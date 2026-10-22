<?php
/**
 * Login / Register forms (Accedi mockup).
 *
 * Login is the default My Account view when logged out.
 * Register: /my-account/?action=register
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 9.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_customer_login_form' );

$registration_enabled = function_exists( 'borgodasso_account_registration_enabled' )
	? borgodasso_account_registration_enabled()
	: ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) );

$is_register = function_exists( 'borgodasso_is_account_register_view' )
	? borgodasso_is_account_register_view()
	: ( isset( $_GET['action'] ) && 'register' === $_GET['action'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$redirect = function_exists( 'borgodasso_account_requested_redirect' ) ? borgodasso_account_requested_redirect() : '';

$login_url    = function_exists( 'borgodasso_account_login_url' ) ? borgodasso_account_login_url( $redirect ) : wc_get_page_permalink( 'myaccount' );
$register_url = function_exists( 'borgodasso_account_register_url' ) ? borgodasso_account_register_url( $redirect ) : add_query_arg( 'action', 'register', wc_get_page_permalink( 'myaccount' ) );

$google_url     = function_exists( 'borgodasso_google_login_url' ) ? borgodasso_google_login_url() : '';
$google_enabled = '' !== $google_url;

$username_value   = ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? wp_unslash( $_POST['username'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
$email_value      = ( ! empty( $_POST['email'] ) && is_string( $_POST['email'] ) ) ? wp_unslash( $_POST['email'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
$first_name_value = ( ! empty( $_POST['account_first_name'] ) && is_string( $_POST['account_first_name'] ) ) ? wp_unslash( $_POST['account_first_name'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
$last_name_value  = ( ! empty( $_POST['account_last_name'] ) && is_string( $_POST['account_last_name'] ) ) ? wp_unslash( $_POST['account_last_name'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
?>

<div class="account account--logged-out">
	<div class="account__inner">
		<div class="account__content">

			<div class="account-auth" id="customer_login">

				<?php if ( $is_register && $registration_enabled ) : ?>

					<section class="account-section account-auth__register" aria-labelledby="account-register-title">
						<h2 id="account-register-title" class="account-section__title"><?php echo esc_html__( 'Crea un account', 'borgodasso' ); ?></h2>

						<form method="post" class="woocommerce-form woocommerce-form-register register account-form" <?php do_action( 'woocommerce_register_form_tag' ); ?> >

							<?php do_action( 'woocommerce_register_form_start' ); ?>

							<p class="account-field form-row">
								<label class="account-field__label" for="reg_account_first_name"><?php echo esc_html__( 'Nome', 'borgodasso' ); ?></label>
								<input type="text" class="account-field__input woocommerce-Input input-text" name="account_first_name" id="reg_account_first_name" autocomplete="given-name" placeholder="<?php esc_attr_e( 'Nome', 'borgodasso' ); ?>" value="<?php echo esc_attr( $first_name_value ); ?>" required aria-required="true" />
							</p>

							<p class="account-field form-row">
								<label class="account-field__label" for="reg_account_last_name"><?php echo esc_html__( 'Cognome', 'borgodasso' ); ?></label>
								<input type="text" class="account-field__input woocommerce-Input input-text" name="account_last_name" id="reg_account_last_name" autocomplete="family-name" placeholder="<?php esc_attr_e( 'Cognome', 'borgodasso' ); ?>" value="<?php echo esc_attr( $last_name_value ); ?>" required aria-required="true" />
							</p>

							<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
								<p class="account-field form-row">
									<label class="account-field__label" for="reg_username"><?php echo esc_html__( 'Username', 'borgodasso' ); ?></label>
									<input type="text" class="account-field__input woocommerce-Input input-text" name="username" id="reg_username" autocomplete="username" placeholder="<?php esc_attr_e( 'Username', 'borgodasso' ); ?>" value="<?php echo esc_attr( $username_value ); ?>" required aria-required="true" />
								</p>
							<?php endif; ?>

							<p class="account-field form-row">
								<label class="account-field__label" for="reg_email"><?php echo esc_html__( 'Email', 'borgodasso' ); ?></label>
								<input type="email" class="account-field__input woocommerce-Input input-text" name="email" id="reg_email" autocomplete="email" placeholder="<?php esc_attr_e( 'Email', 'borgodasso' ); ?>" value="<?php echo esc_attr( $email_value ); ?>" required aria-required="true" />
							</p>

							<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
								<p class="account-field form-row account-field--password">
									<label class="account-field__label" for="reg_password"><?php echo esc_html__( 'Password', 'borgodasso' ); ?></label>
									<span class="account-field__control">
										<input type="password" class="account-field__input woocommerce-Input input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" data-account-password />
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
														'name' => 'hide',
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
														'name' => 'see',
														'class' => '',
													)
												);
												?>
											</span>
										</button>
									</span>
								</p>
							<?php else : ?>
								<p class="account-form__note"><?php echo esc_html__( 'Un link per impostare la password verrà inviato al tuo indirizzo email.', 'borgodasso' ); ?></p>
							<?php endif; ?>

							<?php do_action( 'woocommerce_register_form' ); ?>

							<p class="account-form__actions">
								<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
								<?php if ( '' !== $redirect ) : ?>
									<input type="hidden" name="redirect" value="<?php echo esc_url( $redirect ); ?>" />
								<?php endif; ?>
								<button type="submit" class="<?php echo esc_attr( borgodasso_account_button_class( 'woocommerce-form-register__submit' ) ); ?>" name="register" value="<?php esc_attr_e( 'Crea un account', 'borgodasso' ); ?>">
									<?php echo esc_html__( 'Crea un account', 'borgodasso' ); ?>
								</button>
							</p>

							<p class="account-form__links account-form__links--single">
								<a class="<?php echo esc_attr( borgodasso_account_button_class( 'account-button--outline' ) ); ?>" href="<?php echo esc_url( $login_url ); ?>">
									<?php echo esc_html__( 'Hai già un account? Accedi', 'borgodasso' ); ?>
								</a>
							</p>

							<?php do_action( 'woocommerce_register_form_end' ); ?>

						</form>
					</section>

				<?php else : ?>

					<section class="account-section account-auth__login" aria-labelledby="account-login-title">
						<h2 id="account-login-title" class="account-section__title"><?php echo esc_html__( 'Accedi', 'borgodasso' ); ?></h2>

						<form class="woocommerce-form woocommerce-form-login login account-form" method="post" novalidate>

							<?php do_action( 'woocommerce_login_form_start' ); ?>

							<p class="account-field form-row">
								<label class="account-field__label" for="username"><?php echo esc_html__( 'Email', 'borgodasso' ); ?></label>
								<input type="text" class="account-field__input woocommerce-Input input-text" name="username" id="username" autocomplete="username" placeholder="<?php esc_attr_e( 'Email', 'borgodasso' ); ?>" value="<?php echo esc_attr( $username_value ); ?>" required aria-required="true" />
							</p>

							<p class="account-field form-row account-field--password">
								<label class="account-field__label" for="password"><?php echo esc_html__( 'Password', 'borgodasso' ); ?></label>
								<span class="account-field__control">
									<input class="account-field__input woocommerce-Input input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" data-account-password />
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
													'name' => 'hide',
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
													'name' => 'see',
													'class' => '',
												)
											);
											?>
										</span>
									</button>
								</span>
							</p>

							<?php do_action( 'woocommerce_login_form' ); ?>

							<p class="account-form__actions">
								<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
								<?php if ( '' !== $redirect ) : ?>
									<input type="hidden" name="redirect" value="<?php echo esc_url( $redirect ); ?>" />
								<?php endif; ?>
								<button type="submit" class="<?php echo esc_attr( borgodasso_account_button_class( 'woocommerce-form-login__submit' ) ); ?>" name="login" value="<?php esc_attr_e( 'Accedi', 'borgodasso' ); ?>">
									<?php echo esc_html__( 'Accedi', 'borgodasso' ); ?>
								</button>
							</p>

							<p class="account-form__links">
								<?php if ( $registration_enabled ) : ?>
									<a href="<?php echo esc_url( $register_url ); ?>"><?php echo esc_html__( 'Crea un account', 'borgodasso' ); ?></a>
								<?php else : ?>
									<span></span>
								<?php endif; ?>
								<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php echo esc_html__( 'Password dimenticata?', 'borgodasso' ); ?></a>
							</p>

							<?php do_action( 'woocommerce_login_form_end' ); ?>

						</form>

						<div class="account-form__divider" role="separator">
							<span><?php echo esc_html__( 'O se preferisci', 'borgodasso' ); ?></span>
						</div>

						<?php if ( $google_enabled ) : ?>
							<a class="<?php echo esc_attr( borgodasso_account_button_class( 'account-button--outline account-button--google' ) ); ?>" href="<?php echo esc_url( $google_url ); ?>">
								<?php
								borgodasso_template(
									'objects/svg',
									array(
										'name'  => 'icon-google',
										'class' => 'account-button__icon',
									)
								);
								?>
								<span><?php echo esc_html__( 'Accedi con Google', 'borgodasso' ); ?></span>
							</a>
						<?php else : ?>
							<?php // Stub: wire URL via filter `borgodasso_google_login_url` (see woocommerce/README.md). ?>
							<button
								type="button"
								class="<?php echo esc_attr( borgodasso_account_button_class( 'account-button--outline account-button--google account-button--pending' ) ); ?>"
								disabled
								aria-disabled="true"
								title="<?php esc_attr_e( 'Accesso Google in arrivo', 'borgodasso' ); ?>"
							>
								<?php
								borgodasso_template(
									'objects/svg',
									array(
										'name'  => 'icon-google',
										'class' => 'account-button__icon',
									)
								);
								?>
								<span><?php echo esc_html__( 'Accedi con Google', 'borgodasso' ); ?></span>
							</button>
						<?php endif; ?>
					</section>

				<?php endif; ?>

			</div>

		</div>
	</div>
</div>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>

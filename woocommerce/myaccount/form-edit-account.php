<?php
/**
 * Edit account form — Profilo + Modifica la password.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 11.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook - woocommerce_before_edit_account_form.
 *
 * @since 2.6.0
 */
do_action( 'woocommerce_before_edit_account_form' );

$phone = (string) get_user_meta( get_current_user_id(), 'billing_phone', true );
?>

<form class="woocommerce-EditAccountForm edit-account account-form" action="" method="post" <?php do_action( 'woocommerce_edit_account_form_tag' ); ?> >

	<?php do_action( 'woocommerce_edit_account_form_start' ); ?>

	<section class="account-section" aria-labelledby="account-profile-title">
		<h2 id="account-profile-title" class="account-section__title"><?php echo esc_html__( 'Profilo', 'borgodasso' ); ?></h2>

		<p class="account-field form-row">
			<label class="account-field__label" for="account_first_name"><?php echo esc_html__( 'Nome', 'borgodasso' ); ?></label>
			<input type="text" class="account-field__input woocommerce-Input input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>" aria-required="true" required />
		</p>

		<p class="account-field form-row">
			<label class="account-field__label" for="account_last_name"><?php echo esc_html__( 'Cognome', 'borgodasso' ); ?></label>
			<input type="text" class="account-field__input woocommerce-Input input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr( $user->last_name ); ?>" aria-required="true" required />
		</p>

		<p class="account-field form-row">
			<label class="account-field__label" for="account_email"><?php echo esc_html__( 'Email', 'borgodasso' ); ?></label>
			<input type="email" class="account-field__input woocommerce-Input input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" aria-required="true" required />
		</p>

		<input type="hidden" name="account_display_name" id="account_display_name" value="<?php echo esc_attr( $user->display_name ); ?>" />

		<p class="account-field form-row">
			<label class="account-field__label" for="account_phone"><?php echo esc_html__( 'Numero di telefono', 'borgodasso' ); ?></label>
			<input type="tel" class="account-field__input woocommerce-Input input-text" name="account_phone" id="account_phone" autocomplete="tel" value="<?php echo esc_attr( $phone ); ?>" />
		</p>

		<?php
		/**
		 * Hook where additional fields should be rendered.
		 *
		 * @since 8.7.0
		 */
		do_action( 'woocommerce_edit_account_form_fields' );
		?>

		<p class="account-form__actions">
			<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
			<button type="submit" class="<?php echo esc_attr( borgodasso_account_button_class() ); ?>" name="save_account_details" value="<?php esc_attr_e( 'Salva le modifiche', 'borgodasso' ); ?>">
				<?php echo esc_html__( 'Salva le modifiche', 'borgodasso' ); ?>
			</button>
			<input type="hidden" name="action" value="save_account_details" />
		</p>
	</section>

	<section class="account-section account-section--password" aria-labelledby="account-password-title">
		<h2 id="account-password-title" class="account-section__title"><?php echo esc_html__( 'Modifica la password', 'borgodasso' ); ?></h2>

		<p class="account-field form-row account-field--password">
			<label class="account-field__label" for="password_current"><?php echo esc_html__( 'Password attuale', 'borgodasso' ); ?></label>
			<span class="account-field__control">
				<input type="password" class="account-field__input woocommerce-Input input-text" name="password_current" id="password_current" autocomplete="current-password" data-account-password />
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
		</p>

		<p class="account-field form-row account-field--password">
			<label class="account-field__label" for="password_1"><?php echo esc_html__( 'Nuova password', 'borgodasso' ); ?></label>
			<span class="account-field__control">
				<input type="password" class="account-field__input woocommerce-Input input-text" name="password_1" id="password_1" autocomplete="new-password" data-account-password />
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
		</p>

		<input type="hidden" name="password_2" id="password_2" value="" autocomplete="new-password" data-account-password-confirm />

		<p class="account-form__actions">
			<button type="submit" class="<?php echo esc_attr( borgodasso_account_button_class() ); ?>" name="save_account_details" value="<?php esc_attr_e( 'Modifica la password', 'borgodasso' ); ?>" data-account-password-submit>
				<?php echo esc_html__( 'Modifica la password', 'borgodasso' ); ?>
			</button>
		</p>
	</section>

	<?php
	/**
	 * My Account edit account form.
	 *
	 * @since 2.6.0
	 */
	do_action( 'woocommerce_edit_account_form' );
	?>

	<?php do_action( 'woocommerce_edit_account_form_end' ); ?>
</form>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>

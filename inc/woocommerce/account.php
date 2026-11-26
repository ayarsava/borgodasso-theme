<?php
/**
 * WooCommerce My Account — layout, menu, and form helpers.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Body class for account pages.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function borgodasso_account_body_class( array $classes ): array {
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		$classes[] = 'is-account-page';

		if ( is_user_logged_in() ) {
			$classes[] = 'is-account-logged-in';
		} else {
			$classes[] = 'is-account-logged-out';
		}
	}

	return $classes;
}
add_filter( 'body_class', 'borgodasso_account_body_class' );

/**
 * Account sidebar menu: Profilo, Indirizzi, Ordini, Log out.
 *
 * @param array<string, string> $items Menu items.
 * @return array<string, string>
 */
function borgodasso_account_menu_items( array $items ): array {
	unset( $items );

	return array(
		'edit-account'    => __( 'Profilo', 'borgodasso' ),
		'edit-address'    => __( 'Indirizzi', 'borgodasso' ),
		'orders'          => __( 'Ordini', 'borgodasso' ),
		'customer-logout' => __( 'Log out', 'borgodasso' ),
	);
}
add_filter( 'woocommerce_account_menu_items', 'borgodasso_account_menu_items' );

/**
 * Whether the visitor is on the bare My Account hub (/my-account/, no endpoint).
 */
function borgodasso_is_account_hub(): bool {
	return function_exists( 'is_account_page' )
		&& is_account_page()
		&& is_user_logged_in()
		&& ! is_wc_endpoint_url();
}

/**
 * Mark the account hub for layout styles.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function borgodasso_account_hub_body_class( array $classes ): array {
	if ( borgodasso_is_account_hub() ) {
		$classes[] = 'is-account-hub';
	}

	return $classes;
}
add_filter( 'body_class', 'borgodasso_account_hub_body_class' );

/**
 * Save billing phone from the edit-account form.
 *
 * @param int $user_id User ID.
 */
function borgodasso_account_save_phone( int $user_id ): void {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WC verifies save_account_details nonce.
	if ( ! isset( $_POST['account_phone'] ) ) {
		return;
	}

	$phone = sanitize_text_field( wp_unslash( (string) $_POST['account_phone'] ) );
	// phpcs:enable WordPress.Security.NonceVerification.Missing
	update_user_meta( $user_id, 'billing_phone', $phone );
}
add_action( 'woocommerce_save_account_details', 'borgodasso_account_save_phone' );

/**
 * Button class list for account forms.
 *
 * @param string $extra Extra classes.
 */
function borgodasso_account_button_class( string $extra = '' ): string {
	$classes = array( 'account-button', 'woocommerce-Button', 'button' );
	$theme   = function_exists( 'wc_wp_theme_get_element_class_name' ) ? wc_wp_theme_get_element_class_name( 'button' ) : '';

	if ( is_string( $theme ) && '' !== $theme ) {
		$classes[] = $theme;
	}

	if ( '' !== $extra ) {
		$classes[] = $extra;
	}

	return implode( ' ', array_filter( $classes ) );
}

/**
 * Keep customer registration available on My Account (theme controls the split Accedi / Registrati views).
 *
 * @return string
 */
function borgodasso_force_myaccount_registration(): string {
	return 'yes';
}
add_filter( 'pre_option_woocommerce_enable_myaccount_registration', 'borgodasso_force_myaccount_registration' );

/**
 * Generate username from email on registration.
 *
 * @return string
 */
function borgodasso_force_registration_generate_username(): string {
	return 'yes';
}
add_filter( 'pre_option_woocommerce_registration_generate_username', 'borgodasso_force_registration_generate_username' );

/**
 * Let customers set a password on the Registrati form (no emailed set-password link).
 *
 * @return string
 */
function borgodasso_force_registration_generate_password(): string {
	return 'no';
}
add_filter( 'pre_option_woocommerce_registration_generate_password', 'borgodasso_force_registration_generate_password' );

/**
 * Whether My Account registration is enabled.
 */
function borgodasso_account_registration_enabled(): bool {
	return 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
}

/**
 * Validated return URL from ?redirect= / ?redirect_to= (after login/register).
 */
function borgodasso_account_requested_redirect(): string {
	$raw = '';

	// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public return path; validated below.
	if ( isset( $_GET['redirect'] ) && is_string( $_GET['redirect'] ) ) {
		$raw = wp_unslash( $_GET['redirect'] );
	} elseif ( isset( $_GET['redirect_to'] ) && is_string( $_GET['redirect_to'] ) ) {
		$raw = wp_unslash( $_GET['redirect_to'] );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( '' === $raw ) {
		return '';
	}

	return (string) wp_validate_redirect( $raw, '' );
}

/**
 * Login URL (My Account, no register query arg).
 *
 * @param string $redirect Optional absolute URL to return to after login.
 */
function borgodasso_account_login_url( string $redirect = '' ): string {
	$url = (string) wc_get_page_permalink( 'myaccount' );

	if ( '' === $redirect ) {
		return $url;
	}

	return (string) add_query_arg( 'redirect', $redirect, $url );
}

/**
 * Register URL (My Account + ?action=register).
 *
 * @param string $redirect Optional absolute URL to return to after register.
 */
function borgodasso_account_register_url( string $redirect = '' ): string {
	return (string) add_query_arg( 'action', 'register', borgodasso_account_login_url( $redirect ) );
}

/**
 * Whether the logged-out My Account view should show the register form.
 */
function borgodasso_is_account_register_view(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- view switch only.
	if ( isset( $_GET['action'] ) && 'register' === $_GET['action'] ) {
		return true;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC register POST; keep form after validation errors.
	if ( ! empty( $_POST['register'] ) || ! empty( $_POST['woocommerce-register-nonce'] ) ) {
		return true;
	}

	return false;
}

/**
 * Google OAuth login URL.
 *
 * Empty until BORGODASSO_GOOGLE_CLIENT_ID + BORGODASSO_GOOGLE_CLIENT_SECRET
 * are defined (see `inc/woocommerce/google-login.php` and woocommerce/README.md).
 *
 * @return string Absolute URL, or empty string when not ready.
 */
function borgodasso_google_login_url(): string {
	$url = apply_filters( 'borgodasso_google_login_url', '' );

	return is_string( $url ) ? trim( $url ) : '';
}

/**
 * Hide WooCommerce registration privacy notice on the Registrati form.
 */
function borgodasso_remove_registration_privacy_text(): void {
	remove_action( 'woocommerce_register_form', 'wc_registration_privacy_policy_text', 20 );
}
add_action( 'init', 'borgodasso_remove_registration_privacy_text', 20 );

/**
 * Posted first/last name for registration (My Account) or checkout create-account.
 *
 * Checkout “Ricordami” has no account_* fields — names live on shipping/billing.
 *
 * @param 'first'|'last' $which Name part.
 */
function borgodasso_registration_posted_name( string $which ): string {
	$keys = 'last' === $which
		? array( 'account_last_name', 'billing_last_name', 'shipping_last_name' )
		: array( 'account_first_name', 'billing_first_name', 'shipping_first_name' );

	foreach ( $keys as $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verifies register/checkout nonce upstream.
		if ( ! isset( $_POST[ $key ] ) || ! is_string( $_POST[ $key ] ) ) {
			continue;
		}

		$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
		if ( '' !== $value ) {
			return $value;
		}
	}

	return '';
}

/**
 * Whether My Account name validation should be skipped (e.g. Google OAuth create).
 */
function borgodasso_skip_registration_name_validation(): bool {
	return (bool) apply_filters( 'borgodasso_skip_registration_name_validation', false );
}

/**
 * Require Nome / Cognome on registration.
 *
 * @param string   $username Username.
 * @param string   $email    Email.
 * @param WP_Error $errors   Errors.
 */
function borgodasso_validate_registration_names( string $username, string $email, WP_Error $errors ): void {
	unset( $username, $email );

	if ( borgodasso_skip_registration_name_validation() ) {
		return;
	}

	$first = borgodasso_registration_posted_name( 'first' );
	$last  = borgodasso_registration_posted_name( 'last' );

	if ( '' === $first ) {
		$errors->add( 'account_first_name_error', __( 'Inserisci il tuo nome.', 'borgodasso' ) );
	}

	if ( '' === $last ) {
		$errors->add( 'account_last_name_error', __( 'Inserisci il tuo cognome.', 'borgodasso' ) );
	}
}
add_action( 'woocommerce_register_post', 'borgodasso_validate_registration_names', 10, 3 );

/**
 * Persist Nome / Cognome after customer creation.
 *
 * @param int $customer_id New user ID.
 */
function borgodasso_save_registration_names( int $customer_id ): void {
	$first = borgodasso_registration_posted_name( 'first' );
	$last  = borgodasso_registration_posted_name( 'last' );

	if ( '' !== $first ) {
		update_user_meta( $customer_id, 'first_name', $first );
		update_user_meta( $customer_id, 'billing_first_name', $first );
	}

	if ( '' !== $last ) {
		update_user_meta( $customer_id, 'last_name', $last );
		update_user_meta( $customer_id, 'billing_last_name', $last );
	}

	$display = trim( $first . ' ' . $last );

	if ( '' !== $display ) {
		wp_update_user(
			array(
				'ID'           => $customer_id,
				'display_name' => $display,
			)
		);
	}
}
add_action( 'woocommerce_created_customer', 'borgodasso_save_registration_names' );

/**
 * Minimum password length for account registration / change / reset.
 */
function borgodasso_account_password_min_length(): int {
	return 10;
}

/**
 * User-facing min-length message (IT source; TranslatePress → EN).
 */
function borgodasso_account_password_min_message(): string {
	return __( 'La password deve essere lunga almeno 10 caratteri.', 'borgodasso' );
}

/**
 * Drop WooCommerce password strength meter + hint tips.
 */
function borgodasso_dequeue_password_strength_meter(): void {
	wp_dequeue_script( 'wc-password-strength-meter' );
}
add_action( 'wp_enqueue_scripts', 'borgodasso_dequeue_password_strength_meter', 100 );

/**
 * Do not enforce zxcvbn strength (length is handled separately).
 *
 * @return int
 */
function borgodasso_disable_min_password_strength(): int {
	return 0;
}
add_filter( 'woocommerce_min_password_strength', 'borgodasso_disable_min_password_strength' );

/**
 * Hide WP/WC password hint text (“at least twelve characters…”).
 *
 * @return string
 */
function borgodasso_empty_password_hint(): string {
	return '';
}
add_filter( 'password_hint', 'borgodasso_empty_password_hint' );

/**
 * @param string $password Password to check.
 */
function borgodasso_account_password_too_short( string $password ): bool {
	return strlen( $password ) < borgodasso_account_password_min_length();
}

/**
 * Registration: require ≥ 10 characters.
 *
 * @param WP_Error $errors   Errors.
 * @param string   $username Username.
 * @param string   $password Password.
 * @param string   $email    Email.
 * @return WP_Error
 */
function borgodasso_validate_registration_password_length( WP_Error $errors, string $username, string $password, string $email ): WP_Error {
	unset( $username, $email );

	if ( '' !== $password && borgodasso_account_password_too_short( $password ) ) {
		$errors->add( 'registration-error-password-length', borgodasso_account_password_min_message() );
	}

	return $errors;
}
add_filter( 'woocommerce_process_registration_errors', 'borgodasso_validate_registration_password_length', 10, 4 );

/**
 * Edit account: when a new password is posted, require ≥ 10 characters.
 *
 * @param WP_Error  $errors Errors.
 * @param WP_User   $user   User being updated.
 */
function borgodasso_validate_account_password_length( WP_Error $errors, $user ): void {
	unset( $user );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WC verifies save_account_details nonce.
	$password = isset( $_POST['password_1'] ) ? (string) wp_unslash( $_POST['password_1'] ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( '' !== $password && borgodasso_account_password_too_short( $password ) ) {
		$errors->add( 'password-length', borgodasso_account_password_min_message() );
	}
}
add_action( 'woocommerce_save_account_details_errors', 'borgodasso_validate_account_password_length', 10, 2 );

/**
 * Reset password: require ≥ 10 characters.
 *
 * @param WP_Error $errors Errors.
 * @param WP_User  $user   User resetting.
 */
function borgodasso_validate_reset_password_length( WP_Error $errors, $user ): void {
	unset( $user );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WC verifies reset_password nonce.
	$password = isset( $_POST['password_1'] ) ? (string) wp_unslash( $_POST['password_1'] ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( borgodasso_account_password_too_short( $password ) ) {
		$errors->add( 'password-length', borgodasso_account_password_min_message() );
	}
}
add_action( 'validate_password_reset', 'borgodasso_validate_reset_password_length', 10, 2 );

/**
 * Clear billing or shipping address from My Account edit-address form.
 *
 * Address type comes from the form (query vars are not ready on `wp_loaded`).
 */
function borgodasso_process_delete_address(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( empty( $_POST['delete_address'] ) ) {
		return;
	}

	if ( empty( $_POST['action'] ) || 'edit_address' !== $_POST['action'] ) {
		return;
	}

	$nonce_value = isset( $_REQUEST['woocommerce-edit-address-nonce'] )
		? (string) wp_unslash( $_REQUEST['woocommerce-edit-address-nonce'] )
		: ( isset( $_REQUEST['_wpnonce'] ) ? (string) wp_unslash( $_REQUEST['_wpnonce'] ) : '' );

	$address_type = isset( $_POST['address_type'] )
		? sanitize_title( (string) wp_unslash( $_POST['address_type'] ) )
		: '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( ! wp_verify_nonce( $nonce_value, 'woocommerce-edit_address' ) ) {
		return;
	}

	$user_id = get_current_user_id();

	if ( $user_id <= 0 || ! function_exists( 'WC' ) ) {
		return;
	}

	$address_type = wc_edit_address_i18n( $address_type, true );

	if ( ! in_array( $address_type, array( 'billing', 'shipping' ), true ) ) {
		return;
	}

	$customer = new WC_Customer( $user_id );

	// Full field set — don't rely on country locale fields (address_2 may already be unset).
	$suffixes = array(
		'first_name',
		'last_name',
		'company',
		'address_1',
		'address_2',
		'city',
		'state',
		'postcode',
		'country',
		'phone',
	);

	foreach ( $suffixes as $suffix ) {
		$key    = $address_type . '_' . $suffix;
		$setter = 'set_' . $key;

		if ( is_callable( array( $customer, $setter ) ) ) {
			$customer->{$setter}( '' );
		}

		delete_user_meta( $user_id, $key );
	}

	$customer->save();

	wc_add_notice( __( 'Indirizzo eliminato.', 'borgodasso' ), 'success' );

	wp_safe_redirect( wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ) );
	exit;
}
add_action( 'wp_loaded', 'borgodasso_process_delete_address', 19 );

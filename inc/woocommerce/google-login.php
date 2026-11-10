<?php
/**
 * Google OAuth login for My Account (Accedi con Google).
 *
 * Requires BORGODASSO_GOOGLE_CLIENT_ID + BORGODASSO_GOOGLE_CLIENT_SECRET in wp-config.
 * Optional: BORGODASSO_GOOGLE_REDIRECT_URI (defaults to /oauth/google/callback/).
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BORGODASSO_GOOGLE_OAUTH_REWRITE_VERSION = 1;
const BORGODASSO_GOOGLE_OAUTH_STATE_TTL       = 600;
const BORGODASSO_GOOGLE_OAUTH_META_SUB        = 'borgodasso_google_sub';
const BORGODASSO_GOOGLE_OAUTH_META_PICTURE    = 'borgodasso_google_picture';
const BORGODASSO_GOOGLE_OAUTH_COOKIE          = 'borgodasso_google_oauth';
const BORGODASSO_GOOGLE_OAUTH_CACHE_BYPASS    = 'wordpress_no_cache';

/**
 * Whether Google OAuth credentials are configured.
 */
function borgodasso_google_oauth_is_configured(): bool {
	return '' !== borgodasso_google_oauth_client_id()
		&& '' !== borgodasso_google_oauth_client_secret();
}

/**
 * Google OAuth client ID from wp-config.
 */
function borgodasso_google_oauth_client_id(): string {
	if ( ! defined( 'BORGODASSO_GOOGLE_CLIENT_ID' ) ) {
		return '';
	}

	$id = BORGODASSO_GOOGLE_CLIENT_ID;

	return is_string( $id ) ? trim( $id ) : '';
}

/**
 * Google OAuth client secret from wp-config.
 */
function borgodasso_google_oauth_client_secret(): string {
	if ( ! defined( 'BORGODASSO_GOOGLE_CLIENT_SECRET' ) ) {
		return '';
	}

	$secret = BORGODASSO_GOOGLE_CLIENT_SECRET;

	return is_string( $secret ) ? trim( $secret ) : '';
}

/**
 * Absolute OAuth redirect URI registered in Google Cloud Console.
 */
function borgodasso_google_oauth_redirect_uri(): string {
	if ( defined( 'BORGODASSO_GOOGLE_REDIRECT_URI' ) ) {
		$uri = BORGODASSO_GOOGLE_REDIRECT_URI;
		if ( is_string( $uri ) && '' !== trim( $uri ) ) {
			return trim( $uri );
		}
	}

	return home_url( '/oauth/google/callback/' );
}

/**
 * Absolute URL that starts the Google OAuth flow.
 *
 * @param string $redirect Optional post-login return URL (validated later).
 */
function borgodasso_google_oauth_start_url( string $redirect = '' ): string {
	$args = array(
		// Unique per render so CDN/page cache cannot reuse a start redirect + state.
		'r' => wp_generate_password( 12, false, false ),
	);

	if ( '' !== $redirect ) {
		$args['redirect'] = $redirect;
	}

	return (string) add_query_arg( $args, home_url( '/oauth/google/start/' ) );
}

/**
 * Provide the Accedi con Google URL when credentials exist.
 *
 * @param string $url Existing filter value.
 */
function borgodasso_google_oauth_filter_login_url( string $url ): string {
	if ( '' !== $url || ! borgodasso_google_oauth_is_configured() ) {
		return $url;
	}

	$redirect = function_exists( 'borgodasso_account_requested_redirect' )
		? borgodasso_account_requested_redirect()
		: '';

	return borgodasso_google_oauth_start_url( $redirect );
}
add_filter( 'borgodasso_google_login_url', 'borgodasso_google_oauth_filter_login_url' );

/**
 * Register rewrite rules for OAuth start/callback.
 */
function borgodasso_google_oauth_add_rewrite_rules(): void {
	add_rewrite_rule( '^oauth/google/start/?$', 'index.php?borgodasso_google_oauth=start', 'top' );
	add_rewrite_rule( '^oauth/google/callback/?$', 'index.php?borgodasso_google_oauth=callback', 'top' );
}
add_action( 'init', 'borgodasso_google_oauth_add_rewrite_rules' );

/**
 * Expose the OAuth query var.
 *
 * @param string[] $vars Public query vars.
 * @return string[]
 */
function borgodasso_google_oauth_query_vars( array $vars ): array {
	$vars[] = 'borgodasso_google_oauth';

	return $vars;
}
add_filter( 'query_vars', 'borgodasso_google_oauth_query_vars' );

/**
 * Flush rewrites once after rules change (or on theme switch).
 */
function borgodasso_google_oauth_maybe_flush_rewrites(): void {
	$stored = (int) get_option( 'borgodasso_google_oauth_rewrite_version', 0 );

	if ( BORGODASSO_GOOGLE_OAUTH_REWRITE_VERSION === $stored ) {
		return;
	}

	borgodasso_google_oauth_add_rewrite_rules();
	flush_rewrite_rules( false );
	update_option( 'borgodasso_google_oauth_rewrite_version', BORGODASSO_GOOGLE_OAUTH_REWRITE_VERSION, true );
}
add_action( 'init', 'borgodasso_google_oauth_maybe_flush_rewrites', 20 );

/**
 * Flush OAuth rewrites when this theme is activated.
 */
function borgodasso_google_oauth_flush_on_switch(): void {
	delete_option( 'borgodasso_google_oauth_rewrite_version' );
}
add_action( 'after_switch_theme', 'borgodasso_google_oauth_flush_on_switch' );

/**
 * Prevent CDN/page cache from storing OAuth redirects (unique state per attempt).
 */
function borgodasso_google_oauth_nocache(): void {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	nocache_headers();
	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );
}

/**
 * Handle OAuth start / callback on template_redirect.
 */
function borgodasso_google_oauth_template_redirect(): void {
	$action = get_query_var( 'borgodasso_google_oauth' );

	if ( ! is_string( $action ) || '' === $action ) {
		$action = borgodasso_google_oauth_action_from_request_uri();
	}

	if ( 'start' === $action ) {
		borgodasso_google_oauth_nocache();
		borgodasso_google_oauth_handle_start();
	}

	if ( 'callback' === $action ) {
		borgodasso_google_oauth_nocache();
		borgodasso_google_oauth_handle_callback();
	}
}
add_action( 'template_redirect', 'borgodasso_google_oauth_template_redirect', 0 );

/**
 * Fallback path match before rewrites are flushed.
 */
function borgodasso_google_oauth_action_from_request_uri(): string {
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Path only; compared to fixed strings.
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );

	if ( '' === $path ) {
		return '';
	}

	$home_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '' !== $home_path && '/' !== $home_path && str_starts_with( $path, $home_path ) ) {
		$stripped = substr( $path, strlen( rtrim( $home_path, '/' ) ) );
		$path     = ( false === $stripped || '' === $stripped ) ? '/' : $stripped;
	}

	$path = '/' . trim( $path, '/' ) . '/';

	if ( '/oauth/google/start/' === $path ) {
		return 'start';
	}

	if ( '/oauth/google/callback/' === $path ) {
		return 'callback';
	}

	return '';
}

/**
 * Begin Google OAuth: store state + redirect, send user to Google.
 */
function borgodasso_google_oauth_handle_start(): void {
	if ( ! borgodasso_google_oauth_is_configured() ) {
		borgodasso_google_oauth_fail( __( 'Accesso Google non configurato.', 'borgodasso' ) );
	}

	if ( is_user_logged_in() ) {
		wp_safe_redirect( borgodasso_google_oauth_default_return_url() );
		exit;
	}

	$redirect = function_exists( 'borgodasso_account_requested_redirect' )
		? borgodasso_account_requested_redirect()
		: '';

	$state = wp_generate_password( 32, false, false );
	borgodasso_google_oauth_persist_state( $state, $redirect );

	$params = array(
		'client_id'     => borgodasso_google_oauth_client_id(),
		'redirect_uri'  => borgodasso_google_oauth_redirect_uri(),
		'response_type' => 'code',
		'scope'         => 'openid email profile',
		'state'         => $state,
		'access_type'   => 'online',
		'prompt'        => 'select_account',
	);

	// External Google host — wp_safe_redirect would block it.
	wp_redirect( 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
	exit;
}

/**
 * Handle Google OAuth callback: exchange code, find/create user, log in.
 */
function borgodasso_google_oauth_handle_callback(): void {
	if ( ! borgodasso_google_oauth_is_configured() ) {
		borgodasso_google_oauth_fail( __( 'Accesso Google non configurato.', 'borgodasso' ) );
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- OAuth callback; CSRF via signed state cookie.
	$error = isset( $_GET['error'] ) && is_string( $_GET['error'] )
		? sanitize_text_field( wp_unslash( $_GET['error'] ) )
		: '';

	if ( '' !== $error ) {
		borgodasso_google_oauth_fail( __( 'Accesso Google annullato o rifiutato.', 'borgodasso' ) );
	}

	$code = '';
	if ( isset( $_GET['code'] ) && is_string( $_GET['code'] ) ) {
		$code = trim( wp_unslash( $_GET['code'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Charset-checked below.
	}
	$state = isset( $_GET['state'] ) && is_string( $_GET['state'] )
		? sanitize_text_field( wp_unslash( $_GET['state'] ) )
		: '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( '' === $code || '' === $state || ! preg_match( '#^[A-Za-z0-9._~+/-]+$#', $code ) ) {
		borgodasso_google_oauth_fail( __( 'Risposta Google non valida.', 'borgodasso' ) );
	}

	$payload = borgodasso_google_oauth_consume_state( $state );
	if ( ! is_array( $payload ) ) {
		borgodasso_google_oauth_fail( __( 'Sessione di accesso scaduta. Riprova.', 'borgodasso' ) );
	}

	$tokens = borgodasso_google_oauth_exchange_code( $code );
	if ( is_wp_error( $tokens ) ) {
		borgodasso_google_oauth_fail( $tokens->get_error_message() );
	}

	$access_token = isset( $tokens['access_token'] ) && is_string( $tokens['access_token'] )
		? $tokens['access_token']
		: '';

	if ( '' === $access_token ) {
		borgodasso_google_oauth_fail( __( 'Impossibile ottenere il token Google.', 'borgodasso' ) );
	}

	$profile = borgodasso_google_oauth_fetch_userinfo( $access_token );
	if ( is_wp_error( $profile ) ) {
		borgodasso_google_oauth_fail( $profile->get_error_message() );
	}

	$user_id = borgodasso_google_oauth_resolve_user( $profile );
	if ( is_wp_error( $user_id ) ) {
		borgodasso_google_oauth_fail( $user_id->get_error_message() );
	}

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );
	/**
	 * Fires after a user logs in via Google OAuth (same hook as core login).
	 *
	 * @param string  $user_login Username.
	 * @param WP_User $user       User object.
	 */
	do_action( 'wp_login', wp_get_current_user()->user_login, wp_get_current_user() );

	// Help Cloudways Varnish serve a fresh My Account after login.
	borgodasso_google_oauth_set_cookie( BORGODASSO_GOOGLE_OAUTH_CACHE_BYPASS, '1', time() + HOUR_IN_SECONDS );

	$redirect = '';
	if ( isset( $payload['redirect'] ) && is_string( $payload['redirect'] ) ) {
		$redirect = (string) wp_validate_redirect( $payload['redirect'], '' );
	}

	if ( '' === $redirect ) {
		$redirect = borgodasso_google_oauth_default_return_url();
	}

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Persist OAuth state in a signed cookie (primary) and a transient (backup).
 *
 * @param string $state    CSRF state.
 * @param string $redirect Post-login return URL.
 */
function borgodasso_google_oauth_persist_state( string $state, string $redirect ): void {
	$payload = array(
		'state'    => $state,
		'redirect' => $redirect,
		'exp'      => time() + BORGODASSO_GOOGLE_OAUTH_STATE_TTL,
	);

	set_transient(
		borgodasso_google_oauth_state_transient_key( $state ),
		$payload,
		BORGODASSO_GOOGLE_OAUTH_STATE_TTL
	);

	$json = wp_json_encode( $payload );
	if ( ! is_string( $json ) || '' === $json ) {
		return;
	}

	$cookie = borgodasso_google_oauth_sign_payload( $json );
	borgodasso_google_oauth_set_cookie( BORGODASSO_GOOGLE_OAUTH_COOKIE, $cookie, time() + BORGODASSO_GOOGLE_OAUTH_STATE_TTL );
}

/**
 * Validate and consume OAuth state from cookie or transient.
 *
 * @param string $state State from Google callback.
 * @return array<string, mixed>|null
 */
function borgodasso_google_oauth_consume_state( string $state ): ?array {
	$payload = null;

	$raw_cookie = '';
	if ( isset( $_COOKIE[ BORGODASSO_GOOGLE_OAUTH_COOKIE ] ) ) {
		$raw_cookie = (string) wp_unslash( $_COOKIE[ BORGODASSO_GOOGLE_OAUTH_COOKIE ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- HMAC-verified below.
	}

	if ( '' !== $raw_cookie ) {
		$json = borgodasso_google_oauth_verify_payload( $raw_cookie );
		if ( is_string( $json ) ) {
			$decoded = json_decode( $json, true );
			if ( is_array( $decoded ) ) {
				$payload = $decoded;
			}
		}
	}

	borgodasso_google_oauth_set_cookie( BORGODASSO_GOOGLE_OAUTH_COOKIE, '', time() - YEAR_IN_SECONDS );

	$transient_key = borgodasso_google_oauth_state_transient_key( $state );
	if ( ! is_array( $payload ) ) {
		$from_transient = get_transient( $transient_key );
		if ( is_array( $from_transient ) ) {
			$payload = $from_transient;
		}
	}
	delete_transient( $transient_key );

	if ( ! is_array( $payload ) ) {
		return null;
	}

	$stored_state = isset( $payload['state'] ) && is_string( $payload['state'] ) ? $payload['state'] : '';
	$exp          = isset( $payload['exp'] ) ? (int) $payload['exp'] : 0;

	if ( '' === $stored_state || ! hash_equals( $stored_state, $state ) ) {
		return null;
	}

	if ( $exp > 0 && $exp < time() ) {
		return null;
	}

	return $payload;
}

/**
 * Sign a JSON payload for the OAuth cookie.
 *
 * @param string $json Payload JSON.
 */
function borgodasso_google_oauth_sign_payload( string $json ): string {
	$body = base64_encode( $json ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Opaque cookie payload.
	$sig  = hash_hmac( 'sha256', $body, wp_salt( 'auth' ) );

	return $body . '.' . $sig;
}

/**
 * Verify a signed OAuth cookie and return the JSON body.
 *
 * @param string $cookie Cookie value.
 */
function borgodasso_google_oauth_verify_payload( string $cookie ): ?string {
	$parts = explode( '.', $cookie, 2 );
	if ( 2 !== count( $parts ) ) {
		return null;
	}

	[ $body, $sig ] = $parts;
	$expected       = hash_hmac( 'sha256', $body, wp_salt( 'auth' ) );

	if ( ! hash_equals( $expected, $sig ) ) {
		return null;
	}

	$json = base64_decode( $body, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Opaque cookie payload.
	return is_string( $json ) ? $json : null;
}

/**
 * Set or clear an OAuth-related cookie.
 *
 * @param string $name    Cookie name.
 * @param string $value   Cookie value.
 * @param int    $expires Unix expiry.
 */
function borgodasso_google_oauth_set_cookie( string $name, string $value, int $expires ): void {
	$options = array(
		'expires'  => $expires,
		'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
		'secure'   => is_ssl(),
		'httponly' => BORGODASSO_GOOGLE_OAUTH_CACHE_BYPASS !== $name,
		'samesite' => 'Lax',
	);

	$domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';
	if ( is_string( $domain ) && '' !== $domain ) {
		$options['domain'] = $domain;
	}

	setcookie( $name, $value, $options );
}

/**
 * Transient key for a given OAuth state value.
 *
 * @param string $state CSRF state.
 */
function borgodasso_google_oauth_state_transient_key( string $state ): string {
	return 'bd_ggl_' . hash( 'sha256', $state );
}

/**
 * Exchange authorization code for tokens.
 *
 * @param string $code Authorization code from Google.
 * @return array<string, mixed>|WP_Error
 */
function borgodasso_google_oauth_exchange_code( string $code ) {
	$response = wp_remote_post(
		'https://oauth2.googleapis.com/token',
		array(
			'timeout' => 20,
			'body'    => array(
				'code'          => $code,
				'client_id'     => borgodasso_google_oauth_client_id(),
				'client_secret' => borgodasso_google_oauth_client_secret(),
				'redirect_uri'  => borgodasso_google_oauth_redirect_uri(),
				'grant_type'    => 'authorization_code',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error(
			'borgodasso_google_token',
			__( 'Connessione a Google non riuscita. Riprova.', 'borgodasso' )
		);
	}

	$code_http = (int) wp_remote_retrieve_response_code( $response );
	$body      = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code_http || ! is_array( $body ) ) {
		return new WP_Error(
			'borgodasso_google_token',
			__( 'Autorizzazione Google non valida.', 'borgodasso' )
		);
	}

	return $body;
}

/**
 * Fetch Google userinfo with an access token.
 *
 * @param string $access_token OAuth access token.
 * @return array<string, mixed>|WP_Error
 */
function borgodasso_google_oauth_fetch_userinfo( string $access_token ) {
	$response = wp_remote_get(
		'https://openidconnect.googleapis.com/v1/userinfo',
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error(
			'borgodasso_google_userinfo',
			__( 'Impossibile leggere il profilo Google.', 'borgodasso' )
		);
	}

	$code_http = (int) wp_remote_retrieve_response_code( $response );
	$body      = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code_http || ! is_array( $body ) ) {
		return new WP_Error(
			'borgodasso_google_userinfo',
			__( 'Profilo Google non valido.', 'borgodasso' )
		);
	}

	return $body;
}

/**
 * Find or create a WP user from Google profile data.
 *
 * @param array<string, mixed> $profile Google userinfo.
 * @return int|WP_Error User ID.
 */
function borgodasso_google_oauth_resolve_user( array $profile ) {
	$sub            = isset( $profile['sub'] ) && is_string( $profile['sub'] ) ? trim( $profile['sub'] ) : '';
	$email          = isset( $profile['email'] ) && is_string( $profile['email'] )
		? sanitize_email( $profile['email'] )
		: '';
	$email_verified = isset( $profile['email_verified'] )
		&& ( true === $profile['email_verified'] || 'true' === $profile['email_verified'] );

	if ( '' === $sub ) {
		return new WP_Error(
			'borgodasso_google_profile',
			__( 'Profilo Google incompleto.', 'borgodasso' )
		);
	}

	if ( '' === $email || ! is_email( $email ) || ! $email_verified ) {
		return new WP_Error(
			'borgodasso_google_email',
			__( 'Serve un’email Google verificata per accedere.', 'borgodasso' )
		);
	}

	$users = get_users(
		array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Lookup by Google subject id.
			'meta_key'   => BORGODASSO_GOOGLE_OAUTH_META_SUB,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Lookup by Google subject id.
			'meta_value' => $sub,
			'number'     => 1,
			'fields'     => 'ID',
		)
	);

	if ( ! empty( $users[0] ) ) {
		$user_id = (int) $users[0];
		borgodasso_google_oauth_sync_profile_meta( $user_id, $profile );

		return $user_id;
	}

	$existing = get_user_by( 'email', $email );
	if ( $existing instanceof WP_User ) {
		$user_id = (int) $existing->ID;
		update_user_meta( $user_id, BORGODASSO_GOOGLE_OAUTH_META_SUB, $sub );
		borgodasso_google_oauth_sync_profile_meta( $user_id, $profile );

		return $user_id;
	}

	return borgodasso_google_oauth_create_customer( $email, $profile, $sub );
}

/**
 * Create a WooCommerce customer from Google profile.
 *
 * @param string               $email   Verified email.
 * @param array<string, mixed> $profile Google userinfo.
 * @param string               $sub     Google subject ID.
 * @return int|WP_Error
 */
function borgodasso_google_oauth_create_customer( string $email, array $profile, string $sub ) {
	$first   = isset( $profile['given_name'] ) && is_string( $profile['given_name'] )
		? sanitize_text_field( $profile['given_name'] )
		: '';
	$last    = isset( $profile['family_name'] ) && is_string( $profile['family_name'] )
		? sanitize_text_field( $profile['family_name'] )
		: '';
	$display = isset( $profile['name'] ) && is_string( $profile['name'] )
		? sanitize_text_field( $profile['name'] )
		: trim( $first . ' ' . $last );

	$password = wp_generate_password( 32, true, true );

	// Accedi registration requires Nome/Cognome via POST; Google OAuth has none.
	$skip_names = static function (): bool {
		return true;
	};
	add_filter( 'borgodasso_skip_registration_name_validation', $skip_names );

	if ( function_exists( 'wc_create_new_customer' ) ) {
		$user_id = wc_create_new_customer(
			$email,
			'',
			$password,
			array(
				'first_name'   => $first,
				'last_name'    => $last,
				'display_name' => '' !== $display ? $display : $email,
			)
		);
	} else {
		$username = sanitize_user( current( explode( '@', $email ) ), true );
		if ( '' === $username || username_exists( $username ) ) {
			$username = sanitize_user( $email, true );
		}
		if ( username_exists( $username ) ) {
			$username .= wp_generate_password( 4, false, false );
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => $password,
				'role'         => 'customer',
				'first_name'   => $first,
				'last_name'    => $last,
				'display_name' => '' !== $display ? $display : $email,
			)
		);
	}

	remove_filter( 'borgodasso_skip_registration_name_validation', $skip_names );

	if ( is_wp_error( $user_id ) ) {
		return new WP_Error(
			'borgodasso_google_create',
			__( 'Impossibile creare l’account. Riprova.', 'borgodasso' )
		);
	}

	$user_id = (int) $user_id;

	$update = array( 'ID' => $user_id );

	if ( '' !== $first ) {
		$update['first_name'] = $first;
		update_user_meta( $user_id, 'billing_first_name', $first );
	}

	if ( '' !== $last ) {
		$update['last_name'] = $last;
		update_user_meta( $user_id, 'billing_last_name', $last );
	}

	if ( '' !== $display ) {
		$update['display_name'] = $display;
	}

	if ( count( $update ) > 1 ) {
		wp_update_user( $update );
	}

	update_user_meta( $user_id, BORGODASSO_GOOGLE_OAUTH_META_SUB, $sub );
	borgodasso_google_oauth_sync_profile_meta( $user_id, $profile );

	return $user_id;
}

/**
 * Store optional Google picture URL on the user.
 *
 * @param int                  $user_id User ID.
 * @param array<string, mixed> $profile Google userinfo.
 */
function borgodasso_google_oauth_sync_profile_meta( int $user_id, array $profile ): void {
	if ( isset( $profile['picture'] ) && is_string( $profile['picture'] ) && '' !== $profile['picture'] ) {
		update_user_meta( $user_id, BORGODASSO_GOOGLE_OAUTH_META_PICTURE, esc_url_raw( $profile['picture'] ) );
	}
}

/**
 * Default post-login destination (My Account).
 */
function borgodasso_google_oauth_default_return_url(): string {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'myaccount' );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	return home_url( '/' );
}

/**
 * Abort OAuth with a WooCommerce notice and redirect to Accedi.
 *
 * @param string $message Error message.
 */
function borgodasso_google_oauth_fail( string $message ): void {
	if ( function_exists( 'wc_add_notice' ) ) {
		wc_add_notice( $message, 'error' );
	}

	$login = function_exists( 'borgodasso_account_login_url' )
		? borgodasso_account_login_url()
		: borgodasso_google_oauth_default_return_url();

	// Query arg survives when WC session notices are missing on a cached Accedi page.
	$login = (string) add_query_arg(
		'google_oauth_error',
		rawurlencode( wp_strip_all_tags( $message ) ),
		$login
	);

	wp_safe_redirect( $login );
	exit;
}

/**
 * Surface Google OAuth errors passed via query arg on Accedi.
 */
function borgodasso_google_oauth_print_query_error(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only error from OAuth redirect.
	if ( empty( $_GET['google_oauth_error'] ) || ! is_string( $_GET['google_oauth_error'] ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$message = sanitize_text_field( rawurldecode( wp_unslash( $_GET['google_oauth_error'] ) ) );
	if ( '' === $message ) {
		return;
	}

	echo '<div class="woocommerce-error account-auth__google-error" role="alert">' . esc_html( $message ) . '</div>';
}
add_action( 'woocommerce_before_customer_login_form', 'borgodasso_google_oauth_print_query_error', 5 );

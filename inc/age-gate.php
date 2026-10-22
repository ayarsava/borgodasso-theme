<?php
/**
 * Legal drinking age gate for WooCommerce.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BORGODASSO_AGE_GATE_TEMPLATE = 'page-templates/legal-drinking-age.php';
const BORGODASSO_AGE_GATE_COOKIE   = 'borgodasso_age_verified';

/**
 * Whether the current (or given) page uses the Legal Drinking Age template.
 *
 * @param int|null $post_id Post ID. Defaults to current post.
 */
function borgodasso_is_age_gate_template( ?int $post_id = null ): bool {
	$post_id = $post_id ?? (int) get_the_ID();

	if ( $post_id <= 0 ) {
		return false;
	}

	return BORGODASSO_AGE_GATE_TEMPLATE === get_page_template_slug( $post_id );
}

/**
 * Published page ID that uses the Legal Drinking Age template (0 if none).
 */
function borgodasso_age_gate_page_id(): int {
	$cached = (int) get_option( 'borgodasso_age_gate_page_id', 0 );

	if ( $cached > 0 && 'publish' === get_post_status( $cached ) && borgodasso_is_age_gate_template( $cached ) ) {
		return $cached;
	}

	// Single-page lookup by template slug (cached in option after first hit).
	// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	$pages = get_posts(
		array(
			'post_type'              => 'page',
			'post_status'            => 'publish',
			'meta_key'               => '_wp_page_template',
			'meta_value'             => BORGODASSO_AGE_GATE_TEMPLATE,
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value

	$id = isset( $pages[0] ) ? (int) $pages[0] : 0;
	update_option( 'borgodasso_age_gate_page_id', $id, false );

	return $id;
}

/**
 * Keep the cached age-gate page ID in sync when pages are saved.
 *
 * @param int $post_id Post ID.
 */
function borgodasso_age_gate_sync_page_id( int $post_id ): void {
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( borgodasso_is_age_gate_template( $post_id ) && 'publish' === get_post_status( $post_id ) ) {
		update_option( 'borgodasso_age_gate_page_id', $post_id, false );
		return;
	}

	$cached = (int) get_option( 'borgodasso_age_gate_page_id', 0 );
	if ( $cached === $post_id ) {
		delete_option( 'borgodasso_age_gate_page_id' );
	}
}
add_action( 'save_post_page', 'borgodasso_age_gate_sync_page_id' );

/**
 * Age gate page URL, or empty when no page is published.
 */
function borgodasso_age_gate_url(): string {
	$id = borgodasso_age_gate_page_id();

	return $id > 0 ? (string) get_permalink( $id ) : '';
}

/**
 * Whether the visitor has confirmed legal drinking age.
 */
function borgodasso_age_gate_is_verified(): bool {
	if ( ! isset( $_COOKIE[ BORGODASSO_AGE_GATE_COOKIE ] ) ) {
		return false;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Presence flag only.
	return '1' === (string) wp_unslash( $_COOKIE[ BORGODASSO_AGE_GATE_COOKIE ] );
}

/**
 * Persist the age-verified cookie (1 year).
 */
function borgodasso_age_gate_set_verified_cookie(): void {
	$expire = time() + YEAR_IN_SECONDS;
	$path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
	$domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';

	setcookie(
		BORGODASSO_AGE_GATE_COOKIE,
		'1',
		array(
			'expires'  => $expire,
			'path'     => $path,
			'domain'   => $domain,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);

	$_COOKIE[ BORGODASSO_AGE_GATE_COOKIE ] = '1';
}

/**
 * Whether the current request is a WooCommerce surface that requires the gate.
 */
function borgodasso_age_gate_is_commerce_request(): bool {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return false;
	}

	return is_woocommerce() || is_cart() || is_checkout();
}

/**
 * Validated return URL after SÌ (defaults to shop).
 */
function borgodasso_age_gate_requested_redirect(): string {
	$fallback = function_exists( 'borgodasso_shop_url' ) ? borgodasso_shop_url() : home_url( '/' );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public return path; validated below.
	$raw = isset( $_REQUEST['redirect_to'] ) && is_string( $_REQUEST['redirect_to'] )
		? wp_unslash( $_REQUEST['redirect_to'] )
		: '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( '' === $raw ) {
		return $fallback;
	}

	$validated = wp_validate_redirect( $raw, $fallback );

	// Never bounce back onto the age-gate page itself.
	$gate = borgodasso_age_gate_url();
	if ( '' !== $gate && untrailingslashit( $validated ) === untrailingslashit( $gate ) ) {
		return $fallback;
	}

	return $validated;
}

/**
 * Age-gate field data for the template.
 *
 * @param int|null $post_id Post ID.
 * @return array{
 *   question: string,
 *   yes_label: string,
 *   no_label: string,
 *   image_id: int,
 *   image_mobile_id: int,
 *   redirect_to: string
 * }|null
 */
function borgodasso_age_gate_data( ?int $post_id = null ): ?array {
	$post_id = $post_id ?? (int) get_the_ID();

	if ( $post_id <= 0 || ! borgodasso_is_age_gate_template( $post_id ) ) {
		return null;
	}

	$question = '';
	$yes      = '';
	$no       = '';
	$image_id = 0;
	$mobile   = 0;

	if ( function_exists( 'get_field' ) ) {
		$q        = get_field( 'age_gate_question', $post_id );
		$question = is_string( $q ) ? trim( $q ) : '';

		$y   = get_field( 'age_gate_yes_label', $post_id );
		$yes = is_string( $y ) ? trim( $y ) : '';

		$n  = get_field( 'age_gate_no_label', $post_id );
		$no = is_string( $n ) ? trim( $n ) : '';

		$image_id = (int) get_field( 'age_gate_background', $post_id );
		$mobile   = (int) get_field( 'age_gate_background_mobile', $post_id );
	}

	if ( '' === $question ) {
		$question = __( 'Hai l’età legale per bere nel tuo Paese?', 'borgodasso' );
	}
	if ( '' === $yes ) {
		$yes = __( 'SÌ', 'borgodasso' );
	}
	if ( '' === $no ) {
		$no = __( 'NO', 'borgodasso' );
	}

	return array(
		'question'        => $question,
		'yes_label'       => $yes,
		'no_label'        => $no,
		'image_id'        => $image_id,
		'image_mobile_id' => $mobile > 0 ? $mobile : $image_id,
		'redirect_to'     => borgodasso_age_gate_requested_redirect(),
	);
}

/**
 * Handle SÌ / NO form posts.
 */
function borgodasso_age_gate_handle_post(): void {
	if ( ! isset( $_POST['borgodasso_age_gate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	$nonce = isset( $_POST['borgodasso_age_gate_nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['borgodasso_age_gate_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'borgodasso_age_gate' ) ) {
		return;
	}

	$choice = sanitize_key( wp_unslash( (string) $_POST['borgodasso_age_gate'] ) );

	if ( 'yes' === $choice ) {
		borgodasso_age_gate_set_verified_cookie();
		wp_safe_redirect( borgodasso_age_gate_requested_redirect() );
		exit;
	}

	if ( 'no' === $choice ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
}
add_action( 'template_redirect', 'borgodasso_age_gate_handle_post', 0 );

/**
 * Send unverified visitors away from WooCommerce to the age-gate page.
 */
function borgodasso_age_gate_protect_commerce(): void {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	if ( borgodasso_age_gate_is_verified() ) {
		return;
	}

	if ( borgodasso_is_age_gate_template() ) {
		return;
	}

	if ( ! borgodasso_age_gate_is_commerce_request() ) {
		return;
	}

	$gate = borgodasso_age_gate_url();
	if ( '' === $gate ) {
		return;
	}

	$current = home_url( add_query_arg( array() ) );
	$target  = add_query_arg( 'redirect_to', $current, $gate );

	wp_safe_redirect( $target );
	exit;
}
add_action( 'template_redirect', 'borgodasso_age_gate_protect_commerce', 1 );

/**
 * Verified visitors who land on the gate go straight to shop / redirect_to.
 */
function borgodasso_age_gate_skip_if_verified(): void {
	if ( ! borgodasso_is_age_gate_template() || ! borgodasso_age_gate_is_verified() ) {
		return;
	}

	wp_safe_redirect( borgodasso_age_gate_requested_redirect() );
	exit;
}
add_action( 'template_redirect', 'borgodasso_age_gate_skip_if_verified', 2 );

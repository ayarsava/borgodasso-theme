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
// Cloudways Varnish ignores the age cookie and keeps serving the cached
// "go back to the gate" redirect. This name is already on its bypass list.
const BORGODASSO_AGE_GATE_CACHE_BYPASS_COOKIE = 'wordpress_no_cache';

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

	$cookie = array(
		'expires'  => $expire,
		'path'     => $path,
		'secure'   => is_ssl(),
		'httponly' => false,
		'samesite' => 'Lax',
	);

	// An empty domain attribute makes some browsers drop the cookie.
	if ( is_string( $domain ) && '' !== $domain ) {
		$cookie['domain'] = $domain;
	}

	setcookie( BORGODASSO_AGE_GATE_COOKIE, '1', $cookie );
	setcookie( BORGODASSO_AGE_GATE_CACHE_BYPASS_COOKIE, '1', $cookie );

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
		borgodasso_age_gate_leave( borgodasso_age_gate_requested_redirect() );
	}

	if ( 'no' === $choice ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
}
/**
 * Leave the gate after the cookie is stored.
 *
 * A 302 drops Set-Cookie in some mobile browsers, so the shop request looks
 * unverified and bounces straight back here. A 200 lets the cookie stick,
 * then the browser navigates.
 */
function borgodasso_age_gate_leave( string $url ): void {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	nocache_headers();
	status_header( 200 );
	header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

	$destination = esc_url( $url );

	echo '<!DOCTYPE html><html><head>';
	echo '<meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">';
	echo '<meta http-equiv="refresh" content="0;url=' . esc_attr( $destination ) . '">';
	echo '</head><body><script>location.replace(' . wp_json_encode( $url ) . ');</script></body></html>';
	exit;
}

/**
 * Inline statement that stores the age cookie before the yes form posts.
 */
function borgodasso_age_gate_client_cookie_js(): string {
	$names = array(
		BORGODASSO_AGE_GATE_COOKIE,
		BORGODASSO_AGE_GATE_CACHE_BYPASS_COOKIE,
	);
	$lines = array();

	foreach ( $names as $name ) {
		$lines[] = "document.cookie='{$name}=1; Path=/; Max-Age=31536000; SameSite=Lax'+(location.protocol==='https:'?'; Secure':'')";
	}

	return implode( ';', $lines );
}

/**
 * Keep Varnish from storing the bounce back to the gate.
 */
function borgodasso_age_gate_send_uncached_redirect( string $url ): void {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	nocache_headers();
	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );
	header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
	wp_safe_redirect( $url );
	exit;
}

/**
 * The gate HTML contains a nonce, so a cached copy makes SÌ post and stay put.
 */
function borgodasso_age_gate_nocache(): void {
	if ( ! borgodasso_is_age_gate_template() ) {
		return;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	nocache_headers();
}
add_action( 'template_redirect', 'borgodasso_age_gate_nocache', 0 );
add_action( 'template_redirect', 'borgodasso_age_gate_handle_post', 0 );

/**
 * Link-preview and search crawlers have no age cookie, so they would scrape the gate.
 *
 * @return bool
 */
function borgodasso_age_gate_is_preview_crawler(): bool {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';

	if ( '' === $ua ) {
		return false;
	}

	$needles = array(
		'facebookexternalhit',
		'Facebot',
		'FacebookBot',
		'meta-externalagent',
		'meta-externalfetcher',
		'Twitterbot',
		'LinkedInBot',
		'WhatsApp',
		'Slackbot',
		'Slack-ImgProxy',
		'Pinterest',
		'Discordbot',
		'TelegramBot',
		'Googlebot',
		'bingbot',
	);

	foreach ( $needles as $needle ) {
		if ( false !== stripos( $ua, $needle ) ) {
			return true;
		}
	}

	return false;
}

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

	if ( borgodasso_age_gate_is_preview_crawler() ) {
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

	borgodasso_age_gate_send_uncached_redirect( $target );
}
add_action( 'template_redirect', 'borgodasso_age_gate_protect_commerce', 1 );

/**
 * Verified visitors who land on the gate go straight to shop / redirect_to.
 */
function borgodasso_age_gate_skip_if_verified(): void {
	if ( ! borgodasso_is_age_gate_template() || ! borgodasso_age_gate_is_verified() ) {
		return;
	}

	borgodasso_age_gate_send_uncached_redirect( borgodasso_age_gate_requested_redirect() );
}
add_action( 'template_redirect', 'borgodasso_age_gate_skip_if_verified', 2 );

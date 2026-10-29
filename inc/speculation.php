<?php
/**
 * Same-origin prefetch for ordinary page links.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prefetch documents on hover, including while logged in.
 *
 * Core only does this for logged-out visitors, and only on pointer-down.
 *
 * @param array{mode?: string, eagerness?: string}|null $config Existing configuration.
 * @return array{mode: string, eagerness: string}|null
 */
function borgodasso_speculation_rules_configuration( ?array $config ): ?array {
	unset( $config );

	if ( is_admin() ) {
		return null;
	}

	return array(
		'mode'      => 'prefetch',
		'eagerness' => 'moderate',
	);
}
add_filter( 'wp_speculation_rules_configuration', 'borgodasso_speculation_rules_configuration' );

/**
 * Keep cart, checkout, and account URLs out of speculative loads.
 *
 * @param string[] $paths Path patterns.
 * @param string   $mode  Speculative loading mode.
 * @return string[]
 */
function borgodasso_speculation_rules_href_exclude_paths( array $paths, string $mode ): array {
	unset( $mode );

	foreach ( borgodasso_woocommerce_speculation_paths() as $path ) {
		$paths[] = $path;
	}

	return $paths;
}
add_filter( 'wp_speculation_rules_href_exclude_paths', 'borgodasso_speculation_rules_href_exclude_paths', 10, 2 );

/**
 * Permalink patterns for the WooCommerce pages that must not be prefetched.
 *
 * Includes each TranslatePress language, and the endpoints that live under account.
 *
 * @return string[]
 */
function borgodasso_woocommerce_speculation_paths(): array {
	$urls = borgodasso_woocommerce_speculation_urls();
	if ( array() === $urls ) {
		return array();
	}

	$patterns = array();

	foreach ( borgodasso_urls_in_every_language( $urls ) as $url ) {
		$path = borgodasso_speculation_path_from_url( $url );
		if ( '' === $path ) {
			continue;
		}

		foreach ( borgodasso_speculation_path_patterns( $path ) as $pattern ) {
			$patterns[] = $pattern;
		}
	}

	return array_values( array_unique( $patterns ) );
}

/**
 * Cart, checkout, and account permalinks in the current language.
 *
 * @return string[]
 */
function borgodasso_woocommerce_speculation_urls(): array {
	if ( ! function_exists( 'wc_get_cart_url' ) || ! function_exists( 'wc_get_checkout_url' ) || ! function_exists( 'wc_get_page_permalink' ) ) {
		return array();
	}

	$account = wc_get_page_permalink( 'myaccount' );

	return array_values(
		array_filter(
			array(
				wc_get_cart_url(),
				wc_get_checkout_url(),
				is_string( $account ) ? $account : '',
			),
			static function ( string $url ): bool {
				return '' !== $url;
			}
		)
	);
}

/**
 * Repeat each URL in every published TranslatePress language.
 *
 * @param string[] $urls URLs in the current language.
 * @return string[]
 */
function borgodasso_urls_in_every_language( array $urls ): array {
	if ( ! class_exists( 'TRP_Translate_Press' ) ) {
		return $urls;
	}

	$trp = TRP_Translate_Press::get_trp_instance();
	if ( ! is_object( $trp ) || ! method_exists( $trp, 'get_component' ) ) {
		return $urls;
	}

	$settings_component = $trp->get_component( 'settings' );
	$url_converter      = $trp->get_component( 'url_converter' );

	if (
		! is_object( $settings_component )
		|| ! method_exists( $settings_component, 'get_settings' )
		|| ! is_object( $url_converter )
		|| ! method_exists( $url_converter, 'get_url_for_language' )
	) {
		return $urls;
	}

	$settings  = $settings_component->get_settings();
	$languages = isset( $settings['publish-languages'] ) && is_array( $settings['publish-languages'] )
		? $settings['publish-languages']
		: array();

	$expanded = $urls;

	foreach ( $urls as $url ) {
		foreach ( $languages as $language ) {
			if ( ! is_string( $language ) || '' === $language ) {
				continue;
			}

			$translated = $url_converter->get_url_for_language( $language, $url );
			if ( is_string( $translated ) && '' !== $translated ) {
				$expanded[] = $translated;
			}
		}
	}

	return $expanded;
}

/**
 * Path portion of a front-end URL, or an empty string when it should not be excluded.
 *
 * A missing WooCommerce page falls back to the site root. That path is ignored.
 *
 * @param string $url Absolute or root-relative URL.
 * @return string
 */
function borgodasso_speculation_path_from_url( string $url ): string {
	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( ! is_string( $path ) ) {
		return '';
	}

	$path = untrailingslashit( $path );
	if ( '' === $path || '/' === $path ) {
		return '';
	}

	if ( ! str_starts_with( $path, '/' ) ) {
		$path = '/' . $path;
	}

	$home = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$home = is_string( $home ) ? untrailingslashit( $home ) : '';
	if ( '' === $home ) {
		$home = '/';
	}

	if ( $path === $home ) {
		return '';
	}

	return $path;
}

/**
 * The page itself, plus the endpoints nested under it.
 *
 * URL patterns treat `*` as one path segment, so account routes such as
 * `view-order/123` need more than a single wildcard.
 *
 * @param string $path Permalink path with a leading slash and no trailing slash.
 * @return string[]
 */
function borgodasso_speculation_path_patterns( string $path ): array {
	$patterns = array(
		$path,
		$path . '/',
	);

	$suffix = '';

	for ( $depth = 0; $depth < 3; $depth++ ) {
		$suffix    .= '/*';
		$patterns[] = $path . $suffix;
		$patterns[] = $path . $suffix . '/';
	}

	return $patterns;
}

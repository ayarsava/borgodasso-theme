<?php
/**
 * Asset enqueue (Vite manifest).
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue theme styles and scripts from dist/.
 */
function borgodasso_enqueue_assets(): void {
	$handle        = 'borgodasso-main';
	$manifest_path = BORGODASSO_DIR . '/dist/.vite/manifest.json';
	$style_deps    = array();

	if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
		$style_deps[] = 'woocommerce-general';
		$style_deps[] = 'woocommerce-layout';
	}

	if ( ! file_exists( $manifest_path ) ) {
		// Fallback: theme stylesheet only until a production build exists.
		wp_enqueue_style(
			'borgodasso-style',
			get_stylesheet_uri(),
			$style_deps,
			BORGODASSO_VERSION
		);
		return;
	}

	// Local filesystem read of the Vite manifest (not a remote URL).
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$manifest_json = file_get_contents( $manifest_path );
	$manifest      = json_decode( (string) $manifest_json, true );

	if ( ! is_array( $manifest ) || ! isset( $manifest['assets/js/main.ts'] ) ) {
		return;
	}

	$entry = $manifest['assets/js/main.ts'];

	if ( ! empty( $entry['css'] ) && is_array( $entry['css'] ) ) {
		foreach ( $entry['css'] as $index => $css_file ) {
			$css_rel  = ltrim( (string) $css_file, '/' );
			$css_path = BORGODASSO_DIR . '/dist/' . $css_rel;
			wp_enqueue_style(
				$handle . '-css-' . $index,
				BORGODASSO_URI . '/dist/' . $css_rel,
				$style_deps,
				file_exists( $css_path ) ? (string) filemtime( $css_path ) : BORGODASSO_VERSION
			);
		}
	}

	if ( ! empty( $entry['file'] ) ) {
		$js_rel  = ltrim( (string) $entry['file'], '/' );
		$js_path = BORGODASSO_DIR . '/dist/' . $js_rel;
		wp_enqueue_script(
			$handle,
			BORGODASSO_URI . '/dist/' . $js_rel,
			array(),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : BORGODASSO_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		add_filter( 'script_loader_tag', 'borgodasso_vite_module_type', 10, 3 );
	}
}
add_action( 'wp_enqueue_scripts', 'borgodasso_enqueue_assets' );
add_action( 'wp_enqueue_scripts', 'borgodasso_maybe_enqueue_browsersync', 100 );
add_action( 'wp_head', 'borgodasso_preload_fonts', 1 );

/**
 * Preload brand fonts to reduce FOUT before entrance animations.
 */
function borgodasso_preload_fonts(): void {
	if ( ! file_exists( BORGODASSO_DIR . '/dist/.vite/manifest.json' ) ) {
		return;
	}

	$base = BORGODASSO_URI . '/dist/assets/';

	$fonts = array(
		array(
			'href' => $base . 'PPEditorialNew-Ultralight.woff2',
			'type' => 'font/woff2',
		),
		array(
			'href' => $base . 'PPEditorialNew-UltralightItalic.woff2',
			'type' => 'font/woff2',
		),
		array(
			'href' => $base . 'PPNeueMontreal-Regular.woff',
			'type' => 'font/woff',
		),
	);

	foreach ( $fonts as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="%s" crossorigin>' . "\n",
			esc_url( $font['href'] ),
			esc_attr( $font['type'] )
		);
	}
}

/**
 * Load BrowserSync client while `npm run dev` / `npm run reload` is running (DDEV only).
 */
function borgodasso_maybe_enqueue_browsersync(): void {
	if ( ! is_readable( BORGODASSO_DIR . '/.browsersync' ) ) {
		return;
	}

	// Local DDEV only — never enqueue on production hosts.
	if ( 'true' !== getenv( 'IS_DDEV_PROJECT' ) ) {
		return;
	}

	$client = 'https://localhost:3000/browser-sync/browser-sync-client.js';

	wp_enqueue_script(
		'borgodasso-browsersync',
		$client,
		array(),
		BORGODASSO_VERSION,
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	// Force absolute localhost URL (no ?ver=, no host rewrite).
	add_filter(
		'script_loader_src',
		static function ( $src, $handle ) use ( $client ) {
			if ( 'borgodasso-browsersync' === $handle ) {
				return $client;
			}
			return $src;
		},
		100,
		2
	);
}

/**
 * Mark the Vite entry script as an ES module.
 *
 * @param string $tag    Script tag HTML.
 * @param string $handle Script handle.
 * @param string $src    Script source URL.
 * @return string
 */
function borgodasso_vite_module_type( string $tag, string $handle, string $src ): string {
	unset( $src );

	if ( 'borgodasso-main' !== $handle ) {
		return $tag;
	}

	if ( false !== strpos( $tag, 'type="module"' ) ) {
		return $tag;
	}

	return str_replace( '<script ', '<script type="module" ', $tag );
}

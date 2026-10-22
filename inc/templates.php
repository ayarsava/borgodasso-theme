<?php
/**
 * Theme template loader.
 *
 * Load partials from templates/:
 *   borgodasso_template( 'components/header' );
 *   borgodasso_template( 'objects/button', array( 'label' => 'Discover', 'url' => '#' ) );
 *   borgodasso_template( 'page-builder-components/call-to-action', $args );
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include a template partial under templates/.
 *
 * @param string               $slug Relative path without .php (e.g. objects/button).
 * @param array<string, mixed> $args Variables available inside the partial.
 */
function borgodasso_template( string $slug, array $args = array() ): void {
	$path = BORGODASSO_DIR . '/templates/' . $slug . '.php';

	if ( ! is_readable( $path ) ) {
		return;
	}

	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- intentional scoped vars for partials.
	extract( $args, EXTR_SKIP );
	include $path;
}

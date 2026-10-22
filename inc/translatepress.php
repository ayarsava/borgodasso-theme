<?php
/**
 * TranslatePress integration.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Skip JS-generated char spans from TranslatePress dynamic string detection.
 *
 * Full titles remain translatable in `.split-chars__text` (server-side only).
 *
 * @param array<int, string> $selectors Existing selectors.
 * @return array<int, string>
 */
function borgodasso_trp_skip_dynamic_selectors( array $selectors ): array {
	$selectors[] = '.split-chars__stage';
	$selectors[] = '.split-chars__char';
	$selectors[] = '.split-chars__char-inner';
	$selectors[] = '.split-text__word';
	$selectors[] = '.split-text__space';

	return $selectors;
}
add_filter( 'trp_skip_selectors_from_dynamic_translation', 'borgodasso_trp_skip_dynamic_selectors' );

<?php
/**
 * Inline SVG from theme assets.
 *
 * @package Borgodasso
 *
 * @var string      $name  Filename without extension in assets/svg/.
 * @var string      $class BEM class on the svg element.
 * @var string|null $label Accessible title (optional).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$name  = $name ?? '';
$class = $class ?? 'icon';
$label = $label ?? null;

if ( '' === $name ) {
	return;
}

$path = BORGODASSO_DIR . '/assets/svg/' . $name . '.svg';

if ( ! is_readable( $path ) ) {
	return;
}

// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
$svg = (string) file_get_contents( $path );

if ( '' === $svg ) {
	return;
}

if ( null !== $label && '' !== $label ) {
	$svg = preg_replace( '/<svg\b/', '<svg role="img" aria-label="' . esc_attr( $label ) . '"', $svg, 1 );
} else {
	$svg = preg_replace( '/<svg\b/', '<svg aria-hidden="true"', $svg, 1 );
}

if ( $class ) {
	$svg = preg_replace( '/<svg\b/', '<svg class="' . esc_attr( $class ) . '"', $svg, 1 );
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted theme SVG file.
echo $svg;

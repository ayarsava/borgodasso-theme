<?php
/**
 * Contact page template helpers.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current (or given) page uses the Contact template.
 *
 * @param int|null $post_id Post ID. Defaults to current post.
 */
function borgodasso_is_contact_template( ?int $post_id = null ): bool {
	$post_id = $post_id ?? (int) get_the_ID();

	if ( $post_id <= 0 ) {
		return false;
	}

	return 'page-templates/contact.php' === get_page_template_slug( $post_id );
}

/**
 * Normalized Contact field data (null when not a Contact page or Page header images are missing).
 *
 * Background + optional title come from the Page header field group (Full screen / 1 image).
 *
 * @param int|null $post_id Post ID. Defaults to current post.
 * @return array{
 *   title: string,
 *   image_id: int,
 *   image_mobile_id: int,
 *   blocks: array<int, array{title: string, content: string}>
 * }|null
 */
function borgodasso_contact_data( ?int $post_id = null ): ?array {
	$post_id = $post_id ?? (int) get_the_ID();

	if ( $post_id <= 0 || ! borgodasso_is_contact_template( $post_id ) || ! function_exists( 'get_field' ) ) {
		return null;
	}

	$header = borgodasso_page_header_data();

	if ( null === $header ) {
		return null;
	}

	$image_id        = (int) $header['image_id'];
	$image_mobile_id = (int) $header['image_mobile'];

	// Dual layout: use the primary image as the full-bleed background.
	if ( $image_id <= 0 && (int) $header['image_primary'] > 0 ) {
		$image_id = (int) $header['image_primary'];
	}

	if ( $image_id <= 0 || $image_mobile_id <= 0 ) {
		return null;
	}

	$defaults = array(
		1 => 'Borgo d’Asso',
		2 => 'Contatti',
		3 => 'Cantina Ser’Enzo',
	);

	$blocks = array();

	foreach ( $defaults as $index => $default_title ) {
		$title = get_field( "contact_block_{$index}_title", $post_id );
		$title = is_string( $title ) ? trim( $title ) : '';
		if ( '' === $title ) {
			$title = $default_title;
		}

		$content = get_field( "contact_block_{$index}_content", $post_id );
		if ( is_string( $content ) && '' !== trim( $content ) ) {
			$content = wp_kses_post( $content );
		} else {
			$content = '';
		}

		$blocks[] = array(
			'title'   => $title,
			'content' => $content,
		);
	}

	return array(
		'title'           => (string) $header['title'],
		'image_id'        => $image_id,
		'image_mobile_id' => $image_mobile_id,
		'blocks'          => $blocks,
	);
}

<?php
/**
 * Page builder (ACF flexible content).
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current post has page-builder rows.
 *
 * @param int|null $post_id Post ID. Defaults to current post in the loop.
 */
function borgodasso_has_page_builder( ?int $post_id = null ): bool {
	if ( ! function_exists( 'have_rows' ) ) {
		return false;
	}

	$post_id = $post_id ?? (int) get_the_ID();
	if ( $post_id <= 0 ) {
		return false;
	}

	return (bool) have_rows( 'page_builder', $post_id );
}

/**
 * Render all page-builder sections for a post.
 *
 * @param int|null $post_id Post ID. Defaults to current post in the loop.
 */
function borgodasso_render_page_builder( ?int $post_id = null ): void {
	if ( ! function_exists( 'have_rows' ) ) {
		return;
	}

	$post_id = $post_id ?? (int) get_the_ID();
	if ( $post_id <= 0 || ! have_rows( 'page_builder', $post_id ) ) {
		return;
	}

	echo '<div class="page-builder">';

	while ( have_rows( 'page_builder', $post_id ) ) {
		the_row();
		borgodasso_render_page_builder_row( (string) get_row_layout() );
	}

	echo '</div>';
}

/**
 * Render a single flexible-content row.
 *
 * @param string $layout ACF layout name.
 */
function borgodasso_render_page_builder_row( string $layout ): void {
	switch ( $layout ) {
		case 'text_block':
			borgodasso_template(
				'page-builder-components/text-block',
				array(
					'eyebrow' => borgodasso_acf_optional_string( 'eyebrow' ),
					'title'   => borgodasso_acf_optional_string( 'title' ),
					'content' => borgodasso_acf_optional_string( 'content' ),
					'align'   => borgodasso_acf_string_default( 'align', 'left' ),
				)
			);
			break;

		case 'blockquote':
			borgodasso_template(
				'page-builder-components/blockquote',
				array(
					'quote'  => borgodasso_acf_string( 'quote' ),
					'source' => borgodasso_acf_optional_string( 'source' ),
				)
			);
			break;

		case 'call_to_action':
			$button = get_sub_field( 'button' );
			$link   = is_array( $button ) ? $button : array();

			borgodasso_template(
				'page-builder-components/call-to-action',
				array(
					'title'                  => borgodasso_acf_string( 'title' ),
					'text'                   => borgodasso_acf_optional_string( 'text' ),
					'button_label'           => ! empty( $link['title'] ) ? (string) $link['title'] : __( 'Discover', 'borgodasso' ),
					'button_url'             => ! empty( $link['url'] ) ? (string) $link['url'] : '#',
					'external'               => ! empty( $link['target'] ) && '_blank' === $link['target'],
					'image_id'               => (int) get_sub_field( 'image' ),
					'visibility'             => borgodasso_acf_string_default( 'visibility', 'both' ),
					'desktop_image_position' => borgodasso_acf_string_default( 'desktop_image_position', 'right' ),
					'mobile_image_position'  => borgodasso_acf_string_default( 'mobile_image_position', 'above' ),
				)
			);
			break;

		case 'media_block':
			$link = get_sub_field( 'link' );
			$cta  = is_array( $link ) ? $link : array();

			$description = get_sub_field( 'description' );
			if ( is_string( $description ) && '' !== trim( $description ) ) {
				$description = wp_kses_post( wpautop( trim( $description ) ) );
			} else {
				$description = '';
			}

			borgodasso_template(
				'page-builder-components/media-block',
				array(
					'title'                 => borgodasso_acf_string( 'title' ),
					'description'           => $description,
					'specs'                 => borgodasso_media_block_spec_items(
						borgodasso_acf_optional_int( 'spec_square_meters' ),
						borgodasso_acf_optional_int( 'spec_bedrooms' ),
						borgodasso_acf_optional_int( 'spec_bathrooms' ),
						borgodasso_acf_optional_int( 'spec_guests' )
					),
					'button_label'          => ! empty( $cta['title'] ) ? (string) $cta['title'] : '',
					'button_url'            => ! empty( $cta['url'] ) ? (string) $cta['url'] : '',
					'external'              => is_array( $link ) && ! empty( $link['target'] ) && '_blank' === $link['target'],
					'image_id'              => (int) get_sub_field( 'image' ),
					'image_mobile_id'       => (int) get_sub_field( 'image_mobile' ),
					'image_position'        => borgodasso_acf_string_default( 'image_position', 'right' ),
					'image_layout'          => borgodasso_acf_string_default( 'image_layout', 'edge' ),
					'mobile_image_position' => borgodasso_acf_string_default( 'mobile_image_position', 'above' ),
					'section_id'            => wp_unique_id( 'media-block-' ),
				)
			);
			break;

		case 'story_mosaic':
			$text = get_sub_field( 'text' );
			if ( is_string( $text ) && '' !== trim( $text ) ) {
				$text = wp_kses_post( wpautop( trim( $text ) ) );
			} else {
				$text = '';
			}

			borgodasso_template(
				'page-builder-components/story-mosaic',
				array(
					'text'                 => $text,
					'image_left_top_id'    => (int) get_sub_field( 'image_left_top' ),
					'image_left_bottom_id' => (int) get_sub_field( 'image_left_bottom' ),
					'image_right_id'       => (int) get_sub_field( 'image_right' ),
				)
			);
			break;

		case 'media_gallery':
			$images = get_sub_field( 'images' );
			$ids    = array();

			if ( is_array( $images ) ) {
				foreach ( $images as $image ) {
					if ( is_numeric( $image ) ) {
						$ids[] = (int) $image;
					} elseif ( is_array( $image ) && ! empty( $image['ID'] ) ) {
						$ids[] = (int) $image['ID'];
					}
				}
			}

			borgodasso_template(
				'page-builder-components/media-gallery',
				array(
					'image_ids'  => $ids,
					'height'     => borgodasso_acf_string_default( 'height', '660' ),
					'pagination' => borgodasso_acf_string_default( 'pagination', 'none' ),
					'section_id' => wp_unique_id( 'media-gallery-' ),
				)
			);
			break;

		case 'card_list':
			$raw_cards = get_sub_field( 'cards' );
			$cards     = array();

			if ( is_array( $raw_cards ) ) {
				foreach ( $raw_cards as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}

					$image_id = isset( $row['image'] ) ? (int) $row['image'] : 0;
					$title    = isset( $row['title'] ) ? trim( (string) $row['title'] ) : '';
					$desc     = isset( $row['description'] ) ? trim( (string) $row['description'] ) : '';

					if ( $image_id <= 0 || '' === $title ) {
						continue;
					}

					$cards[] = array(
						'image_id'    => $image_id,
						'title'       => $title,
						'description' => '' !== $desc ? wp_kses_post( wpautop( $desc ) ) : '',
					);
				}
			}

			borgodasso_template(
				'page-builder-components/card-list',
				array(
					'cards' => $cards,
				)
			);
			break;
	}
}

/**
 * Normalize an ACF sub field value to a trimmed string.
 *
 * @param string $name Sub field name.
 */
function borgodasso_acf_string( string $name ): string {
	$value = get_sub_field( $name );

	if ( ! is_string( $value ) ) {
		return '';
	}

	return trim( $value );
}

/**
 * ACF sub field as string, or null when empty.
 *
 * @param string $name Sub field name.
 */
function borgodasso_acf_optional_string( string $name ): ?string {
	$value = borgodasso_acf_string( $name );

	return '' !== $value ? $value : null;
}

/**
 * ACF sub field with a fallback default.
 *
 * @param string $name           Sub field name.
 * @param string $fallback_value Value when the field is empty.
 */
function borgodasso_acf_string_default( string $name, string $fallback_value ): string {
	$value = borgodasso_acf_string( $name );

	return '' !== $value ? $value : $fallback_value;
}

/**
 * ACF sub field as a positive integer, or null when empty.
 *
 * @param string $name Sub field name.
 */
function borgodasso_acf_optional_int( string $name ): ?int {
	if ( ! function_exists( 'get_sub_field' ) ) {
		return null;
	}

	$value = get_sub_field( $name );

	if ( null === $value || '' === $value ) {
		return null;
	}

	$int = (int) $value;

	return $int > 0 ? $int : null;
}

/**
 * Media block specification items for inline display.
 *
 * Labels match the Casa specs, including singular and plural gettext forms.
 *
 * @param int|null $square_meters Square meters.
 * @param int|null $bedrooms      Bedroom count.
 * @param int|null $bathrooms     Bathroom count (desktop only in layout).
 * @param int|null $guests        Guest count.
 * @return array<int, array{icon: string, text: string, key: string}>
 */
function borgodasso_media_block_spec_items( ?int $square_meters, ?int $bedrooms, ?int $bathrooms, ?int $guests ): array {
	return borgodasso_casa_spec_items( $square_meters, $bedrooms, $bathrooms, $guests );
}

/**
 * Smaller files for a media block below 768px.
 *
 * A dedicated mobile image contributes its 768 and 1024 candidates.
 * Without one, the desktop photo is served as the existing 960×720 crop,
 * which matches the 1400×1050 desktop crop and skips the portrait thumbnail.
 *
 * @return array<int, array{0: string, 1: int, 2: int}>
 */
function borgodasso_media_block_mobile_candidates( int $desktop_id, int $mobile_id ): array {
	if ( $mobile_id > 0 ) {
		$dedicated = borgodasso_media_block_size_candidates(
			$mobile_id,
			array( 'medium_large', 'large' )
		);

		if ( count( $dedicated ) > 0 ) {
			return $dedicated;
		}
	}

	return borgodasso_media_block_size_candidates(
		$desktop_id,
		array( 'borgodasso_media_md' )
	);
}

/**
 * Attachment sizes that exist as real intermediates, not the original file.
 *
 * @param string[] $sizes Registered size names, smallest first.
 * @return array<int, array{0: string, 1: int, 2: int}>
 */
function borgodasso_media_block_size_candidates( int $attachment_id, array $sizes ): array {
	$full     = wp_get_attachment_image_src( $attachment_id, 'full' );
	$full_url = is_array( $full ) ? (string) $full[0] : '';
	$found    = array();

	foreach ( $sizes as $size ) {
		$src = wp_get_attachment_image_src( $attachment_id, $size );

		if ( ! is_array( $src ) || '' === (string) $src[0] || (string) $src[0] === $full_url ) {
			continue;
		}

		$width = (int) $src[1];

		if ( $width <= 0 ) {
			continue;
		}

		$found[ $width ] = array( (string) $src[0], $width, (int) $src[2] );
	}

	ksort( $found );

	return array_values( $found );
}

/**
 * srcset value for mobile candidates. Escape the result with esc_attr().
 *
 * @param array<int, array{0: string, 1: int, 2: int}> $candidates Candidates.
 */
function borgodasso_media_block_srcset( array $candidates ): string {
	$parts = array();

	foreach ( $candidates as $candidate ) {
		$parts[] = $candidate[0] . ' ' . (int) $candidate[1] . 'w';
	}

	return implode( ', ', $parts );
}

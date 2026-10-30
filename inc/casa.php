<?php
/**
 * Casa page template helpers.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current (or given) page uses the Casa template.
 *
 * @param int|null $post_id Post ID. Defaults to current post.
 */
function borgodasso_is_casa_template( ?int $post_id = null ): bool {
	$post_id = $post_id ?? (int) get_the_ID();

	if ( $post_id <= 0 ) {
		return false;
	}

	return 'page-templates/casa.php' === get_page_template_slug( $post_id );
}

/**
 * Optional positive ACF integer field, or null when empty.
 *
 * @param string   $name    Field name.
 * @param int|null $post_id Post ID.
 */
function borgodasso_casa_optional_int( string $name, ?int $post_id = null ): ?int {
	if ( ! function_exists( 'get_field' ) ) {
		return null;
	}

	$value = get_field( $name, $post_id );

	if ( null === $value || '' === $value ) {
		return null;
	}

	$int = (int) $value;

	return $int > 0 ? $int : null;
}

/**
 * Normalized Casa field data (null when not a Casa page or title is empty).
 *
 * @param int|null $post_id Post ID. Defaults to current post.
 * @return array{
 *   title: string,
 *   description: string,
 *   specs: array<int, array{icon: string, text: string, key: string}>,
 *   prenota: array{title: string, url: string, target: string}|null,
 *   floor_file: array{id: int, url: string, title: string, mime: string}|null,
 *   more_info: array{text: string, image_id: int},
 *   amenities: array<int, string>,
 *   amenities_split: bool,
 *   amenities_image_id: int,
 *   amenities_image_2_id: int,
 *   gallery_ids: array<int, int>,
 *   cta: array{title: string, description: string, link: array{title: string, url: string, target: string}|null, image_id: int},
 *   mobile_landscape_ids: array{0: int, 1: int, 2: int},
 *   mobile_portrait_ids: array{0: int, 1: int}
 * }|null
 */
function borgodasso_casa_data( ?int $post_id = null ): ?array {
	$post_id = $post_id ?? (int) get_the_ID();

	if ( $post_id <= 0 || ! borgodasso_is_casa_template( $post_id ) || ! function_exists( 'get_field' ) ) {
		return null;
	}

	$title = get_field( 'casa_title', $post_id );
	$title = is_string( $title ) ? trim( $title ) : '';

	if ( '' === $title ) {
		return null;
	}

	$description = get_field( 'casa_description', $post_id );
	if ( is_string( $description ) && '' !== trim( $description ) ) {
		$description = wp_kses_post( wpautop( trim( $description ) ) );
	} else {
		$description = '';
	}

	$more_info_text = get_field( 'casa_more_info_text', $post_id );
	if ( is_string( $more_info_text ) && '' !== trim( $more_info_text ) ) {
		$more_info_text = wp_kses_post( wpautop( trim( $more_info_text ) ) );
	} else {
		$more_info_text = '';
	}

	$amenities_raw = get_field( 'casa_amenities', $post_id );
	$amenities     = array();

	if ( is_array( $amenities_raw ) ) {
		foreach ( $amenities_raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$label = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
			if ( '' !== $label ) {
				$amenities[] = $label;
			}
		}
	}

	$amenities_split = (bool) get_field( 'casa_amenities_split', $post_id );

	$gallery_raw = get_field( 'casa_gallery', $post_id );
	$gallery_ids = array();

	if ( is_array( $gallery_raw ) ) {
		foreach ( $gallery_raw as $image ) {
			if ( is_numeric( $image ) ) {
				$gallery_ids[] = (int) $image;
			} elseif ( is_array( $image ) && ! empty( $image['ID'] ) ) {
				$gallery_ids[] = (int) $image['ID'];
			}
		}
	}

	$floor_raw  = get_field( 'casa_floor_file', $post_id );
	$floor_file = null;

	if ( is_array( $floor_raw ) && ! empty( $floor_raw['url'] ) ) {
		$floor_file = array(
			'id'    => isset( $floor_raw['ID'] ) ? (int) $floor_raw['ID'] : 0,
			'url'   => (string) $floor_raw['url'],
			'title' => isset( $floor_raw['title'] ) ? (string) $floor_raw['title'] : '',
			'mime'  => isset( $floor_raw['mime_type'] ) ? (string) $floor_raw['mime_type'] : '',
		);
	}

	$cta_title = get_field( 'casa_cta_title', $post_id );
	$cta_title = is_string( $cta_title ) ? trim( $cta_title ) : '';

	$cta_description = get_field( 'casa_cta_description', $post_id );
	if ( is_string( $cta_description ) && '' !== trim( $cta_description ) ) {
		$cta_description = wp_kses_post( wpautop( trim( $cta_description ) ) );
	} else {
		$cta_description = '';
	}

	return array(
		'title'                => $title,
		'description'          => $description,
		'specs'                => borgodasso_casa_spec_items(
			borgodasso_casa_optional_int( 'spec_square_meters', $post_id ),
			borgodasso_casa_optional_int( 'spec_bedrooms', $post_id ),
			borgodasso_casa_optional_int( 'spec_bathrooms', $post_id ),
			borgodasso_casa_optional_int( 'spec_guests', $post_id )
		),
		'prenota'              => borgodasso_acf_link( get_field( 'casa_prenota', $post_id ) ),
		'floor_file'           => $floor_file,
		'more_info'            => array(
			'text'     => $more_info_text,
			'image_id' => (int) get_field( 'casa_more_info_image', $post_id ),
		),
		'amenities'            => $amenities,
		'amenities_split'      => $amenities_split,
		'amenities_image_id'   => (int) get_field( 'casa_amenities_image', $post_id ),
		'amenities_image_2_id' => $amenities_split ? (int) get_field( 'casa_amenities_image_2', $post_id ) : 0,
		'gallery_ids'          => $gallery_ids,
		'cta'                  => array(
			'title'       => $cta_title,
			'description' => $cta_description,
			'link'        => borgodasso_acf_link( get_field( 'casa_cta_link', $post_id ) ),
			'image_id'    => (int) get_field( 'casa_cta_image', $post_id ),
		),
		'mobile_landscape_ids' => array(
			(int) get_field( 'casa_mobile_landscape_1', $post_id ),
			(int) get_field( 'casa_mobile_landscape_2', $post_id ),
			$amenities_split ? (int) get_field( 'casa_mobile_landscape_3', $post_id ) : 0,
		),
		'mobile_portrait_ids'  => array(
			(int) get_field( 'casa_mobile_portrait_1', $post_id ),
			(int) get_field( 'casa_mobile_portrait_2', $post_id ),
		),
	);
}

/**
 * Split amenities into two halves (first half gets the extra item when odd).
 *
 * @param array<int, string> $amenities Amenity labels.
 * @return array{0: array<int, string>, 1: array<int, string>}
 */
function borgodasso_casa_amenities_halves( array $amenities ): array {
	$count = count( $amenities );

	if ( $count <= 1 ) {
		return array( $amenities, array() );
	}

	$mid = (int) ceil( $count / 2 );

	return array(
		array_slice( $amenities, 0, $mid ),
		array_slice( $amenities, $mid ),
	);
}

/**
 * Apartment specification items (same labels/icons as Media block).
 *
 * @param int|null $square_meters Square meters.
 * @param int|null $bedrooms      Bedroom count.
 * @param int|null $bathrooms     Bathroom count (desktop only in layout).
 * @param int|null $guests        Guest count.
 * @return array<int, array{icon: string, text: string, key: string}>
 */
function borgodasso_casa_spec_items( ?int $square_meters, ?int $bedrooms, ?int $bathrooms, ?int $guests ): array {
	$items = array();

	if ( null !== $square_meters ) {
		$items[] = array(
			'key'  => 'square_meters',
			'icon' => 'square-meters',
			'text' => sprintf(
				/* translators: %d: floor area in square meters. */
				__( '%d M²', 'borgodasso' ),
				$square_meters
			),
		);
	}

	if ( null !== $bedrooms ) {
		$items[] = array(
			'key'  => 'bedrooms',
			'icon' => 'bedrooms',
			'text' => sprintf(
				/* translators: %d: number of bedrooms. */
				_n( '%d Camera da letto', '%d Camere da letto', $bedrooms, 'borgodasso' ),
				$bedrooms
			),
		);
	}

	if ( null !== $bathrooms ) {
		$items[] = array(
			'key'  => 'bathrooms',
			'icon' => 'bagni',
			'text' => sprintf(
				/* translators: %d: number of bathrooms. */
				_n( '%d Bagno', '%d Bagni', $bathrooms, 'borgodasso' ),
				$bathrooms
			),
		);
	}

	if ( null !== $guests ) {
		$items[] = array(
			'key'  => 'guests',
			'icon' => 'ospiti',
			'text' => sprintf(
				/* translators: %d: number of guests. */
				_n( '%d Ospite', '%d Ospiti', $guests, 'borgodasso' ),
				$guests
			),
		);
	}

	return $items;
}

/**
 * Other Casa template pages for the “Le case del Borgo” grid.
 *
 * @param int|null $exclude_id Current page ID to exclude.
 * @param int      $limit      Max cards (default 4).
 * @return array<int, array{title: string, url: string, image_id: int}>
 */
function borgodasso_casa_related( ?int $exclude_id = null, int $limit = 4 ): array {
	$exclude_id = $exclude_id ?? (int) get_the_ID();

	$query = new WP_Query(
		array(
			'post_type'              => 'page',
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'post__not_in'           => $exclude_id > 0 ? array( $exclude_id ) : array(),
			// Template slug lookup — small page set, acceptable for related Casas.
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key'               => '_wp_page_template',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_value'             => 'page-templates/casa.php',
			'orderby'                => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);

	$cards = array();

	foreach ( $query->posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$image_id = 0;
		if ( function_exists( 'get_field' ) ) {
			$image_id = (int) get_field( 'page_header_image_mobile', $post->ID );
			if ( $image_id <= 0 ) {
				$image_id = (int) get_field( 'page_header_image', $post->ID );
			}
		}

		$cards[] = array(
			'title'    => get_the_title( $post ),
			'url'      => (string) get_permalink( $post ),
			'image_id' => $image_id,
		);
	}

	wp_reset_postdata();

	return $cards;
}

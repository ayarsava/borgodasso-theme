<?php
/**
 * Open Graph tags for link previews.
 *
 * A featured image wins. Pages and products without one use Theme Options → Sharing.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default share image from Theme Options.
 */
function borgodasso_default_share_image_id(): int {
	if ( ! function_exists( 'get_field' ) ) {
		return 0;
	}

	return max( 0, (int) get_field( 'default_share_image', 'option' ) );
}

/**
 * Featured image, or another image that belongs to this request.
 */
function borgodasso_featured_share_image_id(): int {
	if ( function_exists( 'is_product' ) && is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product instanceof WC_Product ) {
			$image_id = (int) $product->get_image_id();
			if ( $image_id > 0 ) {
				return $image_id;
			}
		}
	}

	if ( function_exists( 'borgodasso_is_age_gate_template' ) && borgodasso_is_age_gate_template() ) {
		$data = borgodasso_age_gate_data();
		if ( is_array( $data ) && ! empty( $data['image_id'] ) ) {
			return (int) $data['image_id'];
		}
	}

	$post_id = 0;
	if ( function_exists( 'is_shop' ) && is_shop() && function_exists( 'wc_get_page_id' ) ) {
		$post_id = (int) wc_get_page_id( 'shop' );
	} elseif ( is_singular() ) {
		$post_id = (int) get_queried_object_id();
	}

	if ( $post_id > 0 ) {
		$thumb = (int) get_post_thumbnail_id( $post_id );
		if ( $thumb > 0 ) {
			return $thumb;
		}
	}

	$header = function_exists( 'borgodasso_page_header_data' ) ? borgodasso_page_header_data() : null;
	if ( is_array( $header ) ) {
		foreach ( array( 'image_id', 'image_primary' ) as $key ) {
			if ( ! empty( $header[ $key ] ) ) {
				return (int) $header[ $key ];
			}
		}
	}

	return 0;
}

/**
 * Image used in the link preview.
 */
function borgodasso_share_image_id(): int {
	$featured = borgodasso_featured_share_image_id();

	return $featured > 0 ? $featured : borgodasso_default_share_image_id();
}

/**
 * Public URL for this request, without share-token query args.
 */
function borgodasso_share_url(): string {
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return borgodasso_shop_url();
	}

	if ( is_singular() ) {
		$url = get_permalink();
		return is_string( $url ) && '' !== $url ? $url : home_url( '/' );
	}

	if ( is_front_page() ) {
		return home_url( '/' );
	}

	$queried = get_queried_object();
	if ( $queried instanceof WP_Term ) {
		$link = get_term_link( $queried );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}

	return home_url( '/' );
}

/**
 * Plain text for a meta description.
 */
function borgodasso_share_plain_text( string $text ): string {
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ) ?? '' );

	if ( mb_strlen( $text ) <= 300 ) {
		return $text;
	}

	return rtrim( mb_substr( $text, 0, 297 ) ) . '…';
}

/**
 * Description for the link preview.
 */
function borgodasso_share_description(): string {
	$header = function_exists( 'borgodasso_page_header_data' ) ? borgodasso_page_header_data() : null;
	if ( is_array( $header ) && '' !== $header['description'] ) {
		return borgodasso_share_plain_text( $header['description'] );
	}

	if ( function_exists( 'is_product' ) && is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product instanceof WC_Product ) {
			$short = borgodasso_share_plain_text( $product->get_short_description() );
			if ( '' !== $short ) {
				return $short;
			}
		}
	}

	if ( is_singular() ) {
		$post = get_post();
		if ( $post instanceof WP_Post && '' !== $post->post_excerpt ) {
			return borgodasso_share_plain_text( $post->post_excerpt );
		}
	}

	return borgodasso_share_plain_text( get_bloginfo( 'description' ) );
}

/**
 * Attachment data for og:image.
 *
 * @return array{url: string, width: int, height: int, alt: string}|null
 */
function borgodasso_share_image(): ?array {
	$image_id = borgodasso_share_image_id();
	if ( $image_id <= 0 ) {
		return null;
	}

	$src = wp_get_attachment_image_src( $image_id, 'large' );
	if ( ! is_array( $src ) || (int) $src[1] < 600 ) {
		$full = wp_get_attachment_image_src( $image_id, 'full' );
		if ( is_array( $full ) ) {
			$src = $full;
		}
	}

	if ( ! is_array( $src ) || '' === $src[0] ) {
		return null;
	}

	$alt = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );
	if ( '' === $alt ) {
		$alt = (string) get_the_title( $image_id );
	}

	return array(
		'url'    => $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
		'alt'    => $alt,
	);
}

/**
 * Print Open Graph tags. An SEO plugin already owns these when it is active.
 */
function borgodasso_open_graph_tags(): void {
	if ( is_admin() || is_feed() ) {
		return;
	}

	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return;
	}

	$title = wp_get_document_title();
	$url   = borgodasso_share_url();
	if ( '' === $title || '' === $url ) {
		return;
	}

	$type        = ( function_exists( 'is_product' ) && is_product() ) ? 'product' : 'website';
	$description = borgodasso_share_description();
	$image       = borgodasso_share_image();

	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";

	if ( '' !== $description ) {
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	}

	if ( null === $image ) {
		return;
	}

	echo '<meta property="og:image" content="' . esc_url( $image['url'] ) . '">' . "\n";
	echo '<meta property="og:image:alt" content="' . esc_attr( $image['alt'] ) . '">' . "\n";

	if ( $image['width'] > 0 && $image['height'] > 0 ) {
		echo '<meta property="og:image:width" content="' . esc_attr( (string) $image['width'] ) . '">' . "\n";
		echo '<meta property="og:image:height" content="' . esc_attr( (string) $image['height'] ) . '">' . "\n";
	}

	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url( $image['url'] ) . '">' . "\n";
}
add_action( 'wp_head', 'borgodasso_open_graph_tags', 2 );

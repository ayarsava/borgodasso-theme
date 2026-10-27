<?php
/**
 * Blur-up placeholders for slow, large images.
 *
 * Wrap any picture or img with borgodasso_blur_up_open() / borgodasso_blur_up_close().
 * The preview is a tiny inline JPEG, cached on the attachment, and the real image
 * fades in over it once it has loaded (see assets/js/modules/blur-up.ts).
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BORGODASSO_BLUR_META = '_borgodasso_blur_uri';

/**
 * Drop the cached preview when WordPress regenerates attachment sizes.
 *
 * @param array<string, mixed>|mixed $data          Attachment metadata.
 * @param int                         $attachment_id Attachment ID.
 * @return array<string, mixed>|mixed
 */
function borgodasso_blur_preview_invalidate( $data, int $attachment_id ) {
	delete_post_meta( $attachment_id, BORGODASSO_BLUR_META );

	return $data;
}
add_filter( 'wp_update_attachment_metadata', 'borgodasso_blur_preview_invalidate', 10, 2 );

/**
 * Inline JPEG data URI for a very small version of an attachment.
 */
function borgodasso_blur_preview_uri( int $attachment_id ): string {
	if ( $attachment_id <= 0 || ! wp_attachment_is_image( $attachment_id ) ) {
		return '';
	}

	$cached = get_post_meta( $attachment_id, BORGODASSO_BLUR_META, true );

	if ( is_string( $cached ) && str_starts_with( $cached, 'data:image/jpeg;base64,' ) ) {
		return $cached;
	}

	$uri = borgodasso_blur_preview_generate( $attachment_id );

	if ( '' !== $uri ) {
		update_post_meta( $attachment_id, BORGODASSO_BLUR_META, $uri );
	}

	return $uri;
}

/**
 * Open a blur-up wrapper and print the preview, when one can be made.
 *
 * @return bool Whether the wrapper was opened and must be closed.
 */
function borgodasso_blur_up_open( int $attachment_id ): bool {
	$uri = borgodasso_blur_preview_uri( $attachment_id );

	if ( '' === $uri ) {
		return false;
	}

	echo '<span class="blur-up" data-blur-up>';
	printf(
		'<img class="blur-up__preview" src="%s" alt="" width="32" height="32" decoding="sync" aria-hidden="true">',
		esc_attr( $uri )
	);

	return true;
}

/**
 * Close a wrapper opened by borgodasso_blur_up_open().
 */
function borgodasso_blur_up_close( bool $open ): void {
	if ( $open ) {
		echo '</span>';
	}
}

/**
 * Prefer an existing resized file so generation never decodes the full original.
 */
function borgodasso_blur_source_path( int $attachment_id ): string {
	$uploads = wp_get_upload_dir();
	$basedir = isset( $uploads['basedir'] ) ? (string) $uploads['basedir'] : '';

	if ( '' === $basedir ) {
		return '';
	}

	foreach ( array( 'medium', 'thumbnail', 'large' ) as $size ) {
		$info = image_get_intermediate_size( $attachment_id, $size );

		if ( ! is_array( $info ) || empty( $info['path'] ) || ! is_string( $info['path'] ) ) {
			continue;
		}

		$path = path_join( $basedir, $info['path'] );

		if ( is_readable( $path ) ) {
			return $path;
		}
	}

	$file = get_attached_file( $attachment_id );

	if ( ! is_string( $file ) || ! is_readable( $file ) ) {
		return '';
	}

	$bytes = filesize( $file );

	if ( false !== $bytes && $bytes > 8 * MB_IN_BYTES ) {
		return '';
	}

	return $file;
}

/**
 * Build a ~32px JPEG data URI with GD.
 */
function borgodasso_blur_preview_generate( int $attachment_id ): string {
	if ( ! function_exists( 'imagecreatetruecolor' ) || ! function_exists( 'imagejpeg' ) ) {
		return '';
	}

	$path = borgodasso_blur_source_path( $attachment_id );

	if ( '' === $path ) {
		return '';
	}

	$mime  = (string) get_post_mime_type( $attachment_id );
	$image = borgodasso_blur_gd_load( $path, $mime );

	if ( ! $image instanceof GdImage ) {
		return '';
	}

	$width  = imagesx( $image );
	$height = imagesy( $image );

	if ( $width < 1 || $height < 1 ) {
		imagedestroy( $image );
		return '';
	}

	$target_w = 32;
	$target_h = max( 1, (int) round( $height * ( $target_w / $width ) ) );
	$thumb    = imagecreatetruecolor( $target_w, $target_h );

	if ( ! $thumb instanceof GdImage ) {
		imagedestroy( $image );
		return '';
	}

	imagecopyresampled( $thumb, $image, 0, 0, 0, 0, $target_w, $target_h, $width, $height );
	imagedestroy( $image );

	ob_start();
	$written = imagejpeg( $thumb, null, 40 );
	$bytes   = ob_get_clean();
	imagedestroy( $thumb );

	if ( ! $written || ! is_string( $bytes ) || '' === $bytes ) {
		return '';
	}

	return 'data:image/jpeg;base64,' . base64_encode( $bytes );
}

/**
 * Load a GD image from a path for the attachment mime type.
 */
function borgodasso_blur_gd_load( string $path, string $mime ): GdImage|false {
	$image = match ( $mime ) {
		'image/jpeg', 'image/jpg' => function_exists( 'imagecreatefromjpeg' ) ? @imagecreatefromjpeg( $path ) : false,
		'image/png' => function_exists( 'imagecreatefrompng' ) ? @imagecreatefrompng( $path ) : false,
		'image/gif' => function_exists( 'imagecreatefromgif' ) ? @imagecreatefromgif( $path ) : false,
		'image/webp' => function_exists( 'imagecreatefromwebp' ) ? @imagecreatefromwebp( $path ) : false,
		default => false,
	};

	return $image instanceof GdImage ? $image : false;
}

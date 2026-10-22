<?php
/**
 * Theme setup.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register theme supports.
 */
function borgodasso_setup(): void {
	load_theme_textdomain( 'borgodasso', BORGODASSO_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails', array( 'post', 'product' ) );
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 120,
			'width'       => 400,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_image_size( 'borgodasso_media_lg', 1400, 1050, true );
	add_image_size( 'borgodasso_media_md', 960, 720, true );
	add_image_size( 'borgodasso_media_sm', 768, 960, true );
	add_image_size( 'borgodasso_mosaic_landscape', 1200, 800, true );
	add_image_size( 'borgodasso_mosaic_portrait', 900, 1200, true );
	// Page header mobile (393×347).
	add_image_size( 'borgodasso_page_header_sm', 393, 347, true );
	// Desktop menu panel (1140×642).
	add_image_size( 'borgodasso_menu_desktop', 1140, 642, true );
	// Soft-fit for media gallery slides (CSS sets display height).
	add_image_size( 'borgodasso_gallery', 2000, 758, false );
	// Card list thumbnails (445×298).
	add_image_size( 'borgodasso_card', 445, 298, true );
	// Casa related cards — mobile header crop @2x (display ~330×494).
	add_image_size( 'borgodasso_casa_card', 660, 988, true );
	// Cart drawer thumbs — portrait @2x (display ~72×90).
	add_image_size( 'borgodasso_cart_thumb', 144, 180, true );
}
add_action( 'after_setup_theme', 'borgodasso_setup' );

/**
 * Label the fallback page.php template in the editor dropdown.
 */
function borgodasso_default_page_template_title(): string {
	return __( 'Default page', 'borgodasso' );
}
add_filter( 'default_page_template_title', 'borgodasso_default_page_template_title' );

/**
 * Remove featured image support from pages (runs after post types register).
 */
function borgodasso_remove_page_featured_image(): void {
	remove_post_type_support( 'page', 'thumbnail' );
}
add_action( 'init', 'borgodasso_remove_page_featured_image', 20 );

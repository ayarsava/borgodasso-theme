<?php
/**
 * WooCommerce theme setup.
 *
 * Declares WooCommerce support. Ecommerce logic stays in WooCommerce core.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enable WooCommerce theme support after theme setup.
 */
function borgodasso_woocommerce_setup(): void {
	add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'borgodasso_woocommerce_setup' );

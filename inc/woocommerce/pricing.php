<?php
/**
 * WooCommerce pricing hooks.
 *
 * Future promotional / pricing rules via WooCommerce APIs when native options are insufficient.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Show the euro symbol before the amount: “€ 19,50”.
 *
 * @return string
 */
function borgodasso_force_currency_position(): string {
	return 'left_space';
}
add_filter( 'pre_option_woocommerce_currency_pos', 'borgodasso_force_currency_position' );

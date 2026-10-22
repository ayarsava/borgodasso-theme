<?php
/**
 * Borgo d'Asso theme functions.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BORGODASSO_VERSION', '1.0.0' );
define( 'BORGODASSO_DIR', get_template_directory() );
define( 'BORGODASSO_URI', get_template_directory_uri() );

require_once BORGODASSO_DIR . '/inc/templates.php';
require_once BORGODASSO_DIR . '/inc/helpers.php';
require_once BORGODASSO_DIR . '/inc/setup.php';
require_once BORGODASSO_DIR . '/inc/enqueue.php';
require_once BORGODASSO_DIR . '/inc/acf.php';
require_once BORGODASSO_DIR . '/inc/page-builder.php';
require_once BORGODASSO_DIR . '/inc/casa.php';
require_once BORGODASSO_DIR . '/inc/contact.php';
require_once BORGODASSO_DIR . '/inc/age-gate.php';
require_once BORGODASSO_DIR . '/inc/translatepress.php';
require_once BORGODASSO_DIR . '/inc/woocommerce/setup.php';
require_once BORGODASSO_DIR . '/inc/woocommerce/products.php';
require_once BORGODASSO_DIR . '/inc/woocommerce/account.php';
require_once BORGODASSO_DIR . '/inc/woocommerce/cart.php';
require_once BORGODASSO_DIR . '/inc/woocommerce/checkout.php';
require_once BORGODASSO_DIR . '/inc/woocommerce/orders.php';
require_once BORGODASSO_DIR . '/inc/woocommerce/pricing.php';
require_once BORGODASSO_DIR . '/inc/woocommerce/packages.php';

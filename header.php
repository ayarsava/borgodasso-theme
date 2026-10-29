<?php
/**
 * Theme header — document shell + site header partial.
 *
 * @package Borgodasso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_front = is_front_page();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script>
		/* Entrance styles key off html.js. Set it before first paint so the header and hero do not show, hide, then show again. */
		document.documentElement.classList.add('js');
	</script>
	<?php wp_head(); ?>
</head>

<body <?php body_class( $is_front ? array( 'is-front-page' ) : array() ); ?>>
	<?php wp_body_open(); ?>
	<div class="site">
		<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'borgodasso' ); ?></a>
		<?php borgodasso_template( 'components/header' ); ?>
		<main id="main" class="site-main">
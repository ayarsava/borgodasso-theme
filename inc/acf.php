<?php
/**
 * ACF Local JSON paths + Theme Options.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Save ACF field groups to the theme.
 *
 * @param string $path Default save path.
 * @return string
 */
function borgodasso_acf_json_save_point( string $path ): string {
	unset( $path );
	return BORGODASSO_DIR . '/acf-json';
}
add_filter( 'acf/settings/save_json', 'borgodasso_acf_json_save_point' );

/**
 * Load ACF field groups from the theme (single path — avoid duplicate sync).
 *
 * @param array<int, string> $paths Existing load paths.
 * @return array<int, string>
 */
function borgodasso_acf_json_load_point( array $paths ): array {
	// Replace default theme path so the same folder is not registered twice.
	$paths = array( BORGODASSO_DIR . '/acf-json' );
	return $paths;
}
add_filter( 'acf/settings/load_json', 'borgodasso_acf_json_load_point' );

/**
 * Register Theme Options page (ACF).
 */
function borgodasso_register_theme_options(): void {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page(
		array(
			'page_title' => __( 'Theme Options', 'borgodasso' ),
			'menu_title' => __( 'Theme Options', 'borgodasso' ),
			'menu_slug'  => 'borgodasso-theme-options',
			'capability' => 'edit_theme_options',
			'redirect'   => false,
			'position'   => 59,
			'icon_url'   => 'dashicons-admin-generic',
		)
	);
}
add_action( 'acf/init', 'borgodasso_register_theme_options' );

/**
 * Normalize an ACF link field to title + url (+ optional target).
 *
 * @param mixed $link Link field value.
 * @return array{title: string, url: string, target: string}|null
 */
function borgodasso_acf_link( $link ): ?array {
	if ( ! is_array( $link ) || empty( $link['url'] ) ) {
		return null;
	}

	return array(
		'title'  => isset( $link['title'] ) ? trim( (string) $link['title'] ) : '',
		'url'    => (string) $link['url'],
		'target' => ! empty( $link['target'] ) ? (string) $link['target'] : '',
	);
}

/**
 * Footer data from Theme Options.
 *
 * @return array{
 *   column_1: array{title: string, address: string, maps: ?array, vat: string},
 *   column_2: array{title: string, email: string, phone: string, instagram: ?array, terms: ?array},
 *   column_3: array{title: string, text: string, website: ?array, privacy: ?array, cookies: ?array}
 * }
 */
function borgodasso_footer_options(): array {
	$col1 = function_exists( 'get_field' ) ? get_field( 'footer_column_1', 'option' ) : null;
	$col2 = function_exists( 'get_field' ) ? get_field( 'footer_column_2', 'option' ) : null;
	$col3 = function_exists( 'get_field' ) ? get_field( 'footer_column_3', 'option' ) : null;

	$col1 = is_array( $col1 ) ? $col1 : array();
	$col2 = is_array( $col2 ) ? $col2 : array();
	$col3 = is_array( $col3 ) ? $col3 : array();

	return array(
		'column_1' => array(
			'title'   => isset( $col1['title'] ) ? trim( (string) $col1['title'] ) : '',
			'address' => isset( $col1['address'] ) ? trim( (string) $col1['address'] ) : '',
			'maps'    => borgodasso_acf_link( $col1['maps_link'] ?? null ),
			'vat'     => isset( $col1['vat'] ) ? trim( (string) $col1['vat'] ) : '',
		),
		'column_2' => array(
			'title'     => isset( $col2['title'] ) ? trim( (string) $col2['title'] ) : '',
			'email'     => isset( $col2['email'] ) ? trim( (string) $col2['email'] ) : '',
			'phone'     => isset( $col2['phone'] ) ? trim( (string) $col2['phone'] ) : '',
			'instagram' => borgodasso_acf_link( $col2['instagram'] ?? null ),
			'terms'     => borgodasso_acf_link( $col2['terms_link'] ?? null ),
		),
		'column_3' => array(
			'title'   => isset( $col3['title'] ) ? trim( (string) $col3['title'] ) : '',
			'text'    => isset( $col3['text'] ) ? trim( (string) $col3['text'] ) : '',
			'website' => borgodasso_acf_link( $col3['website'] ?? null ),
			'privacy' => borgodasso_acf_link( $col3['privacy_link'] ?? null ),
			'cookies' => borgodasso_acf_link( $col3['cookies_link'] ?? null ),
		),
	);
}

/**
 * Header menu data from Theme Options.
 *
 * @return array{
 *   items: array<int, array{label: string, url: string, target: string, image_id: int, is_active: bool}>,
 *   desktop_actions: array<int, array{label: string, url: string, target: string, style: string}>,
 *   mobile_icons: array<int, array{label: string, url: string, target: string, icon: string}>,
 *   mobile_secondary: array<int, array{label: string, url: string, target: string, is_external: bool}>,
 *   cantina: ?array{title: string, url: string, target: string}
 * }
 */
function borgodasso_header_options(): array {
	$raw_items = function_exists( 'get_field' ) ? get_field( 'header_menu_items', 'option' ) : null;
	$raw_items = is_array( $raw_items ) ? $raw_items : array();

	$items = array();

	foreach ( $raw_items as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$label = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
		$link  = borgodasso_acf_link( $row['link'] ?? null );

		if ( '' === $label || null === $link ) {
			continue;
		}

		$url = $link['url'];

		$items[] = array(
			'label'     => $label,
			'url'       => $url,
			'target'    => $link['target'],
			'image_id'  => isset( $row['image'] ) ? (int) $row['image'] : 0,
			'is_active' => borgodasso_menu_item_is_active( $url ),
		);
	}

	$raw_actions     = function_exists( 'get_field' ) ? get_field( 'header_desktop_actions', 'option' ) : null;
	$raw_actions     = is_array( $raw_actions ) ? $raw_actions : array();
	$desktop_actions = array();

	foreach ( $raw_actions as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$style = isset( $row['style'] ) ? (string) $row['style'] : 'text';
		if ( ! in_array( $style, array( 'text', 'button', 'cart' ), true ) ) {
			$style = 'text';
		}

		$label = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';

		if ( 'cart' === $style ) {
			if ( '' === $label ) {
				$label = sprintf(
					/* translators: %d: number of products in the cart. */
					__( 'Carrello (%d)', 'borgodasso' ),
					borgodasso_cart_count()
				);
			} elseif ( false !== strpos( $label, '%' ) ) {
				$label = sprintf( $label, borgodasso_cart_count() );
			}

			$desktop_actions[] = array(
				'label'  => $label,
				'url'    => borgodasso_cart_url(),
				'target' => '',
				'style'  => 'cart',
			);
			continue;
		}

		$link = borgodasso_acf_link( $row['link'] ?? null );

		if ( '' === $label || null === $link ) {
			continue;
		}

		$desktop_actions[] = array(
			'label'  => borgodasso_header_account_label( $label, $link['url'] ),
			'url'    => $link['url'],
			'target' => $link['target'],
			'style'  => $style,
		);
	}

	$raw_icons     = function_exists( 'get_field' ) ? get_field( 'header_mobile_icons', 'option' ) : null;
	$raw_icons     = is_array( $raw_icons ) ? $raw_icons : array();
	$mobile_icons  = array();
	$allowed_icons = array( 'bedrooms', 'account', 'shop' );

	foreach ( $raw_icons as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$icon = isset( $row['icon'] ) ? trim( (string) $row['icon'] ) : '';
		if ( ! in_array( $icon, $allowed_icons, true ) ) {
			continue;
		}

		$label = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
		$link  = borgodasso_acf_link( $row['link'] ?? null );

		if ( null === $link ) {
			continue;
		}

		if ( '' === $label ) {
			$label = '' !== $link['title'] ? $link['title'] : ucfirst( $icon );
		}

		$mobile_icons[] = array(
			'label'  => 'account' === $icon
				? borgodasso_header_account_label( $label, $link['url'] )
				: $label,
			'url'    => $link['url'],
			'target' => $link['target'],
			'icon'   => $icon,
		);
	}

	$raw_secondary    = function_exists( 'get_field' ) ? get_field( 'header_mobile_secondary', 'option' ) : null;
	$raw_secondary    = is_array( $raw_secondary ) ? $raw_secondary : array();
	$mobile_secondary = array();

	foreach ( $raw_secondary as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$label = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
		$link  = borgodasso_acf_link( $row['link'] ?? null );

		if ( '' === $label || null === $link ) {
			continue;
		}

		$mobile_secondary[] = array(
			'label'       => borgodasso_header_account_label( $label, $link['url'] ),
			'url'         => $link['url'],
			'target'      => $link['target'],
			'is_external' => '_blank' === $link['target'],
		);
	}

	$cantina = borgodasso_acf_link(
		function_exists( 'get_field' ) ? get_field( 'header_cantina_link', 'option' ) : null
	);

	if ( null === $cantina ) {
		$footer  = borgodasso_footer_options();
		$cantina = $footer['column_3']['website'];
	}

	return array(
		'items'            => $items,
		'desktop_actions'  => $desktop_actions,
		'mobile_icons'     => $mobile_icons,
		'mobile_secondary' => $mobile_secondary,
		'cantina'          => $cantina,
	);
}

/**
 * Mobile footer links from Theme Options, split into two columns.
 *
 * @return array{0: array<int, array{label: string, url: string, target: string}>, 1: array<int, array{label: string, url: string, target: string}>}|null
 */
function borgodasso_mobile_footer_link_columns(): ?array {
	$raw = function_exists( 'get_field' ) ? get_field( 'footer_mobile_links', 'option' ) : null;
	$raw = is_array( $raw ) ? $raw : array();

	$items = array();

	foreach ( $raw as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$label = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
		$link  = borgodasso_acf_link( $row['link'] ?? null );

		if ( '' === $label || null === $link ) {
			continue;
		}

		$items[] = array(
			'label'  => $label,
			'url'    => $link['url'],
			'target' => $link['target'],
		);
	}

	if ( array() === $items ) {
		return null;
	}

	$split_at = (int) ceil( count( $items ) / 2 );

	return array(
		array_slice( $items, 0, $split_at ),
		array_slice( $items, $split_at ),
	);
}

/**
 * —— ACF fields on WooCommerce product variations ——
 *
 * ACF does not render on variations by default. Location JSON uses
 * post_type = product_variation; these hooks surface + save those groups.
 */

/**
 * Expose “Product Variation” in ACF location rules.
 *
 * @param array<string, string> $choices Post type choices.
 * @return array<string, string>
 */
function borgodasso_acf_location_rule_values_post_type( array $choices ): array {
	$choices['product_variation'] = __( 'Product Variation', 'borgodasso' );
	return $choices;
}
add_filter( 'acf/location/rule_values/post_type', 'borgodasso_acf_location_rule_values_post_type' );

/**
 * Match product_variation location rules.
 *
 * @param bool                 $rule_matches Whether the rule matches.
 * @param array<string, mixed> $rule         Location rule.
 * @param array<string, mixed> $options      Screen options.
 * @param array<string, mixed> $field_group  Field group.
 */
function borgodasso_acf_location_rule_match_post_type( bool $rule_matches, array $rule, array $options, array $field_group ): bool {
	unset( $field_group );

	if ( 'product_variation' !== ( $rule['value'] ?? '' ) ) {
		return $rule_matches;
	}

	$post_type = isset( $options['post_type'] ) ? (string) $options['post_type'] : '';

	if ( '==' === ( $rule['operator'] ?? '' ) ) {
		return 'product_variation' === $post_type;
	}

	if ( '!=' === ( $rule['operator'] ?? '' ) ) {
		return 'product_variation' !== $post_type;
	}

	return $match;
}
add_filter( 'acf/location/rule_match/post_type', 'borgodasso_acf_location_rule_match_post_type', 10, 4 );

/**
 * Variation loop index while rendering ACF fields in the WC admin UI.
 *
 * @var int|null
 */
$GLOBALS['borgodasso_variation_acf_loop'] = null;

/**
 * Prefix variation ACF inputs so they do not collide with parent product fields.
 *
 * @param array<string, mixed> $field Field.
 * @return array<string, mixed>
 */
function borgodasso_acf_prepare_variation_field_name( array $field ): array {
	$loop = $GLOBALS['borgodasso_variation_acf_loop'] ?? null;
	if ( null === $loop || ! isset( $field['key'] ) ) {
		return $field;
	}

	$field['name'] = 'acf_variation[' . (int) $loop . '][' . $field['key'] . ']';
	$field['id']   = 'acf_variation-' . (int) $loop . '-' . $field['key'];

	return $field;
}

/**
 * Render ACF field groups inside each variation panel.
 *
 * @param int     $loop           Variation index.
 * @param array   $variation_data Variation meta (unused).
 * @param WP_Post $variation      Variation post.
 */
function borgodasso_acf_render_variation_fields( int $loop, array $variation_data, $variation ): void {
	unset( $variation_data );

	if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_render_fields' ) ) {
		return;
	}

	if ( ! $variation instanceof WP_Post ) {
		return;
	}

	$field_groups = acf_get_field_groups(
		array(
			'post_type' => 'product_variation',
		)
	);

	if ( array() === $field_groups ) {
		return;
	}

	$GLOBALS['borgodasso_variation_acf_loop'] = $loop;
	add_filter( 'acf/prepare_field', 'borgodasso_acf_prepare_variation_field_name' );

	echo '<div class="form-row form-row-full borgodasso-variation-acf">';

	foreach ( $field_groups as $field_group ) {
		$fields = acf_get_fields( $field_group );
		if ( ! is_array( $fields ) || array() === $fields ) {
			continue;
		}
		acf_render_fields( $fields, $variation->ID );
	}

	echo '</div>';

	remove_filter( 'acf/prepare_field', 'borgodasso_acf_prepare_variation_field_name' );
	$GLOBALS['borgodasso_variation_acf_loop'] = null;
}
add_action( 'woocommerce_product_after_variable_attributes', 'borgodasso_acf_render_variation_fields', 10, 3 );

/**
 * Save ACF values for a variation.
 *
 * @param int $variation_id Variation post ID.
 * @param int $i            Variation loop index.
 */
function borgodasso_acf_save_variation_fields( int $variation_id, int $i ): void {
	if ( ! function_exists( 'acf_update_values' ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC product save verifies nonce.
	if ( empty( $_POST['acf_variation'][ $i ] ) || ! is_array( $_POST['acf_variation'][ $i ] ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$values = wp_unslash( $_POST['acf_variation'][ $i ] );
	if ( ! is_array( $values ) ) {
		return;
	}

	acf_update_values( $values, $variation_id );
}
add_action( 'woocommerce_save_product_variation', 'borgodasso_acf_save_variation_fields', 10, 2 );

/**
 * Ensure ACF admin assets load on the product editor (variations are AJAX-loaded).
 *
 * @param string $hook Current admin page hook.
 */
function borgodasso_acf_enqueue_product_admin_assets( string $hook ): void {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'product' !== $screen->post_type ) {
		return;
	}

	if ( function_exists( 'acf_enqueue_scripts' ) ) {
		acf_enqueue_scripts();
	}
}
add_action( 'admin_enqueue_scripts', 'borgodasso_acf_enqueue_product_admin_assets' );

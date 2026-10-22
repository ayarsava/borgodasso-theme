<?php
/**
 * Theme helpers.
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Attachment alt text with a readable fallback.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $fallback      Used when alt is empty in the media library.
 */
function borgodasso_attachment_alt( int $attachment_id, string $fallback = '' ): string {
	$alt = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );

	if ( '' !== $alt ) {
		return $alt;
	}

	return trim( $fallback );
}

/**
 * Header color variant: light (cream) on homepage, hero, and Contact; dark elsewhere.
 */
function borgodasso_header_variant(): string {
	if ( is_front_page() ) {
		return 'light';
	}

	if ( borgodasso_is_contact_template() ) {
		return 'light';
	}

	if ( function_exists( 'borgodasso_is_age_gate_template' ) && borgodasso_is_age_gate_template() ) {
		return 'light';
	}

	if ( 'hero' === borgodasso_page_header_layout() ) {
		return 'light';
	}

	return 'dark';
}

/**
 * Page header layout when the page header is active.
 *
 * @return 'text'|'hero'|'single'|'dual'|null
 */
function borgodasso_page_header_layout(): ?string {
	$header = borgodasso_page_header_data();

	if ( null === $header ) {
		return null;
	}

	return $header['layout'];
}

/**
 * Whether a menu URL matches the current request.
 *
 * @param string $url Menu item URL.
 */
function borgodasso_menu_item_is_active( string $url ): bool {
	if ( '' === $url || '#' === $url ) {
		return false;
	}

	$current = home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) );
	$parsed  = wp_parse_url( $url );
	$path    = isset( $parsed['path'] ) ? untrailingslashit( (string) $parsed['path'] ) : '';

	if ( '' === $path || '/' === $path ) {
		return is_front_page();
	}

	return untrailingslashit( wp_parse_url( $current, PHP_URL_PATH ) ?? '' ) === $path;
}

/**
 * Language switcher links for the mobile menu (TranslatePress when available).
 *
 * @return array<int, array{code: string, label: string, url: string, current: bool}>
 */
function borgodasso_header_languages(): array {
	if ( class_exists( 'TRP_Translate_Press' ) ) {
		$trp           = TRP_Translate_Press::get_trp_instance();
		$url_converter = $trp->get_component( 'url_converter' );
		$settings      = $trp->get_component( 'settings' )->get_settings();

		if ( is_object( $url_converter ) && is_array( $settings ) ) {
			$default   = isset( $settings['default-language'] ) ? (string) $settings['default-language'] : 'it_IT';
			$published = isset( $settings['publish-languages'] ) && is_array( $settings['publish-languages'] )
				? $settings['publish-languages']
				: array( $default );
			$current   = isset( $settings['url-slugs'] ) && is_array( $settings['url-slugs'] )
				? (string) get_locale()
				: $default;

			if ( method_exists( $url_converter, 'get_lang_from_url_string' ) ) {
				$detected = $url_converter->get_lang_from_url_string( home_url( $GLOBALS['wp']->request ?? '' ) );
				if ( is_string( $detected ) && '' !== $detected ) {
					$current = $detected;
				}
			}

			$labels = array(
				'it_IT' => 'IT',
				'en_US' => 'EN',
			);

			$languages = array();

			foreach ( $published as $locale ) {
				$locale = (string) $locale;
				$code   = isset( $labels[ $locale ] ) ? $labels[ $locale ] : strtoupper( substr( $locale, 0, 2 ) );
				$url    = method_exists( $url_converter, 'get_url_for_language' )
					? (string) $url_converter->get_url_for_language( $locale )
					: home_url( '/' );

				$languages[] = array(
					'code'    => $code,
					'label'   => $code,
					'url'     => $url,
					'current' => $locale === $current,
				);
			}

			if ( array() !== $languages ) {
				return $languages;
			}
		}
	}

	$locale = strtolower( substr( get_locale(), 0, 2 ) );

	return array(
		array(
			'code'    => 'IT',
			'label'   => 'IT',
			'url'     => home_url( '/' ),
			'current' => 'it' === $locale,
		),
		array(
			'code'    => 'EN',
			'label'   => 'EN',
			'url'     => home_url( '/en/' ),
			'current' => 'en' === $locale,
		),
	);
}

/**
 * Post ID that owns the page-header ACF fields for the current request.
 *
 * Regular pages use the queried page. The WooCommerce shop archive is not
 * singular, so fields are read from the configured Shop page.
 */
function borgodasso_page_header_source_id(): int {
	if ( is_singular( 'page' ) ) {
		return (int) get_queried_object_id();
	}

	if ( function_exists( 'is_shop' ) && is_shop() && function_exists( 'wc_get_page_id' ) ) {
		$shop_id = (int) wc_get_page_id( 'shop' );

		return $shop_id > 0 ? $shop_id : 0;
	}

	return 0;
}

/**
 * Page header data from ACF (null when header should not render).
 *
 * @return array{
 *   layout: 'text'|'hero'|'single'|'dual',
 *   title: string,
 *   description: string,
 *   image_id: int,
 *   image_mobile: int,
 *   image_primary: int,
 *   image_secondary: int
 * }|null
 */
function borgodasso_page_header_data(): ?array {
	$post_id = borgodasso_page_header_source_id();

	if ( $post_id <= 0 ) {
		return null;
	}

	if ( function_exists( 'get_field' ) && false === get_field( 'page_header_enable', $post_id ) ) {
		return null;
	}

	$layout = 'single';
	if ( function_exists( 'get_field' ) ) {
		$acf_layout = get_field( 'page_header_image_layout', $post_id );
		if ( is_string( $acf_layout ) && in_array( $acf_layout, array( 'text', 'hero', 'single', 'dual' ), true ) ) {
			$layout = $acf_layout;
		}
	}

	$image_id        = 0;
	$image_mobile    = 0;
	$image_primary   = 0;
	$image_secondary = 0;

	if ( function_exists( 'get_field' ) && 'text' !== $layout ) {
		$image_mobile = (int) get_field( 'page_header_image_mobile', $post_id );

		if ( 'dual' === $layout ) {
			$image_primary   = (int) get_field( 'page_header_image_primary', $post_id );
			$image_secondary = (int) get_field( 'page_header_image_secondary', $post_id );
		} else {
			$image_id = (int) get_field( 'page_header_image', $post_id );
		}
	}

	if ( 'dual' === $layout ) {
		if ( ! $image_primary || ! $image_secondary || ! $image_mobile ) {
			return null;
		}
	} elseif ( in_array( $layout, array( 'single', 'hero' ), true ) && ( ! $image_id || ! $image_mobile ) ) {
		return null;
	}

	$title = '';
	if ( function_exists( 'get_field' ) ) {
		$acf_title = get_field( 'page_header_title', $post_id );
		if ( is_string( $acf_title ) && '' !== trim( $acf_title ) ) {
			$title = trim( $acf_title );
		}
	}

	// Contact: only the Header title field — never fall back to the WP page title.
	if ( '' === $title && ! borgodasso_is_contact_template() ) {
		$title = get_the_title( $post_id );
	}

	$description = '';
	if ( function_exists( 'get_field' ) && 'hero' !== $layout ) {
		$acf_description = get_field( 'page_header_description', $post_id );
		if ( is_string( $acf_description ) ) {
			$description = trim( $acf_description );
		}
	}

	if ( 'text' === $layout && '' === $title && '' === $description ) {
		return null;
	}

	// Hero normally needs a title; Contact may use a full-screen image with no title.
	if ( 'hero' === $layout && '' === $title && ! borgodasso_is_contact_template() ) {
		return null;
	}

	return array(
		'layout'          => $layout,
		'title'           => $title,
		'description'     => $description,
		'image_id'        => $image_id,
		'image_mobile'    => $image_mobile,
		'image_primary'   => $image_primary,
		'image_secondary' => $image_secondary,
	);
}

/**
 * Render a responsive page-header picture (desktop + optional mobile art direction).
 *
 * @param int    $desktop_id   Desktop attachment ID.
 * @param int    $mobile_id    Mobile attachment ID (0 = desktop only, no art-direction source).
 * @param string $alt_fallback Alt fallback when media library alt is empty.
 * @param string $img_class    Class for the img element.
 * @param string $mobile_size  Registered image size for the mobile source.
 */
function borgodasso_page_header_picture(
	int $desktop_id,
	int $mobile_id,
	string $alt_fallback,
	string $img_class = 'page-header__img',
	string $mobile_size = 'borgodasso_page_header_sm'
): void {
	if ( $desktop_id <= 0 ) {
		return;
	}

	$alt         = borgodasso_attachment_alt( $desktop_id, $alt_fallback );
	$desktop_src = wp_get_attachment_image_src( $desktop_id, 'full' );

	if ( ! is_array( $desktop_src ) ) {
		return;
	}

	echo '<picture class="page-header__picture">';

	if ( $mobile_id > 0 ) {
		$mobile_src = wp_get_attachment_image_src( $mobile_id, $mobile_size );

		if ( is_array( $mobile_src ) ) {
			$mobile_w = (int) $mobile_src[1];
			$mobile_h = (int) $mobile_src[2];

			printf(
				'<source media="(max-width: 767px)" srcset="%1$s" width="%2$d" height="%3$d">',
				esc_url( $mobile_src[0] ),
				absint( $mobile_w > 0 ? $mobile_w : 393 ),
				absint( $mobile_h > 0 ? $mobile_h : 347 )
			);
		}
	}

	echo wp_get_attachment_image(
		$desktop_id,
		'full',
		false,
		array(
			'class'    => $img_class,
			'loading'  => 'eager',
			'decoding' => 'async',
			'alt'      => $alt,
			'sizes'    => '(min-width: 768px) 58vw, 100vw',
		)
	);

	echo '</picture>';
}

/**
 * Whether the current page uses the split page-header layout.
 */
function borgodasso_page_has_header(): bool {
	return null !== borgodasso_page_header_data();
}

/**
 * WooCommerce cart item count.
 */
function borgodasso_cart_count(): int {
	if ( function_exists( 'WC' ) && WC()->cart ) {
		return (int) WC()->cart->get_cart_contents_count();
	}

	return 0;
}

/**
 * Account or login URL.
 */
function borgodasso_login_url(): string {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$account = wc_get_page_permalink( 'myaccount' );
		if ( $account ) {
			return $account;
		}
	}

	return wp_login_url();
}

/**
 * Shop URL.
 */
function borgodasso_shop_url(): string {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$shop = wc_get_page_permalink( 'shop' );
		if ( $shop ) {
			return $shop;
		}
	}

	return home_url( '/shop/' );
}

/**
 * Cart URL.
 */
function borgodasso_cart_url(): string {
	if ( function_exists( 'wc_get_cart_url' ) ) {
		return wc_get_cart_url();
	}

	return home_url( '/cart/' );
}

/**
 * Checkout URL.
 */
function borgodasso_checkout_url(): string {
	if ( function_exists( 'wc_get_checkout_url' ) ) {
		return wc_get_checkout_url();
	}

	return home_url( '/checkout/' );
}

/**
 * Whether a URL points at the WooCommerce My Account page.
 *
 * @param string $url Absolute or relative URL to inspect.
 */
function borgodasso_is_account_url( string $url ): bool {
	if ( '' === $url ) {
		return false;
	}

	$path = (string) ( wp_parse_url( $url, PHP_URL_PATH ) ?? '' );
	$path = untrailingslashit( strtolower( $path ) );

	if ( '' !== $path && false !== strpos( $path, '/my-account' ) ) {
		return true;
	}

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$account = (string) wc_get_page_permalink( 'myaccount' );
		if ( '' !== $account ) {
			$account_path = untrailingslashit( strtolower( (string) ( wp_parse_url( $account, PHP_URL_PATH ) ?? '' ) ) );

			return '' !== $account_path && $path === $account_path;
		}
	}

	return false;
}

/**
 * Header label for the account link — "Account" when logged in, CMS label otherwise.
 *
 * @param string $label CMS / default link label.
 * @param string $url   Link URL (used to detect My Account).
 */
function borgodasso_header_account_label( string $label, string $url ): string {
	if ( ! is_user_logged_in() || ! borgodasso_is_account_url( $url ) ) {
		return $label;
	}

	return __( 'Account', 'borgodasso' );
}

/**
 * Body classes for layout and header theme.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function borgodasso_body_classes( array $classes ): array {
	if ( borgodasso_is_contact_template() ) {
		$classes[] = 'is-contact-page';
	}

	if ( function_exists( 'borgodasso_is_age_gate_template' ) && borgodasso_is_age_gate_template() ) {
		$classes[] = 'is-age-gate-page';
	}

	if ( borgodasso_page_has_header() ) {
		$classes[] = 'has-page-header';

		$layout = borgodasso_page_header_layout();

		if ( 'text' === $layout ) {
			$classes[] = 'has-page-header-text';
		}

		if ( 'hero' === $layout ) {
			$classes[] = 'has-page-header-hero';
		}
	}

	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
		$classes[] = 'is-commerce-page';
	}

	return $classes;
}
add_filter( 'body_class', 'borgodasso_body_classes' );

<?php
/**
 * WooCommerce product hooks and PDP helpers.
 *
 * Catalog model (launch):
 * - Global attributes: pa_annata, pa_formato
 * - Wine: Variable, Annata only; sell unit = Cassa da 6 (price/stock per case)
 * - Olive oil: Variable, Annata × Formato (250/500/750 ml)
 * - Pasta / legumes: Simple; size via ACF sale_format_label
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Strip default WooCommerce single-product chrome; rebuild via theme templates.
 */
function borgodasso_woocommerce_product_hooks(): void {
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

	// Shop page uses the theme page header instead of the WC archive title.
	if ( function_exists( 'is_shop' ) && is_shop() && borgodasso_page_has_header() ) {
		remove_action( 'woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header', 10 );
	}

	// Custom product-card grid — drop WC result count / ordering chrome.
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );

	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

	add_action( 'woocommerce_single_product_summary', 'borgodasso_template_single_sale_line', 10 );
	add_action( 'woocommerce_single_product_summary', 'borgodasso_template_single_description', 20 );
	add_action( 'woocommerce_single_product_summary', 'borgodasso_template_single_info_rows', 40 );
	add_action( 'woocommerce_after_single_product', 'borgodasso_template_single_info_drawer', 5 );
	add_action( 'woocommerce_after_single_product', 'borgodasso_template_single_product_catalog', 20 );

	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

	if ( function_exists( 'is_product' ) && is_product() ) {
		remove_action( 'woocommerce_before_single_product', 'woocommerce_output_all_notices', 10 );
		add_action( 'woocommerce_after_add_to_cart_button', 'borgodasso_print_product_add_notices', 20 );

		// PDP: Aggiungi only — hide Stripe / PayPal express checkout buttons.
		add_filter( 'wc_stripe_hide_payment_request_on_product_page', '__return_true' );
		add_filter( 'woocommerce_paypal_payments_product_buttons_disabled', '__return_true' );
	}
}
add_action( 'wp', 'borgodasso_woocommerce_product_hooks' );

/**
 * Print WC notices under the add-to-cart button (instead of above the PDP).
 */
function borgodasso_print_product_add_notices(): void {
	if ( ! function_exists( 'wc_notice_count' ) || 0 === wc_notice_count() ) {
		return;
	}

	echo '<div class="product-pdp__notice" data-product-notice>';
	woocommerce_output_all_notices();
	echo '</div>';
}

/**
 * Body class for single product layout.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function borgodasso_product_body_class( array $classes ): array {
	if ( function_exists( 'is_product' ) && is_product() ) {
		$classes[] = 'is-product-page';
	}

	return $classes;
}
add_filter( 'body_class', 'borgodasso_product_body_class' );

/**
 * Add-to-cart button label (price appended in the template / JS).
 *
 * @param string $text Default label.
 * @return string
 */
function borgodasso_single_add_to_cart_text( string $text ): string {
	unset( $text );
	return __( 'AGGIUNGI', 'borgodasso' );
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'borgodasso_single_add_to_cart_text' );

/**
 * Hide stock HTML on the PDP (availability still enforced at add-to-cart).
 *
 * @param string $html Stock HTML.
 * @return string
 */
function borgodasso_hide_single_stock_html( string $html ): string {
	if ( function_exists( 'is_product' ) && is_product() ) {
		return '';
	}

	return $html;
}
add_filter( 'woocommerce_get_stock_html', 'borgodasso_hide_single_stock_html' );

/**
 * Sale format label from ACF (product or variation).
 *
 * Variations fall back to the parent product label when empty.
 *
 * @param WC_Product|null $product Product or variation.
 */
function borgodasso_product_sale_format_label( $product = null ): string {
	$product = $product instanceof WC_Product ? $product : ( function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null );
	if ( ! $product ) {
		return '';
	}

	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$label = get_field( 'sale_format_label', $product->get_id() );
	if ( is_string( $label ) && '' !== trim( $label ) ) {
		return trim( $label );
	}

	if ( $product->is_type( 'variation' ) ) {
		$parent_id = $product->get_parent_id();
		if ( $parent_id > 0 ) {
			$parent_label = get_field( 'sale_format_label', $parent_id );
			if ( is_string( $parent_label ) && '' !== trim( $parent_label ) ) {
				return trim( $parent_label );
			}
		}
	}

	return '';
}

/**
 * Sale format label set directly on this product/variation (no parent fallback).
 *
 * @param WC_Product $product Product or variation.
 */
function borgodasso_product_sale_format_label_own( WC_Product $product ): string {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$label = get_field( 'sale_format_label', $product->get_id() );
	return is_string( $label ) ? trim( $label ) : '';
}

/**
 * Inject per-variation sale format into WC variation JSON for the PDP.
 *
 * @param array<string, mixed> $data      Variation data.
 * @param WC_Product           $product   Parent variable product.
 * @param WC_Product_Variation $variation Variation.
 * @return array<string, mixed>
 */
function borgodasso_available_variation_sale_format( array $data, WC_Product $product, WC_Product_Variation $variation ): array {
	unset( $product );
	$data['sale_format_label'] = borgodasso_product_sale_format_label_own( $variation );
	return $data;
}
add_filter( 'woocommerce_available_variation', 'borgodasso_available_variation_sale_format', 10, 3 );

/**
 * Catalog card price HTML — “A partire da …” for variable products with a range.
 *
 * @param WC_Product $product Product.
 */
function borgodasso_product_catalog_price_html( WC_Product $product ): string {
	if ( $product->is_type( 'variable' ) && $product instanceof WC_Product_Variable ) {
		$min = $product->get_variation_price( 'min', true );
		$max = $product->get_variation_price( 'max', true );

		if ( '' !== $min && is_numeric( $min ) && (string) $min !== (string) $max ) {
			return wp_kses_post(
				sprintf(
					/* translators: %s: formatted minimum price */
					__( 'A partire da %s', 'borgodasso' ),
					wc_price( (float) $min )
				)
			);
		}
	}

	return $product->get_price_html();
}

/**
 * Static formato display (wine). Empty when Formato is a real variation attribute.
 *
 * @param WC_Product|null $product Product.
 */
function borgodasso_product_formato_display( $product = null ): string {
	$product = $product instanceof WC_Product ? $product : ( function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null );
	if ( ! $product ) {
		return '';
	}

	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute ) {
			continue;
		}

		$name = $attribute->get_name();
		if ( 'pa_formato' === $name || 'formato' === $name ) {
			return '';
		}
	}

	if ( function_exists( 'get_field' ) ) {
		$label = get_field( 'formato_display', $product->get_id() );
		if ( is_string( $label ) && '' !== trim( $label ) ) {
			return trim( $label );
		}
	}

	return '';
}

/**
 * Visible attribute rows for a simple product (label + term names).
 *
 * Single-value attributes use the same static PDP rows as a variable product
 * with one option. Multiple terms are listed together.
 *
 * @param WC_Product $product Product.
 * @return array<int, array{label: string, value: string}>
 */
function borgodasso_product_attribute_rows( WC_Product $product ): array {
	$rows = array();

	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute ) {
			continue;
		}

		$options = $attribute->get_options();
		if ( ! is_array( $options ) || array() === $options ) {
			continue;
		}

		$name   = $attribute->get_name();
		$labels = array();

		if ( $attribute->is_taxonomy() ) {
			foreach ( $options as $option ) {
				$term = is_numeric( $option ) ? get_term( (int) $option, $name ) : get_term_by( 'slug', (string) $option, $name );
				if ( $term instanceof WP_Term ) {
					$labels[] = $term->name;
				}
			}
		} else {
			foreach ( $options as $option ) {
				$option = trim( (string) $option );
				if ( '' !== $option ) {
					$labels[] = $option;
				}
			}
		}

		$labels = array_values( array_filter( $labels, static fn( string $label ): bool => '' !== trim( $label ) ) );
		if ( array() === $labels ) {
			continue;
		}

		$rows[] = array(
			'label' => wc_attribute_label( $name ),
			'value' => implode( ', ', $labels ),
		);
	}

	return $rows;
}

/**
 * Title + price line under the product name.
 */
function borgodasso_template_single_sale_line(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$format     = borgodasso_product_sale_format_label( $product );
	$price_html = $product->get_price_html();
	$has_format = '' !== $format;
	?>
	<p
		class="product-pdp__sale-line"
		data-sale-line
		data-default-format="<?php echo esc_attr( $format ); ?>"
		data-default-price="<?php echo esc_attr( $price_html ); ?>"
	>
		<span class="product-pdp__sale-format" data-sale-format<?php echo $has_format ? '' : ' hidden'; ?>><?php echo esc_html( $format ); ?></span>
		<span class="product-pdp__sale-sep" data-sale-sep aria-hidden="true"<?php echo $has_format ? '' : ' hidden'; ?>> — </span>
		<span class="product-pdp__sale-price price" data-sale-price><?php echo wp_kses_post( $price_html ); ?></span>
	</p>
	<?php
}

/**
 * Long description (product content).
 */
function borgodasso_template_single_description(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$description = $product->get_description();
	if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
		return;
	}
	?>
	<div class="product-pdp__description">
		<?php echo wp_kses_post( wpautop( do_shortcode( $description ) ) ); ?>
	</div>
	<?php
}

/**
 * Whether this product should show Scheda tecnica (wine / olive oil).
 *
 * @param WC_Product $product Product.
 */
function borgodasso_product_shows_scheda( WC_Product $product ): bool {
	return has_term( array( 'vino', 'olio' ), 'product_cat', $product->get_id() );
}

/**
 * Collect PDP side-panel field data.
 *
 * @param WC_Product $product Product.
 * @return array{
 *   show_scheda: bool,
 *   schede: array<int, array{annata: string, url: string}>,
 *   varieta: string,
 *   ingredienti: string,
 *   valori_title: string,
 *   valori_rows: array<int, array{label: string, value: string, indent: bool}>
 * }
 */
function borgodasso_product_info_panel_data( WC_Product $product ): array {
	$product_id   = $product->get_id();
	$show_scheda  = borgodasso_product_shows_scheda( $product );
	$schede       = array();
	$varieta      = '';
	$ingredienti  = '';
	$valori_title = '';
	$valori_rows  = array();

	if ( ! function_exists( 'get_field' ) ) {
		return array(
			'show_scheda'  => $show_scheda,
			'schede'       => $schede,
			'varieta'      => $varieta,
			'ingredienti'  => $ingredienti,
			'valori_title' => $valori_title,
			'valori_rows'  => $valori_rows,
		);
	}

	if ( $show_scheda ) {
		$raw_schede = get_field( 'schede_tecniche', $product_id );
		if ( is_array( $raw_schede ) ) {
			foreach ( $raw_schede as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$year = isset( $row['annata'] ) ? trim( (string) $row['annata'] ) : '';
				$file = isset( $row['file'] ) ? $row['file'] : null;
				$url  = '';
				if ( is_array( $file ) && ! empty( $file['url'] ) ) {
					$url = (string) $file['url'];
				} elseif ( is_numeric( $file ) ) {
					$url = (string) wp_get_attachment_url( (int) $file );
				} elseif ( is_string( $file ) ) {
					$url = $file;
				}
				if ( '' === $year || '' === $url ) {
					continue;
				}
				$schede[] = array(
					'annata' => $year,
					'url'    => $url,
				);
			}
		}
	}

	$varieta_raw = get_field( 'varieta', $product_id );
	if ( is_string( $varieta_raw ) ) {
		$varieta = trim( $varieta_raw );
	}

	$ingredienti_raw = get_field( 'ingredienti', $product_id );
	if ( is_string( $ingredienti_raw ) ) {
		$ingredienti = $ingredienti_raw;
	}

	$title_raw = get_field( 'valori_nutrizionali_title', $product_id );
	if ( is_string( $title_raw ) && '' !== trim( $title_raw ) ) {
		$valori_title = trim( $title_raw );
	}

	$raw_rows = get_field( 'valori_nutrizionali_rows', $product_id );
	if ( is_array( $raw_rows ) ) {
		foreach ( $raw_rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
			$value = isset( $row['value'] ) ? trim( (string) $row['value'] ) : '';
			if ( '' === $label && '' === $value ) {
				continue;
			}
			$valori_rows[] = array(
				'label'  => $label,
				'value'  => $value,
				'indent' => ! empty( $row['indent'] ),
			);
		}
	}

	return array(
		'show_scheda'  => $show_scheda,
		'schede'       => $schede,
		'varieta'      => $varieta,
		'ingredienti'  => $ingredienti,
		'valori_title' => $valori_title,
		'valori_rows'  => $valori_rows,
	);
}

/**
 * Scheda tecnica / ingredienti / valori nutrizionali rows + side panel.
 */
function borgodasso_template_single_info_rows(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$data            = borgodasso_product_info_panel_data( $product );
	$has_scheda      = $data['show_scheda'] && ! empty( $data['schede'] );
	$has_ingredienti = '' !== trim( wp_strip_all_tags( $data['ingredienti'] ) );
	$has_valori      = ! empty( $data['valori_rows'] );
	$has_panel       = $has_scheda
		|| '' !== $data['varieta']
		|| $has_ingredienti
		|| $has_valori;

	$rows = array();

	if ( $data['show_scheda'] ) {
		$rows[] = array(
			'id'      => 'scheda',
			'label'   => __( 'Scheda tecnica', 'borgodasso' ),
			'enabled' => $has_scheda,
		);
	}

	$rows[] = array(
		'id'      => 'ingredienti',
		'label'   => __( 'Ingredienti', 'borgodasso' ),
		'enabled' => $has_ingredienti,
	);

	$rows[] = array(
		'id'      => 'valori',
		'label'   => __( 'Valori nutrizionali', 'borgodasso' ),
		'enabled' => $has_valori,
	);
	?>
	<div class="product-pdp__info" data-product-info>
		<?php foreach ( $rows as $row ) : ?>
			<div class="product-pdp__info-item<?php echo $row['enabled'] ? '' : ' is-empty'; ?>">
				<?php if ( $row['enabled'] && $has_panel ) : ?>
					<button
						type="button"
						class="product-pdp__info-trigger"
						data-product-info-open
						data-product-info-section="<?php echo esc_attr( $row['id'] ); ?>"
						aria-expanded="false"
						aria-controls="product-info-drawer-panel"
					>
						<span class="product-pdp__info-label"><?php echo esc_html( $row['label'] ); ?></span>
						<?php
						borgodasso_template(
							'objects/svg',
							array(
								'name'  => 'arrow',
								'class' => 'product-pdp__info-icon',
							)
						);
						?>
					</button>
				<?php else : ?>
					<div class="product-pdp__info-trigger is-disabled" aria-disabled="true">
						<span class="product-pdp__info-label"><?php echo esc_html( $row['label'] ); ?></span>
						<?php
						borgodasso_template(
							'objects/svg',
							array(
								'name'  => 'arrow',
								'class' => 'product-pdp__info-icon',
							)
						);
						?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Render the product info side drawer (outside the summary for fixed positioning).
 */
function borgodasso_template_single_info_drawer(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$data            = borgodasso_product_info_panel_data( $product );
	$has_scheda      = $data['show_scheda'] && ! empty( $data['schede'] );
	$has_ingredienti = '' !== trim( wp_strip_all_tags( $data['ingredienti'] ) );
	$has_valori      = ! empty( $data['valori_rows'] );
	$has_panel       = $has_scheda
		|| '' !== $data['varieta']
		|| $has_ingredienti
		|| $has_valori;

	if ( ! $has_panel ) {
		return;
	}

	borgodasso_template(
		'components/product-info-panel',
		array(
			'product_id'   => $product->get_id(),
			'show_scheda'  => $data['show_scheda'],
			'schede'       => $data['schede'],
			'varieta'      => $data['varieta'],
			'ingredienti'  => $data['ingredienti'],
			'valori_title' => $data['valori_title'],
			'valori_rows'  => $data['valori_rows'],
		)
	);
}

/**
 * Format price for the add-to-cart label.
 *
 * @param string|float $price Raw price.
 */
function borgodasso_format_add_button_price( $price ): string {
	if ( '' === $price || null === $price ) {
		return '';
	}

	return html_entity_decode( wp_strip_all_tags( wc_price( (float) $price ) ), ENT_QUOTES, 'UTF-8' );
}

/**
 * Product category slug order for the catalog grid.
 *
 * @return string[]
 */
function borgodasso_product_catalog_category_slugs(): array {
	return array( 'vino', 'pasta', 'legumi', 'olio' );
}

/**
 * Mobile shop filters, in the order shown on /shop.
 *
 * @return array<int, array{slug: string, label: string}>
 */
function borgodasso_shop_nav_items(): array {
	$items = array(
		array(
			'slug'  => 'all',
			'label' => __( 'Tutti', 'borgodasso' ),
		),
	);

	$labels = array(
		'vino'   => __( 'Vini', 'borgodasso' ),
		'olio'   => __( 'Olio', 'borgodasso' ),
		'pasta'  => __( 'Pasta', 'borgodasso' ),
		'legumi' => __( 'Legumi', 'borgodasso' ),
	);

	foreach ( $labels as $slug => $label ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );

		if ( ! $term instanceof WP_Term || (int) $term->count < 1 ) {
			continue;
		}

		$items[] = array(
			'slug'  => $slug,
			'label' => $label,
		);
	}

	return $items;
}

/**
 * Catalog category used by the mobile shop filter.
 */
function borgodasso_product_shop_category_slug( WC_Product $product ): string {
	$terms = get_the_terms( $product->get_id(), 'product_cat' );

	if ( ! is_array( $terms ) ) {
		return '';
	}

	$slugs = array();

	foreach ( $terms as $term ) {
		if ( $term instanceof WP_Term ) {
			$slugs[] = $term->slug;
		}
	}

	foreach ( array( 'vino', 'olio', 'pasta', 'legumi' ) as $slug ) {
		if ( in_array( $slug, $slugs, true ) ) {
			return $slug;
		}
	}

	return '';
}

/**
 * All published products ordered by catalog categories.
 *
 * Products in vino → pasta → legumi → olio come first (in that order).
 * Any other visible products (e.g. uncategorized) are appended after.
 *
 * @return array<int, WC_Product>
 */
function borgodasso_get_catalog_products(): array {
	$ordered  = array();
	$seen_ids = array();

	$append_products = static function ( array $product_ids ) use ( &$ordered, &$seen_ids ): void {
		foreach ( $product_ids as $product_id ) {
			$product_id = (int) $product_id;
			if ( isset( $seen_ids[ $product_id ] ) ) {
				continue;
			}

			$product = wc_get_product( $product_id );
			if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
				continue;
			}

			$seen_ids[ $product_id ] = true;
			$ordered[]               = $product;
		}
	};

	foreach ( borgodasso_product_catalog_category_slugs() as $slug ) {
		$query = new WP_Query(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'slug',
						'terms'    => $slug,
					),
				),
			)
		);

		$append_products( $query->posts );
	}

	$remaining = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
			'post__not_in'           => array_keys( $seen_ids ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		)
	);

	$append_products( $remaining->posts );

	return $ordered;
}

/**
 * Render the “Tutti i prodotti” catalog below the single product.
 */
function borgodasso_template_single_product_catalog(): void {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$products = borgodasso_get_catalog_products();
	if ( array() === $products ) {
		return;
	}

	borgodasso_template(
		'components/product-catalog',
		array(
			'products' => $products,
		)
	);
}

<?php
/**
 * Variable product add to cart
 *
 * Vars below are injected by `woocommerce_variable_add_to_cart()` via `wc_get_template()`.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Borgodasso
 * @version 10.9.0
 *
 * @var array<string, array<int, string>>      $attributes
 * @var array<int, array<string, mixed>>|false $available_variations
 * @var array<string, string>                  $selected_attributes
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product_Variable ) {
	return;
}

// Fallbacks when the template is analyzed outside WC's loader (IDE / direct include).
$attributes           = $attributes ?? $product->get_variation_attributes();
$available_variations = $available_variations ?? $product->get_available_variations();
$selected_attributes  = $selected_attributes ?? $product->get_default_attributes();

$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
$formato_static  = borgodasso_product_formato_display( $product );

/**
 * Attribute values WooCommerce will show (priced, published variations).
 *
 * Variations with an empty price are omitted from `$available_variations`, but
 * `get_variation_attributes()` still lists every term. Null when variation JSON
 * is not inlined (above the ajax threshold) — the select sync in product-pdp.ts
 * hides those options once WooCommerce loads them.
 *
 * @param array<int, array<string, mixed>>|false $available_variations Variations.
 * @return array<string, array<string, true>>|null
 */
$borgodasso_visible_attribute_values = static function ( $available_variations ): ?array {
	if ( ! is_array( $available_variations ) ) {
		return null;
	}

	$visible = array();

	foreach ( $available_variations as $variation ) {
		if ( ! is_array( $variation ) ) {
			continue;
		}

		$variation_attributes = $variation['attributes'] ?? null;
		if ( ! is_array( $variation_attributes ) ) {
			continue;
		}

		foreach ( $variation_attributes as $attribute_key => $attribute_value ) {
			$attribute_key   = (string) $attribute_key;
			$attribute_value = html_entity_decode( (string) $attribute_value, ENT_QUOTES, 'UTF-8' );

			if ( ! isset( $visible[ $attribute_key ] ) ) {
				$visible[ $attribute_key ] = array();
			}

			// An empty value matches any option of this attribute.
			if ( '' === $attribute_value ) {
				$visible[ $attribute_key ]['*'] = true;
				continue;
			}

			$visible[ $attribute_key ][ $attribute_value ] = true;
		}
	}

	return $visible;
};

$visible_attribute_values = $borgodasso_visible_attribute_values( $available_variations );

/**
 * Resolve a variation option's display label.
 *
 * @param string     $option         Option slug/value.
 * @param string     $attribute_name Attribute taxonomy or name.
 * @param WC_Product $product        Product.
 */
$borgodasso_option_label = static function ( string $option, string $attribute_name, WC_Product $product ): string {
	if ( taxonomy_exists( $attribute_name ) ) {
		$term = get_term_by( 'slug', $option, $attribute_name );
		if ( $term && ! is_wp_error( $term ) ) {
			return (string) apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute_name, $product );
		}
	}

	return (string) apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute_name, $product );
};

do_action( 'woocommerce_before_add_to_cart_form' );
?>

<form class="variations_form cart product-pdp__form" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data" data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'borgodasso' ) ) ); ?></p>
	<?php else : ?>
		<div class="product-pdp__options variations" role="group" aria-label="<?php esc_attr_e( 'Opzioni prodotto', 'borgodasso' ); ?>">
			<?php foreach ( $attributes as $attribute_name => $options ) : ?>
				<?php
				$attr_id    = sanitize_title( $attribute_name );
				$attr_label = wc_attribute_label( $attribute_name );
				// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- WC variation form request args.
				$selected = isset( $_REQUEST[ 'attribute_' . $attr_id ] )
					? wc_clean( wp_unslash( $_REQUEST[ 'attribute_' . $attr_id ] ) )
					: $product->get_variation_default_attribute( $attribute_name );
				// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				if ( ! is_array( $options ) ) {
					$options = array();
				}

				$attribute_key = 'attribute_' . $attr_id;
				if (
					is_array( $visible_attribute_values )
					&& isset( $visible_attribute_values[ $attribute_key ] )
					&& empty( $visible_attribute_values[ $attribute_key ]['*'] )
				) {
					$allowed = $visible_attribute_values[ $attribute_key ];
					$options = array_values(
						array_filter(
							$options,
							static function ( $option ) use ( $allowed ): bool {
								return isset( $allowed[ (string) $option ] );
							}
						)
					);
				}

				// No priced variation uses this attribute — don't render an empty control.
				if ( array() === $options ) {
					continue;
				}

				// Still show Annata/Formato when only one value exists.
				// When nothing valid is chosen yet, pre-select the first priced option.
				$is_single     = 1 === count( $options );
				$option_values = array_map( 'strval', $options );
				if ( ! in_array( (string) $selected, $option_values, true ) ) {
					$selected = (string) reset( $option_values );
				}

				$selected_label = '' !== (string) $selected
					? $borgodasso_option_label( (string) $selected, $attribute_name, $product )
					: '';
				$list_id        = 'product-option-list-' . $attr_id;
				$option_class   = 'product-pdp__option';
				if ( $is_single ) {
					$option_class .= ' product-pdp__option--static product-pdp__option--single';
				}
				?>
				<div class="<?php echo esc_attr( $option_class ); ?>" data-product-option<?php echo $is_single ? ' data-product-option-single' : ''; ?>>
					<label class="product-pdp__option-label screen-reader-text" for="<?php echo esc_attr( $attr_id ); ?>">
						<?php echo esc_html( $attr_label ); ?>
					</label>

					<?php
					wc_dropdown_variation_attribute_options(
						array(
							'options'          => $options,
							'attribute'        => $attribute_name,
							'product'          => $product,
							'selected'         => $selected,
							'class'            => 'product-pdp__option-select',
							'show_option_none' => false,
						)
					);
					?>

					<?php if ( $is_single ) : ?>
						<div class="product-pdp__option-trigger" aria-hidden="true">
							<span class="product-pdp__option-prefix"><?php echo esc_html( strtoupper( $attr_label ) ); ?>:</span>
							<span class="product-pdp__option-value" data-product-option-value><?php echo esc_html( $selected_label ); ?></span>
						</div>
						<span class="screen-reader-text">
							<?php echo esc_html( $attr_label . ': ' . $selected_label ); ?>
						</span>
					<?php else : ?>
						<button
							type="button"
							class="product-pdp__option-trigger"
							data-product-option-toggle
							aria-expanded="false"
							aria-controls="<?php echo esc_attr( $list_id ); ?>"
						>
							<span class="product-pdp__option-prefix"><?php echo esc_html( strtoupper( $attr_label ) ); ?>:</span>
							<span class="product-pdp__option-value" data-product-option-value><?php echo esc_html( $selected_label ); ?></span>
							<span class="product-pdp__option-icon" data-product-option-icon aria-hidden="true">+</span>
						</button>

						<ul class="product-pdp__option-list" id="<?php echo esc_attr( $list_id ); ?>" data-product-option-list hidden>
							<?php foreach ( $options as $option ) : ?>
								<?php
								$option       = (string) $option;
								$option_label = $borgodasso_option_label( $option, $attribute_name, $product );
								$is_selected  = (string) $selected === $option;
								?>
								<li class="product-pdp__option-item">
									<button
										type="button"
										class="product-pdp__option-choice<?php echo $is_selected ? ' is-selected' : ''; ?>"
										data-product-option-choice
										data-value="<?php echo esc_attr( $option ); ?>"
										<?php echo $is_selected ? ' aria-current="true"' : ''; ?>
									>
										<?php echo esc_html( $option_label ); ?>
									</button>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<?php if ( '' !== $formato_static ) : ?>
				<div class="product-pdp__option product-pdp__option--static">
					<div class="product-pdp__option-trigger" aria-hidden="true">
						<span class="product-pdp__option-prefix"><?php echo esc_html( strtoupper( __( 'Formato', 'borgodasso' ) ) ); ?>:</span>
						<span class="product-pdp__option-value"><?php echo esc_html( $formato_static ); ?></span>
					</div>
					<span class="screen-reader-text">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: bottle/pack size */
								__( 'Formato: %s', 'borgodasso' ),
								$formato_static
							)
						);
						?>
					</span>
				</div>
			<?php endif; ?>
		</div>

		<div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>
		<?php do_action( 'woocommerce_after_variations_table' ); ?>

		<div class="single_variation_wrap">
			<?php
			do_action( 'woocommerce_before_single_variation' );
			do_action( 'woocommerce_single_variation' );
			do_action( 'woocommerce_after_single_variation' );
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php
do_action( 'woocommerce_after_add_to_cart_form' );

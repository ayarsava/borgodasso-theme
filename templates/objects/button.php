<?php
/**
 * Button object.
 *
 * @package Borgodasso
 *
 * @var string               $label    Button text.
 * @var string               $url      Link URL (ignored when $tag is button).
 * @var string               $variant  Modifier: primary | ghost | outline | solid (default primary).
 * @var string|null          $class    Extra BEM/block classes.
 * @var bool                 $external Open in new tab.
 * @var string|null          $icon     SVG name in assets/svg/ (optional, e.g. arrow).
 * @var bool                 $line     Show animated underline (default false).
 * @var bool                 $once     Lock after first click (payment / one-shot CTAs).
 * @var string               $tag      Element: a | button (default a).
 * @var array<string,string> $attrs    Extra HTML attributes (e.g. data-*, aria-*).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$label    = $label ?? '';
$url      = $url ?? '#';
$variant  = $variant ?? 'primary';
$class    = $class ?? '';
$external = ! empty( $external );
$icon     = $icon ?? '';
$line     = ! empty( $line );
$once     = ! empty( $once );
$tag      = isset( $tag ) && 'button' === $tag ? 'button' : 'a';
$attrs    = isset( $attrs ) && is_array( $attrs ) ? $attrs : array();

if ( '' === $label ) {
	return;
}

$classes = trim( 'btn btn--' . sanitize_html_class( $variant ) . ' ' . $class );

$attr_html = '';
foreach ( $attrs as $attr_name => $attr_value ) {
	$name = sanitize_key( (string) $attr_name );
	if ( '' === $name ) {
		continue;
	}
	$attr_html .= ' ' . $name . '="' . esc_attr( (string) $attr_value ) . '"';
}
?>
<?php if ( 'button' === $tag ) : ?>
	<button
		type="button"
		class="<?php echo esc_attr( $classes ); ?>"
		<?php echo $attr_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
<?php else : ?>
	<a
		class="<?php echo esc_attr( $classes ); ?>"
		href="<?php echo esc_url( $url ); ?>"
		<?php echo $external ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
		<?php echo $once ? ' data-pay-once' : ''; ?>
		<?php echo $attr_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
<?php endif; ?>
	<span class="btn__row">
		<span class="btn__label"><?php echo esc_html( $label ); ?></span>
		<?php if ( '' !== $icon ) : ?>
			<?php
			borgodasso_template(
				'objects/svg',
				array(
					'name'  => $icon,
					'class' => 'btn__icon',
				)
			);
			?>
		<?php endif; ?>
		<?php if ( $line ) : ?>
			<span class="btn__line" data-btn-line aria-hidden="true"></span>
		<?php endif; ?>
	</span>
<?php if ( 'button' === $tag ) : ?>
	</button>
<?php else : ?>
	</a>
<?php endif; ?>

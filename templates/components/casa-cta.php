<?php
/**
 * Casa call-to-action block.
 *
 * @package Borgodasso
 *
 * @var array{title: string, description: string, link: array{title: string, url: string, target: string}|null} $cta
 * @var string $id Heading id.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cta = isset( $cta ) && is_array( $cta ) ? $cta : array();
$id  = $id ?? 'casa-cta-title';

$title = isset( $cta['title'] ) ? (string) $cta['title'] : '';
if ( '' === $title ) {
	return;
}

$description = isset( $cta['description'] ) ? (string) $cta['description'] : '';
$link        = isset( $cta['link'] ) && is_array( $cta['link'] ) ? $cta['link'] : null;
?>
<div class="casa-cta__content">
	<h2 id="<?php echo esc_attr( $id ); ?>" class="casa-cta__title text-h2">
		<?php echo esc_html( $title ); ?>
	</h2>

	<?php if ( '' !== $description ) : ?>
		<div class="casa-cta__description text-body">
			<?php echo wp_kses_post( $description ); ?>
		</div>
	<?php endif; ?>

	<?php if ( null !== $link ) : ?>
		<div class="casa-cta__actions">
			<?php
			borgodasso_template(
				'objects/button',
				array(
					'label'    => '' !== $link['title'] ? $link['title'] : __( 'Scopri', 'borgodasso' ),
					'url'      => $link['url'],
					'variant'  => '',
					'class'    => 'casa-cta__btn',
					'icon'     => 'arrow',
					'line'     => true,
					'external' => '_blank' === $link['target'],
				)
			);
			?>
		</div>
	<?php endif; ?>
</div>
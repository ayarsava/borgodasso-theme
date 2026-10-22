<?php
/**
 * Casa apartment details block (title, specs, description, links).
 *
 * @package Borgodasso
 *
 * @var array $data       From borgodasso_casa_data().
 * @var string $id        Heading element id.
 * @var bool   $show_floor Whether to show the floor-plan link.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data       = isset( $data ) && is_array( $data ) ? $data : array();
$id         = $id ?? 'casa-title';
$show_floor = ! isset( $show_floor ) || $show_floor;

if ( empty( $data['title'] ) ) {
	return;
}

$prenota = isset( $data['prenota'] ) && is_array( $data['prenota'] ) ? $data['prenota'] : null;
$floor   = isset( $data['floor_file'] ) && is_array( $data['floor_file'] ) ? $data['floor_file'] : null;
$specs   = isset( $data['specs'] ) && is_array( $data['specs'] ) ? $data['specs'] : array();
?>
<div class="casa-details__content">
	<h1 id="<?php echo esc_attr( $id ); ?>" class="casa-details__title text-h1">
		<?php echo esc_html( (string) $data['title'] ); ?>
	</h1>

	<?php if ( array() !== $specs ) : ?>
		<ul class="casa-details__specs">
			<?php foreach ( $specs as $spec ) : ?>
				<li class="casa-details__spec casa-details__spec--<?php echo esc_attr( (string) $spec['key'] ); ?>">
					<span class="casa-details__spec-icon" aria-hidden="true">
						<?php
						borgodasso_template(
							'objects/svg',
							array(
								'name'  => $spec['icon'],
								'class' => 'casa-details__spec-svg',
							)
						);
						?>
					</span>
					<span class="casa-details__spec-text"><?php echo esc_html( $spec['text'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( ! empty( $data['description'] ) ) : ?>
		<div class="casa-details__description text-body">
			<?php echo wp_kses_post( (string) $data['description'] ); ?>
		</div>
	<?php endif; ?>

	<?php if ( null !== $prenota || ( $show_floor && null !== $floor ) ) : ?>
		<div class="casa-details__actions">
			<?php if ( null !== $prenota ) : ?>
				<?php
				borgodasso_template(
					'objects/button',
					array(
						'label'    => '' !== $prenota['title'] ? $prenota['title'] : __( 'Prenota', 'borgodasso' ),
						'url'      => $prenota['url'],
						'variant'  => '',
						'class'    => 'casa-details__prenota',
						'icon'     => 'arrow',
						'line'     => true,
						'external' => '_blank' === $prenota['target'],
					)
				);
				?>
			<?php endif; ?>

			<?php if ( $show_floor && null !== $floor ) : ?>
				<?php
				borgodasso_template(
					'objects/button',
					array(
						'label'    => __( 'Mappa', 'borgodasso' ),
						'url'      => $floor['url'],
						'variant'  => '',
						'class'    => 'casa-details__floor',
						'icon'     => 'arrow',
						'line'     => true,
						'external' => true,
					)
				);
				?>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
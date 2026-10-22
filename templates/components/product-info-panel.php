<?php
/**
 * Product info side panel — scheda / varietà / ingredienti / valori.
 *
 * Expected $args from borgodasso_template():
 * - product_id (int)
 * - show_scheda (bool)
 * - schede (array<int, array{annata: string, url: string}>)
 * - varieta (string)
 * - ingredienti (string) HTML
 * - valori_title (string)
 * - valori_rows (array<int, array{label: string, value: string, indent: bool}>)
 *
 * @package Borgodasso
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$show_scheda  = ! empty( $show_scheda );
$schede       = isset( $schede ) && is_array( $schede ) ? $schede : array();
$varieta      = isset( $varieta ) ? trim( (string) $varieta ) : '';
$ingredienti  = isset( $ingredienti ) ? (string) $ingredienti : '';
$valori_title = isset( $valori_title ) ? trim( (string) $valori_title ) : '';
$valori_rows  = isset( $valori_rows ) && is_array( $valori_rows ) ? $valori_rows : array();

$has_scheda      = $show_scheda && ! empty( $schede );
$has_varieta     = '' !== $varieta;
$has_ingredienti = '' !== trim( wp_strip_all_tags( $ingredienti ) );
$has_valori      = ! empty( $valori_rows );

if ( ! $has_scheda && ! $has_varieta && ! $has_ingredienti && ! $has_valori ) {
	return;
}
?>
<div class="product-info-drawer" data-product-info-drawer hidden>
	<button
		type="button"
		class="product-info-drawer__backdrop"
		data-product-info-close
		tabindex="-1"
		aria-label="<?php esc_attr_e( 'Chiudi pannello informazioni', 'borgodasso' ); ?>"
	></button>

	<aside
		id="product-info-drawer-panel"
		class="product-info-drawer__panel"
		data-product-info-panel
		role="dialog"
		aria-modal="true"
		aria-labelledby="product-info-drawer-title"
		tabindex="-1"
	>
		<div class="product-info-drawer__header">
			<h2 id="product-info-drawer-title" class="product-info-drawer__title screen-reader-text">
				<?php esc_html_e( 'Informazioni prodotto', 'borgodasso' ); ?>
			</h2>
			<button
				type="button"
				class="product-info-drawer__burger"
				data-product-info-close
				aria-label="<?php esc_attr_e( 'Chiudi', 'borgodasso' ); ?>"
			>
				<span class="product-info-drawer__burger" aria-hidden="true">
					<span class="product-info-drawer__burger-line"></span>
					<span class="product-info-drawer__burger-line"></span>
				</span>
			</button>
		</div>

		<div class="product-info-drawer__body">
			<?php if ( $has_scheda ) : ?>
				<section class="product-info-drawer__section" id="product-info-section-scheda" data-product-info-section="scheda">
					<h3 class="product-info-drawer__heading"><?php esc_html_e( 'Scheda tecnica', 'borgodasso' ); ?></h3>
					<ul class="product-info-drawer__scheda-list">
						<?php foreach ( $schede as $scheda ) : ?>
							<?php
							$year = isset( $scheda['annata'] ) ? (string) $scheda['annata'] : '';
							$url  = isset( $scheda['url'] ) ? (string) $scheda['url'] : '';
							if ( '' === $year || '' === $url ) {
								continue;
							}
							?>
							<li class="product-info-drawer__scheda-item">
								<a
									class="product-info-drawer__scheda-link"
									href="<?php echo esc_url( $url ); ?>"
									target="_blank"
									rel="noopener noreferrer"
								>
									<span class="product-info-drawer__scheda-year"><?php echo esc_html( $year ); ?></span>
									<?php
									borgodasso_template(
										'objects/svg',
										array(
											'name'  => 'arrow',
											'class' => 'product-pdp__info-icon',
										)
									);
									?>
									<span class="screen-reader-text">
										<?php
										echo esc_html(
											sprintf(
												/* translators: %s: vintage year */
												__( 'Apri scheda tecnica %s', 'borgodasso' ),
												$year
											)
										);
										?>
									</span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php if ( $has_varieta ) : ?>
				<section class="product-info-drawer__section" id="product-info-section-varieta" data-product-info-section="varieta">
					<h3 class="product-info-drawer__heading"><?php esc_html_e( 'Varietà', 'borgodasso' ); ?></h3>
					<p class="product-info-drawer__text"><?php echo esc_html( $varieta ); ?></p>
				</section>
			<?php endif; ?>

			<?php if ( $has_ingredienti ) : ?>
				<section class="product-info-drawer__section" id="product-info-section-ingredienti" data-product-info-section="ingredienti">
					<h3 class="product-info-drawer__heading"><?php esc_html_e( 'Ingredienti', 'borgodasso' ); ?></h3>
					<div class="product-info-drawer__rich o-content-from-editor">
						<?php echo wp_kses_post( $ingredienti ); ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $has_valori ) : ?>
				<section class="product-info-drawer__section" id="product-info-section-valori" data-product-info-section="valori">
					<h3 class="product-info-drawer__heading">
						<?php
						echo esc_html(
							'' !== $valori_title
								? $valori_title
								: __( 'Valori nutrizionali medi per 100 ml', 'borgodasso' )
						);
						?>
					</h3>
					<table class="product-info-drawer__table">
						<tbody>
							<?php foreach ( $valori_rows as $row ) : ?>
								<?php
								$label  = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
								$value  = isset( $row['value'] ) ? trim( (string) $row['value'] ) : '';
								$indent = ! empty( $row['indent'] );
								if ( '' === $label && '' === $value ) {
									continue;
								}
								?>
								<tr class="product-info-drawer__table-row<?php echo $indent ? ' is-indent' : ''; ?>">
									<th scope="row" class="product-info-drawer__table-label"><?php echo esc_html( $label ); ?></th>
									<td class="product-info-drawer__table-value"><?php echo esc_html( $value ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			<?php endif; ?>
		</div>
	</aside>
</div>

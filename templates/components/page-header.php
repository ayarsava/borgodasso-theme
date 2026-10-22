<?php
/**
 * Page header — title, description, optional image composition.
 *
 * Text only: centered title + description.
 * Full screen (hero): viewport image + overlay + title.
 * Single image: split layout (5/12 content + 7/12 image area).
 * Dual images: text header + two-image composition below.
 *
 * @package Borgodasso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data = borgodasso_page_header_data();

if ( null === $data ) {
	return;
}

$layout          = $data['layout'];
$title           = $data['title'];
$description     = $data['description'];
$image_id        = $data['image_id'];
$image_mobile    = $data['image_mobile'];
$image_primary   = $data['image_primary'];
$image_secondary = $data['image_secondary'];
?>
<?php if ( 'text' === $layout ) : ?>
	<section class="page-header page-header--text" data-page-header aria-labelledby="page-header-title">
		<div class="page-header__content">
			<?php if ( '' !== $title ) : ?>
				<h1 id="page-header-title" class="page-header__title"><?php echo esc_html( $title ); ?></h1>
			<?php endif; ?>
			<?php if ( '' !== $description ) : ?>
				<div class="page-header__description">
					<?php echo wp_kses_post( wpautop( $description ) ); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
<?php elseif ( 'hero' === $layout ) : ?>
	<section
		class="page-header page-header--hero"
		data-page-header
		<?php if ( '' !== $title ) : ?>
			aria-labelledby="page-header-title"
		<?php else : ?>
			aria-label="<?php echo esc_attr( get_the_title() ); ?>"
		<?php endif; ?>
	>
		<div class="page-header__media" aria-hidden="true">
			<?php
			borgodasso_page_header_picture(
				$image_id,
				$image_mobile,
				'' !== $title ? $title : get_the_title(),
				'page-header__img',
				'full'
			);
			?>
		</div>
		<?php if ( '' !== $title ) : ?>
			<div class="page-header__content">
				<div class="page-header__content-inner">
					<h1 id="page-header-title" class="page-header__title"><?php echo esc_html( $title ); ?></h1>
					<div class="page-header__scroll" aria-hidden="true">
						<?php
						borgodasso_template(
							'objects/svg',
							array(
								'name'  => 'arrow-large',
								'class' => 'page-header__scroll-icon',
							)
						);
						?>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</section>
<?php elseif ( 'dual' === $layout ) : ?>
	<div class="page-header-group page-header-group--dual" data-page-header>
		<section class="page-header page-header--dual" aria-labelledby="page-header-title">
			<div class="page-header__content">
				<h1 id="page-header-title" class="page-header__title"><?php echo esc_html( $title ); ?></h1>
				<?php if ( '' !== $description ) : ?>
					<div class="page-header__description">
						<?php echo wp_kses_post( wpautop( $description ) ); ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="page-header__composition-inner">
				<figure class="page-header__figure page-header__figure--primary">
					<?php
					borgodasso_page_header_picture(
						$image_primary,
						$image_mobile,
						$title
					);
					?>
				</figure>
				<figure class="page-header__figure page-header__figure--secondary">
					<?php
					borgodasso_page_header_picture(
						$image_secondary,
						0,
						$title
					);
					?>
				</figure>
			</div>
		</section>
	</div>
<?php else : ?>
	<section class="page-header page-header--single" data-page-header aria-labelledby="page-header-title">
		<div class="page-header__content">
			<h1 id="page-header-title" class="page-header__title"><?php echo esc_html( $title ); ?></h1>
			<?php if ( '' !== $description ) : ?>
				<div class="page-header__description">
					<?php echo wp_kses_post( wpautop( $description ) ); ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="page-header__media">
			<?php
			borgodasso_page_header_picture(
				$image_id,
				$image_mobile,
				$title
			);
			?>
		</div>
	</section>
<?php endif; ?>

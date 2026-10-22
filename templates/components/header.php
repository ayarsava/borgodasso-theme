<?php
/**
 * Site header — menu, logo, shop / login / cart / prenota.
 *
 * @package Borgodasso
 *
 * Light variant (home / full-screen hero / Contact): cream text on imagery.
 * Dark variant (inner pages): black text; page headers / product may lighten zones (see SCSS).
 * Product overlay: cream logo + burger over the image; black actions over the summary. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$variant             = borgodasso_header_variant();
$page_header_layout  = borgodasso_page_header_layout();
$is_product          = function_exists( 'is_product' ) && is_product();
$is_overlay          = is_front_page()
	|| borgodasso_is_contact_template()
	|| ( function_exists( 'borgodasso_is_age_gate_template' ) && borgodasso_is_age_gate_template() )
	|| $is_product
	|| ( null !== $page_header_layout && 'text' !== $page_header_layout );
$header_menu         = borgodasso_header_options();
$menu_items          = $header_menu['items'];
$desktop_actions     = $header_menu['desktop_actions'];
$mobile_icons        = $header_menu['mobile_icons'];
$mobile_secondary    = $header_menu['mobile_secondary'];
$cantina             = $header_menu['cantina'];
$languages           = borgodasso_header_languages();
$has_menu            = array() !== $menu_items;
$has_desktop_actions = array() !== $desktop_actions;
$has_mobile_icons    = array() !== $mobile_icons;
$active_slide        = 0;

$header_classes = array(
	'site-header',
	'site-header--' . $variant,
);

if ( $is_overlay ) {
	$header_classes[] = 'site-header--overlay';
}

if ( null !== $page_header_layout ) {
	$header_classes[] = 'site-header--' . $page_header_layout;
}

if ( $is_product ) {
	$header_classes[] = 'site-header--product';
}

foreach ( $menu_items as $index => $item ) {
	if ( $item['is_active'] ) {
		$active_slide = $index;
		break;
	}
}
?>
<header
	class="<?php echo esc_attr( implode( ' ', $header_classes ) ); ?>"
	data-site-header
>
	<div class="site-header__inner">
		<div class="site-header__start">
			<?php if ( $has_menu ) : ?>
				<button
					type="button"
					class="site-header__menu-toggle"
					aria-expanded="false"
					aria-controls="site-nav-drawer"
					data-menu-toggle
				>
					<span class="site-header__burger" aria-hidden="true">
						<span class="site-header__burger-line"></span>
						<span class="site-header__burger-line"></span>
					</span>
					<span class="site-header__menu-label"><?php esc_html_e( 'Menu', 'borgodasso' ); ?></span>
				</button>
			<?php endif; ?>
		</div>

		<div class="site-header__brand">
			<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<?php
				borgodasso_template(
					'objects/svg',
					array(
						'name'  => 'logo',
						'class' => 'site-header__logo-svg',
					)
				);
				?>
			</a>
		</div>

		<div class="site-header__end">
			<?php if ( $has_desktop_actions ) : ?>
				<nav class="site-header__actions site-header__actions--desktop" aria-label="<?php esc_attr_e( 'Shop and account', 'borgodasso' ); ?>">
					<ul class="site-header__actions-list">
						<?php foreach ( $desktop_actions as $action ) : ?>
							<?php
							$is_button  = 'button' === $action['style'];
							$is_cart    = 'cart' === $action['style'];
							$item_class = 'site-header__actions-item';
							$link_class = 'site-header__action';
							if ( $is_button ) {
								$item_class .= ' site-header__actions-item--button';
								$link_class .= ' site-header__book btn btn--outline';
							}
							if ( $is_cart ) {
								$link_class .= ' site-header__action--cart';
							}
							?>
							<li class="<?php echo esc_attr( $item_class ); ?>">
								<a
									class="<?php echo esc_attr( $link_class ); ?>"
									href="<?php echo esc_url( $action['url'] ); ?>"
									<?php echo $is_cart ? ' data-cart-open data-cart-fly-target' : ''; ?>
									<?php echo '' !== $action['target'] ? ' target="' . esc_attr( $action['target'] ) . '"' : ''; ?>
									<?php echo '_blank' === $action['target'] ? ' rel="noopener noreferrer"' : ''; ?>
								>
								<?php
								if ( $is_cart ) :
									?>
									<span data-cart-count-label><?php echo esc_html( $action['label'] ); ?></span>
									<?php
else :
	?>
									<?php echo esc_html( $action['label'] ); ?><?php endif; ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>

			<?php if ( $has_mobile_icons ) : ?>
				<nav class="site-header__mobile-icons" aria-label="<?php esc_attr_e( 'Booking, account and shop', 'borgodasso' ); ?>">
					<?php foreach ( $mobile_icons as $icon_item ) : ?>
						<?php if ( 'shop' === $icon_item['icon'] && function_exists( 'borgodasso_render_header_shop_icon' ) ) : ?>
							<?php borgodasso_render_header_shop_icon( $icon_item ); ?>
						<?php else : ?>
							<a
								class="site-header__mobile-icon site-header__mobile-icon--<?php echo esc_attr( $icon_item['icon'] ); ?>"
								href="<?php echo esc_url( $icon_item['url'] ); ?>"
								aria-label="<?php echo esc_attr( $icon_item['label'] ); ?>"
								<?php echo '' !== $icon_item['target'] ? ' target="' . esc_attr( $icon_item['target'] ) . '"' : ''; ?>
								<?php echo '_blank' === $icon_item['target'] ? ' rel="noopener noreferrer"' : ''; ?>
							>
								<?php
								borgodasso_template(
									'objects/svg',
									array(
										'name'  => $icon_item['icon'],
										'class' => 'site-header__mobile-icon-svg',
									)
								);
								?>
							</a>
						<?php endif; ?>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		</div>
	</div>
</header>

<?php if ( $has_menu ) : ?>
	<nav
		id="site-nav-drawer"
		class="site-nav site-nav--drawer"
		aria-label="<?php esc_attr_e( 'Primary', 'borgodasso' ); ?>"
		data-nav-drawer
		hidden
	>
		<div class="site-nav__panel" data-nav-panel>
			<div class="site-nav__layout">
				<div class="site-nav__main">
					<ul class="site-nav__list">
						<?php foreach ( $menu_items as $index => $item ) : ?>
							<li class="site-nav__item">
								<a
									class="site-nav__link<?php echo $item['is_active'] ? ' is-active' : ''; ?>"
									href="<?php echo esc_url( $item['url'] ); ?>"
									<?php echo '' !== $item['target'] ? ' target="' . esc_attr( $item['target'] ) . '"' : ''; ?>
									<?php echo '_blank' === $item['target'] ? ' rel="noopener noreferrer"' : ''; ?>
									data-menu-index="<?php echo esc_attr( (string) $index ); ?>"
								>
									<span class="site-nav__link-label">
										<span class="site-nav__link-regular"><?php echo esc_html( $item['label'] ); ?></span>
										<span class="site-nav__link-italic" aria-hidden="true"><?php echo esc_html( $item['label'] ); ?></span>
									</span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>

					<?php if ( array() !== $languages ) : ?>
						<div class="site-nav__lang site-nav__lang--desktop">
							<?php foreach ( $languages as $lang_index => $language ) : ?>
								<?php if ( $lang_index > 0 ) : ?>
									<span class="site-nav__lang-sep" aria-hidden="true">—</span>
								<?php endif; ?>
								<a
									class="site-nav__lang-link<?php echo $language['current'] ? ' is-current' : ''; ?>"
									href="<?php echo esc_url( $language['url'] ); ?>"
									lang="<?php echo esc_attr( strtolower( $language['code'] ) ); ?>"
									<?php echo $language['current'] ? ' aria-current="true"' : ''; ?>
								>
									<?php echo esc_html( $language['label'] ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="site-nav__media" aria-hidden="true">
					<?php foreach ( $menu_items as $index => $item ) : ?>
						<?php if ( $item['image_id'] <= 0 ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<div
							class="site-nav__media-slide<?php echo (int) $index === $active_slide ? ' is-active' : ''; ?>"
							data-menu-index="<?php echo esc_attr( (string) $index ); ?>"
						>
							<?php
							echo wp_get_attachment_image(
								$item['image_id'],
								'borgodasso_menu_desktop',
								false,
								array(
									'class'    => 'site-nav__media-img',
									'loading'  => 'lazy',
									'decoding' => 'async',
									'alt'      => borgodasso_attachment_alt( $item['image_id'], $item['label'] ),
									'sizes'    => '(min-width: 1024px) 58vw, 0px',
								)
							);
							?>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="site-nav__secondary">
					<ul class="site-nav__secondary-list">
						<?php foreach ( $mobile_secondary as $secondary_item ) : ?>
							<li class="site-nav__secondary-item">
								<a
									class="site-nav__secondary-link<?php echo $secondary_item['is_external'] ? ' site-nav__secondary-link--external' : ''; ?>"
									href="<?php echo esc_url( $secondary_item['url'] ); ?>"
									<?php echo '' !== $secondary_item['target'] ? ' target="' . esc_attr( $secondary_item['target'] ) . '"' : ''; ?>
									<?php echo '_blank' === $secondary_item['target'] ? ' rel="noopener noreferrer"' : ''; ?>
								>
									<span class="site-nav__secondary-label"><?php echo esc_html( strtoupper( $secondary_item['label'] ) ); ?></span>
									<?php if ( $secondary_item['is_external'] ) : ?>
										<?php
										borgodasso_template(
											'objects/svg',
											array(
												'name'  => 'arrow',
												'class' => 'site-nav__secondary-icon',
											)
										);
										?>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
						<?php if ( is_array( $cantina ) && ! empty( $cantina['url'] ) ) : ?>
							<li class="site-nav__secondary-item">
								<a
									class="site-nav__secondary-link site-nav__secondary-link--external"
									href="<?php echo esc_url( $cantina['url'] ); ?>"
									<?php echo ! empty( $cantina['target'] ) ? ' target="' . esc_attr( $cantina['target'] ) . '"' : ' target="_blank"'; ?>
									rel="noopener noreferrer"
								>
									<span class="site-nav__secondary-label">
										<?php
										echo esc_html(
											'' !== $cantina['title']
												? strtoupper( $cantina['title'] )
												: __( "Cantina Ser'Enzo", 'borgodasso' )
										);
										?>
									</span>
									<?php
									borgodasso_template(
										'objects/svg',
										array(
											'name'  => 'arrow',
											'class' => 'site-nav__secondary-icon',
										)
									);
									?>
								</a>
							</li>
						<?php endif; ?>
					</ul>

					<?php if ( array() !== $languages ) : ?>
						<div class="site-nav__lang site-nav__lang--mobile">
							<?php foreach ( $languages as $lang_index => $language ) : ?>
								<?php if ( $lang_index > 0 ) : ?>
									<span class="site-nav__lang-sep" aria-hidden="true">—</span>
								<?php endif; ?>
								<a
									class="site-nav__lang-link<?php echo $language['current'] ? ' is-current' : ''; ?>"
									href="<?php echo esc_url( $language['url'] ); ?>"
									lang="<?php echo esc_attr( strtolower( $language['code'] ) ); ?>"
									<?php echo $language['current'] ? ' aria-current="true"' : ''; ?>
								>
									<?php echo esc_html( $language['label'] ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</nav>
<?php endif; ?>

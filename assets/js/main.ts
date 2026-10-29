/**
 * Theme entry. Page modules load on demand (keeps GSAP off shop/checkout).
 */
import '../scss/main.scss';
import { initBlurUp } from './modules/blur-up';
import { initReveal, prepareReveal } from './modules/reveal';

document.documentElement.classList.add('js');
initBlurUp(document.body);
prepareReveal();

if (document.querySelector('[data-shop-nav]')) {
  void import('./modules/shop-nav').then((mod) => mod.initShopNav());
}

if (document.querySelector('[data-menu-toggle]')) {
  void import('./modules/nav').then((mod) => mod.initNav());
}

if (document.querySelector('[data-home]')) {
  void import('./modules/home').then((mod) => mod.initHome());
}

if (document.querySelector('[data-site-header]')) {
  void import('./modules/site-header-scroll').then((mod) => mod.initSiteHeaderScroll());
}

const pageHeaderReady =
  document.querySelector('[data-page-header]') || document.querySelector('[data-site-header]')
    ? import('./modules/page-header').then((mod) => mod.initPageHeader())
    : Promise.resolve();

const shouldInitReveal =
  document.querySelector('.page-builder') ||
  document.querySelector('[data-casa]') ||
  document.querySelector('.shop-catalog');

if (shouldInitReveal) {
  void pageHeaderReady.then(() => {
    initReveal();
  });
}

if (document.querySelector('.story-mosaic')) {
  void import('./modules/story-mosaic').then((mod) => mod.initStoryMosaic());
}

if (document.querySelector('[data-btn-line]')) {
  void import('./modules/btn-line').then((mod) => mod.initBtnLines());
}

if (document.querySelector('[data-media-gallery]')) {
  void import('./modules/media-gallery').then((mod) => mod.initMediaGallery());
}

if (document.querySelector('[data-casa]')) {
  void import('./modules/casa-gallery').then((mod) => mod.initCasaGallery());
}

if (document.querySelector('[data-product-pdp]')) {
  void import('./modules/product-pdp').then((mod) => mod.initProductPdp());
}

if (document.querySelector('[data-account-password-toggle], .woocommerce-EditAccountForm')) {
  void import('./modules/account-forms').then((mod) => mod.initAccountForms());
}

if (document.querySelector('[data-account-order]')) {
  void import('./modules/account-orders').then((mod) => mod.initAccountOrders());
}

if (document.querySelector('[data-cart-drawer], [data-cart-open]')) {
  void import('./modules/cart-drawer').then((mod) => mod.initCartDrawer());
}

if (document.querySelector('.woocommerce-message, .woocommerce-error, .product-pdp__notice')) {
  void import('./modules/wc-notices').then((mod) => mod.initWcNotices());
}

if (document.querySelector('[data-billing-same], .checkout-layout')) {
  void import('./modules/checkout').then((mod) => mod.initCheckout());
}

if (document.querySelector('[data-pay-once]')) {
  void import('./modules/pay-once').then((mod) => mod.initPayOnce());
}

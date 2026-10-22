/**
 * WooCommerce notices — toast only success (+ styled errors).
 * Do NOT toast `.woocommerce-info` (e.g. empty orders / email-confirm copy).
 */

const HIDE_CLASS = 'is-hiding';
const TOAST_CLASS = 'wc-notice-toast';
const SHOW_MS = 5600;
const FADE_MS = 350;

/** Success + errors become toasts. Info stays in-page. */
const TOAST_NOTICES = '.woocommerce-message, .woocommerce-error';
const AUTO_DISMISS = '.woocommerce-message';

function dismiss(el: HTMLElement): void {
  el.classList.add(HIDE_CLASS);

  window.setTimeout(() => {
    el.remove();
  }, FADE_MS);
}

function isOrderPayInlineError(el: HTMLElement): boolean {
  return (
    document.body.classList.contains('woocommerce-order-pay') &&
    el.classList.contains('woocommerce-error') &&
    !el.closest('.woocommerce-notices-wrapper')
  );
}

export function initWcNotices(): void {
  document.querySelectorAll<HTMLElement>(TOAST_NOTICES).forEach((el) => {
    // Guest-order warning on pay-for-order stays in-page (not a toast).
    if (isOrderPayInlineError(el)) {
      el.classList.add('checkout-pay-notice');
      return;
    }

    el.classList.add(TOAST_CLASS);
  });

  document.querySelectorAll<HTMLElement>(AUTO_DISMISS).forEach((el) => {
    window.setTimeout(() => dismiss(el), SHOW_MS);
  });
}

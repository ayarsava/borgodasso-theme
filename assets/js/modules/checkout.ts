/**
 * Checkout — billing same/different toggle.
 */

function syncBillingVisibility(root: HTMLElement): void {
  const selected = root.querySelector<HTMLInputElement>(
    'input[name="borgodasso_billing_same"]:checked',
  );
  const fields = root.querySelector<HTMLElement>('[data-billing-fields]');
  if (!fields) {
    return;
  }

  const different = selected?.value === 'different';
  fields.hidden = !different;
  fields.setAttribute('aria-hidden', different ? 'false' : 'true');
  root.classList.toggle('is-billing-different', different);

  fields
    .querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(
      'input, select, textarea',
    )
    .forEach((el) => {
      el.removeAttribute('required');
    });
}

export function initCheckout(): void {
  const root = document.querySelector<HTMLElement>('[data-billing-same]');
  if (!root) {
    return;
  }

  syncBillingVisibility(root);

  root
    .querySelectorAll<HTMLInputElement>('input[name="borgodasso_billing_same"]')
    .forEach((radio) => {
      radio.addEventListener('change', () => {
        syncBillingVisibility(root);
        document.body.dispatchEvent(new Event('update_checkout'));
      });
    });
}

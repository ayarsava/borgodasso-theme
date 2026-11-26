/**
 * Floating labels — keep `is-filled` in sync for autofill / selects / Select2.
 * Resting vs floated chrome is CSS (:focus-within + :placeholder-shown).
 *
 * FIELD_SELECTOR must stay in sync with the register in
 * `assets/scss/objects/_floating-field.scss`.
 */

/** Surfaces that use floating-field chrome (see SCSS register). */
const FIELD_SELECTOR = [
  '.account-field',
  '.checkout-form .form-row',
  '.woocommerce-account .woocommerce-MyAccount-content .form-row',
  '.woocommerce-Address-fields .form-row',
  '.woocommerce-address-fields .form-row',
].join(', ');

const CONTROL_SELECTOR = 'input.input-text, input.account-field__input, textarea.input-text, select';

const SKIP_FIELD_CLASSES = [
  'checkout-billing-choice__option',
  'checkout-remember',
  'woocommerce-SavedPaymentMethods-saveNew',
] as const;

function isFilledControl(el: HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement): boolean {
  if (el instanceof HTMLSelectElement) {
    const value = el.value.trim();
    if (value === '') {
      return false;
    }
    // Woo empty country/state placeholders often use value "".
    const option = el.options[el.selectedIndex];
    if (option && option.disabled) {
      return false;
    }
    return true;
  }

  return el.value.trim() !== '';
}

function syncField(field: HTMLElement): void {
  const control = field.querySelector<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>(
    CONTROL_SELECTOR,
  );
  if (!control || control.type === 'hidden' || control.type === 'checkbox' || control.type === 'radio') {
    return;
  }

  // Enable :placeholder-shown without showing native placeholder text.
  if (
    (control instanceof HTMLInputElement || control instanceof HTMLTextAreaElement) &&
    !control.placeholder
  ) {
    control.placeholder = ' ';
  }

  // Use the real <select> value — Select2 placeholder text must not count as filled.
  const filled = isFilledControl(control) || control.matches(':-webkit-autofill');

  field.classList.toggle('is-filled', filled);
}

function bindField(field: HTMLElement): void {
  syncField(field);

  field.addEventListener('focusin', () => syncField(field));
  field.addEventListener('focusout', () => syncField(field));
  field.addEventListener('input', () => syncField(field));
  field.addEventListener('change', () => syncField(field));
}

function shouldBindField(field: HTMLElement): boolean {
  if (SKIP_FIELD_CLASSES.some((name) => field.classList.contains(name))) {
    return false;
  }

  return !field.querySelector('input[type="checkbox"], input[type="radio"]');
}

function collectFields(root: ParentNode = document): HTMLElement[] {
  return Array.from(root.querySelectorAll<HTMLElement>(FIELD_SELECTOR)).filter(shouldBindField);
}

export function initFloatingFields(): void {
  const fields = collectFields();

  if (fields.length === 0) {
    return;
  }

  fields.forEach(bindField);

  // Autofill can land after paint without input events.
  [50, 200, 600, 1200].forEach((ms) => {
    window.setTimeout(() => {
      fields.forEach(syncField);
    }, ms);
  });

  // Select2 updates after Woo checkout refresh.
  document.body.addEventListener('updated_checkout', () => {
    window.requestAnimationFrame(() => {
      collectFields().forEach((field) => {
        if (!field.dataset.floatingBound) {
          field.dataset.floatingBound = '1';
          bindField(field);
        } else {
          syncField(field);
        }
      });
    });
  });

  fields.forEach((field) => {
    field.dataset.floatingBound = '1';
  });
}

/**
 * Account forms — password visibility, min-length blur, confirm sync, cancel/back.
 */

const PASSWORD_MIN_LENGTH = 10;

function initPasswordToggles(): void {
  document
    .querySelectorAll<HTMLButtonElement>('[data-account-password-toggle]')
    .forEach((button) => {
      button.addEventListener('click', () => {
        const field = button.closest('.account-field');
        const input = field?.querySelector<HTMLInputElement>(
          '[data-account-password], input[type="password"], input[type="text"]',
        );

        if (!input) {
          return;
        }

        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute(
          'aria-label',
          show
            ? button.dataset.labelHide || 'Nascondi password'
            : button.dataset.labelShow || 'Mostra password',
        );
        button.classList.toggle('is-active', show);
      });
    });
}

function initAccountCancel(): void {
  document.querySelectorAll<HTMLButtonElement>('[data-account-cancel]').forEach((button) => {
    button.addEventListener('click', () => {
      if (window.history.length > 1) {
        window.history.back();
        return;
      }

      const fallback = button.dataset.fallbackUrl;
      if (fallback) {
        window.location.assign(fallback);
      }
    });
  });
}

function setPasswordErrorVisible(field: Element, visible: boolean): void {
  const error = field.querySelector<HTMLElement>('[data-account-password-error]');
  if (!error) {
    return;
  }

  error.hidden = !visible;
  field.classList.toggle('has-error', visible);
}

function passwordNeedsMinLengthError(input: HTMLInputElement): boolean {
  const length = input.value.length;
  const optional = input.hasAttribute('data-account-password-optional');

  // Optional change-password field: empty is fine; short non-empty is not.
  if (optional && length === 0) {
    return false;
  }

  return length < PASSWORD_MIN_LENGTH;
}

function initPasswordMinLength(): void {
  document.querySelectorAll<HTMLInputElement>('[data-account-password-min]').forEach((input) => {
    const field = input.closest('.account-field');
    if (!field) {
      return;
    }

    input.addEventListener('blur', () => {
      setPasswordErrorVisible(field, passwordNeedsMinLengthError(input));
    });

    input.addEventListener('input', () => {
      if (!passwordNeedsMinLengthError(input)) {
        setPasswordErrorVisible(field, false);
      }
    });
  });
}

function syncPasswordConfirm(form: HTMLFormElement): void {
  const password1 = form.querySelector<HTMLInputElement>('#password_1');
  const password2 = form.querySelector<HTMLInputElement>('#password_2');

  if (!password1 || !password2) {
    return;
  }

  const syncConfirm = (): void => {
    password2.value = password1.value;
  };

  password1.addEventListener('input', syncConfirm);
  form.addEventListener('submit', syncConfirm);
}

export function initAccountForms(): void {
  initPasswordToggles();
  initAccountCancel();
  initPasswordMinLength();

  document
    .querySelectorAll<HTMLFormElement>(
      '.woocommerce-EditAccountForm, form.lost_reset_password',
    )
    .forEach(syncPasswordConfirm);
}

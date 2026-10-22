/**
 * Account forms — password visibility toggle + confirm sync.
 */

export function initAccountForms(): void {
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

  const form = document.querySelector<HTMLFormElement>('.woocommerce-EditAccountForm');
  const password1 = form?.querySelector<HTMLInputElement>('#password_1');
  const password2 = form?.querySelector<HTMLInputElement>('#password_2');

  if (form && password1 && password2) {
    const syncConfirm = (): void => {
      password2.value = password1.value;
    };

    password1.addEventListener('input', syncConfirm);
    form.addEventListener('submit', syncConfirm);
  }
}

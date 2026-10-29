const mobileQuery = window.matchMedia('(max-width: 767px)');

function setPressed(button: HTMLButtonElement, pressed: boolean): void {
  button.classList.toggle('is-active', pressed);
  button.setAttribute('aria-pressed', pressed ? 'true' : 'false');
}

export function initShopNav(): void {
  const nav = document.querySelector<HTMLElement>('[data-shop-nav]');
  if (!nav) {
    return;
  }

  const buttons = Array.from(nav.querySelectorAll<HTMLButtonElement>('[data-shop-filter]'));
  const items = Array.from(document.querySelectorAll<HTMLElement>('.shop-catalog [data-shop-category]'));

  const apply = (slug: string): void => {
    buttons.forEach((button) => {
      setPressed(button, button.dataset.shopFilter === slug);
    });

    items.forEach((item) => {
      const visible = slug === 'all' || item.dataset.shopCategory === slug;
      item.hidden = !visible;
    });
  };

  nav.addEventListener('click', (event) => {
    const button = (event.target as HTMLElement | null)?.closest<HTMLButtonElement>('[data-shop-filter]');
    if (!button || !mobileQuery.matches) {
      return;
    }

    apply(button.dataset.shopFilter || 'all');
  });

  const showAllOnDesktop = (): void => {
    if (!mobileQuery.matches) {
      apply('all');
    }
  };

  mobileQuery.addEventListener('change', showAllOnDesktop);
}

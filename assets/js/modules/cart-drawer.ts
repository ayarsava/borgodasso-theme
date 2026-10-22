type CartConfig = {
  nonce: string;
  updateUrl: string;
  removeUrl: string;
  addToCartUrl?: string;
  fragmentsUrl?: string;
  shopUrl: string;
  checkoutUrl: string;
  cartHash?: string;
};

type CartResponse = {
  success?: boolean;
  data?: {
    count?: number;
    label?: string;
    cart_hash?: string;
    fragments?: Record<string, string>;
  };
};

type WcAddToCartResponse = {
  error?: boolean;
  product_url?: string;
  fragments?: Record<string, string>;
  cart_hash?: string;
};

type FragmentsPayload = {
  fragments?: Record<string, string>;
  cart_hash?: string;
};

const OPEN_CLASS = 'is-open';
const CLOSING_CLASS = 'is-closing';
const STATIC_CLASS = 'is-static';
const BUSY_CLASS = 'is-busy';
const BODY_OPEN_CLASS = 'is-cart-drawer-open';
const FLYING_CLASS = 'is-cart-flying';
const RECEIVED_CLASS = 'is-cart-received';
const PANEL_CLOSE_MS = 480;
const FLY_MS = 700;
const RECEIVE_MS = 520;
const CART_HASH_STORAGE_KEY = 'borgodasso_cart_hash';

function getConfig(): CartConfig | null {
  const cfg = (window as Window & { borgodassoCart?: CartConfig }).borgodassoCart;
  return cfg ?? null;
}

function getCookie(name: string): string {
  const encoded = encodeURIComponent(name);
  const parts = document.cookie.split(';');
  for (const part of parts) {
    const trimmed = part.trim();
    if (trimmed.startsWith(`${encoded}=`)) {
      return decodeURIComponent(trimmed.slice(encoded.length + 1));
    }
    if (trimmed.startsWith(`${name}=`)) {
      return decodeURIComponent(trimmed.slice(name.length + 1));
    }
  }
  return '';
}

function getCookieCartHash(): string {
  return getCookie('woocommerce_cart_hash');
}

function rememberCartHash(hash?: string): void {
  const value = typeof hash === 'string' ? hash : getCookieCartHash();
  try {
    localStorage.setItem(CART_HASH_STORAGE_KEY, value);
  } catch {
    // Private mode / blocked storage — cookie compare on focus still works.
  }
}

async function fetchCartFragments(): Promise<FragmentsPayload | null> {
  const config = getConfig();
  if (!config?.fragmentsUrl) {
    return null;
  }

  const body = new FormData();
  body.append('time', String(Date.now()));

  const response = await fetch(config.fragmentsUrl, {
    method: 'POST',
    credentials: 'same-origin',
    body,
  });

  if (!response.ok) {
    return null;
  }

  return (await response.json()) as FragmentsPayload;
}

function getScrollbarWidth(): number {
  return window.innerWidth - document.documentElement.clientWidth;
}

function lockScroll(): void {
  const scrollbarWidth = getScrollbarWidth();
  if (scrollbarWidth > 0) {
    document.documentElement.style.setProperty('--scrollbar-width', `${scrollbarWidth}px`);
  }
  document.body.classList.add(BODY_OPEN_CLASS);
}

function unlockScroll(): void {
  document.body.classList.remove(BODY_OPEN_CLASS);
  document.documentElement.style.removeProperty('--scrollbar-width');
}

function parseFragmentElement(html: string): Element | null {
  const temp = document.createElement('div');
  temp.innerHTML = html.trim();
  return temp.firstElementChild;
}

/**
 * Refresh an already-open drawer without replacing the panel node.
 * Replacing `<aside data-cart-panel>` resets transform and causes a slide/flash.
 */
function patchOpenDrawer(current: HTMLElement, next: HTMLElement): void {
  current.classList.toggle('cart-drawer--empty', next.classList.contains('cart-drawer--empty'));
  current.classList.add(OPEN_CLASS, STATIC_CLASS);
  current.classList.remove(CLOSING_CLASS);
  current.removeAttribute('hidden');

  const currentPanel = current.querySelector<HTMLElement>('[data-cart-panel]');
  const nextPanel = next.querySelector<HTMLElement>('[data-cart-panel]');

  if (currentPanel && nextPanel) {
    currentPanel.replaceChildren(...Array.from(nextPanel.children));
    return;
  }

  // Fallback: full child swap if markup is unexpected.
  current.replaceChildren(...Array.from(next.children));
}

function applyFragments(
  fragments: Record<string, string> | undefined,
  options: { keepDrawerOpen?: boolean } = {},
): void {
  if (!fragments) {
    return;
  }

  const keepDrawerOpen = Boolean(options.keepDrawerOpen);

  Object.entries(fragments).forEach(([selector, html]) => {
    if (!html) {
      return;
    }

    document.querySelectorAll(selector).forEach((node) => {
      const next = parseFragmentElement(html);

      if (
        keepDrawerOpen &&
        node instanceof HTMLElement &&
        next instanceof HTMLElement &&
        node.hasAttribute('data-cart-drawer')
      ) {
        patchOpenDrawer(node, next);
        return;
      }

      if (next) {
        node.replaceWith(next);
      } else if (selector.includes('data-cart-count')) {
        node.innerHTML = html;
      }
    });
  });
}

async function postCart(url: string, body: Record<string, string>): Promise<CartResponse> {
  const config = getConfig();
  if (!config) {
    throw new Error('Missing cart config');
  }

  const form = new FormData();
  Object.entries(body).forEach(([key, value]) => form.append(key, value));
  form.append('nonce', config.nonce);

  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    body: form,
  });

  return (await response.json()) as CartResponse;
}

function isElementVisible(el: HTMLElement): boolean {
  if (!el.getClientRects().length) {
    return false;
  }
  const style = window.getComputedStyle(el);
  return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
}

function getCartFlyTarget(): HTMLElement | null {
  const candidates = Array.from(
    document.querySelectorAll<HTMLElement>(
      '[data-cart-fly-target], [data-cart-open], [data-header-shop], .site-header__action--cart',
    ),
  );

  return candidates.find((el) => isElementVisible(el)) ?? null;
}

function revealSiteHeader(): void {
  document.querySelector<HTMLElement>('[data-site-header]')?.classList.remove('is-scroll-hidden');
}

function pulseCartTarget(target: HTMLElement | null): void {
  if (!target) {
    return;
  }
  target.classList.add(RECEIVED_CLASS);
  window.setTimeout(() => target.classList.remove(RECEIVED_CLASS), RECEIVE_MS);
}

function flyToCart(
  fromEl: HTMLElement,
  toEl: HTMLElement,
  prefersReducedMotion: boolean,
): Promise<void> {
  return new Promise((resolve) => {
    revealSiteHeader();
    document.body.classList.add(FLYING_CLASS);

    const finish = (): void => {
      document.body.classList.remove(FLYING_CLASS);
      resolve();
    };

    if (prefersReducedMotion) {
      finish();
      return;
    }

    const from = fromEl.getBoundingClientRect();
    const to = toEl.getBoundingClientRect();
    const startX = from.left + from.width / 2;
    const startY = from.top + from.height / 2;
    const endX = to.left + to.width / 2;
    const endY = to.top + to.height / 2;

    const particle = document.createElement('span');
    particle.className = 'cart-fly-particle';
    particle.setAttribute('aria-hidden', 'true');
    particle.style.left = `${startX}px`;
    particle.style.top = `${startY}px`;
    document.body.appendChild(particle);

    void particle.offsetWidth;
    particle.style.transform = `translate3d(${endX - startX}px, ${endY - startY}px, 0) scale(0.35)`;
    particle.style.opacity = '0.2';

    let settled = false;
    const settle = (): void => {
      if (settled) {
        return;
      }
      settled = true;
      particle.remove();
      finish();
    };

    particle.addEventListener('transitionend', settle, { once: true });
    window.setTimeout(settle, FLY_MS + 80);
  });
}

function resolveAddToCartProductId(form: HTMLFormElement): number {
  const variationId = form.querySelector<HTMLInputElement>('input.variation_id')?.value ?? '';
  if (variationId && variationId !== '0') {
    return Number.parseInt(variationId, 10) || 0;
  }

  const hidden = form.querySelector<HTMLInputElement>('input[name="add-to-cart"]')?.value ?? '';
  if (hidden) {
    return Number.parseInt(hidden, 10) || 0;
  }

  const button = form.querySelector<HTMLButtonElement>('button[name="add-to-cart"]')?.value ?? '';
  return Number.parseInt(button, 10) || 0;
}

function resolveAddToCartQuantity(form: HTMLFormElement): number {
  const qty =
    form.querySelector<HTMLInputElement>('input.qty, input[name="quantity"]')?.value ?? '1';
  const parsed = Number.parseFloat(qty);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : 1;
}

async function ajaxAddToCart(productId: number, quantity: number): Promise<WcAddToCartResponse> {
  const config = getConfig();
  if (!config?.addToCartUrl) {
    throw new Error('Missing add-to-cart URL');
  }

  const body = new FormData();
  body.append('product_id', String(productId));
  body.append('quantity', String(quantity));

  const response = await fetch(config.addToCartUrl, {
    method: 'POST',
    credentials: 'same-origin',
    body,
  });

  const text = await response.text();
  try {
    return JSON.parse(text) as WcAddToCartResponse;
  } catch {
    throw new Error('Invalid add-to-cart response');
  }
}

export function initCartDrawer(): void {
  const config = getConfig();
  if (!config) {
    return;
  }

  let closeTimer = 0;
  let syncing = false;
  let lastKnownHash = config.cartHash ?? getCookieCartHash();
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  rememberCartHash(lastKnownHash);

  const getDrawer = (): HTMLElement | null =>
    document.querySelector<HTMLElement>('[data-cart-drawer]');

  const isOpen = (drawer: HTMLElement): boolean => drawer.classList.contains(OPEN_CLASS);
  const isClosing = (drawer: HTMLElement): boolean => drawer.classList.contains(CLOSING_CLASS);

  const finishClose = (drawer: HTMLElement): void => {
    drawer.classList.remove(CLOSING_CLASS);
    drawer.setAttribute('hidden', '');
    unlockScroll();
  };

  const open = (): void => {
    const drawer = getDrawer();
    const panel = drawer?.querySelector<HTMLElement>('[data-cart-panel]');
    if (!drawer || !panel) {
      return;
    }

    window.clearTimeout(closeTimer);
    drawer.classList.remove(CLOSING_CLASS, STATIC_CLASS);
    drawer.removeAttribute('hidden');
    lockScroll();
    revealSiteHeader();

    if (prefersReducedMotion) {
      drawer.classList.add(OPEN_CLASS);
      panel.focus({ preventScroll: true });
      return;
    }

    void drawer.offsetWidth;
    drawer.classList.add(OPEN_CLASS);
    panel.focus({ preventScroll: true });
  };

  const close = (): void => {
    const drawer = getDrawer();
    if (!drawer || !isOpen(drawer) || isClosing(drawer)) {
      return;
    }

    drawer.classList.remove(OPEN_CLASS, STATIC_CLASS);

    if (prefersReducedMotion) {
      finishClose(drawer);
      return;
    }

    drawer.classList.add(CLOSING_CLASS);
    closeTimer = window.setTimeout(() => finishClose(drawer), PANEL_CLOSE_MS);
  };

  const syncKnownHash = (hash?: string): void => {
    const next = typeof hash === 'string' ? hash : getCookieCartHash();
    lastKnownHash = next;
    rememberCartHash(next);
  };

  const applyCartFragments = (
    fragments: Record<string, string> | undefined,
    options: { keepDrawerOpen?: boolean; cartHash?: string } = {},
  ): void => {
    applyFragments(fragments, { keepDrawerOpen: options.keepDrawerOpen });
    syncKnownHash(options.cartHash);
  };

  const refreshFromResponse = async (response: CartResponse): Promise<void> => {
    const drawer = getDrawer();
    const wasOpen = Boolean(drawer && isOpen(drawer));

    applyCartFragments(response.data?.fragments, {
      keepDrawerOpen: wasOpen,
      cartHash: response.data?.cart_hash,
    });

    if (response.data?.label) {
      document.querySelectorAll('[data-cart-count-label]').forEach((el) => {
        el.textContent = response.data?.label ?? '';
      });
    }

    if (wasOpen) {
      lockScroll();
    }
  };

  /**
   * Other tabs share the cart cookie; refresh badge/drawer when it drifts.
   */
  const refreshIfStale = async (force = false): Promise<void> => {
    if (syncing) {
      return;
    }

    if (!force && getCookieCartHash() === lastKnownHash) {
      return;
    }

    syncing = true;
    try {
      const payload = await fetchCartFragments();
      if (!payload?.fragments) {
        return;
      }

      const drawer = getDrawer();
      const wasOpen = Boolean(drawer && isOpen(drawer));
      applyCartFragments(payload.fragments, {
        keepDrawerOpen: wasOpen,
        cartHash: payload.cart_hash ?? getCookieCartHash(),
      });

      if (wasOpen) {
        lockScroll();
      }
    } finally {
      syncing = false;
    }
  };

  const setBusy = (busy: boolean): void => {
    getDrawer()?.classList.toggle(BUSY_CLASS, busy);
  };

  const updateQty = async (key: string, quantity: number): Promise<void> => {
    setBusy(true);
    try {
      const response = await postCart(config.updateUrl, {
        cart_item_key: key,
        quantity: String(quantity),
      });
      if (!response.success) {
        throw new Error('Update failed');
      }
      await refreshFromResponse(response);
    } finally {
      setBusy(false);
    }
  };

  const removeItem = async (key: string): Promise<void> => {
    setBusy(true);
    try {
      const response = await postCart(config.removeUrl, {
        cart_item_key: key,
      });
      if (!response.success) {
        throw new Error('Remove failed');
      }
      await refreshFromResponse(response);
    } finally {
      setBusy(false);
    }
  };

  const animateAddToCart = async (
    source: HTMLElement,
    fragments: Record<string, string> | undefined,
    cartHash?: string,
  ): Promise<void> => {
    const target = getCartFlyTarget();
    const drawer = getDrawer();
    const wasOpen = Boolean(drawer && isOpen(drawer));

    if (target) {
      await flyToCart(source, target, prefersReducedMotion);
    }

    applyCartFragments(fragments, { keepDrawerOpen: wasOpen, cartHash });
    pulseCartTarget(getCartFlyTarget());

    if (wasOpen) {
      lockScroll();
    }
  };

  // Capture phase so we beat page-transition (also capture) on the cart link.
  document.addEventListener(
    'click',
    (event) => {
      const target = event.target;
      if (!(target instanceof Element)) {
        return;
      }

      const openTrigger = target.closest<HTMLElement>('[data-cart-open]');
      if (openTrigger) {
        event.preventDefault();
        event.stopPropagation();
        open();
        return;
      }

      if (target.closest('[data-cart-close]')) {
        event.preventDefault();
        close();
        return;
      }

      const item = target.closest<HTMLElement>('[data-cart-item]');
      if (!item) {
        return;
      }

      const key = item.dataset.key;
      if (!key) {
        return;
      }

      if (target.closest('[data-cart-remove]')) {
        event.preventDefault();
        void removeItem(key);
        return;
      }

      const valueEl = item.querySelector<HTMLElement>('[data-cart-qty-value]');
      const current = Number(valueEl?.textContent?.trim() ?? '1');

      if (target.closest('[data-cart-qty-minus]')) {
        event.preventDefault();
        void updateQty(key, Math.max(0, current - 1));
        return;
      }

      if (target.closest('[data-cart-qty-plus]')) {
        event.preventDefault();
        void updateQty(key, current + 1);
      }
    },
    true,
  );

  document.addEventListener('keydown', (event) => {
    const drawer = getDrawer();
    if (event.key === 'Escape' && drawer && isOpen(drawer)) {
      event.preventDefault();
      close();
    }
  });

  // PDP Aggiungi — AJAX add + fly to header cart (no reload / no drawer open).
  document.addEventListener(
    'submit',
    (event) => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement) || !form.classList.contains('cart')) {
        return;
      }

      if (!config.addToCartUrl) {
        return;
      }

      const button =
        form.querySelector<HTMLButtonElement>('[data-product-add]') ??
        form.querySelector<HTMLButtonElement>('button.single_add_to_cart_button');

      if (button?.classList.contains('disabled') || button?.disabled) {
        event.preventDefault();
        return;
      }

      const productId = resolveAddToCartProductId(form);
      if (!productId) {
        return;
      }

      event.preventDefault();

      const quantity = resolveAddToCartQuantity(form);
      const source = button ?? form;

      void (async () => {
        source.classList.add('is-adding');
        if (source instanceof HTMLButtonElement) {
          source.disabled = true;
        }

        try {
          const target = getCartFlyTarget();
          const [response] = await Promise.all([
            ajaxAddToCart(productId, quantity),
            target ? flyToCart(source, target, prefersReducedMotion) : Promise.resolve(),
          ]);

          if (response.error) {
            if (response.product_url) {
              window.location.assign(response.product_url);
            }
            return;
          }

          applyCartFragments(response.fragments, {
            keepDrawerOpen: false,
            cartHash: response.cart_hash,
          });
          pulseCartTarget(getCartFlyTarget());

          // Keep history on a GET URL so refresh cannot re-POST add-to-cart.
          if (window.history.replaceState) {
            window.history.replaceState(null, '', window.location.href);
          }
        } catch {
          // Never fall back to form.submit() — that leaves a POST document and
          // refresh would add the product again. Soft-reload as GET instead.
          window.location.assign(window.location.pathname + window.location.search);
        } finally {
          source.classList.remove('is-adding');
          if (source instanceof HTMLButtonElement) {
            source.disabled = false;
          }
        }
      })();
    },
    true,
  );

  // WooCommerce archive / other AJAX add-to-cart — fly instead of opening the drawer.
  const $ = (window as Window & { jQuery?: JQueryStatic }).jQuery;
  if ($) {
    $(document.body).on('added_to_cart', (...args: unknown[]) => {
      const fragments = args[1];
      const cartHash = typeof args[2] === 'string' ? args[2] : undefined;
      const buttonArg = args[3];
      const source =
        buttonArg &&
        typeof buttonArg === 'object' &&
        '0' in (buttonArg as { 0?: HTMLElement }) &&
        (buttonArg as { 0?: HTMLElement })[0] instanceof HTMLElement
          ? (buttonArg as { 0: HTMLElement })[0]
          : document.querySelector<HTMLElement>('[data-product-add]');

      if (fragments && typeof fragments === 'object' && source) {
        void animateAddToCart(source, fragments as Record<string, string>, cartHash);
        return;
      }

      if (fragments && typeof fragments === 'object') {
        applyCartFragments(fragments as Record<string, string>, {
          keepDrawerOpen: false,
          cartHash,
        });
      }
    });
  }

  // Multi-tab / bfcache: badge + drawer catch up when this tab becomes active again.
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      void refreshIfStale();
    }
  });

  window.addEventListener('focus', () => {
    void refreshIfStale();
  });

  window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
      void refreshIfStale(true);
    }
  });

  window.addEventListener('storage', (event) => {
    if (event.key !== CART_HASH_STORAGE_KEY || event.newValue === lastKnownHash) {
      return;
    }
    void refreshIfStale(true);
  });

  // Deep-link support: ?cart=open
  const params = new URLSearchParams(window.location.search);
  if (params.get('cart') === 'open') {
    open();
  }
}

type JQueryStatic = {
  (selector: unknown): {
    on: (events: string, handler: (...args: unknown[]) => void) => void;
  };
};

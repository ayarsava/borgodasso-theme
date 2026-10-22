/**
 * Hide the site header above the viewport on scroll down; reveal on scroll up.
 */

const HIDDEN_CLASS = 'is-scroll-hidden';
const SCROLL_DELTA = 6;
const TOP_THRESHOLD = 16;

function setHeaderHeight(header: HTMLElement): void {
  document.documentElement.style.setProperty('--site-header-height', `${header.offsetHeight}px`);
}

export function initSiteHeaderScroll(): void {
  const header = document.querySelector<HTMLElement>('[data-site-header]');

  if (!header) {
    return;
  }

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  setHeaderHeight(header);
  window.addEventListener('resize', () => setHeaderHeight(header), { passive: true });

  if (prefersReducedMotion) {
    return;
  }

  let lastY = window.scrollY;
  let ticking = false;

  const shouldKeepVisible = (): boolean => {
    if (
      document.body.classList.contains('is-cart-flying') ||
      document.body.classList.contains('is-cart-drawer-open')
    ) {
      return true;
    }

    const drawer = document.querySelector<HTMLElement>('[data-nav-drawer]');
    return drawer !== null && drawer.classList.contains('is-open');
  };

  const update = (): void => {
    ticking = false;
    const y = window.scrollY;

    if (shouldKeepVisible() || y <= TOP_THRESHOLD) {
      header.classList.remove(HIDDEN_CLASS);
      lastY = y;
      return;
    }

    if (y > lastY + SCROLL_DELTA) {
      header.classList.add(HIDDEN_CLASS);
    } else if (y < lastY - SCROLL_DELTA) {
      header.classList.remove(HIDDEN_CLASS);
    }

    lastY = y;
  };

  window.addEventListener(
    'scroll',
    () => {
      if (!ticking) {
        ticking = true;
        requestAnimationFrame(update);
      }
    },
    { passive: true },
  );
}

/**
 * Fake page transitions — fade out before navigate, fade in on load.
 * Full document reload; no AJAX.
 */

const PENDING_CLASS = 'is-page-pending';
const READY_CLASS = 'is-page-ready';
const EXITING_CLASS = 'is-page-exiting';
const DURATION_MS = 420;

let locking = false;
let exitTimer: number | null = null;

function prefersReducedMotion(): boolean {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function clearExitTimer(): void {
  if (exitTimer !== null) {
    window.clearTimeout(exitTimer);
    exitTimer = null;
  }
}

function markReady(): void {
  locking = false;
  clearExitTimer();

  const root = document.documentElement;
  root.classList.remove(PENDING_CLASS, EXITING_CLASS);
  root.classList.add(READY_CLASS);
}

function sameOrigin(url: URL): boolean {
  return url.origin === window.location.origin;
}

function shouldIntercept(anchor: HTMLAnchorElement, event: MouseEvent): boolean {
  if (event.defaultPrevented) {
    return false;
  }

  if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
    return false;
  }

  if (anchor.target && anchor.target !== '_self') {
    return false;
  }

  if (anchor.hasAttribute('download')) {
    return false;
  }

  // Cart drawer / other in-page overlays — don't navigate away.
  if (anchor.hasAttribute('data-cart-open') || anchor.closest('[data-cart-open]')) {
    return false;
  }

  const href = anchor.getAttribute('href');
  if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) {
    return false;
  }

  let url: URL;
  try {
    url = new URL(anchor.href, window.location.href);
  } catch {
    return false;
  }

  if (!sameOrigin(url)) {
    return false;
  }

  // Same page (path + query), hash-only navigation.
  if (
    url.pathname === window.location.pathname &&
    url.search === window.location.search &&
    url.hash !== ''
  ) {
    return false;
  }

  // Skip WordPress admin / login.
  if (url.pathname.includes('/wp-admin') || url.pathname.includes('/wp-login.php')) {
    return false;
  }

  return true;
}

function navigate(url: string): void {
  window.location.assign(url);
}

export function initPageTransition(): void {
  const root = document.documentElement;

  if (prefersReducedMotion()) {
    markReady();
    return;
  }

  // Double rAF: commit opacity 0, then enable the enter transition next frame
  // so the browser does not animate from a pre-style visible state.
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      markReady();
    });
  });

  // Never freeze a page in bfcache while faded out (click navigations set EXITING).
  window.addEventListener('pagehide', (event) => {
    if (event.persisted) {
      markReady();
    }
  });

  // Back/Forward restore (bfcache or reload) — always ensure body is visible
  // and click interception is unlocked (bfcache keeps JS state).
  window.addEventListener('pageshow', () => {
    markReady();
  });

  document.addEventListener(
    'click',
    (event) => {
      if (locking) {
        event.preventDefault();
        return;
      }

      const target = event.target;
      if (!(target instanceof Element)) {
        return;
      }

      const anchor = target.closest('a');
      if (!(anchor instanceof HTMLAnchorElement)) {
        return;
      }

      if (!shouldIntercept(anchor, event)) {
        return;
      }

      event.preventDefault();
      locking = true;

      const nextUrl = anchor.href;

      root.classList.remove(READY_CLASS);
      root.classList.add(EXITING_CLASS);

      clearExitTimer();
      exitTimer = window.setTimeout(() => {
        exitTimer = null;
        navigate(nextUrl);
      }, DURATION_MS);
    },
    true,
  );
}

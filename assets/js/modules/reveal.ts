/**
 * Scroll reveal — Intersection Observer toggles a class; CSS handles motion.
 * GPU-friendly (opacity + transform only), no animation library on inner pages.
 */

const VISIBLE_CLASS = 'is-visible';
const REVEAL_CLASS = 'reveal';

const PAGE_BUILDER_SELECTOR = '.page-builder > section';
const SHOP_CATALOG_SELECTOR = '.shop-catalog';
const CASA_REVEAL_SELECTOR = [
  '.casa-intro',
  '.casa__mobile > .casa-details',
  '.casa-cta',
  '.casa-related',
].join(', ');

let revealObserver: IntersectionObserver | null = null;
let scrollSyncBound = false;

function revealTargets(): HTMLElement[] {
  const pageBuilder = Array.from(
    document.querySelectorAll<HTMLElement>(PAGE_BUILDER_SELECTOR),
  ).filter(
    (el) => !el.classList.contains('story-mosaic') && !el.classList.contains('media-gallery'),
  );

  const casa = Array.from(document.querySelectorAll<HTMLElement>(CASA_REVEAL_SELECTOR));
  const shop = Array.from(document.querySelectorAll<HTMLElement>(SHOP_CATALOG_SELECTOR));

  // Also catch any other `.reveal` nodes (e.g. left mid-flight before initReveal).
  const marked = Array.from(document.querySelectorAll<HTMLElement>(`.${REVEAL_CLASS}`));

  const seen = new Set<HTMLElement>();
  const merged: HTMLElement[] = [];

  [...pageBuilder, ...casa, ...shop, ...marked].forEach((el) => {
    if (seen.has(el)) {
      return;
    }
    seen.add(el);
    merged.push(el);
  });

  return merged;
}

/** Check whether an element is already within (or above) the visible viewport. */
function isInOrAboveViewport(el: HTMLElement): boolean {
  const rect = el.getBoundingClientRect();
  const vh = window.innerHeight || document.documentElement.clientHeight;
  // Bottom is above viewport (scrolled past) or top is within the viewport.
  return rect.bottom <= 0 || rect.top < vh * 0.92;
}

function showRevealTarget(el: HTMLElement): void {
  el.classList.add(VISIBLE_CLASS);
  revealObserver?.unobserve(el);
}

export function isBackForwardNavigation(): boolean {
  const entries = performance.getEntriesByType('navigation');
  const nav = entries[0] as PerformanceNavigationTiming | undefined;
  if (nav?.type === 'back_forward') {
    return true;
  }

  // Legacy fallback.
  const legacy = performance as Performance & { navigation?: { type?: number } };
  return legacy.navigation?.type === 2;
}

/**
 * Show any reveal targets already in or above the viewport.
 *
 * Needed after browser Back/Forward: scroll restoration often lands mid-page
 * after prepareReveal ran at scrollY ≈ 0, so sections above the fold stay
 * opacity:0 until the user scrolls them into the IntersectionObserver root.
 */
export function syncRevealToScroll(): void {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  revealTargets().forEach((el) => {
    if (el.classList.contains(VISIBLE_CLASS)) {
      return;
    }

    if (isInOrAboveViewport(el)) {
      showRevealTarget(el);
    }
  });
}

/**
 * Retry sync while the browser finishes Back/Forward scroll restoration.
 */
export function syncRevealBurst(): void {
  syncRevealToScroll();

  [0, 50, 100, 250, 500].forEach((ms) => {
    window.setTimeout(syncRevealToScroll, ms);
  });

  let frames = 0;
  const tick = (): void => {
    syncRevealToScroll();
    frames += 1;
    if (frames < 24) {
      requestAnimationFrame(tick);
    }
  };
  requestAnimationFrame(tick);
}

function bindScrollSync(): void {
  if (scrollSyncBound) {
    return;
  }

  scrollSyncBound = true;

  window.addEventListener('pageshow', () => {
    syncRevealBurst();
  });

  window.addEventListener('load', () => {
    syncRevealBurst();
  });

  // Late scroll restoration (common on Forward) — sync while pending reveals remain.
  window.addEventListener(
    'scroll',
    () => {
      if (!document.querySelector(`.${REVEAL_CLASS}:not(.${VISIBLE_CLASS})`)) {
        return;
      }
      syncRevealToScroll();
    },
    { passive: true },
  );
}

/**
 * Mark sections as reveal targets immediately so they stay hidden until observed.
 * Sections already in the viewport (e.g. after a mid-scroll refresh) are shown
 * instantly — no hide/show flash.
 *
 * Shop catalog always waits when a page header is present (it sits in-view under
 * the header and should not appear before the header entrance finishes).
 *
 * Call this synchronously on boot — before any await (e.g. page-header entrance).
 */
export function prepareReveal(): void {
  bindScrollSync();

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  // Back/Forward: don't start hidden — scroll position may restore after this runs.
  if (isBackForwardNavigation()) {
    revealTargets().forEach((el) => {
      el.classList.add(REVEAL_CLASS);
      // Defer visibility to syncRevealBurst once layout/scroll settle.
    });
    syncRevealBurst();
    return;
  }

  const waitForPageHeader = Boolean(document.querySelector('[data-page-header]'));

  revealTargets().forEach((el) => {
    // Shop under a page header is usually in-view on load — keep it hidden until
    // initReveal runs after the header entrance (same cadence as page-builder).
    const deferForHeader = waitForPageHeader && el.matches(SHOP_CATALOG_SELECTOR);

    if (!deferForHeader && isInOrAboveViewport(el)) {
      el.classList.add(VISIBLE_CLASS);
    } else {
      el.classList.add(REVEAL_CLASS);
    }
  });
}

export function initReveal(): void {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const targets = revealTargets();

  if (targets.length === 0) {
    return;
  }

  if (prefersReducedMotion || !('IntersectionObserver' in window)) {
    targets.forEach((el) => {
      el.classList.remove(REVEAL_CLASS);
      el.classList.add(VISIBLE_CLASS);
    });
    return;
  }

  // Back/Forward: show everything already passed; observe only what's still below.
  if (isBackForwardNavigation()) {
    syncRevealBurst();
  }

  revealObserver?.disconnect();
  revealObserver = new IntersectionObserver(
    (entries, io) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add(VISIBLE_CLASS);
        io.unobserve(entry.target);
      });
    },
    {
      root: null,
      rootMargin: '0px 0px -8% 0px',
      threshold: 0.15,
    },
  );

  targets.forEach((el) => {
    if (!el.classList.contains(VISIBLE_CLASS)) {
      el.classList.add(REVEAL_CLASS);
      revealObserver?.observe(el);
    }
  });

  syncRevealToScroll();
  bindScrollSync();
}

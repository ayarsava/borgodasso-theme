/**
 * Page header + site header entrance — fonts first, then reveal on scroll into view.
 */

const FONT_SETTLE_MS = 160;
const FONT_TIMEOUT_MS = 2800;
const ENTERED_CLASS = 'is-entered';

const BRAND_FONTS = [
  '200 1rem "PP Editorial New"',
  '400 1rem "PP Neue Montreal"',
  '500 1rem "PP Neue Montreal"',
];

function wait(ms: number): Promise<void> {
  return new Promise((resolve) => {
    window.setTimeout(resolve, ms);
  });
}

async function waitForBrandFonts(): Promise<void> {
  if (!document.fonts?.load) {
    return;
  }

  const loads = BRAND_FONTS.map((face) => document.fonts.load(face).catch(() => undefined));

  await Promise.race([Promise.all([document.fonts.ready, ...loads]), wait(FONT_TIMEOUT_MS)]);
}

function observeEnter(target: HTMLElement, onEnter: () => void): void {
  let entered = false;

  const apply = () => {
    if (entered) {
      return;
    }

    entered = true;
    onEnter();
    observer?.disconnect();
  };

  let observer: IntersectionObserver | null = null;

  if (!('IntersectionObserver' in window)) {
    apply();
    return;
  }

  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting || entry.intersectionRatio < 0.08) {
          return;
        }

        apply();
      });
    },
    {
      root: null,
      rootMargin: '0px 0px -5% 0px',
      threshold: [0, 0.08, 0.15],
    },
  );

  observer.observe(target);

  requestAnimationFrame(() => {
    const rect = target.getBoundingClientRect();
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

    if (rect.top < viewportHeight * 0.92 && rect.bottom > 0) {
      apply();
    }
  });
}

function enter(pageHeader: HTMLElement | null, siteHeader: HTMLElement | null): void {
  document.documentElement.classList.add('fonts-ready');
  pageHeader?.classList.add(ENTERED_CLASS);
  siteHeader?.classList.add(ENTERED_CLASS);
}

export async function initPageHeader(): Promise<void> {
  const pageHeader = document.querySelector<HTMLElement>('[data-page-header]');
  const siteHeader = document.querySelector<HTMLElement>('[data-site-header]');

  if (!pageHeader && !siteHeader) {
    return;
  }

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const entries = performance.getEntriesByType('navigation');
  const nav = entries[0] as PerformanceNavigationTiming | undefined;
  const isBackForward = nav?.type === 'back_forward';

  // Back/Forward: skip entrance choreography — scroll may restore mid-page.
  if (prefersReducedMotion || isBackForward) {
    enter(pageHeader, siteHeader);
    return;
  }

  await waitForBrandFonts();
  await wait(FONT_SETTLE_MS);

  // A hidden page header (mobile shop) has no box, so it can never intersect.
  // Reveal from the site header instead, or the menu, logo, and icons stay invisible.
  const pageHeaderVisible = !!pageHeader && pageHeader.getClientRects().length > 0;
  const trigger = pageHeaderVisible ? pageHeader : siteHeader;

  if (!trigger) {
    return;
  }

  await new Promise<void>((resolve) => {
    observeEnter(trigger, () => {
      enter(pageHeader, siteHeader);
      resolve();
    });
  });

  // Let page-header title/description CSS delays start before page-builder reveals.
  if (pageHeaderVisible) {
    await wait(700);
  }
}

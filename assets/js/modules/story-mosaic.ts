/**
 * Story mosaic — each media figure animates as it enters the viewport.
 */

const VISIBLE_CLASS = 'is-visible';

function isBackForwardNavigation(): boolean {
  const entries = performance.getEntriesByType('navigation');
  const nav = entries[0] as PerformanceNavigationTiming | undefined;
  return nav?.type === 'back_forward';
}

function syncFrames(frames: NodeListOf<HTMLElement>): void {
  const vh = window.innerHeight || document.documentElement.clientHeight;
  frames.forEach((el) => {
    if (el.classList.contains(VISIBLE_CLASS)) {
      return;
    }
    const rect = el.getBoundingClientRect();
    if (rect.bottom <= 0 || rect.top < vh * 0.92) {
      el.classList.add(VISIBLE_CLASS);
    }
  });
}

export function initStoryMosaic(): void {
  const frames = document.querySelectorAll<HTMLElement>('.story-mosaic__media');

  if (frames.length === 0) {
    return;
  }

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (prefersReducedMotion || !('IntersectionObserver' in window) || isBackForwardNavigation()) {
    frames.forEach((el) => el.classList.add(VISIBLE_CLASS));
    return;
  }

  const observer = new IntersectionObserver(
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
      rootMargin: '0px',
      threshold: 0,
    },
  );

  frames.forEach((el) => observer.observe(el));

  const sync = (): void => {
    syncFrames(frames);
  };

  window.addEventListener('pageshow', sync);
  sync();
}

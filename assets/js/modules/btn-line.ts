/**
 * Draw `.btn__line` left → right when the button enters the viewport.
 * Homepage CTAs are excluded — GSAP owns those lines.
 */

const DRAWN_CLASS = 'is-line-drawn';

export function initBtnLines(): void {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const buttons = Array.from(document.querySelectorAll<HTMLElement>('[data-btn-line]'))
    .map((line) => line.closest<HTMLElement>('.btn'))
    .filter((btn): btn is HTMLElement => btn !== null && !btn.closest('[data-home]'));

  // Dedupe if a button somehow has multiple lines.
  const unique = Array.from(new Set(buttons));

  if (unique.length === 0) {
    return;
  }

  if (prefersReducedMotion || !('IntersectionObserver' in window)) {
    unique.forEach((btn) => btn.classList.add(DRAWN_CLASS));
    return;
  }

  const observer = new IntersectionObserver(
    (entries, io) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add(DRAWN_CLASS);
        io.unobserve(entry.target);
      });
    },
    {
      root: null,
      rootMargin: '0px 0px -8% 0px',
      threshold: 0.35,
    },
  );

  unique.forEach((btn) => observer.observe(btn));
}

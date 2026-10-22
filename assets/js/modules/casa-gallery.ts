/**
 * Casa mobile fullscreen gallery — scroll snaps + live counter + scroll hint.
 */

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function pad2(n: number): string {
  return String(n).padStart(2, '0');
}

function initOne(root: HTMLDialogElement): void {
  const scroller = root.querySelector<HTMLElement>('[data-casa-gallery-scroller]');
  const currentEl = root.querySelector<HTMLElement>('[data-casa-gallery-current]');
  const hintEl = root.querySelector<HTMLElement>('[data-casa-gallery-hint]');
  const slides = Array.from(root.querySelectorAll<HTMLElement>('[data-casa-gallery-slide]'));
  const closeBtn = root.querySelector<HTMLButtonElement>('[data-casa-gallery-close]');

  if (!scroller || slides.length === 0) {
    return;
  }

  const showHint = slides.length > 1;
  const canNudge = showHint && !prefersReducedMotion;
  let nudgePlayed = false;
  let nudgeTimer = 0;

  const updateCounter = (): void => {
    if (!currentEl) {
      return;
    }

    const mid = scroller.scrollTop + scroller.clientHeight / 2;
    let active = 0;

    slides.forEach((slide, index) => {
      const top = slide.offsetTop;
      const bottom = top + slide.offsetHeight;
      if (mid >= top && mid < bottom) {
        active = index;
      }
    });

    currentEl.textContent = pad2(active + 1);
  };

  const endNudge = (): void => {
    window.clearTimeout(nudgeTimer);
    scroller.classList.remove('is-hinting');
  };

  const playNudge = (): void => {
    if (nudgePlayed || !canNudge) {
      return;
    }

    nudgePlayed = true;
    scroller.classList.add('is-hinting');
    scroller.addEventListener('animationend', endNudge, { once: true });
  };

  const cancelNudge = (): void => {
    if (!canNudge) {
      return;
    }

    nudgePlayed = true;
    endNudge();
  };

  let ticking = false;
  scroller.addEventListener(
    'scroll',
    () => {
      cancelNudge();

      if (ticking) {
        return;
      }
      ticking = true;
      requestAnimationFrame(() => {
        updateCounter();
        ticking = false;
      });
    },
    { passive: true },
  );

  scroller.addEventListener('pointerdown', cancelNudge, { passive: true });
  scroller.addEventListener('touchstart', cancelNudge, { passive: true });
  scroller.addEventListener('wheel', cancelNudge, { passive: true });

  const open = (): void => {
    if (typeof root.showModal === 'function') {
      root.showModal();
    } else {
      root.setAttribute('open', '');
    }
    scroller.scrollTop = 0;
    updateCounter();
    document.documentElement.classList.add('is-casa-gallery-open');

    if (showHint) {
      hintEl?.classList.add('is-visible');
    }

    if (canNudge) {
      nudgePlayed = false;
      scroller.classList.remove('is-hinting');
      nudgeTimer = window.setTimeout(playNudge, 420);
    }
  };

  const close = (): void => {
    cancelNudge();
    hintEl?.classList.remove('is-visible');
    if (typeof root.close === 'function') {
      root.close();
    } else {
      root.removeAttribute('open');
    }
    document.documentElement.classList.remove('is-casa-gallery-open');
  };

  document.querySelectorAll<HTMLElement>('[data-casa-gallery-open]').forEach((btn) => {
    btn.addEventListener('click', (event) => {
      event.preventDefault();
      open();
    });
  });

  closeBtn?.addEventListener('click', () => close());

  root.addEventListener('cancel', (event) => {
    event.preventDefault();
    close();
  });

  root.addEventListener('click', (event) => {
    if (event.target === root) {
      close();
    }
  });
}

export function initCasaGallery(): void {
  const dialog = document.querySelector<HTMLDialogElement>('[data-casa-gallery]');
  if (!dialog) {
    return;
  }
  initOne(dialog);
}

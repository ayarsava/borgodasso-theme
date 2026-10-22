import EmblaCarousel, { type EmblaCarouselType, type EmblaOptionsType } from 'embla-carousel';

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const DRAG_CLICK_THRESHOLD = 6;

function syncArrows(root: HTMLElement, embla: EmblaCarouselType): void {
  const prev = root.querySelector<HTMLButtonElement>('[data-account-order-prev]');
  const next = root.querySelector<HTMLButtonElement>('[data-account-order-next]');

  if (!prev || !next) {
    return;
  }

  const atStart = !embla.canScrollPrev();
  const atEnd = !embla.canScrollNext();

  prev.toggleAttribute('disabled', atStart);
  next.toggleAttribute('disabled', atEnd);
  prev.classList.toggle('is-disabled', atStart);
  next.classList.toggle('is-disabled', atEnd);
}

/** Block product-link clicks when the gesture scrolled/dragged the carousel. */
function suppressDragClicks(viewport: HTMLElement, embla: EmblaCarouselType): void {
  let startX = 0;
  let startY = 0;
  let startSnap = 0;
  let startProgress = 0;
  let blockClick = false;
  let releaseTimer = 0;

  const armBlock = (): void => {
    blockClick = true;
    viewport.classList.add('is-dragging');
  };

  const clearDraggingClass = (): void => {
    window.clearTimeout(releaseTimer);
    // Keep pointer-events off until after the synthetic click would fire.
    releaseTimer = window.setTimeout(() => {
      viewport.classList.remove('is-dragging');
    }, 80);
  };

  embla.on('pointerDown', () => {
    startSnap = embla.selectedScrollSnap();
    startProgress = embla.scrollProgress();
    blockClick = false;
    window.clearTimeout(releaseTimer);
    viewport.classList.remove('is-dragging');
  });

  embla.on('scroll', () => {
    if (
      embla.selectedScrollSnap() !== startSnap ||
      Math.abs(embla.scrollProgress() - startProgress) > 0.002
    ) {
      armBlock();
    }
  });

  embla.on('settle', () => {
    if (embla.selectedScrollSnap() !== startSnap) {
      armBlock();
    }
  });

  viewport.addEventListener(
    'pointerdown',
    (event) => {
      startX = event.clientX;
      startY = event.clientY;
      blockClick = false;
      window.clearTimeout(releaseTimer);
      viewport.classList.remove('is-dragging');
    },
    { passive: true },
  );

  viewport.addEventListener(
    'pointermove',
    (event) => {
      if (blockClick || event.buttons === 0) {
        return;
      }

      const dx = Math.abs(event.clientX - startX);
      const dy = Math.abs(event.clientY - startY);
      if (dx > DRAG_CLICK_THRESHOLD || dy > DRAG_CLICK_THRESHOLD) {
        armBlock();
      }
    },
    { passive: true },
  );

  viewport.addEventListener(
    'pointerup',
    (event) => {
      const dx = Math.abs(event.clientX - startX);
      const dy = Math.abs(event.clientY - startY);
      if (dx > DRAG_CLICK_THRESHOLD || dy > DRAG_CLICK_THRESHOLD) {
        armBlock();
      }
      clearDraggingClass();
    },
    { passive: true },
  );

  viewport.addEventListener(
    'pointercancel',
    () => {
      armBlock();
      clearDraggingClass();
    },
    { passive: true },
  );

  viewport.addEventListener(
    'click',
    (event) => {
      if (!blockClick) {
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();
      blockClick = false;
    },
    true,
  );
}

function initOrderSlider(root: HTMLElement): void {
  const viewport = root.querySelector<HTMLElement>('[data-account-order-viewport]');
  if (!viewport) {
    return;
  }

  const slideCount = viewport.querySelectorAll('.account-order__slide').length;
  if (slideCount < 2) {
    return;
  }

  const options: EmblaOptionsType = {
    align: 'start',
    containScroll: 'trimSnaps',
    dragFree: false,
    slidesToScroll: 1,
    loop: false,
    duration: prefersReducedMotion ? 10 : 22,
  };

  const embla = EmblaCarousel(viewport, options);
  root.classList.add('is-ready');
  suppressDragClicks(viewport, embla);

  const onSelect = () => {
    syncArrows(root, embla);
  };

  embla.on('select', onSelect);
  embla.on('reInit', onSelect);
  onSelect();

  root.querySelector('[data-account-order-prev]')?.addEventListener('click', () => {
    embla.scrollPrev();
  });

  root.querySelector('[data-account-order-next]')?.addEventListener('click', () => {
    embla.scrollNext();
  });
}

export function initAccountOrders(): void {
  document.querySelectorAll<HTMLElement>('[data-account-order]').forEach((root) => {
    initOrderSlider(root);
  });
}

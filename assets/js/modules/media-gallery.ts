import EmblaCarousel, { type EmblaCarouselType, type EmblaOptionsType } from 'embla-carousel';

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function padIndex(index: number): string {
  return String(index + 1).padStart(2, '0');
}

function syncCounter(root: HTMLElement, index: number): void {
  const current = root.querySelector<HTMLElement>('[data-media-gallery-current]');
  if (!current) {
    return;
  }

  current.textContent = padIndex(index);
}

function syncNumberBullets(root: HTMLElement, index: number): void {
  const bullets = root.querySelectorAll<HTMLElement>('.media-gallery__bullet');
  bullets.forEach((bullet, i) => {
    const active = i === index;
    bullet.classList.toggle('is-active', active);
    bullet.setAttribute('aria-current', active ? 'true' : 'false');
  });
}

function syncUi(root: HTMLElement, embla: EmblaCarouselType, pagination: string): void {
  const index = embla.selectedScrollSnap();

  if (pagination === 'counter') {
    syncCounter(root, index);
  }

  if (pagination === 'numbers') {
    syncNumberBullets(root, index);
  }
}

function buildNumberPagination(el: HTMLElement, count: number): void {
  el.innerHTML = Array.from({ length: count }, (_, index) => {
    const label = padIndex(index);
    return `<button type="button" class="media-gallery__bullet" data-gallery-go="${index}" aria-label="Slide ${label}">${label}</button>`;
  }).join('');
}

/**
 * Wait for images that are already requested (eager / complete).
 * Lazy images must not block init — they may never load until scrolled near.
 */
function waitForImages(viewport: HTMLElement): Promise<void> {
  const images = Array.from(viewport.querySelectorAll('img')).filter(
    (img) => !img.classList.contains('blur-up__preview') && (img.loading !== 'lazy' || img.complete),
  );

  if (images.length === 0) {
    return Promise.resolve();
  }

  const pending = Promise.all(
    images.map(
      (img) =>
        new Promise<void>((resolve) => {
          if (img.complete) {
            resolve();
            return;
          }

          img.addEventListener('load', () => resolve(), { once: true });
          img.addEventListener('error', () => resolve(), { once: true });
        }),
    ),
  ).then(() => undefined);

  return Promise.race([pending, new Promise<void>((resolve) => window.setTimeout(resolve, 1200))]);
}

/** Fade each slide in the first time Embla reports it in view. */
function watchSlideFadeIns(embla: EmblaCarouselType): void {
  const slides = embla.slideNodes();

  if (prefersReducedMotion) {
    slides.forEach((slide) => slide.classList.add('is-seen'));
    return;
  }

  const reveal = () => {
    embla.slidesInView().forEach((index) => {
      slides[index]?.classList.add('is-seen');
    });
  };

  embla.on('slidesInView', reveal);
  embla.on('reInit', reveal);
  embla.on('scroll', reveal);

  reveal();
  requestAnimationFrame(reveal);
  window.setTimeout(reveal, 50);
  window.setTimeout(() => {
    if (!slides.some((slide) => slide.classList.contains('is-seen'))) {
      slides.forEach((slide) => slide.classList.add('is-seen'));
    }
  }, 200);
}

function playEntranceHint(viewport: HTMLElement, embla: EmblaCarouselType): void {
  if (prefersReducedMotion || !('IntersectionObserver' in window)) {
    return;
  }

  const track = viewport.querySelector<HTMLElement>('.media-gallery__container');
  if (!track) {
    return;
  }

  const HINT_CLASS = 'is-hinting';
  let played = false;
  let observer: IntersectionObserver | null = null;

  const clearHint = () => {
    track.classList.remove(HINT_CLASS);
  };

  const startHint = () => {
    if (played) {
      return;
    }

    played = true;
    track.classList.add(HINT_CLASS);
    track.addEventListener('animationend', clearHint, { once: true });
  };

  const cancelOnInteract = () => {
    played = true;
    clearHint();
    observer?.disconnect();
  };

  embla.on('pointerDown', cancelOnInteract);

  observer = new IntersectionObserver(
    (entries, io) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting || entry.intersectionRatio < 0.45) {
          return;
        }

        io.disconnect();
        window.setTimeout(startHint, 280);
      });
    },
    {
      threshold: [0.45],
      rootMargin: '0px',
    },
  );

  observer.observe(viewport);
}

async function initGallery(root: HTMLElement): Promise<void> {
  const viewport = root.querySelector<HTMLElement>('[data-media-gallery-viewport]');
  const prevBtn = root.querySelector<HTMLButtonElement>('[data-media-gallery-prev]');
  const nextBtn = root.querySelector<HTMLButtonElement>('[data-media-gallery-next]');
  const paginationEl = root.querySelector<HTMLElement>('[data-media-gallery-pagination-el]');
  const pagination = root.dataset.mediaGalleryPagination ?? 'none';

  if (!viewport) {
    return;
  }

  const slideCount = viewport.querySelectorAll('.media-gallery__slide').length;

  if (slideCount < 2) {
    return;
  }

  if (pagination === 'numbers' && paginationEl) {
    buildNumberPagination(paginationEl, slideCount);
  }

  await waitForImages(viewport);

  const options: EmblaOptionsType = {
    loop: true,
    align: 'center',
    dragFree: true,
    slidesToScroll: 1,
    containScroll: false,
    inViewThreshold: 0,
    duration: prefersReducedMotion ? 10 : 25,
  };

  const embla = EmblaCarousel(viewport, options);

  root.classList.add('is-ready');

  const onSelect = () => {
    syncUi(root, embla, pagination);
  };

  embla.on('select', onSelect);
  embla.on('reInit', onSelect);
  onSelect();

  watchSlideFadeIns(embla);
  playEntranceHint(viewport, embla);

  prevBtn?.addEventListener('click', () => {
    embla.scrollPrev();
  });

  nextBtn?.addEventListener('click', () => {
    embla.scrollNext();
  });

  paginationEl?.addEventListener('click', (event) => {
    const target = (event.target as HTMLElement | null)?.closest<HTMLElement>('[data-gallery-go]');
    if (!target) {
      return;
    }

    const index = Number(target.dataset.galleryGo);
    if (Number.isNaN(index)) {
      return;
    }

    embla.scrollTo(index);
  });
}

export function initMediaGallery(): void {
  const roots = Array.from(document.querySelectorAll<HTMLElement>('[data-media-gallery]'));
  roots.forEach((root) => {
    void initGallery(root);
  });
}

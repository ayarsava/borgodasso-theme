import gsap from 'gsap';
import Swiper from 'swiper';
import { Keyboard, Mousewheel } from 'swiper/modules';
import type { Swiper as SwiperInstance } from 'swiper';
import { splitChars } from './split-chars';
import 'swiper/css';

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const TITLE_ENTER_DELAY = 0.74;
const WORD_TRAVEL = 52;
const WORD_STAGGER = 0.04;
const CHAR_WAVE_SPREAD = 0.17;

/** Short words get bigger per-char travel and wider stagger so the wave stays visible. */
function getWordCharMotion(charCount: number): { lagPercent: number; stagger: number } {
  if (charCount <= 1) {
    return { lagPercent: 72, stagger: 0 };
  }

  const stagger = Math.min(0.058, Math.max(0.028, CHAR_WAVE_SPREAD / charCount));
  const lagPercent = Math.min(115, Math.max(42, 168 / charCount));

  return { lagPercent, stagger };
}

export function initHome(): void {
  const root = document.querySelector<HTMLElement>('[data-home]');
  const swiperEl = root?.querySelector<HTMLElement>('[data-home-swiper]');
  const layers = Array.from(root?.querySelectorAll<HTMLElement>('[data-home-layer]') ?? []);
  const prevBtn = root?.querySelector<HTMLElement>('[data-home-prev]');
  const nextBtn = root?.querySelector<HTMLElement>('[data-home-next]');

  if (!root || !swiperEl || layers.length === 0) {
    return;
  }

  const layerChars = new Map<HTMLElement, HTMLElement[]>();

  layers.forEach((layer) => {
    const stage = layer.querySelector<HTMLElement>('[data-split-chars]');
    const textSource = layer.querySelector<HTMLElement>('[data-split-chars-text]');
    if (!stage || !textSource) {
      return;
    }
    layerChars.set(layer, splitChars(stage, textSource));
    resetLayer(layer, layerChars.get(layer) ?? []);
  });

  const getLayer = (index: number): HTMLElement | undefined => layers[index];

  const firstLayer = layers[0];
  if (firstLayer) {
    setLayerStack(firstLayer, null);
    animateLayerIn(firstLayer, layerChars);
  }

  const swiper = new Swiper(swiperEl, {
    modules: [Mousewheel, Keyboard],
    direction: 'vertical',
    slidesPerView: 1,
    speed: prefersReducedMotion ? 0 : 1000,
    resistanceRatio: 0,
    mousewheel: {
      enabled: true,
      forceToAxis: true,
      releaseOnEdges: true,
      sensitivity: 1,
      thresholdDelta: 4,
      eventsTarget: '[data-home]',
    },
    keyboard: {
      enabled: true,
      onlyInViewport: true,
    },
    on: {
      init: (instance) => {
        updateArrows(instance);
        layers.forEach((layer, i) => {
          if (i !== instance.activeIndex) {
            resetLayer(layer, layerChars.get(layer) ?? []);
          }
        });
        const active = getLayer(instance.activeIndex);
        if (active && !active.classList.contains('is-active')) {
          setLayerStack(active, null);
          animateLayerIn(active, layerChars);
        } else if (active) {
          setLayerStack(active, null);
        }
      },
      slideChangeTransitionStart: (instance) => {
        const prev = getLayer(instance.previousIndex);
        const next = getLayer(instance.activeIndex);
        const direction = instance.activeIndex > instance.previousIndex ? 1 : -1;

        if (prev && next) {
          setLayerStack(next, prev);
          animateLayerOut(prev, layerChars, direction);
          animateLayerIn(next, layerChars, direction);
        }
      },
      slideChangeTransitionEnd: (instance) => {
        updateArrows(instance);
        layers.forEach((layer, i) => {
          if (i !== instance.activeIndex) {
            resetLayer(layer, layerChars.get(layer) ?? []);
          }
        });
        const active = getLayer(instance.activeIndex);
        if (active) {
          setLayerStack(active, null);
        }
      },
    },
  });

  prevBtn?.addEventListener('click', () => swiper.slidePrev());
  nextBtn?.addEventListener('click', () => swiper.slideNext());
}

function setLayerStack(active: HTMLElement, exiting: HTMLElement | null): void {
  document.querySelectorAll<HTMLElement>('[data-home-layer]').forEach((layer) => {
    layer.style.zIndex = '';
  });
  active.style.zIndex = exiting ? '11' : '10';
  if (exiting) {
    exiting.style.zIndex = '12';
  }
}

function getCta(layer: HTMLElement): HTMLElement | null {
  return layer.querySelector<HTMLElement>('.home-layer__actions-inner');
}

function getBtnLine(layer: HTMLElement): HTMLElement | null {
  return layer.querySelector<HTMLElement>('[data-btn-line]');
}

function getTitle(layer: HTMLElement): HTMLElement | null {
  return layer.querySelector<HTMLElement>('.split-chars');
}

function getStage(layer: HTMLElement): HTMLElement | null {
  return layer.querySelector<HTMLElement>('[data-split-chars]');
}

function getWords(layer: HTMLElement): HTMLElement[] {
  const stage = getStage(layer);
  if (!stage) {
    return [];
  }

  return Array.from(stage.querySelectorAll<HTMLElement>('.split-text__word'));
}

function slideOffset(direction: number, magnitude: number): number {
  return direction >= 0 ? magnitude : -magnitude;
}

function titleTargets(layer: HTMLElement, chars: HTMLElement[]): HTMLElement[] {
  return [...getWords(layer), ...chars];
}

function resetTitleMotion(layer: HTMLElement, chars: HTMLElement[], direction = 1): void {
  const words = getWords(layer);
  const wordX = slideOffset(direction, WORD_TRAVEL);

  gsap.set(words, { x: wordX, opacity: 0 });

  words.forEach((word) => {
    const wordChars = Array.from(word.querySelectorAll<HTMLElement>('.split-chars__char-inner'));
    const { lagPercent } = getWordCharMotion(wordChars.length);

    gsap.set(wordChars, {
      xPercent: slideOffset(direction, lagPercent),
      opacity: 0,
    });
  });

  gsap.set(chars, { opacity: 0 });
}

function animateTitleIn(
  layer: HTMLElement,
  chars: HTMLElement[],
  direction: number,
  delay: number,
): number {
  const words = getWords(layer);
  const wordFrom = slideOffset(direction, WORD_TRAVEL);
  const tl = gsap.timeline({ delay });

  words.forEach((word, wordIndex) => {
    const wordChars = Array.from(word.querySelectorAll<HTMLElement>('.split-chars__char-inner'));
    const { lagPercent, stagger } = getWordCharMotion(wordChars.length);
    const charFrom = slideOffset(direction, lagPercent);
    const at = wordIndex * WORD_STAGGER;

    tl.fromTo(
      word,
      { x: wordFrom, opacity: 0 },
      { x: 0, opacity: 1, duration: 1.05, ease: 'power3.out' },
      at,
    );

    tl.fromTo(
      wordChars,
      { xPercent: charFrom, opacity: 0 },
      {
        xPercent: 0,
        opacity: 1,
        duration: 0.98,
        stagger,
        ease: 'power3.out',
      },
      at,
    );
  });

  return tl.duration();
}

function animateTitleOut(layer: HTMLElement, chars: HTMLElement[], direction: number): void {
  const words = getWords(layer);
  const wordTo = slideOffset(direction, -WORD_TRAVEL);
  const tl = gsap.timeline();

  words.forEach((word, wordIndex) => {
    const wordChars = Array.from(word.querySelectorAll<HTMLElement>('.split-chars__char-inner'));
    const { lagPercent, stagger } = getWordCharMotion(wordChars.length);
    const charTo = slideOffset(direction, -lagPercent);
    const at = wordIndex * (WORD_STAGGER * 0.5);

    tl.to(word, { x: wordTo, opacity: 0, duration: 0.62, ease: 'power3.in' }, at);

    tl.to(
      wordChars,
      {
        xPercent: charTo,
        opacity: 0,
        duration: 0.55,
        stagger: stagger * 0.55,
        ease: 'power3.in',
      },
      at,
    );
  });
}

function resetLayer(layer: HTMLElement, chars: HTMLElement[]): void {
  const cta = getCta(layer);
  const line = getBtnLine(layer);
  const title = getTitle(layer);

  layer.classList.remove('is-active', 'is-exiting');
  layer.setAttribute('aria-hidden', 'true');

  if (title) {
    gsap.set(title, { visibility: 'hidden', opacity: 0 });
  }

  if (prefersReducedMotion) {
    gsap.set(titleTargets(layer, chars), { clearProps: 'all' });
    if (cta) {
      gsap.set(cta, { clearProps: 'all' });
    }
    if (line) {
      gsap.set(line, { clearProps: 'all' });
    }
    return;
  }

  resetTitleMotion(layer, chars, 1);
  if (cta) {
    gsap.set(cta, { opacity: 0 });
  }
  if (line) {
    gsap.set(line, { scaleX: 0 });
  }
}

function animateLayerIn(
  layer: HTMLElement,
  layerChars: Map<HTMLElement, HTMLElement[]>,
  direction = 0,
): void {
  const chars = layerChars.get(layer) ?? [];
  const cta = getCta(layer);
  const line = getBtnLine(layer);
  const title = getTitle(layer);
  const enterDelay = direction === 0 ? 0.08 : TITLE_ENTER_DELAY;

  layer.classList.add('is-active');
  layer.classList.remove('is-exiting');
  layer.removeAttribute('aria-hidden');

  gsap.killTweensOf([...titleTargets(layer, chars), cta, line, title].filter(Boolean));

  if (title) {
    gsap.set(title, { visibility: 'visible', opacity: 1 });
  }

  if (prefersReducedMotion || chars.length === 0) {
    gsap.set(titleTargets(layer, chars), { clearProps: 'all' });
    if (cta) {
      gsap.set(cta, { clearProps: 'all' });
    }
    if (line) {
      gsap.set(line, { clearProps: 'all' });
    }
    return;
  }

  const titleDuration = animateTitleIn(layer, chars, direction, enterDelay);

  if (cta) {
    gsap.fromTo(
      cta,
      { opacity: 0 },
      {
        opacity: 1,
        duration: 0.65,
        ease: 'power2.out',
        delay: enterDelay + titleDuration * 0.55,
      },
    );
  }

  if (line) {
    gsap.set(line, { scaleX: 0 });
    gsap.to(line, {
      scaleX: 1,
      duration: 0.6,
      ease: 'power2.out',
      delay: enterDelay + titleDuration * 0.65,
    });
  }
}

function animateLayerOut(
  layer: HTMLElement,
  layerChars: Map<HTMLElement, HTMLElement[]>,
  direction = 1,
): void {
  const chars = layerChars.get(layer) ?? [];
  const cta = getCta(layer);
  const line = getBtnLine(layer);
  if (prefersReducedMotion) {
    resetLayer(layer, chars);
    return;
  }

  layer.classList.remove('is-active');
  layer.classList.add('is-exiting');

  gsap.killTweensOf([...titleTargets(layer, chars), cta, line].filter(Boolean));

  animateTitleOut(layer, chars, direction);

  if (cta) {
    gsap.to(cta, {
      opacity: 0,
      duration: 0.38,
      ease: 'power2.in',
    });
  }

  if (line) {
    gsap.to(line, {
      scaleX: 0,
      duration: 0.32,
      ease: 'power2.in',
    });
  }
}

function updateArrows(swiper: SwiperInstance): void {
  const root = document.querySelector<HTMLElement>('[data-home]');
  const prevBtn = root?.querySelector<HTMLElement>('[data-home-prev]');
  const nextBtn = root?.querySelector<HTMLElement>('[data-home-next]');

  prevBtn?.toggleAttribute('disabled', swiper.isBeginning);
  nextBtn?.toggleAttribute('disabled', swiper.isEnd);
  prevBtn?.classList.toggle('is-disabled', swiper.isBeginning);
  nextBtn?.classList.toggle('is-disabled', swiper.isEnd);
}

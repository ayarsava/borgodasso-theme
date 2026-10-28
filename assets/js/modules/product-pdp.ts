/**
 * Single product PDP — info side panel + variation price on add button.
 */

const OPEN_CLASS = 'is-open';
const CLOSING_CLASS = 'is-closing';
const BODY_OPEN_CLASS = 'is-product-info-open';
const PANEL_CLOSE_MS = 950;

export function initProductPdp(): void {
  const root = document.querySelector<HTMLElement>('[data-product-pdp]');
  if (!root) {
    return;
  }

  initInfoDrawer(root);
  initVariationPrice(root);
  initCustomOptionSelects(root);
  initSummaryFeaturedAlign(root);
}

type JQueryLike = {
  val(value: string): JQueryLike;
  trigger(event: string): JQueryLike;
  on(events: string, handler: (...args: unknown[]) => void): JQueryLike;
};

function getJQuery(): ((el: Element) => JQueryLike) | undefined {
  return (window as Window & { jQuery?: (el: Element) => JQueryLike }).jQuery;
}

function initCustomOptionSelects(root: HTMLElement): void {
  const options = root.querySelectorAll<HTMLElement>('[data-product-option]');
  if (options.length === 0) {
    return;
  }

  const closeAll = (except?: HTMLElement): void => {
    options.forEach((option) => {
      if (except && option === except) {
        return;
      }
      setOptionOpen(option, false);
    });
  };

  const syncAll = (): void => {
    options.forEach((option) => {
      const select = option.querySelector<HTMLSelectElement>('select.product-pdp__option-select');
      const valueEl = option.querySelector<HTMLElement>('[data-product-option-value]');
      const choices = option.querySelectorAll<HTMLButtonElement>('[data-product-option-choice]');
      if (!select || !valueEl) {
        return;
      }
      ensureFirstOptionSelected(select);
      syncOptionFromSelect(select, valueEl, choices);
    });
  };

  options.forEach((option) => {
    const toggle = option.querySelector<HTMLButtonElement>('[data-product-option-toggle]');
    const select = option.querySelector<HTMLSelectElement>('select.product-pdp__option-select');
    const valueEl = option.querySelector<HTMLElement>('[data-product-option-value]');
    const choices = option.querySelectorAll<HTMLButtonElement>('[data-product-option-choice]');

    if (!select || !valueEl) {
      return;
    }

    // Keep native select for WooCommerce variation JS; hide from UI/AT.
    // Do not remove WC's empty placeholder option — variation JS uses option:first as
    // the placeholder when data-show_option_none="no".
    select.classList.add('screen-reader-text');
    select.setAttribute('tabindex', '-1');
    select.setAttribute('aria-hidden', 'true');

    const syncFromSelect = (): void => {
      syncOptionFromSelect(select, valueEl, choices);
    };

    // Pre-select the first available option when none is chosen yet.
    ensureFirstOptionSelected(select);

    syncFromSelect();
    select.addEventListener('change', syncFromSelect);

    // Static single-value rows have no dropdown UI.
    if (!toggle) {
      return;
    }

    toggle.addEventListener('click', () => {
      const willOpen = !option.classList.contains('is-open');
      closeAll(option);
      setOptionOpen(option, willOpen);
    });

    choices.forEach((choice) => {
      choice.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        const nextValue = choice.dataset.value ?? '';
        const $ = getJQuery();

        if ($ && select.value !== nextValue) {
          $(select).val(nextValue).trigger('change');
        } else if (select.value !== nextValue) {
          select.value = nextValue;
          select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        syncFromSelect();
        setOptionOpen(option, false);
        toggle.focus();
      });
    });
  });

  // WC rewrites <select> options on attribute updates — re-sync the custom UI.
  const form = root.querySelector<HTMLFormElement>('form.variations_form');
  const $ = getJQuery();
  if (form && $) {
    $(form).on('woocommerce_update_variation_values found_variation reset_data', syncAll);
  }

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Node)) {
      return;
    }
    const inside = Array.from(options).some((option) => option.contains(target));
    if (!inside) {
      closeAll();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeAll();
    }
  });
}

function ensureFirstOptionSelected(select: HTMLSelectElement): void {
  if (select.value !== '') {
    return;
  }

  const first = Array.from(select.options).find((option) => option.value !== '');
  if (!first) {
    return;
  }

  const $ = getJQuery();
  if ($) {
    $(select).val(first.value).trigger('change');
    return;
  }

  select.value = first.value;
  select.dispatchEvent(new Event('change', { bubbles: true }));
}

function visibleSelectValues(select: HTMLSelectElement): Set<string> {
  return new Set(
    Array.from(select.options)
      .map((option) => option.value)
      .filter((value) => value !== ''),
  );
}

function syncOptionFromSelect(
  select: HTMLSelectElement,
  valueEl: HTMLElement,
  choices: NodeListOf<HTMLButtonElement>,
): void {
  const selected = select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;
  const label = selected && selected.value !== '' ? (selected.textContent?.trim() ?? '') : '';
  valueEl.textContent = label;

  // WooCommerce removes attribute options that belong only to variations with no price.
  const available = visibleSelectValues(select);

  choices.forEach((choice) => {
    const value = choice.dataset.value ?? '';
    const item = choice.closest<HTMLElement>('.product-pdp__option-item');
    const visible = available.has(value);
    if (item) {
      item.hidden = !visible;
    }

    const isSelected = visible && value === select.value && select.value !== '';
    choice.classList.toggle('is-selected', isSelected);
    if (isSelected) {
      choice.setAttribute('aria-current', 'true');
    } else {
      choice.removeAttribute('aria-current');
    }
  });
}

function setOptionOpen(option: HTMLElement, open: boolean): void {
  const toggle = option.querySelector<HTMLButtonElement>('[data-product-option-toggle]');
  const list = option.querySelector<HTMLElement>('[data-product-option-list]');
  const icon = option.querySelector<HTMLElement>('[data-product-option-icon]');

  option.classList.toggle('is-open', open);
  toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');

  if (list) {
    list.hidden = !open;
  }

  if (icon) {
    icon.textContent = open ? '−' : '+';
  }
}

function getScrollbarWidth(): number {
  return window.innerWidth - document.documentElement.clientWidth;
}

function lockScroll(): void {
  const scrollbarWidth = getScrollbarWidth();
  if (scrollbarWidth > 0) {
    document.documentElement.style.setProperty('--scrollbar-width', `${scrollbarWidth}px`);
  }
  document.body.classList.add(BODY_OPEN_CLASS);
}

function unlockScroll(): void {
  document.body.classList.remove(BODY_OPEN_CLASS);
  document.documentElement.style.removeProperty('--scrollbar-width');
}

function initInfoDrawer(root: HTMLElement): void {
  const drawer = document.querySelector<HTMLElement>('[data-product-info-drawer]');
  const panel = drawer?.querySelector<HTMLElement>('[data-product-info-panel]') ?? null;
  const openers = root.querySelectorAll<HTMLButtonElement>('[data-product-info-open]');
  const closers = drawer?.querySelectorAll<HTMLElement>('[data-product-info-close]') ?? [];

  if (!drawer || !panel || openers.length === 0) {
    return;
  }

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let closeTimeout = 0;
  let lastOpener: HTMLButtonElement | null = null;

  const isOpen = (): boolean => drawer.classList.contains(OPEN_CLASS);
  const isClosing = (): boolean => drawer.classList.contains(CLOSING_CLASS);

  const setExpanded = (expanded: boolean): void => {
    openers.forEach((btn) => btn.setAttribute('aria-expanded', expanded ? 'true' : 'false'));
  };

  const scrollToSection = (sectionId: string | null): void => {
    if (!sectionId) {
      return;
    }
    const target = drawer.querySelector<HTMLElement>(`[data-product-info-section="${sectionId}"]`);
    if (!target) {
      return;
    }
    // Defer until panel is visible.
    window.requestAnimationFrame(() => {
      target.scrollIntoView({ block: 'start', behavior: prefersReducedMotion ? 'auto' : 'smooth' });
    });
  };

  const finalizeClose = (): void => {
    window.clearTimeout(closeTimeout);
    unlockScroll();
    drawer.classList.remove(CLOSING_CLASS);
    drawer.setAttribute('hidden', '');
    setExpanded(false);
    lastOpener?.focus();
    lastOpener = null;
  };

  const open = (opener: HTMLButtonElement): void => {
    window.clearTimeout(closeTimeout);
    lastOpener = opener;
    drawer.classList.remove(CLOSING_CLASS);
    drawer.removeAttribute('hidden');

    const section = opener.dataset.productInfoSection ?? null;

    if (prefersReducedMotion) {
      drawer.classList.add(OPEN_CLASS);
      setExpanded(true);
      lockScroll();
      panel.focus({ preventScroll: true });
      scrollToSection(section);
      return;
    }

    void drawer.offsetWidth;
    drawer.classList.add(OPEN_CLASS);
    setExpanded(true);
    lockScroll();
    panel.focus({ preventScroll: true });
    scrollToSection(section);
  };

  const close = (): void => {
    if (!isOpen() || isClosing()) {
      return;
    }

    setExpanded(false);
    drawer.classList.remove(OPEN_CLASS);

    if (prefersReducedMotion) {
      finalizeClose();
      return;
    }

    drawer.classList.add(CLOSING_CLASS);

    const onPanelAnimationEnd = (event: AnimationEvent): void => {
      if (event.target !== panel || event.animationName !== 'product-info-panel-close') {
        return;
      }
      panel.removeEventListener('animationend', onPanelAnimationEnd);
      finalizeClose();
    };

    panel.addEventListener('animationend', onPanelAnimationEnd);
    closeTimeout = window.setTimeout(() => {
      panel.removeEventListener('animationend', onPanelAnimationEnd);
      finalizeClose();
    }, PANEL_CLOSE_MS + 50);
  };

  openers.forEach((opener) => {
    opener.addEventListener('click', () => {
      if (isOpen() && !isClosing()) {
        const section = opener.dataset.productInfoSection ?? null;
        scrollToSection(section);
        return;
      }
      open(opener);
    });
  });

  closers.forEach((closer) => {
    closer.addEventListener('click', () => close());
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen()) {
      event.preventDefault();
      close();
    }
  });
}

function initVariationPrice(root: HTMLElement): void {
  const form = root.querySelector<HTMLFormElement>('form.variations_form');
  const button = root.querySelector<HTMLButtonElement>('[data-product-add]');
  const priceEl = root.querySelector<HTMLElement>('[data-add-price]');
  const sepEl = button?.querySelector<HTMLElement>('.product-pdp__add-sep') ?? null;
  const saleLine = root.querySelector<HTMLElement>('[data-sale-line]');
  const salePrice = root.querySelector<HTMLElement>('[data-sale-price]');
  const saleFormat = root.querySelector<HTMLElement>('[data-sale-format]');
  const saleSep = root.querySelector<HTMLElement>('[data-sale-sep]');
  const defaultFormat = saleLine?.dataset.defaultFormat ?? '';
  const defaultPriceHtml = saleLine?.dataset.defaultPrice ?? '';

  if (!form || !button || !priceEl) {
    return;
  }

  const decodeEntities = (value: string): string => {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = value;
    return textarea.value;
  };

  const setSaleFormat = (label: string): void => {
    if (!saleFormat || !saleSep) {
      return;
    }

    const next = label.trim();
    saleFormat.textContent = next;
    saleFormat.hidden = next === '';
    saleSep.hidden = next === '';
  };

  const setPrice = (html: string | undefined): void => {
    const raw = html ? decodeEntities(html.replace(/<[^>]+>/g, '').trim()) : '';
    if (!raw) {
      priceEl.textContent = '';
      priceEl.hidden = true;
      if (sepEl) {
        sepEl.hidden = true;
      }
      return;
    }

    priceEl.textContent = raw;
    priceEl.hidden = false;
    if (sepEl) {
      sepEl.hidden = false;
    }
  };

  const bind = (): boolean => {
    const win = window as Window & {
      jQuery?: (el: Element) => {
        on: (events: string, handler: (...args: unknown[]) => void) => void;
      };
    };
    const $ = win.jQuery;
    if (!$) {
      return false;
    }

    const $form = $(form);

    $form.on('found_variation', (_event: unknown, variation: unknown) => {
      const data = variation as { price_html?: string; sale_format_label?: string };

      if (typeof data.sale_format_label === 'string' && data.sale_format_label.trim() !== '') {
        setSaleFormat(data.sale_format_label);
      } else {
        setSaleFormat(defaultFormat);
      }

      // WC may send an empty price_html for some catalogs — don't wipe the button price.
      if (data.price_html) {
        setPrice(data.price_html);
        if (salePrice) {
          salePrice.innerHTML = data.price_html;
        }
        return;
      }

      const fallback = salePrice?.textContent?.trim() ?? '';
      if (fallback) {
        setPrice(fallback);
      }
    });

    $form.on('hide_variation reset_data', () => {
      const variationId = form.querySelector<HTMLInputElement>('input.variation_id')?.value;
      if (!variationId || variationId === '0') {
        setSaleFormat(defaultFormat);
        if (salePrice && defaultPriceHtml) {
          salePrice.innerHTML = defaultPriceHtml;
        }
        const currentSale = salePrice?.textContent?.trim() ?? '';
        if (currentSale) {
          setPrice(currentSale);
        }
      }
    });

    return true;
  };

  if (bind()) {
    return;
  }

  let attempts = 0;
  const timer = window.setInterval(() => {
    attempts += 1;
    if (bind() || attempts > 40) {
      window.clearInterval(timer);
    }
  }, 50);
}

const PDP_DESKTOP_QUERY = '(min-width: 1024px)';

/**
 * Center the summary on the featured image. Gallery frames stay below that
 * image, so they must not stretch the alignment box. A summary taller than
 * the featured image stays top-aligned with it.
 */
function initSummaryFeaturedAlign(root: HTMLElement): void {
  const featured = root.querySelector<HTMLElement>('.product-pdp__featured');
  const summary = root.querySelector<HTMLElement>('.product-pdp__summary');
  if (!featured || !summary) {
    return;
  }

  const desktop = window.matchMedia(PDP_DESKTOP_QUERY);
  let frame = 0;

  const apply = (): void => {
    if (!desktop.matches) {
      summary.style.removeProperty('--pdp-summary-offset');
      return;
    }

    const featuredHeight = featured.getBoundingClientRect().height;
    if (featuredHeight <= 0) {
      return;
    }

    const summaryHeight = summary.getBoundingClientRect().height;
    const offset = Math.max(0, (featuredHeight - summaryHeight) / 2);
    const next = `${offset}px`;
    if (summary.style.getPropertyValue('--pdp-summary-offset') !== next) {
      summary.style.setProperty('--pdp-summary-offset', next);
    }
  };

  const schedule = (): void => {
    window.cancelAnimationFrame(frame);
    frame = window.requestAnimationFrame(apply);
  };

  schedule();

  if (typeof ResizeObserver !== 'undefined') {
    const observer = new ResizeObserver(schedule);
    observer.observe(featured);
    observer.observe(summary);
  }

  desktop.addEventListener('change', schedule);

  featured.querySelectorAll('img').forEach((img) => {
    if (!img.complete) {
      img.addEventListener('load', schedule, { once: true });
    }
  });
}

/** Site menu drawer — open/close, image switching, scroll lock. */

const OPEN_CLASS = 'is-open';
const CLOSING_CLASS = 'is-closing';
const SETTLED_CLASS = 'is-panel-settled';
const MENU_OPEN_CLASS = 'is-menu-open';
const BODY_OPEN_CLASS = 'is-nav-open';
const ACTIVE_IMAGE_CLASS = 'is-active';
const PANEL_OPEN_MS = 1150;
const PANEL_CLOSE_MS = 950;

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

function setImageActive(drawer: HTMLElement, index: string): void {
  drawer.querySelectorAll<HTMLElement>('.site-nav__media-slide').forEach((slide) => {
    slide.classList.toggle(ACTIVE_IMAGE_CLASS, slide.dataset.menuIndex === index);
  });
}

function getDefaultImageIndex(drawer: HTMLElement): string | null {
  const activeLink = drawer.querySelector<HTMLElement>('.site-nav__link.is-active');
  if (activeLink?.dataset.menuIndex) {
    return activeLink.dataset.menuIndex;
  }

  const firstSlide = drawer.querySelector<HTMLElement>('.site-nav__media-slide');
  return firstSlide?.dataset.menuIndex ?? null;
}

export function initNav(): void {
  const header = document.querySelector<HTMLElement>('[data-site-header]');
  const menuToggle = document.querySelector<HTMLElement>('[data-menu-toggle]');
  const navDrawer = document.querySelector<HTMLElement>('[data-nav-drawer]');
  const navPanel = navDrawer?.querySelector<HTMLElement>('[data-nav-panel]') ?? null;

  if (!menuToggle || !navDrawer) {
    return;
  }

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let closeTimeout = 0;

  const isOpen = (): boolean => navDrawer.classList.contains(OPEN_CLASS);
  const isClosing = (): boolean => navDrawer.classList.contains(CLOSING_CLASS);

  const finishOpen = (): void => {
    header?.classList.add(MENU_OPEN_CLASS);
    lockScroll();
  };

  const revealDefaultImage = (): void => {
    navDrawer.classList.add(SETTLED_CLASS);

    const defaultIndex = getDefaultImageIndex(navDrawer);
    if (defaultIndex) {
      setImageActive(navDrawer, defaultIndex);
    }
  };

  const finalizeClose = (): void => {
    window.clearTimeout(closeTimeout);
    header?.classList.remove(MENU_OPEN_CLASS);
    unlockScroll();
    navDrawer.classList.remove(CLOSING_CLASS, SETTLED_CLASS);
    navDrawer.setAttribute('hidden', '');
  };

  const open = (): void => {
    window.clearTimeout(closeTimeout);
    navDrawer.classList.remove(CLOSING_CLASS, SETTLED_CLASS);
    navDrawer.removeAttribute('hidden');

    if (prefersReducedMotion) {
      navDrawer.classList.add(OPEN_CLASS);
      menuToggle.setAttribute('aria-expanded', 'true');
      finishOpen();
      revealDefaultImage();
      return;
    }

    // Force reflow so the enter transition runs after display change.
    void navDrawer.offsetWidth;
    navDrawer.classList.add(OPEN_CLASS);
    menuToggle.setAttribute('aria-expanded', 'true');
    finishOpen();

    const onPanelOpenEnd = (event: AnimationEvent): void => {
      if (event.target !== navPanel || event.animationName !== 'nav-panel-open') {
        return;
      }

      navPanel?.removeEventListener('animationend', onPanelOpenEnd);
      revealDefaultImage();
    };

    if (navPanel) {
      navPanel.addEventListener('animationend', onPanelOpenEnd);
    }

    window.setTimeout(() => {
      if (
        navDrawer.classList.contains(OPEN_CLASS) &&
        !navDrawer.classList.contains(SETTLED_CLASS)
      ) {
        navPanel?.removeEventListener('animationend', onPanelOpenEnd);
        revealDefaultImage();
      }
    }, PANEL_OPEN_MS + 50);
  };

  const close = (): void => {
    if (isClosing()) {
      return;
    }

    menuToggle.setAttribute('aria-expanded', 'false');
    navDrawer.classList.remove(OPEN_CLASS, SETTLED_CLASS);

    if (prefersReducedMotion) {
      finalizeClose();
      return;
    }

    navDrawer.classList.add(CLOSING_CLASS);

    const onPanelAnimationEnd = (event: AnimationEvent): void => {
      if (event.target !== navPanel || event.animationName !== 'nav-panel-close') {
        return;
      }

      navPanel?.removeEventListener('animationend', onPanelAnimationEnd);
      finalizeClose();
    };

    if (navPanel) {
      navPanel.addEventListener('animationend', onPanelAnimationEnd);
    }

    closeTimeout = window.setTimeout(() => {
      navPanel?.removeEventListener('animationend', onPanelAnimationEnd);
      finalizeClose();
    }, PANEL_CLOSE_MS + 50);
  };

  menuToggle.addEventListener('click', () => {
    if (isOpen()) {
      close();
    } else {
      open();
    }
  });

  navDrawer.querySelectorAll<HTMLElement>('.site-nav__link').forEach((link) => {
    const index = link.dataset.menuIndex;
    if (!index) {
      return;
    }

    link.addEventListener('mouseenter', () => setImageActive(navDrawer, index));
    link.addEventListener('focus', () => setImageActive(navDrawer, index));
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen()) {
      close();
      menuToggle.focus();
    }
  });
}

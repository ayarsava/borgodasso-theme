function fullImage(root: HTMLElement): HTMLImageElement | null {
  const nodes = root.querySelectorAll('img');

  for (const node of nodes) {
    if (node.classList.contains('blur-up__preview')) {
      continue;
    }

    return node;
  }

  return null;
}

function bindBlurUp(el: HTMLElement): void {
  if (el.dataset.blurBound === '1') {
    return;
  }

  el.dataset.blurBound = '1';

  const img = fullImage(el);
  const markLoaded = () => {
    el.classList.add('is-loaded');
  };

  if (!img) {
    markLoaded();
    return;
  }

  if (img.complete && img.naturalWidth > 0) {
    markLoaded();
    return;
  }

  img.addEventListener('load', markLoaded, { once: true });
  img.addEventListener('error', markLoaded, { once: true });
}

let watching = false;

/** Reveal full images that are already in the tree, including later clones. */
export function initBlurUp(root: ParentNode = document): void {
  root.querySelectorAll<HTMLElement>('[data-blur-up]').forEach(bindBlurUp);

  if (watching || !('MutationObserver' in window) || !(root instanceof Node)) {
    return;
  }

  watching = true;

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (!(node instanceof HTMLElement)) {
          return;
        }

        if (node.matches('[data-blur-up]')) {
          bindBlurUp(node);
        }

        node.querySelectorAll<HTMLElement>('[data-blur-up]').forEach(bindBlurUp);
      });
    });
  });

  observer.observe(root, { childList: true, subtree: true });
}

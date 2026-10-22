/**
 * Lock one-shot payment CTAs after the first click.
 * Anchors ignore the HTML disabled attribute, so we gate via aria-disabled.
 */

function lockPayLink(link: HTMLAnchorElement): void {
  link.setAttribute('aria-disabled', 'true');
  link.classList.add('is-disabled');
}

export function initPayOnce(): void {
  document.querySelectorAll<HTMLAnchorElement>('a[data-pay-once]').forEach((link) => {
    link.addEventListener('click', (event) => {
      if (link.getAttribute('aria-disabled') === 'true') {
        event.preventDefault();
        event.stopPropagation();
        return;
      }

      lockPayLink(link);
    });
  });
}

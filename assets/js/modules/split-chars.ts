/**
 * Split heading text into word/char spans for GSAP animation.
 *
 * Reads from a translatable source node; builds animation markup in a separate stage
 * so translation plugins (e.g. TranslatePress) only see the full string.
 */
export function splitChars(stage: HTMLElement, textSource: HTMLElement): HTMLElement[] {
  const text = textSource.textContent?.trim() ?? '';
  stage.textContent = '';
  stage.classList.add('split-chars__stage--split');

  const chars: HTMLElement[] = [];
  const words = text.split(/\s+/).filter(Boolean);

  words.forEach((word, wordIndex) => {
    const wordEl = document.createElement('span');
    wordEl.className = 'split-text__word';
    wordEl.setAttribute('data-no-translation', '');

    for (const char of word) {
      const charWrap = document.createElement('span');
      charWrap.className = 'split-chars__char';
      charWrap.setAttribute('data-no-translation', '');

      const charInner = document.createElement('span');
      charInner.className = 'split-chars__char-inner';
      charInner.setAttribute('data-no-translation', '');
      charInner.textContent = char;

      charWrap.appendChild(charInner);
      wordEl.appendChild(charWrap);
      chars.push(charInner);
    }

    stage.appendChild(wordEl);

    if (wordIndex < words.length - 1) {
      const space = document.createElement('span');
      space.className = 'split-text__space';
      space.textContent = '\u00a0';
      space.setAttribute('aria-hidden', 'true');
      space.setAttribute('data-no-translation', '');
      stage.appendChild(space);
    }
  });

  return chars;
}

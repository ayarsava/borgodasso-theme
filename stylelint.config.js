/** @type {import('stylelint').Config} */
export default {
  extends: ['stylelint-config-standard-scss'],
  ignoreFiles: ['dist/**', 'node_modules/**'],
  rules: {
    'max-nesting-depth': 3,
    'selector-class-pattern': [
      '^[a-z]([a-z0-9-]+)?(__([a-z0-9]+-?)+)?(--([a-z0-9]+-?)+)?$',
      {
        message: 'Expected class selector to follow BEM (block__element--modifier)',
      },
    ],
    'no-descending-specificity': null,
    'scss/at-rule-no-unknown': true,
    'declaration-block-no-duplicate-properties': true,
    'at-rule-empty-line-before': null,
    'value-keyword-case': [
      'lower',
      {
        ignoreKeywords: ['Helvetica', 'Arial', 'Georgia', 'Times', 'New', 'Roman'],
        ignoreProperties: ['font-family'],
      },
    ],
  },
  overrides: [
    {
      files: [
        'assets/scss/vendors/**/*.scss',
        // Checkout styles target WooCommerce / Stripe markup (not theme BEM).
        'assets/scss/pages/_checkout.scss',
      ],
      rules: {
        // Third-party (WooCommerce) class / id names are not BEM / kebab.
        'selector-class-pattern': null,
        'selector-id-pattern': null,
        'max-nesting-depth': null,
      },
    },
  ],
};

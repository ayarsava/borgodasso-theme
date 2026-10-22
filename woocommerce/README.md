# WooCommerce template overrides

Add template overrides here only when markup must differ from WooCommerce defaults.

Prefer hooks and filters in `inc/woocommerce/` before copying templates.

When overriding a template, note the WooCommerce template version from the source file header.

## Order status stepper (order detail)

The My Account / thank-you order detail shows a 4-step fulfilment stepper:

1. **Ordine accettato**
2. **In preparazione**
3. **Inviato**
4. **Consegnato**

Helpers live in `inc/woocommerce/orders.php`:

| Helper                                           | Role                                   |
| ------------------------------------------------ | -------------------------------------- |
| `borgodasso_order_stepper_steps()`               | Labels for the four nodes              |
| `borgodasso_order_stepper_index( $order )`       | Highest reached index (`-1` = none)    |
| `borgodasso_order_stepper_should_show( $order )` | Hide for cancelled / refunded / failed |
| Custom status `wc-shipped`                       | Admin can mark an order **Inviato**    |

### Current WC status → stepper mapping

| WC status                | Stepper index | Dots filled                        |
| ------------------------ | ------------- | ---------------------------------- |
| `pending`, `on-hold`, …  | `-1`          | none                               |
| `processing`             | `1`           | Ordine accettato + In preparazione |
| `shipped` (`wc-shipped`) | `2`           | … + Inviato                        |
| `completed`              | `3`           | all four                           |

Filters: `borgodasso_order_stepper_index`, `borgodasso_order_stepper_should_show`.

### What’s missing / follow-ups

1. **Separate “Ordine accettato” vs “In preparazione”** — both are currently driven by a single `processing` status (index `1` fills the first two dots together). To light them independently, register something like `wc-preparing` (or store a meta flag) and map:
   - paid / accepted → index `0`
   - preparing → index `1`
2. **Ops workflow** — staff must move orders `Processing → Inviato → Completed` in admin (or via a fulfilment plugin). There is no automatic “shipped” transition yet.
3. **Tracking / carrier** — order admin fields **Corriere**, **Codice tracking**, optional **URL**. Shown on order detail + Ordini list; emailed when status becomes **Inviato** (or when tracking is added on an already-shipped order). Known carriers (BRT, GLS, SDA/Poste, DHL, UPS, FedEx) get a default tracking URL if URL is left blank (`borgodasso_tracking_carrier_url_templates` filter).
4. **Email copy** — customer emails still use default WC status names unless customized.

## Catalog model (launch)

| Type            | WC product | Variations                                      | Sellable unit                         |
| --------------- | ---------- | ----------------------------------------------- | ------------------------------------- |
| Wine            | Variable   | `pa_annata` only                                | Cassa da 6 — price & stock per case   |
| Olive oil       | Variable   | `pa_annata` × `pa_formato` (250 / 500 / 750 ml) | Selected bottle size                  |
| Pasta / legumes | Simple     | none                                            | Pack size via ACF `sale_format_label` |

Global attributes: **Annata** (`pa_annata`), **Formato** (`pa_formato`).

Editorial ACF on products **and variations** (not prices/stock):

- `sale_format_label` — parent: catalog line (e.g. Cassa da 6 bottiglie); variation: PDP line when selected (e.g. Cassa da 6 (6 × 250 ml)). Variation fields appear via `inc/acf.php` WooCommerce hooks (ACF does not show on variations by default).
- Catalog cards with a price range show **A partire da {min}** instead of min–max
- `formato_display` — static wine size (750 ml)
- `schede_tecniche` — repeater (annata + PDF); wine/oil only in the UI
- `varieta` — grape/variety line in the info panel
- `ingredienti` — WYSIWYG for the info panel
- `valori_nutrizionali_title` + `valori_nutrizionali_rows` — nutrition table

Future combos: keep SKUs atomic; use Grouped products / bundles + `inc/woocommerce/packages.php`.

## My Account — Accedi / Registrati

Logged-out My Account uses a single-column Accedi form (`woocommerce/myaccount/form-login.php`).

| View     | URL                                 |
| -------- | ----------------------------------- |
| Login    | My Account page permalink           |
| Register | `?action=register` on the same page |

Helpers live in `inc/woocommerce/account.php`:

- Registration is force-enabled via `pre_option_woocommerce_enable_myaccount_registration`
- Username is generated from email; customers set their own password on Registrati
- `borgodasso_account_login_url()` / `borgodasso_account_register_url()`

### Google login (stub)

The Accedi screen always shows **Accedi con Google**. Until a provider is wired, the button is disabled.

To activate it, return an absolute OAuth URL from the filter:

```php
add_filter( 'borgodasso_google_login_url', static function (): string {
	// e.g. Nextend Social Login / WP Social Login / custom Google OAuth start URL
	return 'https://example.com/wp-login.php?loginSocial=google';
} );
```

Suggested later steps:

1. Create a Google Cloud OAuth 2.0 Client (Web) with authorized redirect URIs for the chosen plugin or custom callback.
2. Install a social-login plugin **or** implement a small OAuth start/callback in `inc/woocommerce/` that creates/logs in a WP user and redirects to My Account.
3. Point `borgodasso_google_login_url` at that start URL (keep client secrets in env / WP options, never in the theme).

## Legal drinking age gate

Before shop / products / cart / checkout, visitors must confirm age on a page using the **Legal Drinking Age Page** template.

1. Create a WordPress page (e.g. slug `eta-legale`).
2. Template: **Legal Drinking Age Page**.
3. Fill ACF: background image(s), question, SÌ / NO labels.
4. Publish — the theme finds that page automatically.

Behaviour:

- Unverified visit to any WooCommerce URL → redirect to the age-gate page (`?redirect_to=` original URL).
- **SÌ** → sets cookie `borgodasso_age_verified` (1 year) → continues to shop / original URL.
- **NO** → homepage.
- Cookie present → WooCommerce loads normally (gate shown once per browser).

## Stripe webhooks (local vs live)

Stripe charging a card and WooCommerce marking the order **Processing** are separate. Woo updates the order when it receives a **webhook** (`payment_intent.succeeded`, etc.).

| Environment                         | Webhooks                                                                                                                                                                                                                                                   |
| ----------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Local ddev (`*.ddev.site`)          | Stripe **cannot** reach your machine. Use **Test mode** + optional [Stripe CLI](https://stripe.com/docs/stripe-cli) (`stripe listen --forward-to https://borgodasso.ddev.site/?wc-api=wc_stripe`). Do **not** rely on live charges to update local orders. |
| Staging / production (public HTTPS) | Live webhooks work. Endpoint shape: `https://YOUR-DOMAIN/?wc-api=wc_stripe`                                                                                                                                                                                |

### Do this now (before go-live)

1. Keep developing in **Stripe Test mode** on ddev.
2. Finish checkout / order-pay / thank-you UX against test cards.
3. Note which Stripe account (live) the shop will use; avoid mixing test and live keys in the same env once live.
4. Manually reconcile any accidental live charges made against ddev (mark those WC orders Processing yourself if the money landed in Stripe).

### Do this on the public site (go-live day)

1. Deploy WP with the real domain + valid HTTPS.
2. WooCommerce → Settings → Payments → Stripe → connect / switch to **Live**.
3. Account details → **Configure connection** → **Live** tab → Webhooks = **Configured** (use **Reconfigure webhooks** if not).
4. Place one small live smoke-test order; confirm order leaves **Pending payment** → **Processing**.
5. In Stripe Dashboard → Developers → Webhooks, confirm recent deliveries return success (2xx).

### Why orders stay Pending / get Cancelled automatically

Stripe success ≠ WooCommerce status update. If the live webhook never reaches the site, the order stays **Pending payment** even though Stripe shows the charge as paid.

WooCommerce then runs **Hold stock** cleanup:

- Path: **WooCommerce → Settings → Products → Inventory**
- **Hold stock (minutes)** — currently **60** on this project (with stock management on)
- After that many minutes, unpaid **Pending payment** orders are **Cancelled** and held stock is released
- Leave the field blank to disable auto-cancel

So a paid-in-Stripe but still-Pending order can later flip to **Cancelled** when hold-stock runs. That is core WooCommerce behaviour, not theme code.

If that happens: confirm the PaymentIntent in Stripe, fix/reconfigure live webhooks, then manually set the order to **Processing** (and refund/reconcile only if you truly should not keep the order).

Docs: [Stripe webhooks (WooCommerce)](https://woocommerce.com/document/stripe/setup-and-configuration/stripe-webhooks/), [Hold stock / inventory](https://woocommerce.com/document/configuring-woocommerce-settings/products/).

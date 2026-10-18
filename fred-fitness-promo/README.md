# Fred Fitness — Santa Monica promo landing page (demo)

`fred-promo-landing.html` is one self-contained fragment (markup + scoped CSS + a small JS block).
`preview.html` is the same fragment wrapped in a bare document so it opens directly in a browser
(regenerate it with `./build-preview.sh` after editing the fragment).

## Add it to WordPress (no template, plugin or child theme)

Site stack (detected via Themesinfo, confirm in wp-admin): Hello Elementor theme + Elementor / Elementor Pro.

1. Pages → Add New → title it (e.g. "Grand Opening Offer") → Edit with Elementor.
2. Page Settings (gear icon) → Page Layout → **Elementor Canvas**. This hides the site header/footer on this page only.
3. Add a **Container**: Content Width = **Full Width**, padding 0, gap 0.
4. Drag an **HTML** widget into it and paste the full contents of `fred-promo-landing.html`.
5. Publish. In Rank Math set the SEO title/description (and noindex if the page should stay out of search).
6. If a cache/minify plugin is active, purge the cache and exclude this page from CSS/JS minify/delay if anything looks off.

## Edit points

| What | Where |
|---|---|
| Offer end date/timezone | `data-end` / `data-tz` on `<div class="ffp" ...>`. The countdown and the "Ends …" / "Valid Oct 13–…" labels follow it. Currently a demo date. |
| CTA links | `https://fredfitness.com/membership/` (Join Now, Claim My Free Month) and `https://fredfitness.com/assessment/` (assessment buttons). Clean URLs; the `_gl`/`_ga` params copied from the live site are session-specific and were left out. |
| Photos | Each `.ffp-ph` block is a placeholder. Add `<img src="…" alt="…">` inside it and delete its `<figcaption class="ffp-tag">`. For the hero, do this in `.ffp-hero__bg`. |
| Fine print | The bracketed line in the offer band. |
| Class schedule | The `<ul class="ffp-sched">` list. |

## Isolation

- Every selector starts with `.ffp` and uses class selectors, so Hello Elementor / Elementor Kit rules on `h1`, `a`, `button`, etc. do not apply.
- JS is one IIFE and only touches elements inside `.ffp[data-end]`.
- Fonts: Montserrat (headings, numbers) and Poppins (body), loaded by the `<link>` at the top of the fragment.

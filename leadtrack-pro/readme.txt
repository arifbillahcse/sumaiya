=== LeadTrack Pro ===
Contributors: leadtrackpro
Tags: lead tracking, meta pixel, facebook pixel, elementor, anti-spam, thank you page
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Tracks leads via Meta Pixel, integrates with Elementor forms, protects against spam, and provides a customisable Thank You page.

== Description ==

LeadTrack Pro is a complete lead-tracking solution for WordPress. It connects your Elementor forms to Meta (Facebook) Pixel, automatically redirects visitors to a branded Thank You page after form submission, and guards your forms against bots and spam with a configurable honeypot + optional Google reCAPTCHA layer.

**Key Features**

* **Meta Pixel Integration** — Automatically fires `fbq('track', 'Lead')` on the Thank You page. Deduplication via `sessionStorage` and `localStorage` ensures each lead is counted only once.
* **Elementor Form Redirect** — Listens for the Elementor Pro form-success event and redirects seamlessly to the Thank You page without reloading the form page.
* **Anti-Spam Protection** — Invisible honeypot field (CSS hidden) plus a timing check catches bots before they hit your CRM. Optional Google reCAPTCHA v2 support.
* **Thank You Page** — Auto-created on activation, powered by the `[leadtrack_thankyou]` shortcode, with a polished, mobile-responsive design.
* **Settings Dashboard** — Clean, tabbed admin UI with a built-in Setup Guide so you can be up and running in minutes.

== Installation ==

1. Upload the `leadtrack-pro` folder to the `/wp-content/plugins/` directory, or install it via **Plugins > Add New**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings > LeadTrack Pro** and enter your Meta Pixel ID.
4. (Optional) Enable anti-spam protection and enter your Google reCAPTCHA keys.
5. Done! The Thank You page is created automatically at `/thank-you/`.

== Frequently Asked Questions ==

= Where do I find my Meta Pixel ID? =
Log into your Meta Business Manager, navigate to **Events Manager**, and copy the numeric Pixel ID shown beneath your pixel's name.

= Does this work without Elementor? =
The Meta Pixel base code and Thank You page work independently. The Elementor redirect feature requires Elementor (free) or Elementor Pro.

= What happens to the Thank You page if I deactivate the plugin? =
The page remains on your site. It is only removed when you **delete** (uninstall) the plugin.

= Is the anti-spam feature compatible with all form plugins? =
The honeypot and timing check are injected via JavaScript into standard HTML forms. Elementor forms have dedicated integration. Other form plugins may need custom CSS to hide the honeypot field from the browser's autofill.

= Does the Lead event fire more than once per visitor? =
No. LeadTrack Pro uses both `sessionStorage` and `localStorage` to track whether the event has already been fired for a given pixel ID, preventing duplicate conversions.

== Screenshots ==

1. General Settings tab — configure the Thank You page and toggle features.
2. Pixel Tracking tab — enter your Meta Pixel ID.
3. Anti-Spam tab — honeypot and reCAPTCHA settings.
4. Setup Guide tab — step-by-step instructions.
5. The Thank You page displayed to visitors after form submission.

== Changelog ==

= 1.0.0 =
* Initial release.
* Meta Pixel base code with automatic Lead event on Thank You page.
* Elementor form success redirect.
* Honeypot + optional Google reCAPTCHA anti-spam.
* Auto-created Thank You page with `[leadtrack_thankyou]` shortcode.
* Tabbed admin settings UI with Setup Guide.

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade needed.

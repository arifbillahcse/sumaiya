# MediaForge Summit 2026 — Website

A fully static, multi-page conference website for **MediaForge Summit 2026**, the world's premier annual conference at the intersection of technology and media. Built with plain HTML, CSS, and vanilla JavaScript — no frameworks or build tools required.

---

## Pages

| File | Page | Description |
|------|------|-------------|
| `index.html` | Home | Hero, stats, countdown timer, why attend, conference tracks, featured speakers, testimonials, conference experience, venue, sponsors strip, past editions, newsletter sign-up, CTA |
| `speakers.html` | Speakers | Filterable speaker grid (All / Keynote / Panel / Workshop) with 6 featured profiles |
| `schedule.html` | Schedule | 3-day agenda with tab-switching, session track pills, and a full timetable for April 14–16 |
| `register.html` | Register | Pricing tiers (Early Bird / Standard / VIP), what's included, and FAQ accordion |
| `sponsors.html` | Sponsors | Sponsor tiers (Platinum / Gold / Community), sponsorship packages, and partner CTA |
| `contact.html` | Contact | Contact form with validation, info blocks, and venue map placeholder |
| `about.html` | About | Mission, summit overview, stats, values, highlights, organising committee, and sponsors |

---

## Project Structure

```
/
├── index.html          # Homepage
├── speakers.html       # Speakers listing
├── schedule.html       # Conference agenda
├── register.html       # Registration & pricing
├── sponsors.html       # Sponsors & partners
├── contact.html        # Contact page
├── about.html          # About page (converted from PHP)
├── style.css           # All shared styles
├── js/
│   └── main.js         # Shared JavaScript
├── page-about.php      # Original WordPress PHP template (reference only)
└── README.md           # This file
```

---

## Design System

### Colours

| Token | Hex | Usage |
|-------|-----|-------|
| `--primary-dark-blue` | `#17274D` | Primary brand, headers, dark sections |
| `--accent-cyan` | `#3BFFDD` | Accent highlights, CTAs, active states |
| `--light-background` | `#E7EFED` | Section backgrounds |
| `--text-gray` | `#667085` | Body text, subtitles |
| `--dark-text` | `#101828` | Headings, strong text |
| `--white` | `#FFFFFF` | Cards, light surfaces |

### Typography

- **Headings:** [Poppins](https://fonts.google.com/specimen/Poppins) — weight 700
- **Body:** [Inter](https://fonts.google.com/specimen/Inter) — weight 400/500

Both loaded via Google Fonts CDN.

### Icons

[Font Awesome 6.4.0](https://fontawesome.com/) — loaded via CDN.

---

## JavaScript Features

All shared JS lives in `js/main.js`:

| Feature | Mechanism |
|---------|-----------|
| Sticky header | `scroll` event + CSS class toggle |
| Hamburger / mobile menu | Click toggle on `#hamburger` |
| Fade-in animations | `IntersectionObserver` (threshold 0.1) on `.fade-in` elements |
| Counter animations | `data-count` attribute + `requestAnimationFrame` |
| Scroll-to-top button | `scroll` event + smooth scroll |
| Newsletter form | `handleNewsletter(event)` global function |
| Smooth anchor scroll | `click` delegation on `a[href^="#"]` |

Page-specific JS (inline `<script>` at bottom of each page):

| Page | Feature |
|------|---------|
| `index.html` | Countdown timer targeting `2026-04-14T09:00:00Z`, homepage newsletter form |
| `speakers.html` | Filter tabs by `data-type` attribute |
| `schedule.html` | Day tab switching, show/hide `.schedule-panel` |
| `register.html` | FAQ accordion open/close |
| `contact.html` | Contact form validation and success state |
| All pages | Scroll progress bar (`#scrollProgress`) |

---

## Conference Details

| Detail | Value |
|--------|-------|
| Event | MediaForge Summit 2026 |
| Edition | 11th Annual |
| Dates | 14–16 April 2026 |
| Venue | ExCeL Centre, Royal Victoria Dock, London E16 1XL |
| Expected Attendees | 4,200+ |
| Speakers | 180+ |
| Countries | 62 |

### Pass Pricing

| Tier | Price |
|------|-------|
| Early Bird | £895 |
| Standard | £1,295 |
| VIP | £2,495 |

---

## Getting Started

No build step needed. Open any `.html` file directly in a browser:

```bash
# Quick start with a local server (Python)
python3 -m http.server 8080

# Or with Node.js
npx serve .
```

Then visit `http://localhost:8080` in your browser.

---

## Accessibility

- Semantic HTML5 elements (`<header>`, `<nav>`, `<main>`, `<footer>`, `<section>`, `<article>`, `<blockquote>`)
- `aria-label` on all navigations and interactive controls
- `aria-hidden="true"` on decorative SVGs and icons
- `aria-expanded` on hamburger toggle button
- `role="contentinfo"` on `<footer>`
- All form inputs have associated `<label>` elements
- Colour contrast meets WCAG AA standards

---

## Browser Support

Targets modern evergreen browsers (Chrome, Firefox, Safari, Edge). Uses:
- CSS custom properties (variables)
- CSS Grid and Flexbox
- `IntersectionObserver` API
- `clamp()` for fluid typography

---

## Branch

Active development branch: `claude/big-media-event-site-jKgVQ`

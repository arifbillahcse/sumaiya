/**
 * FRS Lead Tracker – Meta Pixel Lead Event
 *
 * Fires fbq('track','Lead') exactly once per genuine form submission.
 *
 * Duplicate-event guard:
 *   sessionStorage key "frs_lt_lead_fired" is set after the first fire.
 *   A page refresh within the same browser tab will find the key and skip.
 *   (The PHP session gate already redirects direct visits away, but this
 *   provides a belt-and-suspenders client-side guard.)
 */
(function () {
    'use strict';

    var cfg = window.frsLtPixel || {};
    var STORAGE_KEY = 'frs_lt_lead_fired';

    function alreadyFired() {
        try {
            return !!sessionStorage.getItem(STORAGE_KEY);
        } catch (e) {
            return false;
        }
    }

    function markFired() {
        try {
            sessionStorage.setItem(STORAGE_KEY, '1');
        } catch (e) { /* storage unavailable – fire anyway */ }
    }

    function fireLead() {
        if (alreadyFired()) return;

        if (typeof fbq !== 'function') {
            console.warn('[FRS Lead Tracker] fbq not found – is the Meta Pixel base code loaded?');
            return;
        }

        fbq('track', 'Lead');
        markFired();

        // GTM dataLayer push (optional)
        if (cfg.enableGtm) {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ event: 'lead_conversion' });
        }
    }

    // Fire as early as possible – pixel.js is loaded in <head>
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fireLead);
    } else {
        fireLead();
    }
}());

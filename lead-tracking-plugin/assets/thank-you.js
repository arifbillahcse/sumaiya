/**
 * Lead Tracking Pro — Thank You Page Script
 *
 * Fires fbq('track', 'Lead') exactly once per browser session.
 * Uses sessionStorage key 'meta_lead_fired' as a duplicate guard.
 * Also pushes a GTM dataLayer event 'meta_lead_conversion' for
 * Google Tag Manager trigger setup.
 *
 * Depends on:
 *   - The Meta Pixel base code already loaded in <head> (injected by PHP)
 *   - ltpData.pixelId  — localised via wp_localize_script()
 */

(function () {
    'use strict';

    var SESSION_KEY = 'meta_lead_fired';
    var GTM_EVENT   = 'meta_lead_conversion';

    /**
     * Safe sessionStorage read — returns null if unavailable (private mode etc.)
     *
     * @param  {string} key
     * @returns {string|null}
     */
    function ssGet(key) {
        try {
            return sessionStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    /**
     * Safe sessionStorage write.
     *
     * @param  {string} key
     * @param  {string} value
     */
    function ssSet(key, value) {
        try {
            sessionStorage.setItem(key, value);
        } catch (e) {
            // Silently fail — private mode or storage quota
        }
    }

    /**
     * Push an event to the GTM dataLayer.
     * Initialises window.dataLayer if it doesn't already exist.
     *
     * @param {string} eventName
     * @param {string} pixelId
     */
    function pushDataLayer(eventName, pixelId) {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event:    eventName,
            pixel_id: pixelId
        });
    }

    /**
     * Fire the Meta Pixel Lead event and the GTM dataLayer event.
     * Marks sessionStorage so the events are not duplicated on reload.
     */
    function fireLeadEvent() {
        var pixelId = (window.ltpData && window.ltpData.pixelId) ? window.ltpData.pixelId : '';

        // Guard: pixel base code must be present
        if (typeof window.fbq !== 'function') {
            console.warn('[LTP] fbq not found — Meta Pixel base code may not have loaded.');
            return;
        }

        // Duplicate prevention
        if (ssGet(SESSION_KEY) === '1') {
            console.info('[LTP] Lead event already fired this session — skipping duplicate.');
            return;
        }

        // Fire Meta Pixel Lead event
        window.fbq('track', 'Lead');
        console.info('[LTP] Meta Pixel Lead event fired (Pixel ID: ' + (pixelId || 'N/A') + ').');

        // Push GTM dataLayer event
        pushDataLayer(GTM_EVENT, pixelId);
        console.info('[LTP] GTM dataLayer event pushed: ' + GTM_EVENT);

        // Set duplicate prevention flag
        ssSet(SESSION_KEY, '1');
    }

    // Run after the DOM is ready so fbq has had time to initialise
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fireLeadEvent);
    } else {
        // DOM already ready (script loaded with defer/async after parse)
        fireLeadEvent();
    }

}());

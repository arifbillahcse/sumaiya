/**
 * Thank You Page — Meta Pixel Lead event + duplicate-fire guard.
 *
 * Fires exactly once per page load. A sessionStorage flag prevents the event
 * from re-firing if the user refreshes the Thank You Page.
 */
(function () {
    'use strict';

    var SESSION_KEY = 'frs_lead_fired';

    function fireLeadEvent() {
        if (sessionStorage.getItem(SESSION_KEY)) {
            return; // already fired this session
        }

        if (typeof fbq === 'function') {
            fbq('track', 'Lead');
            sessionStorage.setItem(SESSION_KEY, '1');
        }

        // GTM data-layer push (triggers a GTM "Custom Event" trigger named frs_lead)
        if (window.dataLayer) {
            window.dataLayer.push({ event: 'frs_lead' });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fireLeadEvent);
    } else {
        fireLeadEvent();
    }
}());

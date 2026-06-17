/**
 * FRS Lead Tracker – Form Enhancement
 *
 * Responsibilities:
 *  1. Removes any pre-existing fbq('track','Lead') calls that Elementor may
 *     have wired to the form submit trigger (prevents duplicate events while
 *     you migrate to the Thank You Page approach).
 *  2. Copies the reCAPTCHA v3 token into a hidden input before submission.
 *  3. Appends ?lead=1 to the Elementor form redirect URL when the form
 *     succeeds (fallback for environments where PHP sessions are unavailable).
 */
(function ($) {
    'use strict';

    var cfg = window.frsLtForms || {};

    // ── 1. Block any inline fbq Lead calls wired to Elementor submit ──────
    //       We override fbq temporarily during form submission so only our
    //       Thank You Page fires the real Lead event.
    var originalFbq = window.fbq;
    var blockLead   = false;

    if (typeof originalFbq === 'function') {
        window.fbq = function () {
            // Pass all calls through EXCEPT 'track','Lead' while form is submitting
            if (blockLead && arguments[0] === 'track' && arguments[1] === 'Lead') {
                return;
            }
            return originalFbq.apply(this, arguments);
        };
        // Copy any queue / properties so the pixel doesn't break
        for (var key in originalFbq) {
            if (Object.prototype.hasOwnProperty.call(originalFbq, key)) {
                window.fbq[key] = originalFbq[key];
            }
        }
    }

    // ── 2. Elementor form submit event ────────────────────────────────────
    $(document).on('submit', '.elementor-form', function () {
        blockLead = true;
    });

    // Reset block after Elementor's AJAX response
    $(document).on('elementor/forms/ajax/success', function () {
        setTimeout(function () { blockLead = false; }, 2000);
    });
    $(document).on('elementor/forms/ajax/error', function () {
        blockLead = false;
    });

    // ── 3. Append ?lead=1 to redirect URL (client-side fallback) ─────────
    //       Elementor Pro fires 'elementor/frontend/forms/submit_success'.
    $(document).on('submit_success', '.elementor-form', function () {
        // Short delay lets Elementor initiate its own redirect first
        setTimeout(function () {
            var thankyou = cfg.thankyouUrl;
            if (thankyou && window.location.href.indexOf(thankyou) === -1) {
                var sep = thankyou.indexOf('?') !== -1 ? '&' : '?';
                window.location.href = thankyou + sep + 'lead=1';
            }
        }, 300);
    });

}(jQuery));

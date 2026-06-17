/**
 * FRS Lead Tracker – reCAPTCHA v3 token injection
 *
 * Before any Elementor form is submitted, this script:
 *  1. Calls grecaptcha.execute() to get a fresh token.
 *  2. Injects the token into a hidden input named "frs_recaptcha_token".
 *  3. Only then allows the form to proceed.
 *
 * Elementor fires its own AJAX submit so we hook into the native form
 * 'submit' event and preventDefault, get the token async, then
 * re-trigger Elementor's submission mechanism.
 */
(function () {
    'use strict';

    var cfg     = window.frsLtRecaptcha || {};
    var siteKey = cfg.siteKey || '';

    if (!siteKey) return;

    document.addEventListener('DOMContentLoaded', function () {
        var forms = document.querySelectorAll('.elementor-form');

        forms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                // Prevent double-guard if token already injected this session
                if (form.querySelector('[name="frs_recaptcha_token"]')) return;

                e.preventDefault();
                e.stopImmediatePropagation();

                grecaptcha.ready(function () {
                    grecaptcha.execute(siteKey, { action: 'lead_form_submit' }).then(function (token) {
                        // Inject token as hidden field
                        var input = document.createElement('input');
                        input.type  = 'hidden';
                        input.name  = 'frs_recaptcha_token';
                        input.value = token;
                        form.appendChild(input);

                        // Re-submit – this time the hidden field exists so we
                        // do not intercept again
                        form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                    });
                });
            }, true); // capture phase so we run before Elementor
        });
    });
}());

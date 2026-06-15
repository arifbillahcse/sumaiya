/* LeadTrack Pro – Anti-Spam (Honeypot + Time Validation + reCAPTCHA v3) */
(function ($) {
  'use strict';

  var cfg = window.LeadTrackAntispam || {};
  var TIME_LIMIT       = parseInt(cfg.timeLimit || '3', 10) * 1000;
  var ENABLE_RECAPTCHA = cfg.enableRecaptcha === '1';
  var SITE_KEY         = cfg.recaptchaSiteKey || '';

  // Load reCAPTCHA v3 script if enabled.
  if (ENABLE_RECAPTCHA && SITE_KEY) {
    var script   = document.createElement('script');
    script.src   = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(SITE_KEY);
    script.async = true;
    document.head.appendChild(script);
  }

  document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('.elementor-form');

    forms.forEach(function (form) {
      var loadTime = Date.now();

      // ── Honeypot field ─────────────────────────────────────────────────
      var hp = document.createElement('input');
      hp.type      = 'text';
      hp.name      = '_ltp_hp';
      hp.value     = '';
      hp.tabIndex  = -1;
      hp.autocomplete = 'off';
      hp.setAttribute('aria-hidden', 'true');
      hp.style.cssText = 'position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;opacity:0;';
      form.appendChild(hp);

      // ── Timestamp field ────────────────────────────────────────────────
      var ts = document.createElement('input');
      ts.type  = 'hidden';
      ts.name  = '_ltp_ts';
      ts.value = Math.floor(loadTime / 1000);
      form.appendChild(ts);

      // ── reCAPTCHA token field ──────────────────────────────────────────
      var rcField = null;
      if (ENABLE_RECAPTCHA && SITE_KEY) {
        rcField       = document.createElement('input');
        rcField.type  = 'hidden';
        rcField.name  = '_ltp_recaptcha_token';
        rcField.value = '';
        form.appendChild(rcField);
      }

      // ── Submit validation ──────────────────────────────────────────────
      form.addEventListener('submit', function (e) {
        // Honeypot: should be empty.
        if (hp.value !== '') {
          e.preventDefault();
          e.stopImmediatePropagation();
          document.dispatchEvent(new CustomEvent('ltp:spam_detected'));
          return;
        }

        // Time-based check.
        var elapsed = Date.now() - loadTime;
        if (elapsed < TIME_LIMIT) {
          e.preventDefault();
          e.stopImmediatePropagation();
          showError(form, 'Please wait a moment before submitting.');
          document.dispatchEvent(new CustomEvent('ltp:spam_detected'));
          return;
        }

        // reCAPTCHA v3 — execute and set token, then re-submit.
        if (ENABLE_RECAPTCHA && SITE_KEY && rcField && typeof grecaptcha !== 'undefined') {
          if (rcField.value === '') {
            e.preventDefault();
            grecaptcha.ready(function () {
              grecaptcha.execute(SITE_KEY, { action: 'leadtrack_submit' }).then(function (token) {
                rcField.value = token;
                form.dispatchEvent(new Event('submit', { bubbles: true }));
              });
            });
          }
        }
      }, true); // useCapture to fire before Elementor's listener.
    });
  });

  function showError(form, message) {
    var existing = form.querySelector('.ltp-antispam-error');
    if (existing) return;
    var div = document.createElement('div');
    div.className   = 'ltp-antispam-error elementor-message elementor-message-danger';
    div.textContent = message;
    div.style.cssText = 'margin-top:10px;padding:10px 14px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:6px;font-size:0.9rem;';
    form.appendChild(div);
    setTimeout(function () { div.remove(); }, 5000);
  }
}(jQuery));

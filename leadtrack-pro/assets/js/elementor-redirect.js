/* LeadTrack Pro – Elementor Form Redirect */
(function ($) {
  'use strict';

  var redirect = window.LeadTrackRedirect || {};
  var thankYouUrl = redirect.thankYouUrl || '';

  if (!thankYouUrl) {
    return;
  }

  function doRedirect() {
    sessionStorage.setItem('ltp_from_form', '1');
    window.location.href = thankYouUrl;
  }

  // ── Elementor Pro form success event (primary) ──────────────────────────
  // Elementor Pro fires a jQuery event on the form element after success.
  $(document).on('submit_success', '.elementor-form', function (e, response) {
    doRedirect();
  });

  // ── Elementor JS API hook (fallback for newer Elementor versions) ───────
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof elementorFrontend === 'undefined') {
      return;
    }

    // Hook into every form widget once it's ready.
    elementorFrontend.hooks.addAction(
      'frontend/element_ready/form.default',
      function ($scope) {
        var $form = $scope.find('.elementor-form');

        $form.on('submit_success', function (e, response) {
          // Respect Elementor's own redirect action if configured.
          if (
            response &&
            response.data &&
            response.data.redirect_url &&
            response.data.redirect_url !== ''
          ) {
            return; // Elementor already redirecting.
          }
          doRedirect();
        });
      }
    );
  });

  // ── Custom event emitted by antispam.js on block ────────────────────────
  document.addEventListener('ltp:spam_detected', function () {
    // Do nothing — spam detected, no redirect.
  });
}(jQuery));

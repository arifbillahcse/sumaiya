/* LeadTrack Pro – Meta Pixel Lead Event Tracking */
(function () {
  'use strict';

  var vars = window.LeadTrackVars || {};

  if (vars.enable_pixel !== '1' || vars.isThankYouPage !== true) {
    return;
  }

  var STORAGE_KEY   = 'ltp_lead_fired';
  var STORAGE_TS    = 'ltp_lead_ts';
  var DEDUP_WINDOW  = 24 * 60 * 60 * 1000; // 24 hours in ms

  function alreadyFired() {
    // Session storage: fired this session?
    if (sessionStorage.getItem(STORAGE_KEY) === '1') {
      return true;
    }
    // Local storage: fired within dedup window?
    var ts = parseInt(localStorage.getItem(STORAGE_TS) || '0', 10);
    if (ts && Date.now() - ts < DEDUP_WINDOW) {
      return true;
    }
    return false;
  }

  function markFired() {
    sessionStorage.setItem(STORAGE_KEY, '1');
    localStorage.setItem(STORAGE_TS, String(Date.now()));
  }

  function fireLeadEvent() {
    if (alreadyFired()) {
      return;
    }
    if (typeof fbq !== 'function') {
      // Pixel not loaded yet; retry once after 1 second.
      setTimeout(function () {
        if (typeof fbq === 'function') {
          fbq('track', 'Lead');
          markFired();
        }
      }, 1000);
      return;
    }
    fbq('track', 'Lead');
    markFired();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fireLeadEvent);
  } else {
    fireLeadEvent();
  }
}());

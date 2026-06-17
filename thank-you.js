/**
 * Thank You Page — Meta Pixel Lead Event + GTM
 *
 * Fires fbq('Lead') ONLY when:
 *   1. User actually came from the lead form (sessionStorage flag)
 *   2. Event has NOT already fired this session (duplicate guard)
 */

(function () {
  var PIXEL_ID      = 'YOUR_PIXEL_ID';  // 🔁 Replace YOUR_PIXEL_ID
  var SESSION_KEY   = 'meta_lead_fired';
  var SUBMITTED_KEY = 'lead_submitted';
  var TIMESTAMP_KEY = 'lead_timestamp';
  var MAX_AGE_MS    = 30 * 60 * 1000; // 30 minutes — ignore stale flags

  function getSession(key) {
    try { return sessionStorage.getItem(key); } catch(e) { return null; }
  }

  function setSession(key, val) {
    try { sessionStorage.setItem(key, val); } catch(e) {}
  }

  function removeSession(key) {
    try { sessionStorage.removeItem(key); } catch(e) {}
  }

  // Check: did user actually submit the form?
  var submitted  = getSession(SUBMITTED_KEY);
  var timestamp  = parseInt(getSession(TIMESTAMP_KEY) || '0', 10);
  var isRecent   = (Date.now() - timestamp) < MAX_AGE_MS;
  var alreadyFired = getSession(SESSION_KEY) === '1';

  if (!submitted || !isRecent) {
    // Not a genuine redirect from form — do NOT fire pixel
    console.log('[LeadTracking] No valid form submission detected. Pixel NOT fired.');
    return;
  }

  if (alreadyFired) {
    // Duplicate guard — user refreshed the Thank You page
    console.log('[LeadTracking] Lead event already fired this session. Skipping duplicate.');
    return;
  }

  // ✅ All checks passed — fire Meta Pixel Lead event
  if (typeof fbq === 'function') {
    fbq('track', 'Lead', {
      content_name: 'Lead Form Submission',
      content_category: 'Lead Generation'
    });
    console.log('[LeadTracking] Meta Pixel Lead event fired. ✅');
  } else {
    console.warn('[LeadTracking] fbq not found. Is the Pixel base code loaded?');
  }

  // ✅ Push GTM dataLayer event (set up trigger in GTM for this)
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({
    event: 'meta_lead_conversion',
    pixel_id: PIXEL_ID,
    form_name: 'Lead Form'
  });
  console.log('[LeadTracking] GTM dataLayer event pushed: meta_lead_conversion ✅');

  // ✅ Set duplicate-prevention flag
  setSession(SESSION_KEY, '1');

  // ✅ Clean up the submission flags
  removeSession(SUBMITTED_KEY);
  removeSession(TIMESTAMP_KEY);

})();

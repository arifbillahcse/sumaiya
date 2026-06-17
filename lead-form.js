/**
 * Lead Form — Tracking & Spam Protection
 * Handles: reCAPTCHA v3, Honeypot, Validation, Meta Pixel, GTM
 *
 * ✅ Replace these values:
 *   RECAPTCHA_SITE_KEY  → your reCAPTCHA v3 site key
 *   THANK_YOU_PAGE_URL  → your thank-you page URL
 *   YOUR_PIXEL_ID       → your Meta Pixel ID (also in HTML)
 */

const CONFIG = {
  recaptchaSiteKey: 'YOUR_RECAPTCHA_SITE_KEY',   // 🔁 replace
  thankYouPageUrl:  'thank-you.html',             // 🔁 replace with full URL in production
  pixelId:          'YOUR_PIXEL_ID',              // 🔁 replace
  minScore:         0.5                           // reCAPTCHA score threshold
};

/* ========================================================
   FORM SUBMIT HANDLER
======================================================== */
document.getElementById('leadForm').addEventListener('submit', async function (e) {
  e.preventDefault();

  clearErrors();

  // 1. Validate fields
  if (!validateForm()) return;

  // 2. Honeypot check — if filled, silently fake success (bot)
  if (document.getElementById('website_url').value.trim() !== '') {
    fakeSuccessRedirect();
    return;
  }

  setLoading(true);

  try {
    // 3. Get reCAPTCHA v3 token
    const token = await getRecaptchaToken();
    document.getElementById('recaptchaToken').value = token;

    // 4. Optional: send to your server for score validation
    //    If you have no backend, skip this and go straight to redirect.
    //    Uncomment below if you have a backend endpoint:
    //
    // const passed = await verifyOnServer(token);
    // if (!passed) {
    //   showFormError('Spam detected. Please try again.');
    //   setLoading(false);
    //   return;
    // }

    // 5. Collect form data (for your own server/CRM if needed)
    const formData = {
      name:    document.getElementById('fullName').value.trim(),
      email:   document.getElementById('email').value.trim(),
      phone:   document.getElementById('phone').value.trim(),
      message: document.getElementById('message').value.trim(),
      recaptchaToken: token
    };

    // 6. Optional: POST to your backend
    //    Remove the comment block below if you have a server endpoint
    /*
    const response = await fetch('/api/submit-lead', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData)
    });
    if (!response.ok) throw new Error('Server error');
    */

    // 7. Mark lead as pending (Thank You page will fire the pixel)
    sessionStorage.setItem('lead_submitted', '1');
    sessionStorage.setItem('lead_timestamp', Date.now().toString());

    // 8. Redirect to Thank You page
    window.location.href = CONFIG.thankYouPageUrl;

  } catch (err) {
    console.error('Form submission error:', err);
    showFormError('Something went wrong. Please try again.');
    setLoading(false);
  }
});

/* ========================================================
   RECAPTCHA v3 TOKEN
======================================================== */
function getRecaptchaToken() {
  return new Promise((resolve, reject) => {
    if (typeof grecaptcha === 'undefined') {
      // reCAPTCHA not loaded — allow submission (graceful degradation)
      resolve('recaptcha_not_loaded');
      return;
    }
    grecaptcha.ready(function () {
      grecaptcha.execute(CONFIG.recaptchaSiteKey, { action: 'lead_form_submit' })
        .then(resolve)
        .catch(reject);
    });
  });
}

/* ========================================================
   OPTIONAL: Server-side reCAPTCHA score check
   (Only use if you have a backend)
======================================================== */
async function verifyOnServer(token) {
  const res = await fetch('/api/verify-recaptcha', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ token, minScore: CONFIG.minScore })
  });
  const data = await res.json();
  return data.success === true;
}

/* ========================================================
   FORM VALIDATION
======================================================== */
function validateForm() {
  let valid = true;

  const name  = document.getElementById('fullName');
  const email = document.getElementById('email');
  const phone = document.getElementById('phone');

  if (!name.value.trim()) {
    showFieldError(name, 'nameError');
    valid = false;
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email.value.trim())) {
    showFieldError(email, 'emailError');
    valid = false;
  }

  if (!phone.value.trim()) {
    showFieldError(phone, 'phoneError');
    valid = false;
  }

  return valid;
}

function showFieldError(input, errorId) {
  input.classList.add('invalid');
  document.getElementById(errorId).classList.add('visible');
}

function clearErrors() {
  document.querySelectorAll('.form-group input, .form-group textarea').forEach(el => {
    el.classList.remove('invalid');
  });
  document.querySelectorAll('.error-msg').forEach(el => {
    el.classList.remove('visible');
  });
  document.getElementById('formError').style.display = 'none';
}

function showFormError(msg) {
  const banner = document.getElementById('formError');
  banner.textContent = msg;
  banner.style.display = 'block';
}

/* ========================================================
   UI STATE
======================================================== */
function setLoading(state) {
  const btn    = document.getElementById('submitBtn');
  const text   = document.getElementById('btnText');
  const loader = document.getElementById('btnLoader');

  btn.disabled    = state;
  text.textContent = state ? 'Sending...' : 'Send My Request';
  loader.style.display = state ? 'inline-block' : 'none';
}

/* ========================================================
   BOT HONEYPOT: fake success so bot doesn't retry
======================================================== */
function fakeSuccessRedirect() {
  // Don't fire pixel, don't track — just redirect silently
  setTimeout(() => {
    window.location.href = CONFIG.thankYouPageUrl;
  }, 800);
}

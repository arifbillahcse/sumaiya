jQuery(function ($) {
    'use strict';

    // Toggle reCAPTCHA credential fields
    var $toggle = $('#frs_lt_enable_recaptcha');
    var $fields = $('#frs-lt-recaptcha-fields');

    $toggle.on('change', function () {
        $fields.toggleClass('frs-lt-hidden', !this.checked);
    });
});

/**
 * LeadTrack Pro — Anti-Spam JavaScript
 *
 * Injects a honeypot field and a hidden timestamp field into all forms on
 * the page.  On submit, validates that:
 *   1. The honeypot field is empty (bots fill it in).
 *   2. Enough time has elapsed since the page loaded (bot-speed check).
 *
 * Also loads Google reCAPTCHA v2 if a site key is configured.
 *
 * Depends on:
 *   jQuery
 *   LeadTrackAntispam (localised by wp_localize_script)
 *     .honeypotField    {string} Name of the honeypot input.
 *     .recaptchaSiteKey {string} Google reCAPTCHA v2 site key (may be empty).
 */

/* global LeadTrackAntispam, grecaptcha */

( function ( $ ) {
	'use strict';

	if ( typeof LeadTrackAntispam === 'undefined' ) {
		return;
	}

	var honeypotField = LeadTrackAntispam.honeypotField || 'lt_hp_email';
	var siteKey       = LeadTrackAntispam.recaptchaSiteKey || '';
	var pageLoadTime  = Math.floor( Date.now() / 1000 );
	var MIN_TIME      = 3; // seconds

	// -----------------------------------------------------------------------
	// DOM ready
	// -----------------------------------------------------------------------
	$( function () {
		injectFields();
		bindValidation();

		if ( siteKey ) {
			loadRecaptcha();
		}
	} );

	// -----------------------------------------------------------------------
	// Field injection
	// -----------------------------------------------------------------------

	/**
	 * Inject hidden anti-spam fields into every <form> on the page.
	 * Skips forms that already contain our fields (e.g., cached HTML).
	 */
	function injectFields() {
		$( 'form' ).each( function () {
			var $form = $( this );

			// Avoid double-injection.
			if ( $form.find( '[name="' + honeypotField + '"]' ).length ) {
				return;
			}

			// Honeypot field — visually hidden, but NOT via display:none
			// (some bots skip truly invisible fields).
			var $honeypot = $( '<div>', {
				'aria-hidden': 'true',
				css: {
					position:  'absolute',
					left:      '-9999px',
					top:       '-9999px',
					width:     '1px',
					height:    '1px',
					overflow:  'hidden'
				}
			} ).append(
				$( '<label>', {
					'for': 'lt-hp-' + honeypotField,
					text:  'Leave this field empty'   // Screen-reader hint (not translated — intentionally confusing for bots).
				} ),
				$( '<input>', {
					type:         'text',
					id:           'lt-hp-' + honeypotField,
					name:         honeypotField,
					value:        '',
					autocomplete: 'off',
					tabindex:     '-1'
				} )
			);

			// Timestamp field — records when the page was loaded.
			var $timestamp = $( '<input>', {
				type:  'hidden',
				name:  '_lt_form_time',
				value: pageLoadTime
			} );

			$form.append( $honeypot ).append( $timestamp );
		} );
	}

	// -----------------------------------------------------------------------
	// Client-side validation
	// -----------------------------------------------------------------------

	/**
	 * Bind a submit handler to all forms that contain our honeypot field.
	 */
	function bindValidation() {
		$( document ).on( 'submit', 'form', function ( e ) {
			var $form = $( this );

			// Only validate forms that have our fields.
			if ( ! $form.find( '[name="' + honeypotField + '"]' ).length ) {
				return;
			}

			// 1. Honeypot check.
			var honeypotValue = $form.find( '[name="' + honeypotField + '"]' ).val();
			if ( honeypotValue ) {
				e.preventDefault();
				e.stopImmediatePropagation();
				return false;
			}

			// 2. Timing check.
			var formTime  = parseInt( $form.find( '[name="_lt_form_time"]' ).val(), 10 ) || 0;
			var elapsed   = Math.floor( Date.now() / 1000 ) - formTime;

			if ( formTime > 0 && elapsed < MIN_TIME ) {
				e.preventDefault();
				e.stopImmediatePropagation();
				return false;
			}

			// 3. reCAPTCHA: check that the token exists if reCAPTCHA is enabled.
			if ( siteKey ) {
				var token = $form.find( '[name="g-recaptcha-response"]' ).val();
				if ( ! token ) {
					e.preventDefault();
					showRecaptchaError( $form );
					return false;
				}
			}
		} );
	}

	// -----------------------------------------------------------------------
	// Google reCAPTCHA v2
	// -----------------------------------------------------------------------

	/**
	 * Dynamically load the Google reCAPTCHA v2 script and render widgets
	 * inside every form that does not already have one.
	 */
	function loadRecaptcha() {
		// Avoid double-loading.
		if ( document.getElementById( 'lt-recaptcha-script' ) ) {
			return;
		}

		window.ltOnRecaptchaLoad = function () {
			renderRecaptchaWidgets();
		};

		var script   = document.createElement( 'script' );
		script.id    = 'lt-recaptcha-script';
		script.src   = 'https://www.google.com/recaptcha/api.js?onload=ltOnRecaptchaLoad&render=explicit';
		script.async = true;
		script.defer = true;
		document.head.appendChild( script );
	}

	/**
	 * Render a reCAPTCHA v2 widget inside each form that has our honeypot.
	 */
	function renderRecaptchaWidgets() {
		if ( typeof grecaptcha === 'undefined' ) {
			return;
		}

		$( 'form' ).each( function () {
			var $form = $( this );
			if ( ! $form.find( '[name="' + honeypotField + '"]' ).length ) {
				return;
			}
			if ( $form.find( '.lt-recaptcha-widget' ).length ) {
				return;
			}

			var $container = $( '<div>', { 'class': 'lt-recaptcha-widget', css: { margin: '0.75rem 0' } } );
			$form.find( '[type="submit"]' ).first().before( $container );

			grecaptcha.render( $container[0], { sitekey: siteKey } );
		} );
	}

	/**
	 * Show a brief reCAPTCHA error notice near the submit button.
	 *
	 * @param {jQuery} $form The form element.
	 */
	function showRecaptchaError( $form ) {
		if ( $form.find( '.lt-recaptcha-error' ).length ) {
			return;
		}

		var $error = $( '<p>', {
			'class': 'lt-recaptcha-error',
			css: {
				color:     '#b91c1c',
				fontSize:  '0.875rem',
				margin:    '0.4rem 0 0'
			},
			text: 'Please complete the reCAPTCHA challenge before submitting.'
		} );

		$form.find( '.lt-recaptcha-widget' ).after( $error );

		setTimeout( function () {
			$error.remove();
		}, 4000 );
	}

} )( jQuery );

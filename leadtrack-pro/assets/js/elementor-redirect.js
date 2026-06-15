/**
 * LeadTrack Pro — Elementor Form Success Redirect
 *
 * Listens for Elementor Pro form-success events and redirects the visitor
 * to the Thank You page URL provided by LeadTrackElementor.thankyouUrl.
 *
 * Depends on:
 *   jQuery
 *   LeadTrackElementor (localised by wp_localize_script)
 *     .thankyouUrl {string} The full Thank You page URL.
 *
 * Elementor fires the 'submit_success' event on the form widget element
 * after a successful AJAX submission.
 */

/* global LeadTrackElementor */

( function ( $ ) {
	'use strict';

	if ( typeof LeadTrackElementor === 'undefined' || ! LeadTrackElementor.thankyouUrl ) {
		return;
	}

	var redirectUrl = LeadTrackElementor.thankyouUrl;

	/**
	 * Perform the redirect.
	 * Wrapped in a helper so we can call it from multiple event sources.
	 */
	function doRedirect() {
		window.location.href = redirectUrl;
	}

	// -----------------------------------------------------------------------
	// Method 1 — Elementor Pro < 3.x custom event on the form widget.
	// -----------------------------------------------------------------------
	$( document ).on( 'submit_success', '.elementor-form', function () {
		doRedirect();
	} );

	// -----------------------------------------------------------------------
	// Method 2 — Elementor Pro 3.x+ fires a namespaced jQuery event.
	// -----------------------------------------------------------------------
	$( document ).on( 'elementor/forms/submit_success', function () {
		doRedirect();
	} );

	// -----------------------------------------------------------------------
	// Method 3 — Intercept AJAX response data injected by the PHP class.
	//
	// LeadTrack_Elementor_Integration::inject_redirect_into_response() adds
	// a `redirect_url` key to the Elementor AJAX JSON payload.  We hook into
	// the global jQuery ajaxComplete to catch that and redirect if present.
	// -----------------------------------------------------------------------
	$( document ).ajaxComplete( function ( event, xhr, settings ) {
		// Only intercept Elementor form AJAX calls.
		if ( ! settings.data || settings.data.indexOf( 'action=elementor_pro_forms_send_form' ) === -1 ) {
			return;
		}

		var responseText = xhr.responseText || '';
		if ( ! responseText ) {
			return;
		}

		var json;
		try {
			json = JSON.parse( responseText );
		} catch ( e ) {
			return;
		}

		// Respect a redirect_url in the response data (set by our PHP filter).
		if ( json && json.data && json.data.redirect_url ) {
			window.location.href = json.data.redirect_url;
			return;
		}

		// Fallback: redirect on any success response from Elementor forms.
		if ( json && json.success === true ) {
			doRedirect();
		}
	} );

} )( jQuery );

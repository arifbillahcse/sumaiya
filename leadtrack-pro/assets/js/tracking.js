/**
 * LeadTrack Pro — Meta Pixel Lead Event Tracker
 *
 * Fires fbq('track', 'Lead') on the Thank You page with sessionStorage +
 * localStorage deduplication to prevent duplicate conversions.
 *
 * Depends on: LeadTrackVars (localised by wp_localize_script in the main plugin file)
 *   LeadTrackVars.pixelId        {string}  Meta Pixel ID
 *   LeadTrackVars.enablePixel    {boolean} Whether pixel tracking is on
 *   LeadTrackVars.isThankyouPage {boolean} Whether this is the Thank You page
 *   LeadTrackVars.thankyouPageUrl {string} Full URL of the Thank You page
 */

/* global LeadTrackVars, fbq */

( function () {
	'use strict';

	// Guard: wait for DOM ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	function init() {
		// Bail if localised vars are missing.
		if ( typeof LeadTrackVars === 'undefined' ) {
			return;
		}

		var vars = LeadTrackVars;

		// Only fire on the Thank You page.
		if ( ! vars.isThankyouPage ) {
			return;
		}

		// Only fire if pixel tracking is enabled and a pixel ID exists.
		if ( ! vars.enablePixel || ! vars.pixelId ) {
			return;
		}

		// Wait for fbq to become available (base code is in wp_head).
		if ( typeof fbq !== 'function' ) {
			// Try once more after a short delay to handle async load edge-cases.
			setTimeout( function () {
				if ( typeof fbq === 'function' ) {
					maybeFireLeadEvent( vars.pixelId );
				}
			}, 500 );
			return;
		}

		maybeFireLeadEvent( vars.pixelId );
	}

	/**
	 * Fire fbq('track', 'Lead') only if it hasn't been fired yet for this
	 * pixel ID in the current session or for this device (localStorage).
	 *
	 * @param {string} pixelId The Meta Pixel ID.
	 */
	function maybeFireLeadEvent( pixelId ) {
		var storageKey = 'lt_lead_fired_' + pixelId;

		// sessionStorage: per-tab deduplication (page refresh guard).
		try {
			if ( sessionStorage.getItem( storageKey ) ) {
				return;
			}
		} catch ( e ) {
			// Private browsing may block sessionStorage — continue regardless.
		}

		// localStorage: cross-session deduplication (prevents counting a
		// visitor who revisits the Thank You page in a new tab).
		try {
			if ( localStorage.getItem( storageKey ) ) {
				return;
			}
		} catch ( e ) {
			// Swallow storage errors in restricted environments.
		}

		// Fire the Lead event.
		fbq( 'track', 'Lead' );

		// Mark as fired in both storage layers.
		try {
			sessionStorage.setItem( storageKey, '1' );
		} catch ( e ) {}

		try {
			localStorage.setItem( storageKey, '1' );
		} catch ( e ) {}
	}
} )();

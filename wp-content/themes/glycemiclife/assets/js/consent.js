/**
 * GlycemicLife — custom cookie consent banner.
 * Talks to Google Consent Mode (gtag) set up in header.php.
 */
( function () {
	'use strict';

	var STORAGE_KEY = 'glycemiclife_consent';
	var banner = document.getElementById( 'glycemiclife-consent-banner' );

	if ( ! banner ) {
		return;
	}

	function getStored() {
		try {
			return localStorage.getItem( STORAGE_KEY );
		} catch ( e ) {
			return null;
		}
	}

	function store( value ) {
		try {
			localStorage.setItem( STORAGE_KEY, value );
		} catch ( e ) {
			// Storage unavailable (private mode, etc.) — banner will just reappear next visit.
		}
	}

	function updateConsent( granted ) {
		if ( typeof window.gtag !== 'function' ) {
			return;
		}
		window.gtag( 'consent', 'update', {
			ad_storage: granted ? 'granted' : 'denied',
			ad_user_data: granted ? 'granted' : 'denied',
			ad_personalization: granted ? 'granted' : 'denied',
			analytics_storage: granted ? 'granted' : 'denied'
		} );
	}

	function showBanner() {
		banner.classList.add( 'is-visible' );
		banner.setAttribute( 'aria-hidden', 'false' );
	}

	function hideBanner() {
		banner.classList.remove( 'is-visible' );
		banner.setAttribute( 'aria-hidden', 'true' );
	}

	var acceptBtn = banner.querySelector( '[data-consent-accept]' );
	var rejectBtn = banner.querySelector( '[data-consent-reject]' );

	if ( acceptBtn ) {
		acceptBtn.addEventListener( 'click', function () {
			store( 'accepted' );
			updateConsent( true );
			hideBanner();
		} );
	}

	if ( rejectBtn ) {
		rejectBtn.addEventListener( 'click', function () {
			store( 'rejected' );
			updateConsent( false );
			hideBanner();
		} );
	}

	// Show the banner only if the visitor hasn't made a choice yet.
	if ( ! getStored() ) {
		showBanner();
	}

	// Any element with data-consent-reopen (e.g. footer "Cookie Settings" link)
	// lets a visitor change their choice at any time.
	var reopenLinks = document.querySelectorAll( '[data-consent-reopen]' );
	for ( var i = 0; i < reopenLinks.length; i++ ) {
		reopenLinks[ i ].addEventListener( 'click', function ( e ) {
			e.preventDefault();
			showBanner();
		} );
	}
}() );

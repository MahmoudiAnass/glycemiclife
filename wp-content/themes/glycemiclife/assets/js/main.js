/* GlycemicLife theme — minimal JS. Mobile nav toggle only. */
( function () {
	'use strict';

	var header = document.querySelector( '.site-header' );
	var toggle = document.querySelector( '.nav-toggle' );
	var panel  = document.getElementById( 'site-header-panel' );

	if ( ! header || ! toggle || ! panel ) {
		return;
	}

	function closeNav() {
		header.classList.remove( 'is-nav-open' );
		toggle.setAttribute( 'aria-expanded', 'false' );
	}

	function openNav() {
		header.classList.add( 'is-nav-open' );
		toggle.setAttribute( 'aria-expanded', 'true' );
	}

	toggle.addEventListener( 'click', function () {
		if ( header.classList.contains( 'is-nav-open' ) ) {
			closeNav();
		} else {
			openNav();
		}
	} );

	// Close the panel once a nav link is followed (avoids a stuck-open menu on next page).
	panel.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( 'a' ) ) {
			closeNav();
		}
	} );

	// Collapse back to the desktop layout if the viewport is resized past the breakpoint.
	window.addEventListener( 'resize', function () {
		if ( window.innerWidth > 780 ) {
			closeNav();
		}
	} );
} )();

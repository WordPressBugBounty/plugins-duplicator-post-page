/* global wp */
( function () {
	'use strict';

	// core's common.js adds the .notice-dismiss button to .is-dismissible
	// notices and hides the notice; this only records the dismissal.
	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest && event.target.closest( '.wpshark-notice .notice-dismiss' );

		if ( ! button ) {
			return;
		}

		var id = parseInt( button.closest( '.wpshark-notice' ).getAttribute( 'data-wpshark-id' ), 10 );

		if ( ! id || ! wp.apiFetch ) {
			return;
		}

		// apiFetch sends the X-WP-Nonce header, which blocks CSRF.
		wp.apiFetch( {
			path: '/wpshark-sdk/v1/dismiss',
			method: 'POST',
			data: { id: id },
		} ).catch( function () {} );
	} );
} )();

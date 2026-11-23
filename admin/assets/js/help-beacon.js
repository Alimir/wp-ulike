/**
 * WP ULike support chat (HelpWich beacon), opt-in per user.
 *
 * Before consent only a local launcher exists. The first click saves consent,
 * loads the beacon and opens the chat. After that the beacon loads on its own.
 */
( function () {
	'use strict';

	var cfg = window.wpUlikeHelpBeacon;

	if ( ! cfg || ! cfg.key || ! cfg.src ) {
		return;
	}

	var beaconOrigin = '';
	try {
		beaconOrigin = new URL( cfg.src ).origin;
	} catch ( e ) {
		return;
	}

	/**
	 * Inject the beacon with the same queue as the official snippet.
	 *
	 * @param {Object}   options
	 * @param {boolean}  options.open    Open the chat as soon as it is ready.
	 * @param {Function} options.onLoad  Called when the script has loaded.
	 * @param {Function} options.onError Called when it cannot load.
	 */
	function loadBeacon( options ) {
		var w = window;
		var d = document;

		w.HelpWich =
			w.HelpWich ||
			function () {
				( w.HelpWich.q = w.HelpWich.q || [] ).push( arguments );
			};
		w.HelpWich( 'init', cfg.key );

		if ( options.open ) {
			w.HelpWich( 'open' );

			// An "open" sent before the chat frame is ready can be lost, which
			// leaves only the greeting. Ask again once the frame says it is ready.
			var onMessage = function ( event ) {
				if ( event.origin !== beaconOrigin || ! event.data || 'ready' !== event.data.type ) {
					return;
				}
				w.removeEventListener( 'message', onMessage );
				w.HelpWich( 'open' );
			};
			w.addEventListener( 'message', onMessage );
		}

		var script = d.createElement( 'script' );
		script.src = cfg.src;
		script.async = true;
		script.setAttribute( 'data-hw-beacon', cfg.key );
		script.onload = options.onLoad || null;
		script.onerror = function () {
			script.remove();
			if ( options.onError ) {
				options.onError();
			}
		};

		( d.head || d.body ).appendChild( script );
	}

	function saveOptIn() {
		var body = new URLSearchParams( {
			action: cfg.action,
			nonce: cfg.nonce,
		} );

		// keepalive: the save still finishes if the admin leaves the page.
		return fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			keepalive: true,
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		} ).catch( function () {} );
	}

	// Already agreed earlier: behave like the normal beacon snippet.
	if ( cfg.optedIn ) {
		loadBeacon( { open: false } );
		return;
	}

	var button = document.getElementById( 'wp-ulike-help-beacon' );
	if ( ! button ) {
		return;
	}

	var requested = false;

	function setBusy( busy ) {
		button.disabled = busy;
		button.setAttribute( 'aria-busy', busy ? 'true' : 'false' );
		button.classList.toggle( 'is-loading', busy );
	}

	button.addEventListener( 'click', function () {
		if ( requested ) {
			return;
		}
		requested = true;
		setBusy( true );
		saveOptIn();

		loadBeacon( {
			open: true,
			onLoad: function () {
				// The beacon draws its own launcher in the same spot.
				button.hidden = true;
			},
			onError: function () {
				requested = false;
				setBusy( false );
				if ( cfg.fallbackUrl ) {
					window.open( cfg.fallbackUrl, '_blank', 'noopener,noreferrer' );
				}
			},
		} );
	} );
} )();

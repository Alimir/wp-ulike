/**
 * Fresh-install setup wizard (vanilla JS).
 */
( function () {
	'use strict';

	var cfg = window.wpUlikeSetupWizard;
	if ( ! cfg ) {
		return;
	}

	var dialog = null;
	var current = 1;
	var total = 3;
	var lastFocus = null;
	var saving = false;

	function qs( selector, context ) {
		return ( context || document ).querySelector( selector );
	}

	function qsa( selector, context ) {
		return Array.prototype.slice.call( ( context || document ).querySelectorAll( selector ) );
	}

	function getSurfaces() {
		return qsa( 'input[name="surfaces[]"]:checked', dialog ).map( function ( input ) {
			return input.value;
		} );
	}

	function selectedValue( name ) {
		var input = qs( 'input[name="' + name + '"]:checked', dialog );
		return input ? input.value : '';
	}

	function showError( message ) {
		var error = qs( '[data-setup-error]', dialog );
		if ( ! error ) {
			return;
		}
		if ( ! message ) {
			error.hidden = true;
			error.textContent = '';
			return;
		}
		error.hidden = false;
		error.textContent = message;
	}

	function recapText() {
		var i18n = cfg.i18n;
		var places = getSurfaces().map( function ( key ) {
			return i18n.places[ key ] || key;
		} );
		if ( ! places.length ) {
			places = [ i18n.places.post ];
		}

		return i18n.recap
			.replace( '%1$s', places.join( i18n.listJoin ) )
			.replace( '%2$s', selectedValue( 'who_can_vote' ) === 'logged_in' ? i18n.whoLoggedIn : i18n.whoEveryone )
			.replace( '%3$s', i18n.after[ selectedValue( 'unlike_rule' ) ] || i18n.after.once )
			.replace( '%4$s', selectedValue( 'show_likers' ) === 'yes' ? i18n.likersOn : i18n.likersOff );
	}

	function updateRecap() {
		var recap = qs( '[data-setup-recap]', dialog );
		if ( recap ) {
			recap.textContent = recapText();
		}
	}

	function setStep( step ) {
		current = step;

		qsa( '[data-setup-panel]', dialog ).forEach( function ( panel ) {
			var active = String( panel.getAttribute( 'data-setup-panel' ) ) === String( step );
			panel.hidden = ! active;
			panel.classList.toggle( 'is-active', active );
		} );

		qsa( '[data-setup-step-label]', dialog ).forEach( function ( item ) {
			var index = parseInt( item.getAttribute( 'data-setup-step-label' ), 10 );
			item.classList.toggle( 'is-current', index === step );
			item.classList.toggle( 'is-done', index < step );
			item.setAttribute( 'aria-current', index === step ? 'step' : 'false' );
		} );

		var back = qs( '[data-setup-back]', dialog );
		var next = qs( '[data-setup-next]', dialog );
		if ( back ) {
			back.hidden = step === 1;
		}
		if ( next ) {
			next.textContent = step === total ? cfg.i18n.finish : cfg.i18n.continue;
			next.disabled = false;
		}

		showError( '' );
		updateRecap();
	}

	function focusFirst() {
		var panel = qs( '[data-setup-panel].is-active', dialog );
		var target = panel ? qs( 'input, button', panel ) : qs( '[data-setup-next]', dialog );
		if ( target ) {
			target.focus();
		}
	}

	function validateStep() {
		if ( current !== 1 ) {
			return true;
		}
		if ( getSurfaces().length ) {
			showError( '' );
			return true;
		}
		showError( cfg.i18n.needOne );
		return false;
	}

	function close() {
		if ( dialog ) {
			dialog.remove();
			dialog = null;
		}
		document.body.classList.remove( 'wp-ulike-setup-open' );
		document.removeEventListener( 'keydown', onKeydown );
		if ( lastFocus && typeof lastFocus.focus === 'function' ) {
			lastFocus.focus();
		}
	}

	function buildBody() {
		var body = new URLSearchParams();
		body.set( 'action', cfg.saveAction );
		body.set( 'nonce', cfg.nonce );
		body.set( 'who_can_vote', selectedValue( 'who_can_vote' ) || 'everyone' );
		body.set( 'unlike_rule', selectedValue( 'unlike_rule' ) || 'once' );
		body.set( 'show_likers', selectedValue( 'show_likers' ) || 'no' );
		getSurfaces().forEach( function ( value ) {
			body.append( 'surfaces[]', value );
		} );
		return body;
	}

	function persist( thenReload ) {
		if ( saving ) {
			return;
		}

		if ( ! getSurfaces().length ) {
			showError( cfg.i18n.needOne );
			setStep( 1 );
			return;
		}

		saving = true;
		var next = qs( '[data-setup-next]', dialog );
		if ( next ) {
			next.disabled = true;
			next.textContent = cfg.i18n.saving;
		}

		fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: buildBody().toString(),
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				if ( ! data || ! data.success ) {
					throw new Error( 'save' );
				}

				if ( ! thenReload ) {
					close();
					return;
				}

				var success = qs( '[data-setup-success]', dialog );
				var successText = qs( '[data-setup-success-text]', dialog );
				var form = qs( '.wp-ulike-setup__form', dialog );
				var recap = qs( '[data-setup-recap]', dialog );
				var footer = qs( '.wp-ulike-setup__footer', dialog );
				var steps = qs( '.wp-ulike-setup__steps', dialog );

				if ( form ) {
					form.hidden = true;
				}
				if ( recap ) {
					recap.hidden = true;
				}
				if ( footer ) {
					footer.hidden = true;
				}
				if ( steps ) {
					steps.hidden = true;
				}
				if ( successText ) {
					successText.textContent = recapText() + ' ' + cfg.i18n.tryNext;
				}
				if ( success ) {
					success.hidden = false;
				}

				window.setTimeout( function () {
					if ( window.location.href.indexOf( 'page=wp-ulike-settings' ) !== -1 ) {
						window.location.reload();
						return;
					}
					window.location.href = cfg.afterUrl;
				}, 900 );
			} )
			.catch( function () {
				saving = false;
				if ( next ) {
					next.disabled = false;
					next.textContent = current === total ? cfg.i18n.finish : cfg.i18n.continue;
				}
				showError( cfg.i18n.error );
			} );
	}

	function onKeydown( event ) {
		if ( event.key === 'Escape' ) {
			event.preventDefault();
			persist( false );
			return;
		}

		if ( event.key !== 'Tab' || ! dialog ) {
			return;
		}

		var focusable = qsa( 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])', dialog ).filter( function ( el ) {
			return ! el.hidden && ! el.disabled && el.closest( '[hidden]' ) === null;
		} );

		if ( ! focusable.length ) {
			return;
		}

		var first = focusable[ 0 ];
		var last = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	function bind() {
		qsa( '[data-setup-dismiss]', dialog ).forEach( function ( button ) {
			button.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				persist( false );
			} );
		} );

		qsa( '[data-setup-step-label]', dialog ).forEach( function ( item ) {
			item.setAttribute( 'role', 'button' );
			item.setAttribute( 'tabindex', '0' );
			item.addEventListener( 'click', function () {
				var index = parseInt( item.getAttribute( 'data-setup-step-label' ), 10 );
				if ( index < current || item.classList.contains( 'is-done' ) ) {
					setStep( index );
					focusFirst();
				}
			} );
			item.addEventListener( 'keydown', function ( event ) {
				if ( event.key === 'Enter' || event.key === ' ' ) {
					event.preventDefault();
					item.click();
				}
			} );
		} );

		var back = qs( '[data-setup-back]', dialog );
		var next = qs( '[data-setup-next]', dialog );

		if ( back ) {
			back.addEventListener( 'click', function () {
				if ( current > 1 ) {
					setStep( current - 1 );
					focusFirst();
				}
			} );
		}

		if ( next ) {
			next.addEventListener( 'click', function () {
				if ( ! validateStep() ) {
					return;
				}
				if ( current < total ) {
					setStep( current + 1 );
					focusFirst();
					return;
				}
				persist( true );
			} );
		}

		qsa( 'input', dialog ).forEach( function ( input ) {
			input.addEventListener( 'change', function () {
				if ( current === 1 ) {
					showError( getSurfaces().length ? '' : cfg.i18n.needOne );
				}
				updateRecap();
			} );
		} );
	}

	function open() {
		var source = qs( '#wp-ulike-setup-wizard-root .wp-ulike-setup' );
		if ( ! source ) {
			return;
		}

		lastFocus = document.activeElement;
		dialog = source.cloneNode( true );
		document.body.appendChild( dialog );
		document.body.classList.add( 'wp-ulike-setup-open' );

		bind();
		setStep( 1 );
		document.addEventListener( 'keydown', onKeydown );
		focusFirst();
	}

	function init() {
		if ( ! qs( '#wp-ulike-setup-wizard-root' ) ) {
			return;
		}
		window.setTimeout( open, 80 );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();

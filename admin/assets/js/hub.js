/**
 * ULike Hub: heart in the admin bar with one hint and the latest activity.
 *
 * Loads on first open only, so page loads never query votes.
 * Opening it marks likes as seen for this user.
 */
( function () {
	'use strict';

	var cfg = window.wpUlikeHub;
	var node = document.getElementById( 'wp-admin-bar-wp-ulike-hub' );

	if ( ! cfg || ! node ) {
		return;
	}

	var panel = node.querySelector( '.wp-ulike-hub-panel' );
	var countEl = node.querySelector( '.wp-ulike-hub__count' );
	var loadedAt = 0;
	var loading = false;
	var REFRESH_AFTER = 60000;

	if ( ! panel ) {
		return;
	}

	function el( tag, className, text ) {
		var element = document.createElement( tag );
		if ( className ) {
			element.className = className;
		}
		if ( text ) {
			element.textContent = text;
		}
		return element;
	}

	function post( action, extra ) {
		var body = new URLSearchParams( { action: action, nonce: cfg.nonce } );
		Object.keys( extra || {} ).forEach( function ( key ) {
			body.set( key, extra[ key ] );
		} );

		return fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		} ).then( function ( response ) {
			return response.json();
		} );
	}

	function clearBadge() {
		node.classList.remove( 'has-new' );
		if ( countEl ) {
			countEl.textContent = '0';
			countEl.classList.add( 'is-empty' );
		}
	}

	function header( today ) {
		var wrap = el( 'div', 'wp-ulike-hub-panel__header' );
		wrap.appendChild( el( 'span', 'wp-ulike-hub-panel__title', cfg.i18n.title ) );
		if ( today ) {
			wrap.appendChild( el( 'span', 'wp-ulike-hub-panel__today', today ) );
		}
		return wrap;
	}

	function skeleton() {
		panel.textContent = '';
		panel.setAttribute( 'aria-busy', 'true' );
		panel.appendChild( header( '' ) );

		var list = el( 'ul', 'wp-ulike-hub-panel__list' );
		for ( var i = 0; i < 3; i++ ) {
			var row = el( 'li', 'wp-ulike-hub-panel__row is-skeleton' );
			row.appendChild( el( 'span', 'wp-ulike-hub-panel__avatar' ) );
			var lines = el( 'span', 'wp-ulike-hub-panel__body' );
			lines.appendChild( el( 'span', 'wp-ulike-hub-panel__line' ) );
			lines.appendChild( el( 'span', 'wp-ulike-hub-panel__line is-short' ) );
			row.appendChild( lines );
			list.appendChild( row );
		}
		panel.appendChild( list );
		panel.appendChild( el( 'span', 'screen-reader-text', cfg.i18n.loading ) );
	}

	function hintCard( hint ) {
		var card = el( 'div', 'wp-ulike-hub-hint is-' + ( hint.icon || 'tip' ) );
		card.setAttribute( 'data-hint', hint.id );

		card.appendChild( el( 'span', 'wp-ulike-hub-hint__icon' ) );

		var body = el( 'div', 'wp-ulike-hub-hint__body' );
		body.appendChild( el( 'p', 'wp-ulike-hub-hint__text', hint.text ) );
		if ( hint.action ) {
			var action = el( 'a', 'wp-ulike-hub-hint__action', hint.action.label );
			action.href = hint.action.url;
			body.appendChild( action );
		}
		card.appendChild( body );

		var close = el( 'button', 'wp-ulike-hub-hint__close' );
		close.type = 'button';
		close.setAttribute( 'aria-label', cfg.i18n.dismiss );
		close.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			event.stopPropagation();
			card.classList.add( 'is-leaving' );
			post( cfg.dismissAction, { hint: hint.id } )
				.then( function ( json ) {
					var next = json && json.success && json.data ? json.data.hint : null;
					if ( next ) {
						card.replaceWith( hintCard( next ) );
					} else {
						card.remove();
					}
				} )
				.catch( function () {
					card.remove();
				} );
		} );
		card.appendChild( close );

		return card;
	}

	function avatar( item ) {
		var wrap = el( 'span', 'wp-ulike-hub-panel__avatar' + ( item.avatar ? '' : ' is-guest' ) );
		if ( item.avatar ) {
			var img = el( 'img' );
			img.src = item.avatar;
			img.alt = '';
			img.width = 32;
			img.height = 32;
			img.loading = 'lazy';
			wrap.appendChild( img );
		}
		// Reaction emoji or star score when there is one, otherwise the heart.
		var kind = item.badge
			? el( 'span', 'wp-ulike-hub-panel__kind is-badge', item.badge )
			: el( 'span', 'wp-ulike-hub-panel__kind is-' + ( 'dislike' === item.kind ? 'dislike' : 'like' ) );
		kind.setAttribute( 'aria-hidden', 'true' );
		wrap.appendChild( kind );
		return wrap;
	}

	/**
	 * Fill a translated pattern ("%1$s liked %2$s") with the person and the
	 * item, in whatever order the language puts them.
	 */
	function sentence( item ) {
		var text = el( 'span', 'wp-ulike-hub-panel__text' );
		var parts = String( item.text || '' ).split( /(%[12]\$s)/ );

		parts.forEach( function ( part ) {
			if ( '%1$s' === part ) {
				text.appendChild( el( 'strong', 'wp-ulike-hub-panel__name', item.name ) );
			} else if ( '%2$s' === part ) {
				text.appendChild( el( 'span', 'wp-ulike-hub-panel__target', item.title ) );
			} else if ( part ) {
				text.appendChild( document.createTextNode( part ) );
			}
		} );

		return text;
	}

	function row( item ) {
		var li = el( 'li', 'wp-ulike-hub-panel__row' + ( item.isNew ? ' is-new' : '' ) );
		var link = el( item.url ? 'a' : 'div', 'wp-ulike-hub-panel__link' );
		if ( item.url ) {
			link.href = item.url;
		}

		link.appendChild( avatar( item ) );

		var body = el( 'span', 'wp-ulike-hub-panel__body' );
		body.appendChild( sentence( item ) );

		var meta = el( 'span', 'wp-ulike-hub-panel__meta' );
		if ( item.isNew ) {
			meta.appendChild( el( 'span', 'wp-ulike-hub-panel__new', cfg.i18n.new ) );
		}
		meta.appendChild( el( 'span', '', item.time ) );
		body.appendChild( meta );

		link.appendChild( body );
		li.appendChild( link );
		return li;
	}

	function footer( data ) {
		var wrap = el( 'div', 'wp-ulike-hub-panel__footer' );
		var logs = el( 'a', 'wp-ulike-hub-panel__button', cfg.i18n.logs );
		logs.href = data.logsUrl;
		var stats = el( 'a', 'wp-ulike-hub-panel__button is-primary', cfg.i18n.stats );
		stats.href = data.statsUrl;
		wrap.appendChild( logs );
		wrap.appendChild( stats );
		return wrap;
	}

	function render( data ) {
		panel.textContent = '';
		panel.removeAttribute( 'aria-busy' );
		panel.appendChild( header( data.today ) );

		if ( data.hint ) {
			panel.appendChild( hintCard( data.hint ) );
		}

		if ( data.items && data.items.length ) {
			panel.appendChild( el( 'p', 'wp-ulike-hub-panel__label', cfg.i18n.latest ) );
			var list = el( 'ul', 'wp-ulike-hub-panel__list' );
			data.items.forEach( function ( item ) {
				list.appendChild( row( item ) );
			} );
			panel.appendChild( list );
		} else {
			var empty = el( 'div', 'wp-ulike-hub-panel__empty' );
			empty.appendChild( el( 'span', 'wp-ulike-hub-panel__empty-icon' ) );
			empty.appendChild( el( 'p', '', cfg.i18n.empty ) );
			panel.appendChild( empty );
		}

		panel.appendChild( footer( data ) );
	}

	function renderError() {
		panel.textContent = '';
		panel.removeAttribute( 'aria-busy' );
		panel.appendChild( header( '' ) );
		panel.appendChild( el( 'p', 'wp-ulike-hub-panel__empty', cfg.i18n.error ) );
	}

	function load() {
		if ( loading || ( loadedAt && Date.now() - loadedAt < REFRESH_AFTER ) ) {
			return;
		}

		loading = true;
		if ( ! loadedAt ) {
			skeleton();
		}

		post( cfg.panelAction )
			.then( function ( json ) {
				if ( ! json || ! json.success ) {
					throw new Error( 'load' );
				}
				loadedAt = Date.now();
				render( json.data );
				clearBadge();
			} )
			.catch( function () {
				loadedAt = 0;
				renderError();
			} )
			.then( function () {
				loading = false;
			} );
	}

	// Hover, keyboard focus, or a tap on touch screens opens the menu.
	[ 'mouseenter', 'focusin', 'touchstart' ].forEach( function ( type ) {
		node.addEventListener( type, load, { passive: true } );
	} );
} )();

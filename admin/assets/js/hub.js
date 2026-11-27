/**
 * ULike Hub: heart in the admin bar with one tip, team news, and the latest activity.
 *
 * Page loads never query votes. The last panel is kept for the browser
 * session and shown at once; it is fetched again only when the vote counter
 * has moved or it is a few minutes old. Fetching is read-only, so it starts
 * as soon as the pointer reaches the heart. Votes are marked as seen only
 * when the panel is actually opened.
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
	var labelEl = node.querySelector( '.wp-ulike-hub__label' );

	if ( ! panel ) {
		return;
	}

	var MAX_AGE = 5 * 60000;
	// A pointer crossing the admin bar should not clear the badge.
	var OPEN_DELAY = 200;

	var total = parseInt( cfg.total, 10 ) || 0;
	var unseen = parseInt( cfg.unseen, 10 ) || 0;
	var data = null;
	var loadedAt = 0;
	var shownFrom = -1;
	var shownAt = 0;
	var request = null;
	var openTimer = 0;
	var rtf = null;

	try {
		rtf = new Intl.RelativeTimeFormat( cfg.locale || undefined, { numeric: 'auto', style: 'long' } );
	} catch ( e ) {
		rtf = null;
	}

	/* Session copy ------------------------------------------------------ */

	function restore() {
		try {
			var saved = JSON.parse( window.sessionStorage.getItem( cfg.cacheKey ) || 'null' );
			if ( saved && saved.data ) {
				data = saved.data;
				loadedAt = saved.at || 0;
				// Seen since it was saved, maybe on another screen.
				if ( ! unseen ) {
					clearNew();
				}
			}
		} catch ( e ) {}
	}

	function clearNew() {
		( data.items || [] ).forEach( function ( item ) {
			item.isNew = false;
		} );
		data.badge = { unseen: 0 };
	}

	function persist() {
		try {
			window.sessionStorage.setItem( cfg.cacheKey, JSON.stringify( { at: loadedAt, data: data } ) );
		} catch ( e ) {}
	}

	function isFresh() {
		return !! data && data.total === total && ( parseInt( data.badge && data.badge.unseen, 10 ) || 0 ) === unseen && Date.now() - loadedAt < MAX_AGE;
	}

	/* Helpers ----------------------------------------------------------- */

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

	function safeUrl( url ) {
		return typeof url === 'string' && /^https?:\/\//i.test( url ) ? url : '';
	}

	function link( className, text, url ) {
		var a = el( 'a', className, text );
		a.href = url;
		try {
			if ( new URL( url ).origin !== window.location.origin ) {
				a.target = '_blank';
				a.rel = 'noopener noreferrer';
			}
		} catch ( e ) {}
		return a;
	}

	function post( action, extra, keepalive ) {
		var body = new URLSearchParams( { action: action, nonce: cfg.nonce, screen: cfg.screen } );
		Object.keys( extra || {} ).forEach( function ( key ) {
			body.set( key, extra[ key ] );
		} );

		return fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			keepalive: !! keepalive,
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		} ).then( function ( response ) {
			return response.json();
		} );
	}

	function ago( ts ) {
		var seconds = Math.round( ( Date.now() / 1000 ) - ts );
		if ( ! ts || seconds < 60 ) {
			return cfg.i18n.justNow;
		}
		if ( ! rtf ) {
			return '';
		}
		var units = [ [ 'year', 31536000 ], [ 'month', 2592000 ], [ 'week', 604800 ], [ 'day', 86400 ], [ 'hour', 3600 ], [ 'minute', 60 ] ];
		for ( var i = 0; i < units.length; i++ ) {
			if ( seconds >= units[ i ][ 1 ] ) {
				return rtf.format( -Math.floor( seconds / units[ i ][ 1 ] ), units[ i ][ 0 ] );
			}
		}
		return cfg.i18n.justNow;
	}

	/**
	 * @param {{unseen: number, count: string, label: string}} badge From the server.
	 */
	function setBadge( badge ) {
		if ( ! badge ) {
			return;
		}
		unseen = parseInt( badge.unseen, 10 ) || 0;
		node.classList.toggle( 'has-new', unseen > 0 );
		if ( countEl ) {
			countEl.textContent = badge.count;
			countEl.classList.toggle( 'is-empty', ! unseen );
		}
		if ( labelEl ) {
			labelEl.textContent = badge.label;
		}
	}

	/* Pieces ------------------------------------------------------------ */

	function header( today, trend, quiet ) {
		var wrap = el( 'div', 'wp-ulike-hub-panel__header' );
		wrap.appendChild( el( 'span', 'wp-ulike-hub-panel__title', cfg.i18n.title ) );
		if ( today ) {
			wrap.appendChild( el( 'span', 'wp-ulike-hub-panel__today' + ( trend ? ' is-' + trend : '' ) + ( quiet ? ' is-quiet' : '' ), today ) );
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

	function closeButton( id, onDone ) {
		var close = el( 'button', 'wp-ulike-hub-close' );
		close.type = 'button';
		close.setAttribute( 'aria-label', cfg.i18n.dismiss );
		close.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			event.stopPropagation();
			dismiss( id, false, onDone );
		} );
		return close;
	}

	function dismiss( id, done, onDone ) {
		var target = panel.querySelector( '[data-dismiss="' + id + '"]' );
		if ( target ) {
			target.classList.add( 'is-leaving' );
		}
		post( cfg.dismissAction, done ? { hint: id, done: 1 } : { hint: id }, done )
			.then( function ( json ) {
				if ( json && json.success && data ) {
					data.hint = json.data.hint;
					data.news = json.data.news;
					persist();
				}
			} )
			.catch( function () {} )
			.then( function () {
				if ( onDone ) {
					onDone();
				}
			} );
	}

	function refreshTop() {
		var top = panel.querySelector( '.wp-ulike-hub-panel__top' );
		if ( top && data ) {
			top.replaceWith( topSection( data ) );
		}
	}

	function hintCard( hint ) {
		var card = el( 'div', 'wp-ulike-hub-hint is-' + ( hint.icon || 'tip' ) );
		card.setAttribute( 'data-dismiss', hint.id );

		card.appendChild( el( 'span', 'wp-ulike-hub-hint__icon' ) );

		var body = el( 'div', 'wp-ulike-hub-hint__body' );
		body.appendChild( el( 'p', 'wp-ulike-hub-hint__text', hint.text ) );
		var url = hint.action && safeUrl( hint.action.url );
		if ( url ) {
			var action = link( 'wp-ulike-hub-hint__action', hint.action.label, url );
			if ( hint.done ) {
				action.addEventListener( 'click', function () {
					dismiss( hint.id, true, refreshTop );
				} );
			}
			body.appendChild( action );
		}
		card.appendChild( body );
		card.appendChild( closeButton( hint.id, refreshTop ) );

		return card;
	}

	function newsRow( news ) {
		var row = el( 'div', 'wp-ulike-hub-news' );
		row.setAttribute( 'data-dismiss', news.id );

		var ring = el( 'span', 'wp-ulike-hub-news__ring' );
		ring.setAttribute( 'aria-hidden', 'true' );
		ring.appendChild( el( 'span', 'wp-ulike-hub-news__logo' ) );
		row.appendChild( ring );

		var body = el( 'div', 'wp-ulike-hub-news__body' );
		var meta = el( 'p', 'wp-ulike-hub-news__meta' );
		meta.appendChild( el( 'strong', '', cfg.i18n.team ) );
		var when = news.time ? ago( news.time ) : '';
		if ( when ) {
			meta.appendChild( el( 'span', 'wp-ulike-hub-news__time', when ) );
		}
		body.appendChild( meta );

		if ( news.title ) {
			body.appendChild( el( 'p', 'wp-ulike-hub-news__title', news.title ) );
		}
		if ( news.text ) {
			body.appendChild( el( 'p', 'wp-ulike-hub-news__text', news.text ) );
		}
		var url = news.action && safeUrl( news.action.url );
		if ( url ) {
			body.appendChild( link( 'wp-ulike-hub-news__action', news.action.label, url ) );
		}
		row.appendChild( body );
		row.appendChild( closeButton( news.id, refreshTop ) );
		row.setAttribute( 'aria-label', cfg.i18n.fromTeam );

		return row;
	}

	function topSection( payload ) {
		var wrap = el( 'div', 'wp-ulike-hub-panel__top' );
		if ( payload.news ) {
			wrap.appendChild( newsRow( payload.news ) );
		}
		if ( payload.hint ) {
			wrap.appendChild( hintCard( payload.hint ) );
		}
		return wrap;
	}

	function face( url ) {
		var wrap = el( 'span', 'wp-ulike-hub-panel__face' + ( url ? '' : ' is-guest' ) );
		var src = safeUrl( url );
		if ( src ) {
			var img = el( 'img' );
			img.src = src;
			img.alt = '';
			img.width = 32;
			img.height = 32;
			img.loading = 'lazy';
			img.decoding = 'async';
			wrap.appendChild( img );
		}
		return wrap;
	}

	function avatar( item ) {
		var wrap = el( 'span', 'wp-ulike-hub-panel__avatar' + ( item.stack ? ' is-stack' : '' ) );
		// Several people: two faces, overlapped.
		if ( item.stack ) {
			wrap.appendChild( face( item.avatar2 ) );
		}
		wrap.appendChild( face( item.avatar ) );

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
		var url = safeUrl( item.url );
		var wrap = url ? link( 'wp-ulike-hub-panel__link', '', url ) : el( 'div', 'wp-ulike-hub-panel__link' );

		wrap.appendChild( avatar( item ) );

		var body = el( 'span', 'wp-ulike-hub-panel__body' );
		body.appendChild( sentence( item ) );
		body.appendChild( el( 'span', 'wp-ulike-hub-panel__meta', ago( item.ts ) ) );
		wrap.appendChild( body );

		var thumb = safeUrl( item.thumb );
		if ( thumb ) {
			var img = el( 'img', 'wp-ulike-hub-panel__thumb' );
			img.src = thumb;
			img.alt = '';
			img.width = 40;
			img.height = 40;
			img.loading = 'lazy';
			img.decoding = 'async';
			wrap.appendChild( img );
		}

		li.appendChild( wrap );
		return li;
	}

	/**
	 * Group rows the way activity feeds do: new, today, this week, earlier.
	 */
	function sections( items ) {
		var startOfDay = new Date();
		startOfDay.setHours( 0, 0, 0, 0 );
		var today = startOfDay.getTime() / 1000;
		var week = today - 6 * 86400;
		var order = [ 'new', 'today', 'week', 'earlier' ];
		var groups = { new: [], today: [], week: [], earlier: [] };

		items.forEach( function ( item ) {
			if ( item.isNew ) {
				groups.new.push( item );
			} else if ( item.ts >= today ) {
				groups.today.push( item );
			} else if ( item.ts >= week ) {
				groups.week.push( item );
			} else {
				groups.earlier.push( item );
			}
		} );

		var fragment = document.createDocumentFragment();
		order.forEach( function ( key ) {
			if ( ! groups[ key ].length ) {
				return;
			}
			fragment.appendChild( el( 'p', 'wp-ulike-hub-panel__label' + ( 'new' === key ? ' is-new' : '' ), cfg.i18n[ key ] ) );
			var list = el( 'ul', 'wp-ulike-hub-panel__list' );
			groups[ key ].forEach( function ( item ) {
				list.appendChild( row( item ) );
			} );
			fragment.appendChild( list );
		} );

		return fragment;
	}

	function footer( payload ) {
		var wrap = el( 'div', 'wp-ulike-hub-panel__footer' );
		// No votes yet: the logs would be empty.
		if ( payload.items && payload.items.length ) {
			var logs = el( 'a', 'wp-ulike-hub-panel__button', cfg.i18n.logs );
			logs.href = payload.logsUrl;
			wrap.appendChild( logs );
		}
		var stats = el( 'a', 'wp-ulike-hub-panel__button is-primary', cfg.i18n.stats );
		stats.href = payload.statsUrl;
		wrap.appendChild( stats );
		return wrap;
	}

	function render( payload ) {
		shownFrom = loadedAt;
		shownAt = Date.now();
		panel.textContent = '';
		panel.removeAttribute( 'aria-busy' );
		panel.appendChild( header( payload.today, payload.trend, payload.quiet ) );
		panel.appendChild( topSection( payload ) );

		if ( payload.items && payload.items.length ) {
			var feed = el( 'div', 'wp-ulike-hub-panel__feed' );
			feed.appendChild( sections( payload.items ) );
			panel.appendChild( feed );
		} else {
			var empty = el( 'div', 'wp-ulike-hub-panel__empty' );
			empty.appendChild( el( 'span', 'wp-ulike-hub-panel__empty-icon' ) );
			empty.appendChild( el( 'p', '', cfg.i18n.empty ) );
			panel.appendChild( empty );
		}

		panel.appendChild( footer( payload ) );
	}

	function renderError() {
		panel.textContent = '';
		panel.removeAttribute( 'aria-busy' );
		panel.appendChild( header( '' ) );
		panel.appendChild( el( 'p', 'wp-ulike-hub-panel__empty', cfg.i18n.error ) );
	}

	/* Loading ----------------------------------------------------------- */

	function fetchPanel() {
		if ( request ) {
			return request;
		}

		request = post( cfg.panelAction )
			.then( function ( json ) {
				if ( ! json || ! json.success ) {
					throw new Error( 'load' );
				}
				data = json.data;
				loadedAt = Date.now();
				total = Math.max( total, parseInt( data.total, 10 ) || 0 );
				persist();
				render( data );
				setBadge( data.badge );
			} )
			.catch( function () {
				if ( ! data ) {
					renderError();
				}
			} )
			.then( function () {
				request = null;
			} );

		return request;
	}

	function prefetch() {
		if ( ! isFresh() ) {
			fetchPanel();
		}
	}

	function markSeen() {
		if ( ! unseen || ! data ) {
			return;
		}
		post( cfg.seenAction, { total: data.total } )
			.then( function ( json ) {
				if ( json && json.success ) {
					setBadge( json.data.badge );
					// The open panel keeps its "New" rows; the saved copy does not.
					if ( data && ! unseen ) {
						clearNew();
						persist();
					}
				}
			} )
			.catch( function () {} );
	}

	/**
	 * Draw the panel, without redrawing one that is already up to date:
	 * that would drop keyboard focus and the scroll position.
	 */
	function show() {
		if ( ! data ) {
			if ( ! panel.firstChild ) {
				skeleton();
			}
		} else if ( shownFrom !== loadedAt || Date.now() - shownAt > 60000 ) {
			render( data );
		}
	}

	function open() {
		clearTimeout( openTimer );
		show();

		if ( isFresh() ) {
			markSeen();
		} else {
			fetchPanel().then( markSeen );
		}
	}

	restore();

	// Keyboard focus and taps open the menu on purpose; a hover has to rest first.
	node.addEventListener( 'focusin', function ( event ) {
		// Moving between links inside the open panel is not a new open.
		if ( ! panel.contains( event.target ) ) {
			open();
		}
	} );
	node.addEventListener( 'touchstart', open, { passive: true } );
	node.addEventListener( 'mouseenter', function () {
		// The admin bar may show the menu before the delay ends; never show it empty.
		show();
		prefetch();
		clearTimeout( openTimer );
		openTimer = setTimeout( open, OPEN_DELAY );
	} );
	node.addEventListener( 'mouseleave', function () {
		clearTimeout( openTimer );
	} );

	// Admin screens already run WordPress Heartbeat; the badge rides along.
	// Pages without Heartbeat (most front-end views) refresh on page load.
	if ( window.jQuery ) {
		window.jQuery( document )
			.on( 'heartbeat-send', function ( event, payload ) {
				payload.wp_ulike_hub = 1;
			} )
			.on( 'heartbeat-tick', function ( event, payload ) {
				var badge = payload && payload.wp_ulike_hub;
				if ( ! badge ) {
					return;
				}
				total = parseInt( badge.total, 10 ) || total;
				setBadge( badge );
			} );
	}
} )();

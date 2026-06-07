/**
 * Welcome pointer on the WP ULike admin menu (vanilla JS).
 */
( function () {
	'use strict';

	var cfg = window.wpUlikeActivationPointer;
	if ( ! cfg || ! cfg.menuSelector ) {
		return;
	}

	var popover = null;

	function qs( selector, root ) {
		return ( root || document ).querySelector( selector );
	}

	function dismissPointer() {
		if ( ! cfg.ajaxUrl || ! cfg.nonce ) {
			return;
		}

		var body = new URLSearchParams( {
			action: cfg.action,
			nonce: cfg.nonce,
		} );

		fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		} );
	}

	function closePointer() {
		if ( popover ) {
			popover.remove();
			popover = null;
		}

		document.body.classList.remove( 'wp-ulike-activation-pointer-open' );
		document.removeEventListener( 'keydown', onKeydown );
		window.removeEventListener( 'resize', positionPointer );
	}

	function onKeydown( event ) {
		if ( event.key === 'Escape' ) {
			dismissPointer();
			closePointer();
		}
	}

	function positionPointer() {
		if ( ! popover ) {
			return;
		}

		var menu = qs( cfg.menuSelector );
		if ( ! menu ) {
			return;
		}

		var rect = menu.getBoundingClientRect();
		var panel = qs( '.wp-ulike-activation-pointer__panel', popover );
		var rtl = document.documentElement.dir === 'rtl';
		var gap = 12;
		var top = rect.top + ( rect.height / 2 );

		popover.style.top = Math.max( 12, top ) + 'px';
		popover.style.transform = 'translateY(-50%)';

		if ( rtl ) {
			popover.style.left = 'auto';
			popover.style.right = Math.max( 12, window.innerWidth - rect.left + gap ) + 'px';
			popover.classList.add( 'is-rtl' );
		} else {
			popover.style.right = 'auto';
			popover.style.left = Math.min( window.innerWidth - 320, rect.right + gap ) + 'px';
			popover.classList.remove( 'is-rtl' );
		}

		if ( panel ) {
			panel.style.maxHeight = Math.max( 200, window.innerHeight - 24 ) + 'px';
		}
	}

	function bindDismiss( root ) {
		var dismissButtons = root.querySelectorAll( '.wp-ulike-activation-pointer__dismiss, .wp-ulike-activation-pointer__close' );

		dismissButtons.forEach( function ( button ) {
			button.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				dismissPointer();
				closePointer();
			} );
		} );
	}

	function openPointer() {
		var menu = qs( cfg.menuSelector );
		var source = qs( '#wp-ulike-activation-pointer-template .wp-ulike-activation-pointer__panel' );

		if ( ! menu || ! source ) {
			return;
		}

		closePointer();

		popover = document.createElement( 'div' );
		popover.className = 'wp-ulike-activation-pointer';
		popover.setAttribute( 'role', 'dialog' );
		popover.setAttribute( 'aria-modal', 'false' );

		var panel = source.cloneNode( true );
		var title = panel.querySelector( '.wp-ulike-activation-pointer__title' );

		if ( title ) {
			title.id = 'wp-ulike-activation-pointer-title';
			popover.setAttribute( 'aria-labelledby', title.id );
		}

		popover.appendChild( panel );
		document.body.appendChild( popover );
		document.body.classList.add( 'wp-ulike-activation-pointer-open' );

		bindDismiss( popover );
		positionPointer();

		document.addEventListener( 'keydown', onKeydown );
		window.addEventListener( 'resize', positionPointer );
	}

	function init() {
		// Wait for the admin menu markup (folded menus, async paint).
		window.setTimeout( openPointer, 150 );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();

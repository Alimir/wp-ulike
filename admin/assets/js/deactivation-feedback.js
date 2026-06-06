/**
 * Deactivation feedback modal (Plugins screen).
 */
( function () {
	'use strict';

	var cfg = window.wpUlikeDeactivationFeedback;
	if ( ! cfg ) {
		return;
	}

	var deactivateUrl = '';
	var overlay = null;
	var onKeydown = null;

	function qs( selector, root ) {
		return ( root || document ).querySelector( selector );
	}

	function qsa( selector, root ) {
		return Array.prototype.slice.call( ( root || document ).querySelectorAll( selector ) );
	}

	function getPluginRow() {
		var list = qs( '#the-list' );
		if ( ! list ) {
			return null;
		}

		if ( cfg.pluginFile ) {
			var byPlugin = list.querySelector( '[data-plugin="' + cfg.pluginFile + '"]' );
			if ( byPlugin ) {
				return byPlugin;
			}
		}

		return list.querySelector( '[data-slug="' + cfg.slug + '"]' );
	}

	function isDeactivateClick( target ) {
		if ( ! target || ! target.closest ) {
			return false;
		}

		var row = getPluginRow();
		if ( ! row ) {
			return false;
		}

		var inRow = target.closest( 'tr' );
		if ( ! inRow || inRow !== row ) {
			return false;
		}

		return !! target.closest( 'span.deactivate' ) || target.id === 'deactivate-' + cfg.slug;
	}

	function setBodyModalOpen( open ) {
		document.body.classList.toggle( 'wp-ulike-deactivation-modal-open', open );
	}

	function closeModal() {
		if ( overlay ) {
			overlay.remove();
			overlay = null;
		}

		if ( onKeydown ) {
			document.removeEventListener( 'keydown', onKeydown );
			onKeydown = null;
		}

		setBodyModalOpen( false );
		deactivateUrl = '';
	}

	/** Run deactivate link; WordPress redirects back to plugins.php afterward. */
	function finishDeactivation() {
		window.location.href = deactivateUrl || cfg.pluginsUrl;
	}

	function sendFeedback( reasonKey, details ) {
		var body = new URLSearchParams( {
			action: 'wp_ulike_deactivation_feedback',
			nonce: cfg.nonce,
			reason_key: reasonKey,
			details: details || '',
		} );

		return fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		} )
			.then( function ( response ) {
				return response.json().catch( function () {
					return {};
				} );
			} )
			.finally( function () {
				finishDeactivation();
			} );
	}

	function syncDetailsFields( form ) {
		var checked = form.querySelector( 'input[name="reason_key"]:checked' );
		var reason = checked ? checked.value : '';

		qsa( '.wp-ulike-deactivate-feedback-details', form ).forEach( function ( wrap ) {
			var show = wrap.getAttribute( 'data-reason' ) === reason;
			wrap.hidden = ! show;
			if ( ! show ) {
				var input = wrap.querySelector( 'input' );
				if ( input ) {
					input.value = '';
				}
			}
		} );

		var hint = form.querySelector( '.wp-ulike-deactivate-feedback-context' );
		if ( hint ) {
			hint.hidden = reason !== 'not_working';
		}

		var reasonError = form.querySelector( '.wp-ulike-deactivate-feedback-reason-error' );
		if ( reasonError && reason ) {
			reasonError.hidden = true;
		}
	}

	function setButtonBusy( button, busy ) {
		if ( ! button ) {
			return;
		}

		button.disabled = busy;
		button.setAttribute( 'aria-busy', busy ? 'true' : 'false' );

		var spinner = button.querySelector( '.spinner' );
		if ( busy && ! spinner ) {
			spinner = document.createElement( 'span' );
			spinner.className = 'spinner is-active';
			spinner.setAttribute( 'aria-hidden', 'true' );
			button.insertBefore( spinner, button.firstChild );
		} else if ( ! busy && spinner ) {
			spinner.remove();
		}
	}

	function openModal( event ) {
		var source = qs( '#wp-ulike-deactivate-feedback-dialog-wrapper .wp-ulike-deactivate-feedback' );
		if ( ! source ) {
			return;
		}

		event.preventDefault();

		var link = event.currentTarget || event.target;
		if ( link && link.closest ) {
			link = link.closest( 'a' ) || link;
		}

		var pendingDeactivateUrl = link && link.getAttribute ? link.getAttribute( 'href' ) || '' : '';

		closeModal();
		deactivateUrl = pendingDeactivateUrl;

		overlay = document.createElement( 'div' );
		overlay.id = 'wp-ulike-deactivate-feedback-modal';
		overlay.className = 'wp-ulike-deactivate-feedback-modal';
		overlay.setAttribute( 'role', 'dialog' );
		overlay.setAttribute( 'aria-modal', 'true' );

		var dialog = document.createElement( 'div' );
		dialog.className = 'wp-ulike-deactivate-feedback-modal__dialog';

		var panel = source.cloneNode( true );
		var title = panel.querySelector( '.wp-ulike-deactivate-feedback__title' );
		if ( title ) {
			title.id = 'wp-ulike-deactivate-feedback-title';
			overlay.setAttribute( 'aria-labelledby', title.id );
		}

		dialog.appendChild( panel );

		var footer = document.createElement( 'div' );
		footer.className = 'wp-ulike-deactivate-feedback-modal__footer';
		footer.innerHTML =
			'<div class="wp-ulike-deactivate-feedback-modal__primary">' +
				'<button type="button" class="button button-primary wp-ulike-deactivate-feedback-submit">' +
					cfg.i18n.submit +
				'</button>' +
			'</div>' +
			'<div class="wp-ulike-deactivate-feedback-modal__secondary">' +
				'<button type="button" class="button-link wp-ulike-deactivate-feedback-skip">' +
					cfg.i18n.skip +
				'</button>' +
			'</div>';

		dialog.appendChild( footer );
		overlay.appendChild( dialog );
		document.body.appendChild( overlay );
		setBodyModalOpen( true );

		var form = overlay.querySelector( '#wp-ulike-deactivate-feedback-dialog-form' );
		var radios = qsa( 'input[name="reason_key"]', form );
		var submitBtn = overlay.querySelector( '.wp-ulike-deactivate-feedback-submit' );
		var skipBtn = overlay.querySelector( '.wp-ulike-deactivate-feedback-skip' );

		syncDetailsFields( form );

		radios.forEach( function ( radio ) {
			radio.addEventListener( 'change', function () {
				syncDetailsFields( form );
			} );
		} );

		submitBtn.addEventListener( 'click', function () {
			var checked = form.querySelector( 'input[name="reason_key"]:checked' );
			var reasonError = form.querySelector( '.wp-ulike-deactivate-feedback-reason-error' );

			if ( ! checked ) {
				if ( reasonError ) {
					reasonError.hidden = false;
				}
				return;
			}

			var reason = checked.value;
			var detailsInput = form.querySelector(
				'.wp-ulike-deactivate-feedback-details:not([hidden]) input'
			);
			var details = detailsInput ? detailsInput.value : '';

			submitBtn.disabled = true;
			if ( skipBtn ) {
				skipBtn.disabled = true;
			}
			setButtonBusy( submitBtn, true );
			sendFeedback( reason, details );
		} );

		// Elementor-style: skip = deactivate immediately, no feedback request.
		skipBtn.addEventListener( 'click', function () {
			finishDeactivation();
		} );

		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) {
				closeModal();
			}
		} );

		onKeydown = function ( e ) {
			if ( e.key === 'Escape' ) {
				closeModal();
			}
		};
		document.addEventListener( 'keydown', onKeydown );
	}

	function init() {
		document.addEventListener( 'click', function ( event ) {
			if ( ! isDeactivateClick( event.target ) ) {
				return;
			}

			event.preventDefault();

			var target = event.target;
			var link = target.closest ? target.closest( 'a' ) : null;
			if ( ! link && target.tagName === 'A' ) {
				link = target;
			}

			openModal( {
				preventDefault: function () {
					event.preventDefault();
				},
				currentTarget: link || target,
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();

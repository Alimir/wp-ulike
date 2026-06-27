(function ($) {
	'use strict';

	if (typeof wpUlikePulse === 'undefined') {
		return;
	}

	var pollTimer = null;
	var browserActive = false;

	function post(action, extra) {
		return $.post(wpUlikePulse.ajaxUrl, $.extend({
			action: 'wp_ulike_pulse_sync_action',
			nonce: wpUlikePulse.nonce,
			pulse_action: action
		}, extra || {}));
	}

	function fetchStatus() {
		return $.post(wpUlikePulse.ajaxUrl, {
			action: 'wp_ulike_pulse_sync_status',
			nonce: wpUlikePulse.nonce
		});
	}

	function log(msg) {
		$('#wp-ulike-pulse-log').text(msg || '');
	}

	function redirectAfterAction(res) {
		var url = (res && res.data && res.data.redirect) ? res.data.redirect : wpUlikePulse.redirectUrl;
		if (url) {
			window.location.href = url;
			return;
		}
		window.location.reload();
	}

	function syncComplete(data) {
		return !!(data && (data.sync_complete || data.migration_status === 'done' || (data.progress && data.progress.complete)));
	}

	function updateUi(data) {
		if (!data || !data.progress) {
			return;
		}

		var progress = data.progress;
		var total = parseInt(progress.total_legacy, 10) || 0;
		var imported = parseInt(progress.total_imported, 10) || 0;
		var percent = total > 0 ? Math.min(100, Math.round((imported / total) * 1000) / 10) : 100;
		var complete = syncComplete(data);
		var running = data.migration_status === 'running' && !complete;
		var statusLabel = data.status_label || data.migration_status || 'idle';

		$('#wp-ulike-pulse-sync-status').text(statusLabel);
		$('#wp-ulike-pulse-progress-text').text(imported + ' of ' + total + ' rows (' + percent + '%)');
		$('#wp-ulike-pulse-progress-bar').css('width', percent + '%');

		$('#wp-ulike-pulse-start').prop('disabled', running || complete);
		$('#wp-ulike-pulse-pause').prop('disabled', !running);

		if (complete && !data.is_pulse) {
			$('#wp-ulike-pulse-start').hide();
			$('#wp-ulike-pulse-pause').hide();
			$('#wp-ulike-pulse-enable').prop('disabled', false).addClass('button-primary');
			$('#wp-ulike-pulse-next-step').show();
			browserActive = false;
			stopPolling();
		}
	}

	function pollStatus() {
		return fetchStatus().done(function (res) {
			if (res.success && res.data) {
				updateUi(res.data);

				if (syncComplete(res.data) && !res.data.is_pulse) {
					log(wpUlikePulse.strings.syncComplete);
				}
			}
		});
	}

	function startPolling() {
		stopPolling();
		pollTimer = setInterval(pollStatus, 5000);
		return pollStatus();
	}

	function stopPolling() {
		if (pollTimer) {
			clearInterval(pollTimer);
			pollTimer = null;
		}
	}

	function runBatch() {
		if (!browserActive) {
			return;
		}

		post('batch').done(function (res) {
			if (!res.success || !res.data) {
				browserActive = false;
				return;
			}

			updateUi({
				migration_status: res.data.migration_status || 'running',
				sync_complete: !!res.data.done,
				status_label: res.data.done ? 'Complete' : 'Copying…',
				progress: res.data.progress || {}
			});

			if (res.data.done) {
				browserActive = false;
				log(wpUlikePulse.strings.syncComplete);
				pollStatus();
				return;
			}

			setTimeout(runBatch, 800);
		});
	}

	function showActionError(msg) {
		log(msg || wpUlikePulse.strings.actionFailed || 'Request failed.');
	}

	$('#wp-ulike-pulse-start').on('click', function () {
		post('start').done(function (res) {
			if (!res || !res.success) {
				showActionError();
				return;
			}

			browserActive = true;
			log(wpUlikePulse.strings.started);
			$('#wp-ulike-pulse-start').prop('disabled', true);
			$('#wp-ulike-pulse-pause').prop('disabled', false).show();
			startPolling();
			runBatch();
		}).fail(function () {
			showActionError();
		});
	});

	$('#wp-ulike-pulse-pause').on('click', function () {
		browserActive = false;
		post('pause').done(function (res) {
			if (!res || !res.success) {
				showActionError();
				return;
			}

			stopPolling();
			pollStatus();
			log('');
		}).fail(function () {
			showActionError();
		});
	});

	$('#wp-ulike-pulse-enable').on('click', function () {
		if (!window.confirm(wpUlikePulse.confirmEnable)) {
			return;
		}
		post('enable').done(function (res) {
			if (!res || !res.success) {
				log(wpUlikePulse.strings.enableFailed);
				return;
			}
			window.location.reload();
		}).fail(function () {
			log(wpUlikePulse.strings.enableFailed);
		});
	});

	$('#wp-ulike-pulse-dismiss').on('click', function () {
		post('dismiss').done(function (res) {
			log(wpUlikePulse.strings.dismissed);
			redirectAfterAction(res);
		}).fail(function () {
			showActionError();
		});
	});

	$('#wp-ulike-pulse-drop-legacy').on('click', function () {
		if (!window.confirm(wpUlikePulse.confirmDrop)) {
			return;
		}
		post('drop_legacy').done(function (res) {
			if (!res || !res.success) {
				log(wpUlikePulse.strings.dropFailed);
				return;
			}
			log(wpUlikePulse.strings.dropped);
			redirectAfterAction(res);
		}).fail(function () {
			log(wpUlikePulse.strings.dropFailed);
		});
	});

	if (!wpUlikePulse.isPulse && wpUlikePulse.syncComplete) {
		log(wpUlikePulse.strings.syncComplete);
	} else if (wpUlikePulse.isRunning) {
		browserActive = true;
		startPolling();
		runBatch();
	}
})(jQuery);

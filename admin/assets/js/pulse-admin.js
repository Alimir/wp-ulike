(function ($) {
	'use strict';

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
		var $log = $('#wp-ulike-pulse-log');
		$log.text(msg);
	}

	function updateUi(data) {
		if (!data || !data.progress) {
			return;
		}

		var progress = data.progress;
		var total = parseInt(progress.total_legacy, 10) || 0;
		var imported = parseInt(progress.total_imported, 10) || 0;
		var percent = total > 0 ? Math.min(100, Math.round((imported / total) * 1000) / 10) : 100;
		var running = data.migration_status === 'running';
		var done = progress.complete || data.migration_status === 'done';

		$('#wp-ulike-pulse-sync-status').text(data.migration_status || 'idle');
		$('#wp-ulike-pulse-progress-text').text(imported + ' of ' + total + ' rows (' + percent + '%)');
		$('#wp-ulike-pulse-progress-bar').css('width', percent + '%');
		$('#wp-ulike-pulse-start').prop('disabled', running);
		$('#wp-ulike-pulse-pause').prop('disabled', !running);

		if (done) {
			browserActive = false;
			stopPolling();
			log('');
		}
	}

	function pollStatus() {
		fetchStatus().done(function (res) {
			if (res.success && res.data) {
				updateUi(res.data);
			}
		});
	}

	function startPolling() {
		stopPolling();
		pollTimer = setInterval(pollStatus, 5000);
		pollStatus();
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
				migration_status: 'running',
				progress: res.data.progress || {}
			});

			if (res.data.done) {
				browserActive = false;
				log('');
				return;
			}

			setTimeout(runBatch, 800);
		});
	}

	$('#wp-ulike-pulse-start').on('click', function () {
		post('start').done(function () {
			browserActive = true;
			log(wpUlikePulse.strings.started);
			startPolling();
			runBatch();
		});
	});

	$('#wp-ulike-pulse-pause').on('click', function () {
		browserActive = false;
		post('pause').done(function () {
			stopPolling();
			pollStatus();
			log('');
		});
	});

	$('#wp-ulike-pulse-enable').on('click', function () {
		if (!window.confirm(wpUlikePulse.confirmEnable)) {
			return;
		}
		post('enable').done(function () {
			log(wpUlikePulse.strings.finished);
			pollStatus();
		}).fail(function (xhr) {
			log(wpUlikePulse.strings.enableFailed);
		});
	});

	if (wpUlikePulse.isRunning) {
		browserActive = true;
		startPolling();
		runBatch();
	}
})(jQuery);

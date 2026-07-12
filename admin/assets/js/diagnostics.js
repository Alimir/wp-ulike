(function () {
	'use strict';

	if (typeof window.wpUlikeDiagnostics === 'undefined') {
		return;
	}

	var cfg = window.wpUlikeDiagnostics;
	var strings = cfg.strings || {};

	var runBtn = document.getElementById('wp-ulike-diagnostics-run');
	var copyBtn = document.getElementById('wp-ulike-diagnostics-copy');
	var resultsEl = document.getElementById('wp-ulike-diagnostics-results');
	var summaryEl = document.getElementById('wp-ulike-diagnostics-summary');
	var summaryTextEl = summaryEl ? summaryEl.querySelector('.wp-ulike-diagnostics__summary-text') : null;
	var reportEl = document.getElementById('wp-ulike-diagnostics-report');
	var runLabelEl = runBtn ? runBtn.querySelector('.wp-ulike-diagnostics__run-label') : null;
	var lastReportText = '';

	function setRunning(running) {
		if (!runBtn) {
			return;
		}
		runBtn.disabled = running;
		runBtn.setAttribute('aria-busy', running ? 'true' : 'false');
		if (runLabelEl) {
			runLabelEl.textContent = running ? strings.running : strings.rerun;
		}
		if (resultsEl) {
			resultsEl.setAttribute('aria-busy', running ? 'true' : 'false');
		}
	}

	function setStatusBadgeClass(status) {
		switch (status) {
			case 'pass':
				return 'is-pass';
			case 'warn':
				return 'is-warn';
			case 'fail':
				return 'is-fail';
			default:
				return 'is-neutral';
		}
	}

	function statusLabel(status) {
		switch (status) {
			case 'pass':
				return '✓';
			case 'warn':
				return '!';
			case 'fail':
				return '✕';
			default:
				return '·';
		}
	}

	function escapeHtml(value) {
		return String(value == null ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	}

	function renderCheck(check) {
		var cls = setStatusBadgeClass(check.status);
		var hint = check.hint
			? '<p class="wp-ulike-diagnostics__check-hint">' + escapeHtml(check.hint) + '</p>'
			: '';
		return (
			'<li class="wp-ulike-diagnostics__check ' + cls + '">' +
				'<span class="wp-ulike-diagnostics__check-icon" aria-hidden="true">' + statusLabel(check.status) + '</span>' +
				'<div class="wp-ulike-diagnostics__check-body">' +
					'<p class="wp-ulike-diagnostics__check-label">' + escapeHtml(check.label) +
						' <span class="wp-ulike-diagnostics__check-detail">' + escapeHtml(check.detail) + '</span>' +
					'</p>' +
					hint +
				'</div>' +
			'</li>'
		);
	}

	function renderGroup(group) {
		var checks = (group.checks || []).map(renderCheck).join('');
		return (
			'<section class="wp-ulike-diagnostics__group">' +
				'<h2 class="wp-ulike-diagnostics__group-title">' + escapeHtml(group.label) + '</h2>' +
				'<ul class="wp-ulike-diagnostics__check-list">' + checks + '</ul>' +
			'</section>'
		);
	}

	function renderSummary(summary) {
		if (!summaryTextEl || !summaryEl) {
			return;
		}
		var cls;
		var text;
		if (summary.fail > 0) {
			cls = 'has-fail';
			text = strings.summaryFail + ' (' + summary.fail + ' failed, ' + summary.warn + ' warned, ' + summary.pass + ' passed)';
		} else if (summary.warn > 0) {
			cls = 'has-warn';
			text = strings.summaryWarn + ' (' + summary.warn + ' warned, ' + summary.pass + ' passed)';
		} else {
			cls = 'has-pass';
			text = strings.summaryPass + ' (' + summary.pass + ' checks)';
		}
		summaryTextEl.textContent = text;
		summaryEl.className = summaryEl.className.replace(/\s*has-(fail|warn|pass)\s*/g, ' ');
		summaryEl.className = (summaryEl.className + ' ' + cls).replace(/\s+/g, ' ').trim();
		summaryEl.hidden = false;
	}

	function runDiagnostics() {
		setRunning(true);

		var body = new FormData();
		body.append('action', 'wp_ulike_run_diagnostics');
		body.append('nonce', cfg.nonce);

		fetch(cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('HTTP ' + response.status);
				}
				return response.json();
			})
			.then(function (json) {
				if (!json || !json.success || !json.data) {
					throw new Error((json && json.data && json.data.message) || 'invalid_response');
				}
				var report = json.data;
				lastReportText = formatTextReport(report);

				if (resultsEl) {
					var html = Object.keys(report.groups || {})
						.map(function (key) {
							return renderGroup(report.groups[key]);
						})
						.join('');
					resultsEl.innerHTML = html;
				}
				renderSummary(report.summary || { pass: 0, warn: 0, fail: 0 });

				if (reportEl) {
					reportEl.value = lastReportText;
					reportEl.hidden = false;
				}
				if (copyBtn) {
					copyBtn.disabled = false;
					copyBtn.setAttribute('aria-disabled', 'false');
				}
			})
			.catch(function (err) {
				if (resultsEl) {
					var detail = (err && err.message && err.message !== 'invalid_response') ? err.message : '';
					var html = '<div class="notice notice-error inline"><p>' +
						escapeHtml(strings.error) +
						( detail ? ' <br><em>' + escapeHtml(detail) + '</em>' : '' ) +
						'</p></div>';
					resultsEl.innerHTML = html;
				}
				if (summaryEl) {
					summaryEl.hidden = true;
				}
			})
			.then(function () {
				setRunning(false);
			});
	}

	function formatTextReport(report) {
		var lines = [];
		lines.push('WP ULike diagnostics — ' + (report.generated_at || ''));
		lines.push('Mode: ' + (report.mode || '') + ' · Read: ' + (report.read_mode || ''));
		var s = report.summary || { pass: 0, warn: 0, fail: 0 };
		lines.push('Summary: ' + s.pass + ' pass, ' + s.warn + ' warn, ' + s.fail + ' fail');
		lines.push('');
		Object.keys(report.groups || {}).forEach(function (key) {
			var group = report.groups[key];
			lines.push('## ' + (group.label || ''));
			(group.checks || []).forEach(function (check) {
				var tag = check.status.toUpperCase().slice(0, 4);
				lines.push('[' + tag + '] ' + check.label + ' — ' + check.detail);
				if (check.hint) {
					lines.push('       hint: ' + check.hint);
				}
			});
			lines.push('');
		});
		return lines.join('\n');
	}

	function copyReport() {
		if (!lastReportText) {
			return;
		}
		var done = function () {
			if (copyBtn) {
				var original = copyBtn.textContent;
				copyBtn.textContent = strings.copied || 'Copied';
				window.setTimeout(function () {
					copyBtn.textContent = original;
				}, 2000);
			}
		};

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(lastReportText).then(done, function () {
				fallbackCopy();
			});
		} else {
			fallbackCopy();
		}
	}

	function fallbackCopy() {
		if (!reportEl) {
			window.alert(strings.copyFailed || 'Copy failed');
			return;
		}
		reportEl.hidden = false;
		reportEl.focus();
		reportEl.select();
		try {
			document.execCommand('copy');
			if (copyBtn) {
				var original = copyBtn.textContent;
				copyBtn.textContent = strings.copied || 'Copied';
				window.setTimeout(function () {
					copyBtn.textContent = original;
				}, 2000);
			}
		} catch (e) {
			window.alert(strings.copyFailed || 'Copy failed');
		}
	}

	if (runBtn) {
		runBtn.addEventListener('click', runDiagnostics);
	}
	if (copyBtn) {
		copyBtn.addEventListener('click', copyReport);
	}
})();

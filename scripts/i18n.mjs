import { runWp } from './run-wp.mjs';

await runWp(['i18n', 'make-pot', '.', 'languages/wp-ulike.pot', '--domain=wp-ulike', '--skip-js', '--skip-audit', '--exclude=build,scripts,admin/includes/statistics,admin/includes/optiwich,includes/blocks', '--headers={"Report-Msgid-Bugs-To":"https://wpulike.com","Language-Team":"WP ULike Team <info@wpulike.com>"}'], {
	failureMessage: 'wp i18n make-pot failed',
	onSuccess() {
		console.log('Updated languages/wp-ulike.pot');
	},
});

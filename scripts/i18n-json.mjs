import { readdirSync } from 'node:fs';
import { join } from 'node:path';
import { paths } from './config.mjs';
import { runWp } from './run-wp.mjs';

const poFiles = readdirSync(join(paths.root, 'languages')).filter((file) => file.endsWith('.po'));

if (!poFiles.length) {
	console.log('No .po files in languages/. Skipping JSON generation.');
	console.log('Add translation files (e.g. wp-ulike-fa_IR.po) or run: wp i18n make-json languages --no-purge');
	process.exit(0);
}

await runWp(['i18n', 'make-json', 'languages', '--no-purge'], {
	failureMessage: 'wp i18n make-json failed',
	onSuccess(lines) {
		const createdLine = lines.find((line) => /Success: Created \d+ files?\.?/i.test(line));

		if (createdLine && /Created 0 files/i.test(createdLine)) {
			console.log('No JSON files created. Existing .po files may already be up to date.');
			return;
		}

		console.log('Updated JavaScript translation JSON files in languages/.');
	},
});

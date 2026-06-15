import chokidar from 'chokidar';
import { paths } from './config.mjs';
import { run } from './utils.mjs';

let cssTimer;
let jsTimer;

function schedule(task, timerRef, delay, label) {
	if (timerRef.current) {
		clearTimeout(timerRef.current);
	}

	timerRef.current = setTimeout(async () => {
		console.log(`\n[dev] Rebuilding ${label}...`);
		await run('node', [`scripts/${task}`]);
		console.log(`[dev] ${label} ready.`);
	}, delay);
}

const cssSchedule = { current: null };
const jsSchedule = { current: null };

console.log('[dev] Watching assets. Press Ctrl+C to stop.');

chokidar
	.watch(
		[
			'assets/sass/**/*.scss',
			'admin/assets/sass/**/*.scss',
			'assets/js/src/**/*.js',
		],
		{
			cwd: paths.root,
			ignoreInitial: true,
		}
	)
	.on('change', (filePath) => {
		if (filePath.includes('/sass/') || filePath.endsWith('.scss')) {
			schedule('build-css.mjs', cssSchedule, 120, 'CSS');
			return;
		}

		schedule('build-js.mjs', jsSchedule, 120, 'JavaScript');
	})
	.on('add', (filePath) => {
		if (filePath.includes('/sass/') || filePath.endsWith('.scss')) {
			schedule('build-css.mjs', cssSchedule, 120, 'CSS');
			return;
		}

		schedule('build-js.mjs', jsSchedule, 120, 'JavaScript');
	});

await run('node', ['scripts/build-js.mjs']);
await run('node', ['scripts/build-css.mjs']);

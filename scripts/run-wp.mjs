import { spawn } from 'node:child_process';
import { paths } from './config.mjs';

export function filterWpOutput(line) {
	const trimmed = line.trim();

	if (!trimmed) {
		return false;
	}

	if (/Deprecated:|PHP Deprecated:/i.test(trimmed)) {
		return false;
	}

	return /^(Success|Error|Warning):/i.test(trimmed);
}

export function runWp(args, options = {}) {
	const { onSuccess, failureMessage = 'WP-CLI command failed' } = options;

	return new Promise((resolve, reject) => {
		const child = spawn('wp', args, {
			cwd: paths.root,
			env: {
				...process.env,
				PHP_MEMORY_LIMIT: process.env.PHP_MEMORY_LIMIT || '512M',
				...options.env,
			},
		});

		let output = '';

		const collect = (chunk) => {
			output += chunk.toString();
		};

		child.stdout.on('data', collect);
		child.stderr.on('data', collect);

		child.on('close', (code) => {
			const lines = output.split('\n').filter(filterWpOutput);

			for (const line of lines) {
				console.log(line.trim());
			}

			if (code === 0) {
				if (typeof onSuccess === 'function') {
					onSuccess(lines, output);
				}

				resolve({ lines, output });
				return;
			}

			reject(new Error(`${failureMessage} (exit code ${code})`));
		});
	});
}

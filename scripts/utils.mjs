import { spawn } from 'node:child_process';
import { paths } from './config.mjs';

export function run(command, args = [], options = {}) {
	const { env, ...spawnOptions } = options;

	return new Promise((resolve, reject) => {
		const child = spawn(command, args, {
			cwd: paths.root,
			stdio: 'inherit',
			shell: process.platform === 'win32',
			env: env || process.env,
			...spawnOptions,
		});

		child.on('close', (code) => {
			if (code === 0) {
				resolve();
				return;
			}

			reject(new Error(`Command failed (${code}): ${command} ${args.join(' ')}`));
		});
	});
}

export function commandExists(command) {
	return new Promise((resolve) => {
		const which = process.platform === 'win32' ? 'where' : 'which';
		const child = spawn(which, [command], { stdio: 'ignore' });
		child.on('close', (code) => resolve(code === 0));
	});
}

export async function runIfAvailable(command, args) {
	if (await commandExists(command)) {
		await run(command, args);
		return true;
	}

	console.warn(`Skipping optional step: "${command}" is not installed.`);
	return false;
}

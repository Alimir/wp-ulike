import { existsSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const rootDir = join(dirname(fileURLToPath(import.meta.url)), '..');
const envPath = join(rootDir, '.env');

export function loadEnv() {
	if (!existsSync(envPath)) {
		return {};
	}

	const values = {};

	for (const line of readFileSync(envPath, 'utf8').split('\n')) {
		const trimmed = line.trim();

		if (!trimmed || trimmed.startsWith('#')) {
			continue;
		}

		const separator = trimmed.indexOf('=');

		if (separator === -1) {
			continue;
		}

		const key = trimmed.slice(0, separator).trim();
		let value = trimmed.slice(separator + 1).trim();

		if (
			(value.startsWith('"') && value.endsWith('"')) ||
			(value.startsWith("'") && value.endsWith("'"))
		) {
			value = value.slice(1, -1);
		}

		values[key] = value;
	}

	return values;
}

export function requireEnv(keys) {
	const env = loadEnv();
	const missing = keys.filter((key) => !env[key]);

	if (missing.length) {
		throw new Error(
			`Missing required .env keys: ${missing.join(', ')}. Copy .env.example to .env and fill in your values.`
		);
	}

	return env;
}

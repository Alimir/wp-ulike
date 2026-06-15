import { existsSync } from 'node:fs';
import { deployTargets, paths } from './config.mjs';
import { requireEnv } from './load-env.mjs';
import { assertSafeDeployTarget } from './security.mjs';
import { run } from './utils.mjs';

const targetName = process.argv[2] || 'prod';
const envKeyMap = {
	prod: ['DEPLOY_PROD_HOST', 'DEPLOY_PROD_DEST'],
};

if (!envKeyMap[targetName]) {
	const available = Object.keys(envKeyMap).join(', ');
	throw new Error(`Unknown deploy target "${targetName}". Available targets: ${available}`);
}

requireEnv(envKeyMap[targetName]);

const target = deployTargets[targetName];

if (!target?.host || !target?.dest) {
	throw new Error(
		`Deploy target "${targetName}" is not configured. Copy .env.example to .env and set the required values.`
	);
}

assertSafeDeployTarget(target, targetName);

if (!existsSync(paths.buildPath)) {
	throw new Error(`Build directory not found at ${paths.buildPath}. Run "npm run build" first.`);
}

const rsyncArgs = ['-avz', '--delete-after'];

if (target.port) {
	rsyncArgs.push('-e', `ssh -p ${target.port}`);
}

rsyncArgs.push(`${paths.buildPath}/`, `${target.host}:${target.dest}`);

await run('rsync', rsyncArgs);
console.log(`Deployed to ${targetName}.`);

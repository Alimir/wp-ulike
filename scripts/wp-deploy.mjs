import { cpSync, existsSync, mkdirSync, readdirSync, readFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';
import { tmpdir } from 'node:os';
import { stdin as input, stdout as output } from 'node:process';
import { createInterface } from 'node:readline/promises';
import { spawn } from 'node:child_process';
import { paths, pkg, wpDeploy } from './config.mjs';
import { requireEnv } from './load-env.mjs';
import { assertReleaseBuild, assertSafeSvnConfig } from './security.mjs';
import { commandExists, run } from './utils.mjs';

const env = requireEnv(['WP_SVN_USER', 'WP_SVN_URL']);
assertSafeSvnConfig(wpDeploy);

if (!existsSync(paths.buildPath)) {
	throw new Error(`Build directory not found at ${paths.buildPath}. Run "npm run build" first.`);
}

assertReleaseBuild(paths.buildPath, pkg);

const dryRun = ['1', 'true', 'yes'].includes(String(process.env.WP_RELEASE_DRY_RUN || '').toLowerCase());
const autoConfirm = ['1', 'true', 'yes'].includes(String(process.env.WP_RELEASE_YES || '').toLowerCase());
const svnPassword = env.WP_SVN_PASSWORD || process.env.WP_SVN_PASSWORD || '';
const releaseTagPath = `tags/${pkg.version}`;

const workDir = join(tmpdir(), 'wp-ulike-svn');
const repoDir = join(workDir, wpDeploy.pluginSlug);
const trunkDir = join(repoDir, 'trunk');
const assetsDir = join(repoDir, 'assets');
const tagsDir = join(repoDir, 'tags');
const releaseTagDir = join(tagsDir, pkg.version);

function svnArgs(extra = []) {
	const args = [...extra, '--username', wpDeploy.svnUser];

	if (svnPassword) {
		args.push('--password', svnPassword, '--no-auth-cache', '--non-interactive');
	} else {
		args.push('--force-interactive');
	}

	return args;
}

function runQuiet(command, args = [], options = {}) {
	return new Promise((resolve, reject) => {
		const child = spawn(command, args, {
			cwd: options.cwd || process.cwd(),
			stdio: ['ignore', 'ignore', 'inherit'],
			env: options.env || process.env,
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

async function checkoutFresh() {
	if (existsSync(workDir)) {
		rmSync(workDir, { recursive: true, force: true });
	}

	mkdirSync(workDir, { recursive: true });

	console.log('Checking out WordPress.org SVN (trunk + assets only; skipping old tag files)...');
	await runQuiet('svn', svnArgs(['checkout', '--depth', 'immediates', '--quiet', wpDeploy.svnUrl, repoDir]));
	await runQuiet('svn', svnArgs(['update', '--set-depth', 'infinity', '--quiet', 'trunk']), { cwd: repoDir });

	if (existsSync(assetsDir)) {
		await runQuiet('svn', svnArgs(['update', '--set-depth', 'infinity', '--quiet', 'assets']), { cwd: repoDir });
	}

	if (existsSync(tagsDir)) {
		await runQuiet('svn', svnArgs(['update', '--set-depth', 'immediates', '--quiet', 'tags']), { cwd: repoDir });
	}

	console.log('SVN checkout complete.');
}

function syncDirectory(source, destination) {
	if (existsSync(destination)) {
		for (const entry of readdirSync(destination, { withFileTypes: true })) {
			if (entry.name === '.svn') {
				continue;
			}

			rmSync(join(destination, entry.name), { recursive: true, force: true });
		}
	} else {
		mkdirSync(destination, { recursive: true });
	}

	cpSync(source, destination, { recursive: true });
}

async function syncReleaseDirectory(source, destination) {
	if (await commandExists('rsync')) {
		const sourcePath = source.endsWith('/') ? source : `${source}/`;
		const destinationPath = destination.endsWith('/') ? destination : `${destination}/`;
		await runQuiet('rsync', ['-rc', '--delete', sourcePath, destinationPath]);
		return;
	}

	syncDirectory(source, destination);
}

function captureCommand(command, args, cwd = repoDir) {
	return new Promise((resolve, reject) => {
		const child = spawn(command, args, { cwd, stdio: ['ignore', 'pipe', 'inherit'] });
		let output = '';

		child.stdout.on('data', (chunk) => {
			output += chunk.toString();
		});

		child.on('close', (code) => {
			if (code === 0) {
				resolve(output);
				return;
			}

			reject(new Error(`Command failed (${code}): ${command} ${args.join(' ')}`));
		});
	});
}

function getPendingChanges(statusOutput) {
	return statusOutput
		.split('\n')
		.map((line) => line.replace(/\r$/, ''))
		.filter((line) => line.trim() && !line.startsWith('?'));
}

function getStatusPath(line) {
	// SVN status uses a fixed 6-column code block, then a separator space, then the path.
	return line.slice(7).trim().replace(/\\/g, '/');
}

function isAllowedReleasePath(path) {
	return (
		path === 'trunk' ||
		path === 'assets' ||
		path === releaseTagPath ||
		path.startsWith('trunk/') ||
		path.startsWith('assets/') ||
		path.startsWith(`${releaseTagPath}/`)
	);
}

async function removeMissingSvnFiles(targets) {
	for (const target of targets) {
		const status = await captureCommand('svn', ['status', target]);
		const missing = status
			.split('\n')
			.filter((line) => line.startsWith('!'))
			.map((line) => getStatusPath(line));

		for (const path of missing) {
			await runQuiet('svn', ['remove', path], { cwd: repoDir });
		}
	}
}

async function stageReleasePaths(targets) {
	await runQuiet('svn', ['add', ...targets, '--force'], { cwd: repoDir });
}

async function assertOnlyExpectedPathsChanged() {
	const rootStatus = await captureCommand('svn', ['status']);
	const pending = getPendingChanges(rootStatus);
	const unexpected = pending.filter((line) => !isAllowedReleasePath(getStatusPath(line)));

	if (unexpected.length) {
		throw new Error(
			[
				'SVN working copy has unexpected changes outside trunk/, assets/, and the new release tag.',
				'Aborting release for safety.',
				...unexpected.slice(0, 10),
				unexpected.length > 10 ? `...and ${unexpected.length - 10} more` : '',
			].filter(Boolean).join('\n')
		);
	}
}

function assertReadmeStableTag() {
	const readme = readFileSync(join(paths.buildPath, 'readme.txt'), 'utf8');
	const match = readme.match(/^[ \t*]*Stable tag:\s*(\S+)/im);

	if (!match) {
		throw new Error('readme.txt is missing a Stable tag line.');
	}

	if (match[1] !== pkg.version) {
		throw new Error(`readme.txt Stable tag (${match[1]}) does not match package.json (${pkg.version}).`);
	}
}

function assertReleaseTagIsAvailable() {
	if (existsSync(releaseTagDir)) {
		throw new Error(
			[
				`Tag ${pkg.version} already exists on WordPress.org.`,
				'Bump the version in package.json, wp-ulike.php, and readme.txt before releasing again.',
				'If you only need to update trunk without a new tag, that is not supported by npm run release.',
			].join('\n')
		);
	}
}

async function confirmRelease() {
	if (dryRun || autoConfirm) {
		return;
	}

	if (!process.stdin.isTTY) {
		throw new Error(
			'Release confirmation requires an interactive terminal. Set WP_RELEASE_YES=1 to skip this prompt in CI.'
		);
	}

	const rl = createInterface({ input, output });
	const answer = await rl.question(
		`\nPublish wp-ulike ${pkg.version} to WordPress.org?\n` +
			`This will update trunk and create tags/${pkg.version}.\n` +
			'Type "yes" to continue: '
	);
	rl.close();

	if (answer.trim().toLowerCase() !== 'yes') {
		throw new Error('Release cancelled.');
	}
}

async function publishRelease() {
	const commitMessage = process.env.WP_ULIKE_SVN_MESSAGE || `Updating to ${pkg.version}`;
	const stageTargets = existsSync(wpDeploy.assetsDir) ? ['trunk', 'assets'] : ['trunk'];

	console.log('Staging SVN changes...');
	await stageReleasePaths(stageTargets);
	await removeMissingSvnFiles(stageTargets);

	console.log(`Creating local SVN tag ${pkg.version}...`);
	await runQuiet('svn', ['copy', 'trunk', releaseTagPath], { cwd: repoDir });
	await runQuiet('svn', svnArgs(['update', '--quiet']), { cwd: repoDir });

	await assertOnlyExpectedPathsChanged();

	console.log('Reviewing SVN status for this release...');
	await run('svn', ['status', 'trunk', 'assets', releaseTagPath], { cwd: repoDir });

	if (dryRun) {
		console.log('\nDry run enabled (WP_RELEASE_DRY_RUN). Skipping SVN commit.');
		console.log(`Would commit all staged release changes with message: "${commitMessage}"`);
		return;
	}

	await confirmRelease();

	console.log(`Committing ${pkg.version} to WordPress.org...`);
	await run('svn', svnArgs(['commit', '-m', commitMessage]), { cwd: repoDir });

	console.log(`\nReleased ${pkg.version} to WordPress.org.`);
	console.log(`  Plugin: https://wordpress.org/plugins/${wpDeploy.pluginSlug}/`);
	console.log(`  Tag: ${wpDeploy.svnUrl}/${releaseTagPath}`);
}

console.log('Preparing WordPress.org SVN working copy...');
await checkoutFresh();
assertReadmeStableTag();
assertReleaseTagIsAvailable();

console.log('Syncing production build into SVN trunk...');
await syncReleaseDirectory(paths.buildPath, trunkDir);

if (existsSync(wpDeploy.assetsDir)) {
	console.log('Syncing wp-assets into SVN assets...');
	await syncReleaseDirectory(wpDeploy.assetsDir, assetsDir);
}

await publishRelease();

import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { join } from 'node:path';

const HOST_PATTERN = /^[A-Za-z0-9@._-]+$/;
const ABSOLUTE_PATH_PATTERN = /^\/[A-Za-z0-9/_.-]+$/;

function isJunkReleaseFile(name) {
	return (
		name === '.DS_Store' ||
		name === 'Thumbs.db' ||
		name === 'desktop.ini' ||
		name.startsWith('._') ||
		name.endsWith('~') ||
		name.endsWith('.swp')
	);
}

function walkReleaseFiles(dir, baseDir = dir, files = []) {
	for (const entry of readdirSync(dir, { withFileTypes: true })) {
		const entryPath = join(dir, entry.name);

		if (entry.isDirectory()) {
			walkReleaseFiles(entryPath, baseDir, files);
			continue;
		}

		files.push(entryPath.slice(baseDir.length + 1).replace(/\\/g, '/'));
	}

	return files;
}

export function assertSafeDeployTarget(target, name) {
	if (!target?.host || !HOST_PATTERN.test(target.host)) {
		throw new Error(`Invalid deploy host for "${name}".`);
	}

	if (!target?.dest || !ABSOLUTE_PATH_PATTERN.test(target.dest)) {
		throw new Error(`Invalid deploy destination for "${name}". Use an absolute plugin path.`);
	}

	if (!target.dest.includes('/wp-ulike')) {
		throw new Error(`Deploy destination for "${name}" must point to the wp-ulike plugin directory.`);
	}

	if (target.dest.includes('..')) {
		throw new Error(`Deploy destination for "${name}" cannot contain "..".`);
	}

	if (target.port && !/^\d{1,5}$/.test(String(target.port))) {
		throw new Error(`Invalid SSH port for "${name}".`);
	}
}

export function assertSafeSvnConfig(config) {
	if (!config?.svnUser || !/^[A-Za-z0-9._-]+$/.test(config.svnUser)) {
		throw new Error('Invalid WP_SVN_USER in .env.');
	}

	if (!config?.svnUrl || !config.svnUrl.startsWith('https://plugins.svn.wordpress.org/')) {
		throw new Error('WP_SVN_URL must use https://plugins.svn.wordpress.org/.');
	}
}

export function assertReleaseBuild(buildPath, pkg) {
	const blockers = [];
	const requiredFiles = ['wp-ulike.php', 'readme.txt', 'uninstall.php'];

	for (const file of requiredFiles) {
		if (!existsSync(join(buildPath, file))) {
			blockers.push(`Missing required file: ${file}`);
		}
	}

	const forbidden = [
		'.env',
		'.DS_Store',
		'package.json',
		'node_modules',
		'scripts/build.mjs',
		'assets/js/src',
		'assets/sass',
	];

	for (const relativePath of forbidden) {
		if (existsSync(join(buildPath, relativePath))) {
			blockers.push(`Forbidden release artifact present: ${relativePath}`);
		}
	}

	const junkFiles = walkReleaseFiles(buildPath).filter((filePath) =>
		isJunkReleaseFile(filePath.split('/').pop() || '')
	);

	if (junkFiles.length) {
		blockers.push(`Junk files present in build: ${junkFiles.join(', ')}`);
	}

	const mainFile = existsSync(join(buildPath, 'wp-ulike.php'))
		? readFileSync(join(buildPath, 'wp-ulike.php'), 'utf8')
		: '';
	const readme = existsSync(join(buildPath, 'readme.txt'))
		? readFileSync(join(buildPath, 'readme.txt'), 'utf8')
		: '';

	if (mainFile && !mainFile.includes(`Version:           ${pkg.version}`)) {
		blockers.push(`wp-ulike.php version does not match package.json (${pkg.version}).`);
	}

	if (readme && !readme.includes(`Stable tag: ${pkg.version}`)) {
		blockers.push(`readme.txt stable tag does not match package.json (${pkg.version}).`);
	}

	if (mainFile.includes('Access-Control-Allow-Origin')) {
		blockers.push('DEV-only CORS header leaked into production build.');
	}

	if (mainFile.includes('wp-ulike.css') && !mainFile.includes('wp-ulike.min.css')) {
		blockers.push('Production build still references unminified wp-ulike.css.');
	}

	if (mainFile.includes("'/js/wp-ulike.js'") || mainFile.includes('"/js/wp-ulike.js"')) {
		blockers.push('Production build still references unminified wp-ulike.js.');
	}

	if (blockers.length) {
		throw new Error(`Release build validation failed:\n- ${blockers.join('\n- ')}`);
	}
}

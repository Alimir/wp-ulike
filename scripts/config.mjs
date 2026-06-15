import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { loadEnv } from './load-env.mjs';
import { assertSafeDeployTarget, assertSafeSvnConfig } from './security.mjs';

const rootDir = join(dirname(fileURLToPath(import.meta.url)), '..');
const pkg = JSON.parse(readFileSync(join(rootDir, 'package.json'), 'utf8'));
const env = loadEnv();

function targetFromEnv(prefix) {
	const port = env[`${prefix}_PORT`];
	const target = {
		host: env[`${prefix}_HOST`] || '',
		dest: env[`${prefix}_DEST`] || '',
	};

	if (port) {
		target.port = port;
	}

	return target;
}

function buildDeployTargets() {
	const targets = {
		prod: targetFromEnv('DEPLOY_PROD'),
	};

	for (const [name, target] of Object.entries(targets)) {
		if (target.host && target.dest) {
			assertSafeDeployTarget(target, name);
		}
	}

	return targets;
}

export const paths = {
	root: rootDir,
	buildDir: join(rootDir, 'build'),
	buildPath: join(rootDir, 'build', 'wp-ulike'),
	zipPath: join(rootDir, 'build', 'wp-ulike.zip'),
	wpAssets: join(rootDir, 'wp-assets'),
};

export const meta = {
	project: 'wp-ulike',
	version: `${pkg.title || pkg.name} - v${pkg.version}`,
	copyright: `${pkg.author.name} ${new Date().getFullYear()}`,
	phpHeader: [
		'',
		` * @package    ${pkg.name}`,
		` * @author     ${pkg.author.name} ${new Date().getFullYear()}`,
		` * @link       ${pkg.homepage}`,
	].join('\n'),
};

export const jsSources = [
	'assets/js/src/tooltip.js',
	'assets/js/src/notifications.js',
	'assets/js/src/ulike.js',
	'assets/js/src/scripts.js',
];

export const sassEntries = {
	'assets/css/wp-ulike.css': 'assets/sass/wp-ulike.scss',
	'admin/assets/css/admin.css': 'admin/assets/sass/admin.scss',
	'admin/assets/css/plugins.css': 'admin/assets/sass/plugins.scss',
};

export const rsyncExcludes = [
	'.git',
	'.git*',
	'.env',
	'.env.*',
	'node_modules',
	'scripts',
	'package.json',
	'package-lock.json',
	'composer.json',
	'composer.lock',
	'assets/js/src',
	'admin/assets/js/src',
	'includes/blocks/button/src',
	'includes/blocks/top-content/src',
	'includes/blocks/button/package.json',
	'includes/blocks/button/package-lock.json',
	'includes/blocks/top-content/package.json',
	'includes/blocks/top-content/package-lock.json',
	'includes/blocks/button/webpack.config.js',
	'includes/blocks/top-content/webpack.config.js',
	'readme.md',
	'README.md',
	'SUMMARY.md',
	'build',
	'.sass-cache',
	'dist',
	'assets/sass',
	'admin/assets/sass',
	'wp-assets',
	'docs',
	'deploy.sh',
	'.DS_Store',
	'.*',
];

export const deployTargets = buildDeployTargets();

export const wpDeploy = {
	pluginSlug: 'wp-ulike',
	svnUser: env.WP_SVN_USER || '',
	svnUrl: env.WP_SVN_URL || 'https://plugins.svn.wordpress.org/wp-ulike',
	buildDir: paths.buildPath,
	assetsDir: paths.wpAssets,
};

if (wpDeploy.svnUser) {
	assertSafeSvnConfig(wpDeploy);
}

export const preprocessContext = {
	VERSION: pkg.version,
	DEV: false,
	TODO: false,
	LITE: true,
	PRO: false,
	HEADER: meta.phpHeader,
};

export { pkg };

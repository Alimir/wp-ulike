import { cpSync, existsSync, mkdirSync, readFileSync, readdirSync, rmSync, writeFileSync } from 'node:fs';
import { join, relative } from 'node:path';
import preprocess from 'preprocess';
import { meta, paths, pkg, preprocessContext, rsyncExcludes } from './config.mjs';
import { assertReleaseBuild } from './security.mjs';
import { commandExists, run } from './utils.mjs';

function walkFiles(dir, matcher, files = []) {
	for (const entry of readdirSync(dir, { withFileTypes: true })) {
		const entryPath = join(dir, entry.name);

		if (entry.isDirectory()) {
			walkFiles(entryPath, matcher, files);
			continue;
		}

		if (matcher(entryPath)) {
			files.push(entryPath);
		}
	}

	return files;
}

function isJunkEntry(name) {
	return (
		name === '.DS_Store' ||
		name === 'Thumbs.db' ||
		name === 'desktop.ini' ||
		name.startsWith('._') ||
		name.endsWith('~') ||
		name.endsWith('.swp')
	);
}

function shouldExclude(relativePath) {
	const normalized = relativePath.replace(/\\/g, '/');
	const baseName = normalized.split('/').pop() || normalized;

	if (isJunkEntry(baseName)) {
		return true;
	}

	if (normalized === '.env' || normalized.startsWith('.env.')) {
		return true;
	}

	if (
		normalized === 'node_modules' ||
		normalized.endsWith('/node_modules') ||
		normalized.includes('/node_modules/')
	) {
		return true;
	}

	return rsyncExcludes.some((pattern) => {
		if (pattern === 'node_modules') {
			return false;
		}

		if (pattern === '.*') {
			const parts = normalized.split('/');
			return parts.some((part) => part.startsWith('.') && part !== '.' && part !== '..');
		}

		if (pattern.endsWith('*')) {
			return normalized.startsWith(pattern.slice(0, -1));
		}

		return normalized === pattern || normalized.startsWith(`${pattern}/`);
	});
}

async function cleanBuildDir() {
	if (!existsSync(paths.buildPath)) {
		return;
	}

	try {
		rmSync(paths.buildPath, {
			recursive: true,
			force: true,
			maxRetries: 5,
			retryDelay: 200,
		});
	} catch {
		await run('find', [paths.buildPath, '-depth', '-delete'], { stdio: 'ignore' });
	}
}

async function copyProjectToBuild() {
	await cleanBuildDir();

	mkdirSync(paths.buildPath, { recursive: true });

	const walk = (sourceDir, targetDir) => {
		for (const entry of readdirSync(sourceDir, { withFileTypes: true })) {
			if (isJunkEntry(entry.name)) {
				continue;
			}

			const sourcePath = join(sourceDir, entry.name);
			const relativePath = relative(paths.root, sourcePath);

			if (shouldExclude(relativePath)) {
				continue;
			}

			const targetPath = join(targetDir, entry.name);

			if (entry.isDirectory()) {
				mkdirSync(targetPath, { recursive: true });
				walk(sourcePath, targetPath);
				continue;
			}

			cpSync(sourcePath, targetPath);
		}
	};

	walk(paths.root, paths.buildPath);
}

function purgeJunkFiles(dir) {
	for (const entry of readdirSync(dir, { withFileTypes: true })) {
		const entryPath = join(dir, entry.name);

		if (entry.isDirectory()) {
			purgeJunkFiles(entryPath);
			continue;
		}

		if (isJunkEntry(entry.name)) {
			rmSync(entryPath, { force: true });
		}
	}
}

async function compressImages() {
	const pngs = walkFiles(paths.buildPath, (filePath) => filePath.endsWith('.png'));
	const jpgs = walkFiles(paths.buildPath, (filePath) => /\.(jpe?g)$/i.test(filePath));
	const hasPngquant = await commandExists('pngquant');
	const hasJpegoptim = await commandExists('jpegoptim');

	if (!pngs.length && !jpgs.length) {
		return;
	}

	if (!hasPngquant && !hasJpegoptim) {
		console.log('Skipping image compression (pngquant/jpegoptim not installed).');
		return;
	}

	if (pngs.length && hasPngquant) {
		console.log(`Compressing ${pngs.length} PNG files...`);
		for (const absolute of pngs) {
			await run('pngquant', [
				'--speed',
				'3',
				'--quality=65-80',
				'--skip-if-larger',
				'--ext',
				'.png',
				'--force',
				'256',
				absolute,
			], { stdio: 'ignore' });
		}
	}

	if (jpgs.length && hasJpegoptim) {
		console.log(`Compressing ${jpgs.length} JPEG files...`);
		for (const absolute of jpgs) {
			await run('jpegoptim', ['-m80', '-o', '-p', absolute], { stdio: 'ignore' });
		}
	}
}

function applyPreprocess() {
	const targets = [
		...walkFiles(paths.buildPath, (filePath) => filePath.endsWith('.php')),
		...walkFiles(paths.buildPath, (filePath) => filePath.endsWith('.css')),
		join(paths.buildPath, 'readme.txt'),
	].filter((filePath) => existsSync(filePath));

	for (const absoluteFile of targets) {
		const source = readFileSync(absoluteFile, 'utf8');
		const extension = absoluteFile.split('.').pop();
		const output = preprocess.preprocess(source, preprocessContext, extension);

		writeFileSync(absoluteFile, output, 'utf8');
	}
}

function optimizeReleaseBundle() {
	const devOnlyFiles = [
		join(paths.buildPath, 'assets/js/wp-ulike.js'),
		join(paths.buildPath, 'assets/css/wp-ulike.css'),
	];

	for (const filePath of devOnlyFiles) {
		if (existsSync(filePath)) {
			rmSync(filePath, { force: true });
		}
	}
}

function writeVersionFile() {
	for (const file of readdirSync(paths.buildDir)) {
		if (file.endsWith('.txt')) {
			rmSync(join(paths.buildDir, file), { force: true });
		}
	}

	writeFileSync(join(paths.buildDir, `${pkg.version}.txt`), `Latest version: v${pkg.version}\n`, 'utf8');
}

async function packBuild() {
	if (existsSync(paths.zipPath)) {
		rmSync(paths.zipPath, { force: true });
	}

	await run('zip', ['-FSr', '-9', paths.zipPath, 'wp-ulike', '-x', '*/.*', '*/.DS_Store', '*/._*', '*/Thumbs.db'], {
		cwd: paths.buildDir,
	});
}

console.log(`Building ${meta.project} v${pkg.version}...`);
await run('node', ['scripts/build-js.mjs']);
await run('node', ['scripts/build-css.mjs']);
console.log('Copying plugin files to build/...');
await copyProjectToBuild();
console.log('Cleaning build artifacts...');
purgeJunkFiles(paths.buildPath);
await compressImages();
console.log('Applying production preprocess flags...');
applyPreprocess();
console.log('Optimizing release bundle...');
optimizeReleaseBundle();
assertReleaseBuild(paths.buildPath, pkg);
writeVersionFile();
console.log('Creating zip archive...');
await packBuild();
console.log(`Build complete: ${paths.buildPath}`);
console.log(`Zip archive: ${paths.zipPath}`);

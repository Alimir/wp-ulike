import { readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { minify } from 'terser';
import { jsSources, meta, paths } from './config.mjs';

const banner =
	`/*! ${meta.version}\n` +
	` *  ${JSON.parse(readFileSync(join(paths.root, 'package.json'), 'utf8')).homepage}\n` +
	` *  ${meta.copyright};\n` +
	` */\n`;

function concatSources(includeSeparators) {
	const chunks = jsSources.map((relativePath) => {
		const absolutePath = join(paths.root, relativePath);
		const source = readFileSync(absolutePath, 'utf8').replace(/;\s*$/, '') + ';';

		if (!includeSeparators) {
			return source;
		}

		const separator = `\n\n/* ================== ${relativePath} =================== */\n\n\n`;
		return separator + source;
	});

	return chunks.join('');
}

const bundled = banner + concatSources(true);
const minifyInput = concatSources(false);
const outputPath = join(paths.root, 'assets/js/wp-ulike.js');
const minPath = join(paths.root, 'assets/js/wp-ulike.min.js');

writeFileSync(outputPath, bundled, 'utf8');

const minified = await minify(minifyInput, {
	compress: {
		passes: 2,
		drop_console: true,
	},
	mangle: true,
	format: {
		comments: false,
		ascii_only: true,
	},
});

if (!minified.code) {
	throw new Error('Failed to minify frontend JavaScript.');
}

writeFileSync(minPath, minified.code, 'utf8');
console.log('Built assets/js/wp-ulike.js and assets/js/wp-ulike.min.js');

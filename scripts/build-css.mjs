import { readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import * as sass from 'sass';
import { transform } from 'lightningcss';
import { sassEntries, paths } from './config.mjs';

function stripCssComments(code) {
	return Buffer.from(code.toString().replace(/\/\*[\s\S]*?\*\//g, ''));
}

function minifyCss(inputPath, outputPath) {
	const code = readFileSync(inputPath);
	const { code: minified } = transform({
		filename: inputPath,
		code: stripCssComments(code),
		minify: true,
	});

	writeFileSync(outputPath, minified);
}

for (const [outputRelative, inputRelative] of Object.entries(sassEntries)) {
	const inputPath = join(paths.root, inputRelative);
	const outputPath = join(paths.root, outputRelative);
	const result = sass.compile(inputPath, {
		style: 'expanded',
		sourceMap: false,
		quietDeps: true,
		silenceDeprecations: ['import', 'slash-div', 'global-builtin', 'color-functions'],
		logger: {
			warn() {},
			debug() {},
		},
	});

	writeFileSync(outputPath, result.css, 'utf8');
	console.log(`Compiled ${outputRelative}`);

	if (outputRelative === 'assets/css/wp-ulike.css') {
		minifyCss(outputPath, join(paths.root, 'assets/css/wp-ulike.min.css'));
		console.log('Minified assets/css/wp-ulike.min.css');
		continue;
	}

	minifyCss(outputPath, outputPath);
	console.log(`Minified ${outputRelative}`);
}

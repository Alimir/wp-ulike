export default {
	slug: 'wp-ulike',
	mainFile: 'wp-ulike.php',
	textDomain: 'wp-ulike',

	assets: {
		js: {
			bundles: [
				{
					sources: [
						'assets/js/src/tooltip.js',
						'assets/js/src/notifications.js',
						'assets/js/src/ulike.js',
						'assets/js/src/scripts.js',
					],
					output: 'assets/js/wp-ulike.js',
					minOutput: 'assets/js/wp-ulike.min.js',
				},
			],
			minify: [],
		},
		css: {
			sassEntries: {
				'assets/css/wp-ulike.css': 'assets/sass/wp-ulike.scss',
				'admin/assets/css/admin.css': 'admin/assets/sass/admin.scss',
				'admin/assets/css/plugins.css': 'admin/assets/sass/plugins.scss',
			},
			minifySeparate: ['assets/css/wp-ulike.css'],
		},
		watch: {
			scss: [],
			js: [],
		},
	},

	build: {
		excludes: [
			'assets/sass',
			'admin/assets/sass',
			'assets/js/src',
			'admin/assets/js/src',
			'wp-toolkit.config.mjs',
			'includes/blocks/button/src',
			'includes/blocks/top-content/src',
			'includes/blocks/button/package.json',
			'includes/blocks/button/package-lock.json',
			'includes/blocks/top-content/package.json',
			'includes/blocks/top-content/package-lock.json',
			'includes/blocks/button/webpack.config.js',
			'includes/blocks/top-content/webpack.config.js',
		],
		devOnlyFiles: ['assets/js/wp-ulike.js', 'assets/css/wp-ulike.css'],
		preprocess: {
			DEV: false,
			TODO: false,
			LITE: true,
			PRO: false,
		},
		hooks: {
			preBuild: ['npm run build:blocks'],
			postBuild: [],
		},
		zipName: '{slug}.zip',
		trimTrailingWhitespace: true
	},

	deploy: {
		prod: { envPrefix: 'DEPLOY_PROD' },
		kinsta: { envPrefix: 'DEPLOY_KINSTA' },
	},

	release: {
		enabled: true,
		wpAssets: 'wp-assets',
		svnUrl: 'https://plugins.svn.wordpress.org/wp-ulike',
	},

	i18n: {
		domain: 'wp-ulike',
		potFile: 'languages/wp-ulike.pot',
		exclude: 'build,node_modules,admin/includes/statistics,admin/includes/optiwich,includes/blocks',
		headers: {
			'Report-Msgid-Bugs-To': 'https://wpulike.com',
			'Language-Team': 'WP ULike Team <info@wpulike.com>',
		},
	},
};

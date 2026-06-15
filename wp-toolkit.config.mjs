export default {
	slug: 'wp-ulike',
	mainFile: 'wp-ulike.php',
	textDomain: 'wp-ulike',

	jsSources: [
		'assets/js/src/tooltip.js',
		'assets/js/src/notifications.js',
		'assets/js/src/ulike.js',
		'assets/js/src/scripts.js',
	],

	sassEntries: {
		'assets/css/wp-ulike.css': 'assets/sass/wp-ulike.scss',
		'admin/assets/css/admin.css': 'admin/assets/sass/admin.scss',
		'admin/assets/css/plugins.css': 'admin/assets/sass/plugins.scss',
	},

	js: {
		output: 'assets/js/wp-ulike.js',
		minOutput: 'assets/js/wp-ulike.min.js',
	},

	css: {
		minifySeparate: ['assets/css/wp-ulike.css'],
	},

	devOnlyFiles: ['assets/js/wp-ulike.js', 'assets/css/wp-ulike.css'],

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

	preprocess: {
		DEV: false,
		TODO: false,
		LITE: true,
		PRO: false,
	},

	deploy: {
		prod: { envPrefix: 'DEPLOY_PROD' },
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

	validation: {
		forbidden: [
			'.env',
			'.DS_Store',
			'package.json',
			'node_modules',
			'assets/js/src',
			'admin/assets/js/src',
			'assets/sass',
			'admin/assets/sass',
		],
	},
};

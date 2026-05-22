const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const sharedConfig = typeof defaultConfig === 'function' ? defaultConfig() : defaultConfig;
const entry =
	typeof sharedConfig.entry === 'function'
		? sharedConfig.entry()
		: { ...sharedConfig.entry };

module.exports = {
	...sharedConfig,
	entry: {
		...entry,
		style: path.resolve( __dirname, 'src', 'style.scss' ),
	},
};

const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const sharedConfig =
	typeof defaultConfig === 'function' ? defaultConfig() : defaultConfig;
const entry =
	typeof sharedConfig.entry === 'function'
		? sharedConfig.entry()
		: { ...sharedConfig.entry };

/**
 * Use Dart Sass modern API (avoids "legacy JS API" deprecation warnings).
 *
 * @param {Array} rules Webpack module rules.
 * @return {Array}
 */
function patchSassLoaderRules( rules ) {
	if ( ! Array.isArray( rules ) ) {
		return rules;
	}

	return rules.map( ( rule ) => {
		if ( rule.oneOf ) {
			return { ...rule, oneOf: patchSassLoaderRules( rule.oneOf ) };
		}

		if ( rule.rules ) {
			return { ...rule, rules: patchSassLoaderRules( rule.rules ) };
		}

		if ( ! Array.isArray( rule.use ) ) {
			return rule;
		}

		return {
			...rule,
			use: rule.use.map( ( useEntry ) => {
				const loader =
					typeof useEntry === 'string'
						? useEntry
						: useEntry?.loader || '';

				if ( ! loader.includes( 'sass-loader' ) ) {
					return useEntry;
				}

				return {
					...( typeof useEntry === 'object' ? useEntry : { loader: useEntry } ),
					options: {
						...( typeof useEntry === 'object' ? useEntry.options : {} ),
						api: 'modern',
					},
				};
			} ),
		};
	} );
}

module.exports = {
	...sharedConfig,
	entry: {
		...entry,
		style: path.resolve( __dirname, 'src', 'style.scss' ),
	},
	module: {
		...sharedConfig.module,
		rules: patchSassLoaderRules( sharedConfig.module?.rules ),
	},
};

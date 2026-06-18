/**
 * ESLint flat config (ESLint 9+).
 *
 * @see https://eslint.org/docs/latest/use/configure/migration-guide
 */
const wpPlugin = require( '@wordpress/eslint-plugin' );
const { hasBabelConfig } = require( '@wordpress/scripts/utils' );

const config = [
	{
		ignores: [
			'**/build/**',
			'**/node_modules/**',
			'**/vendor/**',
			'tests/**',
		],
	},
	...wpPlugin.configs.recommended,
	{
		languageOptions: {
			globals: {
				wp: 'readonly',
				ajaxurl: 'readonly',
				FormData: 'readonly',
				PLUGIN_CHECK: 'readonly',
			},
		},
		rules: {
			'no-console': 'off',
			'@wordpress/i18n-text-domain': [
				'error',
				{
					allowedTextDomain: 'plugin-check',
				},
			],
		},
	},
	{
		files: [ 'eslint.config.js' ],
		rules: {
			'import/no-extraneous-dependencies': 'off',
		},
	},
];

if ( ! hasBabelConfig() ) {
	config.push( {
		languageOptions: {
			parserOptions: {
				requireConfigFile: false,
				babelOptions: {
					presets: [
						require.resolve( '@wordpress/babel-preset-default' ),
					],
				},
			},
		},
	} );
}

module.exports = config;

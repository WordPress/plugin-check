module.exports = {
	root: true,
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	plugins: [ 'import' ],
	globals: {
		wp: 'off',
		ajaxurl: 'readonly',
		FormData: 'readonly',
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
};

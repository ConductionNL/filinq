module.exports = {
	extends: [
		'@nextcloud',
	],
	rules: {
		'jsdoc/require-jsdoc': 'off',
		// `@spec` is docudesk's spec-coverage marker tag, not a typo.
		'jsdoc/check-tag-names': ['warn', { definedTags: ['spec'] }],
		'vue/first-attribute-linebreak': 'off',
		'@typescript-eslint/no-explicit-any': 'off',
		'vue/enforce-style-attribute': ['error', { allow: ['scoped'] }],
	},
}

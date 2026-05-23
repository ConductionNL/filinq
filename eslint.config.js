/**
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * Flat-config shim. Exists ONLY to stop ESLint from walking up the
 * directory tree and picking up the parent nextcloud-server repo's
 * `eslint.config.mjs`, which uses `linterOptions.reportUnusedInlineConfigs`
 * (an ESLint 9 feature) and crashes our pinned ESLint 8.57 with
 *   `Key "linterOptions": Unexpected key "reportUnusedInlineConfigs" found.`
 *
 * The actual rule set still lives in `.eslintrc.js` (legacy `eslintrc`
 * format). This file uses `@eslint/eslintrc`'s `FlatCompat` to load the
 * existing `extends: '@nextcloud'` config so we don't have to maintain
 * two parallel rule sets while the rest of the docudesk repo migrates
 * to flat config.
 *
 * Follows the same pattern as decidesk's eslint.config.js.
 */

const {
	defineConfig,
} = require('@eslint/config-helpers')

const js = require('@eslint/js')

const {
	FlatCompat,
} = require('@eslint/eslintrc')

const compat = new FlatCompat({
	baseDirectory: __dirname,
	recommendedConfig: js.configs.recommended,
	allConfig: js.configs.all,
})

module.exports = defineConfig([{
	extends: compat.extends('@nextcloud'),

	rules: {
		'jsdoc/require-jsdoc': 'off',
		'vue/first-attribute-linebreak': 'off',
		'@typescript-eslint/no-explicit-any': 'off',
		'vue/enforce-style-attribute': ['error', { allow: ['scoped'] }],
		// Disable namespace/default import checks that need a TypeScript
		// resolver we don't ship here (matches decidesk's accommodations).
		'import/namespace': 'off',
		'import/default': 'off',
		'import/no-named-as-default': 'off',
		'import/no-named-as-default-member': 'off',
		// Don't fail lint on missing imports — the build (webpack) is the
		// authoritative resolver and catches real misses; ESLint's resolver
		// chokes on the @conduction/nextcloud-vue barrel.
		'n/no-missing-import': 'off',
	},
}])

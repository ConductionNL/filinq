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
 * This is the ONLY config ESLint reads. A `.eslintrc.js` used to sit
 * beside it carrying a near-duplicate rule set; a marker probe (adding a
 * unique rule to `.eslintrc.js` and re-running `eslint --print-config`)
 * showed the marker never reached the resolved config, i.e. the file was
 * dead. It has been removed so there is one rule set, not two that can
 * drift.
 *
 * The `@nextcloud` v8 base is Vue-2 era: on its own it activates ZERO
 * `vue/no-deprecated-*` rules, so Vue-2 idioms (`beforeDestroy`,
 * `.sync`, `filters:`) survive a green lint. `conductionVue3Fixes` from
 * @conduction/nextcloud-vue layers the 25 Vue-3 rules on top and must be
 * spread LAST so it wins. It registers no plugins, which is why it
 * layers cleanly onto the @nextcloud base.
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

// CJS: the extensionless subpath works because the package ships no
// `exports` map. From ESM this would need `/eslint/index.js`.
const {
	conductionVue3Fixes,
} = require('@conduction/nextcloud-vue/eslint')

const compat = new FlatCompat({
	baseDirectory: __dirname,
	recommendedConfig: js.configs.recommended,
	allConfig: js.configs.all,
})

module.exports = defineConfig([{
	extends: compat.extends('@nextcloud'),

	rules: {
		'jsdoc/require-jsdoc': 'off',
		// `@spec` is docudesk's spec-coverage marker tag, not a typo.
		'jsdoc/check-tag-names': ['warn', { definedTags: ['spec'] }],
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
},
// Spread LAST so the Vue-3 rules win over the Vue-2 @nextcloud base.
// This is an array of three layers (shared parserOptions, a `.vue`
// parser layer, and the 25-rule layer), not a single object.
...conductionVue3Fixes,
])

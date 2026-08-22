/**
 * SPDX-FileCopyrightText: 2026 Conduction / Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Vitest configuration for Filinq frontend unit tests.
 *
 * This OFFLINE suite targets PURE logic the rendered UI relies on but never
 * asserts exactly:
 *   • fileViewerService — WebDAV URL building + the Nextcloud-internal →
 *     user-relative path normalisation (per-segment encoding).
 *   • the settings Pinia store — fetch envelope, openRegisters/isAdmin flag
 *     derivation, loading/error/initialized lifecycle.
 *   • the anonymisation store getters — hasFiles / hasCompleted / allDone /
 *     findByFileId over a seeded queue.
 *
 * These need no DOM, so the environment is `node`. global fetch is mocked
 * per-test; @nextcloud/auth + @nextcloud/router are aliased to stubs. The
 * existing Jest jsdom suite (jest.config.js) is untouched.
 *
 * tests/unit/reachability.spec.js (openspec/changes/orphaned-surface-
 * restoration) is included explicitly alongside tests/vitest/**: it is a
 * pure Node fs/path static-analysis suite (registry.js ↔ manifest.json ↔
 * src/views/** reachability), so it belongs with this offline runner, not
 * Jest's jsdom + `.vue`-transform suite. See jest.config.js
 * `testPathIgnorePatterns` for the matching exclusion.
 */

const path = require('path')

module.exports = {
	test: {
		environment: 'node',
		globals: false,
		include: [
			'tests/vitest/**/*.spec.{js,ts}',
			'tests/unit/reachability.spec.js',
		],
		exclude: [
			'tests/e2e/**',
			'tests/integration/**',
			'src/**',
			'node_modules/**',
		],
	},
	resolve: {
		alias: [
			{ find: '@', replacement: path.resolve(__dirname, 'src') },
			{
				find: /^@nextcloud\/router$/,
				replacement: path.resolve(
					__dirname,
					'tests/vitest/stubs/nextcloud-router.js',
				),
			},
			{
				find: /^@nextcloud\/auth$/,
				replacement: path.resolve(
					__dirname,
					'tests/vitest/stubs/nextcloud-auth.js',
				),
			},
		],
	},
}

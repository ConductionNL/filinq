/**
 * Jest stub for `@nextcloud/axios`.
 *
 * The upstream package is `type: module` and only declares an `import` export
 * condition, so Jest's CommonJS resolver cannot load it — every store spec that
 * touched it failed at "Cannot find module '@nextcloud/axios'" before the
 * factory in the spec ever ran.
 *
 * The specs do mock it (`jest.mock('@nextcloud/axios', factory)`), but
 * `jest.mock` without `virtual: true` still requires the path to RESOLVE.
 * moduleNameMapper redirects the import here so resolution succeeds; the spec's
 * own factory then replaces this stub.
 *
 * Mirrors nextcloud-vue/tests/__mocks__/nextcloud-axios.js — same root cause,
 * same fix.
 */
module.exports = {
	__esModule: true,
	default: { get: () => Promise.resolve({ status: 200, data: {} }) },
}

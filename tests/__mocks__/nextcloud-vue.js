/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Jest stub for the bare `@nextcloud/vue` import.
 *
 * The package root declares only an `import` export condition, so Jest's
 * CommonJS resolver cannot find it. moduleNameMapper points the bare import
 * here so `jest.mock('@nextcloud/vue', factory)` resolves without the
 * `virtual` flag; the spec's own factory then replaces this stub.
 */
module.exports = {
	__esModule: true,
}

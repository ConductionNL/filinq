/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Jest stub for `@nextcloud/dialogs`.
 *
 * The package declares only an `import` export condition, so Jest's CommonJS
 * resolver cannot find it. moduleNameMapper points the import here so
 * `jest.mock('@nextcloud/dialogs', factory)` resolves without the `virtual`
 * flag; the spec's own factory then replaces this stub.
 */
module.exports = {
	__esModule: true,
	showError: () => {},
	showSuccess: () => {},
}

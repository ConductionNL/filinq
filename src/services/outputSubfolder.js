/**
 * The output subfolder name check of the admin settings.
 *
 * Batch and folder anonymisation write redacted copies to
 * `<source>/<subfolder>/`, so the name must be one safe path segment. The
 * server refuses anything else on save (SettingsService); this check names
 * the offending characters before the admin presses save.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

const ALLOWED = /^[a-z0-9_-]$/

/**
 * The characters a subfolder name may not hold, each listed once, in order.
 *
 * @param {string} name The name as typed.
 * @return {string[]} The disallowed characters; empty when there are none.
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-2
 */
export function disallowedSubfolderCharacters(name) {
	const found = []
	for (const c of Array.from(name || '')) {
		if (!ALLOWED.test(c) && !found.includes(c)) {
			found.push(c)
		}
	}
	return found
}

/**
 * Whether the name is a non-empty single safe path segment.
 *
 * @param {string} name The name as typed.
 * @return {boolean} True when the server will accept it.
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-2
 */
export function isValidSubfolderName(name) {
	return (name || '') !== '' && disallowedSubfolderCharacters(name).length === 0
}

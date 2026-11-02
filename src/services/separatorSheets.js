/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Separator sheets for paper intake: the scan profiles a sheet belongs to, the
 * case numbers a clerk types, and the PDF the server renders with one sheet per
 * case number. The scanner batch is later cut at each sheet.
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/tasks.md#task-2.2
 */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * Turn what a clerk typed into case numbers: one per line, comma or semicolon,
 * trimmed, without blanks or repeats, in the order typed.
 *
 * @param {string|undefined} text The typed case numbers.
 * @return {string[]} The case numbers.
 * @spec openspec/changes/scan-intake-with-separator-sheets/tasks.md#task-2.2
 */
export function parseCaseNumbers(text) {
	const seen = new Set()
	for (const part of String(text ?? '').split(/[\n,;]+/)) {
		const value = part.trim()
		if (value !== '') {
			seen.add(value)
		}
	}
	return [...seen]
}

/**
 * The declared scan profiles.
 *
 * @return {Promise<Array<{id: string, label: string}>>} The profiles.
 * @spec openspec/changes/scan-intake-with-separator-sheets/tasks.md#task-2.2
 */
export async function listScanProfiles() {
	const { data } = await axios.get(generateUrl('/apps/filinq/api/scan/profiles'))
	return Array.isArray(data?.results) ? data.results : []
}

/**
 * Render the separator sheets as one PDF.
 *
 * @param {string} profileId The scan profile the sheets belong to.
 * @param {string[]} caseNumbers One sheet per case number.
 * @return {Promise<Blob>} The PDF.
 * @spec openspec/changes/scan-intake-with-separator-sheets/tasks.md#task-2.2
 */
export async function renderSeparatorSheets(profileId, caseNumbers) {
	const { data } = await axios.post(
		generateUrl('/apps/filinq/api/scan/separators'),
		{ profileId, caseNumbers },
		{ responseType: 'blob' },
	)
	return data
}

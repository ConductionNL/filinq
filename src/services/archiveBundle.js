/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Reading the case archive answers for the download-all-files leaf: the
 * preflight of `GET /api/case-archive/preflight` and the manifest of
 * `POST /api/case-archive/manifest`. Free of Vue, so it can be tested alone.
 */

const REASONS = ['ceiling', 'permission', 'missing']

/**
 * A size in megabytes with one decimal.
 *
 * @param {number} bytes The size in bytes.
 *
 * @return {string} For example "1.5".
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
export function megabytes(bytes) {
	return (Number(bytes || 0) / 1048576).toFixed(1)
}

/**
 * Whether the handler must be warned before the job starts.
 *
 * @param {?object} preflight The preflight answer.
 *
 * @return {boolean} True when the files exceed the ceiling.
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
export function needsWarning(preflight) {
	return (
		preflight !== null
		&& preflight !== undefined
		&& preflight.exceedsCeiling === true
	)
}

/**
 * Every file the archive left out, grouped by reason. A reason this leaf
 * does not know goes to `other` rather than disappearing.
 *
 * @param {object} manifest The manifest answer.
 *
 * @return {{ceiling: string[], permission: string[], missing: string[], other: string[]}} Names per reason.
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
export function excludedByReason(manifest) {
	const grouped = { ceiling: [], permission: [], missing: [], other: [] }
	const excluded = Array.isArray(manifest && manifest.excluded)
		? manifest.excluded
		: []
	for (const entry of excluded) {
		const reason = REASONS.includes(entry.reason) ? entry.reason : 'other'
		grouped[reason].push(entry.name || '')
	}

	return grouped
}

/**
 * The file id of the written archive, or 0 when none was written.
 *
 * @param {object} manifest The manifest answer.
 *
 * @return {number} The file id.
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
export function archiveFileId(manifest) {
	return Number((manifest && manifest.archive && manifest.archive.fileId) || 0)
}

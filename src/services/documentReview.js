/**
 * The review workbench's per-document checked mark and the pure helpers its
 * screen is built from.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The server keeps the mark (`documentReview`) and decides whether it still
 * holds: any toggle, grondslag change or manual entity after the check voids
 * it. The screen only reads that verdict back after every change.
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.4
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * The review endpoint of one file.
 *
 * @param {number} fileId The document.
 * @param {string} [suffix] A path below it.
 * @return {string} The URL.
 */
function reviewUrl(fileId, suffix = '') {
	return generateUrl(`/apps/filinq/api/review/${fileId}${suffix}`)
}

/**
 * Where one document's review stands.
 *
 * @param {number} fileId The document.
 * @return {Promise<object>} `{ checked, stale, extracted, entityCount, manualEntityCount, review, gateMode }`.
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.4
 */
export async function fetchReview(fileId) {
	const response = await axios.get(reviewUrl(fileId))
	return response.data
}

/**
 * Mark one document as reviewed by the signed-in person.
 *
 * @param {number} fileId The document.
 * @param {string} [note] The reviewer's note.
 * @return {Promise<object>} The status after the check.
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.4
 */
export async function checkReview(fileId, note = '') {
	const response = await axios.post(reviewUrl(fileId, '/check'), { note })
	return response.data
}

/**
 * Clear one document's reviewed mark.
 *
 * @param {number} fileId The document.
 * @return {Promise<object>} The status after the uncheck.
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.4
 */
export async function uncheckReview(fileId) {
	const response = await axios.delete(reviewUrl(fileId, '/check'))
	return response.data
}

/**
 * The state the review card shows.
 *
 * @param {object|null} status The status from the server.
 * @return {'reviewed'|'stale'|'unchecked'|'unknown'} The state.
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.4
 */
export function reviewState(status) {
	if (!status || typeof status !== 'object') {
		return 'unknown'
	}
	if (status.checked === true) {
		return 'reviewed'
	}
	if (status.stale === true) {
		return 'stale'
	}
	return 'unchecked'
}

/**
 * The policy badges of one entity: the prohibition or the standing consent
 * that pre-applied to it, each with the list it links to.
 *
 * A prohibition wins on conflict, so at most one badge is ever returned.
 *
 * @param {object} entity A review row.
 * @return {Array<{kind: 'prohibition'|'standingConsent', ruleId: string, ruleName: string, route: object}>} The badges.
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.3
 */
export function policyBadges(entity) {
	const prohibition = entity?.prohibitionMatch
	if (prohibition && prohibition.ruleId) {
		return [
			{
				kind: 'prohibition',
				ruleId: String(prohibition.ruleId),
				ruleName: prohibition.ruleName || '',
				route: { name: 'Prohibitions', query: { rule: String(prohibition.ruleId) } },
			},
		]
	}
	const consent = entity?.standingConsentMatch
	if (consent && consent.ruleId) {
		return [
			{
				kind: 'standingConsent',
				ruleId: String(consent.ruleId),
				ruleName: consent.ruleName || '',
				route: { name: 'StandingConsents', query: { rule: String(consent.ruleId) } },
			},
		]
	}
	return []
}

/**
 * Whether an entity's grondslagen are a proposal the reviewer has not touched:
 * filled from a matched rule, or from the per-type proposal mapping.
 *
 * @param {object} entity A review row.
 * @return {boolean} True while the bases are proposed.
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.3
 */
export function basesAreProposed(entity) {
	if (!entity || entity._basesConfirmed === true) {
		return false
	}
	const bases = entity._decisionBases ?? entity.bases
	if (!Array.isArray(bases) || bases.length === 0) {
		return false
	}
	return entity.basesSource === 'rule' || entity.basesProposed === true
}

/**
 * The grondslagen proposed for an entity type by the admin mapping.
 *
 * @param {object|null} mapping `filinq.grondslagen.entity_type_bases`: type → slug or slugs.
 * @param {string} type The entity type.
 * @return {Array<string>} The proposed slugs, possibly empty.
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.2
 */
export function proposedBasesFor(mapping, type) {
	const value = mapping && typeof mapping === 'object' ? mapping[type] : null
	if (Array.isArray(value)) {
		return value.filter((slug) => typeof slug === 'string' && slug !== '')
	}
	if (typeof value === 'string' && value !== '') {
		return [value]
	}
	return []
}

/**
 * The text a reviewer selected inside one pane, trimmed and on one line;
 * empty when the selection lies outside it or is blank.
 *
 * No offsets are taken: OpenRegister finds every occurrence itself.
 *
 * @param {{toString: function(): string, anchorNode: Node|null, focusNode: Node|null}|null} selection The window selection.
 * @param {Node|null} pane The pane the selection must fall in.
 * @return {string} The selected text.
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.2
 */
export function selectedTextIn(selection, pane) {
	if (!selection || !pane) {
		return ''
	}
	const inside = (node) => !!node && typeof pane.contains === 'function' && pane.contains(node)
	if (!inside(selection.anchorNode) || !inside(selection.focusNode)) {
		return ''
	}
	return String(selection.toString()).replace(/\s+/g, ' ').trim()
}

/**
 * The manual-entity request a selection becomes: value, type and grondslagen.
 *
 * @param {string} value The selected text.
 * @param {string} type The chosen entity type.
 * @param {Array<string>} bases The chosen grondslagen.
 * @return {{value: string, type: string, bases: Array<string>}} The payload for `addManualEntity`.
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.2
 */
export function manualEntityPayload(value, type, bases) {
	return {
		value: String(value || '').trim(),
		type: String(type || ''),
		bases: Array.isArray(bases) ? [...bases] : [],
	}
}

/**
 * The anonymised pane's file, from the file's `anonymizationLink`; null while
 * nothing has been committed.
 *
 * @param {object|null} link The link object, or null.
 * @return {{fileName: string, mimeType: string, path: string}|null} The file, or null.
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.1
 */
export function anonymizedFileOf(link) {
	if (!link || !link.anonymizedFileId) {
		return null
	}
	const fileName = link.anonymizedFileName || link.anonymizedFilePath?.split('/').pop() || ''
	return {
		fileName,
		// The link stores no MIME type; the viewers are picked by file name.
		mimeType: '',
		path: link.anonymizedFilePath || '',
	}
}

/**
 * The link object whose `sourceFileId` is this file, or null.
 *
 * @param {number} fileId The source document.
 * @return {Promise<object|null>} The link.
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.1
 */
export async function fetchAnonymizationLink(fileId) {
	try {
		const response = await axios.get(
			generateUrl('/apps/openregister/api/objects/filinq/anonymizationLink'),
			{ params: { sourceFileId: fileId } },
		)
		const results = response.data?.results || []
		return results.find((row) => Number(row.sourceFileId) === Number(fileId)) || null
	} catch {
		return null
	}
}

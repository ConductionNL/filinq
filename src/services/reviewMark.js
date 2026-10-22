/**
 * The review mark: a person confirms the findings of one detection run
 * before a redacted copy may be written.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The server keys a mark to the exact entities that were checked, so the
 * entities sent with the mark must be the entities sent with the anonymise
 * call. `redactionPayload` builds that one list for both calls, and
 * `reviewSignature` lets the screen notice that the list changed after the
 * check, in which case the server would refuse the old mark anyway.
 *
 * @spec openspec/changes/anonymise-asks-for-the-review-mark/tasks.md#task-4
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * The entities to redact, longest value first.
 *
 * OpenRegister replaces matches in payload order, so a shorter span that runs
 * first eats its own text out of an overlapping longer one ("Claudia Fischer"
 * before "Mevrouw Claudia Fischer" leaves a dangling "Mevrouw").
 *
 * @param {Array<{type: string, value: string, confidence: number}>} entities The included entities.
 * @return {Array<{type: string, value: string, confidence: number}>} The sorted copy.
 * @spec openspec/changes/anonymise-asks-for-the-review-mark/tasks.md#task-4
 */
export function redactionPayload(entities) {
	return (entities || [])
		.map((e) => ({ type: e.type, value: e.value, confidence: e.confidence }))
		.sort((a, b) => (b.value || '').length - (a.value || '').length)
}

/**
 * A stable name for a set of entities: what was checked, in any order.
 *
 * Confidence is left out, as on the server: a re-run that finds the same
 * things with another score found the same things.
 *
 * @param {Array<{type: string, value: string}>} entities The entities.
 * @return {string} The signature.
 * @spec openspec/changes/anonymise-asks-for-the-review-mark/tasks.md#task-4
 */
export function reviewSignature(entities) {
	return JSON.stringify(
		(entities || []).map((e) => `${e.type ?? ''}:${e.value ?? ''}`).sort(),
	)
}

/**
 * Whether a mark held on screen still covers these entities.
 *
 * @param {{signature: string}|null} mark The mark recorded this session.
 * @param {Array<object>} entities The entities that would be sent now.
 * @return {boolean} True when the mark is about exactly these entities.
 * @spec openspec/changes/anonymise-asks-for-the-review-mark/tasks.md#task-4
 */
export function reviewIsCurrent(mark, entities) {
	return !!mark && mark.signature === reviewSignature(entities)
}

/**
 * Record that the signed-in person checked these entities in this file.
 *
 * @param {number} fileId The document.
 * @param {Array<object>} entities The entities exactly as the anonymise call will send them.
 * @return {Promise<{checkedBy: string, checkedAt: string, signature: string}>} The mark.
 * @spec openspec/changes/anonymise-asks-for-the-review-mark/tasks.md#task-4
 */
export async function markChecked(fileId, entities) {
	const response = await axios.post(
		generateUrl(`/apps/filinq/api/redaction/documents/${fileId}/checked`),
		{ entities },
	)
	return {
		checkedBy: response.data?.checkedBy || '',
		checkedAt: response.data?.checkedAt || '',
		signature: reviewSignature(entities),
	}
}

/**
 * The latest mark of each listed file the user can open.
 *
 * @param {Array<number|string>} fileIds The files, at most 100.
 * @return {Promise<object>} Marks keyed by file id; a file nobody checked is absent.
 * @spec openspec/changes/anonymise-asks-for-the-review-mark/tasks.md#task-5
 */
export async function fetchReviewMarks(fileIds = []) {
	if (fileIds.length === 0) {
		return {}
	}
	const response = await axios.get(
		generateUrl('/apps/filinq/api/redaction/documents/checked'),
		{ params: { fileIds: fileIds.join(',') } },
	)
	return response.data?.marks || {}
}

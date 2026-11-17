/**
 * Image redaction: what an extract or anonymise answer says about image
 * content, and the calls for a page, page-range or whole-document redaction.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The server decides; these helpers carry its answer to the screen. Image
 * content that was not looked at or not burned is never shown as clean.
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-missing-image-capability-degrades-fail-flagged-never-fail-silent-req-ddimr-002
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * The scopes a decision may take.
 *
 * @type {string[]}
 */
export const SCOPES = ['page', 'pageRange', 'document']

/**
 * The scope endpoint of a document.
 *
 * @param {number|string} fileId The document.
 * @return {string} The URL.
 */
function base(fileId) {
	return generateUrl(`/apps/filinq/api/anonymization/scope/${encodeURIComponent(fileId)}`)
}

/**
 * The warning for images the extract run did not look at.
 *
 * @param {object} skipped The extract answer's imageDetectionSkipped.
 * @return {string} The warning.
 */
function skippedImagesWarning(skipped) {
	if (skipped.reason === 'backend_unavailable') {
		return t(
			'filinq',
			'This document contains images, and no image detection is available here, so the images were not checked for personal data. Review them by hand.',
		)
	}
	return t(
		'filinq',
		'The images in this document could not be checked for personal data. Review them by hand.',
	)
}

/**
 * The warning for the image content of an extract answer.
 *
 * @param {object} data The extract endpoint's answer.
 * @return {string|null} The warning, or null when the image content was looked at.
 * @spec openspec/changes/image-redaction/tasks.md#task-4.2
 */
export function imageExtractionWarning(data) {
	if (!data) {
		return null
	}
	if (data.imageDetectionSkipped) {
		return skippedImagesWarning(data.imageDetectionSkipped)
	}
	if (data.objectDetection === 'not-run') {
		return t(
			'filinq',
			'Signature detection is not available on this instance, so signatures were not looked for.',
		)
	}
	return null
}

/**
 * The warning for the image content of an anonymise answer.
 *
 * @param {object} result The anonymise endpoint's answer.
 * @return {string|null} The warning, or null when nothing is pending.
 * @spec openspec/changes/image-redaction/tasks.md#task-4.2
 */
export function imageRedactionWarning(result) {
	if (result && result.imageRedactionPending === true) {
		return t(
			'filinq',
			'The images in this copy were not redacted, so they may still show personal data. Do not publish it as it is.',
		)
	}
	return null
}

/**
 * Copy the image warning onto a queue entry.
 *
 * @param {object} entry The queue entry.
 * @param {object} data The extract endpoint's answer.
 * @return {boolean} True when image content was not looked at, so an empty
 *                   entity list must not mark it done.
 * @spec openspec/changes/image-redaction/tasks.md#task-4.2
 */
export function applyImageFlags(entry, data) {
	entry.imageWarning = imageExtractionWarning(data)
	return Boolean(data && data.imageDetectionSkipped)
}

/**
 * Copy the image redaction state of an anonymise answer onto a queue entry.
 *
 * @param {object} entry The queue entry.
 * @param {object} data The anonymise endpoint's answer.
 * @return {void}
 * @spec openspec/changes/image-redaction/tasks.md#task-4.2
 */
export function applyImageRedactionResult(entry, data) {
	// Image content the seam did not burn (image-redaction REQ-DDIMR-006).
	entry.imageRedactionPending = data.imageRedactionPending === true
	entry.burnedRegionCount = data.burnedRegionCount ?? null
}

/**
 * Whether an entity row was found in an image.
 *
 * @param {object} entity The entity row.
 * @return {boolean} True for an image-origin row.
 * @spec openspec/changes/image-redaction/tasks.md#task-4.1
 */
export function isImageEntity(entity) {
	return Boolean(entity && entity.origin === 'image')
}

/**
 * A short label for a scope decision.
 *
 * @param {object} decision The decision.
 * @return {string} The label.
 * @spec openspec/changes/image-redaction/tasks.md#task-6.3
 */
export function scopeLabel(decision) {
	if (!decision) {
		return ''
	}
	if (decision.scope === 'document') {
		return t('filinq', 'Whole document withheld')
	}
	if (decision.scope === 'page' || decision.fromPage === decision.toPage) {
		return t('filinq', 'Page {page} redacted', { page: decision.fromPage })
	}
	return t('filinq', 'Pages {from} to {to} redacted', { from: decision.fromPage, to: decision.toPage })
}

/**
 * The scope state of a document: its decisions, page count and whether page scopes can be committed.
 *
 * @param {number|string} fileId The document.
 * @return {Promise<{decisions: object[], pageCount: number, pageScopesAvailable: boolean}>} The state.
 * @spec openspec/changes/image-redaction/tasks.md#task-6.3
 */
export async function fetchScope(fileId) {
	const { data } = await axios.get(base(fileId))
	return data
}

/**
 * The request body of a decision.
 *
 * @param {object} decision The decision as entered.
 * @return {object} scope, fromPage, toPage and bases.
 */
function body(decision) {
	const payload = { scope: decision.scope, bases: decision.bases || [] }
	if (decision.scope !== 'document') {
		payload.fromPage = Number(decision.fromPage)
		payload.toPage = decision.scope === 'page' ? Number(decision.fromPage) : Number(decision.toPage)
	}
	return payload
}

/**
 * Store one decision, and commit it right after when asked.
 *
 * @param {number|string} fileId The document.
 * @param {object} decision The decision.
 * @param {boolean} commit Whether to commit it too.
 * @return {Promise<{ok: boolean, decision?: object, outcome?: object, error?: string}>} The answer.
 * @spec openspec/changes/image-redaction/tasks.md#task-6.3
 */
export async function decideScope(fileId, decision, commit = false) {
	try {
		const { data } = await axios.post(base(fileId), body(decision))
		if (commit !== true) {
			return { ok: true, decision: data }
		}
		const answer = await axios.post(`${base(fileId)}/commit`, body(decision))
		return { ok: true, decision: data, outcome: answer.data }
	} catch (error) {
		return {
			ok: false,
			error: error.response?.data?.error || t('filinq', 'The redaction could not be saved.'),
		}
	}
}

/**
 * A number clamped to the normalised [0..1] range, or null when it is not a number.
 *
 * @param {number|string|null} value The value.
 * @return {number|null} The clamped number.
 */
function unit(value) {
	const number = Number(value)
	if (value === null || value === '' || Number.isFinite(number) === false) {
		return null
	}
	return Math.min(1, Math.max(0, number))
}

/**
 * One normalised box as an overlay rectangle, or null when it cannot be placed.
 *
 * A box without a page or with no area is left out: the entity row stays in
 * the table, which is the source of truth, so a missed overlay hides nothing.
 *
 * @param {object} box `{ page, x, y, w, h }`, normalised to [0..1].
 * @return {{page: number, x: number, y: number, w: number, h: number}|null} The rectangle.
 */
function placeBox(box) {
	if (!box || typeof box !== 'object') {
		return null
	}
	const page = Number(box.page)
	const x = unit(box.x)
	const y = unit(box.y)
	const w = unit(box.w)
	const h = unit(box.h)
	if (Number.isInteger(page) === false || page < 1 || [x, y, w, h].includes(null)) {
		return null
	}
	const width = Math.min(w, 1 - x)
	const height = Math.min(h, 1 - y)
	if (width <= 0 || height <= 0) {
		return null
	}
	return { page, x, y, w: width, h: height }
}

/**
 * The region overlays of the image-origin entity rows.
 *
 * Each overlay mirrors its row's decision: a row the reviewer rejected
 * (`_decisionSkip`) is kept visible and its region is not burned; every other
 * row is redacted. The row's index in the entity list ties the overlay to the
 * table row, so there is one decision and no second state.
 *
 * @param {Array<object>} entities The entity rows of the review.
 * @return {Array<{index: number, page: number, x: number, y: number, w: number, h: number, type: string, state: string}>} The overlays.
 * @spec openspec/changes/image-redaction/tasks.md#task-4.1
 */
export function regionOverlays(entities) {
	if (!Array.isArray(entities)) {
		return []
	}
	const overlays = []
	entities.forEach((entity, index) => {
		if (!isImageEntity(entity) || !Array.isArray(entity.boxes)) {
			return
		}
		const state = entity._decisionSkip === true ? 'keep' : 'redact'
		const type = String(entity.type || '')
		for (const box of entity.boxes) {
			const placed = placeBox(box)
			if (placed !== null) {
				overlays.push({ index, ...placed, type, state })
			}
		}
	})
	return overlays
}

/**
 * The accessible label of one region overlay.
 *
 * @param {{type: string, state: string}} overlay The overlay.
 * @return {string} The label.
 * @spec openspec/changes/image-redaction/tasks.md#task-4.1
 */
export function regionLabel(overlay) {
	const type = overlay?.type || t('filinq', 'Unknown')
	if (overlay?.state === 'keep') {
		return t('filinq', 'Image region kept visible: {type}', { type })
	}
	return t('filinq', 'Image region to be redacted: {type}', { type })
}

/**
 * Destruction lists (vernietigingslijsten): the archivist's review of what
 * OpenRegister proposes to destroy, and the approve, partial-approve and
 * reject calls behind it.
 *
 * Every call goes to OpenRegister's own `api/archival/destruction-lists*`
 * routes. filinq adds no route of its own and keeps no copy; who may review
 * is OpenRegister's archivist check, which answers 403 to anybody else.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { answer } from './apiAnswer.js'

const LISTS = '/apps/openregister/api/archival/destruction-lists'

/** The statuses OpenRegister gives a destruction list, in process order. */
export const DESTRUCTION_LIST_STATUSES = Object.freeze([
	'in_review',
	'awaiting_second_approval',
	'approved',
	'rejected',
	'completed',
	'executing',
	'executed',
])

/** The statuses in which a list still waits for the archivist's decision. */
export const DECIDABLE_STATUSES = Object.freeze([
	'in_review',
	'awaiting_second_approval',
])

/**
 * The URL of a destruction list route.
 *
 * @param {string} [id] The list's uuid; none for the collection.
 * @param {string} [action] `approve` or `reject`.
 * @return {string} The URL.
 */
function listUrl(id = '', action = '') {
	const parts = [LISTS]
	if (id !== '') {
		parts.push(encodeURIComponent(id))
	}
	if (action !== '') {
		parts.push(action)
	}
	return generateUrl(parts.join('/'))
}

/**
 * The destruction lists, optionally of one status.
 *
 * @param {string} [status] A status from DESTRUCTION_LIST_STATUSES; empty for every list.
 * @return {Promise<{ok: boolean, data?: object, status?: number, error?: string}>} The lists, or the refusal.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function loadDestructionLists(status = '') {
	const params = DESTRUCTION_LIST_STATUSES.includes(status) ? { status } : {}
	return answer(axios.get(listUrl(), { params }))
}

/**
 * One destruction list with its proposed objects.
 *
 * @param {string} id The list's uuid.
 * @return {Promise<{ok: boolean, data?: object, status?: number, error?: string}>} The list, or the refusal.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function loadDestructionList(id) {
	return answer(axios.get(listUrl(id)))
}

/**
 * The rows the overview shows, from OpenRegister's list summaries.
 *
 * @param {object} data The answer of loadDestructionLists().
 * @return {Array<{uuid: string, status: string, createdAt: string, objectCount: number}>} One row per list.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function listRows(data) {
	const results = Array.isArray(data?.results) ? data.results : []
	return results
		.filter(
			(row) =>
				row !== null
				&& typeof row === 'object'
				&& typeof row.uuid === 'string',
		)
		.map((row) => ({
			uuid: row.uuid,
			status: typeof row.status === 'string' ? row.status : '',
			createdAt: typeof row.createdAt === 'string' ? row.createdAt : '',
			objectCount: Number.isInteger(row.entryCount) ? row.entryCount : 0,
		}))
}

/**
 * Whether OpenRegister has a destruction list register and schema to read.
 *
 * @param {object} data The answer of loadDestructionLists().
 * @return {boolean} False when OpenRegister says the lists are not configured.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function listsConfigured(data) {
	return data?.configured !== false
}

/**
 * The proposed objects of one list, as the detail table shows them.
 *
 * @param {object} list The answer of loadDestructionList().
 * @return {Array<{uuid: string, title: string, schema: string, register: string, archiefactiedatum: string, classification: string}>} One row per object.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function entryRows(list) {
	const entries = Array.isArray(list?.objects) ? list.objects : []
	const text = (value) =>
		value === null || value === undefined ? '' : String(value)
	return entries
		.filter(
			(entry) =>
				entry !== null
				&& typeof entry === 'object'
				&& typeof entry.uuid === 'string',
		)
		.map((entry) => ({
			uuid: entry.uuid,
			title: text(entry.title) || entry.uuid,
			schema: text(entry.schema),
			register: text(entry.register),
			archiefactiedatum: text(entry.archiefactiedatum),
			classification: text(entry.classification),
		}))
}

/**
 * The status of a list as read, whichever shape OpenRegister answered.
 *
 * @param {object} list A list detail or an approve/reject answer; both carry the list's data at the top level.
 * @return {string} The status, or empty.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function listStatus(list) {
	return typeof list?.status === 'string' ? list.status : ''
}

/**
 * Whether the archivist can still approve or reject a list in this status.
 *
 * @param {string} status The list's status.
 * @return {boolean} True while the list waits for a decision.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function isDecidable(status) {
	return DECIDABLE_STATUSES.includes(status)
}

/**
 * The excluded objects that still lack a reason.
 *
 * @param {Array<string>} excluded The uuids taken off the list.
 * @param {object} reasons The reason per uuid.
 * @return {Array<string>} The uuids without a reason.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function exclusionsWithoutReason(excluded, reasons) {
	const given = reasons !== null && typeof reasons === 'object' ? reasons : {}
	return (Array.isArray(excluded) ? excluded : []).filter(
		(uuid) => typeof given[uuid] !== 'string' || given[uuid].trim() === '',
	)
}

/**
 * The body OpenRegister's approve route reads: `approve_all` when nothing is
 * excluded, otherwise `approve_partial` with each excluded uuid and its reason.
 *
 * @param {Array<string>} excluded The uuids taken off the list.
 * @param {object} reasons The reason per uuid.
 * @return {{action: string, excluded: Array<string>, exclusionReasons: object}} The body.
 * @throws {Error} When an excluded object has no reason; nothing is sent then.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function approvalBody(excluded, reasons) {
	const uuids = [...new Set(Array.isArray(excluded) ? excluded : [])]
	if (uuids.length === 0) {
		return { action: 'approve_all', excluded: [], exclusionReasons: {} }
	}

	const missing = exclusionsWithoutReason(uuids, reasons)
	if (missing.length > 0) {
		throw new Error(
			'Every excluded object needs a reason: ' + missing.join(', '),
		)
	}

	const exclusionReasons = {}
	for (const uuid of uuids) {
		exclusionReasons[uuid] = reasons[uuid].trim()
	}
	return { action: 'approve_partial', excluded: uuids, exclusionReasons }
}

/**
 * Approve a list in full, or in part with a reason for each excluded object.
 *
 * @param {string} id The list's uuid.
 * @param {Array<string>} [excluded] The uuids taken off the list.
 * @param {object} [reasons] The reason per excluded uuid.
 * @return {Promise<{ok: boolean, data?: object, status?: number, error?: string, missingReasons?: Array<string>}>} The updated list, or the refusal.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function approveDestructionList(id, excluded = [], reasons = {}) {
	let body
	try {
		body = approvalBody(excluded, reasons)
	} catch {
		return Promise.resolve({
			ok: false,
			status: 0,
			error: '',
			missingReasons: exclusionsWithoutReason(excluded, reasons),
		})
	}
	return answer(axios.post(listUrl(id, 'approve'), body))
}

/**
 * Reject a list. A rejection needs a reason; without one nothing is sent.
 *
 * @param {string} id The list's uuid.
 * @param {string} reason Why the list is rejected.
 * @return {Promise<{ok: boolean, data?: object, status?: number, error?: string, missingReason?: boolean}>} The updated list, or the refusal.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function rejectDestructionList(id, reason) {
	const text = typeof reason === 'string' ? reason.trim() : ''
	if (text === '') {
		return Promise.resolve({
			ok: false,
			status: 0,
			error: '',
			missingReason: true,
		})
	}
	return answer(axios.post(listUrl(id, 'reject'), { reason: text }))
}

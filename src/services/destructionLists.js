/**
 * Destruction lists (vernietigingslijsten): the archivist's review of what
 * OpenRegister proposes to destroy, and the approve, partial-approve and
 * reject calls behind it. The overview is a manifest index over the same
 * OpenRegister route (`entitySource: endpoint`), so it needs nothing here.
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
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { answer } from './apiAnswer.js'
import { holdStatus } from './legalHolds.js'

const LISTS = '/apps/openregister/api/archival/destruction-lists'

/** The statuses in which a list still waits for the archivist's decision; OpenRegister knows approved, rejected, completed, executing and executed besides. */
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

/**
 * The legal hold status of each proposed record, read through filinq's hold
 * register. The server names the holding case only to hold authority; for
 * everybody else a status carries `held` without the case names.
 *
 * @param {Array<string>} uuids The uuids of the proposed records.
 * @param {(uuid: string) => Promise<object|null>} [read] The status read; filinq's hold register by default.
 * @return {Promise<object>} The status per uuid; a record whose status could not be read is left out.
 * @spec openspec/changes/archiefwet-retention-engine/tasks.md#task-5.1
 */
export async function loadHolds(uuids, read = holdStatus) {
	const unique = [
		...new Set(
			(Array.isArray(uuids) ? uuids : []).filter(
				(uuid) => typeof uuid === 'string' && uuid !== '',
			),
		),
	]
	const statuses = await Promise.all(
		unique.map((uuid) => read(uuid).catch(() => null)),
	)
	const holds = {}
	unique.forEach((uuid, index) => {
		const status = statuses[index]
		if (status !== null && typeof status === 'object') {
			holds[uuid] = status
		}
	})
	return holds
}

/**
 * What the destruction list says about a record's legal hold: nothing when it
 * is not held, the holding cases by name when the server gave them, and
 * "Under a legal hold" without a name otherwise.
 *
 * @param {object|null|undefined} status The record's hold status.
 * @return {string} The label, or empty.
 * @spec openspec/changes/archiefwet-retention-engine/tasks.md#task-5.1
 */
export function holdLabel(status) {
	if (status?.held !== true) {
		return ''
	}
	const names = (Array.isArray(status.cases) ? status.cases : [])
		.map((holdCase) =>
			typeof holdCase?.name === 'string' ? holdCase.name.trim() : '',
		)
		.filter((name) => name !== '')
	if (names.length === 0) {
		return t('filinq', 'Under a legal hold')
	}
	return t('filinq', 'Under a legal hold: {cases}', {
		cases: names.join(', '),
	})
}

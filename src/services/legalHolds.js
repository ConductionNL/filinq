/**
 * Legal hold cases: the calls behind the hold register, and the labels it shows.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

const base = () => generateUrl('/apps/filinq/api/legal-holds')

/**
 * Run a call and answer { ok, data } or { ok: false, error, status }.
 *
 * @param {() => Promise<{data: object}>} call The axios call.
 * @return {Promise<object>} The outcome.
 */
async function outcome(call) {
	try {
		const { data } = await call()
		return { ok: true, data }
	} catch (error) {
		return {
			ok: false,
			status: error.response?.status || 0,
			error:
				error.response?.data?.error
				|| t(
					'filinq',
					'The legal hold could not be saved. Try again later.',
				),
		}
	}
}

/**
 * The hold register, filtered.
 *
 * @param {object} filters status, holdType, custodian.
 * @return {Promise<object>} The outcome, data.results the cases.
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */
export function listCases(filters = {}) {
	const params = {}
	for (const key of ['status', 'holdType', 'custodian']) {
		if (filters[key]) {
			params[key] = filters[key]
		}
	}
	return outcome(() => axios.get(base(), { params }))
}

/**
 * Open a case.
 *
 * @param {object} input The case fields.
 * @return {Promise<object>} The outcome, data the case.
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */
export function createCase(input) {
	return outcome(() => axios.post(base(), input))
}

/**
 * Release a case.
 *
 * @param {string} uuid The case.
 * @param {string} releaseReason Why.
 * @return {Promise<object>} The outcome.
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */
export function releaseCase(uuid, releaseReason) {
	return outcome(() =>
		axios.post(`${base()}/${encodeURIComponent(uuid)}/release`, {
			releaseReason,
		}),
	)
}

/**
 * Add records to an active case.
 *
 * @param {string} uuid The case.
 * @param {object} scope scopeDocuments and scopeDossiers to add.
 * @return {Promise<object>} The outcome.
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */
export function addScope(uuid, scope) {
	return outcome(() =>
		axios.post(`${base()}/${encodeURIComponent(uuid)}/scope`, scope),
	)
}

/**
 * Retry the records that failed.
 *
 * @param {string} uuid The case.
 * @return {Promise<object>} The outcome.
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */
export function retryCase(uuid) {
	return outcome(() => axios.post(`${base()}/${encodeURIComponent(uuid)}/retry`))
}

/**
 * Whether a record is under a legal hold, and which cases hold it (named to hold authority only).
 *
 * @param {string} objectId The record uuid.
 * @return {Promise<{held: boolean, cases: Array}|null>} The status, null when unknown.
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.2
 */
export async function holdStatus(objectId) {
	if (!objectId) {
		return null
	}
	const answer = await outcome(() =>
		axios.get(`${base()}/status/${encodeURIComponent(objectId)}`),
	)
	return answer.ok ? answer.data : null
}

/**
 * Split pasted references into a clean list: commas, spaces or new lines.
 *
 * @param {string} text The pasted text.
 * @return {Array<string>} The references, each once.
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */
export function parseRefs(text) {
	return [
		...new Set(
			String(text || '')
				.split(/[\s,;]+/)
				.map((ref) => ref.trim())
				.filter(Boolean),
		),
	]
}

/**
 * The matter types, labelled.
 *
 * @return {Array<{id: string, label: string}>} The options.
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */
export function holdTypeOptions() {
	return [
		{ id: 'litigation', label: t('filinq', 'Litigation') },
		{ id: 'audit', label: t('filinq', 'Audit') },
		{ id: 'woo-appeal', label: t('filinq', 'Woo appeal') },
		{ id: 'other', label: t('filinq', 'Other') },
	]
}

/**
 * What happened to one record, in words.
 *
 * @param {object} entry A fan-out entry.
 * @return {string} The record outcome.
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */
export function recordLabel(entry) {
	const labels = {
		held: t('filinq', 'Frozen'),
		held_by_other: t('filinq', 'Already frozen by another party'),
		failed: t('filinq', 'Not frozen'),
		released: t('filinq', 'Released'),
		kept_for_other_case: t('filinq', 'Still frozen for another hold'),
		left_to_other_party: t('filinq', "Left to another party's hold"),
	}
	return labels[entry?.record] ?? entry?.record ?? ''
}

/**
 * What happened to the files of one record, in words.
 *
 * @param {object} entry A fan-out entry.
 * @return {string} The file outcome.
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */
export function fileLabel(entry) {
	const labels = {
		locked: t('filinq', 'Files locked'),
		no_files: t('filinq', 'No files'),
		unavailable: t('filinq', 'Files not locked: file locking is not installed'),
		failed: t('filinq', 'Files not locked'),
		unlocked: t('filinq', 'Files unlocked'),
		kept_for_other_case: t('filinq', 'Files stay locked for another hold'),
	}
	return labels[entry?.file] ?? entry?.file ?? ''
}

/**
 * Whether a case has records to retry.
 *
 * @param {object} holdCase The case.
 * @return {boolean} True when a freeze or unfreeze failed.
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */
export function needsRetry(holdCase) {
	return (holdCase?.fanOut || []).some(
		(entry) => entry.record === 'failed' || entry.file === 'failed',
	)
}

/**
 * Email ingestion: the calls behind the status page and the inbox mapping,
 * and the words for a row's state.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/email-ingestion/tasks.md#3-1
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

const BASE = '/apps/filinq/api/email-ingestion'

/**
 * The state of a record as the status chip shows it.
 *
 * A filed email without its PDF copy is its own state: it is captured, and
 * it can still be converted.
 *
 * @param {object} row An emailDocument record.
 * @return {string} filed, not-converted or failed.
 * @spec openspec/changes/email-ingestion/tasks.md#3-1
 */
export function rowState(row) {
	if (row.status === 'failed') {
		return 'failed'
	}
	if (row.status === 'filed' && !row.pdfFileRef) {
		return 'not-converted'
	}
	return row.status === 'filed' ? 'filed' : 'received'
}

/**
 * The chip label of a state.
 *
 * @param {string} state From rowState().
 * @return {string} The label.
 * @spec openspec/changes/email-ingestion/tasks.md#3-1
 */
export function stateLabel(state) {
	return {
		filed: t('filinq', 'Filed'),
		'not-converted': t('filinq', 'Filed, not converted'),
		failed: t('filinq', 'Failed'),
		received: t('filinq', 'Received'),
	}[state]
}

/**
 * Why an email failed, as a person reads it, with what to do about it.
 *
 * @param {string} reason The failure reason code.
 * @return {string} The sentence.
 * @spec openspec/changes/email-ingestion/tasks.md#4-3
 */
export function failureText(reason) {
	switch (reason) {
	case 'unsupported-format':
		return t(
			'filinq',
			'Outlook .msg files cannot be read. Save the email as .eml and put it in the inbox again.',
		)
	case 'unparseable':
		return t(
			'filinq',
			'This file could not be read as an email. Export it again as .eml.',
		)
	case 'dossier-folder-unavailable':
		return t(
			'filinq',
			'The dossier of this inbox has no folder that can be reached. Check the inbox mapping.',
		)
	case 'filing-failed':
		return t('filinq', 'The email could not be moved into the dossier folder.')
	default:
		return reason || t('filinq', 'Unknown reason')
	}
}

/**
 * Whether a record belongs to a conversation of more than one email.
 *
 * @param {object} row The record.
 * @param {Array<object>} rows All records on the page.
 * @return {boolean} True when another record has the same thread key.
 * @spec openspec/changes/email-ingestion/tasks.md#3-1
 */
export function inThread(row, rows) {
	if (!row.threadKey) {
		return false
	}
	return rows.some(
		(other) => other !== row && other.threadKey === row.threadKey,
	)
}

/**
 * Run a call and answer { ok, data } or { ok: false, status }.
 *
 * @param {Promise<object>} call The axios call.
 * @return {Promise<object>} The answer.
 */
async function answer(call) {
	try {
		const { data } = await call
		return { ok: true, data }
	} catch (error) {
		return {
			ok: false,
			status: error?.response?.status ?? 0,
			error: error?.response?.data?.error ?? '',
		}
	}
}

/**
 * The ingested emails.
 *
 * @param {object} filters { status, dossier }; empty values are left out.
 * @return {Promise<object>} { ok, data: { results } }.
 * @spec openspec/changes/email-ingestion/tasks.md#3-1
 */
export function listEmails(filters = {}) {
	const params = {}
	for (const key of ['status', 'dossier']) {
		if (filters[key]) {
			params[key] = filters[key]
		}
	}
	return answer(axios.get(generateUrl(BASE), { params }))
}

/**
 * Scan the inboxes now.
 *
 * @return {Promise<object>} { ok, data: { processed, filed, failed, duplicates } }.
 * @spec openspec/changes/email-ingestion/tasks.md#3-1
 */
export function rescan() {
	return answer(axios.post(generateUrl(`${BASE}/scan`)))
}

/**
 * Convert a filed email again.
 *
 * @param {string} uuid The record.
 * @return {Promise<object>} { ok, data: record }.
 * @spec openspec/changes/email-ingestion/tasks.md#3-1
 */
export function retryConversion(uuid) {
	return answer(
		axios.post(
			generateUrl(`${BASE}/${encodeURIComponent(uuid)}/convert`),
		),
	)
}

/**
 * The inbox mapping.
 *
 * @return {Promise<object>} { ok, data: { inboxes, filesPerTick } }.
 * @spec openspec/changes/email-ingestion/tasks.md#2-5
 */
export function loadSettings() {
	return answer(axios.get(generateUrl(`${BASE}/settings`)))
}

/**
 * Store the inbox mapping.
 *
 * @param {Array<object>} inboxes Rows of { folderId, dossierRef }.
 * @param {number} filesPerTick The per-run budget.
 * @return {Promise<object>} { ok, data } or { ok: false, status: 400 }.
 * @spec openspec/changes/email-ingestion/tasks.md#2-5
 */
export function saveSettings(inboxes, filesPerTick) {
	return answer(
		axios.put(generateUrl(`${BASE}/settings`), {
			inboxes: inboxes.map((row) => ({
				folderId: Number(row.folderId),
				dossierRef: String(row.dossierRef || '').trim(),
			})),
			filesPerTick: Number(filesPerTick),
		}),
	)
}

/**
 * Inbound classification: the calls behind the pending list and the document
 * card, and the words for a suggestion.
 *
 * A suggestion changes nothing until a person confirms it; these helpers only
 * read suggestions and send that person's decision.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

const BASE = '/apps/filinq/api/classification'

/**
 * The document types a confirmation may set, in the order the picker shows them.
 *
 * @type {Array<string>}
 */
export const TYPES = [
	'brief',
	'besluit',
	'factuur',
	'rapport',
	'contract',
	'formulier',
	'overig',
]

/**
 * The label of a document type.
 *
 * @param {string} type One of TYPES.
 * @return {string} The label.
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
 */
export function typeLabel(type) {
	return (
		{
			brief: t('filinq', 'Letter'),
			besluit: t('filinq', 'Decision'),
			factuur: t('filinq', 'Invoice'),
			rapport: t('filinq', 'Report'),
			contract: t('filinq', 'Contract'),
			formulier: t('filinq', 'Form'),
			overig: t('filinq', 'Other'),
		}[type]
		|| type
		|| ''
	)
}

/**
 * The type options for a picker.
 *
 * @return {Array<object>} Rows of { id, label }.
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
 */
export function typeOptions() {
	return TYPES.map((id) => ({ id, label: typeLabel(id) }))
}

/**
 * The confidence as a whole percentage.
 *
 * @param {number} confidence Between 0 and 1.
 * @return {string} For example "82%".
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
 */
export function confidenceText(confidence) {
	const value = Number(confidence)
	if (!Number.isFinite(value)) {
		return ''
	}
	return `${Math.round(Math.min(Math.max(value, 0), 1) * 100)}%`
}

/**
 * The suggested sender as a person reads it.
 *
 * @param {object} record A classificationResult record.
 * @return {string} The name, or why there is none.
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
 */
export function correspondentText(record) {
	const name = record?.suggestedCorrespondent?.name
	if (name) {
		return name
	}
	if (record?.correspondentPending) {
		return t('filinq', 'Sender not known yet')
	}
	return t('filinq', 'No sender found')
}

/**
 * The decision a person took, as a status label.
 *
 * @param {string} status suggested, confirmed, rejected or superseded.
 * @return {string} The label.
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-2
 */
export function statusLabel(status) {
	return (
		{
			suggested: t('filinq', 'Waiting for you'),
			confirmed: t('filinq', 'Confirmed'),
			rejected: t('filinq', 'Rejected'),
			superseded: t('filinq', 'Replaced'),
		}[status]
		|| status
		|| ''
	)
}

/**
 * The body of a confirmation: only what the person changed.
 *
 * A missing key keeps the suggestion. An empty dossier means "file nowhere"
 * and is sent as null; an empty sender name clears the sender.
 *
 * @param {object} record The suggestion.
 * @param {object} choices { documentType, correspondentName, dossier }.
 * @return {object} The request body.
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
 */
export function confirmBody(record, choices = {}) {
	const body = {}
	if (
		choices.documentType
		&& choices.documentType !== record.suggestedDocumentType
	) {
		body.documentType = choices.documentType
	}
	if (choices.correspondentName !== undefined) {
		const name = String(choices.correspondentName || '').trim()
		if (name !== (record.suggestedCorrespondent?.name || '')) {
			body.correspondent = name
				? {
						name,
						entityType:
							record.suggestedCorrespondent?.entityType || 'PERSON',
					}
				: null
		}
	}
	if (choices.dossier !== undefined) {
		const dossier = choices.dossier || null
		if (dossier !== (record.suggestedDossier || null)) {
			body.dossier = dossier
		}
	}
	return body
}

/**
 * Run a call and answer { ok, data } or { ok: false, status, error }.
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
 * The suggestions waiting on files the caller can open.
 *
 * @return {Promise<object>} { ok, data: { results } }.
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
 */
export function listPending() {
	return answer(axios.get(generateUrl(`${BASE}/pending`)))
}

/**
 * One file's classification, for the document card.
 *
 * @param {number} fileId The file.
 * @return {Promise<object>} { ok, data: record } or { ok: false, status: 404 }.
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-2
 */
export function loadForFile(fileId) {
	return answer(axios.get(generateUrl(`${BASE}/${Number(fileId)}`)))
}

/**
 * Confirm a suggestion, with the person's corrections.
 *
 * @param {number} fileId The file.
 * @param {object} body From confirmBody().
 * @return {Promise<object>} { ok, data: record } or { ok: false, status }.
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
 */
export function confirm(fileId, body = {}) {
	return answer(axios.post(generateUrl(`${BASE}/${Number(fileId)}/confirm`), body))
}

/**
 * Reject a suggestion; the document stays as it is.
 *
 * @param {number} fileId The file.
 * @return {Promise<object>} { ok, data: record } or { ok: false, status }.
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
 */
export function reject(fileId) {
	return answer(axios.post(generateUrl(`${BASE}/${Number(fileId)}/reject`)))
}

/**
 * Confirm several suggestions as suggested, one after the other.
 *
 * @param {Array<object>} records The suggestions.
 * @return {Promise<object>} { confirmed, failed } counts.
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
 */
export async function confirmAll(records) {
	let confirmed = 0
	let failed = 0
	for (const record of records) {
		const result = await confirm(record.fileId)
		if (result.ok) {
			confirmed++
		} else {
			failed++
		}
	}
	return { confirmed, failed }
}

/**
 * The dossiers a document can be filed in.
 *
 * @return {Promise<Array<object>>} Rows of { id, label }; empty when they cannot be read.
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
 */
export async function dossierOptions() {
	const result = await answer(axios.get(generateUrl('/apps/filinq/api/dossiers')))
	if (!result.ok) {
		return []
	}
	return (result.data.results || []).map((row) => ({
		id: row.id,
		label: row.name || row.id,
	}))
}

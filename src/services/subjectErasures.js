/**
 * Subject erasure: the calls behind the erasure page, and the exclusions it builds.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

const base = () => generateUrl('/apps/filinq/api/subject-erasures')

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
					'The erasure step failed. Nothing further was changed. Try again later.',
				),
		}
	}
}

/**
 * Every request.
 *
 * @return {Promise<object>} The outcome, data.results the requests.
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */
export function listRequests() {
	return outcome(() => axios.get(base()))
}

/**
 * Place a request.
 *
 * @param {object} input subject, identifiers, ground, dueAt.
 * @return {Promise<object>} The outcome, data the request.
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */
export function createRequest(input) {
	return outcome(() => axios.post(base(), input))
}

/**
 * Build the preview.
 *
 * @param {string} uuid The request.
 * @return {Promise<object>} The outcome, data the preview.
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */
export function previewRequest(uuid) {
	return outcome(() => axios.post(`${base()}/${encodeURIComponent(uuid)}/preview`))
}

/**
 * Store the exclusions.
 *
 * @param {string} uuid The request.
 * @param {Array<{occurrence: string, reason: string}>} exclusions The exclusions.
 * @return {Promise<object>} The outcome, data the request.
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */
export function saveExclusions(uuid, exclusions) {
	return outcome(() =>
		axios.put(`${base()}/${encodeURIComponent(uuid)}/exclusions`, {
			exclusions,
		}),
	)
}

/**
 * Run or resume the erasure.
 *
 * @param {string} uuid The request.
 * @return {Promise<object>} The outcome, data {request, certificate}.
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */
export function runRequest(uuid) {
	return outcome(() => axios.post(`${base()}/${encodeURIComponent(uuid)}/run`))
}

/**
 * The certificate of a completed request.
 *
 * @param {string} uuid The request.
 * @return {Promise<object>} The outcome, data the certificate.
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */
export function fetchCertificate(uuid) {
	return outcome(() =>
		axios.get(`${base()}/${encodeURIComponent(uuid)}/certificate`),
	)
}

/**
 * Lines of a text area, trimmed, without empties.
 *
 * @param {string} text The text.
 * @return {string[]} The lines.
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */
export function parseLines(text) {
	return (text || '')
		.split('\n')
		.map((line) => line.trim())
		.filter((line) => line !== '')
}

/**
 * The exclusions the preview choices make. A document with every identifier
 * unticked is left whole ("<id>"); one with some unticked leaves those
 * ("<id>:<value>"). Each carries the row's reason.
 *
 * @param {Array<{document: string, values: string[]}>} documents The preview rows.
 * @param {Object<string, Object<string, boolean>>} kept document => value => false when left in place.
 * @param {Object<string, string>} reasons document => reason.
 * @return {Array<{occurrence: string, reason: string}>} The exclusions.
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.2
 */
export function buildExclusions(documents, kept, reasons) {
	const exclusions = []
	for (const row of documents) {
		const values = row.values || []
		const left = values.filter(
			(value) => kept?.[row.document]?.[value] === false,
		)
		if (left.length === 0) {
			continue
		}
		const reason = (reasons?.[row.document] || '').trim()
		if (left.length === values.length) {
			exclusions.push({ occurrence: String(row.document), reason })
			continue
		}
		for (const value of left) {
			exclusions.push({ occurrence: `${row.document}:${value}`, reason })
		}
	}
	return exclusions
}

/**
 * Whether every exclusion carries a reason.
 *
 * @param {Array<{reason: string}>} exclusions The exclusions.
 * @return {boolean}
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.2
 */
export function exclusionsComplete(exclusions) {
	return exclusions.every((exclusion) => exclusion.reason.trim() !== '')
}

/**
 * A request status in words.
 *
 * @param {string} status The status.
 * @return {string}
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */
export function statusLabel(status) {
	const labels = {
		received: t('filinq', 'Received, no preview yet'),
		previewed: t('filinq', 'Previewed'),
		running: t('filinq', 'Running'),
		partially_completed: t('filinq', 'Stopped part way, can be resumed'),
		completed: t('filinq', 'Completed'),
		refused: t('filinq', 'Refused'),
	}
	return labels[status] || status
}

/**
 * A document result in words.
 *
 * @param {string} outcomeName erased, refused, excluded or failed.
 * @return {string}
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */
export function outcomeLabel(outcomeName) {
	const labels = {
		erased: t('filinq', 'Erased'),
		refused: t('filinq', 'Refused'),
		excluded: t('filinq', 'Left in place'),
		failed: t('filinq', 'Not erased'),
	}
	return labels[outcomeName] || outcomeName
}

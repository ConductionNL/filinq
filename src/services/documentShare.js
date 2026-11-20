/**
 * Sharing documents by a link that runs out: the calls behind the Share by
 * link dialog and the Shared links page, and the labels they show.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The answers are the shapes `DocumentShareService` returns:
 * preflight `{maxDays, passwordRequired, defaultExpiresAt, labels, bundle}`,
 * create `{share, url, expiresAt, expiryShortened, manifest?}`, and the
 * revoked record for a take-back. The record never holds the link.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.1
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { answer } from './apiAnswer.js'

const sharesUrl = () => generateUrl('/apps/filinq/api/shares')

/**
 * The positive, distinct file ids of a selection, in the order chosen.
 *
 * @param {Array<number|string>} fileIds The selection.
 *
 * @return {number[]} The ids.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.1
 */
export function shareFileIds(fileIds) {
	const ids = []
	for (const raw of Array.isArray(fileIds) ? fileIds : []) {
		const id = Number(raw)
		if (Number.isInteger(id) && id > 0 && !ids.includes(id)) {
			ids.push(id)
		}
	}
	return ids
}

/**
 * Ask what a share would be: the link rules, the labels, and the bundle.
 *
 * @param {Array<number|string>} fileIds The chosen documents.
 *
 * @return {Promise<object>} `{ok, data}` or `{ok: false, status, error}`.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.1
 */
export function preflightShare(fileIds) {
	return answer(
		axios.post(`${sharesUrl()}/preflight`, { fileIds: shareFileIds(fileIds) }),
	)
}

/**
 * Make the link. One document is shared as itself, two or more as one archive.
 *
 * @param {object} input fileIds, name, expiresOn, password, note, labelConfirmed.
 *
 * @return {Promise<object>} `{ok, data}` or `{ok: false, status, error}`.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.1
 */
export function createShare(input) {
	const fileIds = shareFileIds(input.fileIds)
	const body = {
		fileIds,
		expiresOn: input.expiresOn || '',
		note: (input.note || '').trim(),
		labelConfirmed: input.labelConfirmed === true,
	}
	if (fileIds.length > 1) {
		body.name = (input.name || '').trim()
	}
	if (input.password) {
		body.password = input.password
	}
	return answer(axios.post(sharesUrl(), body))
}

/**
 * Take a link back.
 *
 * @param {string} uuid The share record.
 *
 * @return {Promise<object>} `{ok, data}` or `{ok: false, status, error}`.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.3
 */
export function revokeShare(uuid) {
	return answer(axios.delete(`${sharesUrl()}/${encodeURIComponent(uuid)}`))
}

/**
 * The list query: newest first, and only the user's own links unless the user is an admin.
 *
 * @param {object} user `{uid, isAdmin}`.
 *
 * @return {object} The query parameters.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.3
 */
function shareListParams(user) {
	const params = { _limit: 200, _order: { sharedAt: 'DESC' } }
	if (user?.isAdmin !== true) {
		params.sharedBy = user?.uid ?? ''
	}
	return params
}

/**
 * The share records the page lists: the user's own, or everyone's for an admin.
 *
 * @param {object} user `{uid, isAdmin}`.
 *
 * @return {Promise<object>} `{ok, data: {results}}` or `{ok: false, status, error}`.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.3
 */
export async function listShares(user) {
	const outcome = await answer(
		axios.get(generateUrl('/apps/openregister/api/objects/filinq/documentShare'), { params: shareListParams(user) }),
	)
	if (!outcome.ok) {
		return outcome
	}
	const results = Array.isArray(outcome.data?.results) ? outcome.data.results : []
	return { ok: true, data: { results: results.map(shareRow) } }
}

/**
 * One record as a row: its id, what left, and how it ended.
 *
 * @param {object} record The documentShare object.
 *
 * @return {object} The row.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.3
 */
export function shareRow(record) {
	const files = Array.isArray(record.files) ? record.files : []
	return {
		...record,
		id: record.uuid || record['@self']?.id || record.id || '',
		what: files
			.map((file) => (file.version ? `${file.name} (${file.version})` : file.name))
			.join(', '),
	}
}

/**
 * The label of a share's state.
 *
 * @param {object} row The share row.
 *
 * @return {string} "Active", "Expired", or "Taken back" with who.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.3
 */
export function shareStateLabel(row) {
	if (row.status === 'revoked') {
		if (row.revokedBy === 'removed in Files') {
			return t('filinq', 'Removed in Files')
		}
		return t('filinq', 'Taken back by {name}', { name: row.revokedBy || '' })
	}
	if (row.status === 'expired') {
		return t('filinq', 'Expired')
	}
	return t('filinq', 'Active')
}

/**
 * Whether a share can still be taken back.
 *
 * @param {object} row The share row.
 *
 * @return {boolean} True while it is active.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.3
 */
export function canTakeBack(row) {
	return row.status === 'active'
}

/**
 * The confidentiality labels of the chosen documents, each once.
 *
 * @param {?object} preflight The preflight answer.
 *
 * @return {string[]} The labels.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.2
 */
export function shareLabels(preflight) {
	const labels = []
	for (const entry of Array.isArray(preflight?.labels) ? preflight.labels : []) {
		if (entry.label && !labels.includes(entry.label)) {
			labels.push(entry.label)
		}
	}
	return labels
}

/**
 * The rules the share button waits for, in order; each reads the dialog state.
 *
 * @type {Array<function(object): boolean>}
 */
const SHARE_RULES = [
	(state) => Boolean(state.preflight) && Boolean(state.expiresOn),
	(state) => state.preflight.passwordRequired !== true || Boolean(state.password),
	(state) => state.fileCount <= 1 || Boolean((state.name || '').trim()),
	(state) => shareLabels(state.preflight).length === 0 || state.labelConfirmed === true,
]

/**
 * Whether the share button may be pressed.
 *
 * @param {object} state preflight, expiresOn, password, name, labelConfirmed, fileCount.
 *
 * @return {boolean} True when every rule the dialog knows of is met.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.2
 */
export function canShare(state) {
	return SHARE_RULES.every((rule) => rule(state))
}

/**
 * The latest expiry Nextcloud allows, as YYYY-MM-DD, or '' when there is no maximum.
 *
 * @param {?number} maxDays Nextcloud's enforced maximum in days.
 * @param {Date} today Today.
 *
 * @return {string} The date.
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.1
 */
export function latestExpiry(maxDays, today = new Date()) {
	if (!maxDays || maxDays <= 0) {
		return ''
	}
	const latest = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate() + maxDays))
	return latest.toISOString().slice(0, 10)
}

/**
 * A size in megabytes with one decimal.
 *
 * @param {number} bytes The size.
 *
 * @return {string} For example "1.5".
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.1
 */
export function shareMegabytes(bytes) {
	return (Number(bytes || 0) / 1048576).toFixed(1)
}

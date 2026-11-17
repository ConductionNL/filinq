/**
 * The audit trail of one document: the call behind its activity section and
 * the label of each recorded action.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The trail is read from OpenRegister's audit trail of the document's record
 * (`GET api/documents/{fileId}/audit`); filinq keeps no second store.
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.3
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * The prefix filinq writes its own document actions under.
 *
 * @type {string}
 */
export const DOCUMENT_ACTION_PREFIX = 'filinq.document.'

/**
 * Read the trail of a document, oldest first.
 *
 * A document the caller may not read answers 404 on the server; that and any
 * other failure come back as `ok: false` so the section can say so instead of
 * showing an empty trail.
 *
 * @param {number|string} fileId The Nextcloud file id of the document.
 * @return {Promise<{ok: boolean, entries: Array<object>, status?: number}>} The outcome.
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.3
 */
export async function fetchDocumentTrail(fileId) {
	const id = Number(fileId)
	if (!Number.isInteger(id) || id <= 0) {
		return { ok: true, entries: [] }
	}

	try {
		const { data } = await axios.get(
			generateUrl('/apps/filinq/api/documents/{fileId}/audit', { fileId: id }),
		)
		const entries = Array.isArray(data?.results) ? data.results : []
		return { ok: true, entries }
	} catch (error) {
		return { ok: false, entries: [], status: error.response?.status || 0 }
	}
}

/**
 * The label of one recorded action.
 *
 * Filinq's own actions carry the `filinq.document.` prefix; the record's own
 * OpenRegister entries (`create`, `update`, `delete`) are the registration and
 * changes of the document. An unknown action is shown as recorded.
 *
 * @param {string} action The action as stored in the trail.
 * @return {string} The label to show.
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.3
 */
export function actionLabel(action) {
	const value = String(action ?? '')
	const name = value.startsWith(DOCUMENT_ACTION_PREFIX)
		? value.slice(DOCUMENT_ACTION_PREFIX.length)
		: value

	switch (name) {
	case 'create':
		return t('filinq', 'Registered')
	case 'update':
	case 'edited':
		return t('filinq', 'Changed')
	case 'opened':
		return t('filinq', 'Opened')
	case 'downloaded':
		return t('filinq', 'Downloaded')
	case 'versioned':
		return t('filinq', 'New version')
	case 'shared':
		return t('filinq', 'Shared')
	case 'anonymised':
		return t('filinq', 'Anonymised')
	case 'signed':
		return t('filinq', 'Signed')
	case 'delete':
	case 'deleted':
		return t('filinq', 'Deleted')
	case 'copied_to':
		return t('filinq', 'Copied to another record')
	case 'copied_from':
		return t('filinq', 'Copied from another record')
	default:
		return value
	}
}

/**
 * Who did it: the display name when the trail has one, else the user id.
 *
 * @param {object} entry One trail entry.
 * @return {string} The actor to show.
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.3
 */
export function actorLabel(entry) {
	const name = String(entry?.userName ?? '').trim()
	if (name !== '') {
		return name
	}
	const user = String(entry?.user ?? '').trim()
	return user !== '' ? user : t('filinq', 'System')
}

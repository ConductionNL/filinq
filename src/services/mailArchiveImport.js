/**
 * Mail archive import: the calls behind the import page, and the words and
 * numbers it shows for an import.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { answer } from './apiAnswer.js'

const BASE = '/apps/filinq/api/mail-archive-imports'

/**
 * The statuses an import can be in, in lifecycle order.
 *
 * @type {Array<string>}
 */
export const STATUSES = [
	'received',
	'mapping',
	'importing',
	'done',
	'failed',
	'cancelled',
]

/**
 * The label of an import's status.
 *
 * @param {string} status The status.
 * @return {string} The label.
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */
export function statusLabel(status) {
	return (
		{
			received: t('filinq', 'Received'),
			mapping: t('filinq', 'Waiting for the folder mapping'),
			importing: t('filinq', 'Importing'),
			done: t('filinq', 'Done'),
			failed: t('filinq', 'Failed'),
			cancelled: t('filinq', 'Cancelled'),
		}[status] || status
	)
}

/**
 * The badge colour of each status label.
 *
 * @return {object} Label to colour.
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */
export function statusColorMap() {
	return {
		[statusLabel('received')]: 'default',
		[statusLabel('mapping')]: 'warning',
		[statusLabel('importing')]: 'primary',
		[statusLabel('done')]: 'success',
		[statusLabel('failed')]: 'error',
		[statusLabel('cancelled')]: 'default',
	}
}

/**
 * The label of a detected format.
 *
 * @param {string} format mbox, zip or pst.
 * @return {string} The label.
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */
export function formatLabel(format) {
	return (
		{
			mbox: t('filinq', 'MBOX'),
			zip: t('filinq', 'ZIP of .eml files'),
			pst: t('filinq', 'Outlook PST'),
		}[format] || ''
	)
}

/**
 * How many of the messages found have been handled, filed, sent to the
 * inbox, skipped or failed, as a whole percentage.
 *
 * @param {object} counts { found, filed, toInbox, skippedDuplicate, failed }.
 * @return {number} 0 to 100.
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */
export function progressPercent(counts = {}) {
	const found = Number(counts.found) || 0
	if (found <= 0) {
		return 0
	}
	const handled
		= (Number(counts.filed) || 0)
		+ (Number(counts.toInbox) || 0)
		+ (Number(counts.skippedDuplicate) || 0)
		+ (Number(counts.failed) || 0)
	return Math.min(100, Math.floor((handled / found) * 100))
}

/**
 * The counts as one line a person reads.
 *
 * @param {object} counts { found, filed, toInbox, skippedDuplicate, failed }.
 * @return {string} The line.
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */
export function countsText(counts = {}) {
	return t(
		'filinq',
		'{found} found: {filed} filed, {toInbox} to the intake inbox, {skipped} already there, {failed} failed',
		{
			found: Number(counts.found) || 0,
			filed: Number(counts.filed) || 0,
			toInbox: Number(counts.toInbox) || 0,
			skipped: Number(counts.skippedDuplicate) || 0,
			failed: Number(counts.failed) || 0,
		},
	)
}

/**
 * Why one message failed, as a person reads it.
 *
 * @param {string} reason The reason code.
 * @return {string} The sentence.
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */
export function failureText(reason) {
	switch (reason) {
		case 'unsupported-format':
			return t(
				'filinq',
				'Not an .eml message (an Outlook .msg cannot be read). Export it again as .eml.',
			)
		case 'unparseable':
			return t('filinq', 'This message could not be read as an email.')
		case 'dossier-folder-unavailable':
			return t(
				'filinq',
				'The dossier this folder is mapped to has no folder that can be reached.',
			)
		case 'filing-failed':
			return t('filinq', 'The message could not be stored.')
		default:
			return reason || t('filinq', 'Unknown reason')
	}
}

/**
 * Whether an import can still be cancelled.
 *
 * @param {object} row The import.
 * @return {boolean} True before it has ended.
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */
export function canCancel(row) {
	return ['received', 'mapping', 'importing'].includes(row?.status)
}

/**
 * The mapping to send: every folder with the dossier picked for it, '' for
 * the intake inbox.
 *
 * @param {Array<object>} folderMap The import's folders, { archiveFolder }.
 * @param {object} picked Folder name to dossier id, or to a { id } option.
 * @return {Array<object>} Rows of { archiveFolder, dossier }.
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */
export function mappingPayload(folderMap = [], picked = {}) {
	return folderMap.map((row) => {
		const choice = picked[row.archiveFolder]
		const dossier
			= choice && typeof choice === 'object' ? choice.id : choice
		return {
			archiveFolder: row.archiveFolder,
			dossier: String(dossier || '').trim(),
		}
	})
}

/**
 * The user's imports.
 *
 * @return {Promise<object>} { ok, data: { results } }.
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */
export function listImports() {
	return answer(axios.get(generateUrl(BASE)))
}

/**
 * Start an import of a file.
 *
 * @param {number} fileId The archive's file id.
 * @return {Promise<object>} { ok, data: import }.
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */
export function startImport(fileId) {
	return answer(axios.post(generateUrl(BASE), { fileId: Number(fileId) }))
}

/**
 * Map the folders and start importing.
 *
 * @param {string} uuid The import.
 * @param {Array<object>} folderMap Rows of { archiveFolder, dossier }.
 * @return {Promise<object>} { ok, data: import } or a 403 for a refused dossier.
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */
export function mapFolders(uuid, folderMap) {
	return answer(
		axios.post(generateUrl(`${BASE}/${encodeURIComponent(uuid)}/start`), {
			folderMap,
		}),
	)
}

/**
 * Cancel an import.
 *
 * @param {string} uuid The import.
 * @return {Promise<object>} { ok, data: import }.
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */
export function cancelImport(uuid) {
	return answer(
		axios.post(generateUrl(`${BASE}/${encodeURIComponent(uuid)}/cancel`)),
	)
}

/**
 * The dossiers that can be picked for a folder.
 *
 * @return {Promise<Array<object>>} Options of { id, label }; none when they cannot be read.
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */
export async function loadDossierOptions() {
	const result = await answer(
		axios.get(generateUrl('/apps/openregister/api/objects/filinq/dossier'), {
			params: { _limit: 200 },
		}),
	)
	if (!result.ok || !Array.isArray(result.data?.results)) {
		return []
	}
	return result.data.results.map((record) => ({
		id: record['@self']?.id || record.id || record.uuid || '',
		label:
			record.name
			|| record['@self']?.name
			|| record['@self']?.id
			|| t('filinq', 'Unnamed dossier'),
	}))
}

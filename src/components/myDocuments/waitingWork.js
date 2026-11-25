/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The two lists on My documents beside the files: the document records the
 * person created (`GET api/case-documents/mine`) and what waits for them
 * (`GET api/documents/waiting`), each item saying why it waits
 * (specify-capability-rows REQ-SCR-006, task 2.2).
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * Why an item waits, in the reader's language. The server sends the English
 * reason; each one is written out here so the catalogue carries it.
 *
 * @param {string} reason The reason the server gave.
 * @return {string}
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#2-documents
 */
export function reasonLabel(reason) {
	const labels = {
		'Waiting for your signature': t('filinq', 'Waiting for your signature'),
		'Waiting for your acceptance': t('filinq', 'Waiting for your acceptance'),
		'Routed to your group, waiting for acceptance': t(
			'filinq',
			'Routed to your group, waiting for acceptance',
		),
		'Due for review': t('filinq', 'Due for review'),
	}
	return labels[reason] ?? reason ?? ''
}

/**
 * The rows of the Waiting for me list.
 *
 * @param {Array<object>} items The `results` of `GET api/documents/waiting`.
 * @return {Array<{key: string, kind: string, name: string, reason: string, since: string}>}
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#2-documents
 */
export function waitingRows(items) {
	if (!Array.isArray(items)) {
		return []
	}
	return items.map((item, index) => ({
		key: `${item.kind ?? 'item'}-${item.id || index}`,
		kind: item.kind ?? '',
		name: item.name || item.id || t('filinq', 'Untitled'),
		reason: reasonLabel(item.reason),
		since: item.since ? new Date(item.since).toLocaleDateString() : '',
	}))
}

/**
 * The rows of the My records list: the document records the person created.
 *
 * @param {Array<object>} records The `results` of `GET api/case-documents/mine`.
 * @return {Array<{key: string, name: string, status: string, linkedTo: number}>}
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#2-documents
 */
export function recordRows(records) {
	if (!Array.isArray(records)) {
		return []
	}
	return records.map((record, index) => {
		const id = record.uuid || record.id || record['@self']?.id || String(index)
		return {
			key: id,
			name: record.documentName || id,
			status: record.status || '',
			linkedTo: Array.isArray(record.domains) ? record.domains.length : 0,
		}
	})
}

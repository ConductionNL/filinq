/**
 * Format matrix: which output formats the server can make now.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The choices a form offers come from `GET api/documents/formats`, not from
 * a list in the bundle, so a server without LibreOffice shows DOCX and ODT
 * disabled with the reason instead of failing after the clerk submits.
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-4.1
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * The reason the server gives when LibreOffice is missing, in English.
 */
const LIBREOFFICE_UNAVAILABLE = 'LibreOffice is not available on this server'

/**
 * A readable name for a format.
 *
 * @param {string} format The format key.
 * @return {string}
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-4.1
 */
export function formatLabel(format) {
	switch (format) {
		case 'pdf':
			return t('filinq', 'PDF')
		case 'docx':
			return t('filinq', 'DOCX (editable)')
		case 'odf':
			return t('filinq', 'ODT (editable)')
		case 'html':
			return t('filinq', 'HTML')
		case 'email':
			return t('filinq', 'Email body')
		default:
			return format
	}
}

/**
 * The server's reason, translated when it is one we know.
 *
 * @param {string|undefined} reason The reason from the matrix.
 * @return {string}
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-4.1
 */
export function reasonText(reason) {
	if (reason === LIBREOFFICE_UNAVAILABLE) {
		return t('filinq', 'LibreOffice is not available on this server')
	}
	return reason || ''
}

/**
 * The options a form shows, in the server's order.
 *
 * @param {Object<string, {available: boolean, reason?: string}>} matrix The matrix.
 * @return {Array<{value: string, label: string, disabled: boolean, reason: string}>}
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-4.1
 */
export function formatOptions(matrix) {
	return Object.entries(matrix || {}).map(([value, entry]) => ({
		value,
		label: formatLabel(value),
		disabled: entry.available !== true,
		reason: entry.available === true ? '' : reasonText(entry.reason),
	}))
}

/**
 * The format to keep selected: the current one while it can be made, else
 * the first that can.
 *
 * @param {string} current The selected format.
 * @param {Array<{value: string, disabled: boolean}>} options The options.
 * @return {string}
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-4.1
 */
export function usableFormat(current, options) {
	const selected = options.find((option) => option.value === current)
	if (selected && !selected.disabled) {
		return current
	}
	const first = options.find((option) => !option.disabled)
	return first ? first.value : current
}

/**
 * Fetch the matrix for a flow.
 *
 * @param {'documents'|'correspondence'} flow The flow.
 * @return {Promise<Object<string, {available: boolean, reason?: string}>>}
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-4.1
 */
export async function fetchFormatMatrix(flow) {
	const { data } = await axios.get(
		generateUrl('/apps/filinq/api/documents/formats'),
		{ params: { flow } },
	)
	return data.formats || {}
}

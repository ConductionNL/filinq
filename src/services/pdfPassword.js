/**
 * Remove the password of a PDF into an unlocked copy.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#2-screens
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * Ask the server to unlock a PDF with its password.
 *
 * The password goes in the request body only; it is not kept here.
 *
 * @param {number} fileId The Nextcloud file id.
 * @param {string} password The PDF's password.
 * @return {Promise<object>} The copy: `fileId`, `name`, `validationStatus`, `validationFindings`.
 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#2-screens
 */
export async function removePassword(fileId, password) {
	const response = await axios.post(
		generateUrl(`/apps/filinq/api/documents/${fileId}/remove-password`),
		{ password },
	)
	return response.data || {}
}

/**
 * Ask the server to unlock the file of a waiting intake document. The
 * document then names the copy as its file and keeps the file that arrived
 * as `lockedOriginal` (REQ-PPR-004).
 *
 * @param {string} uuid The intake document.
 * @param {string} password The PDF's password.
 * @return {Promise<object>} The copy (`fileId`, `name`, findings) and the `intakeDocument`.
 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#2-screens
 */
export async function removeIntakePassword(uuid, password) {
	const response = await axios.post(
		generateUrl('/apps/filinq/api/intake/documents/{uuid}/remove-password', { uuid }),
		{ password },
	)
	return response.data || {}
}

/**
 * Whether an intake row's file may still be locked: a PDF whose password
 * was not removed yet. The server answers for the file itself.
 *
 * @param {object} row The intake document (`fileName`, `lockedOriginal`).
 * @return {boolean}
 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#2-screens
 */
export function mayRemovePassword(row) {
	return /\.pdf$/i.test(String(row?.fileName || '')) && !(Number(row?.lockedOriginal) > 0)
}

/**
 * The sentence for a refused unlock: the server's own message, else one per status.
 *
 * @param {object} error The axios error.
 * @return {string}
 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#2-screens
 */
export function removePasswordError(error) {
	const reason = error?.response?.data?.error
	if (reason) {
		return reason
	}
	const byStatus = {
		409: t('filinq', 'This document is final and cannot be changed'),
		422: t('filinq', 'The password is not correct'),
		503: t('filinq', 'qpdf is not installed on this server'),
	}
	return byStatus[error?.response?.status] || t('filinq', 'The password could not be removed from this PDF.')
}

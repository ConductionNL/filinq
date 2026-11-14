/**
 * Bulk send: the calls behind the bulk send dialog, and how it shows a batch.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * A list is uploaded with the document settings and comes back as a report
 * (status `ready`): nothing is sent until the initiator confirms. After the
 * confirm the server creates the requests in the background; the dialog reads
 * the batch again until it is finished.
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

const BASE = '/apps/filinq/api/signing/batches'

/**
 * Upload a recipient list with the document settings.
 *
 * @param {File} file The .csv or .xlsx list
 * @param {object} settings documentFileId, documentName, signatureLevel, signingMode, title
 * @return {Promise<object>} The batch with its report.
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */
export async function uploadList(file, settings) {
	const body = new FormData()
	body.append('file', file)
	for (const [key, value] of Object.entries(settings)) {
		if (value !== undefined && value !== null && value !== '') {
			body.append(key, value)
		}
	}
	const response = await axios.post(generateUrl(BASE), body)
	return response.data
}

/**
 * Read one batch.
 *
 * @param {string} id The batch uuid
 * @return {Promise<object>} The batch.
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */
export async function fetchBatch(id) {
	const response = await axios.get(generateUrl(`${BASE}/${id}`))
	return response.data
}

/**
 * Confirm a ready batch.
 *
 * @param {string} id The batch uuid
 * @return {Promise<object>} The batch, now sending.
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */
export async function confirmBatch(id) {
	const response = await axios.post(generateUrl(`${BASE}/${id}/confirm`))
	return response.data
}

/**
 * Cancel a batch and the requests that can still be cancelled.
 *
 * @param {string} id The batch uuid
 * @return {Promise<object>} The cancelled batch.
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */
export async function cancelBatch(id) {
	const response = await axios.post(generateUrl(`${BASE}/${id}/cancel`))
	return response.data
}

/**
 * Whether a batch is done moving.
 *
 * @param {object} batch The batch
 * @return {boolean}
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */
export function isFinished(batch) {
	return ['completed', 'completed_with_errors', 'cancelled'].includes(
		batch?.status,
	)
}

/**
 * The sentence a rejected row shows.
 *
 * @param {{row: number, reason: string, detail?: string}} rejected The rejected row
 * @return {string}
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */
export function reasonLabel(rejected) {
	const detail = rejected.detail || ''
	switch (rejected.reason) {
		case 'no-recipient':
			return t('filinq', 'No e-mail address or user')
		case 'invalid-email':
			return t('filinq', 'Not a valid e-mail address')
		case 'unknown-user':
			return t('filinq', 'No user {user} on this Nextcloud', { user: detail })
		case 'duplicate':
			return t('filinq', 'Same recipient as {row}', { row: detail })
		case 'creation-failed':
			return t('filinq', 'The request could not be made: {reason}', {
				reason: detail,
			})
		default:
			return rejected.reason
	}
}

/**
 * The label a batch status shows.
 *
 * @param {string} status The stored status
 * @return {string}
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */
export function statusLabel(status) {
	const labels = {
		validating: t('filinq', 'Checking the list'),
		ready: t('filinq', 'Ready to send'),
		creating: t('filinq', 'Sending'),
		completed: t('filinq', 'Sent'),
		completed_with_errors: t('filinq', 'Sent, some rows failed'),
		cancelled: t('filinq', 'Cancelled'),
	}
	return labels[status] || status
}

/**
 * The message an error answer carries, or a fallback.
 *
 * @param {Error} error The axios error
 * @return {string}
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */
export function errorMessage(error) {
	return (
		error?.response?.data?.error
		|| t('filinq', 'The bulk send could not be reached. Try again.')
	)
}

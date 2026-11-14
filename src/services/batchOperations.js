/**
 * Background anonymisation batches: status, cancel and resume.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-per-batch-progress-reporting-req-ddras-003
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * The URL of one batch endpoint.
 *
 * @param {string} batchId The batch.
 * @param {string} action  status, cancel, resume or report.
 * @return {string}
 */
export function batchUrl(batchId, action) {
	return generateUrl(`/apps/filinq/api/anonymization/batch/${encodeURIComponent(batchId)}/${action}`)
}

/**
 * The batch statuses a worker is busy in, or that can still start.
 */
export const CANCELLABLE = ['uploading', 'queued', 'extracting', 'review', 'anonymizing']

/**
 * The batch statuses a resume picks up again.
 */
export const RESUMABLE = ['error', 'cancelled']

/**
 * The statuses in which the batch still changes by itself, so the view keeps polling.
 */
export const RUNNING = ['extracting', 'anonymizing', 'cancelling']

/**
 * Whether a batch in this status can be cancelled.
 *
 * @param {string} status The batch status.
 * @return {boolean}
 */
export function canCancel(status) {
	return CANCELLABLE.includes(status)
}

/**
 * Whether a batch in this status can be resumed.
 *
 * @param {string} status The batch status.
 * @return {boolean}
 */
export function canResume(status) {
	return RESUMABLE.includes(status)
}

/**
 * The files of a batch picked for the QA sample, with their outcome.
 *
 * @param {Array<object>} files The batch's files.
 * @return {Array<{fileId: number, fileName: string, outcome: string}>}
 */
export function qaSamples(files) {
	return (files || [])
		.filter((file) => file.qaSampled === true)
		.map((file) => ({
			fileId: file.fileId,
			fileName: file.relativePath || file.fileName || String(file.fileId),
			outcome: file.qaOutcome || 'pending',
		}))
}

/**
 * Read a batch's status and progress.
 *
 * @param {string} batchId The batch.
 * @return {Promise<object>}
 */
export async function getBatchStatus(batchId) {
	const { data } = await axios.get(batchUrl(batchId, 'status'))
	return data
}

/**
 * Ask a batch to stop between files.
 *
 * @param {string} batchId The batch.
 * @return {Promise<object>} The new batch status.
 */
export async function cancelBatch(batchId) {
	const { data } = await axios.post(batchUrl(batchId, 'cancel'))
	return data
}

/**
 * Put an errored or cancelled batch's unfinished files back in the queue.
 *
 * @param {string} batchId The batch.
 * @return {Promise<object>} The new batch status and how many files were requeued.
 */
export async function resumeBatch(batchId) {
	const { data } = await axios.post(batchUrl(batchId, 'resume'))
	return data
}

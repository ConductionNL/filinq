/**
 * Print jobs: the request Correspondence sends, and how the page shows a job.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The request is the body `PrintJobController::batch()` reads: a template,
 * one item per letter (each with its `dataRefs`, resolved on the server like
 * a generation) and the download name. One request is one job, however many
 * letters it holds.
 *
 * @spec openspec/changes/print-jobs-in-the-app/specs/print-preview/spec.md
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * Build the print request for the Correspondence form.
 *
 * @param {object} form The form state
 * @param {string} form.templateId The template
 * @param {boolean} form.batchMode True for one letter per recipient
 * @param {Array<{register: string, schema: string, id: string}>} form.dataRefs The references of a single letter
 * @param {string} form.register The register of the recipients in batch mode
 * @param {string} form.schema The schema of the recipients in batch mode
 * @param {string[]} form.recipientIds One object id per recipient in batch mode
 * @param {string} form.caseReference The case reference, used in the file names
 * @return {{templateId: string, items: Array<object>, filename: string}} The request body.
 *
 * @spec openspec/changes/print-jobs-in-the-app/tasks.md#task-1.4
 */
export function buildPrintRequest(form) {
	const base = 'brief-' + (form.caseReference || 'correspondentie')
	let items
	if (form.batchMode) {
		items = form.recipientIds.map((id, index) => ({
			dataRefs: [{ register: form.register, schema: form.schema, id }],
			filename: base + '-' + (index + 1) + '.pdf',
		}))
	} else {
		items = [
			{
				dataRefs: form.dataRefs.map((ref) => ({
					register: ref.register,
					schema: ref.schema,
					id: ref.id,
				})),
				filename: base + '.pdf',
			},
		]
	}

	return { templateId: form.templateId, items, filename: base + '.pdf' }
}

/**
 * The label a job's status shows.
 *
 * @param {string} status The stored status
 * @return {string} The label.
 *
 * @spec openspec/changes/print-jobs-in-the-app/tasks.md#task-1.4
 */
export function printJobStatusLabel(status) {
	const labels = {
		rendering: t('filinq', 'Making the PDFs'),
		queued: t('filinq', 'Waiting for the printer'),
		sent: t('filinq', 'At the printer'),
		printed: t('filinq', 'Printed'),
		failed: t('filinq', 'Failed'),
	}
	return labels[status] || status
}

/**
 * Whether a job has something to download.
 *
 * @param {object} job The job
 * @return {boolean} True when at least one PDF is ready.
 *
 * @spec openspec/changes/print-jobs-in-the-app/tasks.md#task-1.4
 */
export function canDownload(job) {
	return job.status !== 'rendering' && (job.rendered || 0) > 0
}

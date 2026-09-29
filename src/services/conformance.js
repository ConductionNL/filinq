/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The PDF/A conformance report of a document (veraPDF), and the advice
 * texts shared by the report and the validation findings.
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * The stored reports of a file and whether a new check can run.
 *
 * @param {number} fileId The file id.
 * @return {Promise<{available: boolean, reports: object}>} The reports, keyed by subject.
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
 */
export async function fetchConformance(fileId) {
	const { data } = await axios.get(
		generateUrl(`/apps/filinq/api/validation/conformance/${Number(fileId)}`),
	)
	return { available: Boolean(data.available), reports: data.reports || {} }
}

/**
 * Run veraPDF on a file now; the server stores the report.
 *
 * @param {number} fileId The file id.
 * @return {Promise<object>} The stored report.
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
 */
export async function checkConformance(fileId) {
	const { data } = await axios.post(
		generateUrl(`/apps/filinq/api/validation/conformance/${Number(fileId)}`),
	)
	return data.report
}

/**
 * The advice for a guidance key the server chose from the failure.
 *
 * @param {string} key none | regenerate | reconvertFromSource | ruleReferences.
 * @return {string} The advice, '' for none.
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
 */
export function guidanceText(key) {
	switch (key) {
		case 'regenerate':
			return t(
				'filinq',
				'Filinq made this document before its fonts were embedded. Generate it again in Filinq to embed them.',
			)
		case 'reconvertFromSource':
			return t(
				'filinq',
				'The fonts sit in pages imported whole, and Filinq cannot embed fonts in those afterwards. Convert again from the original file (for example the Word document), or expect an e-depot to reject this PDF.',
			)
		case 'ruleReferences':
			return t(
				'filinq',
				'Look up the rules listed in the PDF/A standard, fix the source and create the PDF again.',
			)
		default:
			return ''
	}
}

/**
 * A short verdict line for a report.
 *
 * @param {object} report The report.
 * @return {string} The verdict.
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
 */
export function verdictText(report) {
	if (!report) {
		return t('filinq', 'Not checked yet')
	}
	if (report.compliant) {
		return t('filinq', 'Meets PDF/A-{flavour}', { flavour: report.flavour })
	}
	return t('filinq', 'Does not meet PDF/A-{flavour}. Rules failed: {count}', {
		flavour: report.flavour,
		count: report.failedRuleCount,
	})
}

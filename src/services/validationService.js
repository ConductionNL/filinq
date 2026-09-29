/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * Run on-demand validation for a file, returning the verdict + findings.
 * Nothing is persisted by this call.
 *
 * @param {number} fileId       The Nextcloud file id.
 * @param {string} [documentType] Optional document-type hint.
 * @return {Promise<{validationStatus: string, validationFindings: Array}>} The verdict.
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */
export async function validateFile(fileId, documentType) {
	const url = generateUrl('/apps/filinq/api/validation/validate')
	const body = { fileId }
	if (documentType) {
		body.documentType = documentType
	}
	const { data } = await axios.post(url, body)
	return data
}

/**
 * Map a validation status to an NL Design / NC status-badge colour token.
 *
 * @param {string} status The verdict (passed|warnings|failed|'').
 * @return {string} The colour token.
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */
export function verdictColor(status) {
	switch (status) {
		case 'passed':
			return 'success'
		case 'warnings':
			return 'warning'
		case 'failed':
			return 'error'
		default:
			return 'info'
	}
}

/**
 * Findings grouped by category: document checks first, then accessibility,
 * then the archival (PDF/A) checks veraPDF answers. A finding without a category is a
 * document check.
 *
 * @param {Array<object>} findings The findings.
 * @return {Array<{category: string, title: string, findings: Array<object>}>} The groups.
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
 */
export function groupFindings(findings) {
	const titles = {
		document: t('filinq', 'Document checks'),
		accessibility: t('filinq', 'Accessibility checks'),
		archival: t('filinq', 'Archival checks (PDF/A)'),
	}
	const order = ['document', 'accessibility', 'archival']
	const byCategory = {}
	for (const finding of findings) {
		const category = finding.category || 'document'
		byCategory[category] = byCategory[category] || []
		byCategory[category].push(finding)
	}
	return Object.keys(byCategory)
		.sort((a, b) => order.indexOf(a) - order.indexOf(b))
		.map((category) => ({
			category,
			title: titles[category] || category,
			findings: byCategory[category],
		}))
}

/**
 * Whether a document is ready to hand to publication as far as
 * accessibility goes: the open accessibility findings of a fresh validation.
 * A warning, not a gate: only a finding an admin set to blocking stops the
 * hand-off.
 *
 * @param {number} fileId The file id.
 * @return {Promise<{checked: boolean, findings: Array<object>, blocking: boolean}>} The signal.
 * @spec openspec/changes/pdfua-accessible-output/tasks.md#task-3.2
 */
export async function publicationReadiness(fileId) {
	try {
		const result = await validateFile(fileId)
		const findings = (result.validationFindings || []).filter(
			(finding) => finding.category === 'accessibility',
		)
		return {
			checked: true,
			findings,
			blocking: findings.some((finding) => finding.severity === 'blocking'),
		}
	} catch {
		return { checked: false, findings: [], blocking: false }
	}
}

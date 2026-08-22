/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */

import axios from '@nextcloud/axios'
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

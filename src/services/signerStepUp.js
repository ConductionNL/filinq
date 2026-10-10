/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The step-up flow of the signer identity rails, as plain functions the
 * sign dialog and the request page call (signer-identity-rails
 * REQ-DDSIR-003). A signing act refused for weak identity evidence answers
 * 403 with a `stepUp` hint; the signer starts the configured provider, is
 * sent to the broker, comes back on the request page and signs again.
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * The step-up hint of a refused signing act, from an axios error or a
 * signing-folder result.
 *
 * @param {object|null} source An axios error, or a folder result.
 * @return {object|null} The hint (`requiredAssurance`, `reason`, `provider`), or null.
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
export function stepUpHint(source) {
	const hint = source?.response?.data?.stepUp ?? source?.stepUp ?? null
	if (!hint || hint.required !== true) {
		return null
	}
	return hint
}

/**
 * Start a step-up for one signer on one request.
 *
 * @param {string} requestId The signing request.
 * @param {string} signerId The signer record.
 * @return {Promise<object>} The challenge: `type` (`redirect` or `none`), `url`, `requiredAssurance`.
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
export async function startStepUp(requestId, signerId) {
	const { data } = await axios.post(
		generateUrl(
			`/apps/filinq/api/signing/requests/${encodeURIComponent(requestId)}/identity`,
		),
		{ signerId },
	)
	return data
}

/**
 * What the broker callback left in the query of the page it returned to.
 *
 * @param {object} query The route query.
 * @return {object} `{ status: 'done'|'failed'|null, signerId }`.
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
export function stepUpReturn(query) {
	const status = ['done', 'failed'].includes(query?.stepUp) ? query.stepUp : null
	return {
		status,
		signerId: typeof query?.signerId === 'string' ? query.signerId : '',
	}
}

const LEVELS = ['low', 'substantial', 'high']
const FLOORS = { SES: 'low', AdES: 'substantial', QES: 'high' }

/**
 * The assurance floor of a signature level (REQ-DDSIR-002).
 *
 * @param {string} signatureLevel `SES`, `AdES` or `QES`.
 * @return {string} The floor; `low` for anything else.
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
export function assuranceFloor(signatureLevel) {
	return FLOORS[signatureLevel] ?? 'low'
}

/**
 * The assurance levels a request at a signature level may ask for.
 *
 * @param {string} signatureLevel `SES`, `AdES` or `QES`.
 * @return {Array<string>} The floor and everything above it.
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
export function assuranceLevelsFrom(signatureLevel) {
	return LEVELS.slice(LEVELS.indexOf(assuranceFloor(signatureLevel)))
}

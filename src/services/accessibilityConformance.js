/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The PDF/UA-1 (Matterhorn) accessibility report of a document (veraPDF
 * ua1), the state the document shows (the validator's verdict, or the
 * heuristics labelled as heuristic), and regenerating a PDF Filinq
 * generated as PDF/UA. No text here claims a document is certified.
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * The route of a file's accessibility report.
 *
 * @param {number} fileId The file id.
 * @param {string} suffix A sub-route, '' for none.
 * @return {string} The URL.
 */
function route(fileId, suffix = '') {
	return generateUrl(
		`/apps/filinq/api/validation/accessibility/${Number(fileId)}${suffix}`,
	)
}

/**
 * The stored report, the heuristic findings and the state they surface.
 *
 * @param {number} fileId The file id.
 * @return {Promise<{report: object|null, conformance: object, heuristicFindings: Array<object>, regenerable: boolean}>} The state.
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-validator-verdict-is-the-authoritative-conformance-heuristics-remain-the-floor-req-ddpum-004
 */
export async function fetchAccessibility(fileId) {
	const { data } = await axios.get(route(fileId))
	return {
		report: data.report || null,
		conformance: {
			source: 'heuristic',
			state: 'looks-tagged',
			validated: false,
			validatorAvailable: false,
			...(data.conformance || {}),
		},
		heuristicFindings: data.heuristicFindings || [],
		regenerable: Boolean(data.regenerable),
	}
}

/**
 * Run the PDF/UA-1 check now; the server stores the report.
 *
 * @param {number} fileId The file id.
 * @return {Promise<object>} The stored report.
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
 */
export async function checkAccessibility(fileId) {
	const { data } = await axios.post(route(fileId))
	return data.report
}

/**
 * Regenerate a PDF Filinq generated as PDF/UA. The server keeps the new copy
 * only when it passes the check.
 *
 * @param {number} fileId The file id.
 * @return {Promise<{replaced: boolean, report: object}>} Whether the copy replaced the file, and its verdict.
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-document-filinq-generated-can-be-regenerated-as-pdfua-an-imported-one-cannot-req-ddpum-008
 */
export async function regenerateAccessible(fileId) {
	const { data } = await axios.post(route(fileId, '/regenerate'))
	return { replaced: Boolean(data.replaced), report: data.report || null }
}

/**
 * The state line: a conformance verdict only when the validator gave one,
 * else what the heuristics saw, said to be heuristic.
 *
 * @param {object} conformance The surfaced state.
 * @return {string} The line.
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-validator-verdict-is-the-authoritative-conformance-heuristics-remain-the-floor-req-ddpum-004
 */
export function stateText(conformance) {
	switch (conformance?.state) {
		case 'conformant':
			return t('filinq', 'Conforms to PDF/UA-1 according to the validator')
		case 'non-conformant':
			return t('filinq', 'Does not conform to PDF/UA-1 according to the validator')
		case 'heuristic-issues':
			return t(
				'filinq',
				'Not validated against PDF/UA. A quick look found accessibility problems.',
			)
		default:
			return t(
				'filinq',
				'Not validated against PDF/UA. A quick look says it looks tagged, which is not a conformance verdict.',
			)
	}
}

/**
 * The label of where a finding or state comes from.
 *
 * @param {string} source validator | heuristic.
 * @return {string} The label, '' for an unknown source.
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-validator-verdict-is-the-authoritative-conformance-heuristics-remain-the-floor-req-ddpum-004
 */
export function sourceLabel(source) {
	switch (source) {
		case 'validator':
			return t('filinq', 'Validator')
		case 'heuristic':
			return t('filinq', 'Quick check')
		default:
			return ''
	}
}

/**
 * Whether the card offers to make the document accessible: only for a PDF
 * Filinq generated whose stored report did not pass.
 *
 * @param {object|null} report The stored report.
 * @param {boolean} regenerable Whether Filinq generated the PDF.
 * @return {boolean} True when the action is offered.
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-document-filinq-generated-can-be-regenerated-as-pdfua-an-imported-one-cannot-req-ddpum-008
 */
export function offersRegeneration(report, regenerable) {
	return regenerable === true && report !== null && report?.compliant === false
}

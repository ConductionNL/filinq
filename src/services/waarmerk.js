/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The waarmerk (organisation seal) endpoints: sealing a document,
 * certifying an anonymisation run, the seals of a file and the signing
 * evidence document.
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * Seal a PDF the user can read; the sealed copy is written next to it.
 *
 * @param {number} fileId The file id.
 * @return {Promise<object>} `{ waarmerk, fileId, fileName, sourceFileId }`.
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
 */
export async function sealFile(fileId) {
	const { data } = await axios.post(generateUrl('/apps/filinq/api/waarmerk/seal'), { fileId: Number(fileId) })
	return data
}

/**
 * Certify an anonymisation run with a sealed processing certificate.
 *
 * @param {string} linkId The anonymizationLink id.
 * @return {Promise<object>} `{ waarmerk, fileId, fileName, certificate }`.
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
 */
export async function certifyRun(linkId) {
	const { data } = await axios.post(generateUrl(`/apps/filinq/api/waarmerk/anonymization/${encodeURIComponent(linkId)}`))
	return data
}

/**
 * The seals made of one sealed file (the user's own, all for an admin).
 *
 * @param {number} fileId The sealed file id.
 * @return {Promise<Array<object>>} The seals.
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
 */
export async function sealsOfFile(fileId) {
	const { data } = await axios.get(generateUrl('/apps/filinq/api/waarmerk'), { params: { documentFileId: Number(fileId) } })
	return Array.isArray(data.results) ? data.results : []
}

/**
 * The download URL of a signing request's sealed evidence document.
 *
 * @param {string} requestId The signing request id.
 * @return {string} The URL.
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
 */
export function evidenceUrl(requestId) {
	return generateUrl(`/apps/filinq/api/signing/${encodeURIComponent(requestId)}/evidence`)
}

/**
 * Write the sealed evidence document next to the signed document.
 *
 * @param {string} requestId The signing request id.
 * @return {Promise<object>} `{ waarmerk, fileId, fileName }`.
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
 */
export async function copyEvidence(requestId) {
	const { data } = await axios.post(`${evidenceUrl(requestId)}/copy`)
	return data
}

/**
 * The statuses after which a signing request has an evidence document.
 *
 * @type {Array<string>}
 */
export const TERMINAL_SIGNING_STATUSES = ['COMPLETED', 'DECLINED', 'CANCELLED', 'EXPIRED']

/**
 * The ids of the sealed files among the user's seals (all for an admin).
 *
 * @return {Promise<Set<number>>} The sealed file ids.
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
 */
export async function sealedFileIds() {
	const { data } = await axios.get(generateUrl('/apps/filinq/api/waarmerk'))
	const results = Array.isArray(data.results) ? data.results : []
	return new Set(results.filter((seal) => seal.status === 'active').map((seal) => Number(seal.documentFileId)).filter((id) => id > 0))
}

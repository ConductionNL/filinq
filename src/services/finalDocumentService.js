/**
 * Final document service, a thin client over the Filinq final-document
 * endpoints. A final document refuses edits, new versions and deletion; a
 * correction supersedes it instead of overwriting it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * Read the final state of a document, its chain and its checksum.
 *
 * @param {number} fileId The Nextcloud file id.
 * @return {Promise<object>} The final state.
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
export async function readFinalState(fileId) {
	const url = generateUrl('/apps/filinq/api/documents/{fileId}/final', { fileId })
	const { data } = await axios.get(url)
	return data
}

/**
 * Make a document's current version final.
 *
 * @param {number} fileId The Nextcloud file id.
 * @param {string} reason The reason or the act that makes it final.
 * @return {Promise<object>} The stored final version.
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
export async function finaliseDocument(fileId, reason) {
	const url = generateUrl('/apps/filinq/api/documents/{fileId}/final', { fileId })
	const { data } = await axios.post(url, { reason })
	return data
}

/**
 * Correct a final document by superseding it.
 *
 * @param {number} fileId The Nextcloud file id of the final document.
 * @param {string} reason What the correction changes.
 * @return {Promise<object>} The correction, with the version it supersedes.
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
export async function correctDocument(fileId, reason) {
	const url = generateUrl('/apps/filinq/api/documents/{fileId}/final/correction', {
		fileId,
	})
	const { data } = await axios.post(url, { reason })
	return data
}

/**
 * Unfreeze a final version, with a reason that stays on the record.
 *
 * @param {number} fileId The Nextcloud file id.
 * @param {string} reason Why the version is being unfrozen.
 * @return {Promise<object>} The unfrozen version.
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
export async function unfreezeDocument(fileId, reason) {
	const url = generateUrl('/apps/filinq/api/documents/{fileId}/final', { fileId })
	const { data } = await axios.delete(url, { data: { reason } })
	return data
}

/**
 * Store a consuming app's declaration of which states make a document final.
 *
 * @param {object} rule The declaration.
 * @param {string} rule.declaringApp App id of the consuming app.
 * @param {string} rule.typeReference That app's reference for the record type.
 * @param {Array<string>} rule.finalStates The states that make its documents final.
 * @param {string} [rule.documentRole] Which document of the record, or empty for every one.
 * @param {string} [rule.reasonTemplate] The reason written on the version.
 * @return {Promise<object>} The stored declaration.
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
export async function declareFinalityRule(rule) {
	const url = generateUrl('/apps/filinq/api/document-finality-rules')
	const { data } = await axios.post(url, rule)
	return data
}

/**
 * Intake service, a thin client over the Filinq intake endpoints. A document
 * that arrived through a scanner, a mailbox or digital post waits in the inbox
 * until a clerk assigns it to a record or rejects it with a reason.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * Read the documents waiting for a clerk.
 *
 * @return {Promise<object[]>} The waiting documents.
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
export async function listWaitingDocuments() {
	const url = generateUrl('/apps/filinq/api/intake/documents')
	const { data } = await axios.get(url)
	return Array.isArray(data?.results) ? data.results : []
}

/**
 * Assign one waiting document to a record.
 *
 * @param {string} uuid The intake document.
 * @param {object} target The record, as register, schema and id.
 * @return {Promise<object>} The assigned document.
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
export async function assignIntakeDocument(uuid, target) {
	const url = generateUrl('/apps/filinq/api/intake/documents/{uuid}/assign', {
		uuid,
	})
	const { data } = await axios.post(url, {
		register: target?.register || '',
		schema: target?.schema || '',
		id: target?.id || '',
	})
	return data
}

/**
 * Reject one waiting document.
 *
 * @param {string} uuid The intake document.
 * @param {string} reason Why it does not belong here.
 * @return {Promise<object>} The rejected document.
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
export async function rejectIntakeDocument(uuid, reason) {
	const url = generateUrl('/apps/filinq/api/intake/documents/{uuid}/reject', {
		uuid,
	})
	const { data } = await axios.post(url, { reason })
	return data
}

/**
 * Read the documents taken back off a record.
 *
 * @return {Promise<object[]>} The worklist.
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */
export async function listDetachedDocuments() {
	const url = generateUrl('/apps/filinq/api/intake/detached')
	const { data } = await axios.get(url)
	return Array.isArray(data?.results) ? data.results : []
}

/**
 * Take one document off the record it is filed on.
 *
 * @param {number} fileId The Nextcloud file id.
 * @param {string} reason Why it does not belong there.
 * @param {string} documentName The document name, for a file that never had an intake record.
 * @return {Promise<object>} The intake document, now on the worklist.
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */
export async function detachDocument(fileId, reason, documentName = '') {
	const url = generateUrl('/apps/filinq/api/intake/documents/detach')
	const { data } = await axios.post(url, { fileId, reason, documentName })
	return data
}

/**
 * Record what a clerk did with a party suggestion.
 *
 * @param {object} decision The decision: sender, decision, suggested, accepted, intakeDocument.
 * @return {Promise<object>} The stored correction.
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */
export async function decidePartySuggestion(decision) {
	const url = generateUrl('/apps/filinq/api/intake/party-decisions')
	const { data } = await axios.post(url, decision)
	return data
}

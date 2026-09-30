/**
 * Signing envelopes (bulk-signing-field-builder REQ-DDBSF-004/005): several
 * documents signed in one ceremony. Each document stays its own signing
 * request; the envelope groups them.
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

const BASE = '/apps/filinq/api/signing/envelopes'

/**
 * Member statuses a signer can still act on.
 */
const OPEN = ['PENDING', 'IN_PROGRESS']

/**
 * Create an envelope.
 *
 * @param {object} input title, documents, signers, signatureLevel, signingMode, requiredAssurance
 * @return {Promise<object>} The envelope with its members
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
export async function createEnvelope(input) {
	const response = await axios.post(generateUrl(BASE), input)
	return response.data
}

/**
 * Read an envelope with its members.
 *
 * @param {string} id The envelope uuid
 * @return {Promise<object>} The envelope
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
export async function fetchEnvelope(id) {
	const response = await axios.get(generateUrl(`${BASE}/${id}`))
	return response.data
}

/**
 * Sign every document of the envelope the caller still has to sign.
 *
 * @param {string} id The envelope uuid
 * @return {Promise<object>} `{ envelope, results }`
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
export async function signAllInEnvelope(id) {
	const response = await axios.post(generateUrl(`${BASE}/${id}/sign`))
	return response.data
}

/**
 * Cancel an envelope.
 *
 * @param {string} id The envelope uuid
 * @return {Promise<object>} The envelope
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
export async function cancelEnvelope(id) {
	const response = await axios.post(generateUrl(`${BASE}/${id}/cancel`))
	return response.data
}

/**
 * The documents to send: rows with a file id, trimmed, each once.
 *
 * @param {Array<object>} rows `{ documentFileId, documentName }` rows from the dialog
 * @return {Array<object>} The documents
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
export function toDocuments(rows) {
	const seen = new Set()
	const documents = []
	for (const row of rows) {
		const documentFileId = String(row.documentFileId ?? '').trim()
		if (documentFileId === '' || seen.has(documentFileId)) {
			continue
		}
		seen.add(documentFileId)
		documents.push({
			documentFileId,
			documentName: String(row.documentName ?? '').trim() || documentFileId,
		})
	}
	return documents
}

/**
 * Whether the user can sign the envelope now: a signer it names, with a document still open.
 *
 * @param {object} envelope The envelope with members
 * @param {string} uid The current user
 * @return {boolean}
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
export function canSignAll(envelope, uid) {
	return (
		Boolean(envelope)
		&& envelope.status !== 'cancelled'
		&& (envelope.signerUserIds || []).includes(uid)
		&& (envelope.members || []).some((member) => OPEN.includes(member.status))
	)
}

/**
 * Whether the user can cancel the envelope: its sender, while something is open.
 *
 * @param {object} envelope The envelope with members
 * @param {string} uid The current user
 * @return {boolean}
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
export function canCancel(envelope, uid) {
	return (
		Boolean(envelope)
		&& envelope.initiatorUserId === uid
		&& ['pending', 'in_progress'].includes(envelope.status)
	)
}

/**
 * What a sign-all answer means for the user.
 *
 * @param {object} results The results per request id
 * @return {{ signed: number, refused: Array<string> }} Counts and the refusal messages
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
export function summariseSignAll(results) {
	const entries = Object.values(results || {})
	return {
		signed: entries.filter((result) => result.success).length,
		refused: entries
			.filter((result) => !result.success)
			.map((result) => result.error || ''),
	}
}

/**
 * The envelope status in words.
 *
 * @param {string} status The status
 * @return {string}
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
export function envelopeStatusLabel(status) {
	const labels = {
		pending: t('filinq', 'Waiting for signatures'),
		in_progress: t('filinq', 'Being signed'),
		completed: t('filinq', 'Signed'),
		partially_declined: t('filinq', 'Partly declined'),
		incomplete: t('filinq', 'Ended incomplete'),
		cancelled: t('filinq', 'Cancelled'),
	}
	return labels[status] || status
}

/**
 * The error a failed call carries, or a general one.
 *
 * @param {Error} error The failure
 * @return {string}
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
export function envelopeError(error) {
	return (
		error?.response?.data?.error
		|| t('filinq', 'The envelope could not be reached. Try again.')
	)
}

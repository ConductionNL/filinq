/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The plain logic of the public verification page: the token in the page's
 * address, the verdict from the public endpoint, the words for each status,
 * and a local re-hash of a copy the visitor holds. No Vue, so the jest spec
 * runs it as plain JavaScript.
 *
 * The page never claims more than the record says: a signer is "signed by"
 * only when the signature is verified AND bound to the signer; otherwise the
 * name is shown as asserted, not cryptographically bound (REQ-DDSVP-002).
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
 */

import axios from '@nextcloud/axios'
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/** The neutral verdict, in the shape every answer has. */
export const UNKNOWN_VERDICT = Object.freeze({
	status: 'unknown',
	fileName: '',
	contentHash: '',
	recordedAt: '',
	signatures: [],
	audit: { signatureCount: 0, verifiedCount: 0 },
	seal: null,
})

/**
 * The token at the end of `/apps/filinq/verify/{token}`, or ''.
 *
 * @param {string} pathname The page's path.
 * @return {string} The token.
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
 */
export function tokenFromPath(pathname) {
	const match = /\/verify\/([^/?#]+)\/?$/.exec(String(pathname || ''))
	if (!match) {
		return ''
	}
	try {
		return decodeURIComponent(match[1])
	} catch {
		return ''
	}
}

/**
 * The verdict for a token. Anything that is not a well-formed answer reads
 * as the neutral unknown verdict: the page never shows a valid outcome it
 * did not receive.
 *
 * @param {string} token The token.
 * @param {object} [http] The HTTP client (axios shape).
 * @return {Promise<object>} The verdict.
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
 */
export async function fetchVerdict(token, http = axios) {
	if (!token) {
		return { ...UNKNOWN_VERDICT }
	}
	try {
		const { data } = await http.get(generateUrl('/apps/filinq/api/verify/{token}', { token }))
		if (!data || typeof data.status !== 'string' || !Array.isArray(data.signatures)) {
			return { ...UNKNOWN_VERDICT }
		}
		return data
	} catch {
		return { ...UNKNOWN_VERDICT }
	}
}

/**
 * The headline and note-card type for the document's overall status.
 *
 * @param {string} status verified|tampered|unverifiable|mixed|unknown.
 * @return {{type: string, text: string}} The headline.
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
 */
export function verdictHeadline(status) {
	switch (status) {
	case 'verified':
		return { type: 'success', text: t('filinq', 'The document content is unchanged since it was signed.') }
	case 'tampered':
		return { type: 'error', text: t('filinq', 'The document content was changed after it was signed.') }
	case 'mixed':
		return { type: 'warning', text: t('filinq', 'Not every signature on this document could be confirmed.') }
	case 'unverifiable':
		return { type: 'warning', text: t('filinq', 'The signatures on this document cannot be checked here.') }
	default:
		return { type: 'info', text: t('filinq', 'No signed document matches this link.') }
	}
}

/**
 * One signature's status in words.
 *
 * @param {string} status verified|invalid|unverifiable.
 * @return {string} The label.
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
 */
export function signatureStatusLabel(status) {
	if (status === 'verified') {
		return t('filinq', 'Verified')
	}
	if (status === 'invalid') {
		return t('filinq', 'Invalid')
	}
	return t('filinq', 'Cannot be checked')
}

/**
 * Who signed, saying only what the signature guarantees.
 *
 * @param {object} signature The public signature (`signerAsserted`, `integrityVerified`, `identityBound`).
 * @return {string} The line.
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
 */
export function signerLine(signature) {
	const signer = String(signature?.signerAsserted || '')
	if (signer === '') {
		return t('filinq', 'No signer name was given.')
	}
	if (signature.integrityVerified === true && signature.identityBound === true) {
		return t('filinq', 'Signed by {signer}', { signer })
	}
	return t('filinq', 'Signer as stated: {signer} (not cryptographically bound to the signature)', { signer })
}

/**
 * The audit summary: how many of the signatures were verified.
 *
 * @param {object} audit `{signatureCount, verifiedCount}`.
 * @return {string} The line.
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
 */
export function auditLine(audit) {
	const total = Number(audit?.signatureCount || 0)
	const verified = Number(audit?.verifiedCount || 0)
	return n('filinq', '{verified} of %n signature verified', '{verified} of %n signatures verified', total, { verified })
}

/**
 * The seal's status in words, or '' when the document names no seal.
 *
 * @param {object|null} seal `{status, reason?}` or null.
 * @return {string} The line.
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
 */
export function sealLine(seal) {
	if (!seal) {
		return ''
	}
	if (seal.status === 'valid') {
		return t('filinq', 'The organisation seal on this document is valid.')
	}
	if (seal.status === 'revoked') {
		return t('filinq', 'The organisation seal on this document was revoked.')
	}
	return t('filinq', 'The organisation seal on this document cannot be checked right now.')
}

/**
 * The sha256 of a file the visitor picked, as lowercase hex. The bytes stay
 * in the browser: nothing is uploaded.
 *
 * @param {Blob} file The file.
 * @param {SubtleCrypto} [subtle] The WebCrypto digest provider.
 * @return {Promise<string>} The hex digest.
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
 */
export async function sha256Hex(file, subtle = globalThis.crypto?.subtle) {
	const digest = await subtle.digest('SHA-256', await file.arrayBuffer())
	return [...new Uint8Array(digest)].map((byte) => byte.toString(16).padStart(2, '0')).join('')
}

/**
 * Whether a local copy's hash matches the recorded one.
 *
 * @param {string} localHash The copy's hex sha256.
 * @param {string} recordedHash The record's contentHash.
 * @return {boolean|null} True or false, null when there is nothing to compare.
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
 */
export function copyMatches(localHash, recordedHash) {
	const recorded = String(recordedHash || '').toLowerCase()
	if (recorded === '' || !localHash) {
		return null
	}
	return String(localHash).toLowerCase() === recorded
}

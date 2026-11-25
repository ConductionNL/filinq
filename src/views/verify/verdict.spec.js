/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The public verification page's logic: the token from the address, a
 * verdict that is never more than the endpoint said, a signer named "signed
 * by" only when bound, and a local re-hash that uploads nothing.
 *
 * @spec openspec/changes/archive/2026-11-25-signature-verification-portal/tasks.md#task-3.1
 */

jest.mock('@nextcloud/l10n', () => {
	const fill = (text, vars) => text.replace(/\{(\w+)\}/g, (m, k) => (vars && k in vars ? vars[k] : m))
	return {
		translate: (app, text, vars) => fill(text, vars),
		translatePlural: (app, one, many, count, vars) => fill((count === 1 ? one : many).replace('%n', count), vars),
	}
})
jest.mock('@nextcloud/router', () => ({
	generateUrl: (url, params) => url.replace('{token}', encodeURIComponent(params.token)),
}))
jest.mock('@nextcloud/axios', () => ({ get: jest.fn() }))

const { createHash } = require('crypto')
const {
	UNKNOWN_VERDICT,
	auditLine,
	copyMatches,
	fetchVerdict,
	sealLine,
	sha256Hex,
	signatureStatusLabel,
	signerLine,
	tokenFromPath,
	verdictHeadline,
} = require('./verdict.js')

describe('tokenFromPath', () => {
	it('reads the token at the end of the verify address', () => {
		expect(tokenFromPath('/index.php/apps/filinq/verify/ab12cd')).toBe('ab12cd')
		expect(tokenFromPath('/apps/filinq/verify/ab12cd/')).toBe('ab12cd')
	})

	it('answers empty for an address without a token or a broken escape', () => {
		expect(tokenFromPath('/apps/filinq/')).toBe('')
		expect(tokenFromPath('/apps/filinq/verify/%E0%A4%A')).toBe('')
		expect(tokenFromPath(undefined)).toBe('')
	})
})

describe('fetchVerdict', () => {
	it('asks the public endpoint for the token and passes its verdict on', async () => {
		const verdict = { ...UNKNOWN_VERDICT, status: 'verified', signatures: [{ status: 'verified' }] }
		const http = { get: jest.fn().mockResolvedValue({ data: verdict }) }

		await expect(fetchVerdict('ab/12', http)).resolves.toBe(verdict)
		expect(http.get).toHaveBeenCalledWith('/apps/filinq/api/verify/ab%2F12')
	})

	it('reads no token, a failed request and a malformed answer as unknown', async () => {
		const http = { get: jest.fn() }
		await expect(fetchVerdict('', http)).resolves.toEqual(UNKNOWN_VERDICT)
		expect(http.get).not.toHaveBeenCalled()

		http.get.mockRejectedValueOnce(new Error('offline'))
		await expect(fetchVerdict('x', http)).resolves.toEqual(UNKNOWN_VERDICT)

		http.get.mockResolvedValueOnce({ data: { status: 'verified' } })
		await expect(fetchVerdict('x', http)).resolves.toEqual(UNKNOWN_VERDICT)
	})
})

describe('verdictHeadline', () => {
	it('names each overall status, and anything else as no match', () => {
		expect(verdictHeadline('verified')).toEqual({ type: 'success', text: 'The document content is unchanged since it was signed.' })
		expect(verdictHeadline('tampered').type).toBe('error')
		expect(verdictHeadline('mixed').type).toBe('warning')
		expect(verdictHeadline('unverifiable').text).toBe('The signatures on this document cannot be checked here.')
		expect(verdictHeadline('unknown')).toEqual({ type: 'info', text: 'No signed document matches this link.' })
		expect(verdictHeadline('valid')).toEqual(verdictHeadline('unknown'))
	})
})

describe('signatureStatusLabel', () => {
	it('labels the three states, an unknown state as cannot be checked', () => {
		expect(signatureStatusLabel('verified')).toBe('Verified')
		expect(signatureStatusLabel('invalid')).toBe('Invalid')
		expect(signatureStatusLabel('unverifiable')).toBe('Cannot be checked')
		expect(signatureStatusLabel('whatever')).toBe('Cannot be checked')
	})
})

describe('signerLine', () => {
	it('says signed by only when the signature is verified and bound to the signer', () => {
		expect(signerLine({ signerAsserted: 'A. Jansen', integrityVerified: true, identityBound: true })).toBe('Signed by A. Jansen')
	})

	it('shows the signer as stated while the signature does not bind them', () => {
		const stated = 'Signer as stated: A. Jansen (not cryptographically bound to the signature)'
		expect(signerLine({ signerAsserted: 'A. Jansen', integrityVerified: true, identityBound: false })).toBe(stated)
		expect(signerLine({ signerAsserted: 'A. Jansen', integrityVerified: false, identityBound: true })).toBe(stated)
	})

	it('says when no name was given', () => {
		expect(signerLine({ signerAsserted: '' })).toBe('No signer name was given.')
	})
})

describe('auditLine and sealLine', () => {
	it('counts the verified signatures', () => {
		expect(auditLine({ signatureCount: 2, verifiedCount: 1 })).toBe('1 of 2 signatures verified')
		expect(auditLine({ signatureCount: 1, verifiedCount: 1 })).toBe('1 of 1 signature verified')
		expect(auditLine(undefined)).toBe('0 of 0 signatures verified')
	})

	it('names the seal status, nothing for no seal', () => {
		expect(sealLine(null)).toBe('')
		expect(sealLine({ status: 'valid' })).toBe('The organisation seal on this document is valid.')
		expect(sealLine({ status: 'revoked' })).toBe('The organisation seal on this document was revoked.')
		expect(sealLine({ status: 'unverifiable', reason: 'seal-check-unavailable' })).toBe('The organisation seal on this document cannot be checked right now.')
	})
})

describe('the local copy check', () => {
	it('hashes the picked copy with WebCrypto and compares it case-blind', async () => {
		const bytes = Uint8Array.from(Buffer.from('signed bytes'))
		const file = { arrayBuffer: async () => bytes.buffer }
		const subtle = { digest: jest.fn(async (algorithm, data) => new Uint8Array(createHash('sha256').update(Buffer.from(data)).digest()).buffer) }
		const expected = createHash('sha256').update('signed bytes').digest('hex')

		const hash = await sha256Hex(file, subtle)

		expect(subtle.digest).toHaveBeenCalledWith('SHA-256', bytes.buffer)
		expect(hash).toBe(expected)
		expect(copyMatches(hash, expected.toUpperCase())).toBe(true)
		expect(copyMatches(hash, 'ff')).toBe(false)
		expect(copyMatches(hash, '')).toBeNull()
		expect(copyMatches('', expected)).toBeNull()
	})
})

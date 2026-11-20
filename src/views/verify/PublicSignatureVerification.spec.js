/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The public verification page: the verdict for its token, the signer as
 * stated while unbound, no link or download anywhere, and an unknown token
 * that shows only the neutral outcome.
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
 */

const { nextTick } = require('vue')

jest.mock('@nextcloud/l10n', () => {
	const fill = (text, vars) => text.replace(/\{(\w+)\}/g, (m, k) => (vars && k in vars ? vars[k] : m))
	return {
		translate: (app, text, vars) => fill(text, vars),
		translatePlural: (app, one, many, count, vars) => fill((count === 1 ? one : many).replace('%n', count), vars),
	}
})
jest.mock('@nextcloud/router', () => ({
	generateUrl: (url, params) => url.replace('{token}', params.token),
}))
jest.mock('@nextcloud/axios', () => ({ get: jest.fn() }))

const axios = require('@nextcloud/axios')
const { renderStubbed } = require('../../../tests/jest/renderGolden.js')
const PublicSignatureVerification = require('./PublicSignatureVerification.vue').default

let rendered

/**
 * Mount the page for a token whose endpoint answers the given verdict.
 *
 * @param {object} verdict The endpoint's answer.
 * @return {Promise<object>} The rendered page.
 */
async function mountPage(verdict) {
	axios.get.mockResolvedValue({ data: verdict })
	rendered = await renderStubbed(PublicSignatureVerification, { props: { token: 'tok123' } })
	for (let i = 0; i < 4; i++) {
		await nextTick()
	}
	return rendered
}

const text = (wrapper, id) => [...wrapper.vm.$el.querySelectorAll(`[data-testid="${id}"]`)].map((n) => n.textContent.trim())

afterEach(() => {
	rendered?.unmount()
	rendered = null
	axios.get.mockReset()
})

describe('PublicSignatureVerification', () => {
	it('shows the verdict for its token with the signer as stated while unbound', async () => {
		const wrapper = await mountPage({
			status: 'verified',
			fileName: 'Besluit.pdf',
			contentHash: 'ab',
			recordedAt: '2026-11-01T10:00:00Z',
			signatures: [{ status: 'verified', level: 'advanced', signerAsserted: 'A. Jansen', integrityVerified: true, identityBound: false }],
			audit: { signatureCount: 1, verifiedCount: 1 },
			seal: null,
		})

		expect(axios.get).toHaveBeenCalledWith('/apps/filinq/api/verify/tok123')
		expect(text(wrapper, 'verification-verdict')).toEqual(['The document content is unchanged since it was signed.'])
		expect(text(wrapper, 'verification-file-name')).toEqual(['Besluit.pdf'])
		expect(text(wrapper, 'verification-audit')).toEqual(['1 of 1 signature verified'])
		expect(text(wrapper, 'verification-signer')).toEqual(['Signer as stated: A. Jansen (not cryptographically bound to the signature)'])
		expect(wrapper.vm.$el.textContent).not.toContain('Signed by')
		expect(wrapper.vm.$el.querySelectorAll('a')).toHaveLength(0)
		expect(text(wrapper, 'verification-seal')).toEqual([])
	})

	it('shows only the neutral outcome for an unknown token', async () => {
		const wrapper = await mountPage({ status: 'unknown', fileName: '', contentHash: '', recordedAt: '', signatures: [], audit: { signatureCount: 0, verifiedCount: 0 }, seal: null })

		expect(text(wrapper, 'verification-verdict')).toEqual(['No signed document matches this link.'])
		expect(text(wrapper, 'verification-file-name')).toEqual([])
		expect(text(wrapper, 'verification-copy')).toEqual([])
	})
})

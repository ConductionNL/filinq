/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The public verification page: the file is hashed locally and only
 * `{code, sha256}` is posted; the outcome is shown in words.
 */

import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import WaarmerkVerifyPage from './WaarmerkVerifyPage.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'
import { sha256Hex } from './sha256.js'

jest.mock('@nextcloud/axios', () => ({ __esModule: true, default: { post: jest.fn() } }))
jest.mock('@nextcloud/initial-state', () => ({ loadState: jest.fn() }))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('./sha256.js', () => ({ sha256Hex: jest.fn() }))

const CODE = '3f2b1c4d-5e6f-4a7b-8c9d-0e1f2a3b4c5d-abcd'

beforeEach(() => {
	jest.clearAllMocks()
})

test('an active seal shows its facts and the check; only the hash is posted', async () => {
	loadState.mockReturnValue({ status: 'active', code: CODE, organisation: 'Gemeente Demostad', sealedAt: '2026-11-14T10:00:00+00:00', documentName: 'besluit.pdf' })
	sha256Hex.mockResolvedValue('ab'.repeat(32))
	axios.post.mockResolvedValue({ data: { status: 'valid', certificateExpired: false } })
	const file = { name: 'besluit.pdf' }
	const page = await renderStubbed(WaarmerkVerifyPage)

	expect(page.html()).toContain('Gemeente Demostad')
	expect(page.html()).toContain('This waarmerk exists')
	await page.vm.check({ target: { files: [file] } })

	expect(sha256Hex).toHaveBeenCalledWith(file)
	expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/waarmerk/verify', { code: CODE, sha256: 'ab'.repeat(32) })
	expect(page.vm.resultTitle).toBe('Valid')
	page.unmount()
})

test('a mismatch, a failure and an unknown code read as not valid', async () => {
	loadState.mockReturnValue({ status: 'active', code: CODE })
	sha256Hex.mockResolvedValue('00'.repeat(32))
	axios.post.mockResolvedValueOnce({ data: { status: 'hash-mismatch' } }).mockRejectedValueOnce(new Error('429'))
	const page = await renderStubbed(WaarmerkVerifyPage)

	await page.vm.check({ target: { files: [{}] } })
	expect(page.vm.resultTitle).toBe('Not the sealed document')
	await page.vm.check({ target: { files: [{}] } })
	expect(page.vm.result).toEqual({ status: 'unknown' })
	expect(page.vm.resultTitle).toBe('Could not be verified')
	page.unmount()

	loadState.mockReturnValue({ status: 'unknown', code: 'x' })
	const unknown = await renderStubbed(WaarmerkVerifyPage)
	expect(unknown.html()).toContain('No waarmerk is known with this code')
	expect(unknown.html()).not.toContain('Check your copy')
	unknown.unmount()
})

test('a revoked seal shows the revocation and offers no check', async () => {
	loadState.mockReturnValue({ status: 'revoked', code: CODE, revokedAt: '2026-11-15', revocationReason: 'document withdrawn after objection' })
	const page = await renderStubbed(WaarmerkVerifyPage)

	expect(page.html()).toContain('This waarmerk has been revoked')
	expect(page.html()).toContain('document withdrawn after objection')
	expect(page.html()).not.toContain('Check your copy')
	page.unmount()
})

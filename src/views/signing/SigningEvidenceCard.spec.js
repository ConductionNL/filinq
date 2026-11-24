/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The evidence card of the signing request page: download and copy for a
 * finished request only.
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import SigningEvidenceCard from './SigningEvidenceCard.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'

jest.mock('@nextcloud/axios', () => ({ __esModule: true, default: { post: jest.fn() } }))
jest.mock('@nextcloud/dialogs', () => ({ showError: jest.fn(), showSuccess: jest.fn() }))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text, vars) => (vars ? text.replace('{name}', vars.name) : text) }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('@nextcloud/vue', () => ({ NcButton: {} }))

beforeEach(() => {
	jest.clearAllMocks()
})

test('a completed request offers the download link and the copy', async () => {
	axios.post.mockResolvedValue({ data: { fileName: 'contract-bewijsdocument.pdf' } })
	const page = await renderStubbed(SigningEvidenceCard, { props: { requestId: 'req-1', status: 'COMPLETED' } })

	expect(page.vm.finished).toBe(true)
	expect(page.vm.downloadUrl).toBe('/index.php/apps/filinq/api/signing/req-1/evidence')
	expect(page.html()).toContain('Download evidence document (pdf)')
	await page.vm.copy()
	expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/signing/req-1/evidence/copy')
	expect(showSuccess).toHaveBeenCalledWith('Evidence document saved as contract-bewijsdocument.pdf')
	page.unmount()
})

test('an open request offers nothing; a refused copy shows the reason', async () => {
	const open = await renderStubbed(SigningEvidenceCard, { props: { requestId: 'req-1', status: 'IN_PROGRESS' } })
	expect(open.vm.finished).toBe(false)
	expect(open.html()).not.toContain('Download evidence document (pdf)')
	open.unmount()

	axios.post.mockRejectedValue({ response: { data: { error: 'still open' } } })
	const page = await renderStubbed(SigningEvidenceCard, { props: { requestId: 'req-1', status: 'DECLINED' } })
	await page.vm.copy()
	expect(showError).toHaveBeenCalledWith('still open')
	page.unmount()
})

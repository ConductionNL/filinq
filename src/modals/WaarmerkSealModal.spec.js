/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * "Add waarmerk" and the anonymisation certificate dialog.
 */

import { certifyRun, sealFile } from '../services/waarmerk.js'
import WaarmerkSealModal from './WaarmerkSealModal.vue'
import { renderStubbed } from '../../tests/jest/renderGolden.js'

jest.mock('../services/waarmerk.js', () => ({ certifyRun: jest.fn(), sealFile: jest.fn() }))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/vue', () => ({ NcButton: {}, NcLoadingIcon: {}, NcModal: {}, NcNoteCard: {} }))

beforeEach(() => {
	jest.clearAllMocks()
})

test('sealing a file shows the verification code and emits the result', async () => {
	sealFile.mockResolvedValue({ fileId: 6, fileName: 'besluit-gewaarmerkt.pdf', waarmerk: { verificationCode: 'code-1', sealedAt: 'now' } })
	const page = await renderStubbed(WaarmerkSealModal, { props: { show: true, fileId: 5, fileName: 'besluit.pdf' } })

	await page.vm.run()

	expect(sealFile).toHaveBeenCalledWith(5)
	expect(certifyRun).not.toHaveBeenCalled()
	expect(page.vm.result.fileId).toBe(6)
	await page.vm.$nextTick()
	expect(page.html()).toContain('code-1')
	page.unmount()
})

test('the certificate mode certifies the run; a refusal shows the server reason', async () => {
	certifyRun.mockRejectedValue({ response: { data: { error: 'Key resolution failed' } } })
	const page = await renderStubbed(WaarmerkSealModal, { props: { show: true, mode: 'certificate', linkId: 'link-1' } })

	expect(page.vm.title).toBe('Anonymisation certificate')
	await page.vm.run()

	expect(certifyRun).toHaveBeenCalledWith('link-1')
	expect(page.vm.error).toBe('Key resolution failed')
	expect(page.vm.result).toBeNull()
	page.vm.close()
	expect(page.vm.error).toBe('')
	page.unmount()
})

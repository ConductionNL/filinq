/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * "Add waarmerk" and the anonymisation certificate dialog.
 */

import WaarmerkSealModal from './WaarmerkSealModal.vue'
import { renderStubbed } from '../../tests/jest/renderGolden.js'
import { certifyRun, sealFile } from '../services/waarmerk.js'
import { sanitizationStatus } from '../services/sanitization.js'

jest.mock('../services/waarmerk.js', () => ({ certifyRun: jest.fn(), sealFile: jest.fn() }))
jest.mock('../services/sanitization.js', () => ({ sanitizationStatus: jest.fn() }))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/vue', () => ({ NcButton: {}, NcLoadingIcon: {}, NcModal: {}, NcNoteCard: {} }))

const flush = () => new Promise((resolve) => setTimeout(resolve, 0))

beforeEach(() => {
	jest.clearAllMocks()
	sanitizationStatus.mockResolvedValue({ ok: true, data: { sanitized: true, sealed: false } })
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

// document-sanitization REQ-DDSAN-005: the seal action shows whether the file is the clean copy.
test('an unsanitized file says so and hands the file to the sanitize action', async () => {
	sanitizationStatus.mockResolvedValue({ ok: true, data: { sanitized: false, sealed: false } })
	const page = await renderStubbed(WaarmerkSealModal, { props: { show: true, fileId: 5, fileName: 'besluit.pdf' } })
	await flush()
	await page.vm.$nextTick()

	expect(sanitizationStatus).toHaveBeenCalledWith(5)
	expect(page.vm.sanitized).toBe(false)
	expect(page.html()).toContain('waarmerk-seal-not-sanitized')
	expect(page.html()).not.toContain('waarmerk-seal-sanitized"')

	const emitted = []
	page.vm.$.emit = (name, ...args) => emitted.push([name, ...args])
	page.vm.sanitizeFirst()
	expect(emitted).toEqual([['close'], ['sanitize', 5]])
	page.unmount()
})

test('a sanitized copy is named as the one to seal', async () => {
	const page = await renderStubbed(WaarmerkSealModal, { props: { show: true, fileId: 6, fileName: 'besluit-opgeschoond.pdf' } })
	await flush()
	await page.vm.$nextTick()

	expect(page.vm.sanitized).toBe(true)
	expect(page.html()).toContain('waarmerk-seal-sanitized')
	expect(page.html()).not.toContain('waarmerk-seal-not-sanitized')
	page.unmount()
})

test('nothing is said when the state is unknown, and the certificate mode reads nothing', async () => {
	sanitizationStatus.mockResolvedValue({ ok: false, status: 404, error: 'gone' })
	const unread = await renderStubbed(WaarmerkSealModal, { props: { show: true, fileId: 7 } })
	await flush()
	expect(unread.vm.sanitized).toBeNull()
	expect(unread.html()).not.toContain('waarmerk-seal-not-sanitized')
	unread.unmount()

	sanitizationStatus.mockClear()
	const certificate = await renderStubbed(WaarmerkSealModal, { props: { show: true, mode: 'certificate', linkId: 'link-1', fileId: 7 } })
	await flush()
	expect(sanitizationStatus).not.toHaveBeenCalled()
	expect(certificate.vm.sanitized).toBeNull()
	certificate.unmount()

	const closed = await renderStubbed(WaarmerkSealModal, { props: { show: false, fileId: 7 } })
	await flush()
	expect(sanitizationStatus).not.toHaveBeenCalled()
	closed.unmount()
})

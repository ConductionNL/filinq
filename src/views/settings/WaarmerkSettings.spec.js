/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The waarmerk certificate panel: it shows the public certificate facts,
 * saves certificate, reference and custody, sends a typed local key once
 * and never keeps it, and runs the test seal.
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import WaarmerkSettings from './WaarmerkSettings.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn(), put: jest.fn(), post: jest.fn() },
}))
jest.mock('@nextcloud/dialogs', () => ({ showError: jest.fn(), showSuccess: jest.fn() }))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('@nextcloud/vue', () => ({
	NcButton: {},
	NcLoadingIcon: {},
	NcNoteCard: {},
	NcSelect: {},
	NcSettingsSection: {},
	NcTextField: {},
}))

const URL = '/index.php/apps/filinq/api/waarmerk/settings'
const flush = () => new Promise((resolve) => setTimeout(resolve, 0))
const SUMMARY = {
	configured: true,
	certificate: { subject: 'O=Gemeente Demostad, C=NL', fingerprint: 'AA:BB', validTo: '2027-11-14T00:00:00+00:00', expired: false },
	certificateError: null,
	credentialRef: 'doriath://org/demostad/waarmerk-seal',
	custody: 'broker',
	localKeyStored: false,
}

/**
 * Render the panel after its load settles.
 *
 * @return {Promise<object>} The render.
 */
async function render() {
	const page = await renderStubbed(WaarmerkSettings)
	await flush()
	return page
}

beforeEach(() => {
	jest.clearAllMocks()
})

test('the stored facts and reference are shown, never a key', async () => {
	axios.get.mockResolvedValue({ data: SUMMARY })

	const page = await render()

	expect(axios.get).toHaveBeenCalledWith(URL)
	expect(page.html()).toContain('O=Gemeente Demostad, C=NL')
	expect(page.html()).toContain('AA:BB')
	expect(page.vm.form).toEqual({ certificate: '', custody: 'broker', credentialRef: 'doriath://org/demostad/waarmerk-seal', localKey: '' })
	page.unmount()
})

test('a refused load is reported', async () => {
	axios.get.mockRejectedValue(new Error('403'))

	const page = await render()

	expect(page.vm.loading).toBe(false)
	expect(showError).toHaveBeenCalledWith('Could not load the waarmerk settings')
	page.unmount()
})

test('a local key is sent once and cleared; an empty certificate is not sent', async () => {
	axios.get.mockResolvedValue({ data: SUMMARY })
	axios.put.mockResolvedValue({ data: { ...SUMMARY, custody: 'local', localKeyStored: true } })
	const page = await render()
	page.vm.form.custody = 'local'
	page.vm.form.localKey = '-----BEGIN PRIVATE KEY-----x'

	await page.vm.save()

	expect(axios.put).toHaveBeenCalledWith(URL, { custody: 'local', credentialRef: 'doriath://org/demostad/waarmerk-seal', localKey: '-----BEGIN PRIVATE KEY-----x' })
	expect(page.vm.form.localKey).toBe('')
	expect(page.vm.summary.localKeyStored).toBe(true)
	expect(showSuccess).toHaveBeenCalledWith('Waarmerk settings saved')
	page.unmount()
})

test('broker custody never sends a typed key; a refusal shows the server reason and clears the key', async () => {
	axios.get.mockResolvedValue({ data: SUMMARY })
	axios.put.mockRejectedValue({ response: { data: { error: 'The certificate is not a valid X.509 certificate (PEM)' } } })
	const page = await render()
	page.vm.form.certificate = 'garbage'
	page.vm.form.localKey = 'left over'

	await page.vm.save()

	expect(axios.put).toHaveBeenCalledWith(URL, { custody: 'broker', credentialRef: 'doriath://org/demostad/waarmerk-seal', certificate: 'garbage' })
	expect(showError).toHaveBeenCalledWith('The certificate is not a valid X.509 certificate (PEM)')
	expect(page.vm.form.localKey).toBe('')
	page.unmount()
})

test('the test seal reports success, failure and the server refusal', async () => {
	axios.get.mockResolvedValue({ data: SUMMARY })
	axios.post
		.mockResolvedValueOnce({ data: { ok: true } })
		.mockResolvedValueOnce({ data: { ok: false } })
		.mockRejectedValueOnce({ response: { data: { error: 'Key resolution failed' } } })
	const page = await render()

	await page.vm.testSeal()
	expect(axios.post).toHaveBeenCalledWith(URL + '/test')
	expect(page.vm.testResult).toBe('The test seal was made and verified.')
	await page.vm.testSeal()
	expect(page.vm.testResult).toBe('The test seal could not be verified.')
	await page.vm.testSeal()
	expect(page.vm.testResult).toBe('Key resolution failed')
	page.unmount()
})

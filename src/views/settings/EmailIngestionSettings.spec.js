/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The email ingestion settings section: it loads the inbox mapping, adds and
 * removes inboxes, saves the mapping with numeric folder ids, and tells the
 * admin when loading or saving is refused.
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import EmailIngestionSettings from './EmailIngestionSettings.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn(), put: jest.fn() },
}))
jest.mock('@nextcloud/dialogs', () => ({ showError: jest.fn(), showSuccess: jest.fn() }))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('@nextcloud/vue', () => ({
	NcButton: {},
	NcLoadingIcon: {},
	NcSettingsSection: {},
	NcTextField: {},
}))

const flush = () => new Promise((resolve) => setTimeout(resolve, 0))

/**
 * Render the section after its load settles.
 *
 * @return {Promise<object>} The render.
 */
async function render() {
	const page = await renderStubbed(EmailIngestionSettings)
	await flush()
	return page
}

/**
 * The stubbed buttons, by their test id.
 *
 * @param {object} page The render.
 * @param {string} testId The data-testid.
 * @return {object} The stub.
 */
function button(page, testId) {
	return page.stubs.filter((stub) => stub.name === 'NcButton').find((stub) => stub.attrs['data-testid'] === testId)
}

beforeEach(() => {
	jest.clearAllMocks()
})

test('the inbox mapping is loaded with folder ids as text', async () => {
	axios.get.mockResolvedValue({
		data: { inboxes: [{ folderId: 42, dossierRef: 'd-1' }, { folderId: 7, dossierRef: 'd-2' }], filesPerTick: 10 },
	})

	const page = await render()

	expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/email-ingestion/settings')
	expect(page.vm.loading).toBe(false)
	expect(page.vm.inboxes).toEqual([
		{ folderId: '42', dossierRef: 'd-1' },
		{ folderId: '7', dossierRef: 'd-2' },
	])
	expect(page.vm.filesPerTick).toBe(10)
	expect(page.html()).toContain('data-testid="email-settings-boundary"')
	expect(showError).not.toHaveBeenCalled()
	page.unmount()
})

test('a refused load is reported and leaves no inboxes', async () => {
	axios.get.mockRejectedValue({ response: { status: 403, data: {} } })

	const page = await render()

	expect(page.vm.loading).toBe(false)
	expect(page.vm.inboxes).toEqual([])
	expect(page.vm.filesPerTick).toBe(25)
	expect(showError).toHaveBeenCalledWith('The email ingestion settings could not be loaded.')
	page.unmount()
})

test('an inbox can be added and removed', async () => {
	axios.get.mockResolvedValue({ data: { inboxes: [{ folderId: 1, dossierRef: 'a' }], filesPerTick: 25 } })
	const page = await render()

	button(page, 'email-settings-add').attrs.onClick()
	expect(page.vm.inboxes).toEqual([{ folderId: '1', dossierRef: 'a' }, { folderId: '', dossierRef: '' }])

	await flush()
	const remove = page.stubs.filter((stub) => stub.name === 'NcButton' && stub.attrs['aria-label'] === 'Remove this inbox')
	remove[remove.length - 2].attrs.onClick()
	expect(page.vm.inboxes).toEqual([{ folderId: '', dossierRef: '' }])
	page.unmount()
})

test('saving sends numeric folder ids and trimmed dossiers and keeps the stored emails per run', async () => {
	axios.get.mockResolvedValue({ data: { inboxes: [{ folderId: 42, dossierRef: ' d-1 ' }], filesPerTick: 25 } })
	axios.put.mockResolvedValue({ data: { filesPerTick: 50 } })
	const page = await render()
	page.vm.filesPerTick = '60'

	await page.vm.save()

	expect(axios.put).toHaveBeenCalledWith('/index.php/apps/filinq/api/email-ingestion/settings', {
		inboxes: [{ folderId: 42, dossierRef: 'd-1' }],
		filesPerTick: 60,
	})
	expect(page.vm.filesPerTick).toBe(50)
	expect(page.vm.saving).toBe(false)
	expect(showSuccess).toHaveBeenCalledWith('Email ingestion settings saved')
	page.unmount()
})

test('a refused save is reported and the form is left as it was', async () => {
	axios.get.mockResolvedValue({ data: { inboxes: [], filesPerTick: 25 } })
	axios.put.mockRejectedValue({ response: { status: 400, data: { error: 'duplicate' } } })
	const page = await render()

	await page.vm.save()

	expect(page.vm.saving).toBe(false)
	expect(page.vm.filesPerTick).toBe(25)
	expect(showSuccess).not.toHaveBeenCalled()
	expect(showError).toHaveBeenCalledWith(
		'Every inbox needs a folder and a dossier, and a folder can be the inbox of one dossier only.',
	)
	page.unmount()
})

test('the save button triggers a save', async () => {
	axios.get.mockResolvedValue({ data: { inboxes: [], filesPerTick: 25 } })
	axios.put.mockResolvedValue({ data: { filesPerTick: 25 } })
	const page = await render()

	button(page, 'email-settings-save').attrs.onClick()
	await flush()

	expect(axios.put).toHaveBeenCalledTimes(1)
	page.unmount()
})

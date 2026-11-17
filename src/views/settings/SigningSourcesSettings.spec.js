/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The apps-that-request-signatures section (one-signing-rail-for-the-fleet
 * REQ-DDSRC-004): it lists each source app with its count, says so when no
 * app has raised a request, and only shows an error when the load fails.
 */

import axios from '@nextcloud/axios'
import SigningSourcesSettings from './SigningSourcesSettings.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn() },
}))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('@nextcloud/vue', () => ({
	NcLoadingIcon: {},
	NcSettingsSection: {},
}))

const URL = '/index.php/apps/filinq/api/settings/signing-sources'
const flush = () => new Promise((resolve) => setTimeout(resolve, 0))

/**
 * Render the section after its load settles.
 *
 * @return {Promise<object>} The render.
 */
async function render() {
	const page = await renderStubbed(SigningSourcesSettings)
	await flush()
	return page
}

beforeEach(() => {
	jest.clearAllMocks()
})

test('each app that raised requests is listed with its count', async () => {
	axios.get.mockResolvedValue({
		data: {
			sources: [
				{ app: 'dossiq', count: 3 },
				{ app: 'shillinq', count: 2 },
			],
			total: 5,
			truncated: false,
		},
	})

	const page = await render()

	expect(axios.get).toHaveBeenCalledWith(URL)
	expect(page.vm.loading).toBe(false)
	expect(page.vm.failed).toBe(false)
	expect(page.vm.sources).toEqual([
		{ app: 'dossiq', count: 3 },
		{ app: 'shillinq', count: 2 },
	])
	expect(page.html()).toContain('dossiq')
	expect(page.html()).toContain('shillinq')
	expect(page.html()).not.toContain('No app has raised a signing request yet.')
	page.unmount()
})

test('no request is an empty case, not an error', async () => {
	axios.get.mockResolvedValue({ data: { sources: [], total: 0, truncated: false } })

	const page = await render()

	expect(page.vm.failed).toBe(false)
	expect(page.html()).toContain('No app has raised a signing request yet.')
	expect(page.html()).not.toContain('could not be counted')
	page.unmount()
})

test('a failed load is shown as an error and lists nothing', async () => {
	axios.get.mockRejectedValue(new Error('500'))

	const page = await render()

	expect(page.vm.loading).toBe(false)
	expect(page.vm.failed).toBe(true)
	expect(page.vm.sources).toEqual([])
	expect(page.html()).toContain('The signing requests could not be counted.')
	expect(page.html()).not.toContain('No app has raised a signing request yet.')
	page.unmount()
})

test('a truncated count says there are more', async () => {
	axios.get.mockResolvedValue({ data: { sources: [{ app: 'dossiq', count: 100000 }], truncated: true } })

	const page = await render()

	expect(page.vm.truncated).toBe(true)
	expect(page.html()).toContain('Only the first requests were counted; there are more.')
	page.unmount()
})

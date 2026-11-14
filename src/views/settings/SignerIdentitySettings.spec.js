/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The signer identity settings section: it loads the provider and broker
 * fields from its own endpoint (absent values as empty text), saves them
 * back with the provider, shows what the server stored, and reports a
 * refused load or save.
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import SignerIdentitySettings from './SignerIdentitySettings.vue'
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
	NcSelect: {},
	NcSettingsSection: {},
	NcTextField: {},
}))

const URL = '/index.php/apps/filinq/api/settings/signer-identity'
const flush = () => new Promise((resolve) => setTimeout(resolve, 0))

const EMPTY_FORM = {
	issuer: '',
	clientId: '',
	authorizationEndpoint: '',
	tokenEndpoint: '',
	redirectUri: '',
	scopes: '',
	credentialRef: '',
	acrMapping: '',
	evidenceMaxAgeMinutes: '',
	guardianMinimumAssurance: '',
}

/**
 * Render the section after its load settles.
 *
 * @return {Promise<object>} The render.
 */
async function render() {
	const page = await renderStubbed(SignerIdentitySettings)
	await flush()
	return page
}

beforeEach(() => {
	jest.clearAllMocks()
})

test('the stored settings are loaded, numbers as text and absent values as empty', async () => {
	axios.get.mockResolvedValue({
		data: {
			provider: 'oidc-broker',
			issuer: 'https://broker.example',
			clientId: 'filinq',
			scopes: null,
			evidenceMaxAgeMinutes: 15,
			guardianMinimumAssurance: 'substantial',
		},
	})

	const page = await render()

	expect(axios.get).toHaveBeenCalledWith(URL)
	expect(page.vm.loading).toBe(false)
	expect(page.vm.provider).toBe('oidc-broker')
	expect(page.vm.form).toEqual({
		...EMPTY_FORM,
		issuer: 'https://broker.example',
		clientId: 'filinq',
		evidenceMaxAgeMinutes: '15',
		guardianMinimumAssurance: 'substantial',
	})
	page.unmount()
})

test('without a stored provider the Nextcloud login is used', async () => {
	axios.get.mockResolvedValue({ data: {} })

	const page = await render()

	expect(page.vm.provider).toBe('nextcloud-session')
	expect(page.vm.form).toEqual(EMPTY_FORM)
	page.unmount()
})

test('a refused load is reported and the loading state ends', async () => {
	axios.get.mockRejectedValue(new Error('403'))

	const page = await render()

	expect(page.vm.loading).toBe(false)
	expect(showError).toHaveBeenCalledWith('Could not load the signer identity settings')
	page.unmount()
})

test('the provider and assurance selects offer their choices by id', async () => {
	axios.get.mockResolvedValue({ data: {} })

	const page = await render()
	const selects = page.stubs.filter((stub) => stub.name === 'NcSelect')
	const last = selects.slice(-2)

	expect(last[0].attrs.options.map((option) => option.id)).toEqual(['nextcloud-session', 'oidc-broker'])
	expect(last[1].attrs.options.map((option) => option.id)).toEqual(['low', 'substantial', 'high'])
	expect(last[0].attrs.reduce({ id: 'oidc-broker', label: 'x' })).toBe('oidc-broker')
	expect(page.html()).toContain('urn:your-broker:idin')
	page.unmount()
})

test('saving sends the provider with every field and shows what the server stored', async () => {
	axios.get.mockResolvedValue({ data: {} })
	axios.put.mockResolvedValue({ data: { provider: 'oidc-broker', clientId: 'stored', evidenceMaxAgeMinutes: 30 } })
	const page = await render()
	page.vm.provider = 'oidc-broker'
	page.vm.form.clientId = 'typed'

	await page.vm.save()

	expect(axios.put).toHaveBeenCalledWith(URL, { provider: 'oidc-broker', ...EMPTY_FORM, clientId: 'typed' })
	expect(page.vm.form.clientId).toBe('stored')
	expect(page.vm.form.evidenceMaxAgeMinutes).toBe('30')
	expect(page.vm.saving).toBe(false)
	expect(showSuccess).toHaveBeenCalledWith('Signer identity settings saved')
	page.unmount()
})

test('a refused save is reported and the typed values stay', async () => {
	axios.get.mockResolvedValue({ data: {} })
	axios.put.mockRejectedValue({ response: { status: 400 } })
	const page = await render()
	page.vm.form.issuer = 'not a url'

	await page.vm.save()

	expect(page.vm.form.issuer).toBe('not a url')
	expect(page.vm.saving).toBe(false)
	expect(showSuccess).not.toHaveBeenCalled()
	expect(showError).toHaveBeenCalledWith('Check the signer identity settings: one value is not valid')
	page.unmount()
})

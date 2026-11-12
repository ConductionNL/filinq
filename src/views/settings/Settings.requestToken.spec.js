/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The settings page and the anonymiser warning banner send their writes
 * with the Nextcloud request token, so the CSRF check accepts them.
 */

import AnonymiserBackendWarning from '../../components/AnonymiserBackendWarning.vue'
import Settings from './Settings.vue'

jest.mock('@nextcloud/auth', () => ({ getRequestToken: () => 'test-token' }))
jest.mock('@nextcloud/dialogs', () => ({
	showError: () => {},
	showSuccess: () => {},
}))
jest.mock('@conduction/nextcloud-vue', () => ({ CnAdminSettingsShell: {} }))
jest.mock('@nextcloud/vue', () => ({
	NcButton: {},
	NcCheckboxRadioSwitch: {},
	NcLoadingIcon: {},
	NcNoteCard: {},
	NcSelect: {},
	NcSettingsSection: {},
	NcTextField: {},
}))
jest.mock('vue-material-design-icons/AccountSearchOutline.vue', () => ({}))
jest.mock('vue-material-design-icons/FileExportOutline.vue', () => ({}))
jest.mock('vue-material-design-icons/OpenInNew.vue', () => ({}))
jest.mock('vue-material-design-icons/Plus.vue', () => ({}))
jest.mock('vue-material-design-icons/Restart.vue', () => ({}))
jest.mock('./EmailIngestionSettings.vue', () => ({}))
jest.mock('./EntityTypeSelector.vue', () => ({}))
jest.mock('./PageLayoutSettings.vue', () => ({}))
jest.mock('./SignerIdentitySettings.vue', () => ({}))
jest.mock('../../services/conformance.js', () => ({
	fetchValidatorStatus: () => new Promise(() => {}),
}))

/**
 * Replace fetch with a mock that answers every request with an empty 200.
 *
 * @return {jest.Mock} The mock.
 */
function mockFetch() {
	const fetch = jest.fn(() => Promise.resolve({ ok: true, json: () => Promise.resolve({}) }))
	global.fetch = fetch
	return fetch
}

beforeEach(() => {
	global.t = (app, text) => text
})

test('saveConfig sends the request token', () => {
	const fetch = mockFetch()
	const context = {
		sections: { anonymization: { loading: false, selectedRegister: { value: '1' }, selectedSchema: { value: '2' } } },
		saving: false,
	}

	Settings.methods.saveConfig.call(context, 'anonymization')

	const [url, init] = fetch.mock.calls[0]
	expect(url).toBe('/index.php/apps/filinq/api/settings')
	expect(init.method).toBe('POST')
	expect(init.headers.requesttoken).toBe('test-token')
})

test('resetAnonymiserWarning sends the request token', async () => {
	const fetch = mockFetch()
	const context = { anonymiserBackend: { warning: null } }

	await Settings.methods.resetAnonymiserWarning.call(context)

	const [url, init] = fetch.mock.calls[0]
	expect(url).toBe('/index.php/apps/filinq/api/admin/anonymiser-warning/reset')
	expect(init).toEqual({ method: 'POST', headers: { requesttoken: 'test-token' } })
})

test('dismissWarning sends the request token', async () => {
	const fetch = mockFetch()
	const context = { dismissing: false, visible: true, $emit: jest.fn() }

	await AnonymiserBackendWarning.methods.dismissWarning.call(context)

	const [url, init] = fetch.mock.calls[0]
	expect(url).toBe('/index.php/apps/filinq/api/admin/anonymiser-warning/dismiss')
	expect(init).toEqual({ method: 'POST', headers: { requesttoken: 'test-token' } })
	expect(context.$emit).toHaveBeenCalledWith('dismissed')
})

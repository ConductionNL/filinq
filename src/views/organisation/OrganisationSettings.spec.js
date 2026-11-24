/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The organisation settings page: it shows the active organisation's
 * overrides, lets only an editor save, sends empty fields as "inherit",
 * shows an empty state without an active organisation, and reports a
 * refused save.
 *
 * @spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import OrganisationSettings from './OrganisationSettings.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'
import { toForm, toPayload } from '../../services/organisationSettings.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn(), put: jest.fn() },
}))
jest.mock('@nextcloud/dialogs', () => ({ showError: jest.fn(), showSuccess: jest.fn() }))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('@nextcloud/vue', () => ({ NcButton: {}, NcSelect: {}, NcTextArea: {}, NcTextField: {} }))
jest.mock('@conduction/nextcloud-vue', () => ({ CnPageHeader: {}, CnSettingsSection: {} }))

const flush = () => new Promise((resolve) => setTimeout(resolve, 0))

const ORG = { uuid: '11111111-1111-4111-8111-111111111111', name: 'Demostad' }

/**
 * Answer the two reads: the view, then the huisstijl list.
 *
 * @param {object} view The view the server answers.
 */
function answers(view) {
	axios.get.mockImplementation((url) => {
		if (url.includes('/openregister/')) {
			return Promise.resolve({ data: { results: [{ '@self': { id: 'h-1' }, name: 'Demostad blauw' }, { id: 'h-2' }] } })
		}
		return Promise.resolve({ data: view })
	})
}

/**
 * Render the page after its load settles.
 *
 * @return {Promise<object>} The render.
 */
async function render() {
	const page = await renderStubbed(OrganisationSettings)
	await flush()
	await flush()
	return page
}

/**
 * The stubs of one component.
 *
 * @param {object} page The render.
 * @param {string} name The component name.
 * @return {object[]} The stubs.
 */
function stubsOf(page, name) {
	return page.stubs.filter((stub) => stub.name === name)
}

beforeEach(() => {
	jest.clearAllMocks()
})

test('an editor sees the stored overrides and the organisation huisstijl options', async () => {
	answers({
		organisation: ORG,
		canEdit: true,
		settings: { uuid: 's-1', name: 'Demostad', consentPeriodDays: 42, anonymizationProfile: { anonymize: ['PERSON'], keepVisible: ['DATE'] }, defaultHuisstijl: 'h-1', notes: null },
		instanceDefaults: { consentPeriodDays: 28 },
	})

	const page = await render()

	expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/organisation-settings')
	expect(page.vm.organisationLabel).toBe('Demostad')
	expect(page.vm.form).toEqual({ name: 'Demostad', consentPeriodDays: '42', anonymize: ['PERSON'], keepVisible: ['DATE'], defaultHuisstijl: 'h-1', notes: '' })
	expect(page.vm.huisstijlOptions).toEqual([{ id: 'h-1', label: 'Demostad blauw' }, { id: 'h-2', label: 'h-2' }])
	expect(page.vm.anonymizeOptions).not.toContain('DATE')
	expect(page.vm.keepVisibleOptions).not.toContain('PERSON')
	expect(page.html()).not.toContain('organisation-settings-readonly')
	expect(stubsOf(page, 'NcButton')).toHaveLength(1)
	expect(stubsOf(page, 'NcSelect').every((stub) => typeof stub.attrs.inputLabel === 'string' && stub.attrs.inputLabel !== '')).toBe(true)
	page.unmount()
})

test('a member sees the settings read-only, with no save button', async () => {
	answers({ organisation: ORG, canEdit: false, settings: null, instanceDefaults: { consentPeriodDays: 35 } })

	const page = await render()

	expect(page.html()).toContain('organisation-settings-readonly')
	expect(stubsOf(page, 'NcButton')).toHaveLength(0)
	expect(stubsOf(page, 'NcTextField')[0].attrs.disabled).toBe(true)
	expect(page.vm.instanceDays).toBe(35)
	page.unmount()
})

test('without an active organisation the section is empty and no huisstijl is read', async () => {
	answers({ organisation: null, canEdit: false, settings: null, instanceDefaults: { consentPeriodDays: 28 } })

	const page = await render()

	expect(stubsOf(page, 'CnSettingsSection')[0].attrs.empty).toBe(true)
	expect(axios.get).toHaveBeenCalledTimes(1)
	page.unmount()
})

test('a failed load shows the section error', async () => {
	axios.get.mockRejectedValue({ response: { status: 409, data: {} } })

	const page = await render()

	expect(page.vm.loadFailed).toBe(true)
	expect(stubsOf(page, 'CnSettingsSection')[0].attrs.error).toBe(true)
	page.unmount()
})

test('saving sends empty fields as inherit and takes the answer', async () => {
	answers({ organisation: ORG, canEdit: true, settings: null, instanceDefaults: { consentPeriodDays: 28 } })
	axios.put.mockResolvedValue({ data: { organisation: ORG, canEdit: true, settings: { consentPeriodDays: 30 }, instanceDefaults: { consentPeriodDays: 28 } } })
	const page = await render()
	page.vm.form.consentPeriodDays = ' 30 '

	await page.vm.save()

	expect(axios.put).toHaveBeenCalledWith('/index.php/apps/filinq/api/organisation-settings', {
		name: null,
		consentPeriodDays: '30',
		anonymizationProfile: null,
		defaultHuisstijl: null,
		notes: null,
	})
	expect(page.vm.form.consentPeriodDays).toBe('30')
	expect(showSuccess).toHaveBeenCalledWith('Organisation settings saved')
	page.unmount()
})

test('a refused save names who may save', async () => {
	answers({ organisation: ORG, canEdit: true, settings: null, instanceDefaults: { consentPeriodDays: 28 } })
	axios.put.mockRejectedValue({ response: { status: 403, data: { error: 'x' } } })
	const page = await render()

	await page.vm.save()

	expect(page.vm.saving).toBe(false)
	expect(showSuccess).not.toHaveBeenCalled()
	expect(showError).toHaveBeenCalledWith('Only an admin of this organisation can change these settings.')
	page.unmount()
})

test('an invalid value shows the server reason', async () => {
	answers({ organisation: ORG, canEdit: true, settings: null, instanceDefaults: { consentPeriodDays: 28 } })
	axios.put.mockRejectedValue({ response: { status: 400, data: { error: 'The objection period is between 1 and 365 days.' } } })
	const page = await render()

	await page.vm.save()

	expect(showError).toHaveBeenCalledWith('The objection period is between 1 and 365 days.')
	page.unmount()
})

test('the payload keeps a profile with one list and the form reads a stored keep list', () => {
	expect(toPayload({ anonymize: [], keepVisible: ['DATE'], name: ' A ', notes: ' n ', consentPeriodDays: 7, defaultHuisstijl: 'h-1' })).toEqual({
		name: 'A',
		consentPeriodDays: '7',
		anonymizationProfile: { anonymize: [], keepVisible: ['DATE'] },
		defaultHuisstijl: 'h-1',
		notes: 'n',
	})
	expect(toForm({ anonymizationProfile: { anonymize: ['BSN'], keep: ['LOCATION'] }, consentPeriodDays: null }).keepVisible).toEqual(['LOCATION'])
	expect(toForm({ consentPeriodDays: null }).consentPeriodDays).toBe('')
})

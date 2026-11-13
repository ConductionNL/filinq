/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The page layout settings section: it lists the active layouts, fills the
 * form and the version list when one is chosen, creates a new layout under
 * its name or writes the next version of an existing one, and reports a
 * refused load or save with the server's reason.
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import PageLayoutSettings from './PageLayoutSettings.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'
import { formFromLayout, layoutPayload } from '../../services/pageLayoutForm.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn(), post: jest.fn() },
}))
jest.mock('@nextcloud/dialogs', () => ({ showError: jest.fn(), showSuccess: jest.fn() }))
jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars = {}) => text.replace(/\{(\w+)\}/g, (match, key) => (key in vars ? String(vars[key]) : match)),
}))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('@nextcloud/vue', () => ({
	NcButton: {},
	NcCheckboxRadioSwitch: {},
	NcLoadingIcon: {},
	NcSelect: {},
	NcSettingsSection: {},
	NcTextField: {},
}))

const URL = '/index.php/apps/filinq/api/page-layouts'
const flush = () => new Promise((resolve) => setTimeout(resolve, 0))

const BRIEF = { name: 'Brief', layoutVersion: 3, paperSize: 'A5', orientation: 'landscape', header: 'Gemeente' }

/**
 * Render the section after its load settles.
 *
 * @return {Promise<object>} The render.
 */
async function render() {
	const page = await renderStubbed(PageLayoutSettings)
	await flush()
	return page
}

beforeEach(() => {
	jest.clearAllMocks()
})

test('the active layouts are listed after a new-layout choice', async () => {
	axios.get.mockResolvedValue({ data: { results: [BRIEF, { name: 'Besluit', layoutVersion: 1 }] } })

	const page = await render()

	expect(axios.get).toHaveBeenCalledWith(URL)
	expect(page.vm.loading).toBe(false)
	expect(page.vm.options).toEqual([
		{ id: '__new__', label: 'New layout' },
		{ id: 'Brief', label: 'Brief' },
		{ id: 'Besluit', label: 'Besluit' },
	])
	expect(page.vm.current).toEqual({ layoutVersion: 0 })
	page.unmount()
})

test('a response without a result list gives no layouts', async () => {
	axios.get.mockResolvedValue({ data: {} })

	const page = await render()

	expect(page.vm.layouts).toEqual([])
	page.unmount()
})

test('a refused load is reported', async () => {
	axios.get.mockRejectedValue(new Error('500'))

	const page = await render()

	expect(page.vm.loading).toBe(false)
	expect(showError).toHaveBeenCalledWith('The page layouts could not be loaded')
	page.unmount()
})

test('choosing a layout fills the form and loads its versions', async () => {
	axios.get.mockResolvedValueOnce({ data: { results: [BRIEF] } })
	const page = await render()
	axios.get.mockResolvedValueOnce({ data: { results: [{ uuid: 'v3', layoutVersion: 3, active: true }, { uuid: 'v2', layoutVersion: 2 }] } })

	page.vm.selected = 'Brief'
	await page.vm.choose('Brief')
	await flush()

	expect(axios.get).toHaveBeenLastCalledWith(URL, { params: { name: 'Brief' } })
	expect(page.vm.form).toEqual(formFromLayout(BRIEF))
	expect(page.vm.versions).toHaveLength(2)
	expect(page.html()).toContain('Version {version}. Saving makes version {next}. {"version":3,"next":4}')
	expect(page.html()).toContain('(in use)')
	page.unmount()
})

test('a failing version lookup leaves the version list empty', async () => {
	axios.get.mockResolvedValueOnce({ data: { results: [BRIEF] } })
	const page = await render()
	axios.get.mockRejectedValueOnce(new Error('gone'))

	page.vm.selected = 'Brief'
	await page.vm.choose('Brief')

	expect(page.vm.versions).toEqual([])
	expect(page.vm.form.paperSize).toBe('A5')
	page.unmount()
})

test('choosing a new layout resets the form', async () => {
	axios.get.mockResolvedValue({ data: { results: [BRIEF] } })
	const page = await render()
	page.vm.form = formFromLayout(BRIEF)
	page.vm.versions = [{ uuid: 'x', layoutVersion: 1 }]

	await page.vm.choose('__new__')

	expect(page.vm.form).toEqual(formFromLayout({}))
	expect(page.vm.versions).toEqual([])
	page.unmount()
})

test('a new layout can only be saved once it has a name', async () => {
	axios.get.mockResolvedValue({ data: { results: [] } })
	const page = await render()

	expect(page.vm.canSave).toBe(false)
	page.vm.form.name = '   '
	expect(page.vm.canSave).toBe(false)
	page.vm.form.name = 'Brief'
	expect(page.vm.canSave).toBe(true)
	page.vm.form.name = ''
	page.vm.selected = 'Brief'
	expect(page.vm.canSave).toBe(true)
	page.unmount()
})

test('saving a new layout creates it under its trimmed name and selects it', async () => {
	axios.get.mockResolvedValue({ data: { results: [] } })
	axios.post.mockResolvedValue({ data: {} })
	const page = await render()
	page.vm.form.name = '  Brief '
	const fields = layoutPayload(page.vm.form)
	axios.get.mockResolvedValueOnce({ data: { results: [BRIEF] } })
	axios.get.mockResolvedValueOnce({ data: { results: [{ uuid: 'v1', layoutVersion: 1, active: true }] } })

	await page.vm.save()

	expect(axios.post).toHaveBeenCalledWith(URL + '/new', { name: 'Brief', fields })
	expect(page.vm.selected).toBe('Brief')
	expect(page.vm.versions).toEqual([{ uuid: 'v1', layoutVersion: 1, active: true }])
	expect(page.vm.saving).toBe(false)
	expect(showSuccess).toHaveBeenCalledWith('The page layout is saved')
	page.unmount()
})

test('saving an existing layout writes its next version', async () => {
	axios.get.mockResolvedValue({ data: { results: [BRIEF] } })
	axios.post.mockResolvedValue({ data: {} })
	const page = await render()
	page.vm.selected = 'Brief'
	page.vm.form = formFromLayout(BRIEF)
	page.vm.form.margins.top = '30'
	const changes = layoutPayload(page.vm.form)

	await page.vm.save()

	expect(axios.post).toHaveBeenCalledWith(URL, { name: 'Brief', changes })
	expect(changes.margins.top).toBe(30)
	expect(page.vm.form).toEqual(formFromLayout(BRIEF))
	expect(showSuccess).toHaveBeenCalledWith('The page layout is saved')
	page.unmount()
})

test('a refused save reports the server reason, or that none was given', async () => {
	axios.get.mockResolvedValue({ data: { results: [BRIEF] } })
	const page = await render()
	page.vm.selected = 'Brief'

	axios.post.mockRejectedValueOnce({ response: { data: { error: 'The margins do not fit the paper' } } })
	await page.vm.save()
	expect(showError).toHaveBeenLastCalledWith('The page layout could not be saved: The margins do not fit the paper')

	axios.post.mockRejectedValueOnce(new Error('network'))
	await page.vm.save()
	expect(showError).toHaveBeenLastCalledWith('The page layout could not be saved: no reason was given')

	expect(page.vm.saving).toBe(false)
	expect(showSuccess).not.toHaveBeenCalled()
	page.unmount()
})

test('the margins become padding scaled to a 210 mm page', async () => {
	axios.get.mockResolvedValue({ data: { results: [] } })
	const page = await render()
	page.vm.form.margins = { top: 21, right: 42, bottom: 0, left: '' }

	expect(page.vm.pagePadding).toEqual({ padding: '10% 20% 0% 0%' })
	page.unmount()
})

test('the layout select switches the layout through choose', async () => {
	axios.get.mockResolvedValue({ data: { results: [BRIEF] } })
	const page = await render()
	const select = page.stubs.find((stub) => stub.name === 'NcSelect' && stub.attrs.inputLabel === 'Layout')

	expect(select.attrs.reduce({ id: 'Brief' })).toBe('Brief')
	page.vm.form.name = 'typed'
	const listeners = [].concat(select.attrs['onUpdate:modelValue'])
	listeners.forEach((listener) => listener('__new__'))
	await flush()

	expect(page.vm.selected).toBe('__new__')
	expect(page.vm.form.name).toBe('')
	page.unmount()
})

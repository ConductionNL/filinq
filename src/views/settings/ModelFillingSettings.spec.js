/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The model filling settings: without a text model the section says so and
 * the switch cannot be turned on; with one it names the provider, and a
 * profile is saved with its field names (REQ-EMF-001, REQ-EMF-004).
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */

const { nextTick } = require('vue')

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars = {}) =>
		text.replace(/\{(\w+)\}/g, (m, key) => (key in vars ? String(vars[key]) : m)),
}))
jest.mock('../../services/fieldFill.js', () => ({
	fetchFieldFillStatus: jest.fn(),
	listFieldProfiles: jest.fn(),
	saveFieldProfile: jest.fn(),
	setFieldFillEnabled: jest.fn(),
	idOf: (row) => String(row?.uuid || row?.id || ''),
}))

const { renderStubbed } = require('../../../tests/jest/renderGolden.js')
const api = require('../../services/fieldFill.js')
const ModelFillingSettings = require('./ModelFillingSettings.vue').default

let rendered

/**
 * Let pending promises and renders settle.
 *
 * @return {Promise<void>}
 */
async function settle() {
	for (let i = 0; i < 5; i++) {
		await Promise.resolve()
		await nextTick()
	}
}

/**
 * Mount the section over a status and profiles.
 *
 * @param {object} status The status the server answers.
 * @param {Array} [profiles] The profiles.
 * @return {Promise<object>}
 */
async function mount(status, profiles = []) {
	api.fetchFieldFillStatus.mockResolvedValue(status)
	api.listFieldProfiles.mockResolvedValue(profiles)
	rendered = await renderStubbed(ModelFillingSettings, {})
	await settle()
	return rendered
}

const toggle = (wrapper) => wrapper.stubs.filter((s) => s.name === 'NcCheckboxRadioSwitch').find((s) => s.attrs['data-testid'] === 'model-filling-switch').attrs
const testid = (wrapper, id) => wrapper.vm.$el.querySelector(`[data-testid="${id}"]`)

beforeEach(() => {
	jest.clearAllMocks()
})

afterEach(() => {
	rendered?.unmount()
	rendered = null
})

describe('ModelFillingSettings', () => {
	it('says there is no text model and keeps the switch off and disabled', async () => {
		const wrapper = await mount({ enabled: false, provider: null, available: false })

		expect(testid(wrapper, 'model-filling-no-model').textContent).toContain('No text model is available on this server.')
		expect(testid(wrapper, 'model-filling-provider')).toBeNull()
		expect(toggle(wrapper).disabled).toBe(true)
		expect(toggle(wrapper).modelValue).toBe(false)

		await wrapper.vm.toggle(true)
		expect(api.setFieldFillEnabled).not.toHaveBeenCalled()
		expect(testid(wrapper, 'model-filling-no-profiles')).not.toBeNull()
	})

	it('names the provider and switches it on', async () => {
		api.setFieldFillEnabled.mockResolvedValue({ enabled: true, provider: 'Local LLM', available: true })
		const wrapper = await mount({ enabled: false, provider: 'Local LLM', available: false })

		expect(testid(wrapper, 'model-filling-provider').textContent).toContain('read by Local LLM')
		expect(toggle(wrapper).disabled).toBe(false)

		await wrapper.vm.toggle(true)
		await settle()
		expect(api.setFieldFillEnabled).toHaveBeenCalledWith(true)
		expect(toggle(wrapper).modelValue).toBe(true)
	})

	it('saves a profile with its trimmed field names and reloads the list', async () => {
		api.saveFieldProfile.mockResolvedValue({})
		const wrapper = await mount({ enabled: true, provider: 'Local LLM', available: true })
		api.listFieldProfiles.mockResolvedValue([{ id: 'p1', register: 'filinq', schema: 'dossier', fields: ['name', 'description'], active: true }])

		expect(wrapper.vm.canSave).toBe(false)
		wrapper.vm.form = { register: ' filinq ', schema: 'dossier', fields: ' name, , description ', active: true, declaringApp: 'filinq' }
		await settle()
		expect(wrapper.vm.canSave).toBe(true)

		await wrapper.vm.save()
		await settle()
		expect(api.saveFieldProfile).toHaveBeenCalledWith(
			{ declaringApp: 'filinq', register: 'filinq', schema: 'dossier', fields: ['name', 'description'], active: true },
			'',
		)
		expect(wrapper.vm.profiles).toHaveLength(1)
		expect(wrapper.vm.form.fields).toBe('')
		expect(wrapper.vm.$el.textContent).toContain('name, description')
	})

	it('edits a profile in place and shows the refusal for a BSN field', async () => {
		const profile = { id: 'p1', register: 'filinq', schema: 'dossier', fields: ['name'], active: false }
		api.saveFieldProfile.mockRejectedValue({ response: { data: { error: 'A BSN field cannot be filled by a model.' } } })
		const wrapper = await mount({ enabled: true, provider: 'Local LLM', available: true }, [profile])

		wrapper.vm.edit(profile)
		expect(wrapper.vm.editing).toBe('p1')
		expect(wrapper.vm.form.fields).toBe('name')

		wrapper.vm.form.fields = 'name, bsn'
		await wrapper.vm.save()
		await settle()
		expect(api.saveFieldProfile).toHaveBeenCalledWith(expect.objectContaining({ fields: ['name', 'bsn'] }), 'p1')
		expect(wrapper.vm.error).toBe('A BSN field cannot be filled by a model.')

		wrapper.vm.reset()
		expect(wrapper.vm.editing).toBe('')
	})
})

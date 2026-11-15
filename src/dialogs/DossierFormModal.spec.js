/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The new-dossier modal: opening it resets the form to the prefilled name and
 * loads the legal-basis vocabulary, the select-all switch fills or empties the
 * bases, a vocabulary that cannot be read leaves the bases empty, and submit
 * sends the trimmed name or nothing at all.
 */

const { nextTick } = require('vue')

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
}))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))
jest.mock('@nextcloud/axios', () => ({ get: jest.fn() }))
jest.mock('@nextcloud/vue', () => ({
	NcCheckboxRadioSwitch: {},
	NcDialog: {},
	NcNoteCard: {},
	NcSelect: {},
	NcTextArea: {},
	NcTextField: {},
}))
jest.mock('@conduction/nextcloud-vue', () => ({ CnDialogActions: {} }))

const axios = require('@nextcloud/axios')
const { renderStubbed } = require('../../tests/jest/renderGolden.js')
const DossierFormModal = require('./DossierFormModal.vue').default

const BASES_URL = '/apps/openregister/api/objects/filinq/base'

let rendered

/**
 * Let pending promises and renders settle.
 *
 * @return {Promise<void>}
 */
async function settle() {
	for (let i = 0; i < 6; i++) {
		await Promise.resolve()
		await nextTick()
	}
}

/**
 * Mount the modal closed, then open it as a parent would.
 *
 * @param {object} [props] Props that differ.
 * @return {Promise<object>} The rendered modal plus its event spies.
 */
async function openModal(props = {}) {
	const onSubmit = jest.fn()
	const onCancel = jest.fn()
	const onUpdateOpen = jest.fn()
	rendered = await renderStubbed(DossierFormModal, {
		props: {
			open: false,
			onSubmit,
			onCancel,
			'onUpdate:open': onUpdateOpen,
			...props,
		},
	})
	// The watcher fires on a change of `open`, as when the parent opens it.
	rendered.vm.$.props.open = true
	await settle()
	return { ...rendered, onSubmit, onCancel, onUpdateOpen }
}

/**
 * The last-rendered attrs of a stubbed child.
 *
 * @param {object} wrapper The rendered modal.
 * @param {string} name The child component.
 * @return {object|undefined} Its attrs.
 */
function child(wrapper, name) {
	const found = wrapper.stubs.filter((s) => s.name === name)
	return found[found.length - 1]?.attrs
}

beforeEach(() => {
	axios.get.mockReset()
})

afterEach(() => {
	rendered?.unmount()
	rendered = null
})

describe('DossierFormModal', () => {
	it('loads the bases on open and offers their slugs', async () => {
		axios.get.mockResolvedValue({
			data: {
				results: [
					{ '@self': { slug: 'woo-5-1' }, name: 'Woo 5.1' },
					{ slug: 'avg-6', name: 'AVG 6' },
				],
			},
		})
		const wrapper = await openModal({ prefillName: 'Verzoek 14' })

		expect(axios.get).toHaveBeenCalledTimes(1)
		expect(axios.get).toHaveBeenCalledWith(BASES_URL)
		expect(wrapper.vm.baseOptions).toEqual([
			{ slug: 'woo-5-1', label: 'Woo 5.1' },
			{ slug: 'avg-6', label: 'AVG 6' },
		])
		expect(child(wrapper, 'NcSelect').options).toEqual(['woo-5-1', 'avg-6'])
		expect(child(wrapper, 'NcSelect').loading).toBe(false)
		expect(wrapper.vm.form).toEqual({ name: 'Verzoek 14', description: '', bases: [] })
	})

	it('accepts a bare array of bases', async () => {
		axios.get.mockResolvedValue({ data: [{ slug: 'woo-5-2' }] })
		const wrapper = await openModal()

		expect(wrapper.vm.baseOptions).toEqual([{ slug: 'woo-5-2', label: 'woo-5-2' }])
	})

	it('leaves the bases empty when the vocabulary cannot be read', async () => {
		axios.get.mockRejectedValue(new Error('offline'))
		const wrapper = await openModal()

		expect(wrapper.vm.baseOptions).toEqual([])
		expect(wrapper.vm.loadingBases).toBe(false)
	})

	it('selects every basis with the switch and empties them when it is turned off', async () => {
		axios.get.mockResolvedValue({ data: [{ slug: 'a' }, { slug: 'b' }] })
		const wrapper = await openModal({ showSelectAllBases: true })

		child(wrapper, 'NcCheckboxRadioSwitch')['onUpdate:modelValue'](true)
		await settle()
		expect(wrapper.vm.form.bases).toEqual(['a', 'b'])

		child(wrapper, 'NcCheckboxRadioSwitch')['onUpdate:modelValue'](false)
		await settle()
		expect(wrapper.vm.form.bases).toEqual([])
	})

	it('hides the select-all switch unless asked for', async () => {
		axios.get.mockResolvedValue({ data: [] })
		const wrapper = await openModal()

		expect(child(wrapper, 'NcCheckboxRadioSwitch')).toBeUndefined()
	})

	it('submits the trimmed name with the typed description and picked bases', async () => {
		axios.get.mockResolvedValue({ data: [{ slug: 'a' }] })
		const wrapper = await openModal()

		const fields = wrapper.stubs.filter((s) => s.name === 'NcTextField')
		fields[fields.length - 1].attrs['onUpdate:modelValue'](' Woo 14 ')
		const areas = wrapper.stubs.filter((s) => s.name === 'NcTextArea')
		areas[areas.length - 1].attrs['onUpdate:modelValue']('Voor de krant')
		child(wrapper, 'NcSelect')['onUpdate:modelValue'](['a'])
		await settle()

		expect(child(wrapper, 'CnDialogActions').canSubmit).toBe(true)
		child(wrapper, 'CnDialogActions').onSubmit()

		expect(wrapper.onSubmit).toHaveBeenCalledTimes(1)
		expect(wrapper.onSubmit).toHaveBeenCalledWith({
			name: 'Woo 14',
			description: 'Voor de krant',
			bases: ['a'],
		})
	})

	it('submits nothing while the name is blank', async () => {
		axios.get.mockResolvedValue({ data: [] })
		const wrapper = await openModal({ prefillName: '   ' })

		expect(child(wrapper, 'CnDialogActions').canSubmit).toBe(false)
		child(wrapper, 'CnDialogActions').onSubmit()

		expect(wrapper.onSubmit).not.toHaveBeenCalled()
	})

	it('cancel emits cancel and closes the modal', async () => {
		axios.get.mockResolvedValue({ data: [] })
		const wrapper = await openModal()

		child(wrapper, 'CnDialogActions').onCancel()

		expect(wrapper.onCancel).toHaveBeenCalledTimes(1)
		expect(wrapper.onUpdateOpen).toHaveBeenCalledWith(false)
	})
})

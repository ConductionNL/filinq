/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The upload dialog before anonymisation: one file shows its name, several
 * ask for a dossier name; the legal-grounds switch, Continue and Cancel pass
 * their choice to the parent, and submitting locks every control.
 */

const { nextTick } = require('vue')

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
}))

const { renderStubbed } = require('../../tests/jest/renderGolden.js')
const AnonymizationUploadDialog = require('./AnonymizationUploadDialog.vue').default

let rendered

/**
 * Mount the dialog, with its own events recorded.
 *
 * @param {object} [props] The props.
 * @return {Promise<object>} The rendered dialog plus its event spies.
 */
async function mountDialog(props = {}) {
	const spies = {
		onConfirm: jest.fn(),
		onCancel: jest.fn(),
		'onUpdate:dossierName': jest.fn(),
		'onUpdate:grondslagen': jest.fn(),
	}
	rendered = await renderStubbed(AnonymizationUploadDialog, { props: { ...spies, ...props } })
	return { ...rendered, ...spies }
}

const all = (wrapper, name) => wrapper.stubs.filter((s) => s.name === name).map((s) => s.attrs)
const notes = (wrapper) => [...wrapper.vm.$el.querySelectorAll('x-ncnotecard')].map((n) => n.textContent.trim())
const buttons = (wrapper) => all(wrapper, 'NcButton')

afterEach(() => {
	rendered?.unmount()
	rendered = null
})

describe('AnonymizationUploadDialog', () => {
	it('shows the file name for a single file and asks for no dossier name', async () => {
		const wrapper = await mountDialog({ singleFile: true, fileName: 'besluit.pdf' })

		expect(all(wrapper, 'NcDialog')[0].name).toBe('Anonymize document')
		expect(wrapper.vm.$el.querySelector('.single-file__name').textContent).toBe('besluit.pdf')
		expect(all(wrapper, 'NcTextField')).toHaveLength(0)
		expect(notes(wrapper)).toHaveLength(1)
	})

	it('shows the refusal next to a single file', async () => {
		const wrapper = await mountDialog({ singleFile: true, fileName: 'a.pdf', error: 'The file is too large.' })
		expect(notes(wrapper)[0]).toBe('The file is too large.')
	})

	it('asks for a dossier name for several files and passes what is typed to the parent', async () => {
		const wrapper = await mountDialog({ dossierName: 'Buurt', error: 'Name taken' })

		expect(all(wrapper, 'NcDialog')[0].name).toBe('Create dossier')
		const field = all(wrapper, 'NcTextField')[0]
		expect(field).toMatchObject({ label: 'Dossier name', modelValue: 'Buurt', error: true, helperText: 'Name taken', disabled: false })
		expect(notes(wrapper)[0]).toContain('You uploaded multiple documents.')

		field['onUpdate:modelValue']('Buurt 2026')
		expect(wrapper['onUpdate:dossierName']).toHaveBeenCalledWith('Buurt 2026')

		field.onKeyup({ key: 'Enter' })
		expect(wrapper.onConfirm).toHaveBeenCalledTimes(1)
	})

	it('passes the legal grounds switch to the parent', async () => {
		const wrapper = await mountDialog({ grondslagen: false })

		const toggle = all(wrapper, 'NcCheckboxRadioSwitch')[0]
		expect(toggle.modelValue).toBe(false)
		toggle['onUpdate:modelValue'](true)
		expect(wrapper['onUpdate:grondslagen']).toHaveBeenCalledWith(true)
	})

	it('continues and cancels through its buttons and the dialog close', async () => {
		const wrapper = await mountDialog()
		const [cancel, proceed] = buttons(wrapper)

		proceed.onClick()
		cancel.onClick()
		all(wrapper, 'NcDialog')[0].onClosing()

		expect(wrapper.onConfirm).toHaveBeenCalledTimes(1)
		expect(wrapper.onCancel).toHaveBeenCalledTimes(2)
	})

	it('locks every control and shows a spinner while submitting', async () => {
		const wrapper = await mountDialog({ submitting: true })

		expect(all(wrapper, 'NcDialog')[0].noClose).toBe(true)
		expect(all(wrapper, 'NcTextField')[0].disabled).toBe(true)
		expect(all(wrapper, 'NcCheckboxRadioSwitch')[0].disabled).toBe(true)
		expect(buttons(wrapper).map((b) => b.disabled)).toEqual([true, true])
		expect(all(wrapper, 'NcLoadingIcon')).toHaveLength(1)
	})

	it('focuses the dossier name field when it opens', async () => {
		const focus = jest.fn()
		const wrapper = await mountDialog()
		wrapper.vm.$refs.dossierInput.focus = focus
		wrapper.vm.$options.mounted.call(wrapper.vm)
		await nextTick()
		expect(focus).toHaveBeenCalledTimes(1)
	})
})

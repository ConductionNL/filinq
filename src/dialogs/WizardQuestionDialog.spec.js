/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The wizard question dialog: the fields it shows per answer type and
 * condition, the problem it names before a question can be kept, and the
 * question it hands back on Keep question.
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-1
 */

const { nextTick } = require('vue')

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
}))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))
jest.mock('@nextcloud/axios', () => ({ get: jest.fn(), put: jest.fn() }))
jest.mock('@conduction/nextcloud-vue', () => ({ CnDialogActions: {} }))

const { renderStubbed } = require('../../tests/jest/renderGolden.js')
const WizardQuestionDialog = require('./WizardQuestionDialog.vue').default

let rendered

/**
 * Mount the dialog, with its own events recorded.
 *
 * @param {object} [props] The props.
 * @return {Promise<object>} The rendered dialog plus its event spies.
 */
async function mountDialog(props = {}) {
	const onSave = jest.fn()
	const onClose = jest.fn()
	rendered = await renderStubbed(WizardQuestionDialog, {
		props: { onSave, onClose, ...props },
	})
	return { ...rendered, onSave, onClose }
}

/**
 * The stubbed children of one component, in the order they were created.
 *
 * @param {object} wrapper The rendered dialog.
 * @param {string} name The component.
 * @return {Array<object>} Their attrs.
 */
const all = (wrapper, name) => wrapper.stubs.filter((s) => s.name === name).map((s) => s.attrs)
function labels (wrapper, name) {
  return [...wrapper.vm.$el.querySelectorAll('x-' + name.toLowerCase())].map((el) =>
		(name === 'NcSelect' ? el.getAttribute('inputlabel') : el.getAttribute('label')))
}
const actions = (wrapper) => all(wrapper, 'CnDialogActions')[0]
const problem = (wrapper) => wrapper.vm.$el.querySelector('x-ncnotecard')?.textContent.trim() ?? ''

/**
 * Fill form fields and let the dialog re-render.
 *
 * @param {object} wrapper The rendered dialog.
 * @param {object} values The form values.
 * @return {Promise<void>}
 */
async function fill(wrapper, values) {
	Object.assign(wrapper.vm.form, values)
	await nextTick()
	await nextTick()
}

/**
 * The latest stubbed field with a label, as the clerk would find it.
 *
 * @param {object} wrapper The rendered dialog.
 * @param {string} label The field label.
 * @return {object} Its attrs.
 */
function field (wrapper, label) {
  return wrapper.stubs.filter((s) => s.attrs.label === label || s.attrs.inputLabel === label).pop().attrs
}

/**
 * Type into a field through its update listener.
 *
 * @param {object} wrapper The rendered dialog.
 * @param {string} label The field label.
 * @param {*} value The value.
 * @return {Promise<void>}
 */
async function type(wrapper, label, value) {
	field(wrapper, label)['onUpdate:modelValue'](value)
	await nextTick()
	await nextTick()
}

afterEach(() => {
	rendered?.unmount()
	rendered = null
})

describe('WizardQuestionDialog', () => {
	it('opens a new text question and says the question is still to be written', async () => {
		const wrapper = await mountDialog()

		expect(all(wrapper, 'NcDialog')[0].name).toBe('New question')
		expect(labels(wrapper, 'NcTextField')).toEqual(['Question', 'Key', 'Data path in the template (optional)'])
		expect(labels(wrapper, 'NcTextArea')).toEqual(['Help text (optional)'])
		expect(all(wrapper, 'NcSelect')[0].options.map((o) => o.id)).toEqual(['text', 'choice', 'date', 'registerObject'])
		expect(problem(wrapper)).toBe('Write the question.')
		expect(actions(wrapper).canSubmit).toBe(false)
	})

	it('names a bad key and a key another question already uses', async () => {
		const wrapper = await mountDialog({ usedKeys: ['applicant'] })

		await fill(wrapper, { label: 'Who applies?', key: '1abc' })
		expect(problem(wrapper)).toBe('The key needs letters, digits, - or _, starting with a letter.')

		await fill(wrapper, { key: 'applicant' })
		expect(problem(wrapper)).toBe('Another question already uses this key.')

		await fill(wrapper, { key: 'applicantName' })
		expect(problem(wrapper)).toBe('')
		expect(actions(wrapper).canSubmit).toBe(true)
	})

	it('asks for choices on a choice question and hands them back parsed', async () => {
		const wrapper = await mountDialog()
		await fill(wrapper, { label: ' Decision ', key: 'decision', type: 'choice', required: true })

		expect(labels(wrapper, 'NcTextArea')).toContain('Choices, one per line as value=label')
		expect(problem(wrapper)).toBe('Add at least one choice.')

		await fill(wrapper, { choicesText: 'granted=Granted\nrefused' })
		actions(wrapper).onSubmit()

		expect(wrapper.onSave).toHaveBeenCalledWith({
			key: 'decision',
			label: 'Decision',
			type: 'choice',
			required: true,
			choices: [
				{ value: 'granted', label: 'Granted' },
				{ value: 'refused', label: 'refused' },
			],
		})
	})

	it('asks for a register and schema instead of a data path on a register object question', async () => {
		const wrapper = await mountDialog()
		await fill(wrapper, { label: 'Which case?', key: 'case', type: 'registerObject' })

		expect(labels(wrapper, 'NcTextField')).toEqual(['Question', 'Key', 'Register', 'Schema'])
		expect(problem(wrapper)).toBe('Name the register and the schema to pick from.')

		await fill(wrapper, { register: 'zaken', schema: 'zaak' })
		actions(wrapper).onSubmit()
		expect(wrapper.onSave).toHaveBeenCalledWith({
			key: 'case',
			label: 'Which case?',
			type: 'registerObject',
			required: false,
			register: 'zaken',
			schema: 'zaak',
		})
	})

	it('refuses a data path that is not names joined by dots', async () => {
		const wrapper = await mountDialog()
		await fill(wrapper, { label: 'Name', key: 'name', mapsTo: 'applicant..name' })

		expect(problem(wrapper)).toBe('The data path needs names joined by dots, such as applicant.name.')
	})

	it('offers the earlier questions as a condition and the compare field unless the operator is answered', async () => {
		const wrapper = await mountDialog({ earlier: [{ key: 'decision', label: 'Decision' }] })

		expect(all(wrapper, 'NcSelect')[1].options).toEqual([{ id: 'decision', label: 'Decision' }])
		expect(labels(wrapper, 'NcSelect')).toHaveLength(2)

		await fill(wrapper, { label: 'Why?', key: 'why', conditionKey: 'decision' })
		expect(labels(wrapper, 'NcSelect')).toEqual(['Answer type', 'Ask only when this earlier question…', '…is'])
		expect(all(wrapper, 'NcSelect')[2].options.map((o) => o.id)).toEqual(['equals', 'notEquals', 'answered'])
		expect(labels(wrapper, 'NcTextField')).toContain('Answer to compare with')

		await fill(wrapper, { conditionValue: 'refused' })
		actions(wrapper).onSubmit()
		expect(wrapper.onSave.mock.calls[0][0].condition).toEqual({ questionKey: 'decision', operator: 'equals', value: 'refused' })

		await fill(wrapper, { conditionOperator: 'answered' })
		expect(labels(wrapper, 'NcTextField')).not.toContain('Answer to compare with')
		actions(wrapper).onSubmit()
		expect(wrapper.onSave.mock.calls[1][0].condition).toEqual({ questionKey: 'decision', operator: 'answered' })
	})

	it('edits an existing question with its values filled in', async () => {
		const wrapper = await mountDialog({
			question: {
				key: 'applicant',
				label: 'Applicant',
				helpText: 'Full name',
				type: 'text',
				mapsTo: 'applicant.name',
				condition: { questionKey: 'kind', operator: 'notEquals', value: 'anon' },
			},
		})

		expect(all(wrapper, 'NcDialog')[0].name).toBe('Edit question')
		expect(wrapper.vm.form).toMatchObject({ key: 'applicant', helpText: 'Full name', mapsTo: 'applicant.name', conditionKey: 'kind', conditionOperator: 'notEquals' })
		actions(wrapper).onSubmit()
		expect(wrapper.onSave).toHaveBeenCalledWith({
			key: 'applicant',
			label: 'Applicant',
			type: 'text',
			required: false,
			helpText: 'Full name',
			mapsTo: 'applicant.name',
			condition: { questionKey: 'kind', operator: 'notEquals', value: 'anon' },
		})
	})

	it('closes from the actions and from the dialog', async () => {
		const wrapper = await mountDialog()
		actions(wrapper).onCancel()
		all(wrapper, 'NcDialog')[0].onClosing()
		expect(wrapper.onClose).toHaveBeenCalledTimes(2)
		expect(wrapper.onSave).not.toHaveBeenCalled()
	})

	it('takes every answer the clerk types through the fields themselves', async () => {
		const wrapper = await mountDialog({ earlier: [{ key: 'kind', label: 'Kind' }] })

		await type(wrapper, 'Question', 'Pick one')
		await type(wrapper, 'Key', 'pick')
		await type(wrapper, 'Help text (optional)', 'Only one')
		expect(field(wrapper, 'Answer type').reduce({ id: 'choice', label: 'Choice' })).toBe('choice')
		await type(wrapper, 'Answer type', 'choice')
		await type(wrapper, 'Choices, one per line as value=label', 'a=A')
		await type(wrapper, 'Data path in the template (optional)', 'answers.pick')
		wrapper.stubs.find((s) => s.name === 'NcCheckboxRadioSwitch').attrs['onUpdate:modelValue'](true)
		expect(field(wrapper, 'Ask only when this earlier question…').reduce({ id: 'kind' })).toBe('kind')
		await type(wrapper, 'Ask only when this earlier question…', 'kind')
		expect(field(wrapper, '…is').reduce({ id: 'notEquals' })).toBe('notEquals')
		await type(wrapper, '…is', 'notEquals')
		await type(wrapper, 'Answer to compare with', 'x')

		actions(wrapper).onSubmit()
		expect(wrapper.onSave).toHaveBeenCalledWith({
			key: 'pick',
			label: 'Pick one',
			type: 'choice',
			required: true,
			helpText: 'Only one',
			choices: [{ value: 'a', label: 'A' }],
			mapsTo: 'answers.pick',
			condition: { questionKey: 'kind', operator: 'notEquals', value: 'x' },
		})

		await type(wrapper, 'Answer type', 'registerObject')
		await type(wrapper, 'Register', 'zaken')
		await type(wrapper, 'Schema', 'zaak')
		expect(wrapper.vm.form).toMatchObject({ register: 'zaken', schema: 'zaak' })
	})
})

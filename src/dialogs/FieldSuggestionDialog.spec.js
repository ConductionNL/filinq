/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The field suggestion dialog: each suggested field shows the value the
 * record holds now, the suggestion and where it came from, and Save sends a
 * decision per field only once the clerk decided every one.
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */

const { nextTick } = require('vue')

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars = {}) =>
		text.replace(/\{(\w+)\}/g, (m, key) => (key in vars ? String(vars[key]) : m)),
}))
jest.mock('@conduction/nextcloud-vue', () => ({ CnDialogActions: {} }))

const { renderStubbed } = require('../../tests/jest/renderGolden.js')
const FieldSuggestionDialog = require('./FieldSuggestionDialog.vue').default

const SUGGESTION = {
	provider: 'Local LLM',
	truncated: false,
	currentValues: { applicant: '', amount: 100, startDate: '2026-01-01' },
	suggestions: {
		applicant: { value: 'Bakkerij Jansen', foundInText: true, passage: '... aanvrager Bakkerij Jansen te ...' },
		amount: { value: 2500, foundInText: true, passage: 'bedrag van EUR 2500' },
		startDate: { value: '2026-03-01', foundInText: false, passage: null },
	},
}

let rendered

/**
 * Let renders settle.
 *
 * @return {Promise<void>}
 */
async function settle() {
	for (let i = 0; i < 3; i++) {
		await nextTick()
	}
}

/**
 * Mount the dialog with its events recorded.
 *
 * @param {object} [props] Props that differ.
 * @return {Promise<object>}
 */
async function mountDialog(props = {}) {
	const onConfirm = jest.fn()
	const onCancel = jest.fn()
	rendered = await renderStubbed(FieldSuggestionDialog, { props: { suggestion: SUGGESTION, onConfirm, onCancel, ...props } })
	return { ...rendered, onConfirm, onCancel }
}

const actions = (wrapper) => wrapper.stubs.filter((s) => s.name === 'CnDialogActions').pop().attrs
const field = (wrapper, name) => wrapper.vm.$el.querySelector(`[data-testid="field-suggestion-${name}"]`)

/**
 * Decide a field the way the radio does.
 *
 * @param {object} wrapper The dialog.
 * @param {string} name The field.
 * @param {string} decision The decision.
 * @return {Promise<void>}
 */
async function decide(wrapper, name, decision) {
	wrapper.vm.choose(name, decision)
	await settle()
}

afterEach(() => {
	rendered?.unmount()
	rendered = null
})

describe('FieldSuggestionDialog', () => {
	it('shows each field with the value now, the suggestion and its passage', async () => {
		const wrapper = await mountDialog()

		expect(wrapper.vm.fields).toEqual([
			{ name: 'applicant', value: 'Bakkerij Jansen', current: '', passage: '... aanvrager Bakkerij Jansen te ...' },
			{ name: 'amount', value: '2500', current: '100', passage: 'bedrag van EUR 2500' },
			{ name: 'startDate', value: '2026-03-01', current: '2026-01-01', passage: '' },
		])
		expect(field(wrapper, 'amount').textContent).toContain('Now: 100')
		expect(field(wrapper, 'amount').textContent).toContain('Suggested: 2500')
		expect(field(wrapper, 'startDate').textContent).toContain('Not found in the document text')
		expect(field(wrapper, 'applicant').textContent).not.toContain('Now:')
		expect(wrapper.vm.$el.textContent).toContain('Read by Local LLM.')
	})

	it('saves only once every field is decided, and sends the decision per field', async () => {
		const wrapper = await mountDialog()
		expect(actions(wrapper).canSubmit).toBe(false)

		await decide(wrapper, 'applicant', 'accept')
		await decide(wrapper, 'amount', 'accept')
		expect(actions(wrapper).canSubmit).toBe(false)
		await decide(wrapper, 'startDate', 'reject')
		expect(actions(wrapper).canSubmit).toBe(true)

		actions(wrapper).onSubmit()
		expect(wrapper.onConfirm).toHaveBeenCalledWith({
			applicant: { decision: 'accept' },
			amount: { decision: 'accept' },
			startDate: { decision: 'reject' },
		})
	})

	it('starts a change from the suggestion and needs a value for it', async () => {
		const wrapper = await mountDialog()
		await decide(wrapper, 'applicant', 'change')
		await decide(wrapper, 'amount', 'reject')
		await decide(wrapper, 'startDate', 'reject')

		expect(wrapper.vm.changed.applicant).toBe('Bakkerij Jansen')
		expect(actions(wrapper).canSubmit).toBe(true)

		wrapper.vm.changed = { applicant: '  ' }
		await settle()
		expect(actions(wrapper).canSubmit).toBe(false)
		wrapper.vm.submit()
		expect(wrapper.onConfirm).not.toHaveBeenCalled()

		wrapper.vm.changed = { applicant: ' Bakkerij Jansen B.V. ' }
		await settle()
		actions(wrapper).onSubmit()
		expect(wrapper.onConfirm).toHaveBeenCalledWith({
			applicant: { decision: 'change', value: 'Bakkerij Jansen B.V.' },
			amount: { decision: 'reject' },
			startDate: { decision: 'reject' },
		})
	})

	it('says when nothing was found, when the text was cut, and the refusal', async () => {
		const wrapper = await mountDialog({ suggestion: { suggestions: {}, truncated: true }, error: 'You may not change this record.' })

		expect(wrapper.vm.$el.querySelector('[data-testid="field-suggestion-none"]')).not.toBeNull()
		expect(wrapper.vm.$el.textContent).toContain('The document is long')
		expect(wrapper.vm.$el.textContent).toContain('You may not change this record.')
		expect(wrapper.vm.$el.textContent).toContain('Read by the text model.')
		expect(actions(wrapper).canSubmit).toBe(false)

		actions(wrapper).onCancel()
		expect(wrapper.onCancel).toHaveBeenCalled()
	})
})

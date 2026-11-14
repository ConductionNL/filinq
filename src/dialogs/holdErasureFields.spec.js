/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The legal-hold and erasure-request dialogs, driven through their fields:
 * what the user types into each field is what the create call receives
 * (trimmed, references split), Create stays disabled until the required
 * fields are filled, and a refusal is shown while a success emits `created`.
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */

const { nextTick } = require('vue')

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
}))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))
jest.mock('@nextcloud/axios', () => ({ get: jest.fn(), post: jest.fn() }))
jest.mock('@nextcloud/vue', () => ({
	NcDialog: {},
	NcNoteCard: {},
	NcSelect: {},
	NcTextArea: {},
	NcTextField: {},
}))
jest.mock('@conduction/nextcloud-vue', () => ({ CnDialogActions: {} }))
jest.mock('../services/legalHolds.js', () => ({
	...jest.requireActual('../services/legalHolds.js'),
	createCase: jest.fn(),
}))
jest.mock('../services/subjectErasures.js', () => ({
	...jest.requireActual('../services/subjectErasures.js'),
	createRequest: jest.fn(),
}))

const { renderStubbed } = require('../../tests/jest/renderGolden.js')
const { createCase } = require('../services/legalHolds.js')
const { createRequest } = require('../services/subjectErasures.js')
const NewLegalHoldDialog = require('./NewLegalHoldDialog.vue').default
const NewSubjectErasureDialog = require('./NewSubjectErasureDialog.vue').default

let rendered

/**
 * Let pending promises and renders settle.
 *
 * @return {Promise<void>}
 */
async function settle() {
	for (let i = 0; i < 4; i++) {
		await Promise.resolve()
		await nextTick()
	}
}

/**
 * Mount a dialog with its emitted events recorded.
 *
 * @param {object} component The dialog.
 * @return {Promise<object>} The rendered dialog plus its event spies.
 */
async function mountDialog(component) {
	const onCreated = jest.fn()
	const onClose = jest.fn()
	rendered = await renderStubbed(component, { props: { onCreated, onClose } })
	return { ...rendered, onCreated, onClose }
}

/**
 * The last-rendered attrs of the field stub with this label.
 *
 * @param {object} wrapper The rendered dialog.
 * @param {string} label The field's label (or input label for a select).
 * @return {object} Its attrs.
 */
function field(wrapper, label) {
	const found = wrapper.stubs.filter(
		(s) => s.attrs.label === label || s.attrs.inputLabel === label,
	)
	if (found.length === 0) {
		throw new Error('No field labelled ' + label)
	}
	return found[found.length - 1].attrs
}

/**
 * Type a value into a field, as its v-model would.
 *
 * @param {object} wrapper The rendered dialog.
 * @param {string} label The field's label.
 * @param {*} value The new value.
 * @return {Promise<void>}
 */
async function type(wrapper, label, value) {
	field(wrapper, label)['onUpdate:modelValue'](value)
	await settle()
}

/**
 * The last-rendered attrs of the dialog's action buttons.
 *
 * @param {object} wrapper The rendered dialog.
 * @return {object} Its attrs.
 */
function actions(wrapper) {
	const found = wrapper.stubs.filter((s) => s.name === 'CnDialogActions')
	return found[found.length - 1].attrs
}

beforeEach(() => {
	createCase.mockReset()
	createRequest.mockReset()
})

afterEach(() => {
	rendered?.unmount()
	rendered = null
})

describe('NewLegalHoldDialog fields', () => {
	/**
	 * Fill the three required parts of a hold.
	 *
	 * @param {object} wrapper The rendered dialog.
	 * @return {Promise<void>}
	 */
	async function fillRequired(wrapper) {
		await type(wrapper, 'Name', ' Onderzoek ')
		await type(wrapper, 'Why are the records frozen?', ' Rechtszaak ')
		await type(wrapper, 'Document record ids, one per line', 'doc-1')
	}

	it('keeps Create disabled until name, reason and a record are given', async () => {
		const wrapper = await mountDialog(NewLegalHoldDialog)
		expect(actions(wrapper).canSubmit).toBe(false)

		await type(wrapper, 'Name', 'Onderzoek')
		await type(wrapper, 'Why are the records frozen?', 'Rechtszaak')
		expect(actions(wrapper).canSubmit).toBe(false)

		await type(wrapper, 'Dossier ids, one per line', 'dos-1')
		expect(actions(wrapper).canSubmit).toBe(true)

		await type(wrapper, 'Name', '   ')
		expect(actions(wrapper).canSubmit).toBe(false)
	})

	it('sends every typed field, trimmed, with the references split and deduplicated', async () => {
		createCase.mockResolvedValue({ ok: true, data: { id: 'h-1' } })
		const wrapper = await mountDialog(NewLegalHoldDialog)

		await type(wrapper, 'Name', ' Onderzoek ')
		await type(wrapper, 'Matter type', { id: 'audit', label: 'Audit' })
		await type(wrapper, 'Why are the records frozen?', ' Rechtszaak ')
		await type(wrapper, 'Case reference (optional)', ' Z-1 ')
		await type(wrapper, 'Custodian user id (optional)', ' jan ')
		await type(wrapper, 'Document record ids, one per line', 'doc-1\ndoc-2, doc-1')
		await type(wrapper, 'Dossier ids, one per line', 'dos-1;dos-2')
		await actions(wrapper).onSubmit()
		await settle()

		expect(createCase).toHaveBeenCalledTimes(1)
		expect(createCase).toHaveBeenCalledWith({
			name: 'Onderzoek',
			holdType: 'audit',
			reason: 'Rechtszaak',
			caseReference: 'Z-1',
			custodian: 'jan',
			scopeDocuments: ['doc-1', 'doc-2'],
			scopeDossiers: ['dos-1', 'dos-2'],
		})
		expect(wrapper.onCreated).toHaveBeenCalledWith({ id: 'h-1' })
	})

	it('defaults the matter type to litigation', async () => {
		createCase.mockResolvedValue({ ok: true, data: { id: 'h-2' } })
		const wrapper = await mountDialog(NewLegalHoldDialog)

		expect(field(wrapper, 'Matter type').modelValue).toEqual({
			id: 'litigation',
			label: 'Litigation',
		})
		await fillRequired(wrapper)
		await actions(wrapper).onSubmit()
		await settle()

		expect(createCase.mock.calls[0][0].holdType).toBe('litigation')
	})

	it('shows the refusal and emits nothing when the server says no', async () => {
		createCase.mockResolvedValue({ ok: false, error: 'No rights' })
		const wrapper = await mountDialog(NewLegalHoldDialog)

		await fillRequired(wrapper)
		await actions(wrapper).onSubmit()
		await settle()

		expect(wrapper.onCreated).not.toHaveBeenCalled()
		expect(wrapper.vm.$el.textContent).toContain('No rights')
		expect(actions(wrapper).saving).toBe(false)
	})
})

describe('NewSubjectErasureDialog fields', () => {
	it('keeps Record request disabled until the person and the ground are given', async () => {
		const wrapper = await mountDialog(NewSubjectErasureDialog)
		expect(actions(wrapper).canSubmit).toBe(false)

		await type(wrapper, 'Person to remove', 'Jan Jansen')
		expect(actions(wrapper).canSubmit).toBe(false)

		await type(wrapper, 'Legal ground', '  ')
		expect(actions(wrapper).canSubmit).toBe(false)

		await type(wrapper, 'Legal ground', 'AVG 17')
		expect(actions(wrapper).canSubmit).toBe(true)
	})

	it('sends the typed person, identifiers per line, ground and due date', async () => {
		createRequest.mockResolvedValue({ ok: true, data: { id: 'e-1' } })
		const wrapper = await mountDialog(NewSubjectErasureDialog)

		await type(wrapper, 'Person to remove', ' Jan Jansen ')
		await type(
			wrapper,
			'Other ways the documents name this person, one per line (email address, phone number, other spellings)',
			' jan@example.org \n\n J. Jansen ',
		)
		await type(wrapper, 'Legal ground', ' AVG 17 ')
		await type(wrapper, 'Answer due (optional, default one month)', '2026-12-01')
		await actions(wrapper).onSubmit()
		await settle()

		expect(createRequest).toHaveBeenCalledTimes(1)
		expect(createRequest).toHaveBeenCalledWith({
			subject: 'Jan Jansen',
			identifiers: ['jan@example.org', 'J. Jansen'],
			ground: 'AVG 17',
			dueAt: '2026-12-01',
		})
		expect(wrapper.onCreated).toHaveBeenCalledWith({ id: 'e-1' })
	})

	it('shows the refusal and emits nothing when the server says no', async () => {
		createRequest.mockResolvedValue({ ok: false, error: 'Already requested' })
		const wrapper = await mountDialog(NewSubjectErasureDialog)

		await type(wrapper, 'Person to remove', 'Jan Jansen')
		await type(wrapper, 'Legal ground', 'AVG 17')
		await actions(wrapper).onSubmit()
		await settle()

		expect(wrapper.onCreated).not.toHaveBeenCalled()
		expect(wrapper.vm.$el.textContent).toContain('Already requested')
	})
})

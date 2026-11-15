/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The intake assign dialog: picking a register and schema fetches the
 * schema's records, the separator sheet's reference pre-selects one, and
 * Assign sends the register, schema and record id the clerk picked.
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */

const { nextTick } = require('vue')

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars = {}) =>
		text.replace(/\{(\w+)\}/g, (m, key) => (key in vars ? String(vars[key]) : m)),
}))
jest.mock('@nextcloud/router', () => ({
	generateUrl: (url, params = {}) =>
		url.replace(/\{(\w+)\}/g, (m, key) => params[key] ?? m),
}))
jest.mock('@nextcloud/axios', () => ({ get: jest.fn() }))
jest.mock('@conduction/nextcloud-vue', () => ({
	CnDialogActions: {},
	CnRegisterSchemaSelect: {},
}))

const axios = require('@nextcloud/axios')
const { renderStubbed } = require('../../tests/jest/renderGolden.js')
const IntakeAssignDialog = require('./IntakeAssignDialog.vue').default

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
 * Mount the dialog for a document, with its own events recorded.
 *
 * @param {object} [props] Props that differ.
 * @return {Promise<object>} The rendered dialog plus its event spies.
 */
async function mountDialog(props = {}) {
	const onConfirm = jest.fn()
	const onCancel = jest.fn()
	rendered = await renderStubbed(IntakeAssignDialog, {
		props: { document: { subject: 'Invoice 12' }, onConfirm, onCancel, ...props },
	})
	return { ...rendered, onConfirm, onCancel }
}

/**
 * The attributes a stubbed child was last rendered with.
 *
 * @param {object} wrapper The rendered dialog.
 * @param {string} name The child component.
 * @return {object} Its attrs (props and listeners).
 */
const child = (wrapper, name) => wrapper.stubs.find((s) => s.name === name).attrs
const select = (wrapper) => child(wrapper, 'NcSelect')
const actions = (wrapper) => child(wrapper, 'CnDialogActions')
const picker = (wrapper) => child(wrapper, 'CnRegisterSchemaSelect')
const text = (wrapper, selector) => wrapper.vm.$el.querySelector(selector)?.textContent.trim()

/**
 * Pick a register and a schema through the picker.
 *
 * @param {object} wrapper The rendered dialog.
 * @return {Promise<void>}
 */
async function pickRegisterAndSchema(wrapper) {
	picker(wrapper)['onUpdate:register']('zaken')
	await settle()
	picker(wrapper)['onUpdate:schema']('zaak')
	await settle()
}

/**
 * Choose a record in the record select.
 *
 * @param {object} wrapper The rendered dialog.
 * @param {object} option The option.
 * @return {Promise<void>}
 */
async function chooseRecord(wrapper, option) {
	select(wrapper)['onUpdate:modelValue'](option)
	await settle()
}

beforeEach(() => {
	axios.get.mockReset()
})

afterEach(() => {
	rendered?.unmount()
	rendered = null
})

describe('IntakeAssignDialog', () => {
	it('names the document and asks for a register and schema first', async () => {
		const wrapper = await mountDialog()

		expect(text(wrapper, 'p')).toBe('Pick the record "Invoice 12" belongs to.')
		expect(select(wrapper).placeholder).toBe('Pick a register and a schema first')
		expect(select(wrapper).disabled).toBe(true)
		expect(actions(wrapper).canSubmit).toBe(false)
		expect(axios.get).not.toHaveBeenCalled()
	})

	it('falls back to "this document" when the document has no subject', async () => {
		const wrapper = await mountDialog({ document: {} })
		expect(text(wrapper, 'p')).toBe('Pick the record "this document" belongs to.')
	})

	it('fetches the records of the picked schema and offers them by name', async () => {
		axios.get.mockResolvedValue({
			data: {
				results: [
					{ '@self': { id: 'r1', name: 'Case A' } },
					{ id: 'r2', title: 'Case B' },
					{ uuid: 'r3' },
				],
			},
		})
		const wrapper = await mountDialog()
		await pickRegisterAndSchema(wrapper)

		expect(axios.get).toHaveBeenCalledTimes(1)
		expect(axios.get).toHaveBeenCalledWith(
			'/apps/openregister/api/objects/zaken/zaak',
			{ params: { _limit: 100 } },
		)
		expect(select(wrapper).options).toEqual([
			{ id: 'r1', label: 'Case A' },
			{ id: 'r2', label: 'Case B' },
			{ id: 'r3', label: 'Unnamed record' },
		])
		expect(select(wrapper).placeholder).toBe('Pick a record')
		expect(select(wrapper).loading).toBe(false)
	})

	it('says the schema is empty when it holds no records', async () => {
		axios.get.mockResolvedValue({ data: { results: 'not a list' } })
		const wrapper = await mountDialog()
		await pickRegisterAndSchema(wrapper)

		expect(select(wrapper).options).toEqual([])
		expect(select(wrapper).placeholder).toBe('This schema holds no records yet')
	})

	it('shows the loading placeholder while the records are on their way', async () => {
		let answer
		axios.get.mockReturnValue(new Promise((resolve) => { answer = resolve }))
		const wrapper = await mountDialog()
		await pickRegisterAndSchema(wrapper)

		expect(select(wrapper).loading).toBe(true)
		expect(select(wrapper).placeholder).toBe('Loading records')

		answer({ data: { results: [] } })
		await settle()
		expect(select(wrapper).loading).toBe(false)
	})

	it('treats a failed fetch as no records', async () => {
		axios.get.mockRejectedValue(new Error('403'))
		const wrapper = await mountDialog()
		await pickRegisterAndSchema(wrapper)

		expect(select(wrapper).options).toEqual([])
		expect(select(wrapper).loading).toBe(false)
	})

	it('does not fetch when the schema is cleared', async () => {
		const wrapper = await mountDialog()
		picker(wrapper)['onUpdate:register']('zaken')
		await settle()
		picker(wrapper)['onUpdate:schema'](null)
		await settle()

		expect(axios.get).not.toHaveBeenCalled()
	})

	it('pre-selects the record the separator sheet named', async () => {
		axios.get.mockResolvedValue({
			data: { results: [{ id: 'r1', name: 'Z-2026-001' }, { id: 'r2', name: 'Z-2026-002' }] },
		})
		const wrapper = await mountDialog({ document: { subject: 'Scan', sourceRef: ' 2026-002 ' } })
		await pickRegisterAndSchema(wrapper)

		expect(select(wrapper).modelValue).toEqual({ id: 'r2', label: 'Z-2026-002' })
		expect(actions(wrapper).canSubmit).toBe(true)
	})

	it('pre-selects a record whose id is the reference', async () => {
		axios.get.mockResolvedValue({ data: { results: [{ id: 'abc', name: 'Case' }] } })
		const wrapper = await mountDialog({ document: { sourceRef: 'abc' } })
		await pickRegisterAndSchema(wrapper)

		expect(select(wrapper).modelValue).toEqual({ id: 'abc', label: 'Case' })
	})

	it('leaves the record open when the reference matches nothing', async () => {
		axios.get.mockResolvedValue({ data: { results: [{ id: 'r1', name: 'Case' }] } })
		const wrapper = await mountDialog({ document: { sourceRef: 'Z-9' } })
		await pickRegisterAndSchema(wrapper)

		expect(select(wrapper).modelValue).toBeNull()
	})

	it('picking another register clears the schema, the record and the list', async () => {
		axios.get.mockResolvedValue({ data: { results: [{ id: 'r1', name: 'Case' }] } })
		const wrapper = await mountDialog()
		await pickRegisterAndSchema(wrapper)
		await chooseRecord(wrapper, { id: 'r1', label: 'Case' })

		picker(wrapper)['onUpdate:register']('other')
		await settle()

		expect(picker(wrapper)).toMatchObject({ register: 'other', schema: '' })
		expect(select(wrapper).modelValue).toBeNull()
		expect(select(wrapper).options).toEqual([])
	})

	it('sends the register, schema and record id on Assign', async () => {
		axios.get.mockResolvedValue({ data: { results: [{ id: 'r1', name: 'Case' }] } })
		const wrapper = await mountDialog()
		await pickRegisterAndSchema(wrapper)
		await chooseRecord(wrapper, { id: 'r1', label: 'Case' })

		actions(wrapper).onSubmit()

		expect(wrapper.onConfirm).toHaveBeenCalledTimes(1)
		expect(wrapper.onConfirm).toHaveBeenCalledWith({ register: 'zaken', schema: 'zaak', id: 'r1' })
	})

	it('sends nothing on Assign without a record', async () => {
		const wrapper = await mountDialog()
		actions(wrapper).onSubmit()
		expect(wrapper.onConfirm).not.toHaveBeenCalled()
	})

	it('cancels from the actions and from closing the dialog', async () => {
		const wrapper = await mountDialog()
		actions(wrapper).onCancel()
		child(wrapper, 'NcDialog').onClosing()
		expect(wrapper.onCancel).toHaveBeenCalledTimes(2)
	})

	it('shows the server refusal and locks the pickers while saving', async () => {
		const wrapper = await mountDialog({ saving: true, error: 'You may not write to this schema.' })

		expect(text(wrapper, '.intake-assign__error')).toBe('You may not write to this schema.')
		expect(picker(wrapper).disabled).toBe(true)
		expect(actions(wrapper).saving).toBe(true)
	})
})

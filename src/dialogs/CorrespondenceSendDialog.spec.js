/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The send-by-email dialog: on opening it shows where the letter goes and
 * under which subject, a refusal is shown with Send off, and Send hands the
 * PDF choice to the page.
 *
 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-3
 */

const { nextTick } = require('vue')

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars = {}) =>
		text.replace(/\{(\w+)\}/g, (m, key) => (key in vars ? String(vars[key]) : m)),
}))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))
jest.mock('@nextcloud/axios', () => ({ post: jest.fn() }))
jest.mock('@conduction/nextcloud-vue', () => ({ CnDialogActions: {} }))

const axios = require('@nextcloud/axios')
const { renderStubbed } = require('../../tests/jest/renderGolden.js')
const CorrespondenceSendDialog = require('./CorrespondenceSendDialog.vue').default

const REQUEST = {
	templateId: 'tmpl-1',
	dataRefs: [{ register: 'brp', schema: 'persoon', id: 'p-1' }],
	options: { caseReference: 'Z/2026/001' },
}

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
 * Mount the dialog, with its own events recorded.
 *
 * @param {object} [props] Props that differ.
 * @return {Promise<object>} The rendered dialog plus its event spies.
 */
async function mountDialog(props = {}) {
	const onConfirm = jest.fn()
	const onCancel = jest.fn()
	rendered = await renderStubbed(CorrespondenceSendDialog, {
		props: { request: REQUEST, onConfirm, onCancel, ...props },
	})
	await settle()
	return { ...rendered, onConfirm, onCancel }
}

const child = (wrapper, name) => wrapper.stubs.find((s) => s.name === name).attrs
const actions = (wrapper) => child(wrapper, 'CnDialogActions')
const pdf = (wrapper) => child(wrapper, 'NcCheckboxRadioSwitch')
const text = (wrapper, testid) => wrapper.vm.$el.querySelector(`[data-testid="${testid}"]`)?.textContent.trim()

beforeEach(() => {
	axios.post.mockReset()
})

afterEach(() => {
	rendered?.unmount()
	rendered = null
})

describe('CorrespondenceSendDialog', () => {
	it('asks the server for the address and the subject and shows them', async () => {
		axios.post.mockResolvedValue({ data: { to: 'j.jansen@example.nl', subject: 'Uitnodiging gesprek' } })
		const wrapper = await mountDialog()

		expect(axios.post).toHaveBeenCalledTimes(1)
		expect(axios.post).toHaveBeenCalledWith('/apps/filinq/api/correspondence/send/preview', REQUEST)
		expect(text(wrapper, 'correspondence-send-to')).toBe('j.jansen@example.nl')
		expect(text(wrapper, 'correspondence-send-subject')).toBe('Uitnodiging gesprek')
		expect(actions(wrapper).canSubmit).toBe(true)
		expect(text(wrapper, 'correspondence-send-error')).toBeUndefined()
	})

	it('shows a refused preview and keeps Send off', async () => {
		axios.post.mockRejectedValue({ response: { status: 422, data: { error: 'Deze ontvanger heeft geen e-mailadres.' } } })
		const wrapper = await mountDialog()

		expect(text(wrapper, 'correspondence-send-error')).toBe('Deze ontvanger heeft geen e-mailadres.')
		expect(actions(wrapper).canSubmit).toBe(false)
		expect(pdf(wrapper).disabled).toBe(true)
	})

	it('falls back to a sentence of its own when the lookup fails without one', async () => {
		axios.post.mockRejectedValue(new Error('Network Error'))
		const wrapper = await mountDialog()

		expect(text(wrapper, 'correspondence-send-error')).toBe('The recipient could not be looked up.')
	})

	it('hands the PDF choice to the page on Send', async () => {
		axios.post.mockResolvedValue({ data: { to: 'j.jansen@example.nl', subject: 'Uitnodiging gesprek' } })
		const wrapper = await mountDialog()

		pdf(wrapper)['onUpdate:modelValue'](true)
		await settle()
		actions(wrapper).onSubmit()

		expect(wrapper.onConfirm).toHaveBeenCalledTimes(1)
		expect(wrapper.onConfirm).toHaveBeenCalledWith({ attachPdf: true })
		expect(axios.post).toHaveBeenCalledTimes(1)
	})

	it('shows what the server said about a refused send, and stays open', async () => {
		axios.post.mockResolvedValue({ data: { to: 'j.jansen@example.nl', subject: 'Uitnodiging gesprek' } })
		const wrapper = await mountDialog({ error: 'De brief is niet verstuurd: de mailserver heeft hem niet aangenomen.' })

		expect(text(wrapper, 'correspondence-send-error')).toBe('De brief is niet verstuurd: de mailserver heeft hem niet aangenomen.')
		expect(wrapper.onCancel).not.toHaveBeenCalled()
	})

	it('cancels without sending', async () => {
		axios.post.mockResolvedValue({ data: { to: 'j.jansen@example.nl', subject: 'S' } })
		const wrapper = await mountDialog()

		actions(wrapper).onCancel()

		expect(wrapper.onCancel).toHaveBeenCalledTimes(1)
		expect(wrapper.onConfirm).not.toHaveBeenCalled()
	})
})

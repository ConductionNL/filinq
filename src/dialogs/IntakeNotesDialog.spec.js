/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The intake notes dialog: the notes card reads the comments of the intake
 * document's own file, a document without a file says there is nothing to
 * leave notes on, and closing the dialog tells the worklist.
 *
 * @spec openspec/changes/work-document-notes/specs/document-notes/spec.md#requirement-notes-sit-beside-the-document-on-filinqs-screens-req-wdn-002
 */

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars = {}) =>
		text.replace(/\{(\w+)\}/g, (m, key) => (key in vars ? String(vars[key]) : m)),
}))
jest.mock('@conduction/nextcloud-vue', () => ({
	CnNotesCard: {},
}))

const { renderStubbed } = require('../../tests/jest/renderGolden.js')
const IntakeNotesDialog = require('./IntakeNotesDialog.vue').default

let rendered

/**
 * Mount the dialog for a document.
 *
 * @param {object} document The intake document.
 * @return {Promise<object>} The rendered dialog and its close spy.
 */
async function mountDialog(document) {
	const onClose = jest.fn()
	rendered = await renderStubbed(IntakeNotesDialog, { props: { document, onClose } })
	return { ...rendered, onClose }
}

const stub = (wrapper, name) => wrapper.stubs.find((s) => s.name === name)

afterEach(() => {
	rendered?.unmount()
	rendered = null
})

describe('IntakeNotesDialog', () => {
	it('shows the comments of the document\'s own file, named after it', async () => {
		const wrapper = await mountDialog({ uuid: 'intake-1', file: 4711, fileName: 'offerte.pdf' })

		expect(stub(wrapper, 'CnNotesCard').attrs.fileId).toBe('4711')
		expect(stub(wrapper, 'NcDialog').attrs.name).toBe('Notes on offerte.pdf')
	})

	it('says so when the document has no file, and shows no notes card', async () => {
		const wrapper = await mountDialog({ uuid: 'intake-2', subject: 'Losse melding' })

		expect(stub(wrapper, 'CnNotesCard')).toBeUndefined()
		expect(wrapper.vm.$el.querySelector('.intake-notes__none')?.textContent.trim())
			.toBe('This document has no file to leave notes on.')
		expect(stub(wrapper, 'NcDialog').attrs.name).toBe('Notes on Losse melding')
	})

	it('falls back to a plain title and closes through the worklist', async () => {
		const wrapper = await mountDialog({ file: 5 })

		expect(stub(wrapper, 'NcDialog').attrs.name).toBe('Notes')
		stub(wrapper, 'NcDialog').attrs.onClosing()
		expect(wrapper.onClose).toHaveBeenCalledTimes(1)
	})
})

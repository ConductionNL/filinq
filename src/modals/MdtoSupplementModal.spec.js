/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The MDTO supplement form: it edits only the supplement fields, never a
 * retention-owned one, and an edited field stops counting as a proposal.
 *
 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
 */

const { nextTick } = require('vue')

jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))
jest.mock('@nextcloud/axios', () => ({ get: jest.fn(), post: jest.fn(), put: jest.fn() }))
const mockSetResult = jest.fn()
jest.mock('@conduction/nextcloud-vue', () => ({
	CnFormDialog: { name: 'CnFormDialog', methods: { setResult: (...args) => mockSetResult(...args) }, render: () => null },
}))
jest.mock('../services/mdto.js', () => ({ saveSupplement: jest.fn() }))

const { renderStubbed } = require('../../tests/jest/renderGolden.js')
const { saveSupplement } = require('../services/mdto.js')
const MdtoSupplementModal = require('./MdtoSupplementModal.vue').default
const { supplementFormSchema } = require('./MdtoSupplementModal.vue')

const subject = { objectRef: 'rec-1', objectType: 'document' }

/**
 * The form dialog stub.
 *
 * @param {object} rendered The rendered modal.
 * @return {object} Its attrs.
 */
function dialog(rendered) {
	return rendered.stubs.filter((s) => s.name === 'CnFormDialog').pop().attrs
}

let rendered

afterEach(() => {
	rendered?.unmount()
	jest.resetAllMocks()
})

describe('MdtoSupplementModal', () => {
	it('offers no retention-owned attribute', () => {
		const fields = Object.keys(supplementFormSchema().properties)

		expect(fields).toEqual(['aggregatieniveau', 'omschrijving', 'taal', 'dekkingInTijdBegin', 'dekkingInTijdEind', 'archiefvormerOverride'])
		for (const owned of ['archiefnominatie', 'bewaartermijn', 'archiefactiedatum', 'archiefstatus', 'classificatie']) {
			expect(fields).not.toContain(owned)
		}
	})

	it('opens on the stored supplement', async () => {
		rendered = await renderStubbed(MdtoSupplementModal, { props: { supplement: { omschrijving: 'Besluit' }, subject } })

		expect(dialog(rendered).item).toEqual({ omschrijving: 'Besluit' })
	})

	it('saves the edited fields, confirming the proposals they replace, and reports success', async () => {
		const onSaved = jest.fn()
		const supplement = { id: 's-1', proposals: ['aggregatieniveau', 'dekkingInTijdBegin'] }
		rendered = await renderStubbed(MdtoSupplementModal, { props: { supplement, subject, onSaved }, keep: ['CnFormDialog'] })
		saveSupplement.mockResolvedValue({ ok: true, data: { id: 's-1' } })

		await rendered.vm.onConfirm({ aggregatieniveau: 'archiefstuk', dekkingInTijdBegin: '2024-11-03', retention: { archiefnominatie: 'x' } })
		await nextTick()

		expect(saveSupplement).toHaveBeenCalledWith(supplement, { aggregatieniveau: 'archiefstuk', dekkingInTijdBegin: '2024-11-03', proposals: [] }, subject)
		expect(onSaved).toHaveBeenCalledWith({ id: 's-1' })
		expect(mockSetResult).toHaveBeenCalledWith({ success: true })
	})

	it('keeps the dialog open on a refusal', async () => {
		const onSaved = jest.fn()
		rendered = await renderStubbed(MdtoSupplementModal, { props: { supplement: {}, subject, onSaved }, keep: ['CnFormDialog'] })
		saveSupplement.mockResolvedValue({ ok: false, error: 'The MDTO metadata could not be saved.' })

		await rendered.vm.onConfirm({ taal: 'nl' })

		expect(onSaved).not.toHaveBeenCalled()
		expect(mockSetResult).toHaveBeenCalledWith({ error: 'The MDTO metadata could not be saved.' })
	})
})

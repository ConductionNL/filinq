/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The MDTO panel: the core attributes read only from the retention block,
 * proposal chips that save through the supplement, the completeness banner
 * listing the missing fields verbatim, the manual-delivery state and the
 * transfer action.
 *
 * @spec openspec/changes/tmlo-mdto-metadata/tasks.md#3-frontend
 */

const { nextTick } = require('vue')

jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))
jest.mock('@nextcloud/axios', () => ({ get: jest.fn(), post: jest.fn(), put: jest.fn() }))
jest.mock('@nextcloud/vue', () => ({ NcButton: {}, NcLoadingIcon: {}, NcNoteCard: {} }))
jest.mock('@conduction/nextcloud-vue', () => ({ CnFormDialog: {} }))
jest.mock('../services/mdto.js', () => ({
	...jest.requireActual('../services/mdto.js'),
	fetchMdtoStatus: jest.fn(),
	saveSupplement: jest.fn(),
	transferRecord: jest.fn(),
}))

const { renderStubbed } = require('../../tests/jest/renderGolden.js')
const { fetchMdtoStatus, saveSupplement, transferRecord } = require('../services/mdto.js')
const MdtoPanel = require('./MdtoPanel.vue').default

const objection = { type: 'avg-persoonsgegevens', grondslag: 'AVG art. 6 lid 1 sub e', proposed: true, source: 'consent' }

/**
 * A status answer.
 *
 * @param {object} overrides Fields to change.
 * @return {object}
 */
function status(overrides = {}) {
	return {
		complete: true,
		missing: [],
		transportConfigured: true,
		mayTransfer: true,
		retention: { archiefnominatie: 'bewaren', bewaartermijn: 'P20Y', classificatie: '1.1' },
		supplement: { id: 's-1', objectRef: 'rec-1', aggregatieniveau: 'archiefstuk', dekkingInTijdBegin: '2024-11-03', beperkingGebruik: [], proposals: [] },
		proposals: { aggregatieniveau: { value: 'archiefstuk', proposed: true } },
		...overrides,
	}
}

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
 * Render the panel for record rec-1.
 *
 * @param {object} answer The status the server gives.
 * @return {Promise<object>}
 */
async function render(answer) {
	fetchMdtoStatus.mockResolvedValue({ ok: true, data: answer })
	const rendered = await renderStubbed(MdtoPanel, { props: { objectId: 'rec-1' } })
	await settle()
	return rendered
}

/**
 * The last-rendered button stub carrying this test id.
 *
 * @param {object} rendered The rendered panel.
 * @param {string} id The data-testid.
 * @return {object|undefined} Its attrs.
 */
function button(rendered, id) {
	const found = rendered.stubs.filter((s) => s.name === 'NcButton' && s.attrs['data-testid'] === id)
	return found.length ? found[found.length - 1].attrs : undefined
}

let rendered

afterEach(() => {
	rendered?.unmount()
	jest.resetAllMocks()
})

describe('MdtoPanel', () => {
	it('shows the core attributes from the retention block', async () => {
		rendered = await render(status())

		const core = rendered.html().match(/data-testid="mdto-core">(.*?)<\/dl>/s)[1]
		expect(core).toContain('bewaren')
		expect(core).toContain('P20Y')
		expect(core).toContain('1.1')
		expect(fetchMdtoStatus).toHaveBeenCalledWith('rec-1')
	})

	it('lists the missing fields verbatim and switches the transfer off', async () => {
		rendered = await render(status({ complete: false, missing: ['aggregatieniveau', 'file[0].checksum'] }))

		const banner = rendered.html().match(/data-testid="mdto-incomplete".*?<\/x-ncnotecard>/s)[0]
		expect(banner).toContain('<code>aggregatieniveau</code>')
		expect(banner).toContain('<code>file[0].checksum</code>')
		expect(button(rendered, 'mdto-transfer').disabled).toBe(true)
	})

	it('offers the sidecar for manual delivery when no transport is configured', async () => {
		rendered = await render(status({ transportConfigured: false }))

		expect(rendered.html()).toContain('data-testid="mdto-manual-delivery"')
		expect(button(rendered, 'mdto-transfer')).toBeUndefined()
		expect(button(rendered, 'mdto-export-xml').href).toContain('/api/mdto/rec-1/export?format=xml')
	})

	it('shows neither export nor transfer to someone who may not transfer', async () => {
		rendered = await render(status({ mayTransfer: false, transportConfigured: false }))

		expect(button(rendered, 'mdto-export-xml')).toBeUndefined()
		expect(button(rendered, 'mdto-transfer')).toBeUndefined()
		expect(rendered.html()).not.toContain('mdto-manual-delivery')
	})

	it('shows an objection proposal as a chip and saves it confirmed on accept', async () => {
		rendered = await render(status({ proposals: { beperkingGebruik: { value: [objection], proposed: true } } }))
		saveSupplement.mockResolvedValue({ ok: true, data: {} })

		expect(rendered.html()).toContain('avg-persoonsgegevens — AVG art. 6 lid 1 sub e')
		button(rendered, 'mdto-restriction-accept').onClick()
		await settle()

		expect(saveSupplement).toHaveBeenCalledWith(
			expect.objectContaining({ id: 's-1' }),
			{ beperkingGebruik: [{ ...objection, proposed: false, rejected: false }] },
			{ objectRef: 'rec-1', objectType: 'document' },
		)
		expect(fetchMdtoStatus).toHaveBeenCalledTimes(2)
	})

	it('saves a rejected proposal as rejected', async () => {
		rendered = await render(status({ proposals: { beperkingGebruik: { value: [objection], proposed: true } } }))
		saveSupplement.mockResolvedValue({ ok: true, data: {} })

		button(rendered, 'mdto-restriction-reject').onClick()
		await settle()

		expect(saveSupplement.mock.calls[0][1]).toEqual({ beperkingGebruik: [{ ...objection, proposed: true, rejected: true }] })
	})

	it('offers an unconfirmed field proposal and confirms it on accept', async () => {
		const answer = status()
		answer.supplement.proposals = ['aggregatieniveau']
		rendered = await render(answer)
		saveSupplement.mockResolvedValue({ ok: true, data: {} })

		expect(rendered.html()).toContain('(proposal, not confirmed)')
		button(rendered, 'mdto-accept-aggregatieniveau').onClick()
		await settle()

		expect(saveSupplement.mock.calls[0][1]).toEqual({ aggregatieniveau: 'archiefstuk', proposals: [] })
	})

	it('says what a transfer did', async () => {
		rendered = await render(status())
		transferRecord.mockResolvedValue({ ok: true, data: { status: 'failed', transferStatus: 'failed' } })

		button(rendered, 'mdto-transfer').onClick()
		await settle()

		expect(transferRecord).toHaveBeenCalledWith('rec-1')
		expect(rendered.html()).toContain('The e-depot did not confirm the transfer. The record was not changed.')
	})

	it('shows the load error', async () => {
		fetchMdtoStatus.mockResolvedValue({ ok: false, error: 'The record cannot be read.' })
		rendered = await renderStubbed(MdtoPanel, { props: { objectId: 'rec-1' } })
		await settle()

		expect(rendered.html()).toContain('The record cannot be read.')
	})

	// @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-transfer-to-archive-state-for-permanent-records-req-ddare-006
	it('shows a transferred record read only and tells the parent', async () => {
		const onTransferred = jest.fn()
		const answer = status({
			retention: { archiefnominatie: 'bewaren', archiefstatus: 'overgebracht' },
			proposals: { beperkingGebruik: { value: [objection], proposed: true } },
		})
		fetchMdtoStatus.mockResolvedValue({ ok: true, data: answer })
		rendered = await renderStubbed(MdtoPanel, { props: { objectId: 'rec-1', onTransferred } })
		await settle()

		expect(rendered.html()).toContain('data-testid="mdto-transferred"')
		expect(rendered.html()).toContain('Transferred to the e-depot. This record is read only: it can no longer be changed.')
		expect(button(rendered, 'mdto-transfer').disabled).toBe(true)
		expect(button(rendered, 'mdto-restriction-accept').disabled).toBe(true)
		expect(button(rendered, 'mdto-restriction-reject').disabled).toBe(true)
		expect(onTransferred).toHaveBeenLastCalledWith(true)
	})

	// @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-transfer-to-archive-state-for-permanent-records-req-ddare-006
	it('leaves a record that is not transferred editable', async () => {
		const onTransferred = jest.fn()
		fetchMdtoStatus.mockResolvedValue({ ok: true, data: status() })
		rendered = await renderStubbed(MdtoPanel, { props: { objectId: 'rec-1', onTransferred } })
		await settle()

		expect(rendered.html()).not.toContain('mdto-transferred')
		expect(button(rendered, 'mdto-transfer').disabled).toBe(false)
		expect(onTransferred).toHaveBeenLastCalledWith(false)
	})
})


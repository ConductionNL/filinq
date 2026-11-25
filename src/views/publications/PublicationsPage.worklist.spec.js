/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The Woo metadata worklist: the incomplete filter reaches the API, the
 * missing-fields column reads the server's diwooMissing, and a value set on a
 * selection reports what was updated and skipped and lists again.
 *
 * @spec openspec/changes/archive/2026-11-25-woo-metadata-worklist/specs/woo-metadata-worklist/spec.md
 */

jest.mock('@nextcloud/axios', () => ({ __esModule: true, default: { get: jest.fn(), put: jest.fn(), post: jest.fn() } }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/vue', () => ({ NcButton: {}, NcEmptyContent: {}, NcLoadingIcon: {}, NcNoteCard: {}, NcTextField: {}, NcDialog: {} }))
jest.mock('@conduction/nextcloud-vue', () => ({ CnDialogActions: {} }))

const axios = require('@nextcloud/axios').default
const PublicationsPage = require('./PublicationsPage.vue').default
const PublicationBulkMetadataDialog = require('../../dialogs/PublicationBulkMetadataDialog.vue').default
const { listPublications, setMetadataMany } = require('../../services/publications.js')

/**
 * A bare list page carrying the component's methods and computed.
 *
 * @return {object} The page.
 */
function page() {
	const self = { id: '', ...PublicationsPage.data() }
	for (const [name, method] of Object.entries(PublicationsPage.methods)) {
		self[name] = method.bind(self)
	}
	Object.defineProperty(self, 'allSelected', { get: () => PublicationsPage.computed.allSelected.call(self) })
	return self
}

describe('the Woo metadata worklist', () => {
	beforeEach(() => {
		axios.get.mockReset()
		axios.put.mockReset()
	})

	it('asks the API for incomplete records only when the filter is on', async () => {
		axios.get.mockResolvedValue({ data: { results: [] } })

		await listPublications()
		await listPublications({ incomplete: true })

		expect(axios.get.mock.calls).toEqual([
			// Without the filter the call is the one it always was.
			['/apps/filinq/api/publications'],
			['/apps/filinq/api/publications', { params: { incomplete: 1 } }],
		])
	})

	it('sends the selection and the one value to the bulk endpoint', async () => {
		axios.put.mockResolvedValue({ data: { updated: [], skipped: [] } })

		await setMetadataMany(['a', 'b'], { publisher: 'tooi:gm0363' })

		expect(axios.put).toHaveBeenCalledWith('/apps/filinq/api/publications/metadata', { ids: ['a', 'b'], values: { publisher: 'tooi:gm0363' } })
	})

	it('reads the missing fields from diwooMissing, else from the local check', () => {
		const self = page()

		expect(self.missingOf({ diwooMissing: ['publisher', 'documentsoort'] })).toEqual(['publisher', 'documentsoort'])
		expect(self.missingOf({ officieleTitel: 'Besluit', wooCategory: '', publicatiedatum: '' })).toEqual(['wooCategory', 'publicatiedatum'])
	})

	it('selects every listed record, or none', () => {
		const self = page()
		self.records = [{ uuid: 'a' }, { uuid: 'b' }]

		self.toggleAll(true)
		expect(self.selected).toEqual(['a', 'b'])
		expect(self.allSelected).toBe(true)
		self.toggleAll(false)
		expect(self.selected).toEqual([])
		expect(self.allSelected).toBe(false)
	})

	it('reports what was updated and skipped, then lists the filter again', async () => {
		const self = page()
		self.onlyIncomplete = true
		self.records = [{ uuid: 'a', officieleTitel: 'Besluit A' }, { uuid: 'b', officieleTitel: 'Besluit B' }]
		self.selected = ['a', 'b']
		self.categories = [{ code: 'c1', label: 'Wetten' }]
		axios.put.mockResolvedValue({ data: { updated: [{ uuid: 'a' }], skipped: [{ uuid: 'b', reason: 'already handed off' }] } })
		axios.get.mockResolvedValue({ data: { results: [{ uuid: 'b', officieleTitel: 'Besluit B' }] } })

		await self.setMetadata({ publisher: 'tooi:gm0363' })

		expect(self.bulkResult.updated).toHaveLength(1)
		expect(self.bulkResult.skipped).toEqual([{ uuid: 'b', reason: 'already handed off' }])
		expect(self.titleOf('b')).toBe('Besluit B')
		expect(self.selected).toEqual([])
		expect(self.bulkOpen).toBe(false)
		expect(axios.get).toHaveBeenCalledWith('/apps/filinq/api/publications', { params: { incomplete: 1 } })
		expect(self.records.map((row) => row.uuid)).toEqual(['b'])
	})

	it('keeps the selection and shows the refusal when the change fails', async () => {
		const self = page()
		self.selected = ['a']
		axios.put.mockRejectedValue({ response: { data: { error: 'Select at least one publication and name the values to set' } } })

		await self.setMetadata({ publisher: '' })

		expect(self.error).toBe('Select at least one publication and name the values to set')
		expect(self.selected).toEqual(['a'])
		expect(self.busy).toBe(false)
	})

	it('the dialog sets one chosen field, trimmed', () => {
		const dialog = { ...PublicationBulkMetadataDialog.data(), field: 'documentsoort', value: '  besluit ' }
		const values = PublicationBulkMetadataDialog.methods.values.call(dialog)

		expect(values).toEqual({ documentsoort: 'besluit' })
		const keys = PublicationBulkMetadataDialog.computed.fields.call(dialog).map((field) => field.key)
		expect(keys).toEqual(['wooCategory', 'documentsoort', 'publisher'])
	})
})

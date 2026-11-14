/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * ReviewWorkbench wiring: one shared entity model through the anonymisation
 * store, the review state read back from the server, the reviewed mark, and
 * the zero-match path of a manual entity.
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-4.3
 */

import ReviewWorkbench from './ReviewWorkbench.vue'

jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/axios', () => ({ get: jest.fn() }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (u) => u }))
jest.mock('@nextcloud/vue', () => ({ NcButton: { name: 'NcButton', render: () => null } }))
jest.mock('../../components/compare/DocumentCompare.vue', () => ({ name: 'DocumentCompare', render: () => null }))
jest.mock('../../dialogs/AddManualEntityModal.vue', () => ({ name: 'AddManualEntityModal', render: () => null }))
jest.mock('./EntityReviewTable.vue', () => ({ name: 'EntityReviewTable', render: () => null }))
jest.mock('../../services/bases.js', () => ({ fetchBaseOptions: jest.fn().mockResolvedValue([{ label: 'A', value: 'a' }]) }))
jest.mock('../../store/store.js', () => ({
	anonymizationStore: {
		ensureExtracted: jest.fn(),
		toggleEntity: jest.fn(),
		setVisibleEntities: jest.fn(),
		addManualEntity: jest.fn(),
		markEntryChecked: jest.fn(),
	},
}))
jest.mock('../../services/documentReview.js', () => {
	const actual = jest.requireActual('../../services/documentReview.js')
	return {
		...actual,
		fetchReview: jest.fn(),
		checkReview: jest.fn(),
		uncheckReview: jest.fn(),
		fetchAnonymizationLink: jest.fn(),
	}
})

const reviews = require('../../services/documentReview.js')
const { anonymizationStore } = require('../../store/store.js')

/**
 * The component's options bound to a plain state object.
 *
 * @param {object} route The route.
 * @return {object} The instance stand-in.
 */
function instance(route = { params: { fileId: '7' }, query: { name: 'a.pdf', path: '/Filinq/a.pdf' } }) {
	const vm = { $route: route, $refs: {} }
	Object.assign(vm, ReviewWorkbench.data.call(vm))
	for (const [name, fn] of Object.entries(ReviewWorkbench.methods)) {
		vm[name] = fn.bind(vm)
	}
	for (const [name, fn] of Object.entries(ReviewWorkbench.computed)) {
		Object.defineProperty(vm, name, { get: fn.bind(vm) })
	}
	return vm
}

beforeEach(() => {
	jest.clearAllMocks()
})

describe('ReviewWorkbench', () => {
	it('loads the entry through the store, the link and the review state', async () => {
		const entry = { fileId: 7, status: 'extracted', entities: [] }
		anonymizationStore.ensureExtracted.mockResolvedValue(entry)
		reviews.fetchAnonymizationLink.mockResolvedValue(null)
		reviews.fetchReview.mockResolvedValue({ checked: false, stale: false })
		const vm = instance()

		await vm.load()

		expect(anonymizationStore.ensureExtracted).toHaveBeenCalledWith({ fileId: 7, fileName: 'a.pdf', mimeType: '', path: '/Filinq/a.pdf' })
		expect(reviews.fetchAnonymizationLink).toHaveBeenCalledWith(7)
		expect(vm.entry).toBe(entry)
		expect(vm.anonymized).toBeNull()
		expect(vm.basesOptions).toEqual([{ label: 'A', value: 'a' }])
		expect(vm.state).toBe('unchecked')
		expect(vm.loading).toBe(false)
	})

	it('shows the anonymised file once a link exists', () => {
		const vm = instance()
		vm.link = { sourceFileId: 7, anonymizedFileId: 9, anonymizedFileName: 'a-gelakt.pdf', anonymizedFilePath: '/Filinq/a-gelakt.pdf' }
		expect(vm.anonymized).toEqual({ fileName: 'a-gelakt.pdf', mimeType: '', path: '/Filinq/a-gelakt.pdf' })
	})

	it('toggles through the shared store and reads the review back', async () => {
		reviews.fetchReview.mockResolvedValue({ checked: false, stale: true })
		const vm = instance()
		vm.entry = { entities: [{}] }

		await vm.onToggle(0)

		expect(anonymizationStore.toggleEntity).toHaveBeenCalledWith(vm.entry, 0)
		expect(vm.state).toBe('stale')
		expect(vm.stateText).toContain('changed after the last review')
	})

	it('marks reviewed on both marks, and reports a failure', async () => {
		reviews.checkReview.mockResolvedValueOnce({ checked: true })
		const vm = instance()
		vm.entry = { status: 'extracted' }

		await vm.markReviewed()

		expect(reviews.checkReview).toHaveBeenCalledWith(7)
		expect(anonymizationStore.markEntryChecked).toHaveBeenCalledWith(vm.entry)
		expect(vm.state).toBe('reviewed')
		expect(vm.busy).toBe(false)

		reviews.checkReview.mockRejectedValueOnce({ response: { data: { error: 'no findings yet' } } })
		await vm.markReviewed()
		expect(vm.error).toBe('no findings yet')
	})

	it('removes the mark', async () => {
		reviews.uncheckReview.mockResolvedValue({ checked: false, removed: true })
		const vm = instance()
		await vm.unmarkReviewed()
		expect(reviews.uncheckReview).toHaveBeenCalledWith(7)
		expect(vm.state).toBe('unchecked')
	})

	it('drops the row a selection found nowhere added', async () => {
		const vm = instance()
		vm.entry = { entities: [{ value: 'old' }] }
		anonymizationStore.addManualEntity.mockImplementation(async (entry) => {
			entry.entities = [{ value: 'nergens' }, ...entry.entities]
			return { matchCount: 0, relations: [] }
		})

		const found = await vm.saveManualEntity({ value: 'nergens', type: 'PERSON', bases: [] })

		expect(found).toBe(0)
		expect(vm.entry.entities).toEqual([{ value: 'old' }])
		expect(vm.entry.entityCount).toBe(1)
	})

	it('keeps the rows of a selection that was found', async () => {
		const vm = instance()
		vm.entry = { entities: [] }
		anonymizationStore.addManualEntity.mockImplementation(async (entry) => {
			entry.entities = [{ value: 'Pieter' }]
			return { relations: [{ id: 1 }, { id: 2 }] }
		})

		expect(await vm.saveManualEntity({ value: 'Pieter', type: 'PERSON', bases: [] })).toBe(2)
		expect(vm.entry.entities).toHaveLength(1)
	})

	it('takes the selection inside the original pane only', () => {
		const vm = instance()
		const pane = { contains: (node) => node === 'in' }
		vm.$refs.compare = { querySelector: () => pane }
		global.window.getSelection = () => ({ anchorNode: 'in', focusNode: 'in', toString: () => ' Pieter de Vries ' })
		vm.captureSelection()
		expect(vm.selectedText).toBe('Pieter de Vries')

		global.window.getSelection = () => ({ anchorNode: 'out', focusNode: 'in', toString: () => 'x' })
		vm.captureSelection()
		expect(vm.selectedText).toBe('')
	})
})

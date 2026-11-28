// @vitest-environment jsdom
/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * My documents searches by name, or by name and content (work-content-search 2.1).
 *
 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
 */

import { join } from 'node:path'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { loadSfcOptions } from './sfc/sfcOptions.js'

const store = vi.hoisted(() => ({ visibleDocuments: [] }))
const searchListContent = vi.hoisted(() => vi.fn())
const readListFiles = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/axios', () => ({ default: vi.fn() }))
vi.mock('../../src/store/store.js', () => ({ fileViewerStore: {}, myDocumentsStore: store }))
vi.mock('../../src/services/listContentSearch.js', async (original) => ({
	...(await original()),
	searchListContent,
	readListFiles,
}))
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn(), showSuccess: vi.fn() }))
vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
	translatePlural: (app, one, many, count) => (count === 1 ? one : many).replace('%n', String(count)),
}))

const options = await loadSfcOptions(join(process.cwd(), 'src/views/myDocuments/MyDocumentsIndex.vue'))

/**
 * A stand-in for the page: its data, with the computed values read live.
 *
 * @param {object} state Data to set on top.
 * @return {object} The page.
 */
function page(state = {}) {
	const vm = { ...options.data.call({}), ...state }
	for (const [name, getter] of Object.entries(options.computed)) {
		Object.defineProperty(vm, name, { get: () => getter.call(vm) })
	}
	for (const [name, method] of Object.entries(options.methods)) {
		vm[name] = method.bind(vm)
	}
	return vm
}

const docs = [
	{ fileId: 1, fileName: 'Lindenlaan.pdf' },
	{ fileId: 2, fileName: 'Brief.pdf' },
	{ fileId: 3, fileName: 'Besluit.pdf' },
]

describe('My documents content search', () => {
	beforeEach(() => {
		store.visibleDocuments = docs
		searchListContent.mockReset()
		readListFiles.mockReset()
	})

	it('searches by name only until the content mode is chosen', async () => {
		const vm = page({ searchQuery: 'lindenlaan' })
		expect(vm.filteredDocuments.map((doc) => doc.fileId)).toEqual([1])
		await vm.runContentSearch()
		expect(searchListContent).not.toHaveBeenCalled()
		expect(vm.contentSearchNote).toBe('')
	})

	it('lists name matches then content matches, with the snippet marked', async () => {
		searchListContent.mockResolvedValue({ available: true, failed: false, hits: [{ fileId: 3, snippet: 'over de Lindenlaan' }], unread: [] })
		const vm = page({ searchQuery: 'Lindenlaan ', searchMode: 'content' })
		await vm.runContentSearch()
		expect(searchListContent).toHaveBeenCalledWith('Lindenlaan', docs)
		expect(vm.filteredDocuments.map((doc) => doc.fileId)).toEqual([1, 3])
		expect(vm.snippetFor(docs[2])).toEqual([
			{ text: 'over de ', marked: false },
			{ text: 'Lindenlaan', marked: true },
		])
		expect(vm.snippetFor(docs[0])).toEqual([])
		expect(vm.contentSearchNote).toBe('')
	})

	it('drops an answer to words no longer typed', async () => {
		let resolve
		searchListContent.mockReturnValue(new Promise((done) => { resolve = done }))
		const vm = page({ searchQuery: 'brief', searchMode: 'content' })
		const running = vm.runContentSearch()
		vm.searchQuery = 'besluit'
		resolve({ available: true, failed: false, hits: [{ fileId: 1, snippet: 'x' }], unread: [] })
		await running
		expect(vm.contentAnswer).toBeNull()
		expect(vm.filteredDocuments.map((doc) => doc.fileId)).toEqual([3])
	})

	it('says when the content cannot be searched or could not be asked', async () => {
		searchListContent.mockResolvedValue({ available: false, failed: false, hits: [], unread: [] })
		const vm = page({ searchQuery: 'brief', searchMode: 'content' })
		await vm.runContentSearch()
		expect(vm.contentSearchNote).toBe('The content of documents cannot be searched here; only names were matched.')
		vm.contentAnswer = { available: false, failed: true, hits: [], unread: [] }
		expect(vm.contentSearchNote).toBe('The content could not be searched; only names were matched.')
	})

	it('counts the documents not read yet, reads them and searches again', async () => {
		searchListContent.mockResolvedValue({ available: true, failed: false, hits: [], unread: [2, 3] })
		readListFiles.mockResolvedValue({ read: 1, failed: 1 })
		const vm = page({ searchQuery: 'brief', searchMode: 'content' })
		await vm.runContentSearch()
		expect(vm.contentSearchNote).toBe('2 documents have not been read yet and cannot be found by content')
		await vm.readUnreadDocuments()
		expect(readListFiles).toHaveBeenCalledWith([2, 3])
		expect(searchListContent).toHaveBeenCalledTimes(2)
		expect(vm.contentReading).toBe(false)
		expect(vm.contentSearchNote).toBe('1 document could not be read')
	})

	it('schedules one search per pause, and none in name mode or without words', () => {
		vi.useFakeTimers()
		searchListContent.mockResolvedValue({ available: true, failed: false, hits: [], unread: [] })
		const vm = page({ searchQuery: 'brief' })
		vm.scheduleContentSearch()
		vi.advanceTimersByTime(1000)
		expect(searchListContent).not.toHaveBeenCalled()

		vm.setSearchMode('content')
		vm.scheduleContentSearch()
		vi.advanceTimersByTime(1000)
		expect(searchListContent).toHaveBeenCalledTimes(1)

		vm.searchQuery = '  '
		vm.scheduleContentSearch()
		vi.advanceTimersByTime(1000)
		expect(searchListContent).toHaveBeenCalledTimes(1)
		vm.setSearchMode('other')
		expect(vm.searchMode).toBe('name')
		expect(vm.searchModes.map((mode) => mode.key)).toEqual(['name', 'content'])
		vi.useRealTimers()
	})
})

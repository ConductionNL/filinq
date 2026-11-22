/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'

const post = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/axios', () => ({ default: { post } }))
vi.mock('@nextcloud/router', () => ({
	generateUrl: (url, params = {}) => '/index.php' + url.replace(/\{(\w+)\}/g, (_, k) => String(params[k])),
}))

const { MAX_FILES, listedFileIds, nameThenContent, readListFiles, searchListContent } = await import('../../src/services/listContentSearch.js')

const docs = [
	{ fileId: 1, fileName: 'Lindenlaan situatie.pdf' },
	{ fileId: 2, fileName: 'Brief.pdf' },
	{ fileId: 3, fileName: 'Besluit.pdf' },
	{ fileId: 4, fileName: 'Dossier', isFolder: true },
]

beforeEach(() => {
	post.mockReset()
})

describe('listed file ids', () => {
	it('lists each file once, leaves folders and bad ids out, and stops at the maximum', () => {
		expect(listedFileIds([...docs, { fileId: 2 }, { fileId: 'x' }, null])).toEqual([1, 2, 3])
		const many = Array.from({ length: MAX_FILES + 5 }, (_, i) => ({ fileId: i + 1 }))
		expect(listedFileIds(many)).toHaveLength(MAX_FILES)
	})
})

describe('search list content', () => {
	it('asks the server about the listed files and keeps only hits among them', async () => {
		post.mockResolvedValue({ data: { available: true, hits: [{ fileId: 2, rank: 0.9, snippet: 'aan de Lindenlaan' }, { fileId: 99, snippet: 'x' }], unread: [3, 98] } })
		const answer = await searchListContent(' Lindenlaan ', docs)
		expect(post).toHaveBeenCalledWith('/index.php/apps/filinq/api/documents/content-search', { words: 'Lindenlaan', fileIds: [1, 2, 3] })
		expect(answer).toEqual({ available: true, failed: false, hits: [{ fileId: 2, snippet: 'aan de Lindenlaan' }], unread: [3] })
	})

	it('passes on that the server has no content search', async () => {
		post.mockResolvedValue({ data: { available: false, hits: [], unread: [] } })
		expect((await searchListContent('a', docs)).available).toBe(false)
	})

	it('says it failed when the server could not be asked', async () => {
		post.mockRejectedValue(new Error('offline'))
		expect(await searchListContent('a', docs)).toEqual({ available: false, failed: true, hits: [], unread: [] })
	})

	it('asks nothing without words or without files', async () => {
		expect(await searchListContent('  ', docs)).toEqual({ available: true, failed: false, hits: [], unread: [] })
		expect(await searchListContent('a', [])).toEqual({ available: true, failed: false, hits: [], unread: [] })
		expect(post).not.toHaveBeenCalled()
	})
})

describe('name then content', () => {
	it('lists name matches first, then content matches in rank order, each once, with their snippet', () => {
		const hits = [{ fileId: 3, snippet: 'de Lindenlaan 4' }, { fileId: 1, snippet: 'also a name match' }, { fileId: 2, snippet: 'aan de Lindenlaan' }, { fileId: 3, snippet: 'again' }, { fileId: 77, snippet: 'not listed' }]
		const { rows, snippets } = nameThenContent(docs, 'lindenlaan', hits)
		expect(rows.map((doc) => doc.fileId)).toEqual([1, 3, 2])
		expect(snippets).toEqual({ 3: 'de Lindenlaan 4', 2: 'aan de Lindenlaan' })
	})
})

describe('read list files', () => {
	it('asks OpenRegister to read each file and counts the failures', async () => {
		post.mockResolvedValueOnce({}).mockRejectedValueOnce(new Error('404'))
		expect(await readListFiles([5, 6])).toEqual({ read: 1, failed: 1 })
		expect(post.mock.calls.map((call) => call[0])).toEqual([
			'/index.php/apps/openregister/api/files/5/extract',
			'/index.php/apps/openregister/api/files/6/extract',
		])
	})
})

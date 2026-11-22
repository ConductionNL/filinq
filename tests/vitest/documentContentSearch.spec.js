/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/work-content-search/tasks.md#task-2.2
 */
import { afterEach, describe, expect, it, vi } from 'vitest'
import { contentSearchQuery, contentUnavailable, markedSnippet, readFiles, unreadFileIds } from '../../src/integrations/documentContentSearch.js'

vi.mock('@nextcloud/router', () => ({
	generateUrl: (url, params = {}) => '/index.php' + url.replace(/\{(\w+)\}/g, (_, k) => String(params[k])),
}))

afterEach(() => {
	delete globalThis.fetch
})

describe('content search query', () => {
	it('asks the content mode with the trimmed words', () => {
		expect(contentSearchQuery('  parkeren  ')).toEqual({ mode: 'content', search: 'parkeren' })
	})

	it('asks nothing when nothing was typed', () => {
		expect(contentSearchQuery('   ')).toBeNull()
		expect(contentSearchQuery(undefined)).toBeNull()
	})
})

describe('marked snippet', () => {
	it('marks every place a word occurs, ignoring case, and keeps the text as written', () => {
		expect(markedSnippet('De Parkeervergunning en parkeervergunning', 'parkeervergunning')).toEqual([
			{ text: 'De ', marked: false },
			{ text: 'Parkeervergunning', marked: true },
			{ text: ' en ', marked: false },
			{ text: 'parkeervergunning', marked: true },
		])
	})

	it('marks each of several words', () => {
		expect(markedSnippet('bezoek op straat', 'straat bezoek')).toEqual([
			{ text: 'bezoek', marked: true },
			{ text: ' op ', marked: false },
			{ text: 'straat', marked: true },
		])
	})

	it('treats the words as text, not as a pattern', () => {
		expect(markedSnippet('kosten (a+b) totaal', '(a+b)')).toEqual([
			{ text: 'kosten ', marked: false },
			{ text: '(a+b)', marked: true },
			{ text: ' totaal', marked: false },
		])
	})

	it('drops the markup the server put in the snippet and marks the words itself', () => {
		expect(markedSnippet('aan de <mark>Lindenlaan</mark> <b>4</b>', 'lindenlaan')).toEqual([
			{ text: 'aan de ', marked: false },
			{ text: 'Lindenlaan', marked: true },
			{ text: ' 4', marked: false },
		])
	})

	it('keeps the whole snippet plain without words, and nothing without a snippet', () => {
		expect(markedSnippet('een zin', '')).toEqual([{ text: 'een zin', marked: false }])
		expect(markedSnippet('', 'zin')).toEqual([])
		expect(markedSnippet(null, 'zin')).toEqual([])
	})
})

describe('content unavailable', () => {
	it('is true only when the answer says content could not be searched', () => {
		expect(contentUnavailable({ content: { available: false, unread: 0 } })).toBe(true)
		expect(contentUnavailable({ content: { available: true, unread: 2 } })).toBe(false)
		expect(contentUnavailable({ results: [] })).toBe(false)
		expect(contentUnavailable(null)).toBe(false)
	})
})

describe('unread file ids', () => {
	it('reads the file ids the server has no text for, and nothing else', () => {
		expect(unreadFileIds({ content: { unreadFileIds: [4, '5', 0, -1, 'x', 2.5] } })).toEqual([4, 5])
		expect(unreadFileIds({ content: { unread: 2 } })).toEqual([])
		expect(unreadFileIds(null)).toEqual([])
	})
})

describe('read files', () => {
	it('asks OpenRegister to read each file in turn and counts what was read', async () => {
		const calls = []
		globalThis.fetch = vi.fn(async (url, init) => {
			calls.push({ url, init })
			if (url.includes('/6/')) {
				throw new Error('offline')
			}
			return { ok: !url.includes('/5/') }
		})
		expect(await readFiles([4, 5, 6], 'tok')).toEqual({ read: 1, failed: 2 })
		expect(calls.map((call) => call.url)).toEqual([
			'/index.php/apps/openregister/api/files/4/extract',
			'/index.php/apps/openregister/api/files/5/extract',
			'/index.php/apps/openregister/api/files/6/extract',
		])
		expect(calls[0].init).toEqual({ method: 'POST', headers: { requesttoken: 'tok', Accept: 'application/json' } })
	})

	it('asks nothing for no files', async () => {
		globalThis.fetch = vi.fn()
		expect(await readFiles([], 'tok')).toEqual({ read: 0, failed: 0 })
		expect(globalThis.fetch).not.toHaveBeenCalled()
	})
})

/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/work-content-search/tasks.md#task-2.2
 */
import { describe, expect, it } from 'vitest'
import { contentSearchQuery, contentUnavailable, markedSnippet } from '../../src/integrations/documentContentSearch.js'

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

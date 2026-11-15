/**
 * The redaction review mark: the payload order, the order-independent
 * signature, whether a mark is still current, and the two HTTP calls.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const calls = []
let answer = {}
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url, config) => {
			calls.push(['get', url, config])
			return Promise.resolve({ data: answer })
		},
		post: (url, body) => {
			calls.push(['post', url, body])
			return Promise.resolve({ data: answer })
		},
	},
}))

const {
	fetchReviewMarks,
	markChecked,
	redactionPayload,
	reviewIsCurrent,
	reviewSignature,
} = await import('../../src/services/reviewMark.js')

beforeEach(() => {
	calls.length = 0
	answer = {}
})

describe('redactionPayload', () => {
	it('keeps type, value and confidence, longest value first', () => {
		expect(
			redactionPayload([
				{ type: 'PER', value: 'Jan', confidence: 0.9, extra: 1 },
				{ type: 'ADR', value: 'Hoofdstraat 1', confidence: 0.8 },
				{ type: 'X' },
			]),
		).toEqual([
			{ type: 'ADR', value: 'Hoofdstraat 1', confidence: 0.8 },
			{ type: 'PER', value: 'Jan', confidence: 0.9 },
			{ type: 'X', value: undefined, confidence: undefined },
		])
	})

	it('is empty for no entities', () => {
		expect(redactionPayload(null)).toEqual([])
	})
})

describe('reviewSignature and reviewIsCurrent', () => {
	it('does not depend on the order of the entities', () => {
		const a = [{ type: 'PER', value: 'Jan' }, { type: 'ADR', value: 'Dorp' }]
		const b = [{ type: 'ADR', value: 'Dorp' }, { type: 'PER', value: 'Jan' }]
		expect(reviewSignature(a)).toBe(reviewSignature(b))
		expect(reviewSignature(a)).toBe('["ADR:Dorp","PER:Jan"]')
	})

	it('signs missing fields as empty', () => {
		expect(reviewSignature([{}])).toBe('[":"]')
		expect(reviewSignature(undefined)).toBe('[]')
	})

	it('is current only for a mark over the same entities', () => {
		const entities = [{ type: 'PER', value: 'Jan' }]
		const mark = { signature: reviewSignature(entities) }
		expect(reviewIsCurrent(mark, entities)).toBe(true)
		expect(reviewIsCurrent(mark, [{ type: 'PER', value: 'Piet' }])).toBe(false)
		expect(reviewIsCurrent(null, entities)).toBe(false)
	})
})

describe('markChecked', () => {
	it('posts the entities and returns the mark with its signature', async () => {
		answer = { checkedBy: 'anna', checkedAt: '2026-11-13T10:00:00Z' }
		const entities = [{ type: 'PER', value: 'Jan' }]

		const mark = await markChecked(42, entities)

		expect(calls).toEqual([
			['post', '/index.php/apps/filinq/api/redaction/documents/42/checked', { entities }],
		])
		expect(mark).toEqual({
			checkedBy: 'anna',
			checkedAt: '2026-11-13T10:00:00Z',
			signature: '["PER:Jan"]',
		})
	})

	it('answers empty strings when the server says nothing', async () => {
		answer = null
		const mark = await markChecked(1, [])
		expect(mark).toEqual({ checkedBy: '', checkedAt: '', signature: '[]' })
	})
})

describe('fetchReviewMarks', () => {
	it('does not call the server for no files', async () => {
		expect(await fetchReviewMarks([])).toEqual({})
		expect(await fetchReviewMarks()).toEqual({})
		expect(calls).toEqual([])
	})

	it('asks for the file ids comma-separated and answers the marks', async () => {
		answer = { marks: { 1: { checkedBy: 'anna' } } }

		const marks = await fetchReviewMarks([1, 2])

		expect(calls).toEqual([
			['get', '/index.php/apps/filinq/api/redaction/documents/checked', { params: { fileIds: '1,2' } }],
		])
		expect(marks).toEqual({ 1: { checkedBy: 'anna' } })
	})

	it('answers no marks when the server sends none', async () => {
		answer = {}
		expect(await fetchReviewMarks([3])).toEqual({})
	})
})

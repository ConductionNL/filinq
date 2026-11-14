/**
 * Tests for the review mark helpers.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import axios from '@nextcloud/axios'
import {
	fetchReviewMarks,
	redactionPayload,
	reviewIsCurrent,
	reviewSignature,
} from './reviewMark.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn(), post: jest.fn() },
}))
jest.mock('@nextcloud/router', () => ({
	generateUrl: (path) => path,
}))

// @spec openspec/changes/archive/2026-10-30-anonymise-asks-for-the-review-mark/tasks.md#task-4
describe('redactionPayload', () => {
	it('puts the longest value first so a short span cannot eat a longer one', () => {
		const payload = redactionPayload([
			{ type: 'PERSON', value: 'Claudia Fischer', confidence: 0.9, extra: 1 },
			{ type: 'PERSON', value: 'Mevrouw Claudia Fischer', confidence: 0.8 },
		])
		expect(payload.map((e) => e.value)).toEqual([
			'Mevrouw Claudia Fischer',
			'Claudia Fischer',
		])
		expect(Object.keys(payload[0])).toEqual(['type', 'value', 'confidence'])
	})
})

// @spec openspec/changes/archive/2026-10-30-anonymise-asks-for-the-review-mark/tasks.md#task-4
describe('reviewSignature and reviewIsCurrent', () => {
	const a = { type: 'PERSON', value: 'Jan', confidence: 0.9 }
	const b = { type: 'BSN', value: '123456782', confidence: 0.99 }

	it('names the same findings the same, in any order and at any score', () => {
		expect(reviewSignature([a, b])).toBe(
			reviewSignature([b, { ...a, confidence: 0.4 }]),
		)
	})

	it('treats a mark over other findings as not current', () => {
		const mark = { signature: reviewSignature([a]) }
		expect(reviewIsCurrent(mark, [a])).toBe(true)
		expect(reviewIsCurrent(mark, [a, b])).toBe(false)
		expect(reviewIsCurrent(null, [a])).toBe(false)
	})
})

// @spec openspec/changes/archive/2026-10-30-anonymise-asks-for-the-review-mark/tasks.md#task-5
describe('fetchReviewMarks', () => {
	it('asks for the listed files in one call and returns the marks', async () => {
		axios.get.mockResolvedValue({
			data: { marks: { 7: { checkedBy: 'ingrid' } } },
		})

		const marks = await fetchReviewMarks([7, 8])

		expect(axios.get).toHaveBeenCalledWith(
			'/apps/filinq/api/redaction/documents/checked',
			{ params: { fileIds: '7,8' } },
		)
		expect(marks).toEqual({ 7: { checkedBy: 'ingrid' } })
	})

	it('asks nothing for an empty page', async () => {
		axios.get.mockClear()
		expect(await fetchReviewMarks([])).toEqual({})
		expect(axios.get).not.toHaveBeenCalled()
	})
})

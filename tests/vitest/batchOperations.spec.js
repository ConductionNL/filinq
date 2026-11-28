/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-cooperative-cancellation-req-ddras-004
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@nextcloud/router', () => ({
	generateUrl: (path) => `/index.php${path}`,
}))

const post = vi.fn()
const get = vi.fn()
vi.mock('@nextcloud/axios', () => ({
	default: { get: (...args) => get(...args), post: (...args) => post(...args) },
}))

const {
	batchUrl,
	cancelBatch,
	canCancel,
	canResume,
	getBatchStatus,
	qaSamples,
	resumeBatch,
} = await import('../../src/services/batchOperations.js')

describe('batch operations', () => {
	beforeEach(() => {
		post.mockReset()
		get.mockReset()
	})

	it('builds the batch endpoint URLs with an encoded id', () => {
		expect(batchUrl('a b', 'cancel')).toBe('/index.php/apps/filinq/api/anonymization/batch/a%20b/cancel')
	})

	it('offers cancel only while the batch can still stop, and resume only after it stopped', () => {
		expect(canCancel('anonymizing')).toBe(true)
		expect(canCancel('extracting')).toBe(true)
		expect(canCancel('completed')).toBe(false)
		expect(canCancel('cancelling')).toBe(false)
		expect(canResume('error')).toBe(true)
		expect(canResume('cancelled')).toBe(true)
		expect(canResume('anonymizing')).toBe(false)
		expect(canResume('completed')).toBe(false)
	})

	it('resumes a completed batch only while it has files in error', () => {
		expect(canResume('completed', [{ status: 'anonymized' }, { status: 'error' }])).toBe(true)
		expect(canResume('completed', [{ status: 'anonymized' }, { status: 'prohibitionViolation' }])).toBe(false)
		expect(canResume('completed', null)).toBe(false)
		expect(canResume('review', [{ status: 'error' }])).toBe(false)
	})

	it('lists the QA-sampled files with their outcome, pending when none is recorded', () => {
		const samples = qaSamples([
			{ fileId: 1, fileName: 'a.pdf', status: 'anonymized' },
			{ fileId: 2, fileName: 'b.pdf', relativePath: 'stukken/b.pdf', qaSampled: true, qaOutcome: 'corrected' },
			{ fileId: 3, fileName: 'c.pdf', qaSampled: true },
		])

		expect(samples).toEqual([
			{ fileId: 2, fileName: 'stukken/b.pdf', outcome: 'corrected' },
			{ fileId: 3, fileName: 'c.pdf', outcome: 'pending' },
		])
		expect(qaSamples(undefined)).toEqual([])
	})

	it('posts cancel and resume to the batch, and reads its status', async () => {
		post.mockResolvedValue({ data: { batchStatus: 'cancelling' } })
		get.mockResolvedValue({ data: { batchStatus: 'anonymizing', progressPercent: 40 } })

		expect(await cancelBatch('b-1')).toEqual({ batchStatus: 'cancelling' })
		expect(post).toHaveBeenLastCalledWith('/index.php/apps/filinq/api/anonymization/batch/b-1/cancel')
		await resumeBatch('b-1')
		expect(post).toHaveBeenLastCalledWith('/index.php/apps/filinq/api/anonymization/batch/b-1/resume')
		expect((await getBatchStatus('b-1')).progressPercent).toBe(40)
		expect(get).toHaveBeenCalledWith('/index.php/apps/filinq/api/anonymization/batch/b-1/status')
	})
})

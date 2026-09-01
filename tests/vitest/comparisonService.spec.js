// @vitest-environment jsdom
/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Unit tests for src/services/comparisonService.js: it POSTs the two subjects
 * to the comparison endpoint and returns the response payload. @nextcloud/axios
 * and @nextcloud/router are mocked.
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const postMock = vi.fn()

vi.mock('@nextcloud/axios', () => ({
	default: { post: (...args) => postMock(...args) },
}))

vi.mock('@nextcloud/router', () => ({
	generateUrl: (path) => `http://localhost/index.php${path}`,
}))

const { compareDocuments } = await import('../../src/services/comparisonService.js')

describe('compareDocuments', () => {
	beforeEach(() => {
		postMock.mockReset()
	})

	it('POSTs both subjects to the comparison endpoint and returns the payload', async () => {
		const payload = {
			hunks: [],
			summary: { changedHunks: 0 },
			crossFormat: false,
		}
		postMock.mockResolvedValue({ data: payload })

		const result = await compareDocuments(
			{ fileId: 42 },
			{ fileId: 88, versionTimestamp: 123 },
		)

		expect(postMock).toHaveBeenCalledTimes(1)
		const [url, body] = postMock.mock.calls[0]
		expect(url).toContain('/apps/filinq/api/comparison/compare')
		expect(body).toEqual({
			left: { fileId: 42 },
			right: { fileId: 88, versionTimestamp: 123 },
		})
		expect(result).toBe(payload)
	})

	it('propagates errors from the endpoint', async () => {
		postMock.mockRejectedValue(new Error('boom'))
		await expect(compareDocuments({ fileId: 1 }, { fileId: 2 })).rejects.toThrow(
			'boom',
		)
	})
})

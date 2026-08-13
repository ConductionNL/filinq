// @vitest-environment jsdom
/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Unit tests for src/services/validationService.js. @nextcloud/axios and
 * @nextcloud/router are mocked.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'

const postMock = vi.fn()

vi.mock('@nextcloud/axios', () => ({
	default: { post: (...args) => postMock(...args) },
}))

vi.mock('@nextcloud/router', () => ({
	generateUrl: (path) => `http://localhost/index.php${path}`,
}))

const { validateFile, verdictColor } =
	await import('../../src/services/validationService.js')

describe('validateFile', () => {
	beforeEach(() => postMock.mockReset())

	it('POSTs fileId (and documentType when given) and returns the verdict', async () => {
		const payload = { validationStatus: 'warnings', validationFindings: [] }
		postMock.mockResolvedValue({ data: payload })

		const result = await validateFile(42, 'factuur')

		const [url, body] = postMock.mock.calls[0]
		expect(url).toContain('/apps/docudesk/api/validation/validate')
		expect(body).toEqual({ fileId: 42, documentType: 'factuur' })
		expect(result).toBe(payload)
	})

	it('omits documentType when not provided', async () => {
		postMock.mockResolvedValue({ data: {} })
		await validateFile(7)
		expect(postMock.mock.calls[0][1]).toEqual({ fileId: 7 })
	})
})

describe('verdictColor', () => {
	it('maps verdicts to colour tokens', () => {
		expect(verdictColor('passed')).toBe('success')
		expect(verdictColor('warnings')).toBe('warning')
		expect(verdictColor('failed')).toBe('error')
		expect(verdictColor('')).toBe('info')
	})
})

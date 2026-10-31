/**
 * SPDX-FileCopyrightText: 2026 Conduction / Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Unit tests for the step-up flow of the signer identity rails
 * (src/services/signerStepUp.js): reading the hint off a refusal, starting
 * the provider, and reading the broker's return.
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const post = vi.fn()

vi.mock('@nextcloud/axios', () => ({
	default: {
		post: (...args) => post(...args),
	},
}))

const {
	assuranceFloor,
	assuranceLevelsFrom,
	stepUpHint,
	startStepUp,
	stepUpReturn,
} = await import('../../src/services/signerStepUp.js')

beforeEach(() => {
	post.mockReset()
})

describe('the signer step-up flow', () => {
	it('reads the hint off a 403 from the sign endpoint', () => {
		const error = {
			response: {
				status: 403,
				data: {
					error: 'Failed to sign document',
					stepUp: {
						required: true,
						reason: 'insufficient',
						requiredAssurance: 'substantial',
						provider: 'oidc-broker',
					},
				},
			},
		}
		expect(stepUpHint(error)).toEqual({
			required: true,
			reason: 'insufficient',
			requiredAssurance: 'substantial',
			provider: 'oidc-broker',
		})
	})

	it('reads the hint off a signing-folder result', () => {
		expect(
			stepUpHint({
				signed: false,
				stepUp: { required: true, requiredAssurance: 'high' },
			})?.requiredAssurance,
		).toBe('high')
	})

	it('finds no hint on an ordinary refusal', () => {
		expect(
			stepUpHint({
				response: {
					status: 403,
					data: { error: 'Failed to sign document' },
				},
			}),
		).toBeNull()
		expect(
			stepUpHint({
				signed: false,
				reason: 'No signature is pending from you on this document',
			}),
		).toBeNull()
		expect(stepUpHint(null)).toBeNull()
	})

	it('starts the provider for the signer on the request', async () => {
		post.mockResolvedValueOnce({
			data: {
				type: 'redirect',
				url: 'https://broker.example.nl/authorize?x=1',
				requiredAssurance: 'substantial',
			},
		})

		const challenge = await startStepUp('req 1', 'signer-1')

		expect(post).toHaveBeenCalledWith(
			'/index.php/apps/filinq/api/signing/requests/req%201/identity',
			{ signerId: 'signer-1' },
		)
		expect(challenge.type).toBe('redirect')
		expect(challenge.url).toBe('https://broker.example.nl/authorize?x=1')
	})

	it('reads the return from the broker callback', () => {
		expect(stepUpReturn({ stepUp: 'done', signerId: 'signer-1' })).toEqual({
			status: 'done',
			signerId: 'signer-1',
		})
		expect(stepUpReturn({ stepUp: 'failed' })).toEqual({
			status: 'failed',
			signerId: '',
		})
		expect(stepUpReturn({ stepUp: 'whatever' })).toEqual({
			status: null,
			signerId: '',
		})
		expect(stepUpReturn(undefined)).toEqual({ status: null, signerId: '' })
	})

	it('knows the floor of each signature level', () => {
		expect(assuranceFloor('SES')).toBe('low')
		expect(assuranceFloor('AdES')).toBe('substantial')
		expect(assuranceFloor('QES')).toBe('high')
		expect(assuranceFloor('unknown')).toBe('low')
	})

	it('offers only the levels at or above the floor', () => {
		expect(assuranceLevelsFrom('SES')).toEqual(['low', 'substantial', 'high'])
		expect(assuranceLevelsFrom('AdES')).toEqual(['substantial', 'high'])
		expect(assuranceLevelsFrom('QES')).toEqual(['high'])
	})
})

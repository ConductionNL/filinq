/**
 * The signing store: pending and completed getters, each request's endpoint, state and failure answer, and the step-up hint of a refused signature.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude unit tests of an existing store's request and state handling
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const axios = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() }))
vi.mock('@nextcloud/axios', () => ({ default: axios }))

/**
 * An HTTP error as axios raises it.
 *
 * @param {string} message The transport message.
 * @param {string} [serverError] The server's own error text.
 * @return {Error}
 */
function httpError(message, serverError) {
	const err = new Error(message)
	if (serverError) {
		err.response = { data: { error: serverError } }
	}
	return err
}

beforeEach(() => {
	setActivePinia(createPinia())
	for (const fn of Object.values(axios)) {
		fn.mockReset()
	}
	vi.spyOn(console, 'error').mockImplementation(() => {})
})

const { useSigningStore } = await import('../../src/store/modules/signing.js')

const base = '/index.php/apps/filinq/api/signing'

describe('signing store', () => {
	it('splits the requests into pending and completed', () => {
		const store = useSigningStore()
		store.signingRequests = [{ id: 1, status: 'PENDING' }, { id: 2, status: 'COMPLETED' }, { id: 3, status: 'DECLINED' }]

		expect(store.pendingRequests.map((r) => r.id)).toEqual([1])
		expect(store.completedRequests.map((r) => r.id)).toEqual([2])
	})

	it('loads the requests and one request, keeping the transport message on failure', async () => {
		const store = useSigningStore()
		axios.get.mockResolvedValueOnce({ data: [{ id: 1 }] }).mockResolvedValueOnce({ data: { id: 1 } })

		await store.fetchSigningRequests()
		expect(store.signingRequests).toEqual([{ id: 1 }])
		expect(await store.fetchSigningRequest(1)).toEqual({ id: 1 })
		expect(store.signingRequest).toEqual({ id: 1 })
		expect(axios.get.mock.calls.map((c) => c[0])).toEqual([`${base}/requests`, `${base}/requests/1`])

		axios.get.mockRejectedValueOnce(httpError('Network Error')).mockRejectedValueOnce(httpError('404'))
		await store.fetchSigningRequests()
		expect(store.error).toBe('Network Error')
		expect(await store.fetchSigningRequest(9)).toBeNull()
		expect(store.error).toBe('404')
		expect(store.loading).toBe(false)
	})

	it('appends a created request, or answers null', async () => {
		const store = useSigningStore()
		axios.post.mockResolvedValueOnce({ data: { id: 5 } })

		expect(await store.createSigningRequest({ fileId: 3 })).toEqual({ id: 5 })
		expect(axios.post).toHaveBeenCalledWith(`${base}/requests`, { fileId: 3 })
		expect(store.signingRequests).toEqual([{ id: 5 }])

		axios.post.mockRejectedValueOnce(httpError('422'))
		expect(await store.createSigningRequest({})).toBeNull()
		expect(store.signingRequests).toHaveLength(1)
	})

	it('signs for one signer, and keeps the step-up hint of a refused signature', async () => {
		const store = useSigningStore()
		axios.post.mockResolvedValueOnce({ data: { signed: true } })
		expect(await store.signDocument('r1', 's1')).toEqual({ signed: true })
		expect(axios.post).toHaveBeenCalledWith(`${base}/requests/r1/sign`, { signerId: 's1' })
		expect(store.stepUp).toBeNull()

		const refused = httpError('Request failed')
		refused.response = { data: { stepUp: { required: true, requiredAssurance: 'high' } } }
		axios.post.mockRejectedValueOnce(refused)
		expect(await store.signDocument('r1', 's1')).toBeNull()
		expect(store.error).toBe('Request failed')
		expect(store.stepUp).toEqual({ required: true, requiredAssurance: 'high' })

		axios.post.mockResolvedValueOnce({ data: {} })
		await store.signDocument('r1', 's1')
		expect(store.stepUp).toBeNull()

		axios.post.mockRejectedValueOnce(httpError('500'))
		await store.signDocument('r1', 's1')
		expect(store.stepUp).toBeNull()
	})

	it('declines with a reason, cancels, and bulk signs', async () => {
		const store = useSigningStore()
		axios.post.mockResolvedValueOnce({ data: { declined: true } }).mockResolvedValueOnce({ data: { signed: 2 } })
		axios.delete.mockResolvedValueOnce({})

		expect(await store.declineRequest('r1', 's1', 'wrong file')).toEqual({ declined: true })
		expect(axios.post).toHaveBeenCalledWith(`${base}/requests/r1/decline`, { signerId: 's1', reason: 'wrong file' })
		await store.cancelRequest('r1')
		expect(axios.delete).toHaveBeenCalledWith(`${base}/requests/r1`)
		expect(await store.bulkSign(['r1', 'r2'])).toEqual({ signed: 2 })
		expect(axios.post).toHaveBeenLastCalledWith(`${base}/bulk`, { requestIds: ['r1', 'r2'] })
		expect(store.error).toBeNull()
	})

	it('records failed declines, cancels and bulk signs', async () => {
		const store = useSigningStore()
		axios.post.mockRejectedValueOnce(httpError('decline failed')).mockRejectedValueOnce(httpError('bulk failed'))
		axios.delete.mockRejectedValueOnce(httpError('cancel failed'))

		expect(await store.declineRequest('r1', 's1', 'x')).toBeNull()
		expect(store.error).toBe('decline failed')
		await store.cancelRequest('r1')
		expect(store.error).toBe('cancel failed')
		expect(await store.bulkSign([])).toBeNull()
		expect(store.error).toBe('bulk failed')
		expect(store.loading).toBe(false)
	})

	it('verifies a document and reads the audit trail, answering null and none on failure', async () => {
		const store = useSigningStore()
		axios.get.mockResolvedValueOnce({ data: { valid: true } }).mockResolvedValueOnce({ data: [{ event: 'signed' }] })

		expect(await store.verifyDocument(42)).toEqual({ valid: true })
		expect(store.verificationResult).toEqual({ valid: true })
		expect(await store.fetchAuditTrail('r1')).toEqual([{ event: 'signed' }])
		expect(store.auditTrail).toEqual([{ event: 'signed' }])
		expect(axios.get.mock.calls.map((c) => c[0])).toEqual([`${base}/verify/42`, `${base}/requests/r1/audit`])

		axios.get.mockRejectedValueOnce(httpError('verify failed')).mockRejectedValueOnce(httpError('audit failed'))
		expect(await store.verifyDocument(42)).toBeNull()
		expect(store.error).toBe('verify failed')
		expect(await store.fetchAuditTrail('r1')).toEqual([])
		expect(store.error).toBe('audit failed')
	})
})

/**
 * The consent store: status getters and counts, and each request's state, local list update and failure.
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

const { useConsentStore } = await import('../../src/store/modules/consent.js')

const consents = [
	{ id: 'c1', consentStatus: 'pending' },
	{ id: 'c2', consentStatus: 'consent_given' },
	{ uuid: 'c3', consentStatus: 'objection_received' },
	{ id: 'c4', consentStatus: 'no_response' },
	{ id: 'c5', consentStatus: 'anonymized' },
	{ id: 'c6', consentStatus: 'pending' },
]

describe('consent store', () => {
	it('groups the consents by status and counts every status', () => {
		const store = useConsentStore()
		store.consents = consents

		expect(store.pendingConsents.map((c) => c.id)).toEqual(['c1', 'c6'])
		expect(store.approvedConsents.map((c) => c.id)).toEqual(['c2'])
		expect(store.objectedConsents.map((c) => c.uuid)).toEqual(['c3'])
		expect(store.consentStats).toEqual({ total: 6, pending: 2, approved: 1, objected: 1, noResponse: 1, anonymized: 1 })
	})

	it('loads the consent list and keeps the transport message when that fails', async () => {
		const store = useConsentStore()
		axios.get.mockResolvedValueOnce({ data: consents })

		await store.fetchConsents()
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/consents')
		expect(store.consents).toEqual(consents)
		expect(store.loading).toBe(false)

		axios.get.mockRejectedValueOnce(httpError('Network Error', 'ignored'))
		await store.fetchConsents()
		expect(store.error).toBe('Network Error')
		expect(store.consents).toEqual(consents)
		expect(store.loading).toBe(false)
	})

	it('reads one consent into the item, or answers null', async () => {
		const store = useConsentStore()
		axios.get.mockResolvedValueOnce({ data: consents[0] })

		expect(await store.fetchConsent('c1')).toEqual(consents[0])
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/consents/c1')
		expect(store.consentItem).toEqual(consents[0])

		axios.get.mockRejectedValueOnce(httpError('404'))
		expect(await store.fetchConsent('nope')).toBeNull()
		expect(store.error).toBe('404')
	})

	it('updates a consent in place by id or uuid and prefers the server error text', async () => {
		const store = useConsentStore()
		store.consents = consents.map((c) => ({ ...c }))
		const updated = { uuid: 'c3', consentStatus: 'consent_given' }
		axios.put.mockResolvedValueOnce({ data: updated })

		expect(await store.updateConsent('c3', { consentStatus: 'consent_given' })).toEqual(updated)
		expect(axios.put).toHaveBeenCalledWith('/index.php/apps/filinq/api/consents/c3', { consentStatus: 'consent_given' })
		expect(store.consents[2]).toEqual(updated)
		expect(store.consentItem).toEqual(updated)

		axios.put.mockResolvedValueOnce({ data: { id: 'zz' } })
		await store.updateConsent('zz', {})
		expect(store.consents).toHaveLength(6)

		axios.put.mockRejectedValueOnce(httpError('Request failed', 'Consent is locked'))
		expect(await store.updateConsent('c1', {})).toBeNull()
		expect(store.error).toBe('Consent is locked')

		axios.put.mockRejectedValueOnce(httpError('Timeout'))
		await store.updateConsent('c1', {})
		expect(store.error).toBe('Timeout')
	})

	it('lists the consents of one document, or none', async () => {
		const store = useConsentStore()
		axios.get.mockResolvedValueOnce({ data: [consents[0]] })

		expect(await store.fetchConsentsByDocument(42)).toEqual([consents[0]])
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/consents/document/42')

		axios.get.mockRejectedValueOnce(httpError('500'))
		expect(await store.fetchConsentsByDocument(42)).toEqual([])
		expect(store.error).toBe('500')
	})

	it('appends a created consent, or answers null', async () => {
		const store = useConsentStore()
		axios.post.mockResolvedValueOnce({ data: { id: 'new' } })

		expect(await store.createConsent({ documentId: 1 })).toEqual({ id: 'new' })
		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/consents', { documentId: 1 })
		expect(store.consents).toEqual([{ id: 'new' }])

		axios.post.mockRejectedValueOnce(httpError('422'))
		expect(await store.createConsent({})).toBeNull()
		expect(store.consents).toHaveLength(1)
		expect(store.loading).toBe(false)
	})

	it('sets and clears the current item', () => {
		const store = useConsentStore()
		store.setConsentItem(consents[1])
		expect(store.consentItem).toEqual(consents[1])
		store.clearConsentItem()
		expect(store.consentItem).toBeNull()
	})
})

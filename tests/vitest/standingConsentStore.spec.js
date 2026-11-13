/**
 * The standing consent store: active counts, and each request's state, local list update and rethrown failure.
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

const { useStandingConsentStore } = await import('../../src/store/modules/standingConsent.js')

/**
 * A fresh list with each id form.
 *
 * @return {Array<object>}
 */
function list() {
	return [
		{ '@self': { id: 's1' } },
		{ id: 's2', active: false },
		{ uuid: 's3', active: true },
	]
}

describe('standing consent store', () => {
	it('counts active and inactive standing consents', () => {
		const store = useStandingConsentStore()
		store.standingConsents = list()

		expect(store.standingConsentStats).toEqual({ total: 3, active: 2, inactive: 1 })
	})

	it('loads the list and one item, and keeps the server error text', async () => {
		const store = useStandingConsentStore()
		axios.get.mockResolvedValueOnce({ data: list() }).mockResolvedValueOnce({ data: { id: 's2' } })

		await store.fetchStandingConsents()
		expect(store.standingConsents).toHaveLength(3)
		expect(await store.fetchStandingConsent('s2')).toEqual({ id: 's2' })
		expect(store.standingConsentItem).toEqual({ id: 's2' })
		expect(axios.get.mock.calls.map((c) => c[0])).toEqual([
			'/index.php/apps/filinq/api/policy/standing-consents',
			'/index.php/apps/filinq/api/policy/standing-consents/s2',
		])

		axios.get.mockRejectedValueOnce(httpError('Request failed', 'Forbidden'))
		await store.fetchStandingConsents()
		expect(store.error).toBe('Forbidden')

		axios.get.mockRejectedValueOnce(httpError('Timeout'))
		expect(await store.fetchStandingConsent('s9')).toBeNull()
		expect(store.error).toBe('Timeout')
		expect(store.loading).toBe(false)
	})

	it('creates, updates by any id form and deletes', async () => {
		const store = useStandingConsentStore()
		store.standingConsents = list()

		axios.post.mockResolvedValueOnce({ data: { id: 's4' } })
		expect(await store.createStandingConsent({ scope: 'x' })).toEqual({ id: 's4' })
		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/policy/standing-consents', { scope: 'x' })

		axios.put.mockResolvedValueOnce({ data: { uuid: 's3', active: false } })
		await store.updateStandingConsent('s3', { active: false })
		expect(axios.put).toHaveBeenCalledWith('/index.php/apps/filinq/api/policy/standing-consents/s3', { active: false })
		expect(store.standingConsents[2]).toEqual({ uuid: 's3', active: false })
		expect(store.standingConsentItem).toEqual({ uuid: 's3', active: false })

		axios.delete.mockResolvedValueOnce({})
		await store.deleteStandingConsent('s1')
		expect(store.standingConsents.map((s) => s['@self']?.id || s.id || s.uuid)).toEqual(['s2', 's3', 's4'])
	})

	it('rethrows a failed create, update or delete after recording it', async () => {
		const store = useStandingConsentStore()
		store.standingConsents = list()

		for (const [method, call] of [
			['post', () => store.createStandingConsent({})],
			['put', () => store.updateStandingConsent('s1', {})],
			['delete', () => store.deleteStandingConsent('s1')],
		]) {
			const failure = httpError('Request failed', `${method} refused`)
			axios[method].mockRejectedValueOnce(failure)
			await expect(call()).rejects.toBe(failure)
			expect(store.error).toBe(`${method} refused`)
			expect(store.loading).toBe(false)
		}
		expect(store.standingConsents).toHaveLength(3)
	})

	it('sets and clears the current item', () => {
		const store = useStandingConsentStore()
		store.setStandingConsentItem({ id: 's1' })
		expect(store.standingConsentItem).toEqual({ id: 's1' })
		store.clearStandingConsentItem()
		expect(store.standingConsentItem).toBeNull()
	})
})

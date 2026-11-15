/**
 * The prohibition store: active counts, and each request's state, local list update, fallback and rethrow.
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

const { useProhibitionStore } = await import('../../src/store/modules/prohibition.js')

/**
 * A fresh list with each id form.
 *
 * @return {Array<object>}
 */
function list() {
	return [
		{ '@self': { id: 'p1' }, active: true },
		{ id: 'p2', active: false },
		{ uuid: 'p3' },
	]
}

describe('prohibition store', () => {
	it('counts active and inactive prohibitions, a missing flag counting as active', () => {
		const store = useProhibitionStore()
		store.prohibitions = list()

		expect(store.activeCount).toBe(2)
		expect(store.prohibitionStats).toEqual({ total: 3, active: 2, inactive: 1 })
	})

	it('loads the list and keeps the server error text', async () => {
		const store = useProhibitionStore()
		axios.get.mockResolvedValueOnce({ data: list() })
		await store.fetchProhibitions()
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/policy/prohibitions')
		expect(store.prohibitions).toHaveLength(3)

		axios.get.mockRejectedValueOnce(httpError('Request failed', 'Forbidden'))
		await store.fetchProhibitions()
		expect(store.error).toBe('Forbidden')
		expect(store.loading).toBe(false)
	})

	it('reads one prohibition, or answers null', async () => {
		const store = useProhibitionStore()
		axios.get.mockResolvedValueOnce({ data: { id: 'p2' } })
		expect(await store.fetchProhibition('p2')).toEqual({ id: 'p2' })
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/policy/prohibitions/p2')
		expect(store.prohibitionItem).toEqual({ id: 'p2' })

		axios.get.mockRejectedValueOnce(httpError('404'))
		expect(await store.fetchProhibition('p9')).toBeNull()
	})

	it('creates, updates by any id form, and deletes, rethrowing failures', async () => {
		const store = useProhibitionStore()
		store.prohibitions = list()

		axios.post.mockResolvedValueOnce({ data: { id: 'p4' } })
		await store.createProhibition({ term: 'x' })
		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/policy/prohibitions', { term: 'x' })
		expect(store.prohibitions.at(-1)).toEqual({ id: 'p4' })

		axios.put.mockResolvedValueOnce({ data: { '@self': { id: 'p1' }, active: false } })
		await store.updateProhibition('p1', { active: false })
		expect(store.prohibitions[0]).toEqual({ '@self': { id: 'p1' }, active: false })
		expect(store.prohibitionItem).toEqual({ '@self': { id: 'p1' }, active: false })

		axios.delete.mockResolvedValueOnce({})
		await store.deleteProhibition('p3')
		expect(axios.delete).toHaveBeenCalledWith('/index.php/apps/filinq/api/policy/prohibitions/p3')
		expect(store.prohibitions.map((p) => p['@self']?.id || p.id || p.uuid)).toEqual(['p1', 'p2', 'p4'])

		const failure = httpError('Request failed', 'In use')
		axios.delete.mockRejectedValueOnce(failure)
		await expect(store.deleteProhibition('p2')).rejects.toBe(failure)
		expect(store.error).toBe('In use')
		expect(store.prohibitions).toHaveLength(3)
		expect(store.loading).toBe(false)
	})

	it('sets and clears the current item', () => {
		const store = useProhibitionStore()
		store.setProhibitionItem({ id: 'p1' })
		expect(store.prohibitionItem).toEqual({ id: 'p1' })
		store.clearProhibitionItem()
		expect(store.prohibitionItem).toBeNull()
	})
})

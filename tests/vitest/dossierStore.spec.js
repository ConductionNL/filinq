/**
 * The dossier store: the getters, opening a dossier on its first present document, every write adopting the returned dossier into the list, and the fail-safe removal mode.
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

const { useDossierStore } = await import('../../src/store/modules/dossier.js')

const dossier = {
	id: 'd1',
	name: 'Bezwaar 2026',
	description: 'desc',
	status: 'open',
	checkedOn: '2026-11-01',
	bases: ['awb'],
	documents: [{ id: 10, missing: true }, { id: 11 }, { id: 12, missing: true }],
}

describe('dossier store', () => {
	it('lists the dossiers missing documents and finds the selected document', () => {
		const store = useDossierStore()
		store.dossiers = [{ id: 'a', missingCount: 2 }, { id: 'b', missingCount: 0 }, { id: 'c' }]
		store.dossier = dossier

		expect(store.withMissingDocuments.map((d) => d.id)).toEqual(['a'])
		expect(store.selectedDocument).toBeNull()
		store.selectDocument(11)
		expect(store.selectedDocument).toEqual({ id: 11 })
	})

	it('loads the list from the results, empty when there are none, and keeps the error text', async () => {
		const store = useDossierStore()
		axios.get.mockResolvedValueOnce({ data: { results: [{ id: 'd1' }] } })
		await store.fetchDossiers()
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/dossiers')
		expect(store.dossiers).toEqual([{ id: 'd1' }])

		axios.get.mockResolvedValueOnce({ data: {} })
		await store.fetchDossiers()
		expect(store.dossiers).toEqual([])

		axios.get.mockRejectedValueOnce(httpError('Request failed', 'Not allowed'))
		await store.fetchDossiers()
		expect(store.error).toBe('Not allowed')
		expect(store.loading).toBe(false)
	})

	it('opens a dossier on its first document that is not missing', async () => {
		const store = useDossierStore()
		axios.get.mockResolvedValueOnce({ data: dossier })

		expect(await store.fetchDossier('d1')).toEqual(dossier)
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/dossiers/d1')
		expect(store.selectedDocumentId).toBe(11)

		axios.get.mockResolvedValueOnce({ data: { id: 'd2', documents: [{ id: 1, missing: true }] } })
		await store.fetchDossier('d2')
		expect(store.selectedDocumentId).toBeNull()

		axios.get.mockRejectedValueOnce(httpError('gone'))
		expect(await store.fetchDossier('d3')).toBeNull()
		expect(store.error).toBe('gone')
	})

	it('adds a created dossier to the list as a summary row', async () => {
		const store = useDossierStore()
		axios.post.mockResolvedValueOnce({ data: dossier })

		expect(await store.createDossier({ name: 'Bezwaar 2026' })).toEqual(dossier)
		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/dossiers', { name: 'Bezwaar 2026', description: '', bases: [] })
		expect(store.dossier).toEqual(dossier)
		expect(store.dossiers).toEqual([
			{ id: 'd1', name: 'Bezwaar 2026', description: 'desc', status: 'open', checkedOn: '2026-11-01', documentCount: 3, missingCount: 2, bases: ['awb'] },
		])
		expect(store.saving).toBe(false)
	})

	it('replaces the existing row on rename, status change, link and removal', async () => {
		const store = useDossierStore()
		store.dossiers = [{ id: 'x' }, { id: 'd1', name: 'old' }]
		const renamed = { ...dossier, name: 'New', documents: [] }
		axios.put.mockResolvedValue({ data: renamed })
		axios.post.mockResolvedValue({ data: renamed })
		axios.delete.mockResolvedValue({ data: renamed })

		await store.renameDossier('d1', 'New')
		await store.transitionDossier('d1', 'closed')
		await store.linkDocument('d1', 77)
		await store.removeDocument('d1', 77)

		expect(axios.put.mock.calls).toEqual([
			['/index.php/apps/filinq/api/dossiers/d1/name', { name: 'New' }],
			['/index.php/apps/filinq/api/dossiers/d1/status', { status: 'closed' }],
		])
		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/dossiers/d1/documents', { fileId: 77 })
		expect(axios.delete).toHaveBeenCalledWith('/index.php/apps/filinq/api/dossiers/d1/documents/77')
		expect(store.dossiers).toHaveLength(2)
		expect(store.dossiers[1]).toMatchObject({ id: 'd1', name: 'New', documentCount: 0, missingCount: 0 })
	})

	it('ignores a write answer without a dossier id and reports a failed write', async () => {
		const store = useDossierStore()
		axios.put.mockResolvedValueOnce({ data: {} })
		expect(await store.renameDossier('d1', 'x')).toBeNull()
		expect(store.dossiers).toEqual([])

		axios.put.mockRejectedValueOnce(httpError('Request failed', 'Name taken'))
		expect(await store.renameDossier('d1', 'x')).toBeNull()
		expect(store.error).toBe('Name taken')
		expect(store.saving).toBe(false)
	})

	it('reads the removal mode, and assumes trash when it cannot', async () => {
		const store = useDossierStore()
		axios.get.mockResolvedValueOnce({ data: { mode: 'unlink' } })
		expect(await store.removalMode('d1', 5)).toBe('unlink')
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/dossiers/d1/documents/5/removal-mode')

		axios.get.mockRejectedValueOnce(httpError('500'))
		expect(await store.removalMode('d1', 5)).toBe('trash')
	})
})

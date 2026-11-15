/**
 * Two stores: custom dictionaries (capabilities, CRUD on dictionaries and
 * terms, imports) and the file viewer's original/anonymised switching.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude unit tests of existing stores' request and state handling
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const axios = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() }))
vi.mock('@nextcloud/axios', () => ({ default: axios }))

const { useCustomDictionaryStore } = await import('../../src/store/modules/customDictionary.js')
const { useFileViewerStore } = await import('../../src/store/modules/fileViewer.js')

const BASE = '/index.php/apps/filinq/api/custom-dictionaries'

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

describe('custom dictionary store', () => {
	it('may maintain only when the server says exactly true', async () => {
		const store = useCustomDictionaryStore()

		axios.get.mockResolvedValueOnce({ data: { canMaintain: true } })
		await store.fetchCapabilities()
		expect(axios.get).toHaveBeenCalledWith(BASE + '/capabilities')
		expect(store.canMaintain).toBe(true)

		axios.get.mockResolvedValueOnce({ data: { canMaintain: 'yes' } })
		await store.fetchCapabilities()
		expect(store.canMaintain).toBe(false)

		store.canMaintain = true
		axios.get.mockRejectedValueOnce(httpError('Forbidden'))
		await store.fetchCapabilities()
		expect(store.canMaintain).toBe(false)
	})

	it('lists and reads dictionaries, keeping a failed read as the server error', async () => {
		const store = useCustomDictionaryStore()
		axios.get.mockResolvedValueOnce({ data: [{ id: 'd1' }] })
		await store.fetchDictionaries()
		expect(store.dictionaries).toEqual([{ id: 'd1' }])

		axios.get.mockResolvedValueOnce({ data: { id: 'd1', name: 'Namen' } })
		expect(await store.fetchDictionary('d1')).toEqual({ id: 'd1', name: 'Namen' })
		expect(store.dictionaryItem).toEqual({ id: 'd1', name: 'Namen' })

		axios.get.mockRejectedValueOnce(httpError('Request failed', 'Not found'))
		expect(await store.fetchDictionary('gone')).toBeNull()
		expect(store.error).toBe('Not found')
		expect(store.loading).toBe(false)
	})

	it('creates, updates by any id form, and deletes dictionaries', async () => {
		const store = useCustomDictionaryStore()
		store.dictionaries = [{ '@self': { id: 'd1' }, name: 'Old' }, { uuid: 'd2', name: 'Keep' }]

		axios.post.mockResolvedValueOnce({ data: { id: 'd3', name: 'New' } })
		await store.createDictionary({ name: 'New' })
		expect(axios.post).toHaveBeenCalledWith(BASE, { name: 'New' })

		axios.put.mockResolvedValueOnce({ data: { id: 'd1', name: 'Renamed' } })
		await store.updateDictionary('d1', { name: 'Renamed' })
		expect(axios.put).toHaveBeenCalledWith(BASE + '/d1', { name: 'Renamed' })
		expect(store.dictionaries.map((d) => d.name)).toEqual(['Renamed', 'Keep', 'New'])
		expect(store.dictionaryItem).toEqual({ id: 'd1', name: 'Renamed' })

		axios.put.mockResolvedValueOnce({ data: { id: 'zz', name: 'Elsewhere' } })
		await store.updateDictionary('zz', {})
		expect(store.dictionaries).toHaveLength(3)

		axios.delete.mockResolvedValueOnce({})
		await store.deleteDictionary('d2')
		expect(axios.delete).toHaveBeenCalledWith(BASE + '/d2')
		expect(store.dictionaries.map((d) => d.name)).toEqual(['Renamed', 'New'])
	})

	it('rethrows a refused create and keeps the server error', async () => {
		const store = useCustomDictionaryStore()
		axios.post.mockRejectedValueOnce(httpError('Request failed', 'Name taken'))

		await expect(store.createDictionary({ name: 'Namen' })).rejects.toThrow('Request failed')
		expect(store.error).toBe('Name taken')
		expect(store.dictionaries).toEqual([])
	})

	it('loads, adds and removes terms', async () => {
		const store = useCustomDictionaryStore()
		axios.get.mockResolvedValueOnce({ data: [{ id: 't1', value: 'Jansen' }] })
		await store.fetchTerms('d1')
		expect(axios.get).toHaveBeenCalledWith(BASE + '/d1/terms')
		expect(store.termsLoading).toBe(false)

		axios.post.mockResolvedValueOnce({ data: { id: 't2', value: 'De Vries' } })
		await store.createTerm('d1', { value: 'De Vries' })
		expect(store.terms.map((t) => t.id)).toEqual(['t1', 't2'])

		axios.delete.mockResolvedValueOnce({})
		await store.deleteTerm('d1', 't1')
		expect(axios.delete).toHaveBeenCalledWith(BASE + '/d1/terms/t1')
		expect(store.terms.map((t) => t.id)).toEqual(['t2'])
	})

	it('rethrows a refused term change with the server error or transport message', async () => {
		const store = useCustomDictionaryStore()
		store.terms = [{ id: 't1' }]

		axios.post.mockRejectedValueOnce(httpError('Request failed', 'Duplicate term'))
		await expect(store.createTerm('d1', { value: 'x' })).rejects.toThrow()
		expect(store.error).toBe('Duplicate term')

		axios.delete.mockRejectedValueOnce(httpError('Network Error'))
		await expect(store.deleteTerm('d1', 't1')).rejects.toThrow('Network Error')
		expect(store.error).toBe('Network Error')
		expect(store.terms).toEqual([{ id: 't1' }])
	})

	it('imports pasted content with the default format, or a file as form data, then reloads the terms', async () => {
		const store = useCustomDictionaryStore()
		axios.post.mockResolvedValue({ data: { imported: 2 } })
		axios.get.mockResolvedValue({ data: [{ id: 't1' }, { id: 't2' }] })

		await store.importTerms('d1', { content: 'Jansen\nDe Vries' })
		expect(axios.post).toHaveBeenLastCalledWith(BASE + '/d1/import', { content: 'Jansen\nDe Vries', format: 'newline' })
		expect(store.importResult).toEqual({ imported: 2 })
		expect(store.terms).toHaveLength(2)

		await store.importTerms('d1', { content: 'a,b', format: 'csv' })
		expect(axios.post.mock.calls[1][1]).toEqual({ content: 'a,b', format: 'csv' })

		const file = new Blob(['Jansen'])
		await store.importTerms('d1', { file })
		const sent = axios.post.mock.calls[2][1]
		expect(sent).toBeInstanceOf(FormData)
		expect(sent.get('file')).toBeInstanceOf(Blob)
	})

	it('clears the open dictionary', () => {
		const store = useCustomDictionaryStore()
		Object.assign(store, { dictionaryItem: { id: 'd1' }, terms: [{ id: 't1' }], importResult: { imported: 1 } })

		store.clearDictionaryItem()

		expect([store.dictionaryItem, store.terms, store.importResult]).toEqual([null, [], null])
	})
})

describe('file viewer store', () => {
	const original = { fileId: 1, fileName: 'a.pdf' }
	const anonymised = { fileId: 2, fileName: 'a-anon.pdf' }

	it('opens a file fresh, with review on unless asked otherwise', () => {
		const store = useFileViewerStore()
		Object.assign(store, { selection: 'x', addMode: true, highlightEntities: [{}], anonymizedFile: anonymised })

		store.open(original)
		expect(store.$state).toMatchObject({ currentFile: original, originalFile: original, anonymizedFile: null, showAnonymized: false, selection: '', grondslagen: true, addMode: false, highlightEntities: [] })
		expect(store.canToggleVariant).toBe(false)

		store.open(original, { grondslagen: false })
		expect(store.grondslagen).toBe(false)
	})

	it('shows the anonymised variant, or keeps the original on screen when asked', () => {
		const store = useFileViewerStore()
		store.open(original)

		store.setAnonymizedVariant(anonymised)
		expect([store.currentFile, store.showAnonymized, store.canToggleVariant]).toEqual([anonymised, true, true])

		store.setAnonymizedVariant(anonymised, { show: false })
		expect([store.currentFile, store.showAnonymized]).toEqual([original, false])

		const fresh = useFileViewerStore()
		fresh.close()
		fresh.setAnonymizedVariant(anonymised, { show: false })
		expect(fresh.currentFile).toEqual(anonymised)
	})

	it('toggles between the two only when both exist, clearing selection and add mode', () => {
		const store = useFileViewerStore()
		store.open(original)
		store.toggleAnonymized()
		expect(store.currentFile).toEqual(original)

		store.setAnonymizedVariant(anonymised, { show: false })
		store.setAddMode(true)
		store.setSelection('Jan')
		store.toggleAnonymized()
		expect([store.currentFile, store.showAnonymized, store.selection, store.addMode]).toEqual([anonymised, true, '', false])

		store.toggleAnonymized()
		expect(store.currentFile).toEqual(original)
	})

	it('normalises the small setters, and leaving add mode drops the selection', () => {
		const store = useFileViewerStore()

		store.setGrondslagen(0)
		expect(store.grondslagen).toBe(false)
		store.setSelection(null)
		expect(store.selection).toBe('')
		store.setAddMode(1)
		store.setSelection('Jan Jansen')
		expect([store.addMode, store.selection]).toEqual([true, 'Jan Jansen'])
		store.setAddMode(false)
		expect(store.selection).toBe('')
		store.setHighlightEntities('nope')
		expect(store.highlightEntities).toEqual([])
		store.setHighlightEntities([{ value: 'Jan', type: 'PERSON' }])
		expect(store.highlightEntities).toEqual([{ value: 'Jan', type: 'PERSON' }])
	})

	it('closes back to the initial state', () => {
		const store = useFileViewerStore()
		store.open(original, { grondslagen: false })
		store.setAnonymizedVariant(anonymised)

		store.close()

		expect(store.$state).toMatchObject({ currentFile: null, originalFile: null, anonymizedFile: null, showAnonymized: false, selection: '', grondslagen: true, addMode: false, highlightEntities: [] })
	})
})

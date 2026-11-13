/**
 * The template store: language negotiation headers, the served-language flags, filtered listing, item writes, versions, preview and the edit lock.
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

const { useTemplateStore } = await import('../../src/store/modules/template.js')

describe('template store', () => {
	it('sends no language header until a language is chosen, then weights the others lower', () => {
		const store = useTemplateStore()
		expect(store.buildLanguageHeaders()).toEqual({})

		store.setLanguage('nl')
		expect(store.buildLanguageHeaders()).toEqual({ 'Accept-Language': 'nl, en;q=0.9' })

		store.setLanguage('en')
		expect(store.buildLanguageHeaders()).toEqual({ 'Accept-Language': 'en, nl;q=0.9' })

		store.setLanguage('de')
		expect(store.buildLanguageHeaders()).toEqual({ 'Accept-Language': 'de, nl;q=0.9, en;q=0.8' })
	})

	it('lists templates with only the filters that carry a value, and records the served language', async () => {
		const store = useTemplateStore()
		store.setLanguage('nl')
		axios.get.mockResolvedValueOnce({
			data: { results: [{ id: 't1' }], total: 7 },
			headers: { 'content-language': 'en', 'x-content-language-fallback': 'true' },
		})

		await store.fetchTemplates({ type: 'letter', search: '', owner: null, page: 2, tag: undefined })

		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/templates?type=letter&page=2', { headers: { 'Accept-Language': 'nl, en;q=0.9' } })
		expect(store.templates).toEqual([{ id: 't1' }])
		expect(store.total).toBe(7)
		expect(store.servedLanguage).toBe('en')
		expect(store.isFallbackLanguage).toBe(true)
		expect(store.loading).toBe(false)
	})

	it('treats a missing result set and headers as empty and native', async () => {
		const store = useTemplateStore()
		axios.get.mockResolvedValueOnce({ data: {} })

		await store.fetchTemplates()

		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/templates?', { headers: {} })
		expect(store.templates).toEqual([])
		expect(store.total).toBe(0)
		expect(store.servedLanguage).toBeNull()
		expect(store.isFallbackLanguage).toBe(false)

		axios.get.mockRejectedValueOnce(httpError('Network Error', 'server text not used'))
		await store.fetchTemplates()
		expect(store.error).toBe('Network Error')
	})

	it('reads, creates and updates a template into the item, or answers null', async () => {
		const store = useTemplateStore()
		axios.get.mockResolvedValueOnce({ data: { id: 't1' }, headers: { 'content-language': 'nl' } })
		expect(await store.fetchTemplate('t1')).toEqual({ id: 't1' })
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/templates/t1', { headers: {} })
		expect(store.servedLanguage).toBe('nl')

		axios.post.mockResolvedValueOnce({ data: { id: 't2' } })
		expect(await store.createTemplate({ name: 'B' })).toEqual({ id: 't2' })
		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/templates', { name: 'B' })
		expect(store.templateItem).toEqual({ id: 't2' })

		axios.put.mockResolvedValueOnce({ data: { id: 't2', name: 'C' } })
		expect(await store.updateTemplate('t2', { name: 'C' })).toEqual({ id: 't2', name: 'C' })
		expect(axios.put).toHaveBeenCalledWith('/index.php/apps/filinq/api/templates/t2', { name: 'C' })
		expect(store.templateItem).toEqual({ id: 't2', name: 'C' })

		axios.get.mockRejectedValueOnce(httpError('404'))
		expect(await store.fetchTemplate('t9')).toBeNull()
		axios.put.mockRejectedValueOnce(httpError('409'))
		expect(await store.updateTemplate('t2', {})).toBeNull()
		expect(store.error).toBe('409')
	})

	it('deletes a template from the list, answering whether it could', async () => {
		const store = useTemplateStore()
		store.templates = [{ id: 't1' }, { id: 't2' }]
		axios.delete.mockResolvedValueOnce({})

		expect(await store.deleteTemplate('t1')).toBe(true)
		expect(axios.delete).toHaveBeenCalledWith('/index.php/apps/filinq/api/templates/t1')
		expect(store.templates).toEqual([{ id: 't2' }])

		axios.delete.mockRejectedValueOnce(httpError('locked'))
		expect(await store.deleteTemplate('t2')).toBe(false)
		expect(store.templates).toEqual([{ id: 't2' }])
	})

	it('duplicates a template, lists and restores versions', async () => {
		const store = useTemplateStore()
		axios.post.mockResolvedValueOnce({ data: { id: 'copy' } })
		expect(await store.duplicateTemplate('t1')).toEqual({ id: 'copy' })
		expect(axios.post).toHaveBeenLastCalledWith('/index.php/apps/filinq/api/templates/t1/duplicate')

		axios.get.mockResolvedValueOnce({ data: { results: [{ v: 1 }, { v: 2 }] } })
		expect(await store.fetchVersions('t1')).toEqual({ results: [{ v: 1 }, { v: 2 }] })
		expect(axios.get).toHaveBeenLastCalledWith('/index.php/apps/filinq/api/templates/t1/versions')
		expect(store.versions).toEqual([{ v: 1 }, { v: 2 }])

		axios.get.mockResolvedValueOnce({ data: {} })
		await store.fetchVersions('t1')
		expect(store.versions).toEqual([])

		axios.post.mockResolvedValueOnce({ data: { id: 't1', version: 1 } })
		expect(await store.restoreVersion('t1', 'v1')).toEqual({ id: 't1', version: 1 })
		expect(axios.post).toHaveBeenLastCalledWith('/index.php/apps/filinq/api/templates/t1/versions/v1/restore')
		expect(store.templateItem).toEqual({ id: 't1', version: 1 })

		axios.post.mockRejectedValueOnce(httpError('500'))
		expect(await store.duplicateTemplate('t1')).toBeNull()
	})

	it('previews without touching the loading flag, lint defaulting to none', async () => {
		const store = useTemplateStore()
		axios.post.mockResolvedValueOnce({ data: { html: '<p>Hi</p>' } })

		const preview = store.previewTemplate('Hi {{ name }}', { name: 'A' })
		expect(store.loading).toBe(false)
		expect(await preview).toEqual({ html: '<p>Hi</p>', lint: [] })
		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/templates/preview', { content: 'Hi {{ name }}', data: { name: 'A' } })

		axios.post.mockResolvedValueOnce({ data: { html: '', lint: [{ line: 1 }] } })
		expect(await store.previewTemplate('x')).toEqual({ html: '', lint: [{ line: 1 }] })
		expect(axios.post).toHaveBeenLastCalledWith('/index.php/apps/filinq/api/templates/preview', { content: 'x', data: {} })

		axios.post.mockRejectedValueOnce(httpError('500'))
		expect(await store.previewTemplate('x')).toBeNull()
	})

	it('takes and releases the edit lock, keeping who holds it on refusal', async () => {
		const store = useTemplateStore()
		axios.post.mockResolvedValueOnce({ data: { locked: true } })
		expect(await store.acquireLock('t1')).toEqual({ locked: true })
		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/templates/t1/lock')

		axios.post.mockRejectedValueOnce(httpError('Request failed', 'Locked by anna'))
		expect(await store.acquireLock('t1')).toBeNull()
		expect(store.error).toBe('Locked by anna')

		axios.delete.mockResolvedValueOnce({ data: { locked: false } })
		expect(await store.releaseLock('t1')).toEqual({ locked: false })
		expect(axios.delete).toHaveBeenCalledWith('/index.php/apps/filinq/api/templates/t1/lock')

		axios.delete.mockRejectedValueOnce(httpError('Request failed', 'not used'))
		expect(await store.releaseLock('t1')).toBeNull()
		expect(store.error).toBe('Request failed')
	})
})

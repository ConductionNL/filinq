/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.5
 */
import { afterEach, describe, expect, it, vi } from 'vitest'
import { changeDomainLink, copyToRecord, domainLinkUrl, linkTarget } from '../../src/integrations/documentDomainLinks.js'

vi.mock('@nextcloud/router', () => ({
	generateUrl: (url, params = {}) => '/index.php' + url.replace(/\{(\w+)\}/g, (_, k) => String(params[k])),
}))

afterEach(() => {
	delete globalThis.fetch
})

describe('linking a document to another record', () => {
	it('names a record only when register, schema and id are all given', () => {
		expect(linkTarget({ register: ' zaken ', schema: 'zaak', id: 'z-2 ' })).toEqual({ register: 'zaken', schema: 'zaak', id: 'z-2' })
		expect(linkTarget({ register: 'zaken', schema: 'zaak', id: '  ' })).toBeNull()
		expect(linkTarget(null)).toBeNull()
	})

	it('addresses the document and carries the record as query parameters', () => {
		expect(domainLinkUrl('rec-1', { register: 'zaken', schema: 'zaak', id: 'z 2' }))
			.toBe('/index.php/apps/filinq/api/case-documents/rec-1/domains?register=zaken&schema=zaak&id=z+2')
	})

	it('posts a link with the request token and reports success', async () => {
		globalThis.fetch = vi.fn(async () => ({ ok: true, status: 200, json: async () => ({}) }))

		const answer = await changeDomainLink('POST', 'rec-1', { register: 'zaken', schema: 'zaak', id: 'z-2' }, 'tok')

		expect(answer).toEqual({ ok: true, status: 200, error: '' })
		const [url, init] = globalThis.fetch.mock.calls[0]
		expect(url).toBe('/index.php/apps/filinq/api/case-documents/rec-1/domains?register=zaken&schema=zaak&id=z-2')
		expect(init.method).toBe('POST')
		expect(init.headers.requesttoken).toBe('tok')
	})

	it('passes on the refusal the server gave', async () => {
		globalThis.fetch = vi.fn(async () => ({ ok: false, status: 403, json: async () => ({ error: 'You may not change the documents of that record.' }) }))

		const answer = await changeDomainLink('DELETE', 'rec-1', { register: 'zaken', schema: 'zaak', id: 'z-1' }, 'tok')

		expect(answer).toEqual({ ok: false, status: 403, error: 'You may not change the documents of that record.' })
		expect(globalThis.fetch.mock.calls[0][1].method).toBe('DELETE')
	})

	it('reports a network failure without throwing', async () => {
		globalThis.fetch = vi.fn(async () => { throw new Error('offline') })

		expect(await changeDomainLink('POST', 'rec-1', { register: 'a', schema: 'b', id: 'c' }, 'tok')).toEqual({ ok: false, status: 0, error: '' })
	})

	it('posts a copy naming the file and both records', async () => {
		globalThis.fetch = vi.fn(async () => ({ ok: true, status: 200, json: async () => ({}) }))

		const answer = await copyToRecord(5, { register: 'a', schema: 'b', id: 'c' }, { register: 'd', schema: 'e', id: 'f' }, 'tok')

		expect(answer.ok).toBe(true)
		expect(globalThis.fetch.mock.calls[0][0]).toBe('/index.php/apps/filinq/api/case-documents/copy?fileId=5&sourceRegister=a&sourceSchema=b&sourceId=c&register=d&schema=e&id=f')
		expect(globalThis.fetch.mock.calls[0][1].method).toBe('POST')
	})
})

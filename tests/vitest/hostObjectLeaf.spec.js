// SPDX-License-Identifier: EUPL-1.2
// Copyright (C) 2026 Conduction B.V.

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { hostObjectLeaf } from '../../src/integrations/hostObjectLeaf.js'

/**
 * A leaf instance: the mixin's methods bound to a plain state object, the way
 * Vue binds them to a component.
 *
 * @param {object} state The props and data.
 * @return {object} The instance.
 */
function leaf(state = {}) {
	const vm = { register: 'cases', schema: 'case', objectId: 'obj-1', loading: true, error: false, rows: [], ...state }
	for (const [name, fn] of Object.entries(hostObjectLeaf.methods)) {
		vm[name] = fn.bind(vm)
	}
	return vm
}

/**
 * A fetch answer.
 *
 * @param {number} status The HTTP status.
 * @param {*} body The JSON body, or an Error to fail the JSON read.
 * @return {object} The response.
 */
function answer(status, body) {
	return {
		ok: status >= 200 && status < 300,
		json: () => (body instanceof Error ? Promise.reject(body) : Promise.resolve(body)),
	}
}

/**
 * A leaf reads and writes on behalf of one host object: the object as query
 * parameters, the page's request token, and `error` / `loading` as the
 * answer comes back.
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */
describe('hostObjectLeaf', () => {
	let token

	beforeEach(() => {
		token = 'tok-123'
		vi.stubGlobal('document', {
			getElementsByTagName: (tag) => (tag === 'head'
				? [{ getAttribute: (name) => (name === 'data-requesttoken' ? token : null) }]
				: []),
		})
		vi.stubGlobal('fetch', vi.fn())
	})

	afterEach(() => {
		vi.unstubAllGlobals()
	})

	it('declares the host object props with empty defaults', () => {
		expect(hostObjectLeaf.props).toEqual({
			register: { type: String, default: '' },
			schema: { type: String, default: '' },
			objectId: { type: String, default: '' },
		})
	})

	it('puts the host object in the query as register, schema and id', () => {
		expect(leaf({ objectId: 'a b&c' }).hostObjectQuery().toString())
			.toBe('register=cases&schema=case&id=a+b%26c')
	})

	it('sends GET requests with the request token and JSON accept header', async () => {
		fetch.mockResolvedValue(answer(200, {}))

		await leaf().fetchForHostObject('/apps/filinq/api/case-documents/files')

		expect(fetch).toHaveBeenCalledWith(
			'/index.php/apps/filinq/api/case-documents/files?register=cases&schema=case&id=obj-1',
			{ headers: { requesttoken: 'tok-123', Accept: 'application/json' } },
		)
	})

	it('passes an explicit method along', async () => {
		fetch.mockResolvedValue(answer(200, {}))

		await leaf().fetchForHostObject('/apps/filinq/api/merge', 'POST')

		expect(fetch.mock.calls[0][1]).toEqual({
			method: 'POST',
			headers: { requesttoken: 'tok-123', Accept: 'application/json' },
		})
	})

	it('sends an empty token when the page has none', async () => {
		token = null
		fetch.mockResolvedValue(answer(200, {}))
		const vm = leaf()

		expect(vm.requestToken()).toBe('')
		vi.stubGlobal('document', { getElementsByTagName: () => [] })
		expect(vm.requestToken()).toBe('')
	})

	it('asks nothing without an object and stops loading', async () => {
		const vm = leaf({ objectId: '' })
		const apply = vi.fn()

		await vm.loadForHostObject('/apps/filinq/api/x', apply)

		expect(fetch).not.toHaveBeenCalled()
		expect(apply).not.toHaveBeenCalled()
		expect(vm.loading).toBe(false)
		expect(vm.error).toBe(false)
	})

	it('applies a good answer and stops loading', async () => {
		fetch.mockResolvedValue(answer(200, { results: [{ id: 1 }] }))
		const vm = leaf()
		const apply = vi.fn()

		await vm.loadForHostObject('/apps/filinq/api/x', apply)

		expect(apply).toHaveBeenCalledWith({ results: [{ id: 1 }] })
		expect(vm.error).toBe(false)
		expect(vm.loading).toBe(false)
	})

	it('marks a refusal as an error without applying it', async () => {
		fetch.mockResolvedValue(answer(403, { message: 'no' }))
		const vm = leaf()
		const apply = vi.fn()

		await vm.loadForHostObject('/apps/filinq/api/x', apply)

		expect(apply).not.toHaveBeenCalled()
		expect(vm.error).toBe(true)
		expect(vm.loading).toBe(false)
	})

	it('marks an unreachable server or unreadable body as an error', async () => {
		fetch.mockRejectedValueOnce(new Error('offline'))
		const offline = leaf()
		await offline.loadForHostObject('/apps/filinq/api/x', vi.fn())
		expect(offline.error).toBe(true)
		expect(offline.loading).toBe(false)

		fetch.mockResolvedValueOnce(answer(200, new SyntaxError('bad json')))
		const garbled = leaf()
		await garbled.loadForHostObject('/apps/filinq/api/x', vi.fn())
		expect(garbled.error).toBe(true)
		expect(garbled.loading).toBe(false)
	})

	it('loads the results array into rows, and nothing else', async () => {
		fetch.mockResolvedValueOnce(answer(200, { results: [{ fileId: 11 }] }))
		const vm = leaf()
		await vm.loadHostRows('/apps/filinq/api/case-documents/files')
		expect(vm.rows).toEqual([{ fileId: 11 }])

		fetch.mockResolvedValueOnce(answer(200, { results: 'nope' }))
		const odd = leaf({ rows: [{ stale: true }] })
		await odd.loadHostRows('/apps/filinq/api/case-documents/files')
		expect(odd.rows).toEqual([])
	})
})

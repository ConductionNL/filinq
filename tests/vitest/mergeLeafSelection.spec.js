/**
 * The merge-to-PDF leaf around its submit: choosing and ordering documents,
 * naming them, reading the cover templates, and describing a finished merge.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude unit test of the merge leaf selection and template logic
 */

import { join } from 'node:path'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) => '[t] ' + text.replace(/\{(\w+)\}/g, (_, k) => String(vars?.[k] ?? '')),
}))
vi.mock('@nextcloud/router', () => ({
	generateUrl: (url, params = {}) => '/index.php' + url.replace(/\{(\w+)\}/g, (_, k) => String(params[k])),
}))

const calls = []

/**
 * A merge leaf on one host object.
 *
 * @param {object} state State to set on top.
 * @return {Promise<object>} The instance.
 */
async function leaf(state = {}) {
	const options = await loadSfcOptions(join(import.meta.dirname, '../../src/integrations/CnFilinqMergeToPdfWidget.vue'))
	const vm = createInstance(options, { props: { register: 'zaken', schema: 'zaak', objectId: 'z-1' } })
	vm.requestToken = () => 'token-1'
	return Object.assign(vm, state)
}

/**
 * Answer every fetch with one response.
 *
 * @param {number} status The HTTP status.
 * @param {*} body The JSON body.
 * @return {void}
 */
function answerWith(status, body) {
	globalThis.fetch = vi.fn(async (url, init) => {
		calls.push({ url, init })
		return { ok: status >= 200 && status < 300, status, json: async () => body }
	})
}

afterEach(() => {
	calls.length = 0
	delete globalThis.fetch
})

describe('merge leaf: selection', () => {
	it('starts with nothing chosen, bookmarks on and a unique order label', async () => {
		const first = await leaf()
		const second = createInstance(first.$options, { props: {} })
		expect(first.selection).toEqual([])
		expect(first.bookmarks).toBe(true)
		expect(first.mergeable).toBe(false)
		expect(first.orderLabelId).toMatch(/^filinq-merge-order-\d+$/)
		expect(second.orderLabelId).not.toBe(first.orderLabelId)
	})

	it('adds and removes documents, and is mergeable from two on', async () => {
		const vm = await leaf()
		vm.toggle(5)
		expect(vm.selection).toEqual([5])
		expect(vm.mergeable).toBe(false)
		vm.toggle(9)
		expect(vm.selection).toEqual([5, 9])
		expect(vm.mergeable).toBe(true)
		vm.toggle(5)
		expect(vm.selection).toEqual([9])
	})

	it('moves a chosen document and ignores a move past the end', async () => {
		const vm = await leaf({ selection: [1, 2, 3] })
		vm.move(2, -1)
		expect(vm.selection).toEqual([1, 3, 2])
		vm.move(0, -1)
		expect(vm.selection).toEqual([1, 3, 2])
	})

	it('names a chosen document by its listed row, else by its id', async () => {
		const vm = await leaf({ rows: [{ fileId: 4, name: 'brief.pdf' }] })
		expect(vm.nameOf(4)).toBe('brief.pdf')
		expect(vm.nameOf(8)).toBe('8')
	})

	it('builds the payload in the chosen order, bound to the host object', async () => {
		const vm = await leaf({
			rows: [{ fileId: 1, name: 'a.pdf' }, { fileId: 2, name: 'b.pdf' }],
			selection: [2, 1, 99],
			bookmarks: false,
		})
		const payload = vm.mergePayload()
		expect(payload.inputs).toEqual([{ fileId: 2, label: 'b.pdf' }, { fileId: 1, label: 'a.pdf' }])
		expect(payload.options).toEqual({ bookmarks: false })
		expect(payload.hostObject).toEqual({ register: 'zaken', schema: 'zaak', id: 'z-1' })
	})
})

describe('merge leaf: cover templates', () => {
	it('keeps templates with a reference, preferring the uuid', async () => {
		answerWith(200, {
			results: [
				{ uuid: 'u-1', id: 3, name: 'Voorblad' },
				{ id: 4 },
				{ name: 'no reference' },
			],
		})
		const vm = await leaf()
		await vm.loadTemplates()
		expect(calls[0].url).toBe('/index.php/apps/filinq/api/templates')
		expect(calls[0].init).toEqual({ headers: { requesttoken: 'token-1', Accept: 'application/json' } })
		expect(vm.templates).toEqual([{ ref: 'u-1', name: 'Voorblad' }, { ref: '4', name: '' }])
	})

	it('offers no templates when the answer has no list', async () => {
		answerWith(200, { results: null })
		const vm = await leaf({ templates: [{ ref: 'old', name: 'old' }] })
		await vm.loadTemplates()
		expect(vm.templates).toEqual([])
	})

	it('offers no templates when the route refuses', async () => {
		answerWith(403, { results: [{ uuid: 'u-1', name: 'x' }] })
		const vm = await leaf()
		await vm.loadTemplates()
		expect(vm.templates).toEqual([])
	})

	it('rejects a refused request with the status', async () => {
		answerWith(404, {})
		const vm = await leaf()
		await expect(vm.request('/x')).rejects.toThrow('HTTP 404')
	})
})

describe('merge leaf: describe', () => {
	it('links a finished PDF', async () => {
		const vm = await leaf()
		expect(vm.describe(200, { status: 'done', resultFileId: 77 })).toEqual({
			failed: false,
			message: '[t] The PDF is ready.',
			link: '/index.php/f/77',
		})
	})

	it('treats a 200 without a result file as a failure with its last error', async () => {
		const vm = await leaf()
		expect(vm.describe(200, { status: 'failed', lastError: 'Corrupt input' })).toEqual({
			failed: true,
			message: '[t] The merge failed: Corrupt input',
			link: '',
		})
	})

	it('falls back to a generic reason', async () => {
		const vm = await leaf()
		expect(vm.describe(500, {}).message).toBe('[t] The merge failed: [t] no reason was given')
	})

	it('reads the documents and the templates when mounted', async () => {
		answerWith(200, { results: [] })
		const vm = await leaf()
		await vm.$hook('mounted')
		await vi.waitFor(() => expect(calls).toHaveLength(2))
		expect(calls.map((call) => call.url)).toEqual([
			'/index.php/apps/filinq/api/case-documents/files?register=zaken&schema=zaak&id=z-1',
			'/index.php/apps/filinq/api/templates',
		])
	})
})

/**
 * The documents leaf: what it reads for the host object, the Files link per
 * row, and what it reports to a carding host.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude unit test of the documents leaf logic
 */

import { join } from 'node:path'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => '[t] ' + text,
	translatePlural: (app, one, many, count, vars) => (count === 1 ? one : many).replace('%n', String(count)).replace(/\{(\w+)\}/g, (_, k) => String(vars?.[k] ?? '')),
}))
vi.mock('@nextcloud/router', () => ({
	generateUrl: (url, params = {}) => '/index.php' + url.replace(/\{(\w+)\}/g, (_, k) => String(params[k])),
}))

const calls = []

/**
 * A documents leaf on one host object. The component `extends` the
 * host-object mixin, which the harness does not follow, so it is passed as a
 * mixin here.
 *
 * @param {object} props Props to set on top.
 * @return {Promise<object>} The instance.
 */
async function leaf(props = {}) {
	const options = await loadSfcOptions(join(import.meta.dirname, '../../src/integrations/CnFilinqDocumentsWidget.vue'))
	const merged = { ...options, mixins: [options.extends, ...(options.mixins || [])] }
	const vm = createInstance(merged, { props: { register: 'zaken', schema: 'zaak', objectId: 'z-1', ...props } })
	vm.requestToken = () => 'token-1'
	return vm
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

describe('documents leaf', () => {
	it('reads the flat document list for the host object when mounted', async () => {
		answerWith(200, { results: [{ fileId: 1, name: 'a.pdf', dossier: null }] })
		const vm = await leaf()
		await vm.$hook('mounted')
		await vi.waitFor(() => expect(vm.loading).toBe(false))
		expect(calls[0].url).toBe('/index.php/apps/filinq/api/case-documents/files?register=zaken&schema=zaak&id=z-1')
		expect(calls[0].init).toEqual({ headers: { requesttoken: 'token-1', Accept: 'application/json' } })
		expect(vm.rows).toEqual([{ fileId: 1, name: 'a.pdf', dossier: null }])
		expect(vm.error).toBe(false)
	})

	it('keeps no rows when the answer has no list', async () => {
		answerWith(200, { results: 'nope' })
		const vm = await leaf()
		await vm.load()
		expect(vm.rows).toEqual([])
	})

	it('marks an error on a refusal', async () => {
		answerWith(403, {})
		const vm = await leaf()
		await vm.load()
		expect(vm.error).toBe(true)
		expect(vm.loading).toBe(false)
	})

	it('asks nothing without a host object', async () => {
		answerWith(200, { results: [] })
		const vm = await leaf({ objectId: '' })
		await vm.load()
		expect(calls).toHaveLength(0)
		expect(vm.loading).toBe(false)
	})

	it('links a row to the file in Files', async () => {
		const vm = await leaf()
		expect(vm.fileLink({ fileId: 12 })).toBe('/index.php/f/12')
	})

	it('reports the count, the empty label, the noun and the grouped rows', async () => {
		const vm = await leaf()
		vm.rows = [
			{ fileId: 1, name: 'a.pdf', dossier: { id: 'd1', name: 'Fietspad' } },
			{ fileId: 2, name: 'b.pdf', dossier: { id: 'd1', name: 'Fietspad' } },
			{ fileId: 3, name: 'c.pdf', dossier: null },
		]
		expect(vm.leafReport).toEqual({
			count: 3,
			emptyLabel: '[t] No documents yet',
			noun: '[t] documents',
			rows: [
				{ label: 'Dossier "Fietspad" (2 documents)', href: '/index.php/apps/filinq/dossiers/d1', icon: 'FolderOutline' },
				{ label: 'c.pdf', href: '/index.php/f/3', icon: 'FileDocumentOutline' },
			],
		})
	})
})

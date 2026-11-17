/**
 * The generate-document leaf: the request it builds, when it may run, and
 * what it shows for each answer of the server.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
 */

import { join } from 'node:path'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { buildGenerateRequest, canGenerate, storedFile } from '../../src/services/generateDocumentRequest.js'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) => '[t] ' + text.replace(/\{(\w+)\}/g, (_, k) => String(vars?.[k] ?? '')),
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))

const HOST = { register: 'dossiq', schema: 'case', objectId: 'c-1' }

describe('generateDocumentRequest', () => {
	it('binds the template to the host object, names it as the source and stores the result', () => {
		expect(buildGenerateRequest({ template: { id: 't-1', name: ' Besluit ' }, host: HOST })).toEqual({
			templateId: 't-1',
			dataRefs: [{ register: 'dossiq', schema: 'case', id: 'c-1' }],
			options: { output: { mode: 'files' }, sourceObject: { register: 'dossiq', schema: 'case', id: 'c-1' } },
			filename: 'Besluit',
		})
	})

	it('names a source object the server finds among the data references', () => {
		const body = buildGenerateRequest({ template: { id: 't-1', name: 'Besluit' }, host: HOST })
		expect(body.dataRefs).toContainEqual(body.options.sourceObject)
		expect(body.options.sourceObject).not.toBe(body.dataRefs[0])
	})

	it('names a template without a name "document"', () => {
		expect(buildGenerateRequest({ template: { id: 't-1', name: '' }, host: HOST }).filename).toBe('document')
	})

	it('needs a template and a complete host object', () => {
		expect(canGenerate({ id: 't-1' }, HOST)).toBe(true)
		expect(canGenerate(null, HOST)).toBe(false)
		expect(canGenerate({ id: '' }, HOST)).toBe(false)
		expect(canGenerate({ id: 't-1' }, { ...HOST, objectId: '' })).toBe(false)
	})

	it('reads the stored file from the answer', () => {
		expect(storedFile({ fileId: '12', name: 'Besluit.pdf' })).toEqual({ fileId: 12, name: 'Besluit.pdf' })
		expect(storedFile({ fileId: null })).toBeNull()
		expect(storedFile(undefined)).toBeNull()
	})
})

describe('CnFilinqGenerateDocumentWidget submit', () => {
	const calls = []

	afterEach(() => {
		calls.length = 0
		delete globalThis.fetch
	})

	/**
	 * A widget on the host with two templates, the second chosen.
	 *
	 * @return {Promise<object>} The instance.
	 */
	async function leaf() {
		const options = await loadSfcOptions(join(import.meta.dirname, '../../src/integrations/CnFilinqGenerateDocumentWidget.vue'))
		const vm = createInstance(options, { props: HOST })
		vm.requestToken = () => 'token-1'
		Object.assign(vm, {
			rows: [{ id: 't-1', name: 'Brief' }, { id: 't-2', name: 'Besluit' }],
			chosenId: 't-2',
		})
		return vm
	}

	it('posts the chosen template on the host object and names the stored file', async () => {
		globalThis.fetch = async (url, init) => {
			calls.push([url, init.method, init.headers.requesttoken, JSON.parse(init.body)])
			return { ok: true, json: async () => ({ fileId: 7, name: 'Besluit.pdf' }) }
		}
		const vm = await leaf()

		await vm.submit()

		expect(calls).toEqual([[
			'/index.php/apps/filinq/api/documents/generate',
			'POST',
			'token-1',
			buildGenerateRequest({ template: { id: 't-2', name: 'Besluit' }, host: HOST }),
		]])
		expect(vm.outcome).toEqual({ failed: false, message: '[t] Generated Besluit.pdf' })
		expect(vm.busy).toBe(false)
	})

	it('says so when the server refuses', async () => {
		globalThis.fetch = async () => ({ ok: false, json: async () => ({ error: 'no' }) })
		const vm = await leaf()

		await vm.submit()

		expect(vm.outcome).toEqual({ failed: true, message: '[t] The document could not be generated' })
	})

	it('says so when the server cannot be reached', async () => {
		globalThis.fetch = async () => { throw new Error('offline') }
		const vm = await leaf()

		await vm.submit()

		expect(vm.outcome.failed).toBe(true)
		expect(vm.busy).toBe(false)
	})

	it('sends nothing without a chosen template', async () => {
		globalThis.fetch = async () => { calls.push('called') }
		const vm = await leaf()
		vm.chosenId = ''

		await vm.submit()

		expect(calls).toEqual([])
	})
})

/**
 * Golden: the script logic of the four document viewers (odt, docx, text,
 * pdf), recorded on the per-viewer implementations. Fetching, conversion,
 * highlighting and the viewer store are recording fakes.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing document viewer behaviour
 */

import { join } from 'node:path'
import { describe, it, vi } from 'vitest'
import { golden } from './golden/golden.js'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

const calls = vi.hoisted(() => [])
const fail = vi.hoisted(() => ({ fetch: null, convert: null, noContent: false }))
const store = vi.hoisted(() => ({
	highlightEntities: null,
	addMode: false,
	selection: '',
}))

/**
 * Record a call and fail when the named failure is set.
 *
 * @param {string} kind Failure key.
 * @param {Array} entry Call log entry.
 */
function step(kind, entry) {
	calls.push(entry)
	if (fail[kind]) {
		throw fail[kind]
	}
}

vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => '[' + text + ']',
}))

vi.mock('../../src/services/fileViewerService.js', () => ({
	fetchFileAsArrayBuffer: async (path) => {
		step('fetch', ['fetchFileAsArrayBuffer', path])
		return { bytes: path }
	},
	fetchUrlAsArrayBuffer: async (url) => {
		step('fetch', ['fetchUrlAsArrayBuffer', url])
		return { bytes: url }
	},
	fetchFileAsText: async (path) => {
		step('fetch', ['fetchFileAsText', path])
		return 'text of ' + path
	},
	fetchUrlAsText: async (url) => {
		step('fetch', ['fetchUrlAsText', url])
		return 'text of ' + url
	},
}))

vi.mock('../../src/services/highlightDom.js', () => ({
	applyDomHighlights: (root, entities, pending) =>
		calls.push(['applyDomHighlights', root, entities, pending]),
	clearDomHighlights: (root) => calls.push(['clearDomHighlights', root]),
}))

vi.mock('../../src/services/odfToHtml.js', () => ({
	odfXmlToHtml: (xml) => '<p>odf ' + xml + '</p>',
}))

vi.mock('../../src/utils/sanitizeHtml.js', () => ({
	sanitizeHtml: (html) => 'clean(' + html + ')',
}))

vi.mock('mammoth/mammoth.browser.js', () => ({
	default: {
		convertToHtml: async ({ arrayBuffer }) => {
			step('convert', ['mammoth', arrayBuffer])
			return { value: '<p>docx ' + arrayBuffer.bytes + '</p>' }
		},
	},
}))

vi.mock('jszip', () => ({
	default: {
		loadAsync: async (buffer) => {
			step('convert', ['jszip', buffer])
			return {
				file: (name) =>
					fail.noContent
						? null
						: { async: async (kind) => name + ':' + kind },
			}
		},
	},
}))

vi.mock('pdfjs-dist/build/pdf.mjs', () => ({
	GlobalWorkerOptions: {},
	getDocument: () => ({ promise: Promise.resolve({ numPages: 0 }) }),
}))

vi.mock('pdfjs-dist/build/pdf.worker.min.mjs?url', () => ({ default: 'w' }))

vi.mock('../../src/store/store.js', () => ({
	fileViewerStore: new Proxy(store, {
		get(target, key) {
			if (key === 'setSelection') {
				return (text) => {
					calls.push(['setSelection', text])
				}
			}
			return target[key]
		},
	}),
}))

const VIEWERS = {
	odt: 'components/viewers/OdtViewer.vue',
	word: 'components/viewers/WordViewer.vue',
	text: 'components/viewers/TextViewer.vue',
	pdf: 'components/viewers/PdfViewer.vue',
}

const KEYS = [
	'loading',
	'error',
	'html',
	'content',
	'highlightEntities',
	'pendingValue',
	'isAddMode',
]

/**
 * Observable state of a viewer instance plus the calls since last time.
 *
 * @param {object} vm Instance.
 * @param {Array} errors console.error calls.
 * @return {object} Snapshot.
 */
function snapshot(vm, errors) {
	const out = {}
	for (const key of KEYS) {
		if (key in vm) {
			out[key] = vm[key] === undefined ? '(undefined)' : vm[key]
		}
	}
	out.calls = calls.splice(0)
	out.errors = errors.splice(0).map(([msg, err]) => [msg, err?.message])
	return out
}

/**
 * Let pending promise callbacks and $nextTick callbacks run.
 */
async function settle() {
	for (let i = 0; i < 5; i++) {
		await Promise.resolve()
	}
}

/**
 * A component's options and its mixins', mixins first.
 *
 * @param {object} options Component options.
 * @return {object[]} The option objects.
 */
function flatten(options) {
	return [...(options.mixins || []).flatMap(flatten), options]
}

describe('document viewers golden', () => {
	for (const [key, rel] of Object.entries(VIEWERS)) {
		it(key, async () => {
			calls.length = 0
			Object.assign(fail, { fetch: null, convert: null, noContent: false })
			Object.assign(store, {
				highlightEntities: null,
				addMode: false,
				selection: '',
			})
			const errors = []
			const spy = vi
				.spyOn(console, 'error')
				.mockImplementation((...a) => errors.push(a))
			globalThis.window = {
				getSelection: () => ({ toString: () => 'picked text' }),
			}
			const options = await loadSfcOptions(
				join(import.meta.dirname, '../../src', rel),
			)
			const parts = flatten(options)
			const declared = Object.assign({}, ...parts.map((o) => o.props))
			const watched = Object.assign({}, ...parts.map((o) => o.watch))
			const props = Object.fromEntries(
				Object.entries(declared).map(([name, def]) => [
					name,
					{ ...def, type: def.type.name },
				]),
			)
			const record = {
				props,
				watchers: Object.fromEntries(
					Object.entries(watched).map(([name, def]) => [
						name,
						{ deep: !!def.deep, immediate: !!def.immediate },
					]),
				),
			}

			// Watcher wiring, with load and scheduleHighlights recorded.
			const wiring = createInstance(options, { props: { path: '/a' } })
			wiring.load = () => calls.push(['load'])
			wiring.scheduleHighlights = () => calls.push(['scheduleHighlights'])
			for (const name of Object.keys(watched)) {
				await wiring.$fireWatch(name, 'now', 'before')
			}
			record.wiring = snapshot(wiring, errors)

			store.highlightEntities = [{ value: 'Jan', type: 'PERSON' }]
			store.selection = 'sel'
			record.addModeOff = snapshot(wiring, errors)
			store.addMode = true
			record.addModeOn = snapshot(wiring, errors)
			store.selection = null
			record.addModeNoSelection = snapshot(wiring, errors)
			wiring.captureSelection()
			record.captured = snapshot(wiring, errors)
			globalThis.window = { getSelection: () => null }
			wiring.captureSelection()
			record.capturedNothing = snapshot(wiring, errors)

			if (key === 'pdf') {
				spy.mockRestore()
				golden('documentViewers-' + key, record)
				return
			}

			store.selection = 'sel'
			const refs = { content: { el: 'content' } }
			const vm = createInstance(options, { props: { path: '/doc' }, refs })
			record.initial = snapshot(vm, errors)
			await vm.load()
			await settle()
			record.loadedPath = snapshot(vm, errors)

			const byUrl = createInstance(options, {
				props: { path: '/doc', url: 'https://x/y' },
				refs,
			})
			await byUrl.load()
			await settle()
			record.loadedUrl = snapshot(byUrl, errors)

			fail.fetch = new Error('fetch broke')
			await vm.load()
			await settle()
			record.fetchFailed = snapshot(vm, errors)
			fail.fetch = {}
			await vm.load()
			await settle()
			record.fetchFailedBare = snapshot(vm, errors)
			fail.fetch = null

			fail.convert = new Error('convert broke')
			await vm.load()
			await settle()
			record.convertFailed = snapshot(vm, errors)
			fail.convert = null
			fail.noContent = true
			await vm.load()
			await settle()
			record.noContent = snapshot(vm, errors)
			fail.noContent = false

			await vm.load()
			await settle()
			record.reloaded = snapshot(vm, errors)
			await vm.$hook('beforeUnmount')
			record.unmounted = snapshot(vm, errors)

			spy.mockRestore()
			golden('documentViewers-' + key, record)
		})
	}
})

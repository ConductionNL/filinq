/**
 * Golden: running OCR from the file viewer page and from the My documents
 * list. Each page runs OCR for a processed file, an unreadable file, a refusal
 * that switches OCR off (409, 503) and one that does not (500, no response);
 * the record holds the server calls (with the running flag while the call is
 * out), the toasts and the page state after.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of the OCR run on the two pages
 */

import { join } from 'node:path'
import { describe, it, vi } from 'vitest'
import { golden } from './golden/golden.js'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

const log = vi.hoisted(() => ({ calls: [], answer: null, running: null }))

vi.mock('@nextcloud/axios', () => ({
	default: {
		get: async (url) => {
			log.calls.push(['get', url])
			return { data: {} }
		},
		post: async (url) => {
			log.calls.push([
				'post',
				url,
				'running: ' + JSON.stringify(log.running()),
			])
			if (log.answer instanceof Error) {
				throw log.answer
			}
			return { data: log.answer }
		},
	},
}))

vi.mock('@nextcloud/dialogs', () => ({
	showError: (text) => log.calls.push(['showError', text]),
	showSuccess: (text) => log.calls.push(['showSuccess', text]),
}))

vi.mock('@nextcloud/l10n', () => ({
	getLanguage: () => 'nl',
	translate: (app, text, vars) =>
		'[' + text.replace(/\{(\w+)\}/g, (_, k) => String(vars?.[k] ?? '')) + ']',
	translatePlural: (app, one, more) => '[' + more + ']',
}))

vi.mock('@nextcloud/router', () => ({
	generateUrl: (url) => url,
	generateOcsUrl: (url) => url,
	generateRemoteUrl: (url) => url,
}))

vi.mock('../../src/store/store.js', () => ({
	fileViewerStore: { currentFile: { fileId: 42 } },
	myDocumentsStore: {},
}))

/**
 * An axios error with a status, optionally with a server reason.
 *
 * @param {number|null} status HTTP status, or null for no response.
 * @param {string} [reason] The server's error text.
 * @return {Error} The error.
 */
function refusal(status, reason) {
	const error = new Error('request failed')
	if (status !== null) {
		error.response = { status, data: reason ? { error: reason } : {} }
	}
	return error
}

const ANSWERS = {
	processed: { ocrProcessed: true, textLength: 120, confidence: 88 },
	unreadable: { ocrProcessed: false, error: 'Nothing to read' },
	unreadableBare: { ocrProcessed: false },
	refused409: refusal(409, 'OCR is switched off'),
	refused503: refusal(503),
	failed500: refusal(500, 'Server error'),
	offline: refusal(null),
}

const PAGES = {
	fileViewer: [
		'views/fileViewer/FileViewerPage.vue',
		(vm) => vm.runOcrNow(),
		(vm) => ({
			ocrRunning: vm.ocrRunning,
			ocrResult: vm.ocrResult,
			ocrAvailable: vm.ocrAvailable,
		}),
	],
	myDocuments: [
		'views/myDocuments/MyDocumentsIndex.vue',
		(vm) => vm.runOcrOn({ fileId: 7 }),
		(vm) => ({
			ocrRunning: vm.ocrRunning,
			ocrResults: vm.ocrResults,
			ocrAvailable: vm.ocrAvailable,
		}),
	],
}

describe('OCR run on the pages', () => {
	for (const [page, [rel, runIt, state]] of Object.entries(PAGES)) {
		it(page, async () => {
			const options = await loadSfcOptions(
				join(import.meta.dirname, '../../src', rel),
			)
			const record = {}
			for (const [name, answer] of Object.entries(ANSWERS)) {
				const vm = createInstance(options)
				vm.ocrAvailable = true
				log.calls = []
				log.answer = answer
				log.running = () => vm.ocrRunning
				await runIt(vm)
				record[name] = { calls: log.calls, state: state(vm) }
			}
			golden('ocr-run-pages-' + page, record)
		})
	}
})

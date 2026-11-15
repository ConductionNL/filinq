/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden test for the request bookkeeping of the template, prohibition and
 * custom dictionary stores and the answer helpers of the classification,
 * email ingestion and wizard services. Every action runs once against a
 * server that answers and once against one that refuses (with and without a
 * server error text); the golden pins the calls made, the loading flags seen
 * during the call, what the action returns or throws, the store state after
 * it, and what it logs. RECORD_GOLDEN=1 rewrites the golden file.
 */

import axios from '@nextcloud/axios'
import fs from 'fs'
import path from 'path'
import { createPinia, setActivePinia } from 'pinia'
import * as classification from '../services/classification.js'
import * as emailIngestion from '../services/emailIngestion.js'
import * as wizard from '../services/wizard.js'
import { useCustomDictionaryStore } from './modules/customDictionary.js'
import { useProhibitionStore } from './modules/prohibition.js'
import { useTemplateStore } from './modules/template.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: {
		get: jest.fn(),
		post: jest.fn(),
		put: jest.fn(),
		delete: jest.fn(),
	},
}))
jest.mock('@nextcloud/router', () => ({
	generateUrl: (url) => 'url:' + url,
}))
jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
	translatePlural: (app, text) => text,
}))

const GOLDEN = path.join(__dirname, '../../tests/jest/golden/store-requests.json')
const RECORD = process.env.RECORD_GOLDEN === '1'
const golden = RECORD ? {} : JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))

const STORES = {
	template: useTemplateStore,
	prohibition: useProhibitionStore,
	customDictionary: useCustomDictionaryStore,
}

const SEED = {
	template: { templates: [{ id: 't1' }, { id: 't2' }], selectedLanguage: 'nl' },
	prohibition: {
		prohibitions: [{ id: 'p1' }, { '@self': { id: 'p2' } }, { uuid: 'p3' }],
	},
	customDictionary: {
		dictionaries: [{ id: 'd1' }, { '@self': { id: 'd2' } }],
		terms: [{ id: 'x1' }, { uuid: 'x2' }],
	},
}

const STORE_CASES = [
	['template', 'fetchTemplates', [{ type: 'letter', empty: '', none: null }]],
	['template', 'fetchTemplates', []],
	['template', 'fetchTemplate', ['t1']],
	['template', 'createTemplate', [{ name: 'n' }]],
	['template', 'updateTemplate', ['t1', { name: 'n' }]],
	['template', 'deleteTemplate', ['t1']],
	['template', 'duplicateTemplate', ['t2']],
	['template', 'fetchVersions', ['t1']],
	['template', 'restoreVersion', ['t1', 'v1']],
	['template', 'previewTemplate', ['<p>{{x}}</p>', { x: 1 }]],
	['template', 'previewTemplate', ['<p/>']],
	['template', 'acquireLock', ['t1']],
	['template', 'releaseLock', ['t1']],
	['prohibition', 'fetchProhibitions', []],
	['prohibition', 'fetchProhibition', ['p1']],
	['prohibition', 'createProhibition', [{ name: 'n' }]],
	['prohibition', 'updateProhibition', ['p2', { name: 'n' }]],
	['prohibition', 'updateProhibition', ['p9', { name: 'n' }]],
	['prohibition', 'deleteProhibition', ['p3']],
	['customDictionary', 'fetchCapabilities', []],
	['customDictionary', 'fetchDictionaries', []],
	['customDictionary', 'fetchDictionary', ['d1']],
	['customDictionary', 'createDictionary', [{ name: 'n' }]],
	['customDictionary', 'updateDictionary', ['d2', { name: 'n' }]],
	['customDictionary', 'updateDictionary', ['d9', { name: 'n' }]],
	['customDictionary', 'deleteDictionary', ['d1']],
	['customDictionary', 'fetchTerms', ['d1']],
	['customDictionary', 'createTerm', ['d1', { term: 'x' }]],
	['customDictionary', 'deleteTerm', ['d1', 'x2']],
	['customDictionary', 'importTerms', ['d1', { content: 'a\nb' }]],
]

const SERVICE_CASES = [
	['classification', 'listPending', []],
	['classification', 'loadForFile', ['7']],
	['classification', 'confirm', [7, { type: 'invoice' }]],
	['classification', 'reject', [7]],
	['classification', 'dossierOptions', []],
	['emailIngestion', 'listEmails', [{ status: 'failed', dossier: '' }]],
	['emailIngestion', 'rescan', []],
	['emailIngestion', 'retryConversion', ['u1']],
	['emailIngestion', 'loadSettings', []],
	['emailIngestion', 'saveSettings', [[{ mailbox: 'm' }], 5]],
	['wizard', 'loadTemplateWizard', ['t1']],
	['wizard', 'saveWizard', [{ uuid: 'w1', questions: [] }]],
	['wizard', 'saveWizard', [{ questions: [] }]],
	['wizard', 'deleteWizard', ['w1']],
	['wizard', 'listWizardsFor', ['r', 's']],
	['wizard', 'prefillWizard', ['w1', { a: 1 }]],
]

const SERVICES = { classification, emailIngestion, wizard }

const MODES = {
	ok: () => null,
	serverError: () => {
		const err = new Error('boom')
		err.response = {
			status: 409,
			data: { error: 'refused by server', errors: { a: 'bad' } },
		}
		return err
	},
	plainError: () => new Error('network down'),
	emptyStatus: () => {
		const err = new Error('odd')
		err.response = { status: 0, data: { error: '' } }
		return err
	},
}

/**
 * A JSON-safe copy of a value.
 *
 * @param {*} value anything
 * @return {*} the copy
 */
function plain(value) {
	if (value === undefined) {
		return '(undefined)'
	}
	if (value instanceof Error) {
		return { thrown: value.message }
	}
	if (typeof FormData !== 'undefined' && value instanceof FormData) {
		return { formData: [...value.keys()] }
	}
	return JSON.parse(JSON.stringify(value))
}

/**
 * Wire every axios method to answer, or refuse, and record its calls.
 *
 * @param {string} mode a key of MODES
 * @param {Function} probe read when a call is made
 * @return {Array} the recorded calls
 */
function wireAxios(mode, probe) {
	const calls = []
	for (const method of ['get', 'post', 'put', 'delete']) {
		axios[method].mockReset()
		axios[method].mockImplementation((...args) => {
			calls.push({ method, args: args.map(plain), seen: probe() })
			const err = MODES[mode]()
			if (err) {
				return Promise.reject(err)
			}
			return Promise.resolve({
				data: {
					echo: method,
					results: [{ id: 'r1' }],
					total: 1,
					html: '<p/>',
					canMaintain: true,
				},
				headers: {
					'content-language': 'nl',
					'x-content-language-fallback': 'true',
				},
			})
		})
	}
	return calls
}

/**
 * Run one case and describe everything it did.
 *
 * @param {Function} invoke calls the action
 * @param {Array} calls the recorded axios calls
 * @return {Promise<object>} the outcome
 */
async function outcome(invoke, calls) {
	const logged = []
	const spy = jest.spyOn(console, 'error').mockImplementation((...args) => {
		logged.push(args.map(plain))
	})
	let result
	try {
		result = { returned: plain(await invoke()) }
	} catch (err) {
		result = { threw: plain(err) }
	}
	spy.mockRestore()
	return { calls, logged, ...result }
}

/**
 * Compare with the golden, or record into it.
 *
 * @param {string} name the case name
 * @param {object} actual what happened
 */
function check(name, actual) {
	if (RECORD) {
		golden[name] = actual
		return
	}
	expect(actual).toEqual(golden[name])
}

afterAll(() => {
	if (RECORD) {
		fs.writeFileSync(GOLDEN, JSON.stringify(golden, null, '\t') + '\n')
	}
})

describe('store request bookkeeping golden', () => {
	for (const [storeName, action, args] of STORE_CASES) {
		for (const mode of Object.keys(MODES)) {
			const name = `${storeName}.${action}(${JSON.stringify(args)}) ${mode}`
			it(name, async () => {
				setActivePinia(createPinia())
				const store = STORES[storeName]()
				store.$patch(JSON.parse(JSON.stringify(SEED[storeName])))
				store.error = 'stale'
				const calls = wireAxios(mode, () => ({
					loading: store.loading,
					termsLoading: store.termsLoading ?? '(none)',
					error: store.error,
				}))
				const result = await outcome(() => store[action](...args), calls)
				check(name, { ...result, state: plain(store.$state) })
			})
		}
	}
})

describe('service answer golden', () => {
	for (const [service, fn, args] of SERVICE_CASES) {
		for (const mode of Object.keys(MODES)) {
			const name = `${service}.${fn}(${JSON.stringify(args)}) ${mode}`
			it(name, async () => {
				const calls = wireAxios(mode, () => null)
				check(
					name,
					await outcome(() => SERVICES[service][fn](...args), calls),
				)
			})
		}
	}
})

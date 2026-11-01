/**
 * Golden: the script logic of the four admin index pages (publish always,
 * publish never, consent workflow, custom dictionaries), recorded on the
 * per-page implementations. The stores are recording fakes.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing index page behaviour
 */

import { join } from 'node:path'
import { describe, it, vi } from 'vitest'
import { golden } from './golden/golden.js'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

const calls = []
const failNext = { value: null }

/**
 * A store whose state reads come from `state` and whose methods record their
 * call and resolve, or reject once with the error put in `failNext`.
 *
 * @param {string} name Store name used in the call log.
 * @param {object} state State object, reset in place between cases.
 * @return {object} The fake store.
 */
function fakeStore(name, state) {
	return new Proxy(state, {
		get(target, key) {
			if (key in target) {
				return target[key]
			}
			return async (...args) => {
				calls.push([name, key, ...args])
				if (failNext.value) {
					const err = failNext.value
					failNext.value = null
					throw err
				}
				return { id: 'created-1', label: 'Created' }
			}
		},
	})
}

const stores = vi.hoisted(() => ({}))

vi.mock('@nextcloud/l10n', () => ({
	getLanguage: () => 'nl',
	translate: (app, text, vars) =>
		'[' + text.replace(/\{(\w+)\}/g, (_, k) => String(vars?.[k] ?? '')) + ']',
}))

vi.mock('../../src/store/store.js', () => stores)

const ROWS = [
	{
		'@self': { id: 'self-1' },
		id: 'id-1',
		entityText: 'Jan',
		primaryName: 'Jan',
		label: 'Dict',
		matchRules: [
			{ type: 'bsn', value: '123', extra: true },
			{ type: 'exact', value: 'Jan' },
		],
		active: false,
		severity: 'high',
		scope: 'document',
	},
	{ uuid: 'uuid-2', matchRules: 'not-an-array', scope: 'entity' },
	{ id: 'id-3', consentStatus: 'objection_received' },
]

const STORE_STATE = {
	standingConsentStore: [
		'standingConsent',
		'standingConsents',
		'standingConsentStats',
	],
	prohibitionStore: ['prohibition', 'prohibitions', 'prohibitionStats'],
	consentStore: ['consent', 'consents', null],
	customDictionaryStore: ['customDictionary', 'dictionaries', null],
}

const states = {}
for (const [store, [name]] of Object.entries(STORE_STATE)) {
	states[store] = {}
	stores[store] = fakeStore(name, states[store])
}

/**
 * Reset the fake stores' state in place, so components keep their binding.
 *
 * @param {Array} rows Records every list holds.
 */
function resetStores(rows) {
	calls.length = 0
	failNext.value = null
	for (const [store, [, list, stats]] of Object.entries(STORE_STATE)) {
		const state = states[store]
		for (const k of Object.keys(state)) {
			delete state[k]
		}
		Object.assign(state, { [list]: rows, loading: false, error: null })
		if (stats) {
			state[stats] = { total: 3, active: 1, inactive: 2 }
		}
	}
}

const STATE = [
	'isRefreshing',
	'currentPage',
	'pageSize',
	'dialogOpen',
	'saving',
	'editing',
	'editingRecord',
	'formError',
	'deleteTarget',
	'deleting',
	'paginationData',
	'emptyText',
	'emptyContentName',
	'deleteMessage',
	'tableColumns',
	'workflowConsents',
]

/**
 * The observable state of an instance plus the calls made since last time.
 *
 * @param {object} vm Instance.
 * @param {Array} errors console.error calls.
 * @return {object} Snapshot.
 */
function snapshot(vm, errors) {
	const out = {}
	for (const key of STATE) {
		if (key in vm || Object.getOwnPropertyDescriptor(vm, key)) {
			out[key] = vm[key] === undefined ? '(undefined)' : vm[key]
		}
	}
	out.calls = calls.splice(0)
	out.emitted = vm.$emitted.splice(0)
	out.pushed = vm.$pushed.splice(0)
	out.errors = errors
		.splice(0)
		.map(([msg, err]) => [msg, err?.message ?? String(err)])
	return out
}

/**
 * Call a method if the page has it and record the outcome.
 *
 * @param {object} ctx Instance, record and errors.
 * @param {Array} step Step name, method name and arguments.
 */
async function run(ctx, step) {
	const [label, method, ...args] = step
	if (typeof ctx.vm[method] !== 'function') {
		ctx.record[label] = '(no method)'
		failNext.value = null
		return
	}
	const value = await ctx.vm[method](...args)
	ctx.record[label] = {
		returned: value === undefined ? '(undefined)' : value,
		...snapshot(ctx.vm, ctx.errors),
	}
}

const PAGES = {
	standingConsent: 'views/policy/StandingConsentIndex.vue',
	prohibition: 'views/policy/ProhibitionIndex.vue',
	consent: 'views/consent/ConsentIndex.vue',
	customDictionary: 'views/customDictionary/CustomDictionaryIndex.vue',
}

const FORMATTERS = [
	['formatMatchRules', [ROWS[0].matchRules, [], null, 'x']],
	['formatStatus', ['pending', 'consent_given', 'skipped', 'odd', '', undefined]],
	['formatDecision', ['reject', 'publish_anonymized', 'odd', null]],
	['formatDate', [null, '', '2026-01-02T00:00:00Z']],
	['matchModeLabel', ['exact', 'wordBoundary', 'odd', '']],
	['displayLabel', [ROWS[0], { label: { nl: 'NL', en: 'EN' } }, {}]],
]

describe('policy index pages golden', () => {
	for (const [key, rel] of Object.entries(PAGES)) {
		it(key, async () => {
			resetStores(ROWS)
			const errors = []
			const spy = vi
				.spyOn(console, 'error')
				.mockImplementation((...a) => errors.push(a))
			const options = await loadSfcOptions(
				join(import.meta.dirname, '../../src', rel),
			)
			const vm = createInstance(options)
			const record = { initial: snapshot(vm, errors) }
			const ctx = { vm, record, errors }
			await vm.$hook('mounted')
			record.mounted = snapshot(vm, errors)

			for (const [method, inputs] of FORMATTERS) {
				if (typeof vm[method] === 'function') {
					record[method] = inputs.map(
						(v) => vm[method](v) ?? '(undefined)',
					)
				}
			}

			await run(ctx, ['refresh', 'handleRefresh'])
			await run(ctx, ['page3', 'onPageChanged', 3])
			await run(ctx, ['size50', 'onPageSizeChanged', 50])
			await run(ctx, ['create', 'openCreateDialog'])
			await run(ctx, ['submitCreate', 'onModalSubmit', { a: 1 }])
			await run(ctx, ['submitCreateDialog', 'onCreateSubmit', { a: 1 }])
			await run(ctx, ['editFull', 'openEditDialog', ROWS[0]])
			await run(ctx, ['submitUpdate', 'onModalSubmit', { a: 2 }])
			await run(ctx, ['editEmpty', 'openEditDialog', ROWS[1]])
			failNext.value = { response: { data: { error: 'server says no' } } }
			await run(ctx, ['failResponse', 'onModalSubmit', { a: 3 }])
			failNext.value = new Error('network down')
			await run(ctx, ['failMessage', 'onCreateSubmit', { a: 3 }])
			failNext.value = {}
			await run(ctx, ['failBare', 'onModalSubmit', { a: 4 }])
			await run(ctx, ['createAfterFail', 'openCreateDialog'])
			await run(ctx, ['editById', 'openEditDialog', ROWS[2]])
			await run(ctx, ['deleteNone', 'executeDelete'])
			await run(ctx, ['confirm', 'confirmDelete', ROWS[0]])
			await run(ctx, ['cancel', 'cancelDelete'])
			await run(ctx, ['confirmUuid', 'confirmDelete', ROWS[1]])
			await run(ctx, ['deleteOk', 'executeDelete'])
			await run(ctx, ['confirmSelf', 'confirmDelete', ROWS[0]])
			await run(ctx, ['deleteSelf', 'executeDelete'])
			await run(ctx, ['confirmAgain', 'confirmDelete', ROWS[2]])
			failNext.value = new Error('delete refused')
			await run(ctx, ['deleteFail', 'executeDelete'])
			await run(ctx, ['view', 'viewConsent', ROWS[0]])
			await run(ctx, ['viewUuid', 'viewConsent', ROWS[1]])
			await run(ctx, ['viewDict', 'viewDictionary', ROWS[1]])

			resetStores([])
			states[key + 'Store'].error = 'load failed'
			const empty = createInstance(options)
			record.emptyWithError = snapshot(empty, errors)

			spy.mockRestore()
			golden('policyIndexPages-' + key, record)
		})
	}
})

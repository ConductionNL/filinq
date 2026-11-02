/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden test for the publish never and publish always index pages: the
 * rendered template (child components stubbed, the index page stand-in
 * rendering every slot for a sample row) and what every event of the index
 * page and every row action does to the page. RECORD_GOLDEN=1 rewrites the
 * golden file.
 */

import fs from 'fs'
import path from 'path'
import ProhibitionIndex from './ProhibitionIndex.vue'
import StandingConsentIndex from './StandingConsentIndex.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'

const ROW = {
	id: 'row-1',
	primaryName: 'Jan Jansen',
	entityText: 'Jan Jansen',
	entityType: 'PERSON',
	severity: 'high',
	consentMethod: 'paper',
	active: false,
	matchRules: [{ type: 'bsn', value: '123456782' }],
}

const BARE_ROW = { uuid: 'row-2', matchRules: [] }

jest.mock(
	'@nextcloud/l10n',
	() => ({
		translate: (app, text, vars) =>
			vars ? text + ' ' + JSON.stringify(vars) : text,
	}),
	{ virtual: true },
)
global.mockStubs ??= []
global.mockIndexPage ??= { attrs: null }

jest.mock(
	'@nextcloud/vue',
	() => {
		const { stubComponent } = require('../../../tests/jest/renderGolden.js')
		return {
			__esModule: true,
			NcActionButton: stubComponent(
				'NcActionButton',
				(global.mockStubs ??= []),
			),
			NcActions: stubComponent('NcActions', (global.mockStubs ??= [])),
		}
	},
	{ virtual: true },
)
// vue3-jest compiles the script and the template of a `<script setup>`
// component separately and both name their first import `_vue`, so the
// template's `$setup.NcActions` reads `vue.NcActions`. Expose the stubs there.
jest.mock('vue', () => {
	const vue = { ...jest.requireActual('vue') }
	for (const name of ['NcActionButton', 'NcActions']) {
		Object.defineProperty(vue, name, {
			get: () => require('@nextcloud/vue')[name],
		})
	}
	return vue
})
jest.mock(
	'@conduction/nextcloud-vue',
	() => {
		const { defineComponent, h } = require('vue')
		const { stubComponent } = require('../../../tests/jest/renderGolden.js')
		return {
			CnConfirmDialog: stubComponent(
				'CnConfirmDialog',
				(global.mockStubs ??= []),
			),
			CnStatsBlock: stubComponent('CnStatsBlock', (global.mockStubs ??= [])),
			CnStatusBadge: stubComponent('CnStatusBadge', (global.mockStubs ??= [])),
			// Prints its attributes and renders every slot, in name order, for two sample rows.
			CnIndexPage: defineComponent({
				name: 'CnIndexPage',
				inheritAttrs: false,
				setup(props, { attrs, slots }) {
					;(global.mockIndexPage ??= {}).attrs = attrs
					return () => {
						const plain = {}
						for (const [key, value] of Object.entries(attrs)) {
							if (typeof value === 'function') {
								plain['data-on-' + key.toLowerCase()] = ''
							} else if (value !== null && typeof value === 'object') {
								plain['data-' + key.toLowerCase()] =
									JSON.stringify(value)
							} else {
								plain[key] = value
							}
						}
						const children = Object.keys(slots)
							.sort()
							.flatMap((name) =>
								global.SAMPLE_ROWS.map((row) =>
									h('x-slot', { name }, slots[name]({ row })),
								),
							)
						return h('x-cnindexpage', plain, children)
					}
				},
			}),
		}
	},
	{ virtual: true },
)
jest.mock('vue-material-design-icons/Delete.vue', () => ({
	__esModule: true,
	default: require('../../../tests/jest/renderGolden.js').stubComponent(
		'Delete',
		(global.mockStubs ??= []),
	),
}))
jest.mock('vue-material-design-icons/DotsHorizontal.vue', () => ({
	__esModule: true,
	default: require('../../../tests/jest/renderGolden.js').stubComponent(
		'DotsHorizontal',
		(global.mockStubs ??= []),
	),
}))
jest.mock('vue-material-design-icons/Pencil.vue', () => ({
	__esModule: true,
	default: require('../../../tests/jest/renderGolden.js').stubComponent(
		'Pencil',
		(global.mockStubs ??= []),
	),
}))
jest.mock('../../dialogs/ProhibitionFormModal.vue', () => ({
	__esModule: true,
	default: require('../../../tests/jest/renderGolden.js').stubComponent(
		'ProhibitionFormModal',
		(global.mockStubs ??= []),
	),
}))
jest.mock('../../dialogs/StandingConsentFormModal.vue', () => ({
	__esModule: true,
	default: require('../../../tests/jest/renderGolden.js').stubComponent(
		'StandingConsentFormModal',
		(global.mockStubs ??= []),
	),
}))

const mockStoreCalls = []

/**
 * A store whose methods record their calls and resolve.
 *
 * @param {string} name The store name.
 * @param {object} state Its state.
 * @return {object} The store.
 */
function mockStore(name, state) {
	return new Proxy(state, {
		get(target, key) {
			if (key in target) {
				return target[key]
			}
			return (...args) => {
				mockStoreCalls.push([name, key, ...args])
				return Promise.resolve({})
			}
		},
	})
}

jest.mock('../../store/store.js', () => ({
	prohibitionStore: mockStore('prohibition', {
		prohibitions: [1, 2, 3],
		loading: false,
		error: '',
		prohibitionStats: { total: 3, active: 2, inactive: 1 },
	}),
	standingConsentStore: mockStore('standingConsent', {
		standingConsents: [1, 2],
		loading: true,
		error: 'load failed',
		standingConsentStats: { total: 2, active: 0, inactive: 2 },
	}),
}))

const GOLDEN = path.join(
	__dirname,
	'../../../tests/jest/golden/policy-index-pages.json',
)
const RECORD = process.env.RECORD_GOLDEN === '1'
const golden = RECORD ? {} : JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))

const PAGES = {
	prohibition: ProhibitionIndex,
	standingConsent: StandingConsentIndex,
}

const EVENTS = [
	['onRefresh', []],
	['onPageChanged', [3]],
	['onPageSizeChanged', [50]],
	['onAdd', []],
]

/**
 * The page data as plain JSON.
 *
 * @param {object} vm The page.
 * @return {object} Its data.
 */
function dataOf(vm) {
	return JSON.parse(JSON.stringify(vm.$data))
}

/**
 * The rendered HTML without the template's own comments, which a production
 * build drops. Vue's fragment and v-if markers stay.
 *
 * @param {string} html The rendered HTML.
 * @return {string} The HTML.
 */
function withoutAuthorComments(html) {
	return html.replace(/<!--([\s\S]*?)-->/g, (all, text) =>
		['', '[', ']', 'v-if'].includes(text) ? all : '',
	)
}

afterAll(() => {
	if (RECORD) {
		fs.writeFileSync(GOLDEN, JSON.stringify(golden, null, '\t') + '\n')
	}
})

describe('policy index pages golden', () => {
	for (const [name, component] of Object.entries(PAGES)) {
		it(name, async () => {
			global.SAMPLE_ROWS = [ROW, BARE_ROW]
			mockStoreCalls.length = 0
			global.mockStubs.length = 0
			const page = await renderStubbed(component, {
				real: ['CnIndexPage'],
			})
			const actual = {
				html: withoutAuthorComments(page.html())
					.split('><')
					.join('>\n<')
					.split('\n'),
				mountCalls: JSON.parse(JSON.stringify(mockStoreCalls)),
				events: {},
				actions: [],
			}
			const attrs = global.mockIndexPage.attrs
			for (const [event, args] of EVENTS) {
				mockStoreCalls.length = 0
				const before = dataOf(page.vm)
				const listener = attrs[event]
				if (listener) {
					await listener(...args)
				}
				actual.events[event] = {
					present: Boolean(listener),
					calls: JSON.parse(JSON.stringify(mockStoreCalls)),
					data: dataOf(page.vm),
					changed:
						JSON.stringify(before) !== JSON.stringify(dataOf(page.vm)),
				}
			}
			// A `<script setup>` page binds the module's stubs, which register
			// in global.mockStubs instead of the harness registry.
			for (const stub of page.stubs
				.concat(global.mockStubs)
				.filter((s) => s.name === 'NcActionButton')) {
				page.vm.deleteTarget = null
				page.vm.dialogOpen = false
				stub.attrs.onClick()
				actual.actions.push(dataOf(page.vm))
			}
			page.unmount()
			if (RECORD) {
				golden[name] = actual
			} else {
				expect(actual.html).toEqual(golden[name].html)
				expect(actual.mountCalls).toEqual(golden[name].mountCalls)
				expect(actual.events).toEqual(golden[name].events)
				expect(actual.actions).toEqual(golden[name].actions)
			}
		})
	}
})

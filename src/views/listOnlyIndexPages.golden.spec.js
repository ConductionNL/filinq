/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden test for the props the list-only index pages hand CnIndexPage:
 * every attribute by name, after mount, with stores and services faked.
 * RECORD_GOLDEN=1 rewrites the golden file.
 */

import fs from 'fs'
import path from 'path'
import { createApp, nextTick } from 'vue'
import ClassificationPending from './classification/ClassificationPending.vue'
import ConsentIndex from './consent/ConsentIndex.vue'
import CustomDictionaryIndex from './customDictionary/CustomDictionaryIndex.vue'
import DossierIndex from './dossier/DossierIndex.vue'
import EmailIngestion from './emailIngestion/EmailIngestion.vue'
import PolicyRuleIndex from './policy/PolicyRuleIndex.vue'

global.mockIndexPageAttrs ??= { value: null }

jest.mock('@nextcloud/l10n', () => ({
	getLanguage: () => 'nl',
	translate: (app, text, vars) =>
		vars ? text + ' ' + JSON.stringify(vars) : text,
	translatePlural: (app, one, many, count) =>
		(count === 1 ? one : many) + ' #' + count,
}))
jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: {
		get: () => Promise.resolve({ data: { results: [], inboxes: [] } }),
		post: () => Promise.resolve({ data: {} }),
		put: () => Promise.resolve({ data: {} }),
	},
}))
jest.mock('@nextcloud/router', () => ({
	generateUrl: (url) => '/index.php' + url,
	generateOcsUrl: (url) => '/ocs/v2.php' + url,
}))
jest.mock('@conduction/nextcloud-vue', () => {
	const { defineComponent, h } = require('vue')
	const { stubComponent } = require('../../tests/jest/renderGolden.js')
	const stubs = []
	return {
		CnConfirmDialog: stubComponent('CnConfirmDialog', stubs),
		CnStatsBlock: stubComponent('CnStatsBlock', stubs),
		CnStatusBadge: stubComponent('CnStatusBadge', stubs),
		CnIndexPage: defineComponent({
			name: 'CnIndexPage',
			inheritAttrs: false,
			setup(props, { attrs }) {
				global.mockIndexPageAttrs.value = attrs
				return () => h('x-cnindexpage')
			},
		}),
	}
})
jest.mock('@nextcloud/vue', () => {
	const { stubComponent } = require('../../tests/jest/renderGolden.js')
	const stubs = []
	return {
		__esModule: true,
		NcActionButton: stubComponent('NcActionButton', stubs),
		NcActions: stubComponent('NcActions', stubs),
		NcButton: stubComponent('NcButton', stubs),
		NcSelect: stubComponent('NcSelect', stubs),
	}
})
// vue3-jest compiles the script and the template of a `<script setup>`
// component separately and both name their first import `_vue`, so the
// template's `$setup.X` reads `vue.X`. Expose the library stubs there.
jest.mock('vue', () => {
	const vue = { ...jest.requireActual('vue') }
	for (const name of ['NcActionButton', 'NcActions']) {
		Object.defineProperty(vue, name, {
			get: () => require('@nextcloud/vue')[name],
		})
	}
	for (const name of [
		'CnIndexPage',
		'CnStatsBlock',
		'CnStatusBadge',
		'CnConfirmDialog',
	]) {
		Object.defineProperty(vue, name, {
			get: () => require('@conduction/nextcloud-vue')[name],
		})
	}
	return vue
})
jest.mock('../dialogs/PolicyRuleFormModal.vue', () => ({
	__esModule: true,
	default: require('../../tests/jest/renderGolden.js').stubComponent(
		'PolicyRuleFormModal',
		[],
	),
}))
jest.mock('../dialogs/DossierFormModal.vue', () => ({
	__esModule: true,
	default: require('../../tests/jest/renderGolden.js').stubComponent(
		'DossierFormModal',
		[],
	),
}))
jest.mock('../dialogs/CustomDictionaryFormDialog.vue', () => ({
	__esModule: true,
	default: require('../../tests/jest/renderGolden.js').stubComponent(
		'CustomDictionaryFormDialog',
		[],
	),
}))
jest.mock('../modals/ClassificationCorrectModal.vue', () => ({
	__esModule: true,
	default: require('../../tests/jest/renderGolden.js').stubComponent(
		'ClassificationCorrectModal',
		[],
	),
}))
jest.mock('vue-material-design-icons/Delete.vue', () => ({
	__esModule: true,
	default: require('../../tests/jest/renderGolden.js').stubComponent('Delete', []),
}))
jest.mock('vue-material-design-icons/DotsHorizontal.vue', () => ({
	__esModule: true,
	default: require('../../tests/jest/renderGolden.js').stubComponent(
		'DotsHorizontal',
		[],
	),
}))
jest.mock('vue-material-design-icons/Eye.vue', () => ({
	__esModule: true,
	default: require('../../tests/jest/renderGolden.js').stubComponent('Eye', []),
}))
jest.mock('vue-material-design-icons/Pencil.vue', () => ({
	__esModule: true,
	default: require('../../tests/jest/renderGolden.js').stubComponent('Pencil', []),
}))

/**
 * A store whose state reads come from `state` and whose methods resolve.
 *
 * @param {object} state Its state.
 * @return {object} The store.
 */
function mockStore(state) {
	return new Proxy(state, {
		get(target, key) {
			if (key in target) {
				return target[key]
			}
			return () => Promise.resolve([])
		},
	})
}

jest.mock('../store/store.js', () => ({
	prohibitionStore: mockStore({
		prohibitions: [],
		loading: false,
		error: '',
		prohibitionStats: { total: 0, active: 0, inactive: 0 },
	}),
	standingConsentStore: mockStore({
		standingConsents: [],
		loading: false,
		error: '',
		standingConsentStats: { total: 0, active: 0, inactive: 0 },
	}),
	dossierStore: mockStore({
		dossiers: [{ id: 'd-1', title: 'Dossier' }],
		loading: true,
	}),
	consentStore: mockStore({ consents: [], consentList: [], loading: false }),
	customDictionaryStore: mockStore({
		dictionaries: [],
		loading: false,
		canMaintain: true,
	}),
}))

const GOLDEN = path.join(
	__dirname,
	'../../tests/jest/golden/list-only-index-pages.json',
)
const RECORD = process.env.RECORD_GOLDEN === '1'
const golden = RECORD ? {} : JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))

const PAGES = {
	ClassificationPending: [ClassificationPending, {}],
	ConsentIndex: [ConsentIndex, {}],
	CustomDictionaryIndex: [CustomDictionaryIndex, {}],
	DossierIndex: [DossierIndex, {}],
	EmailIngestion: [EmailIngestion, {}],
	'PolicyRuleIndex prohibition': [PolicyRuleIndex, { surface: 'prohibition' }],
	'PolicyRuleIndex standingConsent': [
		PolicyRuleIndex,
		{ surface: 'standingConsent' },
	],
}

/**
 * The attributes as JSON, sorted by name, with listeners and functions as markers.
 *
 * @param {object} attrs The attributes CnIndexPage received.
 * @return {object} The attributes.
 */
function plain(attrs) {
	const out = {}
	for (const key of Object.keys(attrs).sort()) {
		const value = attrs[key]
		out[key] =
			typeof value === 'function'
				? '(function)'
				: JSON.parse(JSON.stringify(value ?? null))
	}
	return out
}

afterAll(() => {
	if (RECORD) {
		fs.writeFileSync(GOLDEN, JSON.stringify(golden, null, '\t') + '\n')
	}
})

describe('list-only index pages', () => {
	it.each(Object.keys(PAGES))(
		'%s hands CnIndexPage the recorded props',
		async (name) => {
			const [component, props] = PAGES[name]
			global.mockIndexPageAttrs.value = null
			const el = document.createElement('div')
			document.body.appendChild(el)
			const app = createApp(component, props)
			app.config.globalProperties.t = (appId, text, vars) =>
				vars ? text + ' ' + JSON.stringify(vars) : text
			app.config.globalProperties.n = (appId, one, many, count) =>
				(count === 1 ? one : many) + ' #' + count
			app.config.globalProperties.$router = { push: () => {} }
			app.config.globalProperties.$route = { params: {}, query: {} }
			app.config.errorHandler = () => {}
			app.config.warnHandler = () => {}
			app.mount(el)
			await nextTick()
			await nextTick()
			const attrs = plain(global.mockIndexPageAttrs.value || {})
			app.unmount()
			el.remove()

			expect(Object.keys(attrs).length).toBeGreaterThan(10)
			if (RECORD) {
				golden[name] = attrs
			}
			expect(attrs).toEqual(golden[name])
		},
	)
})

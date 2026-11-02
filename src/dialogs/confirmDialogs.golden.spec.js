/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden test for the confirm dialogs the pages open before a delete, a
 * removal or a restore. Each page is mounted with its children stubbed except
 * the confirm dialog, which renders for real down to the dialog frame and the
 * buttons. Per state (confirm, loading, success, error) the test records what
 * the user sees: the dialog title, its text, and each button's label, variant
 * and disabled flag, plus the store calls the page made. RECORD_GOLDEN=1
 * rewrites the golden file.
 */

const fs = require('fs')
const path = require('path')
const { nextTick } = require('vue')

jest.mock(
	'@nextcloud/l10n',
	() => ({
		translate: (app, text, vars) =>
			vars ? text + ' ' + JSON.stringify(vars) : text,
		translatePlural: (app, one, many, count) =>
			(count === 1 ? one : many) + ' #' + count,
	}),
	{ virtual: true },
)

/**
 * The Nextcloud components the pages and dialogs use, as printing stubs.
 *
 * @return {object} Name to stub.
 */
function mockNcStubs() {
	const { stubComponent } = require('../../tests/jest/renderGolden.js')
	const names = [
		'NcActionButton',
		'NcActions',
		'NcButton',
		'NcDialog',
		'NcEmptyContent',
		'NcLoadingIcon',
		'NcNoteCard',
		'NcTextField',
		'NcSelect',
		'NcCheckboxRadioSwitch',
	]
	const stubs = {}
	for (const name of names) {
		stubs[name] = stubComponent(name, (global.mockStubs ??= []))
	}
	stubs.NcButton = mockClickable(stubs.NcButton)
	return stubs
}

/**
 * A printing stub whose element runs the component's click listener.
 *
 * @param {object} stub The printing stub.
 * @return {object} The stub, clickable.
 */
function mockClickable(stub) {
	const { defineComponent, h } = jest.requireActual('vue')
	return defineComponent({
		name: stub.name,
		inheritAttrs: false,
		setup(props, context) {
			const render = stub.setup(props, context)
			return () => {
				const vnode = render()
				return h(
					vnode.type,
					{ ...vnode.props, onClick: context.attrs.onClick },
					vnode.children,
				)
			}
		},
	})
}

jest.mock('@nextcloud/vue', () => ({ __esModule: true, ...mockNcStubs() }), {
	virtual: true,
})

// vue3-jest compiles the script and the template of a `<script setup>`
// component separately and both name their first import `_vue`, so the
// template's `$setup.NcActions` reads `vue.NcActions`. Expose the stubs there.
jest.mock('vue', () => {
	const vue = { ...jest.requireActual('vue') }
	for (const name of [
		'NcActionButton',
		'NcActions',
		'NcButton',
		'NcEmptyContent',
		'NcLoadingIcon',
		'NcNoteCard',
		'NcTextField',
	]) {
		Object.defineProperty(vue, name, {
			get: () => require('@nextcloud/vue')[name],
		})
	}
	return vue
})

jest.mock(
	'@conduction/nextcloud-vue',
	() => {
		const path = require('path')
		const { loadSource } = require('../../tests/jest/loadSfc.js')
		const { stubComponent } = require('../../tests/jest/renderGolden.js')
		const src = path.join(
			__dirname,
			'../../node_modules/@conduction/nextcloud-vue/src',
		)
		const stub = (name) => stubComponent(name, (global.mockStubs ??= []))
		const CnConfirmDialog = loadSource(
			path.join(src, 'dialogs/CnConfirmDialog.vue'),
			require,
			{
				[path.join(src, 'components/CnDialog/CnDialog.vue')]: {
					__esModule: true,
					default: stub('CnDialog'),
				},
				[path.join(src, 'components/CnIcon/index.js')]: {
					__esModule: true,
					CnIcon: stub('CnIcon'),
				},
				[path.join(
					src,
					'components/CnConfirmValueField/CnConfirmValueField.vue',
				)]: { __esModule: true, default: stub('CnConfirmValueField') },
			},
		).default
		return {
			__esModule: true,
			CnConfirmDialog,
			CnDataTable: stub('CnDataTable'),
			CnIndexPage: stub('CnIndexPage'),
			CnStatsBlock: stub('CnStatsBlock'),
			CnStatusBadge: stub('CnStatusBadge'),
		}
	},
	{ virtual: true },
)

const {
	renderStubbed: render,
	stubComponent,
} = require('../../tests/jest/renderGolden.js')

const mockCalls = []
const mockPending = []
// Errors a page's handlers leave to Vue, recorded by the app error handler.
const unhandled = []

/**
 * A call that stays pending until the test settles it.
 *
 * @param {Array} call What was called, for the record.
 * @return {Promise} The pending answer.
 */
function pendingCall(call) {
	mockCalls.push(call)
	const answer = new Promise((resolve, reject) =>
		mockPending.push({ call, resolve, reject }),
	)
	// A page that drops the promise must not crash the run when it is rejected.
	answer.catch(() => {})
	return answer
}

const STORE_VALUES = {
	dossierStore: {
		saving: false,
		dossier: null,
		selectedDocument: null,
		selectedDocumentId: null,
		loading: false,
		error: null,
	},
	customDictionaryStore: {
		dictionaries: [],
		dictionaryItem: null,
		terms: [],
		loading: false,
		error: null,
		importResult: null,
	},
	prohibitionStore: {
		prohibitions: [],
		loading: false,
		error: '',
		prohibitionStats: {},
	},
	standingConsentStore: {
		standingConsents: [],
		loading: false,
		error: '',
		standingConsentStats: {},
	},
	myDocumentsStore: {
		documents: [],
		folders: [],
		loading: false,
		error: null,
		currentPath: '/',
		selected: [],
	},
	fileViewerStore: { open: false },
	useTemplateStore: {
		templateItem: { id: 'tpl-1', content: '' },
		templates: [],
		versions: [],
		loading: false,
		error: null,
	},
}

/**
 * A store or service module: listed values are returned as they are, every
 * other key is a function whose call is recorded and stays pending.
 *
 * @param {string} name The module name, for the record.
 * @param {object} values The values to keep.
 * @return {object} The module.
 */
function fakeModule(name, values = {}) {
	return new Proxy(values, {
		get(target, key) {
			if (key in target) {
				return target[key]
			}
			if (key === '__esModule' || typeof key === 'symbol' || key === 'then') {
				return undefined
			}
			if (/^use[A-Z]\w*Store$/.test(key)) {
				return () => fakeModule(key, { ...STORE_VALUES[key] })
			}
			return (...args) =>
				pendingCall([
					name,
					key,
					...args.filter((a) => typeof a !== 'function'),
				])
		},
	})
}

const KEEP = new Set([
	'vue',
	'@nextcloud/l10n',
	'@nextcloud/vue',
	'@conduction/nextcloud-vue',
])
const CONFIRM_DIALOGS =
	/\/(ConfirmActionDialog|ConfirmDeleteTemplateDialog|ConfirmRestoreVersionDialog)\.vue$/

/**
 * Whether a bare import resolves to an installed package.
 *
 * @param {string} spec The import.
 * @return {boolean} Installed.
 */
function isInstalled(spec) {
	try {
		require.resolve(spec)
		return true
	} catch {
		return false
	}
}

/**
 * Replace every import of a page, except the libraries above, the confirm
 * dialogs and the app's mixins, with a stub (components) or a recording module (everything else).
 *
 * @param {string} file The page file.
 */
function mockImportsOf(file) {
	const source = fs.readFileSync(file, 'utf8')
	for (const match of source.matchAll(
		/^import\s+([\s\S]*?)\s+from\s+'([^']+)'/gm,
	)) {
		const spec = match[2]
		if (
			KEEP.has(spec)
			|| CONFIRM_DIALOGS.test(spec)
			|| spec.includes('/mixins/')
		) {
			continue
		}
		const target = spec.startsWith('.')
			? path.resolve(path.dirname(file), spec)
			: spec
		// A virtual mock of an installed package leaks into the resolver cache
		// and breaks the real mocks of later spec files in the same worker.
		const options =
			spec.startsWith('.') || isInstalled(spec) ? {} : { virtual: true }
		if (spec.endsWith('.vue')) {
			const name = path.basename(spec, '.vue')
			jest.doMock(
				target,
				() => ({
					__esModule: true,
					default: stubComponent(name, (global.mockStubs ??= [])),
				}),
				options,
			)
			continue
		}
		const values = {}
		for (const [store, state] of Object.entries(STORE_VALUES)) {
			if (!store.startsWith('use')) {
				values[store] = fakeModule(store, { ...state })
			}
		}
		const name = path.basename(spec)
		jest.doMock(target, () => fakeModule(name, values), options)
	}
}

const ROW = {
	id: 'row-1',
	uuid: 'row-1',
	name: 'Jaarverslag',
	label: 'Woordenlijst',
	entityText: 'Jan Jansen',
	primaryName: 'Jan Jansen',
	value: 'Jansen',
	fileName: 'Jaarverslag.pdf',
	isFolder: false,
	version: 3,
	timestamp: 1700000000,
}

const SITES = {
	dossierRemove: {
		page: '../views/dossier/DossierDetail.vue',
		open: { removeTarget: ROW },
	},
	dictionaryTermRemove: {
		page: '../views/customDictionary/CustomDictionaryDetail.vue',
		open: { removeTarget: ROW },
	},
	dictionaryDelete: {
		page: '../views/customDictionary/CustomDictionaryIndex.vue',
		open: { deleteTarget: ROW },
	},
	documentDelete: {
		page: '../views/myDocuments/MyDocumentsIndex.vue',
		open: { deleteTarget: ROW },
	},
	documentBulkDelete: {
		page: '../views/myDocuments/MyDocumentsIndex.vue',
		open: { bulkDeleteNames: ['a.pdf', 'b.pdf'] },
	},
	prohibitionDelete: {
		page: '../views/policy/PolicyRuleIndex.vue',
		props: { surface: 'prohibition' },
		open: { deleteTarget: ROW },
	},
	standingConsentDelete: {
		page: '../views/policy/PolicyRuleIndex.vue',
		props: { surface: 'standingConsent' },
		open: { deleteTarget: ROW },
	},
	templateDelete: {
		page: '../views/templates/TemplateIndex.vue',
		open: { deleteTarget: ROW },
	},
	templateRestore: {
		page: '../views/templates/TemplateDetail.vue',
		open: { restoreTarget: ROW },
	},
	versionRestore: {
		page: '../views/versions/VersionsView.vue',
		open: { restoreTarget: ROW },
	},
}

const GOLDEN = path.join(__dirname, '../../tests/jest/golden/confirm-dialogs.json')
const RECORD = process.env.RECORD_GOLDEN === '1'
const golden = RECORD ? {} : JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))

/**
 * Collapse the whitespace of a text.
 *
 * @param {string} text The text.
 * @return {string} The text on one line.
 */
function squash(text) {
	return text.replace(/\s+/g, ' ').trim()
}

/**
 * What the user sees of the open confirm dialog, or null when none is open.
 *
 * @param {Element} root The page's element.
 * @return {object|null} Title, text and buttons.
 */
function dialogView(root) {
	const frame = root.querySelector('x-ncdialog, x-cndialog')
	if (!frame) {
		return null
	}
	const actions = frame.querySelector('x-slot[name="actions"]')
	const body = [...frame.children].filter((el) => el !== actions)
	const button = (el) => {
		const disabled = el.getAttribute('disabled')
		const icon = el.querySelector('x-slot[name="icon"]')
		return {
			label: squash(
				[...el.childNodes]
					.filter(
						(n) =>
							n !== icon
							&& !(
								n.getAttribute && n.getAttribute('name') === 'icon'
							),
					)
					.map((n) => n.textContent)
					.join(' '),
			),
			variant: el.getAttribute('variant'),
			disabled: disabled !== null && disabled !== 'false',
			busyIcon: Boolean(el.querySelector('x-ncloadingicon')),
		}
	}
	const noClose = frame.getAttribute('noclose')
	return {
		title: frame.getAttribute('name'),
		text: squash(body.map((el) => el.textContent).join(' ')),
		noClose: noClose !== null && noClose !== 'false',
		buttons: [...(actions ? actions.querySelectorAll('x-ncbutton') : [])].map(
			button,
		),
	}
}

/**
 * Settle every pending call, then let the page re-render.
 *
 * @param {string} how `resolve` or `reject`.
 */
async function settle(how) {
	for (const call of mockPending.splice(0)) {
		if (how === 'resolve') {
			call.resolve({})
		} else {
			call.reject(new Error('refused'))
		}
	}
	for (let i = 0; i < 5; i++) {
		await nextTick()
	}
}

/**
 * Click the dialog's confirming button: the last button of its actions.
 *
 * @param {Element} root The page's element.
 */
async function clickConfirm(root) {
	const buttons = root.querySelectorAll(
		'x-ncdialog x-slot[name="actions"] x-ncbutton, x-cndialog x-slot[name="actions"] x-ncbutton',
	)
	buttons[buttons.length - 1].click()
	for (let i = 0; i < 3; i++) {
		await nextTick()
	}
}

/**
 * Mount a page with its dialog open.
 *
 * @param {object} site The site.
 * @return {Promise<object>} The mounted page.
 */
async function openSite(site) {
	const component = require(site.page).default
	const page = await render(component, {
		props: site.props,
		globals: {
			$route: { params: { id: 'row-1' }, query: {}, name: 'test' },
			$router: { push: (to) => mockCalls.push(['router', 'push', to]) },
		},
		errorHandler: (err) => unhandled.push(String(err && err.message)),
		keep: [
			'ConfirmActionDialog',
			'ConfirmDeleteTemplateDialog',
			'ConfirmRestoreVersionDialog',
			'CnConfirmDialog',
		],
	})
	await settle('resolve')
	mockCalls.length = 0
	Object.assign(page.vm.$data, site.open)
	for (let i = 0; i < 3; i++) {
		await nextTick()
	}
	return page
}

afterAll(() => {
	if (RECORD) {
		fs.writeFileSync(GOLDEN, JSON.stringify(golden, null, '\t') + '\n')
	}
})

for (const site of Object.values(SITES)) {
	mockImportsOf(path.resolve(__dirname, site.page))
}

describe('confirm dialogs golden', () => {
	for (const [name, site] of Object.entries(SITES)) {
		it(name, async () => {
			const actual = {}
			const root = () => document.body

			let page = await openSite(site)
			actual.confirm = dialogView(root())
			await clickConfirm(root())
			actual.loading = {
				view: dialogView(root()),
				calls: JSON.parse(JSON.stringify(mockCalls)),
			}
			await settle('resolve')
			actual.success = dialogView(root())
			page.unmount()

			page = await openSite(site)
			await clickConfirm(root())
			unhandled.length = 0
			await settle('reject')
			actual.error = { view: dialogView(root()), unhandled: [...unhandled] }
			page.unmount()

			if (RECORD) {
				golden[name] = actual
			} else {
				for (const state of ['confirm', 'loading', 'success', 'error']) {
					expect([state, actual[state]]).toEqual([
						state,
						golden[name][state],
					])
				}
			}
		})
	}
})

describe('a failed confirm resets only its own dialog', () => {
	const SECOND = { ...ROW, id: 'row-2', uuid: 'row-2', name: 'Begroting' }
	for (const name of [
		'dictionaryTermRemove',
		'templateDelete',
		'prohibitionDelete',
	]) {
		it(name, async () => {
			const site = SITES[name]
			const page = await openSite(site)
			await clickConfirm(document.body)
			const target = Object.keys(site.open)[0]
			page.vm.$data[target] = null
			await nextTick()
			page.vm.$data[target] = SECOND
			await nextTick()
			await clickConfirm(document.body)
			const first = mockPending.find((p) => /^delete/.test(p.call[1]))
			first.reject(new Error('refused'))
			for (let i = 0; i < 5; i++) {
				await nextTick()
			}
			const view = dialogView(document.body)
			page.unmount()
			expect(view.buttons[1].disabled).toBe(true)
			expect(view.buttons[1].busyIcon).toBe(true)
		})
	}
})

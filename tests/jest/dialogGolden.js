/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden harness for dialogs that ask for input before they act. A spec
 * mocks its libraries with the factories below, mounts each dialog with
 * renderGolden's stubs, and records what the user sees in every phase:
 * empty, filled in, busy after the confirming click, done, and refused.
 * Service calls stay pending until the harness settles them, so the busy
 * phase is the real one the click produced.
 */

const fs = require('fs')
const path = require('path')
const { nextTick } = require('vue')
const { renderStubbed, stubComponent } = require('./renderGolden.js')

const NC_NAMES = [
	'NcButton',
	'NcCheckboxRadioSwitch',
	'NcDialog',
	'NcLoadingIcon',
	'NcNoteCard',
	'NcSelect',
	'NcTextArea',
	'NcTextField',
]

const calls = []
const pending = []

/**
 * The stub registry shared by every stub the specs create.
 *
 * @return {Array<object>} The registry.
 */
function registry() {
	return (global.mockStubs ??= [])
}

/**
 * A printing stub whose element runs the component's click listener.
 *
 * @param {string} name The component name.
 * @return {object} The stub.
 */
function clickableStub(name) {
	const { defineComponent, h } = require('vue')
	const stub = stubComponent(name, registry())
	return defineComponent({
		name,
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

/**
 * The `@nextcloud/vue` module as printing stubs.
 *
 * @return {object} The module.
 */
function ncVueModule() {
	const module = { __esModule: true }
	for (const name of NC_NAMES) {
		module[name] =
			name === 'NcButton'
				? clickableStub(name)
				: stubComponent(name, registry())
	}
	return module
}

/**
 * The `@conduction/nextcloud-vue` module: CnDialogActions from its source,
 * with its icon stubbed, and every other component as a printing stub.
 *
 * @return {object} The module.
 */
function cnVueModule() {
	const { loadSource } = require('./loadSfc.js')
	const src = path.join(
		__dirname,
		'../../node_modules/@conduction/nextcloud-vue/src',
	)
	const CnDialogActions = loadSource(
		path.join(src, 'components/CnDialogActions/CnDialogActions.vue'),
		require,
		{
			[path.join(src, 'components/CnIcon/index.js')]: {
				__esModule: true,
				CnIcon: stubComponent('CnIcon', registry()),
			},
		},
	).default
	return new Proxy(
		{ __esModule: true, CnDialogActions },
		{
			get(target, key) {
				if (key in target || typeof key === 'symbol' || key === 'then') {
					return target[key]
				}
				return stubComponent(String(key), registry())
			},
		},
	)
}

/**
 * A service module whose every function records its call and stays pending.
 *
 * @param {string} name The module name, for the record.
 * @return {object} The module.
 */
function recordingModule(name) {
	return new Proxy(
		{},
		{
			get(target, key) {
				if (key === '__esModule') {
					return true
				}
				if (typeof key === 'symbol' || key === 'then') {
					return undefined
				}
				return (...args) => {
					calls.push([name, key, ...args])
					return new Promise((resolve) => pending.push({ key, resolve }))
				}
			},
		},
	)
}

/**
 * Replace a dialog's relative imports: components by stubs, everything else
 * by a recording module.
 *
 * @param {string} file The dialog file.
 */
function mockRelativeImports(file) {
	const source = fs.readFileSync(file, 'utf8')
	for (const match of source.matchAll(
		/^import\s+[\s\S]*?\s+from\s+'(\.[^']+)'/gm,
	)) {
		const target = path.resolve(path.dirname(file), match[1])
		const name = path.basename(match[1]).replace(/\.(vue|js)$/, '')
		jest.doMock(target, () =>
			match[1].endsWith('.vue')
				? { __esModule: true, default: stubComponent(name, registry()) }
				: recordingModule(name),
		)
	}
}

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
 * Whether a stub attribute says true.
 *
 * @param {Element} el The element.
 * @param {string} name The attribute.
 * @return {boolean} The flag.
 */
function flag(el, name) {
	const value = el.getAttribute(name)
	return value !== null && value !== 'false'
}

/**
 * One button as the user sees it.
 *
 * @param {Element} el The button stub.
 * @return {object} Label, variant, disabled and whether it spins.
 */
function buttonView(el) {
	const label = [...el.childNodes]
		.filter((n) => !(n.getAttribute && n.getAttribute('name') === 'icon'))
		.map((n) => n.textContent)
		.join(' ')
	return {
		label: squash(label),
		variant: el.getAttribute('variant'),
		disabled: flag(el, 'disabled'),
		busyIcon: Boolean(el.querySelector('x-ncloadingicon')),
	}
}

/**
 * What the user sees of the open dialog.
 *
 * @param {Element} root The mount point's parent.
 * @return {object|null} Title, body text, fields and buttons.
 */
function dialogView(root) {
	const frame = root.querySelector('x-ncdialog')
	if (!frame) {
		return null
	}
	const actions = frame.querySelector('x-slot[name="actions"]')
	const body = [...frame.children].filter((el) => el !== actions)
	const fields = [
		...frame.querySelectorAll(
			'x-nctextarea, x-nctextfield, x-ncselect, x-nccheckboxradioswitch',
		),
	].map((el) => ({
		type: el.tagName.toLowerCase(),
		label: el.getAttribute('inputlabel') || el.getAttribute('label'),
		disabled: flag(el, 'disabled'),
	}))
	return {
		title: frame.getAttribute('name'),
		noClose: flag(frame, 'noclose'),
		text: squash(body.map((el) => el.textContent).join(' ')),
		fields,
		buttons: [...(actions ? actions.querySelectorAll('x-ncbutton') : [])].map(
			buttonView,
		),
	}
}

/**
 * Let the dialog re-render.
 */
async function flush() {
	for (let i = 0; i < 5; i++) {
		await nextTick()
	}
}

/**
 * Answer every pending call, by function name, then re-render.
 *
 * @param {object} answers Function name to answer.
 */
async function settle(answers) {
	for (const call of pending.splice(0)) {
		call.resolve(answers[call.key])
	}
	await flush()
}

/**
 * Click the dialog's last action button (the confirming one) or its first (cancel).
 *
 * @param {Element} root The mount point's parent.
 * @param {string} which `confirm` or `cancel`.
 */
async function click(root, which) {
	const buttons = root.querySelectorAll(
		'x-ncdialog x-slot[name="actions"] x-ncbutton',
	)
	buttons[which === 'cancel' ? 0 : buttons.length - 1].click()
	await flush()
}

/**
 * Record one dialog through its phases.
 *
 * @param {object} component The dialog.
 * @param {object} site `{ props, fill, ok, refused, busyProps }`: the props, the data that makes it submittable, the answers for success and for a refusal, and props the parent sets while it waits.
 * @return {Promise<object>} The phases.
 */
async function recordPhases(component, site) {
	const root = document.body
	const emitted = []
	const listen = {}
	for (const event of site.events || []) {
		const prop = 'on' + event[0].toUpperCase() + event.slice(1)
		listen[prop] = (...args) =>
			emitted.push([event, ...JSON.parse(JSON.stringify(args))])
	}
	const mount = async (extra = {}) => {
		calls.length = 0
		pending.length = 0
		const page = await renderStubbed(component, {
			props: { ...site.props, ...extra, ...listen },
			keep: ['NcButton', 'CnDialogActions'],
		})
		await settle(site.ok)
		return page
	}
	const out = {}
	let page = await mount()
	out.empty = dialogView(root)
	Object.assign(page.vm.$data, site.fill)
	await flush()
	out.filled = dialogView(root)
	out.callsBeforeClick = JSON.parse(JSON.stringify(calls))
	calls.length = 0
	emitted.length = 0
	await click(root, 'confirm')
	out.busy = {
		view: dialogView(root),
		calls: JSON.parse(JSON.stringify(calls)),
		emitted: [...emitted],
	}
	emitted.length = 0
	await settle(site.ok)
	out.done = { view: dialogView(root), emitted: [...emitted] }
	page.unmount()

	page = await mount()
	Object.assign(page.vm.$data, site.fill)
	await flush()
	await click(root, 'confirm')
	emitted.length = 0
	await settle(site.refused)
	out.refused = { view: dialogView(root), emitted: [...emitted] }
	emitted.length = 0
	await click(root, 'cancel')
	out.cancel = { emitted: [...emitted] }
	page.unmount()

	if (site.busyProps) {
		page = await mount(site.busyProps)
		Object.assign(page.vm.$data, site.fill)
		await flush()
		out.parentBusy = dialogView(root)
		page.unmount()
	}
	return out
}

module.exports = {
	cnVueModule,
	mockRelativeImports,
	ncVueModule,
	recordPhases,
}

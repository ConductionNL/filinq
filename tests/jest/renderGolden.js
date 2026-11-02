/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Render harness for golden tests of Vue components: child components are
 * replaced by stubs that print their attributes and slots, so the recorded
 * HTML pins what a template hands each child. Stubs keep their listeners in
 * `listenersOf`, so a test can fire them and read the effect.
 */

const { createApp, defineComponent, h, nextTick } = require('vue')

/**
 * A stub that renders as <x-name> with its plain attributes and its slots.
 *
 * @param {string} name The component name.
 * @param {Array<object>} registry Collects every rendered stub instance.
 * @return {object} A component.
 */
function stubComponent(name, registry) {
	return defineComponent({
		name,
		inheritAttrs: false,
		setup(props, { attrs, slots }) {
			const entry = { name, attrs }
			registry.push(entry)
			return () => {
				const plain = {}
				for (const [key, value] of Object.entries(attrs)) {
					if (typeof value === 'function') {
						plain['data-on-' + key.toLowerCase()] = ''
					} else if (value !== null && typeof value === 'object') {
						plain['data-' + key.toLowerCase()] = JSON.stringify(value)
					} else {
						plain[key] = value
					}
				}
				const children = Object.keys(slots).map((slot) =>
					h('x-slot', { name: slot }, slots[slot]({})),
				)
				return h('x-' + name.toLowerCase(), plain, children)
			}
		},
	})
}

/**
 * A copy of the component whose child components are stubs, except the
 * names in `real`, which render for real with their own children stubbed.
 *
 * @param {object} component The component.
 * @param {object} context `{ stubs, real }`: the stub registry and the names to keep.
 * @return {object} The component with stubbed children.
 */
function stubTree(component, context) {
	const stubbed = { ...component, components: {} }
	for (const [name, child] of Object.entries(component.components || {})) {
		stubbed.components[name] = context.real.includes(name)
			? stubTree(child, context)
			: stubComponent(name, context.stubs)
	}
	return stubbed
}

/**
 * Mount a component with every child component stubbed.
 *
 * @param {object} component The component under test.
 * @param {object} options `{ props, data, real }`: props to pass, data to merge after creation, and child component names to render for real.
 * @return {Promise<object>} `{ vm, html, stubs, unmount }`.
 */
async function renderStubbed(component, options = {}) {
	const stubs = []
	const stubbed = stubTree(component, { stubs, real: options.real || [] })
	const el = document.createElement('div')
	document.body.appendChild(el)
	const app = createApp(stubbed, options.props || {})
	app.config.globalProperties.t = (appId, text, vars) =>
		vars ? text + ' ' + JSON.stringify(vars) : text
	app.config.globalProperties.n = (appId, one, many, count) =>
		(count === 1 ? one : many) + ' #' + count
	const vm = app.mount(el)
	if (options.data) {
		Object.assign(vm.$data, options.data)
	}
	await nextTick()
	await nextTick()
	return {
		vm,
		html: () => el.innerHTML,
		stubs,
		unmount: () => {
			app.unmount()
			el.remove()
		},
	}
}

module.exports = { renderStubbed, stubComponent }

/**
 * Render a Vue single-file component's template to HTML in node, with every
 * imported UI component replaced by a stub element.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * `renderSfc(file, setup)` loads the component with `loadSfcOptions`,
 * compiles its `<template>` at runtime and renders it with the server
 * renderer. A stubbed component renders as `<stub-name>` carrying its props
 * as attributes (objects as JSON) and each slot it receives inside a
 * `<template-slot name="...">` wrapper, so a golden of the HTML pins both the
 * markup and what is handed to child components. Scoped slots are called
 * with `setup.slotProps[slotName]`, when given.
 *
 * @spec exclude test harness for characterization tests of component templates
 */

import { compile, createSSRApp, defineComponent, h } from 'vue'
import { renderToString } from 'vue/server-renderer'
import { loadSfc } from './sfcOptions.js'

/**
 * An attribute value as text: strings as they are, everything else as JSON.
 *
 * @param {*} value Attribute value.
 * @return {string} The text.
 */
function attrText(value) {
	if (typeof value === 'function') {
		return 'fn'
	}
	return typeof value === 'string' ? value : (JSON.stringify(value) ?? 'undefined')
}

/**
 * A component that renders its name, attributes and slots.
 *
 * @param {string} name Stub element name.
 * @param {object} slotProps Scoped slot arguments per slot name.
 * @return {object} The stub component.
 */
function stubComponent(name, slotProps) {
	return defineComponent({
		name,
		inheritAttrs: false,
		setup(_, { attrs, slots }) {
			return () => {
				const shown = {}
				for (const [key, value] of Object.entries(attrs)) {
					shown['data-' + key.replace(/[^\w-]/g, '_')] = attrText(value)
				}
				const children = Object.keys(slots).map((slot) =>
					h(
						'template-slot',
						{ name: slot },
						slots[slot](slotProps[slot] || {}),
					),
				)
				return h('stub-' + name, shown, children)
			}
		},
	})
}

const BUILTIN_DIRECTIVES = new Set([
	'if',
	'else',
	'else-if',
	'for',
	'show',
	'model',
	'bind',
	'on',
	'slot',
	'html',
	'text',
	'once',
	'memo',
	'cloak',
	'pre',
])

/**
 * Whether a binding is a stub made by the loader for a UI import.
 *
 * @param {*} value Binding.
 * @return {boolean} True for a stub.
 */
function isStub(value) {
	return Boolean(value && typeof value === 'object' && 'stub' in value)
}

/**
 * A renderable component built from a `.vue` file, with its UI components
 * stubbed except those named in `setup.expand`, which are built the same way.
 *
 * @param {string} file Absolute path of the `.vue` file.
 * @param {object} setup Render setup, see renderSfc.
 * @return {Promise<object>} The component definition.
 */
async function buildComponent(file, setup) {
	const loaded = await loadSfc(file)
	const expand = setup.expand || {}
	const components = {}
	for (const [name, value] of Object.entries({
		...(loaded.options.components || {}),
		...loaded.setup,
	})) {
		if (expand[name]) {
			components[name] = await buildComponent(expand[name], setup)
		} else if (isStub(value) || loaded.options.components?.[name]) {
			components[name] = stubComponent(name, setup.slotProps || {})
		}
	}
	const directives = {}
	for (const [, name] of loaded.template.matchAll(/\sv-([a-z][\w-]*)/g)) {
		if (!BUILTIN_DIRECTIVES.has(name)) {
			directives[name] = { getSSRProps: () => ({}) }
		}
	}
	const bindings = Object.fromEntries(
		Object.entries(loaded.setup).filter(([name]) => !components[name]),
	)
	return {
		...loaded.options,
		components,
		directives,
		setup: () => bindings,
		render: compile(loaded.template),
	}
}

/**
 * Render a component file to HTML.
 *
 * Every component the file registers, or imports in `<script setup>` from a
 * UI source, renders as a stub, unless `setup.expand` maps its name to the
 * `.vue` file to render for real (so a golden recorded before a template
 * moved into a child component still compares). Custom directives do
 * nothing. Lifecycle hooks other than `beforeCreate` and `created` do not run
 * during server rendering.
 *
 * @param {string} file Absolute path of the `.vue` file.
 * @param {object} setup `props`, `data` (merged over the root's data),
 *   `slotProps` (scoped slot arguments per slot name) and `expand`.
 * @return {Promise<string>} The rendered HTML, one tag per line.
 */
export async function renderSfc(file, setup = {}) {
	const root = await buildComponent(file, setup)
	const ownData = root.data
	const component = {
		...root,
		data() {
			return {
				...(ownData ? ownData.call(this) : {}),
				...(setup.data || {}),
			}
		},
	}
	const app = createSSRApp(component, setup.props || {})
	app.config.warnHandler = () => {}
	const html = await renderToString(app)
	return html.replace(/<!--[\s\S]*?-->/g, '').replace(/></g, '>\n<')
}

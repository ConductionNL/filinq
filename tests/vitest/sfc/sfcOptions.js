/**
 * Load the options object of a Vue single-file component in node, without a
 * Vue plugin, and run it against a small fake instance.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * `loadSfcOptions(file)` reads the `<script>` and `<script setup>` blocks of a
 * `.vue` file, imports every non-UI dependency for real (so `vi.mock` in the
 * calling test applies), replaces `.vue` files and UI libraries with named
 * stubs, and evaluates the blocks as one scope, the way the Vue compiler
 * merges them. `createInstance(options, setup)` then builds a plain object
 * with the merged mixins, data, props, computed getters and bound methods,
 * so characterization tests can drive the component's logic.
 *
 * @spec exclude test harness for characterization tests of component logic
 */

import { readFileSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { pathToFileURL } from 'node:url'
import { parse } from 'vue/compiler-sfc'

const IMPORT = /^import\s+([\s\S]*?)\s+from\s+['"]([^'"]+)['"];?[ \t]*$/gm
const UI_SOURCES = [
	/\.vue$/,
	/^@nextcloud\/vue(\/|$)/,
	/^@conduction\/nextcloud-vue(\/|$)/,
	/^vue-material-design-icons\//,
]

/**
 * The local names an import clause binds, mapped to the export they read.
 *
 * @param {string} clause The text between `import` and `from`.
 * @return {Array<[string, string]>} Pairs of local name and export name.
 */
function bindings(clause) {
	const out = []
	const named = clause.match(/\{([\s\S]*)\}/)
	if (named) {
		for (const part of named[1].split(',')) {
			const [exported, local] = part.trim().split(/\s+as\s+/)
			if (exported) {
				out.push([local || exported, exported])
			}
		}
	}
	const rest = clause.replace(/\{[\s\S]*\}/, '').split(',')
	for (const part of rest.map((p) => p.trim()).filter(Boolean)) {
		const ns = part.match(/^\*\s+as\s+(\w+)$/)
		out.push(ns ? [ns[1], '*'] : [part, 'default'])
	}
	return out
}

/**
 * Resolve the value of one import binding.
 *
 * @param {string} source The import source as written in the component.
 * @param {string} dir The component's directory.
 * @return {Promise<object>} The module namespace, or a stub namespace.
 */
async function load(source, dir) {
	if (UI_SOURCES.some((re) => re.test(source))) {
		return new Proxy(
			{},
			{ get: (_, key) => ({ stub: source + '#' + String(key) }) },
		)
	}
	const target = source.startsWith('.')
		? pathToFileURL(resolve(dir, source)).href
		: source
	return import(/* @vite-ignore */ target)
}

/**
 * Evaluate the script blocks of a `.vue` file.
 *
 * @param {string} file Absolute path of the `.vue` file.
 * @return {Promise<object>} `options` (the `export default`), `setup` (the
 *   bindings `<script setup>` imports, which its template can use) and
 *   `template` (the template source).
 */
export async function loadSfc(file) {
	const { descriptor } = parse(readFileSync(file, 'utf8'), { filename: file })
	const code = [descriptor.scriptSetup, descriptor.script]
		.filter(Boolean)
		.map((block) => block.content)
		.join('\n')
	const setupCode = descriptor.scriptSetup?.content || ''
	const names = []
	const values = []
	const setup = {}
	for (const [, clause, source] of code.matchAll(IMPORT)) {
		const mod = await load(source, dirname(file))
		const fromSetup = setupCode.includes(`from '${source}'`)
		for (const [local, exported] of bindings(clause)) {
			const value = exported === '*' ? mod : mod[exported]
			names.push(local)
			values.push(value)
			if (fromSetup) {
				setup[local] = value
			}
		}
	}
	const body = code.replace(IMPORT, '').replace(/^export default\s/m, 'return ')
	return {
		options: new Function(...names, body)(...values),
		setup,
		template: descriptor.template?.content || '',
	}
}

/**
 * Evaluate the script blocks of a `.vue` file and return its options object.
 *
 * @param {string} file Absolute path of the `.vue` file.
 * @return {Promise<object>} The component options (`export default`).
 */
export async function loadSfcOptions(file) {
	return (await loadSfc(file)).options
}

/**
 * The options objects in the order Vue applies them: mixins first, depth first.
 *
 * @param {object} options Component options.
 * @return {object[]} Flattened option objects.
 */
function chain(options) {
	return [...(options.mixins || []).flatMap(chain), options]
}

/**
 * Build a fake component instance from options.
 *
 * Props take their declared defaults unless `setup.props` gives a value.
 * `$options` is the component's own options object. `$emit` calls are
 * recorded in `$emitted`, router pushes in `$pushed`, and
 * watchers can be fired with `$fireWatch(name, now, before)`.
 *
 * @param {object} options Component options, as returned by loadSfcOptions.
 * @param {object} setup Extra state: `props`, `refs` and other `$` members.
 * @return {object} The instance.
 */
export function createInstance(options, setup = {}) {
	const parts = chain(options)
	const vm = {
		$options: options,
		$emitted: [],
		$pushed: [],
		$refs: setup.refs || {},
		$route: setup.route || { params: {}, query: {} },
	}
	vm.$emit = (...args) => vm.$emitted.push(args)
	vm.$router = { push: (to) => vm.$pushed.push(to) }
	vm.$nextTick = (fn) => Promise.resolve().then(fn && fn.bind(vm))
	const watchers = {}
	for (const part of parts) {
		for (const [name, def] of Object.entries(part.props || {})) {
			const given = setup.props && name in setup.props
			const fallback =
				typeof def?.default === 'function' && def.type !== Function
					? def.default()
					: def?.default
			vm[name] = given ? setup.props[name] : fallback
		}
		for (const [name, fn] of Object.entries(part.methods || {})) {
			vm[name] = fn.bind(vm)
		}
		for (const [name, def] of Object.entries(part.computed || {})) {
			const get = typeof def === 'function' ? def : def.get
			Object.defineProperty(vm, name, {
				get: () => get.call(vm),
				configurable: true,
				enumerable: false,
			})
		}
		for (const [name, def] of Object.entries(part.watch || {})) {
			watchers[name] = [...(watchers[name] || []), def.handler || def]
		}
	}
	for (const part of parts) {
		Object.assign(vm, part.data ? part.data.call(vm) : {})
	}
	vm.$fireWatch = async (name, now, before) => {
		for (const handler of watchers[name] || []) {
			const fn = typeof handler === 'string' ? vm[handler] : handler
			await fn.call(vm, now, before)
		}
	}
	vm.$hook = async (hook) => {
		for (const part of parts) {
			if (part[hook]) {
				await part[hook].call(vm)
			}
		}
	}
	return vm
}

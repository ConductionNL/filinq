/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Load a library Vue SFC (or ES module) from its source in node_modules,
 * which Jest does not transform. Relative imports load the same way, unless
 * `replace` names a module for that path; bare imports go through Jest's
 * require, so the spec's mocks apply.
 */

const babel = require('@babel/core')
const sfc = require('@vue/compiler-sfc')
const fs = require('fs')
const path = require('path')

/**
 * The CommonJS code of a source file.
 *
 * @param {string} file The absolute path.
 * @return {string} The code.
 */
function compile(file) {
	const source = fs.readFileSync(file, 'utf8')
	let code = source
	if (file.endsWith('.vue')) {
		const { descriptor } = sfc.parse(source, { filename: file })
		const script = sfc.compileScript(descriptor, {
			id: file,
			inlineTemplate: false,
		})
		const template = sfc.compileTemplate({
			source: descriptor.template.content,
			filename: file,
			id: file,
			compilerOptions: { bindingMetadata: script.bindings },
		})
		code =
			sfc.rewriteDefault(script.content, '__sfc__')
			+ '\n'
			+ template.code.replace('export function render', 'function render')
			+ '\n__sfc__.render = render\nexport default __sfc__\n'
	}
	return babel.transformSync(code, {
		filename: file + '.js',
		babelrc: false,
		configFile: false,
		presets: [['@babel/preset-env', { targets: { node: 'current' } }]],
	}).code
}

/**
 * Load a source file and its relative imports.
 *
 * @param {string} file The absolute path.
 * @param {Function} jestRequire The spec's require, for bare imports.
 * @param {object} replace Absolute path to module, for the imports to replace.
 * @param {object} cache Loaded modules by path.
 * @return {object} The module's exports.
 */
function loadSource(file, jestRequire, replace = {}, cache = {}) {
	if (cache[file]) {
		return cache[file].exports
	}
	const module = { exports: {} }
	cache[file] = module
	const localRequire = (spec) => {
		if (!spec.startsWith('.')) {
			return jestRequire(spec)
		}
		const target = path.resolve(path.dirname(file), spec)
		if (target in replace) {
			return replace[target]
		}
		return loadSource(target, jestRequire, replace, cache)
	}
	new Function('require', 'module', 'exports', compile(file))(
		localRequire,
		module,
		module.exports,
	)
	return module.exports
}

module.exports = { loadSource }

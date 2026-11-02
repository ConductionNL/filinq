/**
 * Golden: the styles each document viewer gives the elements of its template.
 * Every style block of the viewer (inline or `src`) is compiled as Vue
 * compiles it, and each element with a static class gets the declarations of
 * the plain class rules that match it, in cascade order. Rules with other
 * selectors (descendants, :deep) are listed as written.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing document viewer styling
 */

import { compileStyle, parse } from '@vue/compiler-sfc'
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { describe, it } from 'vitest'
import { golden } from './golden/golden.js'

const VIEWERS = join(
	dirname(fileURLToPath(import.meta.url)),
	'../../src/components/viewers',
)
const SCOPE = 'data-v-golden'
const PLAIN_CLASSES = /^(\.[\w-]+)+$/

/**
 * Every style block of one SFC, compiled and parsed, in source order.
 *
 * @param {object} descriptor The parsed SFC.
 * @return {Array<object>} One parsed stylesheet per block.
 */
function compiledSheets(descriptor) {
	return descriptor.styles.map(
		(block) =>
			compileStyle({
				source: block.src
					? readFileSync(join(VIEWERS, block.src), 'utf8')
					: block.content,
				filename: 'viewer.css',
				id: SCOPE,
				scoped: block.scoped,
			}).rawResult.root,
	)
}

/**
 * The static class lists of the template's elements.
 *
 * @param {object} node A template AST node.
 * @param {Array<Array<string>>} out Collects one class list per element.
 * @return {Array<Array<string>>} The class lists.
 */
function classLists(node, out = []) {
	const attr = (node.props || []).find(
		(prop) => prop.type === 6 && prop.name === 'class',
	)
	if (attr && attr.value) {
		out.push(attr.value.content.split(/\s+/).filter(Boolean))
	}
	;(node.children || []).forEach((child) => classLists(child, out))
	return out
}

/**
 * What one viewer's styles give its elements.
 *
 * @param {string} file The viewer's file name.
 * @return {object} Declarations per element (keyed by its first class) and
 *  the rules with other selectors.
 */
function viewerStyles(file) {
	const { descriptor } = parse(readFileSync(join(VIEWERS, file), 'utf8'))
	const elements = {}
	const other = []
	const lists = classLists(descriptor.template.ast)
	lists.forEach((list) => {
		elements[list[0]] = {}
	})
	const visit = (rule) => {
		const decls = {}
		rule.walkDecls((decl) => {
			decls[decl.prop] = decl.value
		})
		rule.selectors.forEach((raw) => {
			const selector = raw.split('[' + SCOPE + ']').join('')
			if (!PLAIN_CLASSES.test(selector)) {
				other.push({ selector, decls })
				return
			}
			const wanted = selector.slice(1).split('.')
			lists
				.filter((list) => wanted.every((cls) => list.includes(cls)))
				.forEach((list) => Object.assign(elements[list[0]], decls))
		})
	}
	compiledSheets(descriptor).forEach((sheet) => sheet.walkRules(visit))
	return { elements, other }
}

describe('document viewer styles', () => {
	for (const file of [
		'OdtViewer.vue',
		'WordViewer.vue',
		'TextViewer.vue',
		'PdfViewer.vue',
	]) {
		it(file, () => {
			golden('document-viewer-styles-' + file, viewerStyles(file))
		})
	}
})

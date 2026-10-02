/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The step-up dialog showed its title twice: NcModal's `name` prints it in the
 * modal header above the box, and the dialog's own h2 printed it again inside.
 * The dialog keeps one visible title, the h2, and names itself after it
 * through NcModal's `labelId`, so screen readers still hear the name.
 */

import { parse } from '@vue/compiler-sfc'
import { readFileSync } from 'fs'
import * as path from 'path'
import { describe, expect, it } from 'vitest'

const FILE = path.resolve(__dirname, '../../src/modals/SignerStepUpModal.vue')

/**
 * Find the first element with a tag in a compiled template tree.
 *
 * @param {object} node A template AST node.
 * @param {string} tag The tag.
 * @return {object|null} The element.
 */
function find(node, tag) {
	if (node.tag === tag) {
		return node
	}
	for (const child of node.children || []) {
		const hit = find(child, tag)
		if (hit) {
			return hit
		}
	}
	return null
}

/**
 * A static or bound attribute of an element.
 *
 * @param {object} element The element.
 * @param {string} name The attribute.
 * @return {string|undefined} Its raw value.
 */
function attr(element, name) {
	for (const prop of element.props) {
		if (prop.type === 6 && prop.name === name) {
			return prop.value?.content
		}
		if (prop.type === 7 && prop.name === 'bind' && prop.arg?.content === name) {
			return prop.exp?.content
		}
	}
	return undefined
}

describe('SignerStepUpModal title', () => {
	const { descriptor } = parse(readFileSync(FILE, 'utf8'))
	const root = descriptor.template.ast
	const modal = find(root, 'NcModal')
	const heading = find(root, 'h2')

	it('prints the title once, as the dialog heading', () => {
		expect(
			attr(modal, 'name'),
			'no second title in the modal header',
		).toBeUndefined()
		expect(heading).not.toBeNull()
	})

	it('is named after that heading', () => {
		expect(attr(heading, 'id')).toBeTruthy()
		expect(attr(modal, 'labelId')).toBe(attr(heading, 'id'))
	})
})

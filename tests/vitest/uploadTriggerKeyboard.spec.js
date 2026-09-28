/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Every file input in src/ can be reached from the keyboard
 * (openspec/specs/upload-dropzone-keyboard-access, WCAG 2.1.1).
 *
 * A file input that its component hides with `display: none` is not
 * focusable, so it needs a real button that calls its `click()`. A visible
 * input is focusable on its own. A drop zone keeps its drag handlers but
 * carries no click handler of its own, so the picker never opens twice.
 *
 * The audit walks the compiled template of every single-file component, so a
 * new upload component is checked the day it is added.
 */

import * as fs from 'fs'
import * as path from 'path'
import { parse } from '@vue/compiler-sfc'
import { describe, expect, it } from 'vitest'

const SRC = path.resolve(__dirname, '../../src')
const ELEMENT = 1

/**
 * Every .vue file under a folder.
 *
 * @param {string} dir The folder to walk.
 * @return {string[]} Paths of single-file components.
 */
function vueFiles(dir = SRC) {
	const out = []
	for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
		const full = path.join(dir, entry.name)
		if (entry.isDirectory()) {
			out.push(...vueFiles(full))
		} else if (entry.name.endsWith('.vue')) {
			out.push(full)
		}
	}
	return out
}

/**
 * Every element node in a template AST.
 *
 * @param {object} node The root node.
 * @return {object[]} Element nodes, depth first.
 */
function elements(node) {
	const out = []
	for (const child of node.children || []) {
		if (child.type === ELEMENT) {
			out.push(child, ...elements(child))
		} else if (child.children) {
			out.push(...elements(child))
		}
	}
	return out
}

/**
 * A static attribute value, or null.
 *
 * @param {object} el The element.
 * @param {string} name The attribute.
 * @return {string|null} The value.
 */
function attr(el, name) {
	const found = el.props.find((p) => p.type === 6 && p.name === name)
	return found ? (found.value?.content ?? '') : null
}

/**
 * The expression of an event binding such as @click, or null.
 *
 * @param {object} el The element.
 * @param {string} event The event name.
 * @return {string|null} The handler expression.
 */
function on(el, event) {
	const found = el.props.find(
		(p) => p.type === 7 && p.name === 'on' && p.arg?.content === event,
	)
	return found ? (found.exp?.content ?? '') : null
}

/**
 * Whether the component's own styles hide an element with one of these classes.
 *
 * @param {string[]} styles The component's style blocks.
 * @param {string[]} classes The element's classes.
 * @return {boolean} True when a rule for one of the classes sets display none.
 */
function hiddenByStyle(styles, classes) {
	return classes.some((cls) =>
		styles.some((css) =>
			new RegExp(`\\.${cls}\\s*\\{[^}]*display:\\s*none`, 'm').test(css),
		),
	)
}

/**
 * The visible text of an element.
 *
 * @param {object} el The element.
 * @return {string} Its text, interpolations included as their source.
 */
function text(el) {
	return (el.children || [])
		.map((c) => (c.type === 2 ? c.content : c.type === 5 ? c.content?.content : text(c)))
		.join('')
		.trim()
}

/**
 * Audit one component.
 *
 * @param {string} source The SFC source.
 * @return {string[]} Problems found, empty when the component is fine.
 */
export function auditUploadTriggers(source) {
	const { descriptor } = parse(source)
	if (!descriptor.template) {
		return []
	}
	const styles = descriptor.styles.map((s) => s.content)
	const all = elements(descriptor.template.ast)
	const problems = []

	for (const input of all.filter((el) => el.tag === 'input' && attr(el, 'type') === 'file')) {
		const classes = (attr(input, 'class') || '').split(/\s+/).filter(Boolean)
		if (!hiddenByStyle(styles, classes)) {
			continue
		}
		const ref = attr(input, 'ref')
		const triggers = all.filter(
			(el) =>
				['button', 'NcButton'].includes(el.tag)
				&& ref !== null
				&& (on(el, 'click') || '').includes(`$refs.${ref}.click()`),
		)
		if (triggers.length === 0) {
			problems.push(`hidden file input "${ref}" has no button that opens it`)
			continue
		}
		for (const button of triggers) {
			if (attr(button, 'tabindex') === '-1') {
				problems.push(`the button opening "${ref}" is taken out of the tab order`)
			}
			if (text(button) === '' && attr(button, 'aria-label') === null) {
				problems.push(`the button opening "${ref}" has no accessible name`)
			}
		}
	}

	for (const zone of all.filter((el) => on(el, 'drop') !== null)) {
		if (on(zone, 'click') !== null) {
			problems.push('a drop zone carries its own click handler, so the picker can open twice')
		}
	}

	return problems
}

describe('upload triggers are keyboard operable', () => {
	for (const file of vueFiles()) {
		const source = fs.readFileSync(file, 'utf8')
		if (!source.includes('type="file"')) {
			continue
		}
		it(`${path.relative(SRC, file)} gives every file input a keyboard route`, () => {
			expect(auditUploadTriggers(source)).toEqual([])
		})
	}

	it('flags a hidden input whose only trigger is a span on the drop zone', () => {
		const before = `<template><div class="drop-zone" @drop.prevent="d" @click="$refs.fileInput.click()">
			<span class="fake-button">+ Select files</span>
			<input ref="fileInput" type="file" class="file-input" /></div></template>
			<style>.file-input { display: none; }</style>`

		expect(auditUploadTriggers(before)).toEqual([
			'hidden file input "fileInput" has no button that opens it',
			'a drop zone carries its own click handler, so the picker can open twice',
		])
	})

	it('flags a trigger button that is out of the tab order or has no name', () => {
		const source = `<template><div><button type="button" tabindex="-1" @click="$refs.f.click()"></button>
			<input ref="f" type="file" class="hidden-input" /></div></template>
			<style scoped>.hidden-input {\n display: none;\n}</style>`

		expect(auditUploadTriggers(source)).toEqual([
			'the button opening "f" is taken out of the tab order',
			'the button opening "f" has no accessible name',
		])
	})

	it('accepts a visible input with a label and no extra button', () => {
		const source = `<template><label for="x">CSV file</label><input id="x" type="file" /></template>`

		expect(auditUploadTriggers(source)).toEqual([])
	})
})

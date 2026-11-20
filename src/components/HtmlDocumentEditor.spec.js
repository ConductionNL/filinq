/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * HtmlDocumentEditor, the editing surface the template page and the draft
 * editor share (generate-draft-and-attended-edit D7): it mounts the HTML
 * sanitised, hands every edit back through v-model, re-mounts only for a
 * change from outside so the caret stays where the person types, takes the
 * raw-HTML box, and changes nothing when read-only.
 *
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.1
 */

import { createApp, h, nextTick, ref } from 'vue'
import HtmlDocumentEditor from './HtmlDocumentEditor.vue'

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
}))

/**
 * Mount the editor under a host that owns the v-model.
 *
 * @param {string} html The starting HTML.
 * @param {object} props Further props.
 * @param {object} slots Slots.
 * @return {{el: HTMLElement, value: object, emitted: Array<string>}}
 */
function mount(html, props = {}, slots = {}) {
	const value = ref(html)
	const emitted = []
	const el = document.createElement('div')
	document.body.appendChild(el)
	createApp({
		render() {
			return h(
				HtmlDocumentEditor,
				{
					modelValue: value.value,
					'onUpdate:modelValue': (v) => {
						emitted.push(v)
						value.value = v
					},
					ariaLabel: 'Letter text',
					...props,
				},
				slots,
			)
		},
	}).mount(el)
	return { el, value, emitted }
}

/**
 * The editing area.
 *
 * @param {HTMLElement} el The mount element.
 * @return {HTMLElement}
 */
const area = (el) => el.querySelector('.html-document-editor__content')

describe('HtmlDocumentEditor', () => {
	it('mounts the HTML without scripts or event handlers, named for screen readers', () => {
		const { el } = mount('<p onclick="steal()">Geachte heer Jansen,</p><script>steal()</script>')

		expect(area(el).innerHTML).toBe('<p>Geachte heer Jansen,</p>')
		expect(area(el).getAttribute('contenteditable')).toBe('true')
		expect(area(el).getAttribute('aria-label')).toBe('Letter text')
		expect(area(el).getAttribute('role')).toBe('textbox')
	})

	it('hands an edit back and keeps the area as typed', async () => {
		const { el, value, emitted } = mount('<p>Met vriendelijke groet</p>')
		const node = area(el)
		node.innerHTML = '<p>Met hartelijke groet</p>'
		const typed = node.firstChild
		node.dispatchEvent(new Event('input'))
		await nextTick()

		expect(emitted).toEqual(['<p>Met hartelijke groet</p>'])
		expect(value.value).toBe('<p>Met hartelijke groet</p>')
		// The same nodes: its own echo did not re-mount it, so the caret stays.
		expect(area(el)).toBe(node)
		expect(area(el).firstChild).toBe(typed)
	})

	it('re-mounts for a change from outside', async () => {
		const { el, value } = mount('<p>Versie 1</p>')
		value.value = '<p>Versie 2</p>'
		await nextTick()

		expect(area(el).innerHTML).toBe('<p>Versie 2</p>')
	})

	it('takes the raw HTML box and shows it in the area', async () => {
		const { el, emitted } = mount('<p>Oud</p>')
		el.querySelector('.html-document-editor__raw-btn').click()
		await nextTick()
		const raw = el.querySelector('.html-document-editor__raw-area')
		expect(raw.value).toBe('<p>Oud</p>')

		raw.value = '<p><strong>Nieuw</strong></p>'
		raw.dispatchEvent(new Event('input'))
		await nextTick()

		expect(emitted).toEqual(['<p><strong>Nieuw</strong></p>'])
		expect(area(el).innerHTML).toBe('<p><strong>Nieuw</strong></p>')
		el.querySelector('.html-document-editor__raw-btn').click()
		await nextTick()
		expect(el.querySelector('.html-document-editor__raw-area')).toBeNull()
	})

	it('changes nothing when read-only', async () => {
		const { el, emitted } = mount('<p>Afgerond</p>', { readonly: true })
		const node = area(el)
		expect(node.getAttribute('contenteditable')).toBe('false')
		expect([...el.querySelectorAll('.html-document-editor__toolbar-btn')].every((b) => b.disabled)).toBe(true)

		node.innerHTML = '<p>Gewijzigd</p>'
		node.dispatchEvent(new Event('input'))
		await nextTick()

		expect(emitted).toEqual([])
	})

	it('offers the host its own toolbar buttons only when given', () => {
		const plain = mount('<p>x</p>').el
		expect(plain.querySelectorAll('.html-document-editor__toolbar-sep')).toHaveLength(2)

		const withExtra = mount('<p>x</p>', {}, { toolbarExtra: () => h('button', { class: 'merge' }, '{ }') }).el
		expect(withExtra.querySelector('.merge').textContent).toBe('{ }')
		expect(withExtra.querySelectorAll('.html-document-editor__toolbar-sep')).toHaveLength(3)
	})
})

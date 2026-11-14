/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The dialog shown when the prohibition guard refuses a skip: what it says
 * about the match, and that only a releasable match offers the force action.
 */

import { createApp, nextTick } from 'vue'
import ProhibitionBlockedDialog from './ProhibitionBlockedDialog.vue'

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars = {}) =>
		text.replace(/\{(\w+)\}/g, (m, k) => (k in vars ? String(vars[k]) : m)),
}))
jest.mock('@nextcloud/vue', () => {
	const { h } = require('vue')
	return {
		NcDialog: {
			name: 'NcDialog',
			props: { open: Boolean, name: String },
			setup(props, { slots }) {
				return () => h('div', { class: 'dialog', 'data-open': String(props.open) }, [
					h('h2', props.name),
					slots.default?.(),
					h('footer', slots.actions?.()),
				])
			},
		},
		NcButton: {
			name: 'NcButton',
			inheritAttrs: false,
			props: { variant: String },
			setup(props, { slots, attrs }) {
				return () => h('button', { 'data-variant': props.variant || '', onClick: attrs.onClick }, slots.default?.())
			},
		},
	}
})

/**
 * Mount the dialog and collect what it emits.
 *
 * @param {object} block The 422 body.
 * @return {{el: HTMLElement, emitted: Array}} The element and the events.
 */
function mountDialog(block) {
	const emitted = []
	const el = document.createElement('div')
	document.body.appendChild(el)
	createApp(ProhibitionBlockedDialog, {
		open: true,
		block,
		'onUpdate:open': (value) => emitted.push(['update:open', value]),
		onForce: () => emitted.push(['force']),
	}).mount(el)
	return { el, emitted }
}

/**
 * The labels of the dialog's buttons.
 *
 * @param {HTMLElement} el The mounted element.
 * @return {Array<string>} The labels.
 */
function buttons(el) {
	return [...el.querySelectorAll('footer button')].map((b) => b.textContent.trim())
}

describe('ProhibitionBlockedDialog', () => {
	afterEach(() => {
		document.body.innerHTML = ''
	})

	it('names the entity, the rule and the confidence against the threshold', () => {
		const { el } = mountDialog({
			threshold: 0.8,
			prohibitionMatch: { entityName: 'Jan Jansen', ruleName: 'BSN', confidence: 0.934, absolute: true },
		})

		expect(el.querySelector('h2').textContent).toBe('Entity may not be skipped')
		const paragraphs = [...el.querySelectorAll('.prohibition-blocked p')].map((p) => p.textContent.trim())
		expect(paragraphs[0]).toBe('“Jan Jansen” matches the prohibition rule “BSN” and must be anonymised.')
		expect(paragraphs[1]).toBe('Detection confidence 93% (threshold 80%).')
	})

	it('refuses the skip outright for an absolute match and offers only keeping it', () => {
		const { el } = mountDialog({
			threshold: 0.8,
			prohibitionMatch: { entityName: 'X', ruleName: 'R', confidence: 0.9, absolute: true },
		})

		expect(el.querySelector('.warn-text').textContent.trim()).toBe('This match is at or above the threshold and cannot be skipped.')
		expect(buttons(el)).toEqual(['Keep anonymised'])
	})

	it('offers the force action for a releasable match and emits force then closes', async () => {
		const { el, emitted } = mountDialog({
			threshold: 0.8,
			prohibitionMatch: { entityName: 'X', ruleName: 'R', confidence: 0.5, absolute: false },
		})

		expect(el.querySelector('.warn-text')).toBeNull()
		expect(el.querySelector('.prohibition-blocked').textContent).toContain('the override is recorded in the audit trail')
		expect(buttons(el)).toEqual(['Keep anonymised', 'Skip anyway (force)'])
		expect(el.querySelectorAll('footer button')[1].dataset.variant).toBe('warning')

		el.querySelectorAll('footer button')[1].click()
		await nextTick()
		expect(emitted).toEqual([['force'], ['update:open', false]])
	})

	it('closes without forcing when the match is kept', () => {
		const { el, emitted } = mountDialog({
			threshold: 0.8,
			prohibitionMatch: { entityName: 'X', ruleName: 'R', confidence: 0.5, absolute: false },
		})

		el.querySelectorAll('footer button')[0].click()
		expect(emitted).toEqual([['update:open', false]])
	})

	it('treats a missing confidence and threshold as zero', () => {
		const { el } = mountDialog({ prohibitionMatch: { entityName: 'X', ruleName: 'R', absolute: true } })
		expect(el.querySelector('.confidence').textContent.trim()).toBe('Detection confidence 0% (threshold 0%).')
	})

	it('shows no match text and no force action without a block', () => {
		const { el } = mountDialog(null)

		expect(el.querySelector('.prohibition-blocked')).toBeNull()
		expect(buttons(el)).toEqual(['Keep anonymised'])
	})
})

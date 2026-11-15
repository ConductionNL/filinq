/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The restore-original dialog: the warning and placeholder count before the
 * restore, the busy state while it runs, the saved copy or the report of
 * names after it, and the refusal.
 */

import { createApp, nextTick } from 'vue'
import RestoreOriginalDialog from './RestoreOriginalDialog.vue'
import { restoreOriginal } from '../services/pseudonymisation.js'

jest.mock('../services/pseudonymisation.js', () => ({ restoreOriginal: jest.fn() }))
jest.mock('@nextcloud/l10n', () => {
	const fill = (text, vars = {}) => text.replace(/\{(\w+)\}/g, (m, k) => (k in vars ? String(vars[k]) : m))
	return {
		translate: (app, text, vars) => fill(text, vars),
		translatePlural: (app, one, many, count) => fill(count === 1 ? one : many).replace('%n', String(count)),
	}
})
jest.mock('@nextcloud/vue', () => {
	const { h } = require('vue')
	const box = (name, tag) => ({
		name,
		inheritAttrs: false,
		props: { type: String },
		setup(props, { slots }) {
			return () => h(tag, { class: name, 'data-type': props.type || '' }, slots.default?.())
		},
	})
	return {
		NcDialog: {
			name: 'NcDialog',
			props: { name: String, noClose: Boolean },
			setup(props, { slots }) {
				return () => h('div', { class: 'dialog', 'data-no-close': String(props.noClose) }, [
					h('h2', props.name),
					slots.default?.(),
					h('footer', slots.actions?.()),
				])
			},
		},
		NcButton: {
			name: 'NcButton',
			inheritAttrs: false,
			props: { variant: String, disabled: Boolean },
			setup(props, { slots, attrs }) {
				return () => h('button', { disabled: props.disabled, onClick: attrs.onClick }, [slots.icon?.(), slots.default?.()])
			},
		},
		NcNoteCard: box('NcNoteCard', 'div'),
		NcLoadingIcon: box('NcLoadingIcon', 'span'),
	}
})

/**
 * Mount the dialog and collect what it emits.
 *
 * @param {number} entryCount The placeholders in the key.
 * @return {{el: HTMLElement, emitted: Array}} The element and the events.
 */
function mountDialog(entryCount = 3) {
	const emitted = []
	const el = document.createElement('div')
	document.body.appendChild(el)
	createApp(RestoreOriginalDialog, {
		linkId: 'link 1',
		entryCount,
		onClose: () => emitted.push(['close']),
		onRestored: (result) => emitted.push(['restored', result]),
	}).mount(el)
	return { el, emitted }
}

/**
 * Let pending promises and the re-render settle.
 *
 * @return {Promise<void>}
 */
async function settle() {
	await Promise.resolve()
	await nextTick()
}

/**
 * The labels of the footer buttons.
 *
 * @param {HTMLElement} el The mounted element.
 * @return {Array<string>} The labels.
 */
function buttonLabels(el) {
	return [...el.querySelectorAll('footer button')].map((b) => b.textContent.trim())
}

describe('RestoreOriginalDialog', () => {
	afterEach(() => {
		document.body.innerHTML = ''
		restoreOriginal.mockReset()
	})

	it('warns about the audit trail and counts the placeholders before restoring', () => {
		const { el } = mountDialog(3)

		expect(el.querySelector('h2').textContent).toBe('Restore original')
		expect(el.querySelector('[data-type="warning"]').textContent).toContain('written to the audit trail before anything is restored')
		expect(el.querySelector('.restore-original-dialog__count').textContent.trim()).toBe('This copy has a key for 3 placeholders.')
		expect(buttonLabels(el)).toEqual(['Cancel', 'Restore and log'])
	})

	it('counts a single placeholder in the singular', () => {
		const { el } = mountDialog(1)
		expect(el.querySelector('.restore-original-dialog__count').textContent.trim()).toBe('This copy has a key for 1 placeholder.')
	})

	it('is busy while the restore runs and then names the saved copy', async () => {
		let finish
		restoreOriginal.mockReturnValue(new Promise((resolve) => {
			finish = resolve
		}))
		const { el, emitted } = mountDialog()

		el.querySelectorAll('footer button')[1].click()
		await nextTick()
		expect(restoreOriginal).toHaveBeenCalledWith('link 1')
		expect(el.querySelector('.dialog').dataset.noClose).toBe('true')
		expect([...el.querySelectorAll('footer button')].every((b) => b.disabled)).toBe(true)
		expect(el.querySelector('.NcLoadingIcon')).not.toBeNull()

		const result = { mode: 'copy', fileName: 'contract (restored).docx' }
		finish({ ok: true, result })
		await settle()

		expect(el.textContent).toContain('Saved as contract (restored).docx, next to the anonymised copy.')
		expect(el.querySelector('[data-type="warning"]')).toBeNull()
		expect(el.querySelector('.restore-original-dialog__count')).toBeNull()
		expect(buttonLabels(el)).toEqual(['Close'])
		expect(el.querySelector('.dialog').dataset.noClose).toBe('false')
		expect(emitted).toEqual([['restored', result]])
	})

	it('lists the names behind the placeholders when the format cannot be rewritten', async () => {
		const result = {
			mode: 'report',
			entries: [
				{ placeholder: '[PERSOON-1]', originalValue: 'Jan Jansen' },
				{ placeholder: '[ADRES-1]', originalValue: 'Dorpsstraat 1' },
			],
		}
		restoreOriginal.mockResolvedValue({ ok: true, result })
		const { el } = mountDialog()

		el.querySelectorAll('footer button')[1].click()
		await settle()

		expect(el.textContent).toContain('This format cannot be rewritten safely, so no file was made.')
		const rows = [...el.querySelectorAll('.restore-original-dialog__report tbody tr')]
			.map((tr) => [...tr.querySelectorAll('td')].map((td) => td.textContent))
		expect(rows).toEqual([['[PERSOON-1]', 'Jan Jansen'], ['[ADRES-1]', 'Dorpsstraat 1']])
	})

	it('shows the refusal and keeps the restore available', async () => {
		restoreOriginal.mockResolvedValue({ ok: false, error: 'You may not restore this document.' })
		const { el, emitted } = mountDialog()

		el.querySelectorAll('footer button')[1].click()
		await settle()

		expect(el.querySelector('[data-type="error"]').textContent.trim()).toBe('You may not restore this document.')
		expect(buttonLabels(el)).toEqual(['Cancel', 'Restore and log'])
		expect(el.querySelectorAll('footer button')[1].disabled).toBe(false)
		expect(emitted).toEqual([])
	})

	it('closes on Cancel', () => {
		const { el, emitted } = mountDialog()

		el.querySelectorAll('footer button')[0].click()
		expect(emitted).toEqual([['close']])
		expect(restoreOriginal).not.toHaveBeenCalled()
	})
})

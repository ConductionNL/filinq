/**
 * The draft editor's behaviour (generate-draft-and-attended-edit): it shows
 * a draft's saved text, says not found for a draft that is not the user's,
 * sends a wizard run still being answered back to the wizard, saves only a
 * changed text, finishes after saving what changed and offers the document,
 * shows the wizard refusal, and discards back to the list.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-20-generate-draft-and-attended-edit/tasks.md#task-3.2
 */

import { join } from 'node:path'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

const world = vi.hoisted(() => ({ calls: [], answers: {}, toasts: [] }))

vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars = {}) => text.replace(/\{(\w+)\}/g, (_, key) => String(vars[key] ?? '')),
}))
vi.mock('@nextcloud/dialogs', () => ({
	showError: (message) => world.toasts.push(['error', message]),
	showSuccess: (message) => world.toasts.push(['success', message]),
}))
// The real module is kept for its pure helpers; its HTTP client is never reached.
vi.mock('@nextcloud/axios', () => ({ default: {} }))
vi.mock('../../src/services/drafts.js', async (original) => {
	const real = await original()
	const record = (name) => async (...args) => {
		world.calls.push([name, ...args])
		return world.answers[name] ?? { ok: true, data: {} }
	}
	return {
		...real,
		loadDraft: record('loadDraft'),
		saveDraft: record('saveDraft'),
		finishDraft: record('finishDraft'),
		discardDraft: record('discardDraft'),
	}
})

const FILE = join(import.meta.dirname, '../../src/views/drafts/DraftEditor.vue')

const DRAFT = {
	id: 'd1',
	'@self': { id: 'd1', updated: '2026-11-13T09:00:00Z' },
	stage: 'editing',
	origin: 'correspondence',
	templateName: 'Algemene Correspondentiebrief',
	html: '<p>Geachte heer Jansen,</p>',
	warnings: ['The field "kenmerk" had no value.'],
	expiresAt: '2026-12-13T09:00:00Z',
}

/**
 * A mounted editor for draft d1.
 *
 * @return {Promise<object>} The instance.
 */
async function editor() {
	const options = await loadSfcOptions(FILE)
	const vm = createInstance(options, { route: { params: { id: 'd1' }, query: {} } })
	vm.$replaced = []
	vm.$router.replace = (to) => vm.$replaced.push(to)
	await options.mounted.call(vm)
	return vm
}

beforeEach(() => {
	world.calls = []
	world.toasts = []
	world.answers = { loadDraft: { ok: true, data: { ...DRAFT } } }
	globalThis.URL.createObjectURL = vi.fn(() => 'blob:doc')
	globalThis.URL.revokeObjectURL = vi.fn()
	globalThis.document = {
		createElement: () => ({ click: vi.fn() }),
		body: { appendChild: vi.fn(), removeChild: vi.fn() },
	}
})

describe('the draft editor', () => {
	it('shows the saved text, the render warnings and when the draft goes', async () => {
		const vm = await editor()

		expect(world.calls).toEqual([['loadDraft', 'd1']])
		expect(vm.html).toBe('<p>Geachte heer Jansen,</p>')
		expect(vm.dirty).toBe(false)
		expect(vm.finished).toBe(false)
		expect(vm.warnings).toEqual(['The field "kenmerk" had no value.'])
		expect(vm.metaLine).toContain('Last change ')
		expect(vm.metaLine).toContain('unless saved again')
	})

	it('says not found for a draft that is not the user\'s, without an error toast', async () => {
		world.answers.loadDraft = { ok: false, status: 404, error: 'Not found' }
		const vm = await editor()

		expect(vm.draft).toBeNull()
		expect(vm.loading).toBe(false)
		expect(world.toasts).toEqual([])
	})

	it('sends a wizard run still being answered back into the wizard', async () => {
		world.answers.loadDraft = { ok: true, data: { id: 'd2', stage: 'answering', templateId: 't1' } }
		const vm = await editor()

		expect(vm.$replaced).toEqual([{ name: 'WizardRunner', params: { id: 't1' }, query: { draft: 'd2' } }])
		expect(vm.draft).toBeNull()
	})

	it('saves the changed text and is then clean', async () => {
		const vm = await editor()
		vm.html = '<p>Geachte heer Jansen,</p><p>Met hartelijke groet</p>'

		expect(await vm.save()).toBe(true)

		expect(world.calls[1]).toEqual(['saveDraft', DRAFT, { html: '<p>Geachte heer Jansen,</p><p>Met hartelijke groet</p>' }])
		expect(vm.dirty).toBe(false)
		expect(world.toasts).toEqual([['success', 'Draft saved.']])
	})

	it('saves what changed before finishing, offers the document and closes the draft', async () => {
		world.answers.finishDraft = { ok: true, data: { size: 4 }, json: null, filename: 'document.pdf' }
		const vm = await editor()
		vm.html = '<p>Gewijzigd</p>'

		await vm.finish()

		expect(world.calls.map(([name]) => name)).toEqual(['loadDraft', 'saveDraft', 'finishDraft'])
		expect(world.calls[2]).toEqual(['finishDraft', 'd1'])
		expect(URL.createObjectURL).toHaveBeenCalledWith({ size: 4 })
		expect(vm.finished).toBe(true)
		expect(world.toasts.at(-1)).toEqual(['success', 'The document is stored.'])
	})

	it('finishes an unchanged draft without saving it, and an HTML answer offers no download', async () => {
		world.answers.finishDraft = { ok: true, data: null, json: { content: '<p>x</p>' }, filename: '' }
		const vm = await editor()

		await vm.finish()

		expect(world.calls.map(([name]) => name)).toEqual(['loadDraft', 'finishDraft'])
		expect(URL.createObjectURL).not.toHaveBeenCalled()
		expect(vm.finished).toBe(true)
	})

	it('does not finish when the save failed, and shows the wizard refusal', async () => {
		world.answers.saveDraft = { ok: false, status: 500 }
		const vm = await editor()
		vm.html = '<p>Gewijzigd</p>'
		await vm.finish()
		expect(world.calls.map(([name]) => name)).toEqual(['loadDraft', 'saveDraft'])
		expect(vm.errorText).toBe('The draft could not be saved.')

		world.answers.saveDraft = { ok: true, data: {} }
		world.answers.finishDraft = { ok: false, status: 422, error: 'Answers missing', errors: {} }
		await vm.finish()
		expect(vm.errorText).toBe('Some wizard answers were not accepted. Open the wizard again to correct them.')
		expect(vm.finished).toBe(false)
	})

	it('discards the draft and goes back to the list', async () => {
		const vm = await editor()
		vm.confirmDiscard = true

		await vm.discard()

		expect(world.calls.at(-1)).toEqual(['discardDraft', 'd1'])
		expect(vm.confirmDiscard).toBe(false)
		expect(vm.$pushed).toEqual([{ name: 'Drafts' }])
	})

	it('keeps a finished draft read-only', async () => {
		world.answers.loadDraft = { ok: true, data: { ...DRAFT, stage: 'finished' } }
		const vm = await editor()

		expect(vm.finished).toBe(true)
		expect(await vm.save()).toBe(false)
		expect(world.calls).toEqual([['loadDraft', 'd1']])
		expect(vm.metaLine).toMatch(/^Finished\. Removed on /)
	})
})

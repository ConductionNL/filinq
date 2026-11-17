/**
 * The wizard's drafts (generate-draft-and-attended-edit REQ-GDA-003): Save
 * and finish later keeps the answers so far and the step, a saved run
 * continues at that step with those answers, Open for editing renders the
 * answers into a draft and passes them as the wizard context, and the run's
 * saved answers make way once it went on.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.4
 */

import { join } from 'node:path'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

const world = vi.hoisted(() => ({ calls: [], answers: {}, toasts: [] }))

const WIZARD = vi.hoisted(() => ({
	uuid: 'w1',
	version: 2,
	name: 'Besluit op aanvraag',
	questions: [
		{ key: 'naam', label: 'Naam', type: 'text', required: true },
		{ key: 'adres', label: 'Adres', type: 'text', required: true },
		{ key: 'datum', label: 'Datum', type: 'date', required: true },
		{ key: 'besluit', label: 'Besluit', type: 'choice', required: true, choices: [{ value: 'toe', label: 'Toegekend' }] },
		{ key: 'toelichting', label: 'Toelichting', type: 'text', required: false },
	],
}))

vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
vi.mock('@nextcloud/axios', () => ({ default: {} }))
vi.mock('@nextcloud/dialogs', () => ({
	showError: (message) => world.toasts.push(['error', message]),
	showSuccess: (message) => world.toasts.push(['success', message]),
}))
vi.mock('../../src/pinia.js', () => ({ default: {} }))
vi.mock('../../src/store/store.js', () => ({ useObjectStore: () => ({}) }))
vi.mock('../../src/services/drafts.js', async (original) => {
	const real = await original()
	const record = (name) => async (...args) => {
		world.calls.push([name, ...args])
		return world.answers[name] ?? { ok: true, data: {} }
	}
	return {
		...real,
		createDraft: record('createDraft'),
		discardDraft: record('discardDraft'),
		loadDraft: record('loadDraft'),
		saveWizardDraft: record('saveWizardDraft'),
	}
})
vi.mock('../../src/services/wizard.js', async (original) => {
	const real = await original()
	return {
		...real,
		loadTemplateWizard: async () => ({ ok: true, data: { wizard: WIZARD } }),
		prefillWizard: async () => {
			world.calls.push(['prefillWizard'])
			return { ok: false }
		},
		generateWithWizard: async () => ({ ok: true, data: { size: 1 }, filename: 'besluit.pdf' }),
	}
})

const FILE = join(import.meta.dirname, '../../src/views/wizard/WizardRunner.vue')

/**
 * The wizard of template t1, mounted with these query parameters.
 *
 * @param {object} query The route query.
 * @return {Promise<object>} The instance.
 */
async function runner(query = {}) {
	const options = await loadSfcOptions(FILE)
	const vm = createInstance(options, { route: { params: { id: 't1' }, query } })
	await options.mounted.call(vm)
	return vm
}

beforeEach(() => {
	world.calls = []
	world.toasts = []
	world.answers = {}
	globalThis.URL.createObjectURL = vi.fn(() => 'blob:doc')
	globalThis.URL.revokeObjectURL = vi.fn()
	globalThis.document = { createElement: () => ({ click: vi.fn() }) }
})

describe('the wizard and its drafts', () => {
	it('saves the answers so far and the step, then goes to Drafts', async () => {
		world.answers.saveWizardDraft = { ok: true, data: { id: 'd2', stage: 'answering' } }
		const vm = await runner()
		vm.answers = { naam: 'Jan Jansen', adres: 'Dorpsstraat 1', datum: '2026-11-01' }
		vm.step = 3

		await vm.saveForLater()

		expect(world.calls.at(-1)).toEqual(['saveWizardDraft', {
			draft: null,
			templateId: 't1',
			templateName: 'Besluit op aanvraag',
			wizard: WIZARD,
			answers: { naam: 'Jan Jansen', adres: 'Dorpsstraat 1', datum: '2026-11-01' },
			step: 3,
			format: 'pdf',
		}])
		expect(vm.draft).toEqual({ id: 'd2', stage: 'answering' })
		expect(vm.$pushed).toEqual([{ name: 'Drafts' }])
	})

	it('continues a saved run at question four with the first three answers, without prefilling', async () => {
		world.answers.loadDraft = { ok: true, data: { id: 'd2', stage: 'answering', wizardAnswers: { naam: 'Jan Jansen', adres: 'Dorpsstraat 1', datum: '2026-11-01' }, wizardStep: 3, format: 'odf' } }

		const vm = await runner({ draft: 'd2', register: 'brp', schema: 'persoon', objectId: 'p1' })

		expect(world.calls).toEqual([['loadDraft', 'd2']])
		expect(vm.step).toBe(3)
		expect(vm.current.key).toBe('besluit')
		expect(vm.answers).toEqual({ naam: 'Jan Jansen', adres: 'Dorpsstraat 1', datum: '2026-11-01' })
		expect(vm.format).toBe('odf')
	})

	it('saves a resumed run onto its own draft', async () => {
		world.answers.loadDraft = { ok: true, data: { id: 'd2', stage: 'answering', wizardAnswers: {}, wizardStep: 0 } }
		const vm = await runner({ draft: 'd2' })

		await vm.saveForLater()

		expect(world.calls.at(-1)[1].draft).toEqual({ id: 'd2', stage: 'answering', wizardAnswers: {}, wizardStep: 0 })
	})

	it('starts afresh when the saved run is gone or no longer being answered', async () => {
		world.answers.loadDraft = { ok: true, data: { id: 'd2', stage: 'editing' } }
		const vm = await runner({ draft: 'd2' })

		expect(vm.draft).toBeNull()
		expect(vm.step).toBe(0)
		expect(world.toasts).toEqual([['error', 'The saved answers could not be found. The wizard starts again.']])
	})

	it('opens the answers for editing as a wizard draft and drops the saved run', async () => {
		world.answers.loadDraft = { ok: true, data: { id: 'd2', stage: 'answering', wizardAnswers: { naam: 'Jan', adres: 'X', datum: '2026-11-01', besluit: 'toe' }, wizardStep: 4 } }
		world.answers.createDraft = { ok: true, data: { id: 'd3', stage: 'editing' } }
		const vm = await runner({ draft: 'd2' })

		await vm.openForEditing()

		const [, request] = world.calls.find(([name]) => name === 'createDraft')
		expect(request.templateId).toBe('t1')
		expect(request.origin).toBe('wizard')
		expect(request.options.format).toBe('pdf')
		expect(request.options.wizardContext).toEqual({ wizardId: 'w1', wizardVersion: '2', answers: { naam: 'Jan', adres: 'X', datum: '2026-11-01', besluit: 'toe' }, step: 4 })
		expect(world.calls.at(-1)).toEqual(['discardDraft', 'd2'])
		expect(vm.$pushed).toEqual([{ name: 'DraftEditor', params: { id: 'd3' } }])
	})

	it('keeps the saved run when the draft could not be opened', async () => {
		world.answers.loadDraft = { ok: true, data: { id: 'd2', stage: 'answering', wizardAnswers: {}, wizardStep: 0 } }
		world.answers.createDraft = { ok: false, status: 409 }
		const vm = await runner({ draft: 'd2' })

		await vm.openForEditing()

		expect(world.calls.map(([name]) => name)).not.toContain('discardDraft')
		expect(world.toasts).toEqual([['error', 'This template has a plain-language version. Generate it directly.']])
		expect(vm.$pushed).toEqual([])
	})

	it('drops the saved run once the document was generated directly', async () => {
		world.answers.loadDraft = { ok: true, data: { id: 'd2', stage: 'answering', wizardAnswers: {}, wizardStep: 0 } }
		const vm = await runner({ draft: 'd2' })

		await vm.generate()

		expect(world.calls.at(-1)).toEqual(['discardDraft', 'd2'])
		expect(vm.draft).toBeNull()
	})
})

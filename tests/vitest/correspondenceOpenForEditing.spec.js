/**
 * "Open for editing" on Letters & correspondence (generate-draft-and-attended-edit
 * REQ-GDA-001, REQ-GDA-005): it renders the letter into a draft with the page's
 * template, recipients and options and opens the draft editor, files nothing,
 * hides itself for a template with a plain-language version, and says why when
 * the server refuses one.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.3
 */

import { join } from 'node:path'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

const world = vi.hoisted(() => ({ calls: [], created: null, editable: true, store: null }))

vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
vi.mock('@nextcloud/axios', () => ({ default: {} }))
vi.mock('../../src/services/drafts.js', () => ({
	createDraft: async (request) => {
		world.calls.push(['createDraft', request])
		return world.created
	},
	templateEditability: async (templateId) => {
		world.calls.push(['templateEditability', templateId])
		return { known: true, editable: world.editable }
	},
}))
vi.mock('../../src/services/formatMatrix.js', () => ({
	fetchFormatMatrix: async () => ({}),
	formatOptions: () => [],
	usableFormat: (format) => format,
}))
vi.mock('../../src/services/printJobs.js', () => ({ buildPrintRequest: () => ({}) }))
vi.mock('../../src/store/modules/correspondence.js', () => ({ useCorrespondenceStore: () => world.store }))

/**
 * The page, mounted with a letter filled in.
 *
 * @return {Promise<object>} The instance.
 */
async function page() {
	const options = await loadSfcOptions(join(import.meta.dirname, '../../src/views/correspondence/CorrespondenceIndex.vue'))
	const vm = createInstance(options)
	await options.mounted.call(vm)
	return vm
}

beforeEach(() => {
	world.calls = []
	world.editable = true
	world.created = { ok: true, data: { id: 'd1', stage: 'editing' } }
	world.store = {
		templateId: 'brief-algemeen',
		dataRefs: [{ register: 'brp', schema: 'persoon', id: 'p1' }],
		format: 'pdf',
		huisstijlId: '',
		caseReference: 'Z-2026-001',
		loading: false,
		recipientIds: [],
	}
})

describe('Open for editing', () => {
	it('opens the letter as a draft in the draft editor', async () => {
		const vm = await page()

		await vm.openForEditing()

		expect(world.calls).toEqual([
			['templateEditability', 'brief-algemeen'],
			['createDraft', {
				templateId: 'brief-algemeen',
				dataRefs: [{ register: 'brp', schema: 'persoon', id: 'p1' }],
				options: { format: 'pdf', huisstijlId: undefined, caseReference: 'Z-2026-001' },
				origin: 'correspondence',
			}],
		])
		expect(vm.$pushed).toEqual([{ name: 'DraftEditor', params: { id: 'd1' } }])
		expect(vm.opening).toBe(false)
	})

	it('is hidden for a template with a plain-language version, and shown again for another', async () => {
		world.editable = false
		const vm = await page()
		expect(vm.templateEditable).toBe(false)

		world.editable = true
		world.store.templateId = 'besluit'
		await vm.checkEditability()
		expect(vm.templateEditable).toBe(true)
	})

	it('says why the server refused a plain-language template, and hides the action', async () => {
		world.created = { ok: false, status: 409, error: 'conflict' }
		const vm = await page()

		await vm.openForEditing()

		expect(vm.openError).toBe('This template has a plain-language version. Generate it directly.')
		expect(vm.templateEditable).toBe(false)
		expect(vm.$pushed).toEqual([])
	})

	it('shows any other refusal as the server put it', async () => {
		world.created = { ok: false, status: 404, error: 'Template not found' }
		const vm = await page()

		await vm.openForEditing()

		expect(vm.openError).toBe('Template not found')
		expect(vm.templateEditable).toBe(true)
	})
})

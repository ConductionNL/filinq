/**
 * What the new legal hold and new erasure dialogs send and keep when they
 * are submitted, before their shared submit step moved to a mixin.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of the create dialogs' submit
 */

import { join } from 'node:path'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

const world = vi.hoisted(() => ({ calls: [], answer: { ok: true, data: { uuid: 'new' } }, seen: null }))

vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
vi.mock('../../src/services/legalHolds.js', () => ({
	holdTypeOptions: () => [{ id: 'litigation', label: 'Litigation' }],
	parseRefs: (text) => text.split('\n').map((line) => line.trim()).filter(Boolean),
	createCase: async (payload) => {
		world.calls.push(['createCase', payload, world.seen()])
		return world.answer
	},
}))
vi.mock('../../src/services/subjectErasures.js', () => ({
	parseLines: (text) => text.split('\n').map((line) => line.trim()).filter(Boolean),
	createRequest: async (payload) => {
		world.calls.push(['createRequest', payload, world.seen()])
		return world.answer
	},
}))

/**
 * A dialog instance.
 *
 * @param {string} name The dialog file name.
 * @return {Promise<object>} The instance.
 */
async function dialog(name) {
	const options = await loadSfcOptions(join(import.meta.dirname, '../../src/dialogs/' + name + '.vue'))
	const vm = createInstance(options)
	world.seen = () => ({ busy: vm.busy, error: vm.error })
	return vm
}

beforeEach(() => {
	world.calls = []
	world.answer = { ok: true, data: { uuid: 'new' } }
})

describe('create dialogs submit', () => {
	it('the legal hold dialog sends the trimmed case and emits it', async () => {
		const vm = await dialog('NewLegalHoldDialog')
		Object.assign(vm, { name: ' Zaak ', reason: ' Claim ', caseReference: ' Z-1 ', custodian: ' anna ', documents: 'd1\n\nd2', dossiers: ' x1 ', error: 'old' })
		vm.holdType = { id: 'litigation' }
		await vm.onCreate()
		expect(world.calls).toEqual([[
			'createCase',
			{ name: 'Zaak', holdType: 'litigation', reason: 'Claim', caseReference: 'Z-1', custodian: 'anna', scopeDocuments: ['d1', 'd2'], scopeDossiers: ['x1'] },
			{ busy: true, error: '' },
		]])
		expect(vm.$emitted).toEqual([['created', { uuid: 'new' }]])
		expect([vm.busy, vm.error]).toEqual([false, ''])
	})

	it('the erasure dialog sends the trimmed request and emits it', async () => {
		const vm = await dialog('NewSubjectErasureDialog')
		Object.assign(vm, { subject: ' Jan ', identifiers: 'a\nb', ground: ' 17 ', dueAt: '2026-12-01', error: 'old' })
		await vm.onCreate()
		expect(world.calls).toEqual([[
			'createRequest',
			{ subject: 'Jan', identifiers: ['a', 'b'], ground: '17', dueAt: '2026-12-01' },
			{ busy: true, error: '' },
		]])
		expect(vm.$emitted).toEqual([['created', { uuid: 'new' }]])
	})

	it.each(['NewLegalHoldDialog', 'NewSubjectErasureDialog'])('%s keeps the refusal and emits nothing', async (name) => {
		world.answer = { ok: false, error: 'Refused' }
		const vm = await dialog(name)
		Object.assign(vm, { name: 'a', reason: 'b', subject: 'c', ground: 'd' })
		await vm.onCreate()
		expect(vm.$emitted).toEqual([])
		expect([vm.busy, vm.error]).toEqual([false, 'Refused'])
	})
})

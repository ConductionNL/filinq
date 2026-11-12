/**
 * The folder anonymisation store's fresh state and what reset() puts back.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of the folder anonymisation store state
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useFolderAnonymizationStore } from '../../src/store/modules/folderAnonymization.js'

vi.mock('@nextcloud/axios', () => ({ default: vi.fn(async () => ({ data: {} })) }))

const FRESH = {
	folderPath: '',
	folderId: null,
	batchId: null,
	batchStatus: null,
	files: [],
	entities: [],
	progress: 0,
	totalFiles: 0,
	totalEntities: 0,
	error: null,
	processing: false,
	pollTimer: null,
	minConfidence: 0.0,
	reviewRequired: false,
	skippedUnreviewed: [],
	dossier: {
		uuid: null,
		name: '',
		description: '',
		bases: ['art-5-1-2-e'],
		creating: false,
		error: null,
	},
	appendBasisSummary: true,
	report: {
		generating: false,
		result: null,
		error: null,
	},
}

describe('folder anonymisation store state', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('starts from the fresh state', () => {
		const store = useFolderAnonymizationStore()
		expect(store.$state).toEqual(FRESH)
	})

	it('gives every store its own nested objects', () => {
		const first = useFolderAnonymizationStore()
		first.dossier.bases.push('changed')
		first.report.error = 'changed'
		setActivePinia(createPinia())
		const second = useFolderAnonymizationStore()
		expect(second.$state).toEqual(FRESH)
	})

	it('reset() stops polling and puts every field back', () => {
		vi.useFakeTimers()
		const store = useFolderAnonymizationStore()
		const timer = setInterval(() => {}, 1000)
		Object.assign(store, {
			folderPath: '/x',
			folderId: 3,
			batchId: 'b',
			batchStatus: 'running',
			files: [{ id: 1 }],
			entities: [{ included: true }],
			progress: 50,
			totalFiles: 2,
			totalEntities: 9,
			error: 'boom',
			processing: true,
			pollTimer: timer,
			minConfidence: 0.5,
			reviewRequired: true,
			skippedUnreviewed: [{ id: 2 }],
			appendBasisSummary: false,
		})
		store.dossier.uuid = 'd'
		store.dossier.bases.push('extra')
		store.report.result = { ok: true }

		store.reset()

		expect(store.$state).toEqual(FRESH)
		expect(vi.getTimerCount()).toBe(0)
		vi.useRealTimers()
	})

	it('reset() twice leaves the defaults untouched for the next reset', () => {
		const store = useFolderAnonymizationStore()
		store.reset()
		store.dossier.bases.push('after-first-reset')
		store.reset()
		expect(store.dossier.bases).toEqual(['art-5-1-2-e'])
	})
})

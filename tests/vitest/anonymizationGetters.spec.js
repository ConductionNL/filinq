// @vitest-environment jsdom
/**
 * SPDX-FileCopyrightText: 2026 Conduction / DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Runs under jsdom: the anonymisation store transitively imports
 * @nextcloud/axios (and uses DOMParser), which need a DOM/`window` at load.
 *
 * Unit tests for the anonymisation store's derived getters
 * (src/store/modules/anonymization.js): the queue-summary getters
 * (hasFiles / hasCompleted / hasExtracted / allDone / isProcessing) and the
 * findByFileId lookup that matches either the original or post-anonymisation
 * file id. State is seeded directly; the async pipeline actions are out of
 * scope.
 */

import { describe, it, expect, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAnonymizationStore } from '../../src/store/modules/anonymization.js'

beforeEach(() => {
	setActivePinia(createPinia())
})

describe('anonymisation store — summary getters', () => {
	it('reports empty defaults', () => {
		const store = useAnonymizationStore()
		expect(store.hasFiles).toBe(false)
		expect(store.hasCompleted).toBe(false)
		expect(store.hasExtracted).toBe(false)
		expect(store.allDone).toBe(false) // false when there are no files
		expect(store.isProcessing).toBe(false)
	})

	it('hasFiles / hasCompleted / hasExtracted reflect queue contents', () => {
		const store = useAnonymizationStore()
		store.files = [
			{ status: 'queued' },
			{ status: 'extracted' },
			{ status: 'completed' },
		]
		expect(store.hasFiles).toBe(true)
		expect(store.hasCompleted).toBe(true)
		expect(store.hasExtracted).toBe(true)
	})

	it('allDone is true only when every file is completed or errored', () => {
		const store = useAnonymizationStore()
		store.files = [{ status: 'completed' }, { status: 'error' }]
		expect(store.allDone).toBe(true)

		store.files = [{ status: 'completed' }, { status: 'extracting' }]
		expect(store.allDone).toBe(false)
	})

	it('isProcessing mirrors the processing flag', () => {
		const store = useAnonymizationStore()
		expect(store.isProcessing).toBe(false)
		store.processing = true
		expect(store.isProcessing).toBe(true)
	})
})

describe('anonymisation store — findByFileId', () => {
	it('matches on the original fileId (numeric-coerced)', () => {
		const store = useAnonymizationStore()
		const entry = { fileId: 42, name: 'doc.pdf' }
		store.files = [entry, { fileId: 7 }]
		expect(store.findByFileId(42)).toBe(entry)
		expect(store.findByFileId('42')).toBe(entry) // string id coerced
	})

	it('also matches on the post-anonymisation anonymizedFileId', () => {
		const store = useAnonymizationStore()
		const entry = { fileId: 42, anonymizedFileId: 99, name: 'doc.pdf' }
		store.files = [entry]
		expect(store.findByFileId(99)).toBe(entry)
	})

	it('returns undefined for an unknown id or null/undefined', () => {
		const store = useAnonymizationStore()
		store.files = [{ fileId: 1 }]
		expect(store.findByFileId(123)).toBeUndefined()
		expect(store.findByFileId(null)).toBeUndefined()
		expect(store.findByFileId(undefined)).toBeUndefined()
	})
})

// @vitest-environment jsdom
/**
 * SPDX-FileCopyrightText: 2026 Conduction / Filinq Contributors
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

/*
 * Identity note (Vue 2 → Vue 3).
 *
 * These assertions use `toBe` on purpose: `findByFileId` must return the very
 * entry the store holds, not a copy — a copy would make callers mutate
 * something the store never sees.
 *
 * Under Vue 2 / pinia 2, assigning `store.files = [entry]` left `entry` itself
 * in the array, so `toBe(entry)` held. Vue 3's reactivity wraps everything
 * written into reactive state in a Proxy, so the array now holds a proxy OF
 * `entry`, and `Object.is(proxy, entry)` is false even though the two
 * serialise identically. The assertion is therefore re-anchored on
 * `store.files[i]` — still strict identity, just against the object the store
 * actually exposes. Swapping to `toEqual` would have hidden a genuine
 * copy-instead-of-reference bug, which is exactly what this test is for.
 */
describe('anonymisation store — findByFileId', () => {
	it('matches on the original fileId (numeric-coerced)', () => {
		const store = useAnonymizationStore()
		const entry = { fileId: 42, name: 'doc.pdf' }
		store.files = [entry, { fileId: 7 }]
		const held = store.files[0]
		expect(store.findByFileId(42)).toBe(held)
		expect(store.findByFileId('42')).toBe(held) // string id coerced
		// Guard the re-anchoring itself: `held` must still be the entry we put
		// in, otherwise this test would pass against any object at index 0.
		expect(held).toEqual(entry)
	})

	it('also matches on the post-anonymisation anonymizedFileId', () => {
		const store = useAnonymizationStore()
		const entry = { fileId: 42, anonymizedFileId: 99, name: 'doc.pdf' }
		store.files = [entry]
		const held = store.files[0]
		expect(store.findByFileId(99)).toBe(held)
		expect(held).toEqual(entry)
	})

	it('returns undefined for an unknown id or null/undefined', () => {
		const store = useAnonymizationStore()
		store.files = [{ fileId: 1 }]
		expect(store.findByFileId(123)).toBeUndefined()
		expect(store.findByFileId(null)).toBeUndefined()
		expect(store.findByFileId(undefined)).toBeUndefined()
	})
})

/**
 * SPDX-FileCopyrightText: 2026 Conduction / Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Unit tests for the signing folder half of the signing Pinia store
 * (src/store/modules/signing.js): reading the folder, what it keeps, and
 * signing a selection in one pass.
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */

import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

const get = vi.fn()
const post = vi.fn()

vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (...args) => get(...args),
		post: (...args) => post(...args),
	},
}))

const { useSigningStore } = await import('../../src/store/modules/signing.js')

beforeEach(() => {
	setActivePinia(createPinia())
	get.mockReset()
	post.mockReset()
	vi.spyOn(console, 'error').mockImplementation(() => {})
})

afterEach(() => {
	vi.restoreAllMocks()
})

describe('the signing folder store', () => {
	it('starts with an empty folder', () => {
		const store = useSigningStore()
		expect(store.folderEntries).toEqual([])
		expect(store.folderTotal).toBe(0)
	})

	it('reads the folder and keeps its entries and its total', async () => {
		get.mockResolvedValueOnce({
			data: {
				total: 40,
				limit: 50,
				offset: 0,
				entries: [
					{ requestId: 'a', documentName: 'Besluit A' },
					{ requestId: 'b', documentName: 'Besluit B' },
				],
			},
		})

		const store = useSigningStore()
		await store.fetchSigningFolder()

		expect(get).toHaveBeenCalledWith(
			'/index.php/apps/filinq/api/signing/folder',
			{
				params: { limit: 50, offset: 0 },
			},
		)
		expect(store.folderEntries).toHaveLength(2)
		expect(store.folderTotal).toBe(40)
		expect(store.loading).toBe(false)
	})

	it('asks for the page it was given', async () => {
		get.mockResolvedValueOnce({ data: { total: 40, entries: [] } })

		const store = useSigningStore()
		await store.fetchSigningFolder(10, 30)

		expect(get).toHaveBeenCalledWith(
			'/index.php/apps/filinq/api/signing/folder',
			{
				params: { limit: 10, offset: 30 },
			},
		)
	})

	it('reports a folder it could not read instead of showing an empty one', async () => {
		get.mockRejectedValueOnce(new Error('Network down'))

		const store = useSigningStore()
		const result = await store.fetchSigningFolder()

		expect(result).toBeNull()
		expect(store.error).toBe('Network down')
		expect(store.folderEntries).toEqual([])
		expect(store.loading).toBe(false)
	})

	it('signs a selection in one pass and hands back the per-document results', async () => {
		post.mockResolvedValueOnce({
			data: {
				signed: 1,
				refused: 1,
				results: [
					{ requestId: 'a', signed: true },
					{
						requestId: 'b',
						signed: false,
						reason: 'Outside your mandate',
					},
				],
			},
		})

		const store = useSigningStore()
		const outcome = await store.signFolderSelection(['a', 'b'])

		expect(post).toHaveBeenCalledWith(
			'/index.php/apps/filinq/api/signing/folder/sign',
			{
				requestIds: ['a', 'b'],
			},
		)
		expect(outcome.signed).toBe(1)
		expect(outcome.results[1].reason).toBe('Outside your mandate')
	})
})

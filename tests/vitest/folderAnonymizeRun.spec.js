/**
 * The folder anonymisation store's anonymizeFolder run: which calls go out,
 * in which order, and what the store and the files hold afterwards.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of the folder anonymise run
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, it, vi } from 'vitest'
import { useFolderAnonymizationStore } from '../../src/store/modules/folderAnonymization.js'
import { golden } from './golden/golden.js'

const calls = []
const failing = new Set()

vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))
vi.mock('../../src/services/reviewMark.js', () => ({
	markChecked: vi.fn(),
	redactionPayload: (list) => list,
	reviewIsCurrent: (mark) => mark === 'checked',
}))
vi.mock('@nextcloud/axios', () => {
	const axios = vi.fn(async () => ({ data: {} }))
	axios.patch = vi.fn(async (url, body) => {
		calls.push(['PATCH', url, body])
		if (failing.has(url)) {
			throw Object.assign(new Error('patch down'), { response: { data: { error: 'patch refused' } } })
		}
		return { data: {} }
	})
	axios.post = vi.fn(async (url, body) => {
		calls.push(['POST', url, body])
		if (failing.has(url)) {
			throw new Error('post down')
		}
		return { data: { anonymizedFileId: 'anon-' + url.split('/').pop(), replacementCount: 2, residualCount: 0 } }
	})
	return { default: axios }
})

/**
 * A store with two checked files, one unchecked file and two entities.
 *
 * @param {object} overrides State to set on top.
 * @return {object} The store.
 */
function storeWith(overrides = {}) {
	const store = useFolderAnonymizationStore()
	Object.assign(store, {
		folderId: 42,
		files: [
			{ fileId: 1, fileName: 'a.pdf', status: 'extracted', reviewMark: 'checked' },
			{ fileId: 2, fileName: 'b.pdf', status: 'extracted', reviewMark: 'stale' },
			{ fileId: 3, fileName: 'c.pdf', status: 'extracted', reviewMark: 'checked' },
		],
		entities: [
			{ type: 'PERSON', value: 'Jan', highestConfidence: 0.9, included: true, relationIds: ['r1', 'r2'], _decisionBases: ['5.1.2e'] },
			{ type: 'CITY', value: 'Delft', highestConfidence: 0.8, included: false, relationIds: ['r3'] },
		],
		...overrides,
	})
	return store
}

/**
 * What the run left behind.
 *
 * @param {object} store The store.
 * @return {object} The trace.
 */
function trace(store) {
	return {
		calls: [...calls],
		batchStatus: store.batchStatus,
		error: store.error,
		processing: store.processing,
		progress: store.progress,
		reviewRequired: store.reviewRequired,
		skippedUnreviewed: store.skippedUnreviewed,
		files: store.files.map((f) => ({ ...f })),
		entityErrors: store.entities.map((e) => e._patchError ?? null),
	}
}

describe('folder anonymise run', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		calls.length = 0
		failing.clear()
	})

	it('anonymises only the checked files, with the summary', async () => {
		const store = storeWith()
		await store.anonymizeFolder()
		golden('folderAnonymizeRun-checked-files-with-summary', trace(store))
	})

	it('sends no summary and no dossier key when neither is set', async () => {
		const store = storeWith({ folderId: null, appendBasisSummary: false })
		await store.anonymizeFolder()
		golden('folderAnonymizeRun-no-summary-no-dossier-key', trace(store))
	})

	it('asks for a review when nothing is checked', async () => {
		const store = storeWith()
		store.files.forEach((f) => { f.reviewMark = 'stale' })
		await store.anonymizeFolder()
		golden('folderAnonymizeRun-review-required', trace(store))
	})

	it('keeps going when one file fails', async () => {
		failing.add('/apps/filinq/api/anonymization/anonymize/1')
		const store = storeWith()
		await store.anonymizeFolder()
		golden('folderAnonymizeRun-one-file-fails', trace(store))
	})

	it('reports an error when every file fails', async () => {
		failing.add('/apps/filinq/api/anonymization/anonymize/1')
		failing.add('/apps/filinq/api/anonymization/anonymize/3')
		const store = storeWith()
		await store.anonymizeFolder()
		golden('folderAnonymizeRun-every-file-fails', trace(store))
	})

	it('keeps a failed decision on the entity and still anonymises', async () => {
		failing.add('/apps/filinq/api/anonymization/relations/r1')
		const store = storeWith()
		await store.anonymizeFolder()
		golden('folderAnonymizeRun-decision-fails', trace(store))
	})

	it('stops with the error when the run itself throws', async () => {
		const store = storeWith({
			folderId: {
				toString() {
					throw new Error('no folder id')
				},
			},
		})
		await store.anonymizeFolder()
		golden('folderAnonymizeRun-run-throws', trace(store))
	})
})

// @vitest-environment jsdom
/**
 * Golden: which entries the anonymisation store's anonymiseAllExtracted runs,
 * reports and counts, recorded on the implementation where the batch was
 * set up and counted inline.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing batch anonymisation
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, it, vi } from 'vitest'
import { useAnonymizationStore } from '../../src/store/modules/anonymization.js'
import { golden } from './golden/golden.js'

const CHECKED = { signature: '[]' }

/**
 * A queue entry.
 *
 * @param {number|string} fileId The file id.
 * @param {string} status The entry status.
 * @param {object|null} reviewMark The review mark.
 * @return {object}
 */
function entry(fileId, status, reviewMark) {
	return { fileId, name: 'f' + fileId, status, reviewMark, entities: [] }
}

const OPTIONS = [
	{},
	{ fileIds: [1, '3', 6, 2] },
	{ files: [{ fileId: 6 }, { fileId: 7 }], appendBasisSummary: true },
	{ files: [], fileIds: [] },
	{ files: 'x', outputFormat: 'pdf' },
	{ fileIds: ['5'] },
]

beforeEach(() => {
	vi.spyOn(console, 'error').mockImplementation(() => {})
})

describe('anonymiseAllExtracted golden', () => {
	it('runs the checked entries and reports the rest', async () => {
		const out = []
		for (const options of OPTIONS) {
			setActivePinia(createPinia())
			const store = useAnonymizationStore()
			store.files.push(
				entry(1, 'extracted', CHECKED),
				entry(2, 'extracted', null),
				entry('3', 'extracted', CHECKED),
				entry(4, 'completed', CHECKED),
				entry(5, 'extracted', CHECKED),
				entry(8, 'extracted', { signature: 'other' }),
			)
			const calls = []
			store.ensureExtracted = async (meta) => {
				calls.push(['ensure', meta, { ...store.batch }])
				if (meta.fileId === 6) {
					store.files.push(entry(6, 'extracted', CHECKED))
				}
			}
			store.anonymiseEntry = async (e, opts) => {
				calls.push(['anonymise', e.fileId, opts, { ...store.batch }])
				if (String(e.fileId) === '3') {
					e.status = 'error'
					throw new Error('failed')
				}
				e.status = Number(e.fileId) === 5 ? 'error' : 'completed'
			}
			await store.anonymiseAllExtracted(options)
			out.push({
				options,
				calls,
				batch: store.batch,
				statuses: store.files.map((e) => [e.fileId, e.status]),
			})
		}
		setActivePinia(createPinia())
		const store = useAnonymizationStore()
		await store.anonymiseAllExtracted()
		out.push({ none: store.batch })
		golden('anonymiseAllExtracted', out)
	})
})

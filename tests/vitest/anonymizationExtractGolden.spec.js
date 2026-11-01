// @vitest-environment jsdom
/**
 * Golden: how the anonymisation store turns an extract response into review
 * rows, through upload, open, re-analyse and re-anonymise, recorded on the
 * implementation where each action applied the response inline.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing extract handling
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, it, vi } from 'vitest'
import { useAnonymizationStore } from '../../src/store/modules/anonymization.js'
import { golden } from './golden/golden.js'

const calls = []
let extractAnswer = null

vi.mock('@nextcloud/axios', () => {
	const axios = vi.fn(async (config) => {
		calls.push([config.method, config.url, config.headers ?? null])
		return { data: {} }
	})
	axios.post = vi.fn(async (url, body) => {
		calls.push(['POST', url, body instanceof FormData ? 'form' : (body ?? null)])
		if (url.endsWith('/upload')) {
			return { data: { fileId: 77, filePath: '/DocuDesk/up.pdf' } }
		}
		if (extractAnswer instanceof Error) {
			throw extractAnswer
		}
		return { data: extractAnswer }
	})
	return { default: axios }
})

const ENTITIES = [
	{ type: 'PERSON', value: 'Jan', relationId: 1, confidence: 0.4 },
	{
		type: 'PERSON',
		value: 'Jan',
		relationId: 2,
		confidence: 0.9,
		bases: ['5.1.2e'],
	},
	{ type: 'PERSON', value: 'Jan', relationId: 2, confidence: 0.1, bases: ['x'] },
	{ type: 'PERSON', value: 'Jan', confidence: undefined, skipAnonymization: true },
	{
		type: 'EMAIL',
		value: 'a@b.nl',
		relationId: 3,
		bases: [],
		skipAnonymization: true,
	},
	{ type: 'EMAIL', value: 'a@b.nl', relationId: 4, bases: ['5.1.1d'] },
	{ value: 'no type' },
	{ type: 'LOC' },
	{ type: 'LOC', value: '' },
	{
		type: 'IBAN',
		value: 'NL00',
		relationId: 0,
		bases: 'loose',
		prohibitionMatch: { ruleId: 9, ruleName: 'r', highConfidence: true },
		extra: 'kept',
	},
	{ type: 'IBAN', value: 'NL00', relationId: 0 },
]

const ANSWERS = [
	{ entities: ENTITIES, confidentialityLabel: 'Secret', confidentialityLevel: 3 },
	{ entities: [] },
	{ entities: [], ocrDetectionPending: true },
	{ ocrSkipped: 'ocr_disabled' },
	{ entities: [{ type: 'PERSON', value: 'Piet' }], confidentialityLevel: 0 },
	{ entities: null },
]

/**
 * The observable fields of a queue entry.
 *
 * @param {object} entry The entry.
 * @return {object}
 */
function view(entry) {
	const { id, _file, ...rest } = entry
	return { ...rest, hasFile: _file !== undefined }
}

/**
 * Run one action for every answer and record the entries.
 *
 * @param {Function} run Runs the action on a fresh store for one answer.
 * @return {Promise<object[]>}
 */
async function overAnswers(run) {
	const out = []
	for (const answer of [
		...ANSWERS,
		Object.assign(new Error('down'), {
			response: { data: { error: 'extract failed' } },
		}),
		new Error('plain'),
	]) {
		setActivePinia(createPinia())
		calls.length = 0
		extractAnswer = answer
		const entry = await run(useAnonymizationStore())
		out.push({ entry: entry ? view(entry) : null, calls: [...calls] })
	}
	return out
}

beforeEach(() => {
	vi.spyOn(console, 'error').mockImplementation(() => {})
})

describe('anonymisation extract golden', () => {
	it('uploads and extracts', async () => {
		golden(
			'anonymizationUploadAndExtract',
			await overAnswers(async (store) => {
				const entry = {
					name: 'up.pdf',
					status: 'queued',
					dossier: 'D 1',
					_file: new Blob(['x']),
					entities: [],
				}
				await store.uploadAndExtract(entry)
				const plain = {
					name: 'up.pdf',
					status: 'queued',
					dossier: null,
					_file: new Blob(['x']),
				}
				await store.uploadAndExtract(plain)
				return { ...entry, plain: view(plain) }
			}),
		)
	})

	it('extracts a file opened from the listing', async () => {
		golden(
			'anonymizationEnsureExtracted',
			await overAnswers(async (store) => {
				const meta = {
					fileId: 5,
					fileName: 'f.pdf',
					path: '/DocuDesk/Dos/f.pdf',
				}
				const entry = await store.ensureExtracted(meta)
				const again = await store.ensureExtracted(meta)
				return {
					...entry,
					same: entry === again,
					queued: store.files.length,
				}
			}),
		)
	})

	it('re-analyses a file', async () => {
		golden(
			'anonymizationReanalyse',
			await overAnswers(async (store) => {
				store.files.push({
					id: 'x',
					name: 'r.pdf',
					fileId: 8,
					status: 'completed',
					entities: [],
				})
				const missing = await store.reanalyseEntry(9)
				const entry = await store.reanalyseEntry(8)
				return { ...entry, missing }
			}),
		)
	})

	it('prepares a re-anonymisation, keeping earlier decisions', async () => {
		golden(
			'anonymizationPrepareReanonymize',
			await overAnswers(async (store) => {
				const entry = {
					name: 'p.pdf',
					fileId: 3,
					status: 'completed',
					viewMode: 'anonymized',
					detailUnavailable: true,
					reviewMark: { by: 'x' },
					entities: [
						{
							type: 'PERSON',
							value: 'Jan',
							_decisionBases: ['kept'],
							_decisionSkip: 1,
						},
						{
							type: 'EMAIL',
							value: 'a@b.nl',
							_decisionBases: 'no',
							_decisionSkip: 0,
						},
						{ type: 'PERSON', value: 'Piet' },
					],
				}
				const none = await store.prepareReanonymize({ fileId: 0 })
				await store.prepareReanonymize(entry)
				return { ...entry, none }
			}),
		)
	})
})

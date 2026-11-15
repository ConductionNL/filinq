/**
 * Golden: the requests and state the correspondence store's generate and
 * generateBatch actions produce, recorded on the implementation where each
 * action built its options and read its error inline.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing correspondence generation
 */

import { createPinia, setActivePinia } from 'pinia'
import { describe, it, vi } from 'vitest'
import { useCorrespondenceStore } from '../../src/store/modules/correspondence.js'
import { golden } from './golden/golden.js'

const calls = []
let answer = null

vi.mock('@nextcloud/axios', () => {
	const axios = vi.fn(async () => ({ data: {} }))
	axios.post = vi.fn(async (url, body, config) => {
		calls.push(['POST', url, body, config ?? null])
		if (answer instanceof Error) {
			throw answer
		}
		return { data: answer }
	})
	axios.get = vi.fn(async (url) => {
		calls.push(['GET', url])
		if (answer instanceof Error) {
			throw answer
		}
		return { data: answer }
	})
	return { default: axios }
})

const STATES = [
	{ format: 'pdf', huisstijlId: 'h1', caseReference: 'Z-1' },
	{ format: 'docx', huisstijlId: null, caseReference: '' },
	{ format: 'html', huisstijlId: '', caseReference: 'Z-2' },
	{ format: 'txt' },
]

const ANSWERS = [
	{ warnings: ['w1'], jobId: 'j1', totalRecipients: 3 },
	{ status: 'done' },
	{ jobId: '', warnings: null },
	Object.assign(new Error('down'), { response: { data: { error: 'bad' } } }),
	new Error('plain'),
]

/**
 * Run one action over every state and answer and record what it did.
 *
 * @param {Function} run Runs the action on a prepared store.
 * @return {Promise<object[]>}
 */
async function overCases(run) {
	const out = []
	for (const state of STATES) {
		for (const a of ANSWERS) {
			setActivePinia(createPinia())
			calls.length = 0
			answer = a
			const store = useCorrespondenceStore()
			store.$patch({
				templateId: 't1',
				dataRefs: [{ register: 'r', schema: 's', id: '1' }],
				recipientIdsText: 'a\n b \n\n',
				warnings: ['old'],
				jobId: 'old',
				jobStatus: { old: true },
				...state,
			})
			const downloads = []
			store._triggerDownload = (...args) => downloads.push(args)
			await run(store)
			out.push({
				state,
				answer: a instanceof Error ? a.message : a,
				calls: [...calls],
				downloads,
				after: {
					loading: store.loading,
					error: store.error,
					warnings: store.warnings,
					jobId: store.jobId,
					jobStatus: store.jobStatus,
				},
			})
		}
	}
	return out
}

describe('correspondence generate golden', () => {
	it('generates one document', async () => {
		golden(
			'correspondenceGenerate',
			await overCases(async (store) => {
				await store.generate()
				await store.generate('eigen.docx')
			}),
		)
	})

	it('generates a batch', async () => {
		golden(
			'correspondenceGenerateBatch',
			await overCases((store) => store.generateBatch('reg', 'sch')),
		)
	})

	it('polls a batch job', async () => {
		golden(
			'correspondencePollJobStatus',
			await overCases(async (store) => {
				await store.pollJobStatus()
				store.jobId = 'a/b c'
				await store.pollJobStatus()
				store.jobId = ''
				await store.pollJobStatus()
			}),
		)
	})
})

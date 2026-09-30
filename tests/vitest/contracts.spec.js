/**
 * The contract pages' calls: CRUD on OpenRegister's object API, the actions
 * on the app's routes, and what a failure shows.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	activateContract,
	attachDocuments,
	decideSuggestion,
	generateDocument,
	getContract,
	linkSigningRequest,
	listContracts,
	renewContract,
	terminateContract,
} from '../../src/services/contracts.js'

const calls = []
let answer = () => Promise.resolve({ data: {} })
const record = (verb) => (url, body) => {
	calls.push([verb, url, body])
	return answer(verb, url, body)
}
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url, config) => {
			calls.push(['get', url, config?.params])
			return answer('get', url)
		},
		post: (...args) => record('post')(...args),
		put: (...args) => record('put')(...args),
	},
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))

const objects = '/apps/openregister/api/objects/filinq/documentContract'

describe('contract service', () => {
	beforeEach(() => {
		calls.length = 0
		answer = () => Promise.resolve({ data: {} })
	})

	it('lists contracts from the object API', async () => {
		answer = () =>
			Promise.resolve({ data: { results: [{ id: 'c-1', title: 'A' }] } })
		const outcome = await listContracts()
		expect(outcome).toEqual({ ok: true, data: [{ id: 'c-1', title: 'A' }] })
		expect(calls[0][1]).toBe(objects)
	})

	it('reads one contract', async () => {
		answer = () => Promise.resolve({ data: { id: 'c-1', title: 'A' } })
		const outcome = await getContract('c 1')
		expect(calls[0][1]).toBe(`${objects}/c%201`)
		expect(outcome.data.title).toBe('A')
	})

	it('activates by saving the whole contract with the new status', async () => {
		const contract = {
			id: 'c-1',
			'@self': { id: 'c-1' },
			title: 'A',
			value: 10,
			status: 'draft',
		}
		await activateContract(contract)
		expect(calls[0][0]).toBe('put')
		expect(calls[0][1]).toBe(`${objects}/c-1`)
		expect(calls[0][2]).toEqual({ title: 'A', value: 10, status: 'active' })
	})

	it('attaches files once each, keeping the documents already there', async () => {
		await attachDocuments({ id: 'c-1', title: 'A', documents: ['4'] }, [
			4,
			'7',
			'7',
		])
		expect(calls[0][2].documents).toEqual(['4', '7'])
		expect(calls[0][2].title).toBe('A')
	})

	it('calls the action routes', async () => {
		await renewContract('c-1')
		await terminateContract('c-1', 'Opgezegd')
		await decideSuggestion('c-1', 2, 'accepted')
		await linkSigningRequest('c-1', 'req-9')
		expect(calls.map((c) => [c[0], c[1], c[2]])).toEqual([
			['post', '/apps/filinq/api/contracts/c-1/renew', undefined],
			[
				'post',
				'/apps/filinq/api/contracts/c-1/terminate',
				{ reason: 'Opgezegd' },
			],
			[
				'put',
				'/apps/filinq/api/contracts/c-1/suggestions/2',
				{ decision: 'accepted' },
			],
			[
				'post',
				'/apps/filinq/api/contracts/c-1/signing',
				{ signingRequestId: 'req-9' },
			],
		])
	})

	it('generates into a file and answers its id', async () => {
		answer = () => Promise.resolve({ data: { fileId: 88, name: 'A.pdf' } })
		const outcome = await generateDocument('tpl-1', 'c-1', 'A')
		expect(calls[0][1]).toBe('/apps/filinq/api/documents/generate')
		expect(calls[0][2]).toEqual({
			templateId: 'tpl-1',
			dataRefs: [
				{ register: 'filinq', schema: 'documentContract', id: 'c-1' },
			],
			filename: 'A',
			options: { format: 'pdf', output: { mode: 'files' } },
		})
		expect(outcome.data.fileId).toBe('88')
	})

	it('shows the server refusal, or a plain sentence', async () => {
		answer = () =>
			Promise.reject({
				response: {
					status: 400,
					data: { error: 'A termination needs a reason.' },
				},
			})
		expect(await terminateContract('c-1', '')).toEqual({
			ok: false,
			status: 400,
			error: 'Give a reason for ending the contract.',
		})
		answer = () => Promise.reject({ response: { status: 500, data: {} } })
		const outcome = await renewContract('c-1')
		expect(outcome.ok).toBe(false)
		expect(outcome.error).toBe(
			'The contract could not be changed. Try again later.',
		)
	})
})

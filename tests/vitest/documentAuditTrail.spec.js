/**
 * The document audit trail service: the call it makes, how a refusal comes
 * back, and the label of each recorded action and actor.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.3
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	actionLabel,
	actorLabel,
	fetchDocumentTrail,
} from '../../src/services/documentAuditTrail.js'

const calls = []
let answer = () => Promise.resolve({ data: {} })
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url) => {
			calls.push(url)
			return answer()
		},
	},
}))
vi.mock('@nextcloud/router', () => ({
	generateUrl: (path, params = {}) =>
		path.replace(/\{(\w+)\}/g, (match, key) => String(params[key])),
}))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))

describe('document audit trail service', () => {
	beforeEach(() => {
		calls.length = 0
		answer = () => Promise.resolve({ data: {} })
	})

	it('reads the trail of the open file and keeps the server order', async () => {
		const results = [
			{ action: 'create', user: 'ann', userName: 'Ann', created: '2026-11-01T09:00:00+00:00' },
			{ action: 'filinq.document.signed', user: 'bob', userName: '', created: '2026-11-02T09:00:00+00:00' },
		]
		answer = () => Promise.resolve({ data: { results, total: 2 } })

		const outcome = await fetchDocumentTrail(42)

		expect(calls).toEqual(['/apps/filinq/api/documents/42/audit'])
		expect(outcome).toEqual({ ok: true, entries: results })
	})

	it('asks nothing for a missing file id', async () => {
		expect(await fetchDocumentTrail(null)).toEqual({ ok: true, entries: [] })
		expect(await fetchDocumentTrail('x')).toEqual({ ok: true, entries: [] })
		expect(calls).toEqual([])
	})

	it('reports a refused read as a failure, not as an empty trail', async () => {
		answer = () => Promise.reject({ response: { status: 404 } })

		expect(await fetchDocumentTrail(7)).toEqual({ ok: false, entries: [], status: 404 })
	})

	it('labels filinq actions and the record entries of OpenRegister', () => {
		expect(actionLabel('create')).toBe('Registered')
		expect(actionLabel('update')).toBe('Changed')
		expect(actionLabel('filinq.document.downloaded')).toBe('Downloaded')
		expect(actionLabel('filinq.document.versioned')).toBe('New version')
		expect(actionLabel('filinq.document.anonymised')).toBe('Anonymised')
		expect(actionLabel('filinq.document.copied_to')).toBe('Copied to another record')
		expect(actionLabel('something.else')).toBe('something.else')
	})

	it('names the actor by display name, then user id, then the system', () => {
		expect(actorLabel({ user: 'ann', userName: 'Ann de Vries' })).toBe('Ann de Vries')
		expect(actorLabel({ user: 'ann', userName: '' })).toBe('ann')
		expect(actorLabel({ user: '', userName: '' })).toBe('System')
	})
})

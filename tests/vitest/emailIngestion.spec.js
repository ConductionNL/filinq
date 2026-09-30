/**
 * The email ingestion page's calls, states and failure texts.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/email-ingestion/tasks.md#3-1
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	failureText,
	inThread,
	listEmails,
	rescan,
	retryConversion,
	rowState,
	saveSettings,
	stateLabel,
} from '../../src/services/emailIngestion.js'

const calls = []
let answer = () => Promise.resolve({ data: {} })
const record = (method) => (url, body) => {
	calls.push([method, url, body])
	return answer()
}
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url, body) => record('get')(url, body),
		post: (url, body) => record('post')(url, body),
		put: (url, body) => record('put')(url, body),
	},
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))

describe('email ingestion service', () => {
	beforeEach(() => {
		calls.length = 0
		answer = () => Promise.resolve({ data: {} })
	})

	it('lists with only the filters that are set, scans and retries', async () => {
		await listEmails({ status: 'failed', dossier: '' })
		await rescan()
		await retryConversion('a/b')
		expect(calls).toEqual([
			[
				'get',
				'/apps/filinq/api/email-ingestion',
				{ params: { status: 'failed' } },
			],
			['post', '/apps/filinq/api/email-ingestion/scan', undefined],
			['post', '/apps/filinq/api/email-ingestion/a%2Fb/convert', undefined],
		])
	})

	it('sends numeric folder ids and trimmed dossiers, and reports a refusal', async () => {
		answer = () =>
			Promise.reject({ response: { status: 400, data: { error: 'x' } } })
		const result = await saveSettings(
			[{ folderId: '12', dossierRef: ' dossier-1 ' }],
			'30',
		)
		expect(calls[0][2]).toEqual({
			inboxes: [{ folderId: 12, dossierRef: 'dossier-1' }],
			filesPerTick: 30,
		})
		expect(result).toEqual({ ok: false, status: 400, error: 'x' })
	})

	it('shows a filed email without its PDF as not converted', () => {
		expect(rowState({ status: 'filed', pdfFileRef: '9' })).toBe('filed')
		expect(rowState({ status: 'filed' })).toBe('not-converted')
		expect(rowState({ status: 'failed' })).toBe('failed')
		expect(stateLabel('not-converted')).toBe('Filed, not converted')
	})

	it('explains a .msg failure with what to do', () => {
		expect(failureText('unsupported-format')).toBe(
			'Outlook .msg files cannot be read. Save the email as .eml and put it in the inbox again.',
		)
		expect(failureText('')).toBe('Unknown reason')
	})

	it('marks emails that share a thread', () => {
		const a = { threadKey: 'root@x' }
		const b = { threadKey: 'root@x' }
		const c = { threadKey: 'other@x' }
		expect(inThread(a, [a, b, c])).toBe(true)
		expect(inThread(c, [a, b, c])).toBe(false)
		expect(inThread({}, [a])).toBe(false)
	})
})

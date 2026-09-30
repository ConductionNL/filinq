/**
 * Bulk send service: the calls, the report sentences, and when a batch is done.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	cancelBatch,
	confirmBatch,
	errorMessage,
	isFinished,
	reasonLabel,
	statusLabel,
	uploadList,
} from '../../src/services/bulkSend.js'

const calls = []
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url) => {
			calls.push(['get', url])
			return Promise.resolve({ data: {} })
		},
		post: (url, body) => {
			calls.push(['post', url, body])
			return Promise.resolve({ data: { uuid: 'b1', status: 'ready' } })
		},
	},
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars = {}) =>
		text.replace(/{(\w+)}/g, (m, k) => (k in vars ? vars[k] : m)),
}))

describe('bulk send service', () => {
	beforeEach(() => {
		calls.length = 0
	})

	it('uploads the list with only the settings that are set', async () => {
		const file = new Blob(['email\n'], { type: 'text/csv' })
		const batch = await uploadList(file, {
			documentFileId: '7',
			documentName: 'a.pdf',
			title: '',
			signatureLevel: 'SES',
		})
		expect(batch.status).toBe('ready')
		const [verb, url, body] = calls[0]
		expect([verb, url]).toEqual(['post', '/apps/filinq/api/signing/batches'])
		expect([...body.keys()]).toEqual([
			'file',
			'documentFileId',
			'documentName',
			'signatureLevel',
		])
	})

	it('confirms and cancels by id', async () => {
		await confirmBatch('b1')
		await cancelBatch('b1')
		expect(calls.map((c) => c[1])).toEqual([
			'/apps/filinq/api/signing/batches/b1/confirm',
			'/apps/filinq/api/signing/batches/b1/cancel',
		])
	})

	it('says why each row was rejected', () => {
		expect(reasonLabel({ row: 4, reason: 'invalid-email' })).toBe(
			'Not a valid e-mail address',
		)
		expect(reasonLabel({ row: 5, reason: 'duplicate', detail: 'row 3' })).toBe(
			'Same recipient as row 3',
		)
		expect(
			reasonLabel({ row: 2, reason: 'unknown-user', detail: 'ghost' }),
		).toBe('No user ghost on this Nextcloud')
		expect(
			reasonLabel({ row: 9, reason: 'creation-failed', detail: 'gone' }),
		).toBe('The request could not be made: gone')
	})

	it('knows when a batch is finished', () => {
		expect(isFinished({ status: 'creating' })).toBe(false)
		expect(isFinished({ status: 'ready' })).toBe(false)
		expect(isFinished({ status: 'completed_with_errors' })).toBe(true)
		expect(isFinished({ status: 'cancelled' })).toBe(true)
		expect(statusLabel('completed_with_errors')).toBe('Sent, some rows failed')
	})

	it('shows the server reason, or a fallback', () => {
		expect(
			errorMessage({
				response: { data: { error: 'The recipient list is empty' } },
			}),
		).toBe('The recipient list is empty')
		expect(errorMessage(new Error('x'))).toBe(
			'The bulk send could not be reached. Try again.',
		)
	})
})

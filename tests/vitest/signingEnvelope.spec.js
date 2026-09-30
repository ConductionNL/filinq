/**
 * Signing envelope service: the calls, which documents are sent, who may
 * sign all or cancel, and what a sign-all answer says.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	canCancel,
	cancelEnvelope,
	canSignAll,
	createEnvelope,
	envelopeError,
	envelopeStatusLabel,
	signAllInEnvelope,
	summariseSignAll,
	toDocuments,
} from '../../src/services/signingEnvelope.js'

const calls = []
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url) => {
			calls.push(['get', url])
			return Promise.resolve({ data: {} })
		},
		post: (url, body) => {
			calls.push(['post', url, body])
			return Promise.resolve({ data: { uuid: 'e1', status: 'pending' } })
		},
	},
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars = {}) =>
		text.replace(/{(\w+)}/g, (m, k) => (k in vars ? vars[k] : m)),
}))

function envelope(overrides = {}) {
	return {
		status: 'pending',
		initiatorUserId: 'alice',
		signerUserIds: ['bob', 'carol'],
		members: [
			{ id: 'r1', status: 'PENDING' },
			{ id: 'r2', status: 'COMPLETED' },
		],
		...overrides,
	}
}

describe('signing envelope service', () => {
	beforeEach(() => {
		calls.length = 0
	})

	it('posts to the envelope endpoints', async () => {
		await createEnvelope({ documents: [] })
		await signAllInEnvelope('e1')
		await cancelEnvelope('e1')
		expect(calls.map(([verb, url]) => [verb, url])).toEqual([
			['post', '/apps/filinq/api/signing/envelopes'],
			['post', '/apps/filinq/api/signing/envelopes/e1/sign'],
			['post', '/apps/filinq/api/signing/envelopes/e1/cancel'],
		])
	})

	it('sends each document with a file id once, named by its id when unnamed', () => {
		expect(
			toDocuments([
				{ documentFileId: ' 11 ', documentName: 'a.pdf' },
				{ documentFileId: '', documentName: 'empty' },
				{ documentFileId: '11', documentName: 'again' },
				{ documentFileId: 12, documentName: '' },
			]),
		).toEqual([
			{ documentFileId: '11', documentName: 'a.pdf' },
			{ documentFileId: '12', documentName: '12' },
		])
	})

	it('offers sign all only to a named signer while a document is open', () => {
		expect(canSignAll(envelope(), 'bob')).toBe(true)
		expect(canSignAll(envelope(), 'alice')).toBe(false)
		expect(canSignAll(envelope({ status: 'cancelled' }), 'bob')).toBe(false)
		expect(
			canSignAll(
				envelope({ members: [{ id: 'r1', status: 'DECLINED' }] }),
				'bob',
			),
		).toBe(false)
		expect(canSignAll(null, 'bob')).toBe(false)
	})

	it('offers cancel only to the sender of an open envelope', () => {
		expect(canCancel(envelope(), 'alice')).toBe(true)
		expect(canCancel(envelope(), 'bob')).toBe(false)
		expect(canCancel(envelope({ status: 'partially_declined' }), 'alice')).toBe(
			false,
		)
	})

	it('counts what was signed and lists what was refused', () => {
		expect(
			summariseSignAll({
				r1: { success: true },
				r2: { success: false, error: 'Identity check needed' },
			}),
		).toEqual({ signed: 1, refused: ['Identity check needed'] })
	})

	it('names every status and passes the server error through', () => {
		expect(envelopeStatusLabel('partially_declined')).toBe('Partly declined')
		expect(envelopeStatusLabel('incomplete')).toBe('Ended incomplete')
		expect(
			envelopeError({ response: { data: { error: 'Document 2: no' } } }),
		).toBe('Document 2: no')
		expect(envelopeError(new Error('x'))).toBe(
			'The envelope could not be reached. Try again.',
		)
	})
})

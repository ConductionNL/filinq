/**
 * The classification list and card: the calls, the words, and the body of a
 * confirmation carrying only what the person changed.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#4-1
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	confidenceText,
	confirm,
	confirmAll,
	confirmBody,
	correspondentText,
	dossierOptions,
	listPending,
	loadForFile,
	reject,
	statusLabel,
	typeLabel,
	typeOptions,
	TYPES,
} from '../../src/services/classification.js'

const calls = []
let answer = () => Promise.resolve({ data: {} })
function record(method) {
	return (url, body) => {
		calls.push([method, url, body])
		return answer(url)
	}
}
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url, body) => record('get')(url, body),
		post: (url, body) => record('post')(url, body),
	},
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))

const suggestion = {
	fileId: 812010,
	suggestedDocumentType: 'factuur',
	documentTypeConfidence: 0.823,
	suggestedCorrespondent: {
		name: 'Heijmans B.V.',
		entityType: 'ORGANIZATION',
		source: 'ner',
	},
	suggestedDossier: 'dossier-1',
	status: 'suggested',
}

describe('classification service', () => {
	beforeEach(() => {
		calls.length = 0
		answer = () => Promise.resolve({ data: {} })
	})

	it('labels every type and offers them all', () => {
		expect(TYPES).toHaveLength(7)
		expect(typeLabel('factuur')).toBe('Invoice')
		expect(typeLabel('besluit')).toBe('Decision')
		expect(typeLabel('memo')).toBe('memo')
		expect(typeOptions().map((o) => o.id)).toEqual(TYPES)
	})

	it('shows the confidence as a bounded percentage', () => {
		expect(confidenceText(0.823)).toBe('82%')
		expect(confidenceText(1.4)).toBe('100%')
		expect(confidenceText('x')).toBe('')
	})

	it('says why there is no sender rather than inventing one', () => {
		expect(correspondentText(suggestion)).toBe('Heijmans B.V.')
		expect(correspondentText({ correspondentPending: true })).toBe(
			'Sender not known yet',
		)
		expect(correspondentText({})).toBe('No sender found')
	})

	it('labels the statuses', () => {
		expect(statusLabel('suggested')).toBe('Waiting for you')
		expect(statusLabel('confirmed')).toBe('Confirmed')
		expect(statusLabel('rejected')).toBe('Rejected')
	})

	it('sends only what the person changed', () => {
		expect(confirmBody(suggestion, {})).toEqual({})
		expect(
			confirmBody(suggestion, {
				documentType: 'factuur',
				correspondentName: ' Heijmans B.V. ',
				dossier: 'dossier-1',
			}),
		).toEqual({})
		expect(
			confirmBody(suggestion, {
				documentType: 'besluit',
				correspondentName: 'Gemeente Tilburg',
				dossier: '',
			}),
		).toEqual({
			documentType: 'besluit',
			correspondent: { name: 'Gemeente Tilburg', entityType: 'ORGANIZATION' },
			dossier: null,
		})
		expect(
			confirmBody(
				{ ...suggestion, suggestedCorrespondent: null },
				{ correspondentName: 'J. de Vries' },
			),
		).toEqual({
			correspondent: { name: 'J. de Vries', entityType: 'PERSON' },
		})
		expect(confirmBody(suggestion, { correspondentName: '  ' })).toEqual({
			correspondent: null,
		})
	})

	it('calls the classification endpoints', async () => {
		await listPending()
		await loadForFile('812010')
		await confirm(812010, { documentType: 'besluit' })
		await reject(812010)
		expect(calls).toEqual([
			['get', '/apps/filinq/api/classification/pending', undefined],
			['get', '/apps/filinq/api/classification/812010', undefined],
			[
				'post',
				'/apps/filinq/api/classification/812010/confirm',
				{ documentType: 'besluit' },
			],
			['post', '/apps/filinq/api/classification/812010/reject', undefined],
		])
	})

	it('answers a refusal with its status', async () => {
		answer = () =>
			Promise.reject({
				response: {
					status: 409,
					data: { error: 'This suggestion was already decided' },
				},
			})
		expect(await confirm(1)).toEqual({
			ok: false,
			status: 409,
			error: 'This suggestion was already decided',
		})
	})

	it('confirms visible rows one by one and counts the failures', async () => {
		answer = (url) =>
			url.includes('/2/')
				? Promise.reject({ response: { status: 409 } })
				: Promise.resolve({ data: {} })
		expect(
			await confirmAll([{ fileId: 1 }, { fileId: 2 }, { fileId: 3 }]),
		).toEqual({ confirmed: 2, failed: 1 })
		expect(calls.map((c) => c[1])).toEqual([
			'/apps/filinq/api/classification/1/confirm',
			'/apps/filinq/api/classification/2/confirm',
			'/apps/filinq/api/classification/3/confirm',
		])
	})

	it('reads the dossiers as picker options, or none', async () => {
		answer = () =>
			Promise.resolve({
				data: {
					results: [
						{ id: 'd1', name: 'Heijmans' },
						{ id: 'd2', name: '' },
					],
				},
			})
		expect(await dossierOptions()).toEqual([
			{ id: 'd1', label: 'Heijmans' },
			{ id: 'd2', label: 'd2' },
		])
		answer = () => Promise.reject({ response: { status: 500 } })
		expect(await dossierOptions()).toEqual([])
	})
})

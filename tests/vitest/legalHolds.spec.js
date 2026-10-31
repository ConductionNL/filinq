/**
 * The hold register's service: the calls, the refusal it shows, and the
 * labels per record. And the release dialog's mandatory reason.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */

import { readFileSync } from 'node:fs'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	fileLabel,
	holdStatus,
	listCases,
	needsRetry,
	parseRefs,
	recordLabel,
	releaseCase,
} from '../../src/services/legalHolds.js'

const calls = []
let answer = () => Promise.resolve({ data: {} })
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url, config) => {
			calls.push(['get', url, config?.params])
			return answer()
		},
		post: (url, body) => {
			calls.push(['post', url, body])
			return answer()
		},
	},
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))

describe('legal hold service', () => {
	beforeEach(() => {
		calls.length = 0
		answer = () => Promise.resolve({ data: { results: [] } })
	})

	it('sends only the filters that are set', async () => {
		await listCases({ status: 'active', holdType: '', custodian: 'carol' })
		expect(calls).toEqual([
			[
				'get',
				'/apps/filinq/api/legal-holds',
				{ status: 'active', custodian: 'carol' },
			],
		])
	})

	it('shows the server refusal on a release without a reason', async () => {
		answer = () =>
			Promise.reject({
				response: {
					status: 400,
					data: { error: 'A release needs a reason.' },
				},
			})
		const outcome = await releaseCase('case/1', '')
		expect(calls[0]).toEqual([
			'post',
			'/apps/filinq/api/legal-holds/case%2F1/release',
			{ releaseReason: '' },
		])
		expect(outcome).toEqual({
			ok: false,
			status: 400,
			error: 'A release needs a reason.',
		})
	})

	it('answers null for a record the caller cannot read, and asks nothing without an id', async () => {
		expect(await holdStatus('')).toBe(null)
		expect(calls).toEqual([])
		answer = () => Promise.reject({ response: { status: 404, data: {} } })
		expect(await holdStatus('doc-1')).toBe(null)
		answer = () => Promise.resolve({ data: { held: true, cases: [] } })
		expect(await holdStatus('doc-1')).toEqual({ held: true, cases: [] })
	})

	it('splits pasted ids on commas, spaces and new lines, each once', () => {
		expect(parseRefs('a, b\nc  a;d\n\n')).toEqual(['a', 'b', 'c', 'd'])
		expect(parseRefs(undefined)).toEqual([])
	})

	it('names what happened per record and offers a retry only after a failure', () => {
		expect(recordLabel({ record: 'kept_for_other_case' })).toBe(
			'Still frozen for another hold',
		)
		expect(fileLabel({ file: 'unavailable' })).toBe(
			'Files not locked: file locking is not installed',
		)
		expect(needsRetry({ fanOut: [{ record: 'held', file: 'locked' }] })).toBe(
			false,
		)
		expect(needsRetry({ fanOut: [{ record: 'held', file: 'failed' }] })).toBe(
			true,
		)
	})
})

describe('the hold register surfaces', () => {
	const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

	it('keeps Release off until a reason is typed', () => {
		const dialog = read('../../src/dialogs/ReleaseLegalHoldDialog.vue')
		expect(dialog).toMatch(/:disabled="busy \|\| reason\.trim\(\) === ''"/)
	})

	it('switches off removal on a held dossier', () => {
		const dossier = read('../../src/views/dossier/DossierDetail.vue')
		expect(dossier).toContain('@held="underLegalHold = $event"')
		expect(dossier).toContain(':disabled="underLegalHold"')
	})

	it('ships no review, tagging or export surface', () => {
		const page = read('../../src/views/legalHolds/LegalHolds.vue')
		const template = page.slice(0, page.indexOf('<script>'))
		expect(template).not.toMatch(/export|tagging|review/i)
	})
})

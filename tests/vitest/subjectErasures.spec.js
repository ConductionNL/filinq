/**
 * The erasure page's service: the calls, the refusal it shows, and the
 * exclusions the preview choices make. And that the page never offers the run
 * before a preview state, nor an exclusion without a reason.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */

import { readFileSync } from 'node:fs'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	buildExclusions,
	exclusionsComplete,
	previewRequest,
	runRequest,
	saveExclusions,
} from '../../src/services/subjectErasures.js'

const calls = []
let answer = () => Promise.resolve({ data: {} })
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url) => {
			calls.push(['get', url])
			return answer()
		},
		post: (url, body) => {
			calls.push(['post', url, body])
			return answer()
		},
		put: (url, body) => {
			calls.push(['put', url, body])
			return answer()
		},
	},
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))

const rows = [
	{ document: '11', values: ['Jan Jansen', 'jan@voorbeeld.example'] },
	{ document: '12', values: ['Jan Jansen'] },
]

describe('subject erasure service', () => {
	beforeEach(() => {
		calls.length = 0
		answer = () => Promise.resolve({ data: {} })
	})

	it('builds no exclusion when everything is erased', () => {
		expect(buildExclusions(rows, {}, {})).toEqual([])
	})

	it('leaves one identifier in one document, with the row reason', () => {
		const exclusions = buildExclusions(
			rows,
			{ 11: { 'jan@voorbeeld.example': false } },
			{ 11: 'Gedeeld adres' },
		)
		expect(exclusions).toEqual([
			{ occurrence: '11:jan@voorbeeld.example', reason: 'Gedeeld adres' },
		])
	})

	it('leaves a whole document when every identifier in it is unticked', () => {
		const exclusions = buildExclusions(
			rows,
			{ 12: { 'Jan Jansen': false } },
			{ 12: 'Andere Jan Jansen' },
		)
		expect(exclusions).toEqual([
			{ occurrence: '12', reason: 'Andere Jan Jansen' },
		])
	})

	it('is not complete while an exclusion has no reason', () => {
		const exclusions = buildExclusions(rows, { 12: { 'Jan Jansen': false } }, {})
		expect(exclusionsComplete(exclusions)).toBe(false)
		expect(exclusionsComplete([])).toBe(true)
	})

	it('calls the preview, exclusions and run routes of the request', async () => {
		await previewRequest('req-1')
		await saveExclusions('req-1', [{ occurrence: '12', reason: 'x' }])
		await runRequest('req-1')
		expect(calls).toEqual([
			['post', '/apps/filinq/api/subject-erasures/req-1/preview', undefined],
			[
				'put',
				'/apps/filinq/api/subject-erasures/req-1/exclusions',
				{ exclusions: [{ occurrence: '12', reason: 'x' }] },
			],
			['post', '/apps/filinq/api/subject-erasures/req-1/run', undefined],
		])
	})

	it('shows the server refusal', async () => {
		answer = () =>
			Promise.reject({
				response: {
					status: 409,
					data: { error: 'Build the preview first' },
				},
			})
		const outcome = await runRequest('req-1')
		expect(outcome).toEqual({
			ok: false,
			status: 409,
			error: 'Build the preview first',
		})
	})

	it('offers the run only from previewed or partially completed, and the save only with every reason', () => {
		const page = readFileSync(
			new URL(
				'../../src/views/subjectErasures/SubjectErasures.vue',
				import.meta.url,
			),
			'utf8',
		)
		expect(page).toContain(
			"return ['previewed', 'partially_completed'].includes(this.selected?.status)",
		)
		expect(page).toContain(':disabled="!exclusionsReady"')
		expect(page).not.toMatch(/<NcDialog|<NcModal/)
	})
})

/**
 * The Sanitize action's calls and the report rows.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/document-sanitization/tasks.md#4-1
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	reportRows,
	sanitizationStatus,
	sanitizeFile,
} from '../../src/services/sanitization.js'

const calls = []
let answer = () => Promise.resolve({ data: {} })
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url) => {
			calls.push(['get', url])
			return answer()
		},
		post: (url) => {
			calls.push(['post', url])
			return answer()
		},
	},
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))

describe('sanitization service', () => {
	beforeEach(() => {
		calls.length = 0
		answer = () => Promise.resolve({ data: {} })
	})

	it('posts to the file and reads its status', async () => {
		await sanitizeFile(41)
		await sanitizationStatus(41)
		expect(calls).toEqual([
			['post', '/apps/filinq/api/sanitization/41'],
			['get', '/apps/filinq/api/sanitization/41'],
		])
	})

	it('names an encrypted document and a file nobody can open', async () => {
		answer = () =>
			Promise.reject({ response: { status: 422, data: { error: 'encrypted' } } })
		expect((await sanitizeFile(1)).error).toBe(
			'This document is encrypted. Remove the password and try again.',
		)
		answer = () =>
			Promise.reject({ response: { status: 404, data: { error: 'not_found' } } })
		expect((await sanitizeFile(1)).error).toBe(
			'This file is not there, or you cannot open it.',
		)
	})

	it('shows counts per category and leaves out what was not found', () => {
		expect(
			reportRows({
				commentsRemoved: 4,
				metadataFieldsScrubbed: 6,
				trackedChangesDropped: 0,
				sentinelApplied: 'x',
			}),
		).toEqual([
			{ key: 'commentsRemoved', label: 'Comments removed', count: 4 },
			{ key: 'metadataFieldsScrubbed', label: 'Metadata fields cleared', count: 6 },
		])
		expect(reportRows(null)).toEqual([])
	})
})

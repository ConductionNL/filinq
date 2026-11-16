/**
 * The admin's grounds section says where the Woo refusal grounds come from.
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-the-snapshot-is-read-only-and-the-admin-says-where-the-list-lives-req-grd-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const http = vi.hoisted(() => ({ calls: [], answer: () => Promise.resolve({ data: {} }) }))

vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url) => {
			http.calls.push(url)
			return http.answer()
		},
	},
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))

const { dossiqGroundsUrl, fetchGroundsSource, sourceSummary } = await import('../../src/services/groundsSource.js')

describe('sourceSummary', () => {
	it('reads the snapshot with its version and the citable grounds', () => {
		expect(sourceSummary({
			source: 'snapshot',
			version: '1.2.0',
			grounds: [
				{ code: '5.1', label: 'Uitzonderingen', citable: false },
				{ code: '5.1.2.e', label: 'Persoonlijke levenssfeer' },
				{ code: '', label: 'zonder code' },
			],
		})).toEqual({ source: 'snapshot', version: '1.2.0', grounds: [{ code: '5.1.2.e', label: 'Persoonlijke levenssfeer' }] })
	})

	it('names dossiq as the source when dossiq answered', () => {
		expect(sourceSummary({ source: 'dossiq', version: '', grounds: [{ code: '5.1.4' }] }).source).toBe('dossiq')
	})

	it('is null when nothing could be read', () => {
		expect(sourceSummary(null)).toBeNull()
		expect(sourceSummary({ error: 'x' })).toBeNull()
		expect(sourceSummary({ grounds: [] })).toBeNull()
	})
})

describe('fetchGroundsSource', () => {
	beforeEach(() => {
		http.calls.length = 0
	})

	it('reads the grounds endpoint', async () => {
		http.answer = () => Promise.resolve({ data: { source: 'dossiq', version: 'v', grounds: [{ code: '5.1.4', label: 'Geadresseerde' }] } })

		expect(await fetchGroundsSource()).toEqual({ source: 'dossiq', version: 'v', grounds: [{ code: '5.1.4', label: 'Geadresseerde' }] })
		expect(http.calls).toEqual(['/apps/filinq/api/grounds'])
	})

	it('is null when the endpoint fails', async () => {
		http.answer = () => Promise.reject(new Error('503'))

		expect(await fetchGroundsSource()).toBeNull()
	})

	it('links to dossiq\'s grounds page', () => {
		expect(dossiqGroundsUrl()).toBe('/apps/dossiq/settings/woo-refusal-grounds')
	})
})

/**
 * Sharing documents by link: the calls the dialog and the Shared links page
 * make, and the rules that keep the share button disabled.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-3.1
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	canShare,
	canTakeBack,
	createShare,
	latestExpiry,
	listShares,
	preflightShare,
	revokeShare,
	shareFileIds,
	shareLabels,
	shareMegabytes,
	shareRow,
	shareStateLabel,
} from '../../src/services/documentShare.js'

const calls = []
let refuse = null
vi.mock('@nextcloud/axios', () => {
	const respond = (data) => {
		if (refuse) {
			const error = new Error('refused')
			error.response = refuse
			return Promise.reject(error)
		}
		return Promise.resolve({ data })
	}
	return {
		default: {
			get: (url, config) => {
				calls.push(['get', url, config])
				return respond({
					results: [
						{
							uuid: 's-1',
							files: [{ name: 'besluit.pdf', version: 'e7' }, { name: 'advies.pdf' }],
							status: 'active',
						},
					],
				})
			},
			post: (url, body) => {
				calls.push(['post', url, body])
				return respond({ url: 'https://cloud.example/s/x' })
			},
			delete: (url) => {
				calls.push(['delete', url])
				return respond({ status: 'revoked' })
			},
		},
	}
})
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars = {}) =>
		text.replace(/{(\w+)}/g, (m, k) => (k in vars ? vars[k] : m)),
}))

function preflight (overrides = {}) {
  return {
	maxDays: null,
	passwordRequired: false,
	defaultExpiresAt: '2026-11-28',
	labels: [],
	bundle: null,
	...overrides,
}
}

describe('documentShare service', () => {
	beforeEach(() => {
		calls.length = 0
		refuse = null
	})

	it('keeps each chosen file once, and only real ids', () => {
		expect(shareFileIds([7, '8', 7, 'x', 0, -2, 8.5, '9'])).toEqual([7, 8, 9])
		expect(shareFileIds(undefined)).toEqual([])
	})

	it('asks the preflight for the chosen files', async () => {
		const outcome = await preflightShare(['7', 8, 7])

		expect(calls).toEqual([['post', '/apps/filinq/api/shares/preflight', { fileIds: [7, 8] }]])
		expect(outcome.ok).toBe(true)
	})

	it('sends a bundle name only for two or more documents, and a password only when set', async () => {
		await createShare({ fileIds: [7], name: 'ignored', expiresOn: '2026-11-20', note: ' Advies ', labelConfirmed: true })
		await createShare({ fileIds: [7, 8], name: ' Bezwaar 2026-031 ', expiresOn: '', password: 'geheim', labelConfirmed: 'yes' })

		expect(calls[0][2]).toEqual({ fileIds: [7], expiresOn: '2026-11-20', note: 'Advies', labelConfirmed: true })
		expect(calls[1][2]).toEqual({
			fileIds: [7, 8],
			expiresOn: '',
			note: '',
			labelConfirmed: false,
			name: 'Bezwaar 2026-031',
			password: 'geheim',
		})
	})

	it("answers Nextcloud's refusal with its status and reason", async () => {
		refuse = { status: 403, data: { error: 'Sharing is not allowed' } }

		expect(await createShare({ fileIds: [7] })).toEqual({ ok: false, status: 403, error: 'Sharing is not allowed' })
	})

	it('takes a link back by its record', async () => {
		await revokeShare('a/b')

		expect(calls).toEqual([['delete', '/apps/filinq/api/shares/a%2Fb']])
	})

	it("lists a clerk's own shares, and everyone's for an admin", async () => {
		const clerk = await listShares({ uid: 'clara', isAdmin: false })
		await listShares({ uid: 'root', isAdmin: true })

		expect(calls[0][1]).toBe('/apps/openregister/api/objects/filinq/documentShare')
		expect(calls[0][2].params.sharedBy).toBe('clara')
		expect('sharedBy' in calls[1][2].params).toBe(false)
		expect(clerk.data.results[0]).toMatchObject({ id: 's-1', what: 'besluit.pdf (e7), advies.pdf' })
	})

	it('names how a share ended', () => {
		expect(shareStateLabel({ status: 'active' })).toBe('Active')
		expect(shareStateLabel({ status: 'expired' })).toBe('Expired')
		expect(shareStateLabel({ status: 'revoked', revokedBy: 'clara' })).toBe('Taken back by clara')
		expect(shareStateLabel({ status: 'revoked', revokedBy: 'removed in Files' })).toBe('Removed in Files')
		expect([canTakeBack({ status: 'active' }), canTakeBack({ status: 'expired' })]).toEqual([true, false])
		expect(shareRow({ '@self': { id: 'x' } })).toMatchObject({ id: 'x', what: '' })
	})

	it('keeps the share button disabled until every rule is met', () => {
		const base = { preflight: preflight(), expiresOn: '2026-11-28', fileCount: 1 }
		expect(canShare(base)).toBe(true)
		expect(canShare({ ...base, preflight: null })).toBe(false)
		expect(canShare({ ...base, expiresOn: '' })).toBe(false)
		expect(canShare({ ...base, preflight: preflight({ passwordRequired: true }) })).toBe(false)
		expect(canShare({ ...base, preflight: preflight({ passwordRequired: true }), password: 'p' })).toBe(true)
		expect(canShare({ ...base, fileCount: 3, name: '  ' })).toBe(false)
		expect(canShare({ ...base, fileCount: 3, name: 'Bundel' })).toBe(true)

		const labelled = { ...base, preflight: preflight({ labels: [{ fileId: 7, label: 'Confidential' }] }) }
		expect(canShare(labelled)).toBe(false)
		expect(canShare({ ...labelled, labelConfirmed: true })).toBe(true)
	})

	it('names each label once', () => {
		expect(shareLabels(preflight({ labels: [{ label: 'Confidential' }, { label: 'Confidential' }, { label: 'Secret' }] }))).toEqual(['Confidential', 'Secret'])
		expect(shareLabels(null)).toEqual([])
	})

	it("works out Nextcloud's latest expiry and writes sizes in megabytes", () => {
		const today = new Date('2026-11-13T15:30:00Z')
		expect(latestExpiry(7, today)).toBe('2026-11-20')
		expect(latestExpiry(null, today)).toBe('')
		expect(latestExpiry(0, today)).toBe('')
		expect(shareMegabytes(1572864)).toBe('1.5')
		expect(shareMegabytes(undefined)).toBe('0.0')
	})
})

/**
 * The renewal pipeline's buckets and the status a contract shows.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-2
 */

import { describe, expect, it } from 'vitest'
import {
	bucketContracts,
	bucketOf,
	BUCKETS,
	displayStatus,
} from '../../src/services/contractPipeline.js'

const today = '2026-09-30'
const active = (fields) => ({ status: 'active', ...fields })

describe('contract pipeline', () => {
	it('has the four buckets in urgency order', () => {
		expect(BUCKETS).toEqual(['expired', 'noticeDue', 'expiring', 'later'])
	})

	it('shows an active contract past its end date as expired', () => {
		expect(displayStatus(active({ endDate: '2026-09-29' }), today)).toBe(
			'expired',
		)
		expect(displayStatus(active({ endDate: '2026-09-30' }), today)).toBe(
			'active',
		)
		expect(
			displayStatus({ status: 'draft', endDate: '2020-01-01' }, today),
		).toBe('draft')
		expect(displayStatus({}, today)).toBe('draft')
	})

	it('buckets by the first rule that holds', () => {
		expect(bucketOf(active({ endDate: '2026-01-01' }), today)).toBe('expired')
		// Notice deadline passed: still notice due, the end date is ahead.
		expect(
			bucketOf(
				active({ endDate: '2026-12-31', noticeDeadline: '2026-09-01' }),
				today,
			),
		).toBe('noticeDue')
		expect(
			bucketOf(
				active({ endDate: '2027-12-31', noticeDeadline: '2026-10-30' }),
				today,
			),
		).toBe('noticeDue')
		expect(
			bucketOf(
				active({ endDate: '2027-12-31', noticeDeadline: '2026-10-31' }),
				today,
			),
		).toBe('later')
		expect(bucketOf(active({ endDate: '2026-12-29' }), today)).toBe('expiring')
		expect(bucketOf(active({ endDate: '2026-12-30' }), today)).toBe('later')
		expect(bucketOf(active({}), today)).toBe('later')
	})

	it('leaves every contract that is not active out', () => {
		for (const status of ['draft', 'renewed', 'terminated', 'expired']) {
			expect(bucketOf({ status, endDate: '2026-10-01' }, today)).toBe(null)
		}
	})

	it('places the seeded contracts as the spec says', () => {
		const buckets = bucketContracts(
			[
				active({
					title: 'Raamovereenkomst groenonderhoud 2026-2028',
					endDate: '2028-12-31',
					noticeDeadline: '2028-10-02',
				}),
				active({
					title: 'Schoonmaakdienstverlening stadskantoor',
					endDate: '2026-12-31',
					noticeDeadline: '2026-10-20',
				}),
				{
					title: 'Subsidieovereenkomst cultuurfonds (concept)',
					status: 'draft',
				},
			],
			today,
		)
		expect(buckets.noticeDue.map((c) => c.title)).toEqual([
			'Schoonmaakdienstverlening stadskantoor',
		])
		expect(buckets.later.map((c) => c.title)).toEqual([
			'Raamovereenkomst groenonderhoud 2026-2028',
		])
		expect(buckets.expired).toEqual([])
		expect(buckets.expiring).toEqual([])
	})

	it('puts the most urgent contract first in a bucket', () => {
		const buckets = bucketContracts(
			[
				active({ title: 'B', endDate: '2026-12-01' }),
				active({ title: 'A', endDate: '2026-11-01' }),
			],
			today,
		)
		expect(buckets.expiring.map((c) => c.title)).toEqual(['A', 'B'])
	})
})

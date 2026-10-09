/**
 * The inbox says whether a failed reading reaches anybody.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-failure-reaches-someone/tasks.md#task-3.3
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it, vi } from 'vitest'
import { reachWarning } from '../../src/services/intakeReading.js'
import { fetchWaitingInbox } from '../../src/services/intakeService.js'

vi.mock('@nextcloud/axios', () => ({
	default: {
		get: () =>
			Promise.resolve({
				data: {
					results: [{ uuid: 'a', readingState: 'failed' }],
					total: 1,
					notificationReach: {
						staffed: false,
						failureCount: 1,
						group: 'docudesk-woo-officers',
						notificationReaches: 0,
					},
				},
			}),
	},
}))

describe('reachWarning', () => {
	it('names the group and the failures when nobody is told', () => {
		const text = reachWarning({
			staffed: false,
			failureCount: 2,
			group: 'docudesk-woo-officers',
		})
		expect(text).toContain('docudesk-woo-officers')
		expect(text).toContain('2')
	})

	it('warns before anything has failed when the group is empty', () => {
		const text = reachWarning({
			staffed: false,
			failureCount: 0,
			group: 'docudesk-woo-officers',
		})
		expect(text).toContain('docudesk-woo-officers')
		expect(text).not.toBe('')
	})

	it('says nothing when the group has people in it, or when there is no answer', () => {
		expect(reachWarning({ staffed: true, failureCount: 3, group: 'g' })).toBe('')
		expect(reachWarning(null)).toBe('')
		expect(reachWarning(undefined)).toBe('')
	})
})

describe('fetchWaitingInbox', () => {
	it('returns the rows and the reach from one answer', async () => {
		const inbox = await fetchWaitingInbox()
		expect(inbox.results.map((row) => row.uuid)).toEqual(['a'])
		expect(inbox.notificationReach.staffed).toBe(false)
	})
})

describe('the inbox view', () => {
	it('loads the reach and shows its warning', () => {
		const source = readFileSync('src/views/intake/IntakeIndex.vue', 'utf8')
		expect(source).toContain('fetchWaitingInbox')
		expect(source).toContain('data-testid="intake-reach-warning"')
	})
})

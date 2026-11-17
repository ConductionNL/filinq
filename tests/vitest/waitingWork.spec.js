/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * My documents lists the records the person created and what waits for
 * them, each item saying why it waits (specify-capability-rows REQ-SCR-006).
 */

import { describe, expect, it } from 'vitest'
import {
	reasonLabel,
	recordRows,
	waitingRows,
} from '../../src/components/myDocuments/waitingWork.js'

describe('the Waiting for me list', () => {
	it('says why each item waits', () => {
		expect(reasonLabel('Waiting for your signature')).toBe('Waiting for your signature')
		expect(reasonLabel('Routed to your group, waiting for acceptance')).toBe(
			'Routed to your group, waiting for acceptance',
		)
		expect(reasonLabel('Something new')).toBe('Something new')
		expect(reasonLabel(undefined)).toBe('')
	})

	it('lists three kinds of waiting work, each with its reason', () => {
		const rows = waitingRows([
			{ kind: 'signature', id: 'req-1', name: 'Besluit', reason: 'Waiting for your signature', since: '2026-11-01T09:00:00Z' },
			{ kind: 'intake', id: 'in-1', name: 'Brief.pdf', reason: 'Routed to your group, waiting for acceptance', since: '' },
			{ kind: 'review', id: 'gen-1', name: '', reason: 'Due for review', since: '' },
		])

		expect(rows.map((row) => row.kind)).toEqual(['signature', 'intake', 'review'])
		expect(rows.map((row) => row.key)).toEqual(['signature-req-1', 'intake-in-1', 'review-gen-1'])
		expect(rows[0].since).toBe(new Date('2026-11-01T09:00:00Z').toLocaleDateString())
		expect(rows[1].since).toBe('')
		expect(rows[2].name).toBe('gen-1')
		expect(rows[2].reason).toBe('Due for review')
	})

	it('lists nothing without an answer', () => {
		expect(waitingRows(undefined)).toEqual([])
	})
})

describe('the My records list', () => {
	it('names each record and counts the records it is on', () => {
		expect(
			recordRows([
				{ uuid: 'v1', documentName: 'Besluit.pdf', status: 'final', domains: [{}, {}] },
				{ '@self': { id: 'v2' } },
			]),
		).toEqual([
			{ key: 'v1', name: 'Besluit.pdf', status: 'final', linkedTo: 2 },
			{ key: 'v2', name: 'v2', status: '', linkedTo: 0 },
		])
		expect(recordRows(null)).toEqual([])
	})
})

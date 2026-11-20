/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The recipient list on the signing request detail page lists the signer
 * as signer and the accept-only recipient as accept-only, in order
 * (signing-accept-only-recipient REQ-SAO-001, task 4.2).
 */

import { describe, expect, it } from 'vitest'
import {
	recipientRows,
	roleLabel,
	statusLabel,
} from '../../src/views/signing/recipientList.js'

describe('the recipient list on the signing request detail page', () => {
	it('names the role: accept-only, or sign for anything else', () => {
		expect(roleLabel('accept')).toBe('Accept only')
		expect(roleLabel('sign')).toBe('Sign')
		expect(roleLabel(undefined)).toBe('Sign')
	})

	it('names the state, and an unknown one as unknown', () => {
		expect(statusLabel('PENDING')).toBe('Waiting')
		expect(statusLabel('SIGNED')).toBe('Signed')
		expect(statusLabel('ACCEPTED')).toBe('Accepted')
		expect(statusLabel('DECLINED')).toBe('Declined')
		expect(statusLabel('UNREADABLE')).toBe('Unknown')
	})

	it('lists the recipients in order, with a name for a nameless one', () => {
		const rows = recipientRows([
			{ id: 'officer', name: 'Officer', role: 'accept', status: 'PENDING', order: 1, at: '' },
			{ id: 'tenant', name: '', role: 'sign', status: 'SIGNED', order: 0, at: '2026-11-01T10:00:00Z' },
		])

		expect(rows.map((row) => row.id)).toEqual(['tenant', 'officer'])
		expect(rows[0].name).toBe('Recipient 1')
		expect(rows[0].role).toBe('Sign')
		expect(rows[0].status).toBe('Signed')
		expect(rows[0].at).toBe(new Date('2026-11-01T10:00:00Z').toLocaleString())
		expect(rows[1]).toEqual({ id: 'officer', name: 'Officer', role: 'Accept only', status: 'Waiting', at: '-' })
	})

	it('lists nothing without recipients', () => {
		expect(recipientRows(undefined)).toEqual([])
		expect(recipientRows([])).toEqual([])
	})
})

/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Issue #1209: the new signing request form must name at least one signer
 * who can be reached, and send them as `signers`.
 */

import { describe, expect, it } from 'vitest'
import {
	emptySignerRow,
	isReachable,
	signersAreComplete,
	toSigners,
} from '../../src/views/signing/signerRows.js'

describe('signer rows on the signing request form', () => {
	it('a name alone reaches nobody', () => {
		expect(isReachable({ displayName: 'Bea' })).toBe(false)
		expect(isReachable({ email: '  ' })).toBe(false)
		expect(isReachable({ email: 'bea@example.org' })).toBe(true)
		expect(isReachable({ userId: 'carl' })).toBe(true)
	})

	it('needs at least one row and every row reachable', () => {
		expect(signersAreComplete([])).toBe(false)
		expect(signersAreComplete([emptySignerRow()])).toBe(false)
		expect(
			signersAreComplete([{ email: 'bea@example.org' }, emptySignerRow()]),
		).toBe(false)
		expect(
			signersAreComplete([{ email: 'bea@example.org' }, { userId: 'carl' }]),
		).toBe(true)
	})

	it('sends trimmed signers in row order', () => {
		expect(
			toSigners([
				{ displayName: ' Bea ', email: 'bea@example.org', userId: '' },
				{ displayName: '', email: '', userId: 'carl' },
			]),
		).toEqual([
			{ order: 0, displayName: 'Bea', email: 'bea@example.org' },
			{ order: 1, userId: 'carl' },
		])
	})
})

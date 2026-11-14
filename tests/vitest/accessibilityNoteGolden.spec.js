/**
 * Golden: every accessibility note after redaction, for every state, tag
 * count, veraPDF flag and reason list, recorded on the switch-based
 * implementation.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/specs/accessible-redaction-output/spec.md
 */

import { describe, it } from 'vitest'
import { accessibilityNote } from '../../src/services/redactionAccessibility.js'
import { golden } from './golden/golden.js'

const STATES = {
	preserved: 'preserved',
	degraded: 'degraded',
	'not-applicable': 'not-applicable',
	unknown: 'unknown',
	other: 'other',
	upper: 'PRESERVED',
	toString: 'toString',
	['__proto__']: '__proto__',
	hasOwnProperty: 'hasOwnProperty',
	number: 7,
	boxed: { toString: () => 'preserved' },
	array: ['degraded'],
}
const BEFORE = { absent: undefined, null: null, zero: 0, three: 3 }
const AFTER = { absent: undefined, null: null, zero: 0, two: 2 }
const VERIFIED = { absent: undefined, yes: true, no: false, text: 'yes' }
const REASONS = {
	absent: undefined,
	null: null,
	empty: [],
	mixed: ['input-not-tagged', 'verapdf-not-pdfua', 'some-new-reason'],
	text: 'input-not-tagged',
	blank: '',
	false: false,
}

/**
 * The outcome for one combination, leaving absent fields out.
 *
 * @param {object} fields The fields, undefined meaning absent.
 * @return {object}
 */
function outcomeOf(fields) {
	return Object.fromEntries(
		Object.entries(fields).filter(([, value]) => value !== undefined),
	)
}

/**
 * The note for an outcome, or the error it throws.
 *
 * @param {*} outcome The structurePreservation block.
 * @return {*}
 */
function noteOrError(outcome) {
	try {
		return { note: accessibilityNote(outcome) }
	} catch (error) {
		return { threw: error.constructor.name }
	}
}

describe('accessibilityNote golden', () => {
	it('gives the recorded note for an empty outcome', () => {
		const cases = {
			undefined,
			null: null,
			false: false,
			zero: 0,
			empty: {},
			emptyState: { state: '', tagCountBefore: 3 },
			nullState: { state: null },
			zeroState: { state: 0 },
			falseState: { state: false },
		}
		golden(
			'accessibilityNote-empty',
			Object.fromEntries(
				Object.entries(cases).map(([key, outcome]) => [
					key,
					noteOrError(outcome),
				]),
			),
		)
	})

	it('gives the recorded note for every state, count and veraPDF flag', () => {
		const rows = {}
		for (const [state, stateValue] of Object.entries(STATES)) {
			for (const [before, beforeValue] of Object.entries(BEFORE)) {
				for (const [after, afterValue] of Object.entries(AFTER)) {
					for (const [verified, verifiedValue] of Object.entries(
						VERIFIED,
					)) {
						const outcome = outcomeOf({
							state: stateValue,
							tagCountBefore: beforeValue,
							tagCountAfter: afterValue,
							veraPdfVerified: verifiedValue,
							lossReasons: ['input-not-tagged'],
						})
						rows[[state, before, after, verified].join(' ')] =
							noteOrError(outcome)
					}
				}
			}
		}
		golden('accessibilityNote-counts', rows)
	})

	it('gives the recorded note for every state and reason list', () => {
		const rows = {}
		for (const [state, stateValue] of Object.entries(STATES)) {
			for (const [reasons, reasonsValue] of Object.entries(REASONS)) {
				rows[[state, reasons].join(' ')] = noteOrError(
					outcomeOf({
						state: stateValue,
						tagCountBefore: 4,
						tagCountAfter: 1,
						veraPdfVerified: true,
						lossReasons: reasonsValue,
					}),
				)
			}
		}
		golden('accessibilityNote-reasons', rows)
	})
})

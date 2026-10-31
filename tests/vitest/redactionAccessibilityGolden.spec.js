/**
 * Golden: the loss reason sentences and the accessibility notes, recorded on
 * the switch-based implementation.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/specs/accessible-redaction-output/spec.md
 */

import { describe, it } from 'vitest'
import {
	accessibilityNote,
	lossReasonText,
} from '../../src/services/redactionAccessibility.js'
import { golden } from './golden/golden.js'

const REASONS = [
	'engine-cannot-reauthor-structtree',
	'marked-content-correspondence-broken',
	'structtreeroot-dropped-on-rebuild',
	'input-not-tagged',
	'page-structure-not-preservable',
	'verapdf-not-pdfua',
	'some-new-reason',
	'',
	'toString',
	'constructor',
	'__proto__',
	'hasOwnProperty',
	undefined,
	null,
	42,
]

describe('lossReasonText golden', () => {
	it('gives the recorded sentence for every reason', () => {
		golden(
			'lossReasonText',
			REASONS.map((reason) => {
				const out = lossReasonText(reason)
				return { reason: String(reason), type: typeof out, out: out ?? null }
			}),
		)
	})

	it('gives the recorded note for every reason list', () => {
		golden(
			'accessibilityNoteReasons',
			['preserved', 'degraded', 'not-applicable', 'other'].map((state) =>
				accessibilityNote({
					state,
					tagCountBefore: 3,
					lossReasons: REASONS,
				}),
			),
		)
	})
})

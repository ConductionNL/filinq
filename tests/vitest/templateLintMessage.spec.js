/**
 * The template lint sentence for findings the accessibility spec does not
 * cover: a nameless image, an unknown rule, and a rule named like an
 * object key.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-3.3
 */

import { describe, expect, it } from 'vitest'
import { lintMessage } from '../../src/services/templateLint.js'

describe('template lint sentence edges', () => {
	it('names a nameless image with a question mark', () => {
		expect(lintMessage({ rule: 'image-missing-alt', position: 4, text: '' }))
			.toBe('Image 4 (?) has no alternative text.')
	})

	it('answers an unknown rule with the rule itself', () => {
		expect(lintMessage({ rule: 'contrast-too-low', position: 1, text: 'x' })).toBe('contrast-too-low')
		expect(lintMessage({ rule: 'constructor', position: 1, text: 'x' })).toBe('constructor')
		expect(lintMessage({ rule: undefined })).toBe(undefined)
	})

	it('fills the table sentence in full', () => {
		expect(lintMessage({ rule: 'table-without-headers', position: 2, text: 'Bedrag' }))
			.toBe('Table 2 (starting with "Bedrag") has no header cells.')
	})
})

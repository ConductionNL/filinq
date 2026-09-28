/**
 * The merge field dialog offers the legal basis and the objection deadline.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/decision-letter-legal-basis-and-deadline/specs/document-creatie-sjablonen/spec.md
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import { suggestedMergeFields } from '../../src/services/mergeFieldSuggestions.js'

describe('suggestedMergeFields', () => {
	it('offers the legal basis and the objection deadline fields', () => {
		const fields = suggestedMergeFields().map((s) => s.field)
		expect(fields).toEqual(expect.arrayContaining([
			'grondslag.name',
			'grondslag.description',
			'bezwaar.uiterlijk',
			'bezwaar.termijnWeken',
		]))
	})

	it('gives every field a label', () => {
		for (const s of suggestedMergeFields()) {
			expect(s.label).toBeTruthy()
		}
	})

	it('is the list the dialog renders', () => {
		const dialog = readFileSync(new URL('../../src/dialogs/MergeFieldDialog.vue', import.meta.url), 'utf8')
		expect(dialog).toContain('suggestedMergeFields')
		expect(dialog).toMatch(/v-for="suggestion in suggestions"/)
	})
})

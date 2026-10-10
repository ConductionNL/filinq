/**
 * What the sidebar and the publication page say about accessibility after redaction.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/specs/accessible-redaction-output/spec.md
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import {
	accessibilityLost,
	accessibilityNote,
	lossReasonText,
} from '../../src/services/redactionAccessibility.js'

describe('accessibilityNote', () => {
	it('shows a preserved copy as preserved, with the tag counts', () => {
		const note = accessibilityNote({
			state: 'preserved',
			tagCountBefore: 96,
			tagCountAfter: 96,
			lossReasons: [],
			veraPdfVerified: true,
		})
		expect(note.type).toBe('success')
		expect(note.title).toBe('Accessibility preserved')
		expect(note.detail).toBe('96 of 96 tags kept. Confirmed by veraPDF.')
	})

	it('flags a degraded copy with readable loss reasons', () => {
		const note = accessibilityNote({
			state: 'degraded',
			tagCountBefore: 12,
			tagCountAfter: 0,
			lossReasons: ['structtreeroot-dropped-on-rebuild', 'something-new'],
		})
		expect(note.type).toBe('warning')
		expect(note.title).toBe('Accessibility lost')
		expect(note.reasons).toEqual([
			'The tag structure was dropped when the file was rebuilt',
			'something-new',
		])
	})

	it('never shows unknown as fine', () => {
		const note = accessibilityNote({ state: 'unknown' })
		expect(note.type).toBe('warning')
		expect(note.title).toBe('Accessibility unknown')
	})

	it('shows nothing for a run that recorded nothing', () => {
		expect(accessibilityNote(null)).toBeNull()
	})

	it('names the veraPDF finding', () => {
		expect(lossReasonText('verapdf-not-pdfua')).toContain('veraPDF')
	})
})

describe('the publication page', () => {
	it('treats degraded and unknown as lost', () => {
		expect(accessibilityLost({ accessibilityState: 'degraded' })).toBe(true)
		expect(accessibilityLost({ accessibilityState: 'unknown' })).toBe(true)
		expect(accessibilityLost({ accessibilityState: 'preserved' })).toBe(false)
		expect(accessibilityLost({})).toBe(false)
	})

	it('asks for the override reason and saves it with the metadata', () => {
		const source = readFileSync(
			new URL(
				'../../src/views/publications/PublicationsPage.vue',
				import.meta.url,
			),
			'utf8',
		)
		expect(source).toContain('v-model="form.accessibilityOverrideReason"')
		expect(source).toMatch(/'accessibilityOverrideReason',\n\]/)
	})

	it('keeps the outcome the server returns on the sidebar entry', () => {
		const store = readFileSync(
			new URL('../../src/store/modules/anonymization.js', import.meta.url),
			'utf8',
		)
		expect(store).toContain('anonymizeResponse.data.structurePreservation')
	})
})

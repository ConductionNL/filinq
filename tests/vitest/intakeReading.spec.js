/**
 * The inbox shows how far reading an arriving scan got.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-intake-ocr-on-arrival/tasks.md#task-2.2
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import { readingColorMap, readingLabel } from '../../src/services/intakeReading.js'

describe('readingLabel', () => {
	it('names every reading state', () => {
		expect(readingLabel('queued')).toBe('Waiting to be read')
		expect(readingLabel('reading')).toBe('Reading')
		expect(readingLabel('read')).toBe('Text recognised')
		expect(readingLabel('failed')).toBe('Text could not be read')
	})

	it('shows nothing for a document that is not being read', () => {
		expect(readingLabel(undefined)).toBe('')
		expect(readingLabel(null)).toBe('')
	})

	it('never calls an unknown state finished', () => {
		expect(readingLabel('done?')).toBe('Text could not be read')
	})

	it('colours a failure as an error', () => {
		expect(readingColorMap()['Text could not be read']).toBe('error')
	})
})

describe('the inbox', () => {
	it('has a reading column', () => {
		const source = readFileSync('src/views/intake/IntakeIndex.vue', 'utf8')
		expect(source).toContain("key: 'readingState'")
		expect(source).toContain('#column-readingState')
	})
})

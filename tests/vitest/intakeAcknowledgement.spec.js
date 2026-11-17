/**
 * The inbox shows whether the sender was told a document arrived, and opens
 * the copy with the received stamp.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-the-worklist-shows-both-outcomes-req-ias-004
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import {
	acknowledgementColorMap,
	acknowledgementDetail,
	acknowledgementLabel,
	stampedCopyUrl,
} from '../../src/services/intakeAcknowledgement.js'

describe('acknowledgementLabel', () => {
	it('names every outcome', () => {
		expect(acknowledgementLabel({ status: 'sent' })).toBe('Sent')
		expect(acknowledgementLabel({ status: 'queued-as-letter' })).toBe('Queued as letter')
		expect(acknowledgementLabel({ status: 'refused' })).toBe('Refused')
		expect(acknowledgementLabel({ status: 'failed' })).toBe('Failed')
	})

	it('shows nothing for a document no rule acknowledged', () => {
		expect(acknowledgementLabel(undefined)).toBe('')
		expect(acknowledgementLabel(null)).toBe('')
		expect(acknowledgementLabel({})).toBe('')
	})

	it('never calls an unknown outcome sent', () => {
		expect(acknowledgementLabel({ status: 'maybe' })).toBe('Failed')
	})

	it('colours a sent acknowledgement as success and a failure as an error', () => {
		const colours = acknowledgementColorMap()
		expect(colours.Sent).toBe('success')
		expect(colours.Refused).toBe('warning')
		expect(colours.Failed).toBe('error')
	})
})

describe('acknowledgementDetail', () => {
	it('gives the reason before the address', () => {
		expect(acknowledgementDetail({ status: 'failed', address: 'a@example.nl', reason: 'smtp down' })).toBe('smtp down')
		expect(acknowledgementDetail({ status: 'sent', address: 'a@example.nl', reason: null })).toBe('a@example.nl')
		expect(acknowledgementDetail(null)).toBe('')
	})
})

describe('stampedCopyUrl', () => {
	it('opens the stamped copy through the file route', () => {
		expect(stampedCopyUrl({ stampedRendition: 77 })).toBe('/index.php/f/77')
	})

	it('offers nothing without a stamped copy', () => {
		expect(stampedCopyUrl({})).toBe('')
		expect(stampedCopyUrl({ stampedRendition: 0 })).toBe('')
		expect(stampedCopyUrl({ stampedRendition: 'x' })).toBe('')
		expect(stampedCopyUrl(null)).toBe('')
	})
})

describe('the inbox', () => {
	it('has an acknowledged column and opens the stamped copy', () => {
		const source = readFileSync('src/views/intake/IntakeIndex.vue', 'utf8')
		expect(source).toContain("key: 'acknowledgement'")
		expect(source).toContain('#column-acknowledgement')
		expect(source).toContain("t('filinq', 'Open stamped copy')")
	})

	it('lists the intake rules with both fields', () => {
		const fragment = JSON.parse(readFileSync('src/manifest.d/20-intake.json', 'utf8'))
		const page = fragment.pages.find((candidate) => candidate.id === 'IntakeRules')
		expect(page.config.schema).toBe('intakeDefaultRule')
		expect(page.config.columns).toEqual(expect.arrayContaining(['acknowledgeWith', 'stampReceived']))
	})
})

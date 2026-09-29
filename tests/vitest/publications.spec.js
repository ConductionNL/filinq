/**
 * Woo publications in the browser: what a record means, and where the pages hang.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/woo-publicatie-pipeline/specs/woo-publicatie-pipeline/spec.md
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import {
	canHandOff,
	canWithdraw,
	missingMetadata,
	publicationStatusLabel,
	readinessChecks,
} from '../../src/services/publicationRecord.js'

const ready = {
	status: 'ready',
	entitiesReviewed: true,
	consentClear: true,
	prohibitionsClear: true,
	officieleTitel: 'Besluit',
	wooCategory: 'c_8c840238',
	publicatiedatum: '2026-10-01',
}

describe('a publication record', () => {
	it('lists the three checks with their verdicts', () => {
		const checks = readinessChecks({ ...ready, consentClear: false })
		expect(checks.map((c) => c.key)).toEqual([
			'entitiesReviewed',
			'consentClear',
			'prohibitionsClear',
		])
		expect(checks.map((c) => c.ok)).toEqual([true, false, true])
		expect(checks.map((c) => c.route)).toEqual([
			'Anonymization',
			'Consent',
			'Prohibitions',
		])
	})

	it('names the metadata a hand-off still needs', () => {
		expect(missingMetadata(ready)).toEqual([])
		expect(missingMetadata({ status: 'ready' })).toEqual([
			'officieleTitel',
			'wooCategory',
			'publicatiedatum',
		])
	})

	it('can be handed off only when ready, complete, and there is a platform', () => {
		expect(canHandOff(ready, true)).toBe(true)
		expect(canHandOff(ready, false)).toBe(false)
		expect(canHandOff({ ...ready, status: 'draft' }, true)).toBe(false)
		expect(canHandOff({ ...ready, wooCategory: '' }, true)).toBe(false)
	})

	it('can be withdrawn once handed off, and labels every status', () => {
		expect(canWithdraw({ status: 'published' })).toBe(true)
		expect(canWithdraw({ status: 'handed_off' })).toBe(true)
		expect(canWithdraw({ status: 'ready' })).toBe(false)
		for (const status of [
			'draft',
			'ready',
			'handed_off',
			'published',
			'depublication_requested',
			'depublished',
		]) {
			expect(publicationStatusLabel(status)).not.toBe(status)
		}
	})
})

describe('where the pages hang', () => {
	it('has a routed Publications page, a menu entry and a Publish action on a document', () => {
		const manifest = JSON.parse(
			readFileSync(
				new URL('../../src/manifest.json', import.meta.url),
				'utf8',
			),
		)
		const page = manifest.pages.find((p) => p.id === 'Publications')
		expect(page.route).toBe('/publications/:id?')
		expect(page.component).toBe('PublicationsPage')
		expect(manifest.menu.some((m) => m.route === 'Publications')).toBe(true)
		const registry = readFileSync(
			new URL('../../src/registry.js', import.meta.url),
			'utf8',
		)
		expect(registry).toContain(
			"PublicationsPage: { kind: 'page', component: PublicationsPage }",
		)
		const viewer = readFileSync(
			new URL(
				'../../src/views/fileViewer/FileViewerPage.vue',
				import.meta.url,
			),
			'utf8',
		)
		expect(viewer).toContain('startPublication(')
		expect(viewer).toContain("name: 'Publications'")
	})
})

/**
 * The PDF/UA report helpers: the routes called, the state line that is a
 * verdict only when the validator gave one, the source labels, the honest
 * guidance, and the regenerate action offered for generated PDFs only.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-validator-verdict-is-the-authoritative-conformance-heuristics-remain-the-floor-req-ddpum-004
 */

import { readFileSync } from 'node:fs'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	checkAccessibility,
	fetchAccessibility,
	offersRegeneration,
	regenerateAccessible,
	sourceLabel,
	stateText,
} from '../../src/services/accessibilityConformance.js'
import { guidanceText } from '../../src/services/conformance.js'

const calls = []
let getAnswer = {}
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url) => {
			calls.push(['get', url])
			return Promise.resolve({ data: getAnswer })
		},
		post: (url) => {
			calls.push(['post', url])
			if (url.endsWith('/regenerate')) {
				return Promise.resolve({
					data: { replaced: true, report: { compliant: true } },
				})
			}
			return Promise.resolve({
				data: { validated: true, report: { compliant: false } },
			})
		},
	},
}))

beforeEach(() => {
	calls.length = 0
	getAnswer = {}
})

describe('PDF/UA report service', () => {
	it('reads, checks and regenerates on the file routes', async () => {
		getAnswer = {
			report: { compliant: false },
			conformance: {
				source: 'validator',
				state: 'non-conformant',
				validated: true,
				validatorAvailable: true,
			},
			heuristicFindings: [],
			regenerable: true,
		}

		const state = await fetchAccessibility(42)
		const report = await checkAccessibility(42)
		const regenerated = await regenerateAccessible(42)

		expect(state.conformance.state).toBe('non-conformant')
		expect(state.regenerable).toBe(true)
		expect(report.compliant).toBe(false)
		expect(regenerated).toEqual({ replaced: true, report: { compliant: true } })
		expect(
			calls.map(([verb, url]) => [verb, url.replace(/^.*\/apps/, '/apps')]),
		).toEqual([
			['get', '/apps/filinq/api/validation/accessibility/42'],
			['post', '/apps/filinq/api/validation/accessibility/42'],
			['post', '/apps/filinq/api/validation/accessibility/42/regenerate'],
		])
	})

	it('reads a bare answer as not validated, never as a verdict', async () => {
		const state = await fetchAccessibility(7)

		expect(state.report).toBeNull()
		expect(state.conformance).toEqual({
			source: 'heuristic',
			state: 'looks-tagged',
			validated: false,
			validatorAvailable: false,
		})
		expect(state.regenerable).toBe(false)
	})
})

describe('PDF/UA state line', () => {
	it('is a conformance verdict only from the validator', () => {
		expect(stateText({ state: 'conformant' })).toBe(
			'Conforms to PDF/UA-1 according to the validator',
		)
		expect(stateText({ state: 'non-conformant' })).toBe(
			'Does not conform to PDF/UA-1 according to the validator',
		)
		expect(stateText({ state: 'looks-tagged' })).toContain('looks tagged')
		expect(stateText({ state: 'looks-tagged' })).toContain(
			'not a conformance verdict',
		)
		expect(stateText({ state: 'heuristic-issues' })).toContain(
			'Not validated against PDF/UA',
		)
		expect(stateText(null)).toContain('Not validated against PDF/UA')
	})

	it('labels where a finding comes from', () => {
		expect(sourceLabel('validator')).toBe('Validator')
		expect(sourceLabel('heuristic')).toBe('Quick check')
		expect(sourceLabel('')).toBe('')
	})

	it('never claims certification', () => {
		const texts = [
			stateText({ state: 'conformant' }),
			stateText({ state: 'non-conformant' }),
			stateText({ state: 'looks-tagged' }),
			stateText({ state: 'heuristic-issues' }),
			guidanceText('regenerateAccessible'),
			guidanceText('reauthorFromSource'),
		]
		for (const text of texts) {
			expect(text.toLowerCase()).not.toContain('certif')
		}
	})
})

describe('PDF/UA guidance and regeneration', () => {
	it('advises regenerating Filinq output and re-authoring imported pages', () => {
		expect(guidanceText('regenerateAccessible')).toContain(
			'accessible output option',
		)
		expect(guidanceText('reauthorFromSource')).toContain(
			'does not retag imported pages',
		)
		expect(guidanceText('reauthorFromSource')).toContain('accessible source')
	})

	it('offers to make accessible only a generated PDF that did not pass', () => {
		expect(offersRegeneration({ compliant: false }, true)).toBe(true)
		expect(offersRegeneration({ compliant: false }, false)).toBe(false)
		expect(offersRegeneration({ compliant: true }, true)).toBe(false)
		expect(offersRegeneration(null, true)).toBe(false)
	})

	it('labels every validator-backed accessibility check id the server sends', () => {
		const panel = readFileSync(
			new URL(
				'../../src/components/ValidationFindingsPanel.vue',
				import.meta.url,
			),
			'utf8',
		)
		const server = readFileSync(
			new URL(
				'../../lib/Kernel/Contracts/DocumentValidationServiceInterface.php',
				import.meta.url,
			),
			'utf8',
		)
		const ids = [
			...server.matchAll(
				/CHECK_(?:PDFUA_CONFORMANCE|ACCESSIBILITY_UNAVAILABLE) = '([a-z-]+)'/g,
			),
		].map((m) => m[1])

		expect(ids).toEqual([
			'pdfua-conformance-failed',
			'accessibility-validator-unavailable',
		])
		for (const id of ids) {
			expect(panel).toContain(`'${id}':`)
		}
	})
})

/**
 * The PDF/A report helpers and the grouped validation findings: the routes
 * called, the verdict line, the advice per failure shape, and archival
 * findings kept apart from the document checks.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it, vi } from 'vitest'
import {
	checkConformance,
	fetchConformance,
	guidanceText,
	verdictText,
} from '../../src/services/conformance.js'
import { groupFindings } from '../../src/services/validationService.js'

const calls = []
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url) => {
			calls.push(['get', url])
			return Promise.resolve({ data: { available: true, reports: { file: { compliant: true } } } })
		},
		post: (url) => {
			calls.push(['post', url])
			return Promise.resolve({ data: { report: { compliant: false, fontsNotEmbedded: ['Helvetica'] } } })
		},
	},
}))

describe('conformance service', () => {
	it('reads and runs the check on the file route', async () => {
		const read = await fetchConformance(42)
		const report = await checkConformance(42)

		expect(read.available).toBe(true)
		expect(read.reports.file.compliant).toBe(true)
		expect(report.fontsNotEmbedded).toEqual(['Helvetica'])
		expect(calls.map(([verb, url]) => [verb, url.replace(/^.*\/apps/, '/apps')])).toEqual([
			['get', '/apps/filinq/api/validation/conformance/42'],
			['post', '/apps/filinq/api/validation/conformance/42'],
		])
	})

	it('says what the verdict is', () => {
		expect(verdictText(null)).toBe('Not checked yet')
		expect(verdictText({ compliant: true, flavour: '3b' })).toBe('Meets PDF/A-3b')
		expect(verdictText({ compliant: false, flavour: '3b', failedRuleCount: 2 })).toBe(
			'Does not meet PDF/A-3b. Rules failed: 2',
		)
	})

	it('gives advice that fits the failure and never promises a font repair on imported pages', () => {
		expect(guidanceText('none')).toBe('')
		expect(guidanceText('regenerate')).toContain('Generate it again in Filinq')
		expect(guidanceText('reconvertFromSource')).toContain('cannot embed fonts')
		expect(guidanceText('reconvertFromSource')).toContain('original file')
		expect(guidanceText('ruleReferences')).toContain('rules listed')
	})
})

describe('grouped validation findings', () => {
	it('keeps archival findings apart and after the document checks', () => {
		const groups = groupFindings([
			{ checkId: 'pdfa-conformance-failed', category: 'archival' },
			{ checkId: 'pdf-encrypted', category: 'document' },
			{ checkId: 'text-layer-missing' },
		])

		expect(groups.map((g) => g.category)).toEqual(['document', 'archival'])
		expect(groups[0].findings.map((f) => f.checkId)).toEqual(['pdf-encrypted', 'text-layer-missing'])
		expect(groups[1].title).toBe('Archival checks (PDF/A)')
	})

	it('labels every archival check id the server sends', () => {
		const panel = readFileSync(new URL('../../src/components/ValidationFindingsPanel.vue', import.meta.url), 'utf8')
		const server = readFileSync(new URL('../../lib/Service/DocumentValidationService.php', import.meta.url), 'utf8')
		const ids = [...server.matchAll(/CHECK_(?:PDFA_\w+|ARCHIVAL_\w+) = '([a-z-]+)'/g)].map((m) => m[1])

		expect(ids).toEqual(['pdfa-conformance-failed', 'pdfa-font-not-embedded', 'archival-validator-unavailable'])
		for (const id of ids) {
			expect(panel).toContain(`'${id}':`)
		}
	})
})

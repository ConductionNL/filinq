/**
 * Accessibility findings and lint in the UI: the accessibility group sits
 * between the document and the archival checks, every accessibility check
 * id the server sends has a label, the publication warning takes only
 * accessibility findings and blocks only on a blocking one, and the
 * template lint says what is wrong and where.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-3.1
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it, vi } from 'vitest'
import { lintMessage } from '../../src/services/templateLint.js'
import {
	groupFindings,
	publicationReadiness,
} from '../../src/services/validationService.js'

let answer = null
vi.mock('@nextcloud/axios', () => ({
	default: {
		post: () =>
			answer instanceof Error
				? Promise.reject(answer)
				: Promise.resolve({ data: answer }),
	},
}))

describe('accessibility findings', () => {
	it('groups them between the document and the archival checks', () => {
		const groups = groupFindings([
			{ checkId: 'pdfa-conformance-failed', category: 'archival' },
			{ checkId: 'pdf-not-tagged', category: 'accessibility' },
			{ checkId: 'pdf-encrypted', category: 'document' },
		])

		expect(groups.map((g) => g.category)).toEqual([
			'document',
			'accessibility',
			'archival',
		])
		expect(groups[1].title).toBe('Accessibility checks')
	})

	it('labels every accessibility check id the server sends, and never says certified', () => {
		const panel = readFileSync(
			new URL(
				'../../src/components/ValidationFindingsPanel.vue',
				import.meta.url,
			),
			'utf8',
		)
		const server = readFileSync(
			new URL(
				'../../lib/Service/DocumentValidationService.php',
				import.meta.url,
			),
			'utf8',
		)
		const ids = [
			...server.matchAll(
				/CHECK_(?:PDF_NOT_TAGGED|PDF_LANGUAGE_MISSING|PDF_TITLE_MISSING|PDFUA_IDENTIFIER_MISSING) = '([a-z-]+)'/g,
			),
		].map((m) => m[1])

		expect(ids).toEqual([
			'pdf-not-tagged',
			'pdf-language-missing',
			'pdf-title-missing',
			'pdfua-identifier-missing',
		])
		for (const id of ids) {
			expect(panel).toContain(`'${id}':`)
		}
		expect(panel.toLowerCase()).not.toContain('certified')
	})
})

describe('publication readiness', () => {
	it('warns on open accessibility findings only', async () => {
		answer = {
			validationStatus: 'warnings',
			validationFindings: [
				{
					checkId: 'pdf-not-tagged',
					category: 'accessibility',
					severity: 'warning',
				},
				{
					checkId: 'metadata-incomplete',
					category: 'document',
					severity: 'warning',
				},
			],
		}

		const readiness = await publicationReadiness(7)

		expect(readiness).toEqual({
			checked: true,
			findings: [
				{
					checkId: 'pdf-not-tagged',
					category: 'accessibility',
					severity: 'warning',
				},
			],
			blocking: false,
		})
	})

	it('blocks only when a finding is set to blocking, and says so when it could not check', async () => {
		answer = {
			validationStatus: 'failed',
			validationFindings: [
				{
					checkId: 'pdf-not-tagged',
					category: 'accessibility',
					severity: 'blocking',
				},
			],
		}
		expect((await publicationReadiness(7)).blocking).toBe(true)

		answer = { validationStatus: 'passed', validationFindings: [] }
		expect(await publicationReadiness(7)).toEqual({
			checked: true,
			findings: [],
			blocking: false,
		})

		answer = new Error('500')
		expect(await publicationReadiness(7)).toEqual({
			checked: false,
			findings: [],
			blocking: false,
		})
	})
})

describe('template lint', () => {
	it('says what is wrong and where', () => {
		expect(
			lintMessage({
				rule: 'image-missing-alt',
				position: 1,
				text: 'wapen.png',
			}),
		).toBe('Image 1 (wapen.png) has no alternative text.')
		expect(
			lintMessage({
				rule: 'heading-order-jump',
				position: 2,
				text: 'Overwegingen',
				from: 'h1',
				to: 'h3',
			}),
		).toBe('Heading "Overwegingen" is h3 straight after h1: a level is skipped.')
		expect(
			lintMessage({
				rule: 'table-without-headers',
				position: 1,
				text: 'Kenteken',
			}),
		).toContain('Table 1')
		expect(
			lintMessage({ rule: 'language-unresolved', position: 0, text: '' }),
		).toContain('No language')
	})
})

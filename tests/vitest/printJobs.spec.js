/**
 * Print jobs: the Correspondence selection becomes one batch request.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/print-jobs-in-the-app/specs/print-preview/spec.md
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import {
	buildPrintRequest,
	canDownload,
	printJobStatusLabel,
} from '../../src/services/printJobs.js'

describe('buildPrintRequest', () => {
	it('turns three selected recipients into one request with three letters', () => {
		const body = buildPrintRequest({
			templateId: 'tmpl-1',
			batchMode: true,
			dataRefs: [],
			register: 'brp',
			schema: 'persoon',
			recipientIds: ['a', 'b', 'c'],
			caseReference: 'Z/1',
		})

		expect(body.templateId).toBe('tmpl-1')
		expect(body.items).toHaveLength(3)
		expect(body.items[1]).toEqual({
			dataRefs: [{ register: 'brp', schema: 'persoon', id: 'b' }],
			filename: 'brief-Z/1-2.pdf',
		})
	})

	it('sends a single letter with all its references', () => {
		const body = buildPrintRequest({
			templateId: 'tmpl-1',
			batchMode: false,
			dataRefs: [
				{ register: 'zaken', schema: 'zaak', id: 'z1', extra: 'x' },
				{ register: 'brp', schema: 'persoon', id: 'p1' },
			],
			register: '',
			schema: '',
			recipientIds: [],
			caseReference: '',
		})

		expect(body.items).toEqual([
			{
				dataRefs: [
					{ register: 'zaken', schema: 'zaak', id: 'z1' },
					{ register: 'brp', schema: 'persoon', id: 'p1' },
				],
				filename: 'brief-correspondentie.pdf',
			},
		])
	})
})

describe('the print jobs page', () => {
	it('labels every status and downloads only a rendered job', () => {
		for (const status of ['rendering', 'queued', 'sent', 'printed', 'failed']) {
			expect(printJobStatusLabel(status)).not.toBe(status)
		}
		expect(canDownload({ status: 'rendering', rendered: 0 })).toBe(false)
		expect(canDownload({ status: 'queued', rendered: 3 })).toBe(true)
	})

	it('is a routed page with a menu entry, and Correspondence sends to print', () => {
		const manifest = JSON.parse(
			readFileSync(
				new URL('../../src/manifest.json', import.meta.url),
				'utf8',
			),
		)
		const page = manifest.pages.find((p) => p.id === 'PrintJobs')
		expect(page.component).toBe('PrintJobs')
		expect(manifest.menu.some((m) => m.route === 'PrintJobs')).toBe(true)
		const registry = readFileSync(
			new URL('../../src/registry.js', import.meta.url),
			'utf8',
		)
		expect(registry).toMatch(
			/PrintJobs: \{ kind: 'page', component: PrintJobs \}/,
		)
		const correspondence = readFileSync(
			new URL(
				'../../src/views/correspondence/CorrespondenceIndex.vue',
				import.meta.url,
			),
			'utf8',
		)
		expect(correspondence).toContain('buildPrintRequest')
		expect(correspondence).toContain('/apps/filinq/api/print/batch')
	})
})

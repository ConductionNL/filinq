/**
 * Printing separator sheets from the intake page: the profiles, the case
 * numbers a clerk types, and the PDF the server renders.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/tasks.md#task-2.2
 */

import { readFileSync } from 'node:fs'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	listScanProfiles,
	parseCaseNumbers,
	renderSeparatorSheets,
} from '../../src/services/separatorSheets.js'

const calls = []

vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url) => {
			calls.push(['get', url])
			return Promise.resolve({
				data: { results: [{ id: 'balie', label: 'Balie' }], total: 1 },
			})
		},
		post: (url, body, config) => {
			calls.push(['post', url, body, config])
			return Promise.resolve({ data: 'PDF-BYTES' })
		},
	},
}))

beforeEach(() => {
	calls.length = 0
})

describe('parseCaseNumbers', () => {
	it('splits on new lines, commas and semicolons, trims, and drops blanks and repeats', () => {
		expect(parseCaseNumbers('Z-1\n Z-2 ,Z-3;;\n\nZ-1')).toEqual([
			'Z-1',
			'Z-2',
			'Z-3',
		])
	})

	it('answers an empty list for nothing typed', () => {
		expect(parseCaseNumbers('')).toEqual([])
		expect(parseCaseNumbers(undefined)).toEqual([])
	})
})

describe('listScanProfiles', () => {
	it('reads the declared profiles', async () => {
		const profiles = await listScanProfiles()
		expect(calls[0][1]).toContain('/apps/filinq/api/scan/profiles')
		expect(profiles).toEqual([{ id: 'balie', label: 'Balie' }])
	})
})

describe('renderSeparatorSheets', () => {
	it('posts the profile and the case numbers and asks for the PDF as a blob', async () => {
		const pdf = await renderSeparatorSheets('balie', ['Z-1', 'Z-2'])
		const [verb, url, body, config] = calls[0]
		expect(verb).toBe('post')
		expect(url).toContain('/apps/filinq/api/scan/separators')
		expect(body).toEqual({ profileId: 'balie', caseNumbers: ['Z-1', 'Z-2'] })
		expect(config.responseType).toBe('blob')
		expect(pdf).toBe('PDF-BYTES')
	})
})

describe('the intake page', () => {
	it('offers the print action and its dialog', () => {
		const page = readFileSync('src/views/intake/IntakeIndex.vue', 'utf8')
		expect(page).toContain('SeparatorSheetsDialog')
		expect(page).toContain('data-testid="intake-print-separators"')
		const dialog = readFileSync('src/dialogs/SeparatorSheetsDialog.vue', 'utf8')
		expect(dialog).toContain('renderSeparatorSheets')
		expect(dialog).toContain('inputLabel')
	})
})

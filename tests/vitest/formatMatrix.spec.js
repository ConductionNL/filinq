/**
 * The correspondence form offers what the server can make, from the matrix.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/specs/multi-format-output/spec.md
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@nextcloud/axios', () => ({
	default: {
		get: vi.fn(async (url, config) => ({
			data: {
				formats: {
					pdf: { available: true },
					docx: {
						available: false,
						reason: 'LibreOffice is not available on this server',
					},
				},
				url,
				config,
			},
		})),
	},
}))

const { fetchFormatMatrix, formatOptions, reasonText, usableFormat } =
	await import('../../src/services/formatMatrix.js')

describe('formatOptions', () => {
	it('keeps the server order and disables an unavailable format with its reason', () => {
		const options = formatOptions({
			pdf: { available: true },
			docx: {
				available: false,
				reason: 'LibreOffice is not available on this server',
			},
			html: { available: true },
			email: { available: true },
		})
		expect(options.map((o) => o.value)).toEqual(['pdf', 'docx', 'html', 'email'])
		expect(options[1]).toEqual({
			value: 'docx',
			label: 'DOCX (editable)',
			disabled: true,
			reason: 'LibreOffice is not available on this server',
		})
		expect(options[0].disabled).toBe(false)
		expect(options[0].reason).toBe('')
	})

	it('passes an unknown reason through', () => {
		expect(reasonText('conversion backend offline')).toBe(
			'conversion backend offline',
		)
	})
})

describe('usableFormat', () => {
	const options = formatOptions({
		pdf: { available: true },
		docx: { available: false, reason: 'x' },
	})

	it('keeps an available selection and moves off a disabled one', () => {
		expect(usableFormat('pdf', options)).toBe('pdf')
		expect(usableFormat('docx', options)).toBe('pdf')
	})
})

describe('fetchFormatMatrix', () => {
	it('asks the formats endpoint for the flow', async () => {
		const matrix = await fetchFormatMatrix('correspondence')
		expect(Object.keys(matrix)).toEqual(['pdf', 'docx'])
		const axios = (await import('@nextcloud/axios')).default
		expect(axios.get.mock.calls[0][0]).toContain(
			'/apps/filinq/api/documents/formats',
		)
		expect(axios.get.mock.calls[0][1]).toEqual({
			params: { flow: 'correspondence' },
		})
	})
})

describe('CorrespondenceIndex', () => {
	const source = readFileSync(
		new URL(
			'../../src/views/correspondence/CorrespondenceIndex.vue',
			import.meta.url,
		),
		'utf8',
	)

	it('has no format list of its own', () => {
		expect(source).not.toMatch(/value:\s*'docx'/)
		expect(source).toContain("fetchFormatMatrix('correspondence')")
	})

	it('renders an unavailable format disabled, not hidden', () => {
		expect(source).toContain(':disabled="fmt.disabled"')
	})
})

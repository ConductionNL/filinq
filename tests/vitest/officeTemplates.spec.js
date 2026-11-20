/**
 * The office template client: each call hits the right URL with the right
 * verb and body, multipart bodies carry the file and only filled fields, and
 * the field-mapping helpers keep, change and drop mappings as edited.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const http = vi.hoisted(() => ({ calls: [], answer: () => Promise.resolve({ data: {} }) }))

vi.mock('@nextcloud/axios', () => {
	const record = (method) => (url, body) => {
		http.calls.push([method, url, body])
		return http.answer()
	}
	return { default: { get: record('get'), post: record('post'), put: record('put') } }
})
vi.mock('@nextcloud/router', () => ({
	generateUrl: (path, vars = {}) => path.replace(/\{(\w+)\}/g, (whole, key) => (key in vars ? String(vars[key]) : whole)),
}))

const office = await import('../../src/services/officeTemplates.js')

beforeEach(() => {
	http.calls = []
	http.answer = () => Promise.resolve({ data: { id: 'x' } })
})

/**
 * A multipart body as plain entries.
 *
 * @param {FormData} body The body.
 * @return {Array<Array<string>>}
 */
function entries(body) {
	return [...body.entries()].map(([key, value]) => [key, typeof value === 'string' ? value : 'FILE:' + value.name])
}

describe('office template calls', () => {
	it('uploads a template as multipart with only the filled fields', async () => {
		const file = new File(['docx'], 'brief.docx')
		const result = await office.uploadOfficeTemplate(file, { name: ' Brief ', namespace: 'filinq', category: '', boundSchema: null })

		expect(result).toEqual({ ok: true, data: { id: 'x' } })
		expect(http.calls[0][0]).toBe('post')
		expect(http.calls[0][1]).toBe('/apps/filinq/api/templates/office')
		expect(entries(http.calls[0][2])).toEqual([['file', 'FILE:brief.docx'], ['name', 'Brief'], ['namespace', 'filinq']])
	})

	it('posts a new source with its changelog to the template', async () => {
		await office.uploadNewSource('t-1', new File(['docx'], 'v2.docx'), 'Nieuw logo')

		expect(http.calls[0][1]).toBe('/apps/filinq/api/templates/t-1/office')
		expect(entries(http.calls[0][2])).toEqual([['file', 'FILE:v2.docx'], ['changelog', 'Nieuw logo']])
	})

	it('saves the field mapping with PUT', async () => {
		await office.saveFieldMap('t-1', { naam_aanvrager: 'aanvrager.naam' })

		expect(http.calls).toEqual([['put', '/apps/filinq/api/templates/t-1/field-map', { fieldMap: { naam_aanvrager: 'aanvrager.naam' } }]])
	})

	it('starts an import from a folder without a file, and reads its progress', async () => {
		await office.startImport(null, { namespace: 'filinq', path: 'Sjablonen' })
		await office.fetchImport('job-1')

		expect(entries(http.calls[0][2])).toEqual([['namespace', 'filinq'], ['path', 'Sjablonen']])
		expect(http.calls[1]).toEqual(['get', '/apps/filinq/api/templates/import/job-1', undefined])
	})

	it('answers a refusal with its status and error', async () => {
		http.answer = () => Promise.reject({ response: { status: 422, data: { error: 'Macros are not allowed.' } } })

		expect(await office.uploadOfficeTemplate(new File(['x'], 'm.docm'), { name: 'M', namespace: 'filinq' })).toEqual({ ok: false, status: 422, error: 'Macros are not allowed.' })
	})

	it('builds the source and preview addresses', () => {
		expect(office.sourceUrl('t-1')).toBe('/apps/filinq/api/templates/t-1/office/source')
		expect(office.previewUrl('t-1')).toBe('/apps/filinq/api/templates/t-1/office/preview')
		expect(office.FINISHED_IMPORT).toEqual(['completed', 'failed'])
	})
})

describe('field mapping helpers', () => {
	it('lists the unknown tags with the path already mapped', () => {
		expect(office.unknownTags({ tagReport: { unknown: ['a', 'b'] }, fieldMap: { a: 'x.y', b: 3 } })).toEqual([{ tag: 'a', path: 'x.y' }, { tag: 'b', path: '' }])
		expect(office.unknownTags({})).toEqual([])
	})

	it('changes, keeps and drops mappings as edited', () => {
		expect(office.fieldMapFrom([{ tag: 'a', path: ' x.z ' }, { tag: 'b', path: '' }], { a: 'x.y', b: 'q', c: 'kept' })).toEqual({ a: 'x.z', c: 'kept' })
		expect(office.fieldMapFrom([], null)).toEqual({})
	})
})

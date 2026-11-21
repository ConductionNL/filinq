/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage tests for template-charts.
 *
 * The chart, table and image functions have no page of their own: an author
 * types them into a template and sees the result in the template preview.
 * These tests drive that preview endpoint as the signed-in admin, which is
 * the same request the template editor's preview makes.
 */

// @e2e openspec/specs/template-charts/spec.md#bar-chart-from-register-bound-data-in-preview-and-pdf
// @e2e openspec/specs/template-charts/spec.md#malformed-chart-data-degrades-visibly
// @e2e openspec/specs/template-charts/spec.md#collection-renders-with-selected-formatted-columns
// @e2e openspec/specs/template-charts/spec.md#readable-raster-image-is-embedded
// @e2e openspec/specs/template-charts/spec.md#unreadable-file-degrades-to-a-marker-not-a-bypass
// @e2e openspec/changes/office-charts-and-images/specs/template-charts/spec.md#office-chart-is-native-and-survives-the-cascade
// @e2e openspec/changes/office-charts-and-images/specs/template-charts/spec.md#readable-image-lands-in-the-docx

import type { APIRequestContext } from '@playwright/test'

import { expect, test } from '@playwright/test'
import JSZip from 'jszip'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

const PREVIEW = '/index.php/apps/filinq/api/templates/preview'
const HEADERS = { 'OCS-APIRequest': 'true' }
const API = '/index.php/apps/filinq/api'
const DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'

// The office fixture: `${chart:bezwaren}`, `${image:logo}` and plain tags.
const CHART_TEMPLATE = readFileSync(resolve(process.cwd(), 'tests/sample-documents/chart-template.docx'))

// A 1x1 PNG, small enough to write inline.
const PNG_BASE64 =
	'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='

/**
 * Render template content through the preview endpoint.
 *
 * @param request The signed-in request context.
 * @param content The Twig content.
 * @param data The data context.
 */
async function preview(
	request: APIRequestContext,
	content: string,
	data: Record<string, unknown> = {},
): Promise<string> {
	const res = await request.post(PREVIEW, {
		data: { content, data },
		headers: HEADERS,
	})
	expect(res.status(), await res.text()).toBe(200)
	const body = await res.json()
	return body.html as string
}

test.describe('template-charts: charts, tables and images in the template preview', () => {
	test('a bar chart over the data renders as SVG in the preview', async ({
		request,
	}) => {
		// @e2e openspec/specs/template-charts/spec.md#bar-chart-from-register-bound-data-in-preview-and-pdf
		const html = await preview(
			request,
			'{{ chart("bar", {labels: dossiers|column("naam"), series: [{name: "Bezwaren", values: dossiers|column("aantal")}]}) }}',
			{
				dossiers: [
					{ naam: 'Centrum', aantal: 4 },
					{ naam: 'Noord', aantal: 9 },
				],
			},
		)

		expect(html).toContain('<svg')
		expect(html).toContain('Centrum')
		expect(html).not.toContain('chart error')
	})

	test('chart data without series shows a chart error marker', async ({
		request,
	}) => {
		// @e2e openspec/specs/template-charts/spec.md#malformed-chart-data-degrades-visibly
		const html = await preview(request, '{{ chart("bar", {labels: ["A"]}) }}')

		expect(html).toContain('<svg')
		expect(html).toMatch(/chart error|grafiekfout/)
	})

	test('data_table shows only the chosen columns, in order, formatted', async ({
		request,
	}) => {
		// @e2e openspec/specs/template-charts/spec.md#collection-renders-with-selected-formatted-columns
		const html = await preview(
			request,
			'{{ data_table(rows, [{key: "naam", label: "Naam"}, {key: "datum", label: "Datum", format: "date"}, {key: "bedrag", label: "Bedrag", format: "currency"}]) }}',
			{
				rows: [
					{
						naam: '<b>Centrum</b>',
						datum: '2026-03-01',
						bedrag: 1250.5,
						geheim: 'niet tonen',
					},
				],
			},
		)

		const headers = [...html.matchAll(/<th[^>]*>(.*?)<\/th>/g)].map(
			(match) => match[1],
		)
		expect(headers).toEqual(['Naam', 'Datum', 'Bedrag'])
		expect(html).toContain('&lt;b&gt;Centrum&lt;/b&gt;')
		expect(html).not.toContain('niet tonen')
		expect(html).toContain('01-03-2026')
	})

	test('an image the author can open is embedded as a data URI', async ({
		request,
	}) => {
		// @e2e openspec/specs/template-charts/spec.md#readable-raster-image-is-embedded
		const name = `template-charts-${Date.now()}.png`
		const url = `/remote.php/dav/files/admin/${name}`
		const put = await request.put(url, {
			data: Buffer.from(PNG_BASE64, 'base64'),
		})
		expect([201, 204]).toContain(put.status())

		const found = await request.fetch(url, {
			method: 'PROPFIND',
			headers: { Depth: '0', 'Content-Type': 'application/xml' },
			data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
		})
		const fileId = (await found.text()).match(
			/<oc:fileid>(\d+)<\/oc:fileid>/,
		)?.[1]
		expect(fileId).toBeTruthy()

		try {
			const html = await preview(
				request,
				'{{ nc_image(id, {alt: "Logo"}) }}',
				{ id: Number(fileId) },
			)
			expect(html).toContain(`src="data:image/png;base64,${PNG_BASE64}"`)
			expect(html).toContain('alt="Logo"')
		} finally {
			await request.delete(url)
		}
	})

	test('a file id the author cannot open becomes a marker with no file content', async ({
		request,
	}) => {
		// @e2e openspec/specs/template-charts/spec.md#unreadable-file-degrades-to-a-marker-not-a-bypass
		const html = await preview(request, '{{ nc_image(999999999) }}')

		expect(html).toMatch(/\[(image unavailable|afbeelding niet beschikbaar): /)
		expect(html).not.toContain('data:image')
	})
})

test.describe('office-charts-and-images: native charts and images in a generated DOCX', () => {
	test('an office generation carries a native chart part and the image at its placeholder', async ({
		request,
	}) => {
		// @e2e openspec/changes/office-charts-and-images/specs/template-charts/spec.md#office-chart-is-native-and-survives-the-cascade
		// @e2e openspec/changes/office-charts-and-images/specs/template-charts/spec.md#readable-image-lands-in-the-docx
		const stamp = `e2echart-${Date.now()}`
		const logo = `/remote.php/dav/files/admin/${stamp}.png`
		const put = await request.put(logo, { data: Buffer.from(PNG_BASE64, 'base64') })
		expect([201, 204]).toContain(put.status())
		const found = await request.fetch(logo, {
			method: 'PROPFIND',
			headers: { Depth: '0', 'Content-Type': 'application/xml' },
			data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
		})
		const fileId = Number((await found.text()).match(/<oc:fileid>(\d+)<\/oc:fileid>/)?.[1])
		expect(fileId).toBeGreaterThan(0)

		let templateId = ''
		try {
			const created = await request.post(`${API}/templates/office`, {
				headers: HEADERS,
				multipart: { file: { name: `${stamp}.docx`, mimeType: DOCX, buffer: CHART_TEMPLATE }, name: stamp, namespace: 'filinq' },
			})
			const body = await created.json()
			expect(created.status(), JSON.stringify(body)).toBe(201)
			templateId = String(body.template.id)
			const published = await request.post(`${API}/templates/${templateId}/publish`, { headers: HEADERS })
			expect(published.ok(), await published.text()).toBeTruthy()

			const generated = await request.post(`${API}/documents/generate`, {
				headers: HEADERS,
				data: {
					templateId,
					dataRefs: [],
					filename: `${stamp}-document`,
					options: {
						format: 'docx',
						adHocData: {
							zaak: { nummer: 'Z-2026-1', status: 'open' },
							besluiten: [],
							charts: { bezwaren: { type: 'bar', data: { labels: ['Q1', 'Q2'], series: [{ name: 'Bezwaren', values: [4, 7] }] } } },
							images: { logo: { fileId, width: 80, height: 80 } },
						},
					},
				},
			})
			expect(generated.status(), (await generated.body()).toString('utf8', 0, 300)).toBe(200)

			const zip = await JSZip.loadAsync(await generated.body())
			const names = Object.keys(zip.files)
			expect(names.filter((name) => /^word\/charts\/chart\d+\.xml$/.test(name))).toHaveLength(1)
			expect(names.some((name) => name.startsWith('word/media/'))).toBe(true)
			const document = await zip.file('word/document.xml')!.async('string')
			expect(document).not.toContain('chart:bezwaren')
			expect(document).not.toContain('image:logo')
			expect(document).toContain('Z-2026-1')
		} finally {
			if (templateId !== '') {
				await request.delete(`${API}/templates/${templateId}`, { headers: HEADERS }).catch(() => undefined)
			}
			await request.delete(logo)
		}
	})
})

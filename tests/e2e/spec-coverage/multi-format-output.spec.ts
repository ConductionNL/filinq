/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: multi-format-output.
 *
 * The generation half runs against the live API with a template this file
 * creates. Whether DOCX can be made depends on LibreOffice on the instance,
 * so the test reads the matrix first and holds the manifest to it: a DOCX
 * entry is generated when the matrix offers docx, and failed with the
 * matrix's own reason when it does not. The correspondence half answers the
 * matrix request with a route handler, so the disabled state is shown on any
 * instance.
 */

// @e2e openspec/specs/document-creatie-sjablonen/spec.md#pdf-and-docx-from-one-render
// @e2e openspec/specs/multi-format-output/spec.md#correspondence-view-disables-an-unavailable-format
// @e2e openspec/specs/multi-format-output/spec.md#available-formats-come-from-the-api-not-the-bundle

import { expect, test } from '@playwright/test'
import { dismissOverlays, waitForNcContentReady } from './_helpers.ts'

const API = '/index.php/apps/filinq/api'
const HEADERS = { 'OCS-APIRequest': 'true' }

test.describe('multi-format-output', () => {
	test('one generation files a PDF and a DOCX from the same render', async ({ request }) => {
		// @e2e openspec/specs/document-creatie-sjablonen/spec.md#pdf-and-docx-from-one-render
		const matrixRes = await request.get(`${API}/documents/formats`, { headers: HEADERS })
		expect(matrixRes.status()).toBe(200)
		expect(matrixRes.headers()['cache-control']).toContain('no-store')
		const matrix = (await matrixRes.json()).formats

		const created = await request.post(`${API}/templates`, {
			data: { name: 'e2e multi-format', content: '<h1>Besluit</h1><p>{{ naam }}</p>' },
			headers: HEADERS,
		})
		expect(created.ok(), await created.text()).toBeTruthy()
		const template = await created.json()
		const templateId = template.id ?? template.uuid

		try {
			const res = await request.post(`${API}/documents/generate`, {
				data: {
					templateId,
					dataRefs: [],
					filename: 'e2e-besluit',
					options: { formats: ['pdf', 'docx'], adHocData: { naam: 'Demostad' } },
				},
				headers: HEADERS,
			})
			expect(res.status(), await res.text()).toBe(200)
			const body = await res.json()
			expect(body.outputs.map((o: { format: string }) => o.format)).toEqual(['pdf', 'docx'])
			expect(body.outputs[0].status).toBe('generated')
			expect(body.outputs[0].downloadUrl).toContain('/remote.php/dav/files/')

			const docx = body.outputs[1]
			if (matrix.docx.available) {
				expect(docx.status).toBe('generated')
				const file = await request.get(docx.downloadUrl)
				expect(file.ok()).toBeTruthy()
				// A real WordprocessingML package: a ZIP holding word/document.xml.
				const bytes = await file.body()
				expect(bytes.subarray(0, 2).toString()).toBe('PK')
				expect(bytes.includes(Buffer.from('word/document.xml'))).toBeTruthy()
			} else {
				expect(docx.status).toBe('failed')
				expect(docx.error).toBe(matrix.docx.reason)
			}
		} finally {
			await request.delete(`${API}/templates/${templateId}`, { headers: HEADERS })
		}
	})

	test('the correspondence view offers what the matrix offers and disables the rest', async ({ page }) => {
		// @e2e openspec/specs/multi-format-output/spec.md#correspondence-view-disables-an-unavailable-format
		// @e2e openspec/specs/multi-format-output/spec.md#available-formats-come-from-the-api-not-the-bundle
		await page.route('**/apps/filinq/api/documents/formats**', async (route) => {
			await route.fulfill({
				json: {
					formats: {
						pdf: { available: true },
						docx: { available: false, reason: 'LibreOffice is not available on this server' },
						html: { available: true },
						email: { available: true },
					},
				},
			})
		})
		await page.goto('/index.php/apps/filinq/correspondence')
		await waitForNcContentReady(page)
		await dismissOverlays(page)

		const radios = page.locator('.correspondence-index__radio-group input[type="radio"]')
		await expect(radios).toHaveCount(4)
		await expect(radios.nth(1)).toBeDisabled()
		await expect(page.locator('#corr-format-reason-docx')).toContainText('LibreOffice')
		await expect(radios.nth(0)).toBeEnabled()
	})
})

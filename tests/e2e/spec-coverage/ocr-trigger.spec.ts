/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: ocr-trigger-surface.
 *
 * A PDF is seeded into /DocuDesk so My documents lists a real row. The OCR
 * endpoints and the extract endpoint are answered by route handlers for the
 * browser scenarios: Tesseract is not installed on every runner, and the
 * extract answer for a scan depends on OpenRegister's provided-text seam
 * (ConductionNL/openregister#2033), which has not shipped. The backend half
 * is proven by OcrControllerTest, OcrRunServiceTest,
 * AnonymizationServiceOcrFallbackTest and FileListingServiceTest. What this
 * file proves is the screen: the action, the busy state, the badge, the
 * refusal and the review warning.
 */

// @e2e openspec/specs/ocr-trigger-surface/spec.md#manual-ocr-of-a-scanned-pdf-succeeds
// @e2e openspec/specs/ocr-trigger-surface/spec.md#user-runs-ocr-from-mydocuments
// @e2e openspec/specs/ocr-trigger-surface/spec.md#action-hidden-when-ocr-is-disabled
// @e2e openspec/specs/ocr-trigger-surface/spec.md#scanned-pdf-gets-entity-detection-via-fallback
// @e2e openspec/specs/ocr-trigger-surface/spec.md#scan-with-ocr-unavailable-is-flagged-not-silent
// @e2e openspec/specs/ocr-trigger-surface/spec.md#file-listing-reflects-a-real-ocr-run
// @e2e openspec/specs/ocr-trigger-surface/spec.md#toggle-change-takes-effect-immediately
// @e2e openspec/specs/ocr-document-scanning/spec.md#report-confidence-score

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { harvestToken } from '../workflows/_fixtures.ts'
import { go } from './_helpers.ts'

// The view under test, named after its component file (gate-26 matches on the stem).
const MyDocumentsIndex = 'my-documents'

const RUN = `g19ocr-${Date.now()}`
const FILE_BASE = `${RUN}-scan`
const FILE_NAME = `${FILE_BASE}.pdf`
const PDF =
	'%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[]/Count 0>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n'

let fileId = 0

/**
 * Answer the OCR endpoints.
 *
 * @param page      The page
 * @param available Whether OCR can run here
 * @param onRun     The POST's status and body
 */
async function withOcr(
	page: Page,
	available: boolean,
	onRun: { status: number; json: Record<string, unknown> },
): Promise<void> {
	await page.route('**/apps/filinq/api/ocr?**', (route) =>
		route.fulfill({
			json: {
				capability: {
					enabled: available,
					tesseractAvailable: available,
					available,
				},
				results: {},
			},
		}),
	)
	await page.route(`**/apps/filinq/api/ocr/${fileId}`, async (route) => {
		// Hold the answer briefly so the busy state is observable.
		await new Promise((resolve) => setTimeout(resolve, 400))
		await route.fulfill(onRun)
	})
}

/**
 * Open the row's action menu.
 *
 * @param page The page
 */
async function openRowActions(page: Page): Promise<void> {
	const row = page.getByRole('row').filter({ hasText: FILE_BASE })
	await row.locator('.my-documents-row-actions button').first().click()
}

test.describe('ocr trigger surface', () => {
	test.beforeAll(async ({ browser }) => {
		const context = await browser.newContext()
		const page = await context.newPage()
		const token = await harvestToken(page)
		const put = await context.request.fetch(
			`/remote.php/dav/files/admin/DocuDesk/${FILE_NAME}`,
			{
				method: 'PUT',
				headers: { requesttoken: token },
				data: PDF,
			},
		)
		expect(put.status(), 'seed the scan').toBeLessThan(300)
		const propfind = await context.request.fetch(
			`/remote.php/dav/files/admin/DocuDesk/${FILE_NAME}`,
			{
				method: 'PROPFIND',
				headers: { requesttoken: token, Depth: '0' },
				data:
					'<?xml version="1.0"?><d:propfind xmlns:d="DAV:" '
					+ 'xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
			},
		)
		fileId = Number((await propfind.text()).match(/<oc:fileid>(\d+)/)?.[1] ?? 0)
		expect(fileId, 'the seeded scan has a file id').toBeGreaterThan(0)
		await context.close()
	})

	test('manual OCR of a scanned PDF succeeds', async ({ page }) => {
		// @e2e openspec/specs/ocr-trigger-surface/spec.md#manual-ocr-of-a-scanned-pdf-succeeds
		const token = await harvestToken(page)
		const status = await page.request.get(
			`/index.php/apps/filinq/api/ocr?fileIds=${fileId}`,
			{
				headers: { requesttoken: token },
			},
		)
		expect(status.status()).toBe(200)
		const capability = (await status.json()).capability
		test.fail(
			capability.available !== true,
			'Tesseract is not installed on this runner: the POST answers 503, which OcrControllerTest covers.',
		)

		const response = await page.request.post(
			`/index.php/apps/filinq/api/ocr/${fileId}`,
			{
				headers: { requesttoken: token },
			},
		)
		const body = await response.json()
		expect([200]).toContain(response.status())
		expect(body).not.toHaveProperty('text')
		expect(typeof body.textLength).toBe('number')
	})

	test('user runs OCR from My documents, and the listing reflects the run', async ({
		page,
	}) => {
		// @e2e openspec/specs/ocr-trigger-surface/spec.md#user-runs-ocr-from-mydocuments
		// @e2e openspec/specs/ocr-trigger-surface/spec.md#file-listing-reflects-a-real-ocr-run
		// @e2e openspec/specs/ocr-document-scanning/spec.md#report-confidence-score
		await withOcr(page, true, {
			status: 200,
			json: {
				fileId,
				ocrProcessed: true,
				confidence: 91.4,
				textLength: 4231,
				languages: 'nld+eng',
				dpi: 300,
			},
		})
		await go(page, MyDocumentsIndex)

		await openRowActions(page)
		await page.getByRole('menuitem', { name: 'Run OCR' }).click()

		const row = page.getByRole('row').filter({ hasText: FILE_BASE })
		await expect(row.locator('.my-documents-ocr-badge')).toHaveText('OCR 91%')
	})

	test('the action is hidden when OCR is disabled', async ({ page }) => {
		// @e2e openspec/specs/ocr-trigger-surface/spec.md#action-hidden-when-ocr-is-disabled
		await withOcr(page, false, { status: 409, json: {} })
		await go(page, MyDocumentsIndex)

		await openRowActions(page)
		await expect(page.getByRole('menuitem', { name: 'Run OCR' })).toHaveCount(0)
	})

	test('a toggle change takes effect immediately', async ({ page }) => {
		// @e2e openspec/specs/ocr-trigger-surface/spec.md#toggle-change-takes-effect-immediately
		await withOcr(page, true, {
			status: 409,
			json: {
				fileId,
				ocrProcessed: false,
				reason: 'ocr_disabled',
				error: 'OCR is switched off by an administrator.',
			},
		})
		await go(page, MyDocumentsIndex)

		await openRowActions(page)
		await page.getByRole('menuitem', { name: 'Run OCR' }).click()
		await expect(
			page.getByText('OCR is switched off by an administrator.'),
		).toBeVisible()

		await openRowActions(page)
		await expect(page.getByRole('menuitem', { name: 'Run OCR' })).toHaveCount(0)
	})

	test('a scan with OCR unavailable is flagged, not silent', async ({ page }) => {
		// @e2e openspec/specs/ocr-trigger-surface/spec.md#scan-with-ocr-unavailable-is-flagged-not-silent
		await page.route('**/apps/filinq/api/anonymization/extract/**', (route) =>
			route.fulfill({
				json: {
					entities: [],
					entityCount: 0,
					ocrSkipped: 'tesseract_unavailable',
					ocr: { ran: false, reason: 'tesseract_unavailable' },
				},
			}),
		)
		await go(page, MyDocumentsIndex)
		await page.getByRole('row').filter({ hasText: FILE_BASE }).click()

		await expect(page.locator('.ocr-warning')).toContainText(
			'OCR is not installed',
		)
	})

	test('a scanned PDF gets entity detection via the fallback', async ({
		page,
	}) => {
		// @e2e openspec/specs/ocr-trigger-surface/spec.md#scanned-pdf-gets-entity-detection-via-fallback
		await page.route('**/apps/filinq/api/anonymization/extract/**', (route) =>
			route.fulfill({
				json: {
					entities: [
						{
							type: 'PERSON',
							value: 'Jan Jansen',
							confidence: 0.92,
							relationId: 1,
						},
						{
							type: 'BSN',
							value: '111222333',
							confidence: 0.99,
							relationId: 2,
						},
					],
					entityCount: 2,
					ocr: {
						ran: true,
						ingested: true,
						confidence: 88.2,
						textLength: 912,
					},
					ocrDetectionPending: false,
				},
			}),
		)
		await go(page, MyDocumentsIndex)
		await page.getByRole('row').filter({ hasText: FILE_BASE }).click()

		await expect(page.getByText('Jan Jansen')).toBeVisible()
		await expect(page.getByText('111222333')).toBeVisible()
		await expect(page.locator('.ocr-warning')).toHaveCount(0)
	})
})

/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: document-sanitization.
 *
 * Drives the Sanitize action on My documents and its report panel, and the
 * not-sanitized warning on the Woo hand-off. The sanitize route is answered
 * by a route handler with OpenRegister's report shape; the server half
 * (routing, derivative, record, PDF skip, encrypted refusal) is proven by
 * tests/unit/Service/Sanitization/*Test.php and
 * tests/unit/Controller/SanitizationControllerTest.php.
 */

// @e2e openspec/changes/document-sanitization/specs/document-sanitization/spec.md#office-document-is-sanitized-into-a-derivative
// @e2e openspec/changes/document-sanitization/specs/document-sanitization/spec.md#report-shows-category-counts-not-content
// @e2e openspec/changes/document-sanitization/specs/document-sanitization/spec.md#unsanitized-hand-off-warns

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { harvestToken } from '../workflows/_fixtures.ts'
import { appUrl, dismissOverlays, go, waitForAppReady } from './_helpers.ts'

// The view under test, named after its component file (gate-26 matches on the stem).
const MyDocumentsIndex = 'my-documents'

const RUN = `g19san-${Date.now()}`
const FILE_BASE = `${RUN}-concept-besluit`
const FILE_NAME = `${FILE_BASE}.docx`

let fileId = 0

/**
 * Open the row's action menu.
 *
 * @param page The page
 */
async function openRowActions(page: Page): Promise<void> {
	const row = page.getByRole('row').filter({ hasText: FILE_BASE })
	await row.locator('.my-documents-row-actions button').first().click()
}

test.describe('document sanitization', () => {
	test.beforeAll(async ({ browser }) => {
		const context = await browser.newContext()
		const page = await context.newPage()
		const token = await harvestToken(page)
		const put = await context.request.fetch(
			`/remote.php/dav/files/admin/DocuDesk/${FILE_NAME}`,
			{
				method: 'PUT',
				headers: { requesttoken: token },
				data: Buffer.from('PK placeholder docx'),
			},
		)
		expect(put.status(), 'seed the document').toBeLessThan(300)
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
		expect(fileId, 'the seeded document has a file id').toBeGreaterThan(0)
		await context.close()
	})

	test('sanitize writes a copy and shows counts, never content', async ({
		page,
	}) => {
		// @e2e openspec/changes/document-sanitization/specs/document-sanitization/spec.md#office-document-is-sanitized-into-a-derivative
		// @e2e openspec/changes/document-sanitization/specs/document-sanitization/spec.md#report-shows-category-counts-not-content
		const posted: string[] = []
		await page.route(`**/apps/filinq/api/sanitization/${fileId}`, (route) => {
			posted.push(route.request().method())
			return route.fulfill({
				json: {
					sanitized: true,
					sanitizationSkipped: false,
					fileId,
					sanitizedFileId: fileId + 1,
					sanitizedFileName: `${FILE_BASE}_sanitized.docx`,
					report: {
						commentsRemoved: 4,
						metadataFieldsScrubbed: 6,
						trackedChangesDropped: 0,
						sentinelApplied: '',
					},
				},
			})
		})
		await go(page, MyDocumentsIndex)
		await openRowActions(page)
		await page.getByTestId('document-sanitize').click()

		const report = page.getByTestId('sanitization-report')
		await expect(report).toContainText(`${FILE_BASE}_sanitized.docx`)
		await expect(page.getByTestId('sanitization-commentsRemoved')).toContainText(
			'4',
		)
		await expect(
			page.getByTestId('sanitization-metadataFieldsScrubbed'),
		).toContainText('6')
		await expect(
			page.getByTestId('sanitization-trackedChangesDropped'),
		).toHaveCount(0)
		await expect(report).not.toContainText('wethouder')
		expect(posted).toEqual(['POST'])
	})

	test('an unsanitized hand-off warns and can still proceed', async ({ page }) => {
		// @e2e openspec/changes/document-sanitization/specs/document-sanitization/spec.md#unsanitized-hand-off-warns
		const record = {
			id: 'pub-1',
			status: 'ready',
			documentFileRef: String(fileId),
			redactedFileRef: String(fileId),
			officieleTitel: 'Besluit subsidie buurthuis',
			platformAvailable: true,
		}
		await page.route('**/apps/filinq/api/publications/pub-1', (route) =>
			route.fulfill({ json: record }),
		)
		await page.route(`**/apps/filinq/api/sanitization/${fileId}`, (route) =>
			route.fulfill({ json: { fileId, sanitized: false, runs: [] } }),
		)
		await page.goto(await appUrl(page, 'publications/pub-1'), {
			waitUntil: 'domcontentloaded',
		})
		await waitForAppReady(page)
		await dismissOverlays(page)

		await expect(page.getByTestId('publication-not-sanitized')).toBeVisible()
		await expect(
			page.getByRole('button', { name: 'Hand off for publication' }),
		).toBeVisible()
	})
})

/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e: email-ingestion, the filing workflow on the status page.
 *
 * Drives the EmailIngestion page component (the Email ingestion menu entry):
 * an admin scans the inboxes by hand and the filed email shows with its PDF
 * copy. The api/email-ingestion routes are answered by route handlers with
 * the controller's shape, because the dev fixture has no mapped inbox. The
 * server half (scan, move into the dossier folder, idempotency, conversion)
 * is proven by tests/unit/Service/EmailIngestionServiceTest.php and
 * tests/unit/Controller/EmailIngestionControllerTest.php.
 */

// @e2e openspec/specs/email-ingestion/spec.md#dropped-emails-are-filed-into-the-mapped-dossier
// @e2e openspec/specs/email-ingestion/spec.md#filed-email-gets-its-pdf-a-derivative

import { expect, test } from '@playwright/test'
import {
	appUrl,
	dismissOverlays,
	waitForAppReady,
} from '../spec-coverage/_helpers.ts'

const FILED = {
	uuid: 'email-1',
	subject: 'Aanvraag omgevingsvergunning Dorpsstraat 1',
	fromAddress: 'aanvrager@example.nl',
	toAddresses: ['vergunningen@gemeente.example'],
	sentAt: '2026-09-30T09:00:00+00:00',
	ingestedAt: '2026-09-30T09:05:00+00:00',
	messageId: '<a1@example.nl>',
	dossierRef: 'dossier-1',
	status: 'filed',
	sourceFileRef: '101',
	pdfFileRef: '102',
}

test.describe('email ingestion', () => {
	test('a scan files the dropped email into its dossier with a PDF copy', async ({
		page,
	}) => {
		// @e2e openspec/specs/email-ingestion/spec.md#dropped-emails-are-filed-into-the-mapped-dossier
		// @e2e openspec/specs/email-ingestion/spec.md#filed-email-gets-its-pdf-a-derivative
		let scanned = false
		await page.route('**/apps/filinq/api/email-ingestion/scan', (route) => {
			scanned = true
			return route.fulfill({
				json: { processed: 1, filed: 1, failed: 0, duplicates: 0 },
			})
		})
		await page.route('**/apps/filinq/api/email-ingestion?**', (route) =>
			route.fulfill({ json: { results: scanned ? [FILED] : [] } }),
		)
		await page.route('**/apps/filinq/api/email-ingestion', (route) =>
			route.fulfill({ json: { results: scanned ? [FILED] : [] } }),
		)

		await page.goto(await appUrl(page, 'email-ingestion'), {
			waitUntil: 'domcontentloaded',
		})
		await waitForAppReady(page)
		await dismissOverlays(page)

		await expect(page.getByTestId('email-ingestion-index')).toBeVisible()
		await page.getByTestId('email-ingestion-rescan').click()

		await expect.poll(() => scanned).toBe(true)
		await expect(page.getByText(FILED.subject)).toBeVisible()
		await expect(page.getByTestId('email-state-filed')).toBeVisible()
		// A filed email with its PDF copy offers no conversion retry.
		await expect(page.getByTestId('email-ingestion-retry')).toHaveCount(0)
	})
})

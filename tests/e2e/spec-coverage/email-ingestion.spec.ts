/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: email-ingestion.
 *
 * Drives the EmailIngestion page component: a filed email whose PDF copy
 * failed is converted again, a dropped .msg shows as a failure with what to
 * do, and the status filter narrows the list. The api/email-ingestion routes
 * are answered by route handlers with the controller's shape; the server half
 * is proven by tests/unit/Service/EmailIngestionServiceTest.php
 * (testConversionOutageStillCapturesTheMail,
 * testMsgIsAVisibleFailureThatStaysInTheInbox) and
 * tests/unit/Controller/EmailIngestionControllerTest.php.
 */

// @e2e openspec/specs/email-ingestion/spec.md#conversion-outage-still-captures-the-mail
// @e2e openspec/specs/email-ingestion/spec.md#a-dropped-msg-is-a-visible-failure
// @e2e openspec/specs/email-ingestion/spec.md#status-page-shows-filed-and-failed-rows

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { appUrl, dismissOverlays, waitForAppReady } from './_helpers.ts'

// The view under test, named after its component file (gate-26 matches on the stem).
const EmailIngestion = 'email-ingestion'

const NOT_CONVERTED = {
	uuid: 'email-2',
	subject: 'Zienswijze bestemmingsplan',
	fromAddress: 'inwoner@example.nl',
	status: 'filed',
	sourceFileRef: '201',
	pdfFileRef: null,
	ingestedAt: '2026-09-30T10:00:00+00:00',
	dossierRef: 'dossier-1',
}

const MSG_FAILED = {
	uuid: 'email-3',
	status: 'failed',
	failureReason: 'unsupported-format',
	sourceFileRef: '301',
	ingestedAt: '2026-09-30T10:01:00+00:00',
	dossierRef: 'dossier-1',
}

/**
 * Open the status page.
 *
 * @param page The page
 */
async function goStatus(page: Page): Promise<void> {
	await page.goto(await appUrl(page, EmailIngestion), {
		waitUntil: 'domcontentloaded',
	})
	await waitForAppReady(page)
	await dismissOverlays(page)
}

test.describe('email ingestion status', () => {
	test('a filed email without its PDF copy is converted again', async ({
		page,
	}) => {
		// @e2e openspec/specs/email-ingestion/spec.md#conversion-outage-still-captures-the-mail
		let converted = false
		await page.route(
			'**/apps/filinq/api/email-ingestion/email-2/convert',
			(route) => {
				converted = true
				return route.fulfill({
					json: { ...NOT_CONVERTED, pdfFileRef: '202' },
				})
			},
		)
		await page.route('**/apps/filinq/api/email-ingestion', (route) =>
			route.fulfill({
				json: {
					results: [
						converted
							? { ...NOT_CONVERTED, pdfFileRef: '202' }
							: NOT_CONVERTED,
					],
				},
			}),
		)
		await goStatus(page)

		await expect(page.getByTestId('email-state-not-converted')).toBeVisible()
		await page.getByTestId('email-ingestion-retry').click()
		await expect.poll(() => converted).toBe(true)
		await expect(page.getByTestId('email-state-filed')).toBeVisible()
	})

	test('a dropped .msg shows as a failure with what to do', async ({ page }) => {
		// @e2e openspec/specs/email-ingestion/spec.md#a-dropped-msg-is-a-visible-failure
		await page.route('**/apps/filinq/api/email-ingestion', (route) =>
			route.fulfill({ json: { results: [MSG_FAILED] } }),
		)
		await goStatus(page)

		await expect(page.getByTestId('email-state-failed')).toBeVisible()
		await expect(page.getByTestId('email-failure-reason')).toContainText('.eml')
	})

	test('the status page shows filed and failed rows and filters on status', async ({
		page,
	}) => {
		// @e2e openspec/specs/email-ingestion/spec.md#status-page-shows-filed-and-failed-rows
		await page.route('**/apps/filinq/api/email-ingestion', (route) =>
			route.fulfill({ json: { results: [NOT_CONVERTED, MSG_FAILED] } }),
		)
		await goStatus(page)

		await expect(page.getByTestId('email-state-not-converted')).toBeVisible()
		await expect(page.getByTestId('email-state-failed')).toBeVisible()

		await page.getByTestId('email-ingestion-status-filter').click()
		await page.getByRole('option', { name: 'Failed' }).click()
		await expect(page.getByTestId('email-state-not-converted')).toHaveCount(0)
		await expect(page.getByTestId('email-state-failed')).toBeVisible()
	})
})

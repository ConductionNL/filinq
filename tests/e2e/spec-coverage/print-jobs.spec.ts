/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: print-jobs-in-the-app.
 *
 * The print endpoints are answered by route handlers here: rendering three
 * real letters needs a template and three recipient objects, which the
 * backend suite covers (PrintJobServiceTest, PrintJobControllerTest). What
 * this file proves is the browser half: Send to print posts ONE batch with
 * three items, and the Print jobs page shows the job's status and when it
 * last changed.
 */

// @e2e openspec/specs/print-preview/spec.md#three-letters-go-to-print-as-one-job
// @e2e openspec/specs/print-preview/spec.md#the-print-service-reports-back

import { expect, test } from '@playwright/test'
import { go } from './_helpers.ts'

const PrintJobs = 'print-jobs'
const Correspondence = 'correspondence'

/**
 * One job as GET api/print/jobs lists it.
 *
 * @param status The stored status
 * @return The job
 */
function job(status: string) {
	return {
		uuid: 'job-1',
		filename: 'brief-Z-1.pdf',
		status,
		total: 3,
		rendered: 3,
		requestedAt: '2026-09-29T08:00:00+00:00',
		statusChangedAt: '2026-09-29T09:30:00+00:00',
	}
}

test.describe('print jobs', () => {
	test('three letters go to print as one job', async ({ page }) => {
		// @e2e openspec/specs/print-preview/spec.md#three-letters-go-to-print-as-one-job
		const posted: Array<Record<string, unknown>> = []
		await page.route('**/apps/filinq/api/print/batch', async (route) => {
			posted.push(route.request().postDataJSON())
			await route.fulfill({
				status: 201,
				json: { jobId: 'job-1', status: 'queued', total: 3 },
			})
		})
		await page.route('**/apps/filinq/api/print/jobs', (route) =>
			route.fulfill({ json: { results: [job('queued')] } }),
		)

		await go(page, Correspondence)
		await page.getByLabel('Template UUID').fill('tmpl-1')
		await page.getByRole('button', { name: /Batch/ }).click()
		await page.getByLabel('Register slug').fill('brp')
		await page.getByLabel('Schema slug').fill('persoon')
		await page.locator('#corr-recipients').fill('a\nb\nc')
		await page.getByRole('button', { name: 'Send to print' }).click()

		await expect(
			page.getByText('Your letters went to print as one job.'),
		).toBeVisible()
		expect(posted).toHaveLength(1)
		expect((posted[0].items as unknown[]).length).toBe(3)

		await page.getByRole('link', { name: 'Open print jobs' }).click()
		const row = page.locator('tr[data-status="queued"]')
		await expect(row).toContainText('Waiting for the printer')
		await expect(row).toContainText('3 of 3')
	})

	test('the print service reports back', async ({ page }) => {
		// @e2e openspec/specs/print-preview/spec.md#the-print-service-reports-back
		await page.route('**/apps/filinq/api/print/jobs', (route) =>
			route.fulfill({ json: { results: [job('printed')] } }),
		)

		await go(page, PrintJobs)
		const row = page.locator('tr[data-status="printed"]')
		await expect(row).toContainText('Printed')
		const changed = await page.evaluate(() =>
			new Date('2026-09-29T09:30:00+00:00').toLocaleString(),
		)
		await expect(row).toContainText(changed)
		await expect(row.getByRole('link', { name: /Download/ })).toBeVisible()
	})
})

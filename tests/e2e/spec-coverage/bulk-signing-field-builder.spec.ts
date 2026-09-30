/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: bulk-signing-field-builder, bulk send.
 *
 * The batch endpoints are answered by route handlers: creating 47 real
 * signing requests needs a document and 47 signer records, which the backend
 * suite covers (tests/unit/Service/BulkSigning/BulkSigningServiceTest.php
 * reads the same 50-row fixture). What this file proves is the browser half:
 * the list is uploaded with the form's document, the report names the three
 * rejected rows before anything is sent, and Send posts the confirm.
 */

// @e2e openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#scenario-mixed-csv-yields-a-report-then-a-partial-batch

import { expect, test } from '@playwright/test'
import { readFileSync } from 'node:fs'
import { go } from './_helpers.ts'

const rejected = [
	{ row: 4, reason: 'invalid-email', detail: '' },
	{ row: 18, reason: 'invalid-email', detail: '' },
	{ row: 43, reason: 'invalid-email', detail: '' },
]

/**
 * The batch as the endpoints answer it.
 *
 * @param status The batch status
 * @param processedRows Rows worked through
 * @return The batch
 */
function batch(status: string, processedRows = 0) {
	return {
		uuid: 'batch-1',
		title: 'verklaring.pdf',
		status,
		totalRows: 50,
		acceptedRows: 47,
		processedRows,
		rejectedRows: rejected,
		requestRefs: [],
	}
}

test.describe('bulk send', () => {
	test('a mixed list yields a report, then a partial batch', async ({ page }) => {
		// @e2e openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#scenario-mixed-csv-yields-a-report-then-a-partial-batch
		let uploaded = ''
		let confirmed = false
		await page.route('**/apps/filinq/api/signing/batches', async (route) => {
			uploaded = route.request().postData() || ''
			await route.fulfill({ status: 201, json: batch('ready') })
		})
		await page.route(
			'**/apps/filinq/api/signing/batches/batch-1/confirm',
			async (route) => {
				confirmed = true
				await route.fulfill({ json: batch('creating') })
			},
		)
		await page.route('**/apps/filinq/api/signing/batches/batch-1', (route) =>
			route.fulfill({ json: batch('completed_with_errors', 47) }),
		)

		await go(page, 'signing/new')
		await page.getByLabel('Document File ID').fill('4711')
		await page.getByLabel('Document Name').fill('verklaring.pdf')
		await page.getByRole('button', { name: 'Send to many from a list' }).click()
		await page.getByLabel('Recipient list').setInputFiles({
			name: 'recipients-50.csv',
			mimeType: 'text/csv',
			buffer: readFileSync('tests/fixtures/bulk-signing/recipients-50.csv'),
		})
		await page.getByRole('button', { name: 'Check the list' }).click()

		await expect(
			page.getByText('47 of 50 rows can be sent. 3 rows are left out.'),
		).toBeVisible()
		const rows = page
			.getByRole('table', { name: 'Rows left out' })
			.getByRole('row')
		await expect(rows).toHaveCount(4)
		await expect(rows.nth(1)).toContainText('Not a valid e-mail address')
		expect(uploaded).toContain('name="documentFileId"')
		expect(confirmed).toBe(false)

		await page.getByRole('button', { name: 'Send to 47 recipients' }).click()
		expect(confirmed).toBe(true)
		await expect(page.getByText('47 of 47 rows processed')).toBeVisible({
			timeout: 10000,
		})
	})
})

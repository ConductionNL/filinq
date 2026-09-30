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

test.describe('field placement', () => {
	test('a field placed on page 3 is sent with the request', async ({ page }) => {
		// @e2e openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#scenario-a-placed-field-appears-in-the-artifact-at-its-position
		// The browser half: the page preview, the click that places the box,
		// and the placement the request carries. That the native artifact draws
		// it inside the MAC is proven by
		// tests/unit/Service/Signing/FieldPlacementRenderingTest.php.
		let created: Record<string, unknown> = {}
		await page.route(
			'**/apps/filinq/api/documents/4711/versions/0/download',
			(route) =>
				route.fulfill({
					contentType: 'application/pdf',
					body: readFileSync('tests/fixtures/signing/three-pages.pdf'),
				}),
		)
		await page.route('**/apps/filinq/api/signing/requests', async (route) => {
			created = route.request().postDataJSON()
			await route.fulfill({
				status: 201,
				json: { id: 'req-1', status: 'PENDING' },
			})
		})

		await go(page, 'signing/new')
		await page.getByLabel('Document File ID').fill('4711')
		await page.getByLabel('Document Name').fill('verklaring.pdf')
		await page.getByLabel('Name').first().fill('Anna de Vries')
		await page.getByLabel('E-mail').first().fill('anna@example.invalid')
		await page.getByText('Place fields on the document').click()
		await expect(page.getByText('Page 1 of 3')).toBeVisible()
		await page.getByRole('button', { name: 'Next page' }).click()
		await page.getByRole('button', { name: 'Next page' }).click()
		await page
			.getByTestId('field-placement-sheet')
			.click({ position: { x: 300, y: 400 } })
		await expect(
			page.getByRole('button', {
				name: 'Signature for Anna de Vries on page 3',
			}),
		).toBeVisible()

		await page.getByRole('button', { name: 'Create Signing Request' }).click()
		const placements = created.fieldPlacements as Array<Record<string, unknown>>
		expect(placements).toHaveLength(1)
		expect(placements[0]).toMatchObject({
			signerIndex: 0,
			page: 3,
			type: 'signature',
		})
	})
})

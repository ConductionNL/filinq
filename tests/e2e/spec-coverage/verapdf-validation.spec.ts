/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: verapdf-validation.
 *
 * veraPDF is an admin-installed binary the test instance does not carry, so
 * the server answers (settings status, conformance reports, validation
 * findings) are given by route handlers. The backend halves are proven by
 * PHPUnit: VeraPdfServiceTest (the CLI against veraPDF 1.30.2's recorded
 * reports), ConformanceServiceTest, ConformanceControllerTest,
 * ArchivalChecksTest and Pdfa3OutputVerifierTest. What this file proves is
 * that the pages show what the server said: the validator status, the
 * report with its verdict, fonts and advice, and archival findings kept
 * apart from the document checks.
 */

// @e2e openspec/specs/verapdf-validation/spec.md#admin-sees-validator-status
// @e2e openspec/specs/verapdf-validation/spec.md#imported-pdf-failing-on-fonts-gets-the-honest-guidance
// @e2e openspec/specs/verapdf-validation/spec.md#conformance-report-is-stored-and-shown
// @e2e openspec/specs/pdfa3-conversion/spec.md#report-mode-ships-bytes-but-records-the-failure
// @e2e openspec/specs/document-validation-checks/spec.md#non-conformant-pdf-fires-the-archival-finding

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import {
	createDavFile,
	createDavFolder,
	deleteDavPath,
	harvestToken,
	TEST_PREFIX,
} from '../workflows/_fixtures.ts'
import { dismissOverlays, go, waitForNcContentReady } from './_helpers.ts'

const SETTINGS = '/index.php/settings/admin/filinq'
const DOCS_FOLDER = 'DocuDesk'
const PDF_FILE = `${TEST_PREFIX}-pdfa.pdf`
const PDF_PATH = `${DOCS_FOLDER}/${PDF_FILE}`

const IMPORTED_REPORT = {
	subject: 'file',
	flavour: '3b',
	compliant: false,
	failedRuleCount: 1,
	failedRules: [
		{
			ruleId: 'ISO 19005-3:2012/6.2.11.4.1/1',
			specification: 'ISO 19005-3:2012',
			clause: '6.2.11.4.1',
			testNumber: 1,
			checksFailed: 3,
		},
	],
	fontsNotEmbedded: ['Helvetica'],
	guidance: 'reconvertFromSource',
	validatorVersion: 'veraPDF 1.30.2',
	validatedAt: '2026-09-29T10:00:00+00:00',
	trigger: 'manual',
}

const CONVERSION_REPORT = {
	...IMPORTED_REPORT,
	subject: 'conversionOutput',
	trigger: 'conversion',
	guidance: 'regenerate',
}

test.afterAll(async ({ request }) => {
	await deleteDavPath(request, '', PDF_PATH)
})

/**
 * Open the seeded PDF in My Documents.
 *
 * @param page The page
 */
async function openSeededPdf(page: Page): Promise<void> {
	const token = await harvestToken(page)
	const mkcol = await createDavFolder(page.request, token, DOCS_FOLDER)
	expect([201, 405]).toContain(mkcol)
	const seeded = await createDavFile(
		page.request,
		token,
		PDF_PATH,
		'%PDF-1.7\n%%EOF\n',
	)
	expect(seeded.status, `PUT /${PDF_PATH}`).toBeLessThan(300)
	await go(page, 'my-documents')
	await dismissOverlays(page)
}

test('the admin sees whether the PDF/A validator is installed', async ({ page }) => {
	await page.route('**/apps/filinq/api/settings', async (route) => {
		if (route.request().method() !== 'GET') {
			await route.continue()
			return
		}
		const response = await route.fetch()
		const body = await response.json()
		body.veraPdfStatus = { enabled: true, available: true, version: 'veraPDF 1.30.2' }
		await route.fulfill({ response, json: body })
	})
	await page.goto(SETTINGS)
	await waitForNcContentReady(page)

	await expect(page.getByTestId('verapdf-status')).toContainText(
		'The PDF/A validator is installed: veraPDF 1.30.2',
	)
})

test('the PDF/A report shows the stored verdict, the fonts and the honest advice', async ({ page }) => {
	await page.route('**/apps/filinq/api/validation/conformance/*', async (route) => {
		if (route.request().method() === 'POST') {
			await route.fulfill({ json: { report: IMPORTED_REPORT } })
			return
		}
		await route.fulfill({
			json: { available: true, reports: { conversionOutput: CONVERSION_REPORT } },
		})
	})
	await openSeededPdf(page)
	const stem = PDF_FILE.replace(/\.pdf$/, '')
	await page.locator('tr').filter({ hasText: stem }).first().click()

	await page.getByTestId('open-conformance-report').click()
	const report = page.getByTestId('conformance-report')

	// Report mode: the conversion's failure is recorded and shown.
	await expect(report.getByTestId('conformance-conversionOutput')).toContainText(
		'Does not meet PDF/A-3b. Rules failed: 1',
	)

	// A new check stores and shows the file's own report.
	await report.getByTestId('conformance-check').click()
	const own = report.getByTestId('conformance-file')
	await expect(own).toContainText('Does not meet PDF/A-3b. Rules failed: 1')
	await expect(own).toContainText('Helvetica')
	await expect(own).toContainText('6.2.11.4.1')
	await expect(own).toContainText('Filinq cannot embed fonts in those afterwards')
	await expect(own).toContainText('Convert again from the original file')
})

test('archival findings sit in their own group after the document checks', async ({ page }) => {
	await page.route('**/apps/filinq/api/validation/validate', async (route) => {
		await route.fulfill({
			json: {
				validationStatus: 'warning',
				validationFindings: [
					{
						checkId: 'metadata-incomplete',
						category: 'document',
						severity: 'warning',
						message: 'Incomplete metadata',
						params: {},
					},
					{
						checkId: 'pdfa-conformance-failed',
						category: 'archival',
						severity: 'warning',
						message: 'The PDF does not meet PDF/A-{flavour}: {failedRuleCount} rules fail, such as {rules}.',
						params: { flavour: '3b', failedRuleCount: 1, rules: '6.2.11.4.1' },
						guidance: 'reconvertFromSource',
					},
				],
			},
		})
	})
	await openSeededPdf(page)
	const stem = PDF_FILE.replace(/\.pdf$/, '')
	const row = page.locator('tr').filter({ hasText: stem }).first()
	await row.getByRole('button', { name: /actions/i }).click()
	await page.getByRole('menuitem', { name: 'Validate' }).click()

	const archival = page.getByTestId('findings-archival')
	await expect(page.getByTestId('findings-document')).toBeVisible()
	await expect(archival).toContainText('Archival checks (PDF/A)')
	await expect(archival).toContainText('Not PDF/A')
	await expect(archival).toContainText('Convert again from the original file')
})

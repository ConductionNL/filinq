/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: pdfua-verapdf-matterhorn.
 *
 * veraPDF is an admin-installed binary the test instance does not carry, so
 * the server answers (the PDF/UA state of a file, a new check, the
 * validation findings) are given by route handlers. The reports they answer
 * with are the ones a real veraPDF printed for the fixtures under
 * tests/sample-documents/pdfua: heuristics-pass-matterhorn-fail.pdf passes
 * every quick check and fails ISO 14289-1 7.21.4.1-1, 7.3-1 and 7.1-3. The
 * server halves are proven by PHPUnit: PdfUaFixturesTest (those fixtures
 * through the real validation service and conformance state),
 * VeraPdfUaValidationTest, PdfUaChecksTest,
 * AccessibilityConformanceControllerTest and AccessibleRegenerationServiceTest.
 * What this file proves is that the pages show what the server said: the
 * validator's verdict over a quick-check pass, the failed Matterhorn
 * checkpoints, the honest advice for an imported PDF with no offer to
 * convert it, and the validator finding beside the quick-check ones.
 *
 * Not here: the attach scenario (a PDF attached to a publication in
 * opencatalogi is checked in the background and labelled). It needs the
 * veraPDF binary, background jobs and opencatalogi on the instance, and is
 * left to the live pass.
 */

// @e2e openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#heuristically-tagged-pdf-fails-real-matterhorn-validation
// @e2e openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#accessibility-conformance-report-is-stored-and-shown
// @e2e openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#imported-pdf-failing-matterhorn-gets-honest-guidance
// @e2e openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#validator-fail-overrides-a-heuristic-pass-in-the-surfaced-state
// @e2e openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#an-imported-pdf-is-never-converted
// @e2e openspec/changes/pdfua-verapdf-matterhorn/specs/document-validation-checks/spec.md#non-conformant-pdf-fires-the-validator-backed-accessibility-finding

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import {
	createDavFile,
	createDavFolder,
	deleteDavPath,
	harvestToken,
	TEST_PREFIX,
} from '../workflows/_fixtures.ts'
import { dismissOverlays, go } from './_helpers.ts'

const DOCS_FOLDER = 'DocuDesk'
const PDF_FILE = `${TEST_PREFIX}-pdfua1.pdf`
const PDF_PATH = `${DOCS_FOLDER}/${PDF_FILE}`

/** What veraPDF 1.28.2 reported for heuristics-pass-matterhorn-fail.pdf. */
const MATTERHORN_FAIL_REPORT = {
	fileId: 42,
	flavour: 'ua1',
	compliant: false,
	failedCheckCount: 3,
	failedChecks: [
		{ clause: '7.21.4.1', testNumber: 1, checkpoint: 'ISO14289-1_7.21.4.1-1' },
		{ clause: '7.3', testNumber: 1, checkpoint: 'ISO14289-1_7.3-1' },
		{ clause: '7.1', testNumber: 3, checkpoint: 'ISO14289-1_7.1-3' },
	],
	guidance: 'reauthorFromSource',
	validatorVersion: 'veraPDF 1.28.2',
	validatedAt: '2026-11-17T10:00:00+00:00',
	trigger: 'manual',
}

const VALIDATOR_FAIL = {
	source: 'validator',
	state: 'non-conformant',
	validated: true,
	validatorAvailable: true,
}

const LOOKS_TAGGED = {
	source: 'heuristic',
	state: 'looks-tagged',
	validated: false,
	validatorAvailable: true,
}

test.afterAll(async ({ request }) => {
	await deleteDavPath(request, '', PDF_PATH)
})

/**
 * Answer the PDF/UA routes of a file: GET with the given state, POST (a
 * new check) by storing the given report, after which GET answers with it.
 *
 * @param page The page
 * @param state The first GET answer
 * @param checked The report a new check stores, or null when none is run
 * @return Whether a regenerate call was made
 */
async function pdfUaSays(
	page: Page,
	state: object,
	checked: object | null = null,
): Promise<{ regenerateCalled: boolean }> {
	const seen = { regenerateCalled: false }
	let current = state
	await page.route(
		'**/apps/filinq/api/validation/accessibility/**',
		async (route) => {
			if (route.request().url().endsWith('/regenerate')) {
				seen.regenerateCalled = true
				await route.fulfill({
					status: 409,
					json: { error: 'not-generated-by-filinq' },
				})
				return
			}
			if (route.request().method() === 'POST' && checked !== null) {
				current = {
					report: checked,
					conformance: VALIDATOR_FAIL,
					heuristicFindings: [],
					regenerable: false,
				}
				await route.fulfill({ json: { report: checked } })
				return
			}
			await route.fulfill({ json: current })
		},
	)

	return seen
}

/**
 * Seed a PDF, open it from My Documents and open its PDF/UA report.
 *
 * @param page The page
 */
async function openPdfUaReport(page: Page): Promise<void> {
	const token = await harvestToken(page)
	expect([201, 405]).toContain(
		await createDavFolder(page.request, token, DOCS_FOLDER),
	)
	const seeded = await createDavFile(
		page.request,
		token,
		PDF_PATH,
		'%PDF-1.7\n%%EOF\n',
	)
	expect(seeded.status, `PUT /${PDF_PATH}`).toBeLessThan(300)
	await go(page, 'my-documents')
	await dismissOverlays(page)
	await page
		.locator('tr')
		.filter({ hasText: PDF_FILE.replace(/\.pdf$/, '') })
		.first()
		.click()
	await page.getByTestId('open-pdfua-report').click()
}

test('a quick-check pass that fails Matterhorn reads as not conformant, with its checkpoints', async ({
	page,
}) => {
	await pdfUaSays(page, {
		report: MATTERHORN_FAIL_REPORT,
		conformance: VALIDATOR_FAIL,
		heuristicFindings: [],
		regenerable: false,
	})
	await openPdfUaReport(page)
	const report = page.getByTestId('pdfua-report')

	const state = report.getByTestId('pdfua-state')
	await expect(state).toContainText('Validator')
	await expect(state).toContainText(
		'Does not conform to PDF/UA-1 according to the validator',
	)
	await expect(state).not.toContainText('looks tagged')

	const checks = report.getByTestId('pdfua-failed-checks')
	await expect(checks.locator('tbody tr')).toHaveCount(3)
	await expect(checks).toContainText('ISO14289-1_7.1-3')
	await expect(checks).toContainText('ISO14289-1_7.3-1')
	await expect(report).toContainText('Checked by veraPDF 1.28.2')
	await expect(report.getByText(/certified/i)).toHaveCount(0)
})

test('a new check stores the report and the card shows it', async ({ page }) => {
	await pdfUaSays(
		page,
		{
			report: null,
			conformance: LOOKS_TAGGED,
			heuristicFindings: [],
			regenerable: false,
		},
		MATTERHORN_FAIL_REPORT,
	)
	await openPdfUaReport(page)
	const report = page.getByTestId('pdfua-report')

	await expect(report.getByTestId('pdfua-state')).toContainText(
		'which is not a conformance verdict',
	)
	await expect(report.getByTestId('pdfua-failed-checks')).toHaveCount(0)

	await report.getByTestId('pdfua-check').click()

	await expect(report.getByTestId('pdfua-state')).toContainText(
		'Does not conform to PDF/UA-1 according to the validator',
	)
	await expect(
		report.getByTestId('pdfua-failed-checks').locator('tbody tr'),
	).toHaveCount(3)
})

test('an imported PDF gets the re-author advice and no offer to convert it', async ({
	page,
}) => {
	const seen = await pdfUaSays(page, {
		report: MATTERHORN_FAIL_REPORT,
		conformance: VALIDATOR_FAIL,
		heuristicFindings: [],
		regenerable: false,
	})
	await openPdfUaReport(page)
	const report = page.getByTestId('pdfua-report')

	await expect(report).toContainText('Filinq does not retag imported pages.')
	await expect(report.getByTestId('pdfua-regenerate')).toHaveCount(0)
	expect(seen.regenerateCalled).toBe(false)
})

test('the validator finding sits beside the quick-check findings, each labelled', async ({
	page,
}) => {
	await page.route('**/apps/filinq/api/validation/validate', async (route) => {
		await route.fulfill({
			json: {
				validationStatus: 'warnings',
				validationFindings: [
					{
						checkId: 'pdf-title-missing',
						category: 'accessibility',
						severity: 'warning',
						source: 'heuristic',
						message: 'The PDF has no title.',
						params: {},
					},
					{
						checkId: 'pdfua-conformance-failed',
						category: 'accessibility',
						severity: 'warning',
						source: 'validator',
						message:
							'The PDF does not conform to PDF/UA-{flavour}: {failedCheckCount} Matterhorn checks fail, such as {checks}.',
						params: {
							flavour: '1',
							failedCheckCount: 3,
							checks: 'ISO14289-1_7.21.4.1-1, ISO14289-1_7.3-1, ISO14289-1_7.1-3',
						},
						guidance: 'reauthorFromSource',
					},
				],
			},
		})
	})
	const token = await harvestToken(page)
	expect([201, 405]).toContain(
		await createDavFolder(page.request, token, DOCS_FOLDER),
	)
	const seeded = await createDavFile(
		page.request,
		token,
		PDF_PATH,
		'%PDF-1.7\n%%EOF\n',
	)
	expect(seeded.status).toBeLessThan(300)
	await go(page, 'my-documents')
	await dismissOverlays(page)
	const row = page
		.locator('tr')
		.filter({ hasText: PDF_FILE.replace(/\.pdf$/, '') })
		.first()
	await row.getByRole('button', { name: /actions/i }).click()
	await page.getByRole('menuitem', { name: 'Validate' }).click()

	const group = page.getByTestId('findings-accessibility')
	await expect(group).toContainText('Not PDF/UA')
	await expect(group).toContainText('3 Matterhorn checks fail')
	await expect(group.getByTestId('finding-source-validator')).toHaveText(
		'Validator',
	)
	await expect(group.getByTestId('finding-source-heuristic')).toHaveText(
		'Quick check',
	)
})

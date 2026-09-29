/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: pdfua-accessible-output.
 *
 * The validation answer and the template preview are given by route
 * handlers, so each scenario holds whatever the instance's PDFs happen to
 * be. The server halves are proven by PHPUnit: AccessibilityChecksTest
 * (the four checks on real fixture PDFs), TemplateAccessibilityLintTest
 * (the lint), AccessiblePdfRendererTest and LibreOfficeTaggedExportTest
 * (tagged output with language and title, never through mPDF). What this
 * file proves is that the pages show it: the accessibility group, the
 * warning before publishing, and the lint checklist beside the preview.
 */

// @e2e openspec/specs/pdfua-accessible-output/spec.md#operator-sees-grouped-accessibility-findings
// @e2e openspec/specs/pdfua-accessible-output/spec.md#inaccessible-document-warns-before-publication
// @e2e openspec/specs/pdfua-accessible-output/spec.md#clean-document-proceeds-without-warning
// @e2e openspec/specs/pdfua-accessible-output/spec.md#author-sees-a-missing-alt-lint-in-preview
// @e2e openspec/specs/document-validation-checks/spec.md#untagged-pdf-fires-the-accessibility-findings

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import {
	createDavFile,
	createDavFolder,
	createTemplate,
	deleteDavPath,
	harvestToken,
	TEST_PREFIX,
} from '../workflows/_fixtures.ts'
import { appUrl, dismissOverlays, go, waitForAppReady } from './_helpers.ts'

const DOCS_FOLDER = 'DocuDesk'
const PDF_FILE = `${TEST_PREFIX}-pdfua.pdf`
const PDF_PATH = `${DOCS_FOLDER}/${PDF_FILE}`

const UNTAGGED = [
	{ checkId: 'pdf-not-tagged', category: 'accessibility', severity: 'warning', message: 'The PDF has no tags, so a screen reader cannot follow its structure.', params: {} },
	{ checkId: 'pdf-language-missing', category: 'accessibility', severity: 'warning', message: 'The PDF does not say which language it is in.', params: {} },
]

test.afterAll(async ({ request }) => {
	await deleteDavPath(request, '', PDF_PATH)
})

/**
 * Answer POST api/validation/validate with the given findings.
 *
 * @param page The page
 * @param findings The findings
 */
async function validationSays(page: Page, findings: object[]): Promise<void> {
	await page.route('**/apps/filinq/api/validation/validate', async (route) => {
		await route.fulfill({
			json: { validationStatus: findings.length > 0 ? 'warnings' : 'passed', validationFindings: findings },
		})
	})
}

/**
 * Seed a PDF and open My Documents.
 *
 * @param page The page
 */
async function myDocumentsWithPdf(page: Page): Promise<void> {
	const token = await harvestToken(page)
	expect([201, 405]).toContain(await createDavFolder(page.request, token, DOCS_FOLDER))
	const seeded = await createDavFile(page.request, token, PDF_PATH, '%PDF-1.7\n%%EOF\n')
	expect(seeded.status).toBeLessThan(300)
	await go(page, 'my-documents')
	await dismissOverlays(page)
}

test('the validation result shows an accessibility group', async ({ page }) => {
	await validationSays(page, UNTAGGED)
	await myDocumentsWithPdf(page)
	const row = page.locator('tr').filter({ hasText: PDF_FILE.replace(/\.pdf$/, '') }).first()
	await row.getByRole('button', { name: /actions/i }).click()
	await page.getByRole('menuitem', { name: 'Validate' }).click()

	const group = page.getByTestId('findings-accessibility')
	await expect(group).toContainText('Accessibility checks')
	await expect(group).toContainText('No tags')
	await expect(group).toContainText('No language')
	await expect(page.getByText(/certified/i)).toHaveCount(0)
})

test('publishing a document with accessibility findings warns first', async ({ page }) => {
	await validationSays(page, UNTAGGED)
	let started = false
	await page.route('**/apps/filinq/api/publications', async (route) => {
		started = true
		await route.fulfill({ json: { uuid: 'pub-1' } })
	})
	await myDocumentsWithPdf(page)
	await page.locator('tr').filter({ hasText: PDF_FILE.replace(/\.pdf$/, '') }).first().click()
	await page.getByRole('button', { name: 'Publish' }).click()

	const warning = page.getByTestId('accessibility-publish-warning')
	await expect(warning).toContainText('open accessibility findings')
	await expect(warning).toContainText('No tags')
	expect(started).toBe(false)

	await warning.getByTestId('publish-anyway').click()
	await expect.poll(() => started).toBe(true)
})

test('publishing a clean document shows no warning', async ({ page }) => {
	await validationSays(page, [])
	let started = false
	await page.route('**/apps/filinq/api/publications', async (route) => {
		started = true
		await route.fulfill({ json: { uuid: 'pub-2' } })
	})
	await myDocumentsWithPdf(page)
	await page.locator('tr').filter({ hasText: PDF_FILE.replace(/\.pdf$/, '') }).first().click()
	await page.getByRole('button', { name: 'Publish' }).click()

	await expect.poll(() => started).toBe(true)
	await expect(page.getByTestId('accessibility-publish-warning')).toHaveCount(0)
})

test('the template preview lists what to fix for accessibility', async ({ page }) => {
	const token = await harvestToken(page)
	const tmpl = await createTemplate(page.request, token, { name: `${TEST_PREFIX}-pdfua-lint` })
	await page.route('**/apps/filinq/api/templates/preview', async (route) => {
		await route.fulfill({
			json: {
				html: '<h1>Besluit parkeervergunning Demostad</h1><img src="wapen.png"><h3>Overwegingen</h3>',
				lint: [
					{ rule: 'image-missing-alt', position: 1, text: 'wapen.png' },
					{ rule: 'heading-order-jump', position: 2, text: 'Overwegingen', from: 'h1', to: 'h3' },
				],
			},
		})
	})
	await page.goto(await appUrl(page, `templates/${tmpl.id}`), { waitUntil: 'domcontentloaded' })
	await waitForAppReady(page)
	await dismissOverlays(page)
	await page.getByRole('button', { name: 'Preview', exact: true }).click()

	const lint = page.getByTestId('template-lint')
	await expect(lint).toContainText('Image 1 (wapen.png) has no alternative text.')
	await expect(lint).toContainText('Heading "Overwegingen" is h3 straight after h1')
	// Advice only: saving stays possible.
	await page.getByRole('button', { name: 'Editor', exact: true }).click()
	await expect(page.getByRole('button', { name: 'Save', exact: true })).toBeEnabled()
})

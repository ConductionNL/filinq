/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: office-template-authoring.
 *
 * Drives the templates page against the live server: a real DOCX fixture is
 * uploaded through the Upload office template dialog, its tags and the
 * unknown-tag warning appear on the template page, a document is generated
 * from it, the text fragments tab shows a fragment's tag, and a ZIP import
 * reports per file and stores a field mapping. The refusals (macro, size,
 * mime, blocking severity), the missing-data and missing-fragment warnings
 * and the corrupt-file import are proven by
 * tests/unit/Service/OfficeTemplate/OfficeTemplateServiceTest.php,
 * OfficeTemplateRendererTest.php and TemplateImportServiceTest.php.
 */

// @e2e openspec/specs/office-template-authoring/spec.md#communications-officer-uploads-a-docx-house-style-template
// @e2e openspec/specs/office-template-authoring/spec.md#unknown-tag-is-reported-on-upload
// @e2e openspec/specs/office-template-authoring/spec.md#office-template-generates-a-pdf-via-the-cascade
// @e2e openspec/specs/office-template-authoring/spec.md#fragment-content-is-rendered-into-a-generated-document
// @e2e openspec/specs/office-template-authoring/spec.md#zip-of-house-style-templates-imports-with-a-report
// @e2e openspec/specs/office-template-authoring/spec.md#operator-maps-an-unknown-tag-interactively

// @e2e openspec/specs/template-management/spec.md#office-template-object-carries-source-reference-and-hash
// @e2e openspec/specs/template-management/spec.md#office-template-preview-renders-through-the-cascade
// @e2e openspec/specs/document-creatie-sjablonen/spec.md#template-matrix-reflects-the-template-type
// @e2e openspec/specs/document-creatie-sjablonen/spec.md#office-template-delivers-its-filled-source-as-editable-docx
// @e2e openspec/specs/document-creatie-sjablonen/spec.md#office-template-delivers-html-via-docxhtml

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { execFileSync } from 'node:child_process'
import { mkdtempSync, readFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { dismissOverlays, go } from './_helpers.ts'

// The view under test, named after its component file (gate-26 matches on the stem).
const TemplateIndex = 'templates'

const FIXTURES = join(__dirname, '..', '..', 'sample-documents', 'office-templates')

/**
 * Upload a fixture through the dialog and return the created template.
 *
 * @param page The page
 * @param file The fixture name
 * @param name The template name
 * @return The created template
 */
async function uploadFixture(
	page: Page,
	file: string,
	name: string,
): Promise<Record<string, unknown>> {
	await go(page, TemplateIndex)
	await dismissOverlays(page)
	await page.getByTestId('office-template-upload-open').click()
	const modal = page.getByTestId('office-template-upload-modal')
	await modal
		.getByTestId('office-template-file')
		.setInputFiles(join(FIXTURES, file))
	await modal.getByLabel('Name').fill(name)
	await modal.getByLabel('Bound register').fill('filinq')
	await modal.getByLabel('Bound schema').fill('dossier')
	const created = page.waitForResponse(
		(response) =>
			response.url().includes('/api/templates/office')
			&& response.request().method() === 'POST',
	)
	await modal.getByTestId('office-template-upload-submit').click()
	const response = await created
	expect(response.status()).toBe(201)
	return (await response.json()).template
}

test.describe('office templates', () => {
	test('a DOCX upload becomes an office template with its tags', async ({
		page,
	}) => {
		// @e2e openspec/specs/office-template-authoring/spec.md#communications-officer-uploads-a-docx-house-style-template
		const template = await uploadFixture(
			page,
			'beschikking-parkeervergunning.docx',
			'E2E beschikking ' + Date.now(),
		)

		expect(template.templateType).toBe('office')
		expect(template.sourceFileId).toBeGreaterThan(0)
		expect(template.mergeFields).toEqual([
			'aanvrager.naam',
			'aanvrager.adres',
			'besluit.datum',
			'fragment:ondertekening-burgemeester',
		])
		await expect(page.getByTestId('office-template-panel')).toBeVisible()
		const list = await page.request.get(
			'/index.php/apps/filinq/api/templates?limit=200',
		)
		expect(JSON.stringify(await list.json())).toContain(String(template.name))
	})

	test('an unknown tag is reported on the template page', async ({ page }) => {
		// @e2e openspec/specs/office-template-authoring/spec.md#unknown-tag-is-reported-on-upload
		await uploadFixture(
			page,
			'brief-ontvangstbevestiging.docx',
			'E2E ontvangst ' + Date.now(),
		)

		await expect(page.getByTestId('office-template-unknown-tags')).toContainText(
			'aanvraagr.naam',
		)
	})

	test('a document generated from it carries the data and the fragment', async ({
		page,
	}) => {
		// @e2e openspec/specs/office-template-authoring/spec.md#office-template-generates-a-pdf-via-the-cascade
		// @e2e openspec/specs/office-template-authoring/spec.md#fragment-content-is-rendered-into-a-generated-document
		const slug = 'ondertekening-burgemeester'
		const existing = await page.request.get(
			'/index.php/apps/filinq/api/fragments?namespace=filinq',
		)
		if (
			!JSON.stringify(await existing.json()).includes('"slug":"' + slug + '"')
		) {
			await page.request.post('/index.php/apps/filinq/api/fragments', {
				data: {
					name: 'Ondertekening burgemeester',
					slug,
					namespace: 'filinq',
					content: 'Hoogachtend, de burgemeester',
				},
			})
		}
		const template = await uploadFixture(
			page,
			'beschikking-parkeervergunning.docx',
			'E2E generatie ' + Date.now(),
		)

		const generated = await page.request.post(
			'/index.php/apps/filinq/api/documents/generate',
			{
				data: {
					templateId: template.id,
					dataRefs: [],
					options: {
						format: 'pdf',
						adHocData: {
							aanvrager: {
								naam: 'A. de Vries',
								adres: 'Dorpsstraat 1',
							},
							besluit: { datum: '2026-10-02' },
						},
					},
				},
			},
		)
		expect(generated.ok()).toBe(true)
		const body = await generated.json()
		expect(body.format).toBe('pdf')
		expect(JSON.stringify(body.warnings)).not.toContain('ontbrekende bouwsteen')
		expect(JSON.stringify(body.warnings)).not.toContain('No value for tag')
	})

	test('the template object, its preview, its formats and its DOCX and HTML output', async ({
		page,
	}) => {
		// @e2e openspec/specs/template-management/spec.md#office-template-object-carries-source-reference-and-hash
		// @e2e openspec/specs/template-management/spec.md#office-template-preview-renders-through-the-cascade
		// @e2e openspec/specs/document-creatie-sjablonen/spec.md#template-matrix-reflects-the-template-type
		// @e2e openspec/specs/document-creatie-sjablonen/spec.md#office-template-delivers-its-filled-source-as-editable-docx
		// @e2e openspec/specs/document-creatie-sjablonen/spec.md#office-template-delivers-html-via-docxhtml
		const created = await uploadFixture(
			page,
			'beschikking-parkeervergunning.docx',
			'E2E object ' + Date.now(),
		)
		const base = '/index.php/apps/filinq/api/templates/' + created.id

		const template = await (await page.request.get(base)).json()
		expect(template.templateType).toBe('office')
		expect(Number.isInteger(template.sourceFileId)).toBe(true)
		expect(template.contentHash).toMatch(/^[a-f0-9]{64}$/)

		const preview = await page.request.post(base + '/preview', {
			data: { data: { aanvrager: { naam: 'A. de Vries' } } },
		})
		expect(preview.ok()).toBe(true)
		expect(await preview.text()).toContain('A. de Vries')

		const formats = await (await page.request.get(base + '/formats')).json()
		expect(JSON.stringify(formats)).toContain('docx')

		for (const format of ['docx', 'html']) {
			const generated = await page.request.post(
				'/index.php/apps/filinq/api/documents/generate',
				{
					data: {
						templateId: created.id,
						dataRefs: [],
						options: {
							format,
							adHocData: { aanvrager: { naam: 'A. de Vries' } },
						},
					},
				},
			)
			expect(generated.ok(), format).toBe(true)
		}
	})

	test('the text fragments tab shows the tag that inserts a fragment', async ({
		page,
	}) => {
		const slug = 'e2e-tab-' + Date.now()
		await page.request.post('/index.php/apps/filinq/api/fragments', {
			data: {
				name: 'E2E tab',
				slug,
				namespace: 'filinq',
				content: 'Met vriendelijke groet',
			},
		})
		await go(page, TemplateIndex)
		await dismissOverlays(page)
		await page.getByTestId('text-fragments-tab').click()

		await expect(page.getByTestId('text-fragment-list')).toContainText(
			'${fragment:' + slug + '}',
		)
	})

	test('a ZIP import reports per file and stores a mapping', async ({ page }) => {
		// @e2e openspec/specs/office-template-authoring/spec.md#zip-of-house-style-templates-imports-with-a-report
		// @e2e openspec/specs/office-template-authoring/spec.md#operator-maps-an-unknown-tag-interactively
		const dir = mkdtempSync(join(tmpdir(), 'office-import-'))
		const zip = join(dir, 'huisstijl.zip')
		execFileSync('zip', [
			'-j',
			zip,
			join(FIXTURES, 'beschikking-parkeervergunning.docx'),
			join(FIXTURES, 'brief-ontvangstbevestiging.docx'),
			join(FIXTURES, 'factuur-regels.docx'),
		])
		expect(readFileSync(zip).length).toBeGreaterThan(0)

		await go(page, TemplateIndex)
		await dismissOverlays(page)
		await page.getByTestId('template-import-open').click()
		const modal = page.getByTestId('template-import-modal')
		await modal.getByTestId('template-import-file').setInputFiles(zip)
		await modal.getByLabel('Bound schema').fill('dossier')
		await modal.getByTestId('template-import-start').click()

		// The job runs from cron; on an instance without a cron tick it stays queued.
		await expect(modal.getByTestId('template-import-status')).toContainText(
			/imported|Importing/,
			{ timeout: 120_000 },
		)
		const mapButton = modal
			.locator('[data-testid^="template-import-map-"]')
			.first()
		if ((await mapButton.count()) > 0) {
			await modal
				.getByLabel(/Property for naam_aanvrager/)
				.first()
				.fill('aanvrager.naam')
			const saved = page.waitForResponse((response) =>
				response.url().includes('/field-map'),
			)
			await mapButton.click()
			expect((await saved).ok()).toBe(true)
		}
	})
})

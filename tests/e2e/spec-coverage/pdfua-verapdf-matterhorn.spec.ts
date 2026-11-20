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
 * The attach scenario runs against the instance itself, without route
 * handlers: the Matterhorn-fail fixture is put under `Open Registers/` (where
 * OpenRegister keeps the files attached to its objects, opencatalogi's
 * publications among them), the queued PdfUaAttachCheckJob is run through
 * occ, and the test reads the stored report (trigger `attach`) and the
 * file's system tag. occ is reached through FILINQ_E2E_OCC,
 * FILINQ_E2E_CONTAINER or NEXTCLOUD_CONTAINER (see shared-instance.ts). It
 * skips, saying why, on an instance without the veraPDF binary.
 */

// @e2e openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#heuristically-tagged-pdf-fails-real-matterhorn-validation
// @e2e openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#accessibility-conformance-report-is-stored-and-shown
// @e2e openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#imported-pdf-failing-matterhorn-gets-honest-guidance
// @e2e openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#validator-fail-overrides-a-heuristic-pass-in-the-surfaced-state
// @e2e openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#an-imported-pdf-is-never-converted
// @e2e openspec/changes/pdfua-verapdf-matterhorn/specs/document-validation-checks/spec.md#non-conformant-pdf-fires-the-validator-backed-accessibility-finding
// @e2e openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#a-pdf-attached-to-a-publication-in-opencatalogi-is-checked

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { execFileSync } from 'node:child_process'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { occPrefix } from '../shared-instance.ts'
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

const REGISTER_ROOT = 'Open Registers'
const ATTACH_FOLDER = `${REGISTER_ROOT}/${TEST_PREFIX}-publicatie`
const ATTACH_FILE = 'besluit.pdf'
const ATTACH_PATH = `${ATTACH_FOLDER}/${ATTACH_FILE}`
const ATTACH_JOB = 'OCA\\Filinq\\BackgroundJob\\PdfUaAttachCheckJob'
const VERDICT_TAGS = [
	'pdfua-conform',
	'pdfua-niet-conform',
	'pdfua-niet-gecontroleerd',
]

/**
 * Run occ on the instance under test.
 *
 * @param args The occ arguments
 * @return What occ printed
 */
function occ(args: string[]): string {
	const [bin, ...prefix] = occPrefix()
	// A bare `php occ` runs from the server root, two levels above this app.
	const cwd = bin === 'php' ? resolve(process.cwd(), '../..') : process.cwd()
	return execFileSync(bin, [...prefix, ...args], { cwd, encoding: 'utf8' })
}

/**
 * Run every queued PDF/UA attach check now, instead of waiting for cron.
 *
 * @return How many jobs were run
 */
function runAttachChecks(): number {
	const jobs = JSON.parse(
		occ(['background-job:list', `--class=${ATTACH_JOB}`, '--output=json']),
	) as Array<{ id: number | string }>
	for (const job of jobs) {
		occ(['background-job:execute', String(job.id), '--force-execute'])
	}
	return jobs.length
}

/**
 * The system tags on a file, read through WebDAV the way a file list reads them.
 *
 * @param req The request context
 * @param token The CSRF request-token
 * @param relPath The path under the admin Files root
 * @return The tag names
 */
async function systemTagsOf(
	req: APIRequestContext,
	token: string,
	relPath: string,
): Promise<string[]> {
	const res = await req.fetch(`/remote.php/dav/files/admin/${relPath}`, {
		method: 'PROPFIND',
		headers: {
			requesttoken: token,
			Depth: '0',
			'Content-Type': 'application/xml',
		},
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns"><d:prop><nc:system-tags/></d:prop></d:propfind>',
	})
	const xml = await res.text()
	return [...xml.matchAll(/<nc:system-tag[^>]*>([^<]+)<\/nc:system-tag>/g)].map(
		(m) => m[1].trim(),
	)
}

test.describe('a PDF attached through OpenRegister', () => {
	test.afterAll(async ({ request }) => {
		await deleteDavPath(request, '', ATTACH_FOLDER)
	})

	test('is checked in the background and labelled with its verdict', async ({
		page,
	}) => {
		const token = await harvestToken(page)
		expect([201, 405]).toContain(
			await createDavFolder(page.request, token, REGISTER_ROOT),
		)
		expect(await createDavFolder(page.request, token, ATTACH_FOLDER)).toBe(201)

		const pdf = readFileSync(
			resolve(
				process.cwd(),
				'tests/sample-documents/pdfua/heuristics-pass-matterhorn-fail.pdf',
			),
		)
		const put = await page.request.put(
			`/remote.php/dav/files/admin/${ATTACH_PATH}`,
			{
				headers: { requesttoken: token, 'Content-Type': 'application/pdf' },
				data: pdf,
			},
		)
		expect(put.status(), `PUT /${ATTACH_PATH}`).toBeLessThan(300)
		const fileId =
			put.headers()['oc-fileid']?.replace(/^0*/, '').match(/^\d+/)?.[0] ?? ''
		expect(fileId, 'the PUT answers the new file id').not.toBe('')

		const before = await page.request.get(
			`/index.php/apps/filinq/api/validation/accessibility/${fileId}`,
			{
				headers: { requesttoken: token },
			},
		)
		expect(before.status()).toBe(200)
		const beforeBody = await before.json()
		test.skip(
			beforeBody.conformance?.validatorAvailable !== true,
			'veraPDF is not installed on this instance; the attach check tags such a file pdfua-niet-gecontroleerd, which PdfUaAttachCheckJobTest covers',
		)
		// The upload itself only queues: no report exists before the job runs.
		expect(beforeBody.report).toBeNull()

		expect(
			runAttachChecks(),
			'the upload queued an attach check',
		).toBeGreaterThan(0)

		const after = await page.request.get(
			`/index.php/apps/filinq/api/validation/accessibility/${fileId}`,
			{
				headers: { requesttoken: token },
			},
		)
		const { report } = await after.json()
		expect(report.trigger).toBe('attach')
		expect(report.flavour).toBe('ua1')
		expect(report.compliant).toBe(false)

		const verdicts = (
			await systemTagsOf(page.request, token, ATTACH_PATH)
		).filter((t) => VERDICT_TAGS.includes(t))
		expect(verdicts).toEqual(['pdfua-niet-conform'])
	})
})

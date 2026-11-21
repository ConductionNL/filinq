/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * intake-paper-archive-digitisation (REQ-PAD-001, -002, -004): an archivist
 * loads a box list on a digitisation project, a scan operator prints the
 * separator sheets of a box, and a checker sends a scan back.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The box list import (the project page's "Import box list" form), the Boxes
 * widget, the "Print separators for this box" action and the original's
 * "Reject" form are what the archivist, operator and checker use, so they are
 * clicked and read. Seeding is not a journey of this change and goes through
 * OpenRegister's objects API: the project itself (the index page's create
 * dialog is OpenRegister's own), and, for the check, an original moved to
 * `scanned`, which in production is done by splitting a scanned batch in the
 * background (covered by PHPUnit). The separator PDF is read with the same
 * request the action opens, because a headless browser does not render a
 * downloaded PDF; its pages are counted in the file.
 */

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from './spec-coverage/_helpers.ts'
import {
	API,
	harvestToken,
	jsonHeaders,
	TEST_PREFIX,
} from './workflows/_fixtures.ts'

/** OpenRegister's objects API for the filinq register. */
const OBJECTS = '/index.php/apps/openregister/api/objects/filinq'

/** The box this run fills. */
const BOX = '117'

/** How many originals the box list holds. */
const ORIGINALS = 40

/**
 * A CSV box list of one box.
 *
 * @param box   The box.
 * @param count The number of originals.
 * @return The CSV text.
 */
function boxList(box: string, count: number): string {
	const rows = ['box,shelfMark,description,dateFrom,dateTo']
	for (let n = 1; n <= count; n++) {
		rows.push(
			`${box},${TEST_PREFIX}-${box}-${String(n).padStart(3, '0')},Bouwvergunning ${n},1970-01-01,2000-12-31`,
		)
	}
	return rows.join('\n')
}

/**
 * Create an object in the filinq register.
 *
 * @param request The request context.
 * @param token   The request token.
 * @param schema  The schema slug.
 * @param data    The object.
 * @return The object's id.
 */
async function createObject(
	request: APIRequestContext,
	token: string,
	schema: string,
	data: Record<string, unknown>,
): Promise<string> {
	const created = await request.post(`${OBJECTS}/${schema}`, {
		headers: jsonHeaders(token),
		data,
	})
	expect(
		created.status(),
		`POST ${schema} -> ${await created.text()}`,
	).toBeLessThan(300)
	const body = await created.json()
	const id = String(body['@self']?.id ?? body.id ?? body.uuid ?? '')
	expect(id, `${schema} id`).not.toBe('')
	return id
}

/**
 * The per-box counts of a project.
 *
 * @param request   The request context.
 * @param token     The request token.
 * @param projectId The project.
 * @return The rows, one per box.
 */
async function boxCounts(
	request: APIRequestContext,
	token: string,
	projectId: string,
) {
	const response = await request.get(
		`${API}/digitisation/projects/${projectId}/boxes`,
		{
			headers: jsonHeaders(token),
		},
	)
	expect(response.status(), await response.text()).toBe(200)
	return (await response.json()) as Array<Record<string, number | string>>
}

/**
 * Click a detail page action, whether it shows as a button or sits in the
 * actions menu.
 *
 * @param page  The page.
 * @param label The action's label.
 * @return Nothing.
 */
async function runDetailAction(page: Page, label: string): Promise<void> {
	const button = page.getByRole('button', { name: label }).first()
	if (await button.isVisible().catch(() => false)) {
		await button.click()
		return
	}
	await page
		.getByRole('button', { name: /^(Actions|Acties)$/ })
		.first()
		.click()
	await page.getByRole('menuitem', { name: label }).click()
}

test.describe('Digitising a paper archive', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(180_000)

	let token = ''
	let projectId = ''

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		projectId = await createObject(page.request, token, 'digitisationProject', {
			name: `${TEST_PREFIX} Bouwvergunningen 1970-2000`,
			archiveDescription: 'Bouwvergunningen 1970-2000',
			boxes: [BOX],
			scanProfile: `${TEST_PREFIX}-profile`,
			checkShare: 10,
			status: 'open',
		})
		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		const cleanupToken = await harvestToken(page)
		const listed = await page.request.get(`${OBJECTS}/paperOriginal`, {
			headers: jsonHeaders(cleanupToken),
			params: { project: projectId, _limit: 200 },
		})
		for (const original of (await listed.json().catch(() => ({}))).results
			?? []) {
			const id = original['@self']?.id ?? original.id
			await page.request.delete(`${OBJECTS}/paperOriginal/${id}`, {
				headers: jsonHeaders(cleanupToken),
			})
		}
		await page.request.delete(`${OBJECTS}/digitisationProject/${projectId}`, {
			headers: jsonHeaders(cleanupToken),
		})
		await page.close()
	})

	// @e2e paper-archive-digitisation::an-archivist-loads-a-box-list
	test('a box list of 40 originals shows box 117 with 40 registered', async ({
		page,
	}) => {
		await go(page, `digitisation/${projectId}`)
		await runDetailAction(page, 'Import box list')

		const dialog = page
			.getByRole('dialog')
			.filter({ hasText: 'Import a box list' })
		await expect(dialog).toBeVisible({ timeout: 15000 })
		await dialog.getByLabel('Box list (CSV)').fill(boxList(BOX, ORIGINALS))
		const imported = page.waitForResponse((res) =>
			res
				.url()
				.includes(`/digitisation/projects/${projectId}/originals/import`),
		)
		await dialog.getByRole('button', { name: 'Import', exact: true }).click()
		const response = await imported
		expect(response.status(), await response.text()).toBe(200)
		expect((await response.json()).imported).toBe(ORIGINALS)

		await page.reload({ waitUntil: 'domcontentloaded' })
		const boxRow = page.locator('tr').filter({ hasText: BOX }).first()
		await expect(boxRow).toBeVisible({ timeout: 30000 })
		await expect(boxRow).toContainText(String(ORIGINALS))

		const counts = await boxCounts(page.request, token, projectId)
		expect(counts.find((row) => row.box === BOX)).toMatchObject({
			registered: ORIGINALS,
			total: ORIGINALS,
		})
	})

	// @e2e paper-archive-digitisation::a-scan-operator-prints-the-sheets-for-a-box
	test('the separators of box 117 are a PDF of 40 pages, one per original', async ({
		page,
	}) => {
		await go(page, `digitisation/${projectId}`)
		const boxRow = page.locator('tr').filter({ hasText: BOX }).first()
		await expect(boxRow).toBeVisible({ timeout: 30000 })
		await expect(
			boxRow
				.getByText('Print separators for this box')
				.or(
					boxRow.getByRole('button', {
						name: 'Print separators for this box',
					}),
				)
				.first(),
		).toBeAttached()

		const sheets = await page.request.get(
			`${API}/digitisation/projects/${projectId}/separators`,
			{
				params: { box: BOX },
			},
		)
		expect(sheets.status(), 'separator sheets').toBe(200)
		expect(sheets.headers()['content-type']).toContain('application/pdf')
		const pdf = (await sheets.body()).toString('latin1')
		const pages = pdf.match(/\/Type\s*\/Page(?!s)/g) ?? []
		expect(pages).toHaveLength(ORIGINALS)
	})

	// @e2e paper-archive-digitisation::a-checker-sends-a-scan-back
	test('a rejected scan goes back to registered with its note and counts as rejected', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')
		const originalId = await createObject(page.request, token, 'paperOriginal', {
			project: projectId,
			box: BOX,
			shelfMark: `${TEST_PREFIX}-${BOX}-cut-off`,
			description: 'Bouwvergunning met afgesneden pagina',
			status: 'registered',
		})
		const scanned = await page.request.patch(
			`${OBJECTS}/paperOriginal/${originalId}`,
			{
				headers: jsonHeaders(token),
				data: { status: 'scanned', scannedAt: new Date().toISOString() },
			},
		)
		expect(
			scanned.status(),
			`mark scanned -> ${await scanned.text()}`,
		).toBeLessThan(300)

		await go(page, `paper-originals/${originalId}`)
		await runDetailAction(page, 'Reject')
		const dialog = page
			.getByRole('dialog')
			.filter({ hasText: 'Reject the scan' })
		await expect(dialog).toBeVisible({ timeout: 15000 })
		await dialog.getByLabel('Note').fill('page 2 cut off')
		const checked = page.waitForResponse((res) =>
			res.url().includes(`/digitisation/originals/${originalId}/check`),
		)
		await dialog.getByRole('button', { name: 'Reject', exact: true }).click()
		const response = await checked
		expect(response.status(), await response.text()).toBe(200)

		const read = await page.request.get(
			`${OBJECTS}/paperOriginal/${originalId}`,
			{ headers: jsonHeaders(token) },
		)
		const original = await read.json()
		expect(original.status).toBe('registered')
		expect(original.check).toMatchObject({
			result: 'rejected',
			note: 'page 2 cut off',
		})

		const counts = await boxCounts(page.request, token, projectId)
		expect(counts.find((row) => row.box === BOX)?.rejected).toBe(1)
	})
})

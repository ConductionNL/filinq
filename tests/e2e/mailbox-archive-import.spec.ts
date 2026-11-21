/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * intake-mailbox-archive-import (REQ-MAI-001, -002, -003, -005): an archivist
 * starts an import of a mail archive in their Files, maps its folders to
 * dossiers, and reads the counts on the Mail archive import page.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The page, its status badges, the Map folders dialog and the counts are what
 * an archivist uses, so they are read and clicked. Two things are not:
 *   - The file is picked in Nextcloud's own file picker, which is not a Filinq
 *     screen. The tests seed the archive over WebDAV and start the import with
 *     the same POST api/mail-archive-imports the page sends after the pick.
 *   - The import itself runs in a background job (one bounded unit per cron
 *     run), which a browser cannot trigger. Where a scenario reads the outcome
 *     after the job ran, GET api/mail-archive-imports is answered from a route
 *     handler with the record shape MailArchiveImportService stores
 *     (status, folderMap, counts, failures).
 * The first two tests use the real server: splitting an MBOX into folders and
 * refusing a PST without readpst happen in the request that starts an import.
 */

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from './spec-coverage/_helpers.ts'
import {
	API,
	createDavFile,
	createDavFolder,
	deleteDavPath,
	harvestToken,
	jsonHeaders,
	TEST_PREFIX,
} from './workflows/_fixtures.ts'

/** This run's folder in the admin's Files. */
const FOLDER = `${TEST_PREFIX}-mail-archive`

/** The in-app route of the import page. */
const PAGE_ROUTE = 'mail-archive-import'

/** The list route the page reads. */
const LIST_ROUTE = '**/apps/filinq/api/mail-archive-imports'

/**
 * An MBOX with two messages, both in the archive's root folder.
 *
 * @return The file contents.
 */
function twoMessageMbox(): string {
	const message = (n: number) =>
		[
			`From jansen@example.org Mon Jan  6 09:0${n}:00 2025`,
			'From: J. Jansen <jansen@example.org>',
			'To: archief@example.org',
			`Subject: Overdracht ${n}`,
			`Message-ID: <${TEST_PREFIX}-${n}@example.org>`,
			'Date: Mon, 6 Jan 2025 09:00:00 +0100',
			'',
			`Bericht ${n}.`,
			'',
		].join('\n')
	return message(1) + message(2)
}

/**
 * Start an import of a file, as the page does after the pick.
 *
 * @param page   The page.
 * @param token  The request token.
 * @param fileId The picked file's id.
 * @return The stored import record.
 */
async function startImportOf(
	page: Page,
	token: string,
	fileId: string,
): Promise<Record<string, unknown>> {
	const response = await page.request.post(`${API}/mail-archive-imports`, {
		headers: jsonHeaders(token),
		data: { fileId: Number(fileId) },
	})
	expect(response.status(), await response.text()).toBe(201)
	return await response.json()
}

/**
 * Answer the import list with fixed records.
 *
 * @param page    The page.
 * @param results The import records.
 * @return Nothing.
 */
async function answerImports(
	page: Page,
	results: Record<string, unknown>[],
): Promise<void> {
	await page.route(LIST_ROUTE, async (route) => {
		if (route.request().method() !== 'GET') {
			await route.fallback()
			return
		}
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({ results }),
		})
	})
}

/**
 * The page's row of an archive.
 *
 * @param page     The page.
 * @param fileName The archive's file name.
 * @return The row locator.
 */
function rowOf(page: Page, fileName: string) {
	return page.locator('tr').filter({ hasText: fileName }).first()
}

test.describe('Mail archive import', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(120_000)

	let token = ''

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect(await createDavFolder(page.request, token, FOLDER)).toBeLessThan(300)
		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		await deleteDavPath(page.request, await harvestToken(page), FOLDER)
		await page.close()
	})

	// @e2e openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#an-archivist-imports-a-departing-official-s-mailbox
	test('an import of an MBOX waits for the mapping and lists its folders', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')
		const seeded = await createDavFile(
			page.request,
			token,
			`${FOLDER}/mailbox-jansen.mbox`,
			twoMessageMbox(),
		)
		expect(seeded.status, 'seeding the mailbox').toBeLessThan(300)

		const started = await startImportOf(page, token, seeded.fileId)
		expect(started.status).toBe('mapping')
		expect(started.folderMap).toEqual([
			expect.objectContaining({
				archiveFolder: 'mailbox-jansen',
				messages: 2,
			}),
		])

		await go(page, PAGE_ROUTE)
		const row = rowOf(page, 'mailbox-jansen.mbox')
		await expect(row).toBeVisible({ timeout: 30000 })
		await expect(row.getByTestId('mail-archive-status-mapping')).toBeVisible()

		await row.getByTestId('mail-archive-map').click()
		const dialog = page.getByTestId('mail-archive-mapping-dialog')
		await expect(dialog).toBeVisible({ timeout: 15000 })
		await expect(dialog.getByTestId('mail-archive-mapping-row')).toHaveCount(1)
		await expect(dialog).toContainText('mailbox-jansen (2 messages)')

		const cancelled = await page.request.post(
			`${API}/mail-archive-imports/${started.uuid}/cancel`,
			{
				headers: jsonHeaders(token),
			},
		)
		expect(cancelled.status()).toBeLessThan(300)
	})

	// @e2e openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#a-pst-without-the-tool
	test('a PST on a server without readpst fails at once and says why', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')
		const settings = await page.request.get(
			`${API}/mail-archive-imports/settings`,
			{
				headers: jsonHeaders(token),
			},
		)
		expect(settings.status(), await settings.text()).toBe(200)
		test.skip(
			(await settings.json()).readpstInstalled === true,
			'readpst is installed on this instance, so a PST is converted instead of refused',
		)

		const seeded = await createDavFile(
			page.request,
			token,
			`${FOLDER}/archief.pst`,
			'!BDN not a real PST',
		)
		expect(seeded.status, 'seeding the PST').toBeLessThan(300)
		const started = await startImportOf(page, token, seeded.fileId)
		expect(started.status).toBe('failed')
		expect(started.statusReason).toBe('readpst is not installed on this server')

		await go(page, PAGE_ROUTE)
		const row = rowOf(page, 'archief.pst')
		await expect(row.getByTestId('mail-archive-status-failed')).toBeVisible({
			timeout: 30000,
		})
		await expect(row.getByTestId('mail-archive-status-reason')).toHaveText(
			'readpst is not installed on this server',
		)
	})

	// @e2e openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#one-folder-filed-one-folder-to-the-inbox
	test('a mapped folder goes to its dossier and the other to the intake inbox', async ({
		page,
	}) => {
		const uuid = `${TEST_PREFIX}-import-two-folders`
		const dossierId = `${TEST_PREFIX}-dossier-brug`
		const waiting = {
			uuid,
			fileName: 'archief-projecten.zip',
			format: 'zip',
			status: 'mapping',
			folderMap: [
				{ archiveFolder: 'Projecten/Brug', messages: 3, dossier: '' },
				{ archiveFolder: 'Inbox', messages: 2, dossier: '' },
			],
			counts: {
				found: 5,
				filed: 0,
				toInbox: 0,
				skippedDuplicate: 0,
				failed: 0,
			},
			failures: [],
		}
		await answerImports(page, [waiting])
		await page.route(
			'**/apps/openregister/api/objects/filinq/dossier**',
			async (route) => {
				await route.fulfill({
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify({
						results: [
							{
								'@self': { id: dossierId },
								name: 'Vervanging brug Zuid',
							},
						],
					}),
				})
			},
		)
		let sentMap: unknown = null
		await page.route(
			`**/apps/filinq/api/mail-archive-imports/${uuid}/start`,
			async (route) => {
				sentMap = route.request().postDataJSON().folderMap
				await route.fulfill({
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify({ ...waiting, status: 'importing' }),
				})
			},
		)

		await go(page, PAGE_ROUTE)
		const row = rowOf(page, 'archief-projecten.zip')
		await row.getByTestId('mail-archive-map').click()
		const dialog = page.getByTestId('mail-archive-mapping-dialog')
		await expect(dialog).toBeVisible({ timeout: 15000 })

		const brug = dialog
			.getByTestId('mail-archive-mapping-row')
			.filter({ hasText: 'Projecten/Brug' })
		await brug.locator('.vs__dropdown-toggle').click()
		await page.getByRole('option', { name: 'Vervanging brug Zuid' }).click()

		await page.unroute(LIST_ROUTE)
		await answerImports(page, [
			{
				...waiting,
				status: 'done',
				folderMap: [
					{
						archiveFolder: 'Projecten/Brug',
						messages: 3,
						dossier: dossierId,
					},
					{ archiveFolder: 'Inbox', messages: 2, dossier: '' },
				],
				counts: {
					found: 5,
					filed: 3,
					toInbox: 2,
					skippedDuplicate: 0,
					failed: 0,
				},
			},
		])
		await dialog.getByRole('button', { name: 'Start import' }).click()
		await expect(dialog).toBeHidden({ timeout: 15000 })

		expect(sentMap).toEqual([
			{ archiveFolder: 'Projecten/Brug', dossier: dossierId },
			{ archiveFolder: 'Inbox', dossier: '' },
		])
		await expect(row.getByTestId('mail-archive-status-done')).toBeVisible({
			timeout: 15000,
		})
		await expect(row.getByTestId('mail-archive-counts')).toHaveText(
			'5 found: 3 filed, 2 to the intake inbox, 0 already there, 0 failed',
		)
	})

	// @e2e openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#the-same-archive-imported-twice
	test('a second import of the same archive counts every message as already there', async ({
		page,
	}) => {
		await answerImports(page, [
			{
				uuid: `${TEST_PREFIX}-import-second-run`,
				fileName: 'mailbox-jansen.mbox',
				format: 'mbox',
				status: 'done',
				folderMap: [
					{
						archiveFolder: 'mailbox-jansen',
						messages: 2,
						dossier: `${TEST_PREFIX}-dossier`,
					},
				],
				counts: {
					found: 2,
					filed: 0,
					toInbox: 0,
					skippedDuplicate: 2,
					failed: 0,
				},
				failures: [],
			},
		])

		await go(page, PAGE_ROUTE)
		const row = rowOf(page, 'mailbox-jansen.mbox')
		await expect(row.getByTestId('mail-archive-status-done')).toBeVisible({
			timeout: 30000,
		})
		await expect(row.getByTestId('mail-archive-counts')).toHaveText(
			'2 found: 0 filed, 0 to the intake inbox, 2 already there, 0 failed',
		)
		await expect(row.getByTestId('mail-archive-failures')).toHaveCount(0)
	})
})

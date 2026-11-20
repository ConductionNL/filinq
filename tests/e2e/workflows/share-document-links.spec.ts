/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * share-document-links 3.1 to 3.3: a handler shares one document, then three
 * as a bundle, from My documents; each link opens for someone who is not
 * logged in; a labelled document cannot be shared without the confirmation;
 * and a link taken back on the Shared links page stops opening.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The dialog, the row and bulk actions, the Shared links page and Take back
 * are what a handler uses, so they are clicked. Seeding the documents (WebDAV)
 * and putting a confidentiality tag on one (the system-tag DAV API) are not
 * Filinq journeys, so they go through those APIs. A link is read off the
 * dialog's Link field rather than the clipboard, which a headless browser does
 * not share with the test.
 *
 * The labelled-document test needs the files_confidential app, which is what
 * makes Filinq read a system tag as a label. Without it the preflight carries
 * no label and the test fails naming that, instead of passing on a dialog that
 * never had anything to warn about.
 */

import type { Browser, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from '../spec-coverage/_helpers.ts'
import {
	API,
	createDavFile,
	createDavFolder,
	deleteDavPath,
	harvestToken,
	jsonHeaders,
	TEST_PREFIX,
} from './_fixtures.ts'

/** The folder My documents lists. */
const DOCS_FOLDER = 'DocuDesk'

/** This run's documents, by stem (the list shows names without the extension). */
const STEMS = ['besluit', 'bijlage-a', 'bijlage-b', 'vertrouwelijk'].map(
	(name) => `${TEST_PREFIX}-share-${name}`,
)

/**
 * The My documents row of a seeded document.
 *
 * @param page The page.
 * @param stem The document's name without extension.
 * @return The row locator.
 */
function rowOf(page: Page, stem: string) {
	return page.locator('tr').filter({ hasText: stem }).first()
}

/**
 * Open a link in a context that has never logged in.
 *
 * @param browser The browser.
 * @param url     The public link.
 * @return The HTTP status of the page.
 */
async function openLoggedOut(browser: Browser, url: string): Promise<number> {
	const context = await browser.newContext({ storageState: { cookies: [], origins: [] } })
	const response = await (await context.newPage()).goto(url)
	const status = response?.status() ?? 0
	await context.close()
	return status
}

/**
 * Fill the dialog's required fields and share.
 *
 * @param page The page.
 * @return The link the dialog shows.
 */
async function shareFromDialog(page: Page): Promise<string> {
	const dialog = page.getByTestId('share-by-link')
	await expect(dialog).toBeVisible({ timeout: 15000 })
	await dialog.getByTestId('share-by-link-password').locator('input').fill('Pw-share-2026!Strong#e2e')
	await dialog.getByTestId('share-by-link-note').locator('input, textarea').first().fill('For the applicant')
	await dialog.getByTestId('share-by-link-submit').click()
	const link = dialog.getByTestId('share-by-link-url').locator('input')
	await expect(link).toHaveValue(/\/s\/[A-Za-z0-9]+/, { timeout: 30000 })
	return await link.inputValue()
}

test.describe('Documents shared by link', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(120_000)

	let token = ''
	const fileIds: Record<string, string> = {}
	const links: string[] = []

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect([201, 405]).toContain(await createDavFolder(page.request, token, DOCS_FOLDER))
		for (const stem of STEMS) {
			const seeded = await createDavFile(page.request, token, `${DOCS_FOLDER}/${stem}.txt`, `Contents of ${stem}.`)
			expect(seeded.status, `seeding ${stem}`).toBeLessThan(300)
			fileIds[stem] = seeded.fileId
		}
		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		const cleanupToken = await harvestToken(page)
		for (const stem of STEMS) {
			await deleteDavPath(page.request, cleanupToken, `${DOCS_FOLDER}/${stem}.txt`)
		}
		await page.close()
	})

	// @e2e openspec/changes/share-document-links/tasks.md#3.1
	test('one document is shared from its row, and the link opens logged out', async ({ page, browser }) => {
		await go(page, 'my-documents')
		const row = rowOf(page, STEMS[0])
		await expect(row).toBeVisible({ timeout: 30000 })
		await row.locator('.my-documents-row-actions button').first().click()
		await page.getByRole('menuitem', { name: 'Share by link' }).click()

		const link = await shareFromDialog(page)
		links.push(link)

		expect(await openLoggedOut(browser, link), 'the public link opens').toBe(200)
	})

	// @e2e openspec/changes/share-document-links/tasks.md#3.1
	test('three documents are shared as one bundle, and that link opens logged out', async ({ page, browser }) => {
		await go(page, 'my-documents')
		await page.getByRole('button', { name: 'Filter' }).first().click()
		await page.getByRole('menuitem', { name: 'Bulk selection' }).click()
		for (const stem of STEMS.slice(0, 3)) {
			await rowOf(page, stem).getByRole('checkbox').check()
		}

		await page.getByRole('button', { name: 'Options' }).first().click()
		await page.getByTestId('documents-share-selected').click()

		const dialog = page.getByTestId('share-by-link')
		await expect(dialog.getByTestId('share-by-link-bundle'), 'the preflight lists what goes in').toBeVisible({ timeout: 30000 })
		await dialog.getByTestId('share-by-link-name').locator('input').fill(`${TEST_PREFIX} bundle`)
		const link = await shareFromDialog(page)
		links.push(link)

		expect(await openLoggedOut(browser, link), 'the bundle link opens').toBe(200)
	})

	// @e2e openspec/changes/share-document-links/tasks.md#3.2
	test('a labelled document cannot be shared without the confirmation', async ({ page }) => {
		const stem = STEMS[3]
		const tag = await page.request.post('/remote.php/dav/systemtags', {
			headers: { 'Content-Type': 'application/json', requesttoken: token },
			data: { name: 'Confidential', userVisible: true, userAssignable: true },
		})
		expect([201, 409], `creating the Confidential tag answered ${tag.status()}`).toContain(tag.status())
		const tags = await page.request.fetch('/remote.php/dav/systemtags', {
			method: 'PROPFIND',
			headers: { Depth: '1', 'Content-Type': 'application/xml', requesttoken: token },
			data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:id/><oc:display-name/></d:prop></d:propfind>',
		})
		const tagId = /<oc:id>(\d+)<\/oc:id>\s*<oc:display-name>Confidential<\/oc:display-name>/.exec(await tags.text())?.[1] ?? ''
		expect(tagId, 'the Confidential system tag has an id').not.toBe('')
		const assigned = await page.request.put(`/remote.php/dav/systemtags-relations/files/${fileIds[stem]}/${tagId}`, {
			headers: { requesttoken: token },
		})
		expect([201, 409]).toContain(assigned.status())

		const preflight = await page.request.post(`${API}/shares/preflight`, {
			headers: jsonHeaders(token),
			data: { fileId: Number(fileIds[stem]) },
		})
		expect(
			(await preflight.json()).labels,
			'the document carries a label: this needs the files_confidential app on the instance',
		).toEqual([{ fileId: Number(fileIds[stem]), label: 'Confidential' }])

		await go(page, 'my-documents')
		const row = rowOf(page, stem)
		await expect(row).toBeVisible({ timeout: 30000 })
		await row.locator('.my-documents-row-actions button').first().click()
		await page.getByRole('menuitem', { name: 'Share by link' }).click()

		const dialog = page.getByTestId('share-by-link')
		await expect(dialog.getByTestId('share-by-link-label')).toContainText('Confidential')
		await dialog.getByTestId('share-by-link-password').locator('input').fill('Pw-share-2026!Strong#e2e')
		await expect(dialog.getByTestId('share-by-link-submit'), 'no share before the confirmation').toBeDisabled()

		await dialog.getByTestId('share-by-link-confirm').locator('input').check()
		await expect(dialog.getByTestId('share-by-link-submit')).toBeEnabled()
	})

	// @e2e openspec/changes/share-document-links/tasks.md#3.3
	test('a link taken back on Shared links stops opening', async ({ page, browser }) => {
		expect(links.length, 'the first test shared a document').toBeGreaterThan(0)
		await go(page, 'my-documents')
		await page.getByRole('button', { name: 'Filter' }).first().click()
		await page.getByTestId('documents-shared-links').click()
		await expect(page).toHaveURL(/\/my-documents\/shared-links$/)

		const row = page.locator('tr').filter({ hasText: STEMS[0] }).first()
		await expect(row).toBeVisible({ timeout: 30000 })
		await row.getByTestId('shared-link-take-back').click()
		const takenBack = page.waitForResponse(
			(res) => /\/api\/shares\/[^/]+$/.test(res.url()) && res.request().method() === 'DELETE',
		)
		await page.getByRole('dialog').getByRole('button', { name: 'Take back' }).click()
		const response = await takenBack
		expect(response.status(), await response.text()).toBe(200)
		expect((await response.json()).status, 'the record is revoked').toBe('revoked')

		expect(await openLoggedOut(browser, links[0]), 'a link taken back is gone').toBe(404)
	})
})

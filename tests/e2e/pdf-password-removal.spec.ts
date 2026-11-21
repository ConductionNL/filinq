/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * intake-pdf-password-removal (REQ-PPR-001 to REQ-PPR-004): a clerk unlocks a
 * password-protected PDF into "<name> (unlocked).pdf" from the validation
 * result on My documents or from its row on /intake; a wrong password writes
 * nothing; the copy is validated at once; and without qpdf the admin settings
 * and the action both say so.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The Validate row action, the "Remove password" link on the Encrypted PDF
 * finding, the password dialog and the intake row action are what a clerk
 * uses, so they are clicked. Two things are not in a browser's reach:
 *  - A PDF locked with a known password. The suite carries no locked fixture
 *    and the browser cannot make one. When E2E_LOCKED_PDF (a path to a PDF
 *    locked with E2E_LOCKED_PDF_PASSWORD) is set, the My documents scenarios
 *    upload it and go through the real routes end to end; without it they are
 *    skipped, naming the variables.
 *  - Which qpdf the server has. The copy's own findings and the intake row
 *    answer the routes' shapes from route handlers, so the screens are
 *    checked on any instance; the decryption itself is PHPUnit's
 *    (PdfPasswordRemovalServiceTest with a locked fixture).
 * The qpdf-missing scenario reads the real admin settings and the real route;
 * on a server WITH qpdf its precondition does not hold and it is skipped.
 */

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { readFileSync } from 'node:fs'
import {
	dismissOverlays,
	go,
	waitForNcContentReady,
} from './spec-coverage/_helpers.ts'
import {
	createDavFolder,
	deleteDavPath,
	harvestToken,
	jsonHeaders,
	TEST_PREFIX,
} from './workflows/_fixtures.ts'

/** The folder My documents lists. */
const DOCS_FOLDER = 'DocuDesk'

/** The locked fixture, when the run provides one. */
const LOCKED_PDF = process.env.E2E_LOCKED_PDF ?? ''

/** Its password. */
const LOCKED_PASSWORD = process.env.E2E_LOCKED_PDF_PASSWORD ?? ''

/** This run's supplier PDF, by stem. */
const STEM = `${TEST_PREFIX}-offerte`

/**
 * Upload bytes through WebDAV and read back the file id.
 *
 * @param req   The request context.
 * @param token The CSRF request-token.
 * @param path  The path under the admin user's files.
 * @param bytes The contents.
 * @return The file id, or 0.
 */
async function upload(
	req: APIRequestContext,
	token: string,
	path: string,
	bytes: Buffer,
): Promise<number> {
	const url = `/remote.php/dav/files/admin/${encodeURI(path)}`
	const put = await req.put(url, { headers: { requesttoken: token }, data: bytes })
	expect(put.status(), `uploading ${path}`).toBeLessThan(300)
	return await fileIdOf(req, token, path)
}

/**
 * The file id of a path, or 0 when nothing is there.
 *
 * @param req   The request context.
 * @param token The CSRF request-token.
 * @param path  The path under the admin user's files.
 * @return The file id, or 0.
 */
async function fileIdOf(
	req: APIRequestContext,
	token: string,
	path: string,
): Promise<number> {
	const found = await req.fetch(`/remote.php/dav/files/admin/${encodeURI(path)}`, {
		method: 'PROPFIND',
		headers: {
			requesttoken: token,
			Depth: '0',
			'Content-Type': 'application/xml',
		},
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
	})
	if (found.status() !== 207) {
		return 0
	}
	return Number(/<oc:fileid>(\d+)<\/oc:fileid>/.exec(await found.text())?.[1] ?? 0)
}

/**
 * The bytes of a path.
 *
 * @param req   The request context.
 * @param token The CSRF request-token.
 * @param path  The path under the admin user's files.
 * @return The contents.
 */
async function bytesOf(
	req: APIRequestContext,
	token: string,
	path: string,
): Promise<Buffer> {
	const got = await req.get(`/remote.php/dav/files/admin/${encodeURI(path)}`, {
		headers: { requesttoken: token },
	})
	expect(got.status()).toBe(200)
	return await got.body()
}

/**
 * Open the validation result of a My documents row and its password dialog.
 *
 * @param page The page.
 * @return The dialog.
 */
async function openPasswordDialog(page: Page) {
	await go(page, 'my-documents')
	const row = page.locator('tr').filter({ hasText: STEM }).first()
	await expect(row).toBeVisible({ timeout: 30000 })
	await row.getByRole('button').last().click()
	await page
		.getByRole('menuitem', { name: 'Validate' })
		.or(page.getByRole('button', { name: 'Validate' }))
		.first()
		.click()
	await expect(page.getByText('Encrypted PDF')).toBeVisible({ timeout: 30000 })
	await page.getByTestId('finding-remove-password').click()
	const dialog = page.getByTestId('pdf-password-modal')
	await expect(dialog).toBeVisible()
	return dialog
}

test.describe('Removing the password of a locked PDF', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(120_000)

	let token = ''
	let original = Buffer.alloc(0)

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect([201, 405]).toContain(
			await createDavFolder(page.request, token, DOCS_FOLDER),
		)
		if (LOCKED_PDF !== '') {
			original = readFileSync(LOCKED_PDF)
			await upload(page.request, token, `${DOCS_FOLDER}/${STEM}.pdf`, original)
		}
		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		const cleanup = await harvestToken(page)
		await deleteDavPath(page.request, cleanup, `${DOCS_FOLDER}/${STEM}.pdf`)
		await deleteDavPath(
			page.request,
			cleanup,
			`${DOCS_FOLDER}/${STEM} (unlocked).pdf`,
		)
		await page.close()
	})

	// @e2e pdf-password-removal::a-wrong-password-writes-nothing
	test('a wrong password says so and writes no file', async ({ page }) => {
		test.skip(
			LOCKED_PDF === '',
			'needs E2E_LOCKED_PDF and E2E_LOCKED_PDF_PASSWORD: a PDF locked with a known password',
		)
		const dialog = await openPasswordDialog(page)
		await dialog
			.getByTestId('pdf-password-input')
			.locator('input')
			.fill(`${LOCKED_PASSWORD}-wrong`)
		const answered = page.waitForResponse((res) =>
			res.url().includes('/remove-password'),
		)
		await dialog.getByTestId('pdf-password-submit').click()
		expect((await answered).status()).toBe(422)
		await expect(dialog.getByTestId('pdf-password-error')).toHaveText(
			/The password is not correct/,
		)
		expect(
			await fileIdOf(
				page.request,
				token,
				`${DOCS_FOLDER}/${STEM} (unlocked).pdf`,
			),
		).toBe(0)
	})

	// @e2e pdf-password-removal::a-clerk-unlocks-a-suppliers-pdf-from-my-documents
	test('the right password writes the unlocked copy beside an unchanged original', async ({
		page,
	}) => {
		test.skip(
			LOCKED_PDF === '',
			'needs E2E_LOCKED_PDF and E2E_LOCKED_PDF_PASSWORD: a PDF locked with a known password',
		)
		const dialog = await openPasswordDialog(page)
		await dialog
			.getByTestId('pdf-password-input')
			.locator('input')
			.fill(LOCKED_PASSWORD)
		const answered = page.waitForResponse((res) =>
			res.url().includes('/remove-password'),
		)
		await dialog.getByTestId('pdf-password-submit').click()
		const response = await answered
		expect(response.status(), await response.text()).toBe(200)
		expect((await response.json()).name).toBe(`${STEM} (unlocked).pdf`)

		await expect(
			page
				.locator('tr')
				.filter({ hasText: `${STEM} (unlocked)` })
				.first(),
		).toBeVisible({ timeout: 30000 })
		expect(
			await fileIdOf(
				page.request,
				token,
				`${DOCS_FOLDER}/${STEM} (unlocked).pdf`,
			),
		).toBeGreaterThan(0)
		expect(
			(
				await bytesOf(page.request, token, `${DOCS_FOLDER}/${STEM}.pdf`)
			).equals(original),
			'the original is unchanged',
		).toBe(true)
	})

	// @e2e pdf-password-removal::the-copy-shows-its-own-findings
	test('the copy of a scan shows Missing text layer and no Encrypted PDF', async ({
		page,
	}) => {
		const stem = `${TEST_PREFIX}-scan`
		const lockedId = await upload(
			page.request,
			token,
			`${DOCS_FOLDER}/${stem}.pdf`,
			Buffer.from('%PDF-1.4\n%locked scan placeholder\n'),
		)
		try {
			await page.route(
				'**/apps/filinq/api/validation/validate',
				async (route) => {
					await route.fulfill({
						status: 200,
						contentType: 'application/json',
						body: JSON.stringify({
							validationStatus: 'failed',
							validationFindings: [
								{
									checkId: 'pdf-encrypted',
									severity: 'error',
									category: 'pdf',
								},
							],
						}),
					})
				},
			)
			await page.route(
				`**/apps/filinq/api/documents/${lockedId}/remove-password`,
				async (route) => {
					await route.fulfill({
						status: 200,
						contentType: 'application/json',
						body: JSON.stringify({
							fileId: lockedId + 1,
							name: `${stem} (unlocked).pdf`,
							validationStatus: 'warning',
							validationFindings: [
								{
									checkId: 'text-layer-missing',
									severity: 'warning',
									category: 'pdf',
									suggestedAction: 'ocr',
								},
							],
						}),
					})
				},
			)

			await go(page, 'my-documents')
			const row = page.locator('tr').filter({ hasText: stem }).first()
			await expect(row).toBeVisible({ timeout: 30000 })
			await row.getByRole('button').last().click()
			await page
				.getByRole('menuitem', { name: 'Validate' })
				.or(page.getByRole('button', { name: 'Validate' }))
				.first()
				.click()
			await page.getByTestId('finding-remove-password').click()
			const dialog = page.getByTestId('pdf-password-modal')
			await dialog
				.getByTestId('pdf-password-input')
				.locator('input')
				.fill('any-password')
			const sent = page.waitForRequest((req) =>
				req.url().includes(`/documents/${lockedId}/remove-password`),
			)
			await dialog.getByTestId('pdf-password-submit').click()
			expect((await sent).postDataJSON()).toEqual({ password: 'any-password' })

			await expect(page.getByText('Missing text layer')).toBeVisible({
				timeout: 15000,
			})
			await expect(page.getByText('Encrypted PDF')).toHaveCount(0)
		} finally {
			await deleteDavPath(page.request, token, `${DOCS_FOLDER}/${stem}.pdf`)
		}
	})

	// @e2e pdf-password-removal::the-admin-sees-that-the-tool-is-missing
	test('without qpdf the admin settings say so and the action answers it', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')
		const settings = await page.request.get(
			'/index.php/apps/filinq/api/settings',
			{ headers: jsonHeaders(token) },
		)
		expect(settings.status()).toBe(200)
		const body = await settings.json()
		const qpdf = body?.qpdfStatus ?? body?.settings?.qpdfStatus ?? {}
		test.skip(
			qpdf.available === true,
			"this server has qpdf, so the scenario's precondition does not hold",
		)

		await page.goto('/index.php/settings/admin/filinq', {
			waitUntil: 'domcontentloaded',
		})
		await waitForNcContentReady(page)
		await dismissOverlays(page)
		const status = page.getByTestId('qpdf-status')
		await status.scrollIntoViewIfNeeded()
		await expect(status).toContainText('qpdf is not installed.')

		const stem = `${TEST_PREFIX}-geen-qpdf`
		const fileId = await upload(
			page.request,
			token,
			`${DOCS_FOLDER}/${stem}.pdf`,
			Buffer.from('%PDF-1.4\n%no qpdf\n'),
		)
		try {
			const refused = await page.request.post(
				`/index.php/apps/filinq/api/documents/${fileId}/remove-password`,
				{
					headers: jsonHeaders(token),
					data: { password: 'irrelevant' },
				},
			)
			expect(refused.status()).toBe(503)
			expect((await refused.json()).error).toBe(
				'qpdf is not installed on this server',
			)
		} finally {
			await deleteDavPath(page.request, token, `${DOCS_FOLDER}/${stem}.pdf`)
		}
	})

	// @e2e pdf-password-removal::a-registrar-unlocks-a-document-in-the-intake-worklist
	test('a registrar unlocks a waiting document and the row then names the copy', async ({
		page,
	}) => {
		const uuid = `${TEST_PREFIX}-locked-intake`
		const arrived = 9001
		const copy = 9002
		let unlocked = false
		await page.route(
			(url) =>
				url.pathname.endsWith('/apps/filinq/api/intake/documents')
				&& !url.searchParams.has('mode'),
			async (route) => {
				if (route.request().method() !== 'GET') {
					await route.fallback()
					return
				}
				const row = {
					uuid,
					channel: 'mail',
					sender: `${TEST_PREFIX}@voorbeeld.nl`,
					subject: `${TEST_PREFIX} versleutelde factuur`,
					receivedAt: new Date().toISOString(),
					status: 'received',
					file: unlocked ? copy : arrived,
					fileName: unlocked ? 'factuur (unlocked).pdf' : 'factuur.pdf',
					...(unlocked ? { lockedOriginal: arrived } : {}),
				}
				await route.fulfill({
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify({
						results: [row],
						total: 1,
						notificationReach: null,
					}),
				})
			},
		)
		await page.route(
			`**/apps/filinq/api/intake/documents/${uuid}/remove-password`,
			async (route) => {
				unlocked = true
				await route.fulfill({
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify({
						fileId: copy,
						name: 'factuur (unlocked).pdf',
						validationStatus: 'passed',
						validationFindings: [],
					}),
				})
			},
		)

		await go(page, 'intake')
		const row = page.getByRole('row', {
			name: new RegExp(`${TEST_PREFIX} versleutelde factuur`),
		})
		await expect(row).toBeVisible({ timeout: 15000 })
		await row.getByRole('button').last().click()
		await page
			.getByRole('menuitem', { name: 'Remove password' })
			.or(page.getByRole('button', { name: 'Remove password' }))
			.first()
			.click()
		const dialog = page.getByTestId('pdf-password-modal')
		await dialog
			.getByTestId('pdf-password-input')
			.locator('input')
			.fill('factuur-2026')
		const sent = page.waitForRequest((req) =>
			req.url().includes(`/intake/documents/${uuid}/remove-password`),
		)
		await dialog.getByTestId('pdf-password-submit').click()
		expect((await sent).postDataJSON()).toEqual({ password: 'factuur-2026' })

		// The worklist reloads: the row now points at the copy, keeps the arrived
		// file as lockedOriginal, and no longer offers to remove a password.
		await expect(dialog).toBeHidden({ timeout: 15000 })
		await row.getByRole('button').last().click()
		await expect(page.getByTestId('intake-remove-password')).toHaveCount(0)
	})
})

/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * work-document-notes (REQ-WDN-001 to REQ-WDN-004): a note is a Nextcloud
 * comment on the document's file. It is written beside the viewer on
 * My documents, on the intake worklist and under a dossier's document, it
 * shows in Files, it stays on its own file, and My documents counts it.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * Writing and reading notes is what a clerk does, so the notes card
 * (CnNotesCard) beside the viewer, in the intake Notes dialog and under the
 * dossier viewer is clicked. Seeding is not a filinq journey: documents go in
 * through WebDAV, the waiting intake document and the dossier through their
 * APIs, and the "colleague" is a run-stamped user created through the OCS
 * provisioning API, who gets the file shared and works in a context with an
 * EMPTY storage state, so no admin session leaks into what they see.
 *
 * Notes counted on My documents (REQ-WDN-004) are seeded through Nextcloud's
 * comments DAV endpoint, the same store the card writes to, because the
 * scenario is about the listing, not the composer.
 *
 * The anonymised-copy scenario needs a document OpenRegister has analysed,
 * which needs the anonymisation backend: it reads the file from
 * FILINQ_E2E_ANALYSED_FILE_ID and is skipped, naming that, when it is unset.
 * The copy's notes are read from the comments endpoint of the copy's file id,
 * because the viewer always shows the notes of the file as opened.
 *
 * "No access, no notes" is `@e2e exclude` in the spec (Nextcloud's comments
 * endpoint enforces it; Newman covers it).
 *
 * Not run in the change that wrote it: the live pass runs it.
 */

import type {
	APIRequestContext,
	Browser,
	BrowserContext,
	Locator,
	Page,
} from '@playwright/test'

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

/** The folder My documents lists. */
const DOCS_FOLDER = 'DocuDesk'

/** Provisioning and sharing. */
const USERS_API = '/ocs/v1.php/cloud/users'
const SHARES_API = '/ocs/v2.php/apps/files_sharing/api/v1/shares?format=json'
const OCS = { 'OCS-APIRequest': 'true' }

/** OpenRegister's object API for the intake register. */
const INTAKE_OBJECTS =
	'/index.php/apps/openregister/api/objects/filinq/intakeDocument'

/** A document OpenRegister has analysed, prepared by the live pass. */
const ANALYSED_FILE_ID = process.env.FILINQ_E2E_ANALYSED_FILE_ID ?? ''

/** This run's documents, by stem. */
const STEMS = [
	'contract',
	'brief',
	'bijlage',
	'besluit',
	'nota',
	'memo',
	'intake',
].map((name) => `${TEST_PREFIX}-notes-${name}`)

/**
 * The comments collection of a file.
 *
 * @param fileId The file id.
 * @return The DAV path.
 */
function commentsOf(fileId: string): string {
	return `/remote.php/dav/comments/files/${fileId}`
}

/**
 * Write a note on a file through Nextcloud's comments endpoint.
 *
 * @param req     The request context.
 * @param token   The request token.
 * @param fileId  The file id.
 * @param message The note.
 * @return Nothing.
 */
async function writeComment(
	req: APIRequestContext,
	token: string,
	fileId: string,
	message: string,
): Promise<void> {
	const response = await req.post(commentsOf(fileId), {
		headers: { 'Content-Type': 'application/json', requesttoken: token },
		data: { actorType: 'users', verb: 'comment', message },
	})
	expect(response.status(), `writing a note on ${fileId}`).toBe(201)
}

/**
 * Read the notes of a file through Nextcloud's comments endpoint.
 *
 * @param req    The request context.
 * @param token  The request token (empty for a basic-auth context).
 * @param fileId The file id.
 * @return The response status and the body.
 */
async function readComments(
	req: APIRequestContext,
	token: string,
	fileId: string,
): Promise<{ status: number; body: string }> {
	const headers: Record<string, string> = { 'Content-Type': 'application/xml' }
	if (token !== '') {
		headers.requesttoken = token
	}
	const response = await req.fetch(commentsOf(fileId), {
		method: 'REPORT',
		headers,
		data: '<?xml version="1.0"?><oc:filter-comments xmlns:D="DAV:" xmlns:oc="http://owncloud.org/ns"><oc:limit>50</oc:limit><oc:offset>0</oc:offset></oc:filter-comments>',
	})
	return { status: response.status(), body: await response.text() }
}

/**
 * Write a note in the notes card a locator holds.
 *
 * @param card The notes card (or the dialog that holds it).
 * @param text The note.
 * @return Nothing.
 */
async function addNote(card: Locator, text: string): Promise<void> {
	const openComposer = card.getByTestId('cn-notes-card-open-composer')
	if (await openComposer.isVisible().catch(() => false)) {
		await openComposer.click()
	}
	// The composer is a rich contenteditable whose attribute value is not
	// always "true" (seen on a live run, 25 Nov 2026): find it by its role.
	const box = card.getByRole('textbox').first()
	await expect(box).toBeVisible({ timeout: 15000 })
	await box.click()
	await box.fill(text)
	await card.getByRole('button', { name: 'Add note' }).last().click()
	await expect(
		card.locator('.cn-notes-card__body').filter({ hasText: text }),
	).toBeVisible({ timeout: 15000 })
}

/**
 * The My documents row of a seeded document.
 *
 * @param page The page.
 * @param stem The document's name without extension.
 * @return The row locator.
 */
function rowOf(page: Page, stem: string): Locator {
	return page.locator('tr').filter({ hasText: stem }).first()
}

/**
 * A browser context for a colleague on an empty storage state, logged in
 * through the login form, with basic auth for API calls.
 *
 * @param browser  The browser.
 * @param userId   The colleague.
 * @param password Their password.
 * @return The context.
 */
async function colleagueContext(
	browser: Browser,
	userId: string,
	password: string,
): Promise<BrowserContext> {
	const context = await browser.newContext({
		storageState: { cookies: [], origins: [] },
		httpCredentials: { username: userId, password },
	})
	// Basic auth covers the API calls; the Files page needs a real session
	// through the login form (seen on a live run, 25 Nov 2026).
	const login = await context.newPage()
	await login.goto('/index.php/login', { waitUntil: 'domcontentloaded' })
	await login.locator('#user').fill(userId)
	await login.locator('#password').fill(password)
	await login.locator('button[type="submit"]').click()
	await login.waitForURL(/\/apps\//, { timeout: 60000 })
	await login.close()
	return context
}

test.describe('Notes on a document are its Nextcloud comments', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(180_000)

	let token = ''
	const fileIds: Record<string, string> = {}
	const colleague = {
		userId: `${TEST_PREFIX}-colleague`,
		password: 'Pw-notes-2026!Strong#e2e',
	}
	let colleagueMade = false

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect([201, 405]).toContain(
			await createDavFolder(page.request, token, DOCS_FOLDER),
		)
		for (const stem of STEMS) {
			const seeded = await createDavFile(
				page.request,
				token,
				`${DOCS_FOLDER}/${stem}.txt`,
				`Contents of ${stem}.`,
			)
			expect(seeded.status, `seeding ${stem}`).toBeLessThan(300)
			fileIds[stem] = seeded.fileId
		}

		const made = await page.request.post(USERS_API, {
			headers: OCS,
			// The provisioning API reads `userid`, not `userId` (400 otherwise).
			form: { userid: colleague.userId, password: colleague.password },
		})
		expect(made.ok(), `create the colleague -> ${made.status()}`).toBeTruthy()
		colleagueMade = true
		for (const stem of [STEMS[0], STEMS[6]]) {
			const shared = await page.request.post(SHARES_API, {
				headers: OCS,
				form: {
					path: `/${DOCS_FOLDER}/${stem}.txt`,
					shareType: '0',
					shareWith: colleague.userId,
					permissions: '19',
				},
			})
			expect(shared.ok(), `share ${stem} -> ${shared.status()}`).toBeTruthy()
		}
		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		const cleanupToken = await harvestToken(page)
		for (const stem of STEMS) {
			await deleteDavPath(
				page.request,
				cleanupToken,
				`${DOCS_FOLDER}/${stem}.txt`,
			)
		}
		if (colleagueMade) {
			await page.request.delete(`${USERS_API}/${colleague.userId}`, {
				headers: OCS,
			})
		}
		await page.close()
	})

	// @e2e document-notes::a-note-written-in-filinq-shows-in-files
	test('a note written beside the viewer shows in the Files comments', async ({
		page,
		browser,
	}) => {
		const note = 'Annex 2 is missing, asked the supplier'
		await go(page, 'my-documents')
		const row = rowOf(page, STEMS[0])
		await expect(row).toBeVisible({ timeout: 30000 })
		await row.click()

		const card = page.getByTestId('file-viewer-notes')
		await expect(card).toBeVisible({ timeout: 30000 })
		await addNote(card, note)

		const context = await colleagueContext(
			browser,
			colleague.userId,
			colleague.password,
		)
		try {
			// The session cookie from the login form needs its request token.
			const colleaguePage = await context.newPage()
			const colleagueToken = await harvestToken(colleaguePage)
			await colleaguePage.close()
			const stored = await readComments(
				context.request,
				colleagueToken,
				fileIds[STEMS[0]],
			)
			expect(
				stored.status,
				'the colleague may read the shared file comments',
			).toBe(207)
			expect(
				stored.body,
				'the note is a Nextcloud comment on the file',
			).toContain(note)

			const files = await context.newPage()
			// `/f/<id>` opens the file in the viewer, not its details. The
			// shared file sits in the colleague's root folder; `opendetails`
			// opens the sidebar on it (seen on a live run, 25 Nov 2026).
			await files.goto(`/index.php/apps/files/files/${fileIds[STEMS[0]]}?dir=/&opendetails=true`, {
				waitUntil: 'domcontentloaded',
			})
			const sidebar = files.locator('#app-sidebar-vue, .app-sidebar').first()
			await expect(sidebar).toBeVisible({ timeout: 30000 })
			const tab = sidebar
				.getByRole('tab', { name: /Comments|Activity/ })
				.first()
			if (await tab.isVisible().catch(() => false)) {
				await tab.click()
			}
			await expect(sidebar.getByText(note)).toBeVisible({ timeout: 30000 })
		} finally {
			await context.close()
		}
	})

	// @e2e document-notes::a-registrar-leaves-a-line-on-the-intake-worklist
	test('a note left on an intake row is read with its author and time', async ({
		page,
		browser,
	}) => {
		const note = 'Check with finance before assigning'
		const subject = `${TEST_PREFIX} waiting letter`
		const created = await page.request.post(INTAKE_OBJECTS, {
			headers: jsonHeaders(token),
			data: {
				channel: 'mail',
				subject,
				sender: `${TEST_PREFIX}@voorbeeld.nl`,
				receivedAt: new Date().toISOString(),
				sourceRef: `${TEST_PREFIX}-notes-intake`,
				status: 'received',
				file: Number(fileIds[STEMS[6]]),
				fileName: `${STEMS[6]}.txt`,
			},
		})
		expect(created.status(), await created.text()).toBeLessThan(300)

		await go(page, 'intake')
		const row = page.locator('tr').filter({ hasText: subject }).first()
		await expect(row).toBeVisible({ timeout: 30000 })
		await row
			.locator('.action-item button, button[aria-haspopup]')
			.last()
			.click()
		await page.getByRole('menuitem', { name: 'Notes' }).click()

		const dialog = page.getByTestId('intake-notes-dialog')
		await expect(dialog).toBeVisible({ timeout: 15000 })
		await addNote(dialog, note)
		const written = dialog
			.locator('.cn-notes-card__note')
			.filter({ hasText: note })
		await expect(written.locator('.cn-notes-card__author')).not.toBeEmpty()
		await expect(written.locator('.cn-notes-card__time')).not.toBeEmpty()

		// The colleague reads the same line: the file is shared with them, so
		// the comment is theirs to read. Whether they see the intake row too
		// depends on the intake register's group rights on the instance.
		const context = await colleagueContext(
			browser,
			colleague.userId,
			colleague.password,
		)
		try {
			const colleaguePage = await context.newPage()
			const colleagueToken = await harvestToken(colleaguePage)
			await colleaguePage.close()
			const stored = await readComments(
				context.request,
				colleagueToken,
				fileIds[STEMS[6]],
			)
			expect(stored.status).toBe(207)
			expect(stored.body).toContain(note)
		} finally {
			await context.close()
		}
	})

	// @e2e document-notes::each-document-of-a-dossier-has-its-own-notes
	test('each document of a dossier shows only its own notes', async ({ page }) => {
		const note = `${TEST_PREFIX} only on the letter`
		const made = await page.request.post(`${API}/dossiers`, {
			headers: jsonHeaders(token),
			data: { name: `${TEST_PREFIX} notes dossier` },
		})
		expect(made.status(), await made.text()).toBe(201)
		const dossierId = (await made.json()).id
		for (const stem of [STEMS[1], STEMS[2]]) {
			const linked = await page.request.post(
				`${API}/dossiers/${dossierId}/documents`,
				{
					headers: jsonHeaders(token),
					data: { fileId: Number(fileIds[stem]) },
				},
			)
			expect(linked.status(), await linked.text()).toBeLessThan(300)
		}
		await writeComment(page.request, token, fileIds[STEMS[1]], note)

		await go(page, `dossiers/${dossierId}`)
		const list = page.locator('.dossier-document-list')
		await expect(list).toBeVisible({ timeout: 30000 })
		const notes = page.getByTestId('dossier-document-notes')

		await list
			.locator('.dossier-document-button')
			.filter({ hasText: STEMS[1] })
			.click()
		await expect(notes.getByText(note)).toBeVisible({ timeout: 15000 })

		await list
			.locator('.dossier-document-button')
			.filter({ hasText: STEMS[2] })
			.click()
		await expect(notes).toBeVisible({ timeout: 15000 })
		await expect(
			notes.getByText(note),
			'the note stays under the document it was written on',
		).toHaveCount(0)
	})

	// @e2e document-notes::the-anonymised-copy-starts-clean
	test('the anonymised copy carries no notes', async ({ page }) => {
		test.skip(
			ANALYSED_FILE_ID === '',
			'needs FILINQ_E2E_ANALYSED_FILE_ID: an admin document whose findings are all certain (an e-mail address, no phone number)',
		)
		await writeComment(
			page.request,
			token,
			ANALYSED_FILE_ID,
			`${TEST_PREFIX} mentions Jan Jansen`,
		)

		// Anonymising takes the entities to redact and a checked mark on
		// exactly those entities (the review gate), as the UI sends them:
		// read the analysis, mark it checked, then anonymise (an empty body
		// is 400 'No entities provided', seen on a live run, 25 Nov 2026).
		const analysed = await page.request.post(
			`${API}/anonymization/extract/${ANALYSED_FILE_ID}`,
			{ headers: jsonHeaders(token), data: {} },
		)
		expect(analysed.status(), await analysed.text()).toBe(200)
		const found = ((await analysed.json()).entities ?? []) as Array<{
			type: string
			value: string
			confidence: string | number
		}>
		expect(found.length, 'the analysed document has entities').toBeGreaterThan(0)
		const entities = found
			.map((e) => ({ type: e.type, value: e.value, confidence: e.confidence }))
			.sort((a, b) => (b.value || '').length - (a.value || '').length)
		// The document review mark the anonymise gate reads (needs every
		// uncertain finding decided: use a document with certain findings).
		const reviewed = await page.request.post(
			`${API}/review/${ANALYSED_FILE_ID}/check`,
			{ headers: jsonHeaders(token), data: {} },
		)
		expect(reviewed.status(), await reviewed.text()).toBeLessThan(300)
		const checked = await page.request.post(
			`${API}/redaction/documents/${ANALYSED_FILE_ID}/checked`,
			{ headers: jsonHeaders(token), data: { entities } },
		)
		expect(checked.status(), await checked.text()).toBeLessThan(300)
		const anonymised = await page.request.post(
			`${API}/anonymization/anonymize/${ANALYSED_FILE_ID}`,
			{
				headers: jsonHeaders(token),
				data: { entities },
			},
		)
		expect(anonymised.status(), await anonymised.text()).toBe(200)
		const answer = JSON.stringify(await anonymised.json())
		const copyId = /"anonymi[sz]edFileId"\s*:\s*"?(\d+)/.exec(answer)?.[1] ?? ''
		expect(copyId, 'the answer names the anonymised copy').not.toBe('')
		expect(copyId).not.toBe(ANALYSED_FILE_ID)

		const copyNotes = await readComments(page.request, token, copyId)
		expect(copyNotes.status).toBe(207)
		expect(copyNotes.body, 'the copy starts without notes').not.toContain(
			'<oc:message>',
		)
	})

	// D5 for shares: a link serves the file, never its comments (task 3.1).
	test('a share link of a document with a note does not carry the note', async ({
		page,
		browser,
	}) => {
		const note = `${TEST_PREFIX} internal: call the supplier first`
		await writeComment(page.request, token, fileIds[STEMS[0]], note)

		const shared = await page.request.post(SHARES_API, {
			headers: { ...OCS, Accept: 'application/json' },
			form: { path: `/${DOCS_FOLDER}/${STEMS[0]}.txt`, shareType: '3', permissions: '1' },
		})
		expect(shared.ok(), `share by link -> ${shared.status()}`).toBeTruthy()
		const shareToken = String((await shared.json()).ocs?.data?.token ?? '')
		expect(shareToken).not.toBe('')

		// An outsider with the link: no session at all.
		const outsider = await browser.newContext({
			storageState: { cookies: [], origins: [] },
		})
		try {
			const download = await outsider.request.get(
				`/index.php/s/${shareToken}/download`,
			)
			expect(download.status()).toBe(200)
			const body = await download.text()
			expect(body).toContain(`Contents of ${STEMS[0]}.`)
			expect(body, 'the shared file carries no note').not.toContain(note)

			const landing = await outsider.newPage()
			await landing.goto(`/index.php/s/${shareToken}`, {
				waitUntil: 'domcontentloaded',
			})
			await landing.waitForTimeout(2000)
			await expect(landing.getByText(note)).toHaveCount(0)
		} finally {
			await outsider.close()
			const id = (await shared.json().catch(() => ({}))).ocs?.data?.id
			if (id) {
				await page.request.delete(
					`/ocs/v2.php/apps/files_sharing/api/v1/shares/${id}`,
					{ headers: OCS },
				)
			}
		}
	})

	// @e2e document-notes::a-clerk-spots-the-documents-with-notes
	test('My documents counts the notes of a document on its row', async ({
		page,
	}) => {
		const counted = STEMS[3]
		await writeComment(
			page.request,
			token,
			fileIds[counted],
			`${TEST_PREFIX} first`,
		)
		await writeComment(
			page.request,
			token,
			fileIds[counted],
			`${TEST_PREFIX} second`,
		)

		await go(page, 'my-documents')
		const row = rowOf(page, counted)
		await expect(row).toBeVisible({ timeout: 30000 })
		await expect(row.getByTestId('my-documents-notes-count')).toHaveText('2')
		for (const stem of [STEMS[4], STEMS[5]]) {
			await expect(
				rowOf(page, stem).getByTestId('my-documents-notes-count'),
			).toHaveCount(0)
		}
	})
})

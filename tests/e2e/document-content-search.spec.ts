/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * work-content-search (REQ-WCS-001 to REQ-WCS-003): a clerk finds a document
 * by the words in it, from My documents and from a record's documents, only
 * files she can open come back, and a list says how many documents have not
 * been read yet.
 *
 * Built: tasks 1.1 to 2.4 of the change (the switch on My documents, the
 * content mode of `caseDocuments#files`, the unread count with Read them
 * now, the `file` route parameter), and OpenRegister's scoped content query
 * (ConductionNL/openregister#11044). The selectors below are the built
 * screen's `data-testid`s. Written in the cloud lane without a live
 * instance: this spec runs in CI and the nightly, against an OpenRegister
 * that carries `ScopedContentSearchService`.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The search bar and its switch on My documents are what a clerk uses, so
 * they are clicked. Documents go in through WebDAV and their text is read by
 * OpenRegister's extract route (`POST /api/files/{fileId}/extract`, design
 * D4), because seeding is not a filinq journey. The record list is asked
 * through `caseDocuments#files`, the route the documents widget reads; the
 * widget renders on other apps' record pages, which this suite does not
 * host. The colleague with a private document is a run-stamped user on an
 * EMPTY storage state.
 *
 * "An older OpenRegister" and "A hit in the top bar opens in filinq" are
 * `@e2e exclude` in the spec; the second waits for OpenRegister's file
 * deep-link hook (#11037 item 3). The page half of REQ-WCS-004, My documents
 * opening the viewer from a `file` route parameter, is driven here.
 */

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from './spec-coverage/_helpers.ts'
import {
	API,
	createDavFile,
	harvestToken,
	jsonHeaders,
	TEST_PREFIX,
} from './workflows/_fixtures.ts'

/** The folder My documents lists. */
const DOCS_FOLDER = 'DocuDesk'

/** The word the scenarios search for: in the letter's text, not its name. */
const WORD = 'Lindenlaan'

/** OpenRegister's extract route (design D4). */
function extractRoute(fileId: string) {
	return `/index.php/apps/openregister/api/files/${fileId}/extract`
}

/** The OpenRegister objects endpoint for the document records. */
const OR_RECORDS = '/index.php/apps/openregister/api/objects/filinq/documentVersion'

/**
 * Switch My documents to "Name and content" and type the words.
 *
 * @param page  The page, on My documents.
 * @param words The words to search for.
 */
async function searchContent(page: Page, words: string): Promise<void> {
	await page.getByTestId('my-documents-search-mode-content').click()
	await expect(
		page.getByTestId('my-documents-search-mode-content'),
	).toHaveAttribute('aria-pressed', 'true')
	await page.locator('.my-documents-search input').fill(words)
}

/** Provisioning. */
const USERS_API = '/ocs/v1.php/cloud/users'
const OCS = { 'OCS-APIRequest': 'true' }

/**
 * Seed a text document and have OpenRegister read it.
 *
 * @param req      The request context.
 * @param token    The request token.
 * @param name     The file name.
 * @param contents The text.
 * @param extract  Whether to extract its text now.
 * @return The file id.
 */
async function seed(
	req: APIRequestContext,
	token: string,
	name: string,
	contents: string,
	extract = true,
): Promise<string> {
	const seeded = await createDavFile(
		req,
		token,
		`${DOCS_FOLDER}/${name}`,
		contents,
	)
	expect(seeded.status, `seeding ${name}`).toBeLessThan(300)
	if (extract) {
		const read = await req.post(extractRoute(seeded.fileId), {
			headers: jsonHeaders(token),
		})
		expect(read.status(), `extracting ${name}`).toBeLessThan(300)
	}
	return seeded.fileId
}

test.describe('Documents are found by the words in them', () => {
	test.setTimeout(120_000)

	// @e2e document-content-search::a-woo-coordinator-finds-a-letter-by-a-street-name
	test('My documents finds a letter by a word in its text, with the word marked', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')
		const token = await harvestToken(page)
		const name = `${TEST_PREFIX}-brief-aan-bewoners.txt`
		await seed(
			page.request,
			token,
			name,
			`Beste bewoners van de ${WORD}, de werkzaamheden beginnen maandag.`,
		)

		const fileId = await seed(
			page.request,
			token,
			`${TEST_PREFIX}-brief-${WORD.length}.txt`,
			`Tweede brief over de ${WORD}.`,
		)

		await go(page, 'my-documents')
		await expect(
			page.getByTestId('my-documents-search-mode-name'),
			'Name is the default mode',
		).toHaveAttribute('aria-pressed', 'true')
		await searchContent(page, WORD)

		const row = page
			.locator('tr')
			.filter({ hasText: name.replace(/\.txt$/, '') })
			.first()
		await expect(row).toBeVisible({ timeout: 30000 })
		await expect(row.locator('mark'), 'the snippet marks the word').toHaveText(
			WORD,
		)
		await expect(
			page.getByTestId(`my-documents-snippet-${fileId}`).locator('mark'),
		).toHaveText(WORD)
	})

	// @e2e document-content-search::a-clerk-searches-the-documents-of-a-record
	test("a record's documents are searched by content", async ({ page }) => {
		await page.goto('/index.php/apps/filinq/')
		const token = await harvestToken(page)
		const record = {
			register: 'filinq',
			schema: 'dossier',
			id: `${TEST_PREFIX}-record`,
		}

		// One word, no hyphen, so every database's text query reads it whole.
		const unique = `Kastanjeweg${Date.now()}`
		const fileId = await seed(
			page.request,
			token,
			`${TEST_PREFIX}-record-letter.txt`,
			`Het bezwaar over de ${unique} is ontvangen.`,
		)
		const linked = await page.request.post(OR_RECORDS, {
			headers: jsonHeaders(token),
			data: {
				fileId: Number(fileId),
				documentName: `${TEST_PREFIX}-record-letter`,
				status: 'draft',
				domains: [record],
			},
		})
		expect(linked.status(), 'linking the letter to the record').toBeLessThan(300)

		// The content mode of the flat list the documents widget reads (design D2).
		const answer = await page.request.get(`${API}/case-documents/files`, {
			headers: jsonHeaders(token),
			params: { ...record, search: unique, mode: 'content' },
		})
		expect(answer.status(), await answer.text()).toBe(200)
		const body = await answer.json()
		expect(body.content?.available, 'OpenRegister answers the content query').toBe(true)
		const hits = (body.results ?? []).filter(
			(row: { fileId?: number | string }) => String(row.fileId) === String(fileId),
		)
		expect(hits, 'the letter is found by a word that is not in its name').toHaveLength(1)
		expect(hits[0].match).toBe('content')
		expect(String(hits[0].snippet ?? '')).toContain(unique)
	})

	// @e2e document-content-search::unread-documents-are-named-not-hidden
	test('a content search names the documents that were never read', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')
		const token = await harvestToken(page)
		for (let index = 0; index < 3; index++) {
			await seed(
				page.request,
				token,
				`${TEST_PREFIX}-unread-${index}.txt`,
				`Ongelezen ${index}.`,
				false,
			)
		}

		await go(page, 'my-documents')
		await searchContent(page, WORD)
		// Design D4: the count line and its action.
		await expect(page.getByTestId('my-documents-search-note')).toContainText(
			/\d+ documents? (has|have) not been read yet and cannot be found by content/,
			{ timeout: 30000 },
		)
		const readNow = page.getByTestId('my-documents-read-now')
		await expect(readNow).toHaveText('Read them now')
		await readNow.click()
		// The reading runs on the server; the search then runs again.
		await expect(readNow).toBeEnabled({ timeout: 60000 })
	})

	// @e2e document-content-search::a-colleagues-private-document-stays-out
	test("a colleague's private document never shows in the results", async ({
		browser,
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')
		const adminToken = await harvestToken(page)
		const coordinator = {
			userid: `${TEST_PREFIX}-coordinator`,
			password: 'Pw-search-2026!Strong#e2e',
		}
		const made = await page.request.post(USERS_API, {
			headers: OCS,
			form: coordinator,
		})
		expect(made.ok(), `create the coordinator -> ${made.status()}`).toBeTruthy()

		// The admin's own document, never shared with the coordinator.
		const privateName = `${TEST_PREFIX}-prive-notitie.txt`
		await seed(
			page.request,
			adminToken,
			privateName,
			`Afspraak over de ${WORD} met de wethouder.`,
		)

		const context = await browser.newContext({
			storageState: { cookies: [], origins: [] },
			httpCredentials: {
				username: coordinator.userid,
				password: coordinator.password,
			},
		})
		try {
			const own = await context.newPage()
			await go(own, 'my-documents')
			await searchContent(own, WORD)
			await own.waitForTimeout(2000)
			await expect(
				own.getByText(privateName.replace(/\.txt$/, '')),
			).toHaveCount(0)
			await expect(own.getByText('met de wethouder')).toHaveCount(0)
		} finally {
			await context.close()
			await page.request.delete(`${USERS_API}/${coordinator.userid}`, {
				headers: OCS,
			})
		}
	})

	// @e2e document-content-search::a-document-link-opens-the-viewer
	test('My documents opens the viewer on the file a link names', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')
		const token = await harvestToken(page)
		const name = `${TEST_PREFIX}-deep-link.txt`
		const fileId = await seed(page.request, token, name, 'Een document met een eigen link.', false)

		// REQ-WCS-004, the page half: `?file=<id>` opens the viewer on that file.
		await go(page, `my-documents?file=${fileId}`)
		const viewer = page.locator('.file-viewer-page')
		await expect(viewer).toBeVisible({ timeout: 30000 })
		await expect(viewer).toContainText(name.replace(/\.txt$/, ''))
	})

	// @e2e document-content-search::a-document-link-opens-the-viewer
	test('a link to a file one cannot open does not open it', async ({ page }) => {
		await go(page, 'my-documents?file=999999999')
		await expect(
			page.getByText('The linked document could not be found, or you do not have access to it.'),
		).toBeVisible({ timeout: 30000 })
		await expect(page.locator('.file-viewer-page')).toHaveCount(0)
	})
})

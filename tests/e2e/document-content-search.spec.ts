/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * work-content-search (REQ-WCS-001 to REQ-WCS-003): a clerk finds a document
 * by the words in it, from My documents and from a record's documents, only
 * files she can open come back, and a list says how many documents have not
 * been read yet.
 *
 * ⚠️ THE FEATURE IS NOT BUILT YET. Tasks 1.1 to 2.4 of the change are open:
 * there is no "Name and content" switch, no content mode on
 * `caseDocuments#files`, and OpenRegister has no access-checked, file-scoped
 * content query yet (task 3.2). This spec is written from the scenarios and
 * design.md D2 to D4 against the PLANNED screen, and every test is
 * `test.fixme` (with the reason in NOT_BUILT) so the suite does not go red. Names marked PLANNED below
 * (the switch label, the snippet's <mark>, the unread line, the `mode`
 * parameter) follow the design; the builder of tasks 1.2 to 2.3 checks
 * them against what they build and lifts the fixme.
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
 * `@e2e exclude` in the spec.
 */

import type { APIRequestContext } from '@playwright/test'

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

/** Why every test waits. */
const NOT_BUILT =
	'work-content-search tasks 1.1 to 2.3 are not built; OpenRegister has no scoped content query yet'

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

	// @e2e openspec/changes/work-content-search/specs/document-content-search/spec.md#a-woo-coordinator-finds-a-letter-by-a-street-name
	test('My documents finds a letter by a word in its text, with the word marked', async ({
		page,
	}) => {
		test.fixme(true, NOT_BUILT)
		await page.goto('/index.php/apps/filinq/')
		const token = await harvestToken(page)
		const name = `${TEST_PREFIX}-brief-aan-bewoners.txt`
		await seed(
			page.request,
			token,
			name,
			`Beste bewoners van de ${WORD}, de werkzaamheden beginnen maandag.`,
		)

		await go(page, 'my-documents')
		// PLANNED (design D3): a switch beside the search bar, "Name" by default.
		await page.getByRole('radio', { name: 'Name and content' }).check()
		await page.locator('.my-documents-search input').fill(WORD)

		const row = page
			.locator('tr')
			.filter({ hasText: name.replace(/\.txt$/, '') })
			.first()
		await expect(row).toBeVisible({ timeout: 30000 })
		await expect(row.locator('mark'), 'the snippet marks the word').toHaveText(
			WORD,
		)
	})

	// @e2e openspec/changes/work-content-search/specs/document-content-search/spec.md#a-clerk-searches-the-documents-of-a-record
	test("a record's documents are searched by content", async ({ page }) => {
		test.fixme(true, NOT_BUILT)
		await page.goto('/index.php/apps/filinq/')
		const token = await harvestToken(page)
		const record = {
			register: 'filinq',
			schema: 'dossier',
			id: `${TEST_PREFIX}-record`,
		}

		const unique = `${TEST_PREFIX}-only-here`
		const answer = await page.request.get(`${API}/case-documents/files`, {
			headers: jsonHeaders(token),
			// PLANNED (design D2): the content mode of the flat list.
			params: { ...record, search: unique, mode: 'content' },
		})
		expect(answer.status(), await answer.text()).toBe(200)
		const body = await answer.json()
		expect(body.results).toHaveLength(1)
		expect(String(body.results[0].snippet ?? '')).toContain(unique)
	})

	// @e2e openspec/changes/work-content-search/specs/document-content-search/spec.md#unread-documents-are-named-not-hidden
	test('a content search names the documents that were never read', async ({
		page,
	}) => {
		test.fixme(true, NOT_BUILT)
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
		await page.getByRole('radio', { name: 'Name and content' }).check()
		await page.locator('.my-documents-search input').fill(WORD)
		// PLANNED (design D4): the count line and its action.
		await expect(
			page.getByText(
				/\d+ documents have not been read yet and cannot be found by content/,
			),
		).toBeVisible({ timeout: 30000 })
		await expect(
			page.getByRole('button', { name: 'Read them now' }),
		).toBeVisible()
	})

	// @e2e openspec/changes/work-content-search/specs/document-content-search/spec.md#a-colleague-s-private-document-stays-out
	test("a colleague's private document never shows in the results", async ({
		browser,
		page,
	}) => {
		test.fixme(true, NOT_BUILT)
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
			await own.getByRole('radio', { name: 'Name and content' }).check()
			await own.locator('.my-documents-search input').fill(WORD)
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
})

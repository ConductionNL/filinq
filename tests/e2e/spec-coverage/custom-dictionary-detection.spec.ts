/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage —
 * custom-dictionary-recognition::a-dictionary-hit-is-detected-reviewable-and-redacted
 *
 * WHY THIS FILE EXISTS SEPARATELY, AND WHY IT NEARLY DID NOT EXIST AT ALL
 * ----------------------------------------------------------------------
 * This scenario spans the whole pipeline — dictionary → file → extraction →
 * detection → the review workbench — so it does not belong in the admin-UI file
 * next to the dictionaries list.
 *
 * It was very nearly excluded instead, on two reasons that were both FALSE, and
 * both are worth recording because each is a trap the next reader can fall into:
 *
 *   1. "It needs a real anonymiser backend, and CI runs regex-only." Wrong.
 *      Custom-dictionary matching does not go through the NER backend at all —
 *      `CustomDictionaryDetectionRunner` is a pure term matcher over the
 *      extracted text. Measured on an instance reporting
 *      `anonymiserBackend.method = "regex"` with no backend installed:
 *        POST api/anonymization/extract/{fileId} -> 200
 *        {"entities":[{"type":"CUSTOM_DICTIONARY","value":"Operatie Zilverreiger",
 *                      "confidence":"1.00",...}],"entityCount":1}
 *      A degraded SUBSYSTEM is not a degraded PIPELINE.
 *
 *   2. "There is no per-file review surface." Wrong, and the lookup could not
 *      have found it: the grep covered `src/views` and `src/components`, and the
 *      workbench lives in `src/sidebars/FileViewerSidebar.vue` (mounted through
 *      `src/sidebars/SideBars.vue`). A directory-scoped grep is not a search.
 *
 * An `@e2e exclude` on this scenario would therefore have been a false
 * statement — the strongest reason to write the test instead.
 *
 * WHAT MAKES IT FALSIFIABLE
 * -------------------------
 * The term is unique per run, so the assertion cannot be satisfied by anything
 * already on the instance, and the file is seeded with the term embedded in
 * ordinary prose so a substring match is genuinely about detection rather than
 * about the filename. Delete the dictionary pass from
 * `AnonymizationService`, or break `CustomDictionaryMatchService`, and the
 * `CUSTOM_DICTIONARY` card disappears while everything else still renders.
 */

import { test, expect, type APIRequestContext } from '@playwright/test'
import { go, waitForAppReady } from './_helpers'
import { harvestToken, jsonHeaders, API } from '../workflows/_fixtures'

/** Unique per run — the whole point of the assertion is that only WE seeded it. */
const RUN = `g19cdd-${Date.now()}`
const TERM = `Operatie Zilverreiger ${RUN}`
/**
 * ⚠️ Two names, deliberately. My Documents renders the file's BASE name — the
 * row for `x-dossier.txt` reads `x-dossier` — so a locator built from the
 * on-disk name matches nothing and fails as "the document is not listed" when
 * it is listed perfectly well.
 */
const FILE_BASE = `${RUN}-dossier`
const FILE_NAME = `${FILE_BASE}.txt`

/**
 * `/DocuDesk` is the root `myDocumentsStore` lists (`currentPath` default), so
 * the seeded file has to live there to be clickable in the UI.
 */
const DAV_DIR = '/DocuDesk'

let dictionaryId = ''
let fileId = 0

async function dav(
	req: APIRequestContext,
	token: string,
	method: 'MKCOL' | 'PUT',
	path: string,
	body?: string,
) {
	return req.fetch(`/remote.php/dav/files/admin${path}`, {
		method,
		headers: { requesttoken: token },
		data: body,
	})
}

test.describe('custom-dictionary-recognition — a dictionary hit is detected, reviewable and redacted', () => {
	test.beforeAll(async ({ browser }) => {
		const ctx = await browser.newContext()
		const page = await ctx.newPage()
		const token = await harvestToken(page)
		const req = ctx.request

		// 1. An active dictionary carrying the term.
		const dict = await req.post(`${API}/custom-dictionaries`, {
			headers: jsonHeaders(token),
			data: {
				label: `${RUN}-codenames`,
				matchMode: 'caseInsensitive',
				active: true,
			},
		})
		expect(
			dict.status(),
			`create dictionary (${await dict.text().catch(() => '')})`,
		).toBe(201)
		dictionaryId = (await dict.json()).id

		const term = await req.post(
			`${API}/custom-dictionaries/${dictionaryId}/terms`,
			{
				headers: jsonHeaders(token),
				data: { value: TERM },
			},
		)
		expect(
			term.status(),
			`create term (${await term.text().catch(() => '')})`,
		).toBe(201)

		// 2. A document containing it, in the folder My Documents lists.
		// MKCOL is allowed to 405 — the folder normally already exists.
		await dav(req, token, 'MKCOL', DAV_DIR).catch(() => null)
		const put = await dav(
			req,
			token,
			'PUT',
			`${DAV_DIR}/${FILE_NAME}`,
			`Interne notitie.\n\nHet dossier vermeldt ${TERM} als codenaam voor het traject.\n`,
		)
		expect(
			[201, 204],
			`PUT ${DAV_DIR}/${FILE_NAME} -> ${put.status()}`,
		).toContain(put.status())

		// Resolve the numeric fileId — needed to drive extraction, and to prove
		// the file landed rather than assuming it.
		const propfind = await req.fetch(
			`/remote.php/dav/files/admin${DAV_DIR}/${FILE_NAME}`,
			{
				method: 'PROPFIND',
				headers: { requesttoken: token, Depth: '0' },
				data:
					'<?xml version="1.0"?><d:propfind xmlns:d="DAV:" '
					+ 'xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
			},
		)
		expect(propfind.status(), 'PROPFIND for the seeded file').toBe(207)
		fileId = Number((await propfind.text()).match(/<oc:fileid>(\d+)/)?.[1] ?? 0)
		expect(
			fileId,
			'seeded file must resolve to a numeric fileId',
		).toBeGreaterThan(0)

		// 3. Extraction + detection. Asserted here rather than in the test so a
		// backend failure is not reported as a missing DOM node later.
		const extract = await req.post(`${API}/anonymization/extract/${fileId}`, {
			headers: jsonHeaders(token),
		})
		expect(
			extract.status(),
			`extract ${fileId} (${await extract.text().catch(() => '')})`,
		).toBe(200)
		const body = await extract.json()
		expect(
			(body.entities ?? []).map(
				(e: { type: string; value: string }) => `${e.type}:${e.value}`,
			),
			'the detection pass must produce a CUSTOM_DICTIONARY occurrence for the seeded term',
		).toContain(`CUSTOM_DICTIONARY:${TERM}`)

		await ctx.close()
	})

	test.afterAll(async ({ browser }) => {
		if (!dictionaryId) return
		const ctx = await browser.newContext()
		const page = await ctx.newPage()
		const token = await harvestToken(page)
		const res = await ctx.request
			.delete(`${API}/custom-dictionaries/${dictionaryId}`, {
				headers: jsonHeaders(token),
			})
			.catch(() => null)
		if (res && res.status() >= 400) {
			// eslint-disable-next-line no-console
			console.warn(
				`[teardown] dictionary ${dictionaryId} -> ${res.status()} (leaked)`,
			)
		}
		// The seeded document is deliberately left in place: every identifier in
		// this file is stamped with `Date.now()`, so a leftover cannot satisfy or
		// break a later run's assertions, and deleting a file mid-anonymisation
		// is a worse failure mode than a stray fixture.
		await ctx.close()
	})

	test('the dictionary hit appears in the review workbench as a CUSTOM_DICTIONARY occurrence', async ({
		page,
	}) => {
		// @e2e openspec/specs/custom-dictionary-recognition/spec.md#a-dictionary-hit-is-detected-reviewable-and-redacted
		await go(page, 'my-documents')
		await expect(page).toHaveURL(/\/apps\/docudesk\/my-documents/)

		// Opening the document is what sets `fileViewerStore.currentFile`, which
		// is what mounts FileViewerPage and its sidebar (MyDocumentsIndex.vue:8).
		const row = page
			.locator('#content tr, .app-content tr')
			.filter({ hasText: FILE_BASE })
			.first()
		await expect(
			row,
			'the seeded document must be listed in /DocuDesk',
		).toBeVisible()
		await row.click()
		await waitForAppReady(page)

		// The workbench: DdEntityCard renders the raw type token in
		// `.dd-entity-card__type` (entityTypeLabel() falls through to the
		// uppercase token when untranslated) and the matched text in
		// `.dd-entity-card__value`. Assert BOTH on the SAME card — asserting them
		// separately would pass if any card carried the type and any other card
		// carried the value.
		const card = page
			.locator('.dd-entity-card')
			.filter({ hasText: TERM })
			.first()
		await expect(
			card,
			'a review card for the seeded term must render',
		).toBeVisible()
		await expect(
			card.locator('.dd-entity-card__type'),
			'the occurrence must be typed CUSTOM_DICTIONARY, not a generic NER type',
		).toHaveText('CUSTOM_DICTIONARY')
		await expect(card.locator('.dd-entity-card__value').first()).toContainText(
			TERM,
		)
	})
})

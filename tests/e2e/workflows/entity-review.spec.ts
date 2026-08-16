/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Behavioural coverage for `anonymization-entity-review`.
 *
 * WHY THIS FILE EXISTS AT ALL
 * ---------------------------
 * Every scenario in that spec was blanket-excluded from gate-19 by ONE
 * whole-spec `@e2e exclude` whose reason said the consolidated-entities
 * endpoint and the review table "are unbuilt" in "the current DocuDesk release
 * (v0.0.34)". Both halves were falsifiable in one command each and both are
 * false at 0.0.46:
 *
 *   POST /api/anonymization/batch/folder            -> 200
 *   POST /api/anonymization/batch/{id}/extract      -> 200 {"batchStatus":"review"}
 *   GET  /api/anonymization/batch/{id}/entities     -> 200 (2 entities, with
 *                                                     prohibitionMatch + suggestedBases)
 *
 * and the review table renders on `/folder-anonymization` the moment the store
 * reaches `batchStatus === 'review'`. See ConductionNL/docudesk#431.
 *
 * WHAT IS AND IS NOT COVERED HERE
 * -------------------------------
 * This instance — and CI — run the anonymiser in `regex` mode with no NER
 * sidecar, so the ONLY entity type the extraction pass can produce is
 * `CUSTOM_DICTIONARY` (verified: a document containing a BSN, an IBAN, an
 * e-mail address and two Dutch person names yields `entityCount: 0`; the same
 * document with two dictionary terms yields exactly those two). That is an
 * environment fact, not a defect, and it is asserted loudly in `beforeAll`
 * rather than skipped around — a self-skip is indistinguishable from a healthy
 * run in the summary line.
 *
 * Three consequences, stated so nobody "fixes" them by weakening a test:
 *   - `sort-entities-by-confidence` needs two DIFFERENT confidences to
 *     discriminate; dictionary hits are all 1.00, so a sorted and an unsorted
 *     table are byte-identical here. Left UNCOVERED, not excluded.
 *   - `filter-entities-by-type` / `combined-search-and-type-filter` need a
 *     second entity TYPE. Same reason. Left UNCOVERED, not excluded.
 *   - `apply-confidence-threshold` needs an entity BELOW the threshold.
 *     Same reason. Left UNCOVERED, not excluded.
 * A scenario that cannot be discriminated on this environment is honest debt;
 * writing a test that would pass whether or not the feature works would be
 * worse than the exclusion it replaced.
 *
 * Fixtures are stamped with `Date.now()` (TEST_PREFIX) so this run's negative
 * assertions name only this run's strings: the DocuDesk consent/dictionary
 * surfaces do not all offer a working DELETE (measured: 405 on consents, 409
 * on retention-protected prohibitions), so leakage has to be made harmless
 * rather than cleaned up.
 */

import { test, expect, type APIRequestContext, type Page } from '@playwright/test'
import { appUrl, dismissOverlays, waitForAppReady } from '../spec-coverage/_helpers'
import {
	harvestToken, jsonHeaders, API, TEST_PREFIX, TEST_FAMILY,
	createDavFolder, createDavFile,
} from './_fixtures'

test.describe.configure({ mode: 'serial' })

const FOLDER = `${TEST_PREFIX}-review`
/** Two dictionary terms, run-stamped, chosen so a substring search separates them. */
const TERM_A = `Operatie Zilverreiger ${TEST_PREFIX}`
const TERM_B = `Dossier Karekiet ${TEST_PREFIX}`
/** Substring that appears in TERM_A and NOT in TERM_B. */
const NEEDLE = 'Zilverreiger'

interface Fixture {
	token: string
	batchId: string
	ruleId: string
}

let fixture: Fixture | null = null

/**
 * Provision one batch that has reached `review` and one prohibition rule that
 * matches exactly one of its two entities.
 *
 * Every status code is asserted with the URL in the message — a body parsed
 * from a 403 reads exactly like an empty success.
 *
 * @param page The page used to harvest the CSRF token.
 * @param req  The request context (inherits the admin storage state).
 * @return The provisioned fixture.
 */
async function provision(page: Page, req: APIRequestContext): Promise<Fixture> {
	if (fixture !== null) return fixture

	const token = await harvestToken(page)

	// ---- environment precondition: regex-only backend, dictionary detection on
	const settings = await req.get(`${API}/settings`, { headers: jsonHeaders(token) })
	expect(settings.status(), `GET ${API}/settings must answer 200`).toBe(200)
	const method = (await settings.json()).anonymiserBackend?.method
	expect(method,
		'PRECONDITION: these scenarios drive detection through the CUSTOM_DICTIONARY pass, '
		+ `which does not use the NER backend. This instance reports backend "${method}". `
		+ 'If this is not "regex" the fixture below may also detect PERSON/EMAIL entities '
		+ 'and the counts asserted here will be too low — that is a fixture mismatch, '
		+ 'not a product defect.').toBe('regex')

	// ---- a run-stamped dictionary with two terms
	const dict = await req.post(`${API}/custom-dictionaries`, {
		headers: jsonHeaders(token),
		data: { label: `${TEST_PREFIX}-review-dict`, description: 'entity-review fixture', matchMode: 'caseInsensitive', active: true },
	})
	expect(dict.status(), `POST ${API}/custom-dictionaries must answer 201`).toBe(201)
	const dictId = (await dict.json()).id as string

	for (const value of [TERM_A, TERM_B]) {
		const term = await req.post(`${API}/custom-dictionaries/${dictId}/terms`, {
			headers: jsonHeaders(token),
			data: { value },
		})
		expect(term.status(), `POST term "${value}" must answer 201`).toBe(201)
	}

	// ---- two documents: TERM_A in both (fileCount 2), TERM_B in one (fileCount 1)
	await createDavFolder(req, token, FOLDER)
	await createDavFile(req, token, `${FOLDER}/zaak-a.txt`,
		`Zaakdossier A\n\n${TERM_A} is afgerond.\nZie ook ${TERM_B} voor de bijlagen.\n`)
	await createDavFile(req, token, `${FOLDER}/zaak-b.txt`,
		`Zaakdossier B\n\nVervolg op ${TERM_A}.\n`)

	// ---- run the batch to `review`
	const batch = await req.post(`${API}/anonymization/batch/folder`, {
		headers: jsonHeaders(token),
		data: { folderPath: FOLDER },
	})
	expect(batch.status(), `POST ${API}/anonymization/batch/folder must answer 200`).toBe(200)
	const batchBody = await batch.json()
	const batchId = batchBody.batchId as string
	expect(batchId, 'the folder-batch response must carry a batchId').toBeTruthy()
	// ⚠️ Assert the batch actually PICKED UP the two seeded files. A batchId is
	// returned even for a folder the service found nothing in, so a truthy
	// batchId says nothing about the fixture having landed — and the next call
	// then fails in a way that reads like a broken endpoint. CI run 31527918659
	// failed at the extract below with a 404 while this assertion did not
	// exist, so that log cannot distinguish "the fixture never landed" from
	// "the batch state was lost between two requests". That is why it is here.
	expect(batchBody.fileCount,
		`the folder-batch must have found the two seeded files in ${FOLDER}. `
		+ `Response: ${JSON.stringify(batchBody).slice(0, 400)}`).toBe(2)

	const extract = await req.post(`${API}/anonymization/batch/${batchId}/extract`, {
		headers: jsonHeaders(token),
	})
	expect(extract.status(), `POST .../batch/${batchId}/extract must answer 200`).toBe(200)
	expect((await extract.json()).batchStatus,
		'the batch must reach "review" — the whole spec was excluded on the claim that it cannot')
		.toBe('review')

	// ---- one prohibition rule, matching TERM_A only
	const rule = await req.post(`${API}/policy/prohibitions`, {
		headers: jsonHeaders(token),
		data: {
			primaryName: TERM_A,
			entityType: 'OTHER',
			matchRules: [{ type: 'exact', value: TERM_A }],
			reason: `${TEST_FAMILY}entity-review fixture`,
			active: true,
		},
	})
	expect(rule.status(), `POST ${API}/policy/prohibitions must answer 201`).toBe(201)
	const ruleId = (await rule.json()).id as string

	fixture = { token, batchId, ruleId }
	return fixture
}

/**
 * Read the consolidated-entities response for the provisioned batch.
 *
 * @param req   The request context.
 * @param f     The provisioned fixture.
 * @param query Optional query string (without the leading `?`).
 * @return The parsed body.
 */
async function entities(req: APIRequestContext, f: Fixture, query = ''): Promise<Record<string, unknown>> {
	const url = `${API}/anonymization/batch/${f.batchId}/entities${query ? `?${query}` : ''}`
	const res = await req.get(url, { headers: jsonHeaders(f.token) })
	expect(res.status(), `GET ${url} must answer 200`).toBe(200)
	return await res.json()
}

// ---------------------------------------------------------------------------
// API layer — the consolidated-entities endpoint
// ---------------------------------------------------------------------------

test.describe('anonymization-entity-review — consolidated entities endpoint', () => {
	test('the endpoint returns deduplicated entities carrying every declared field', async ({ page, request }) => {
		// @e2e openspec/specs/anonymization-entity-review/spec.md#retrieve-consolidated-entities-for-review
		// @e2e openspec/specs/anonymization-entity-review/spec.md#retrieve-consolidated-entities
		const f = await provision(page, request)
		const body = await entities(request, f)
		const list = body.entities as Array<Record<string, unknown>>

		// Positive control FIRST: an absence assertion is satisfied for free by
		// an empty list, so nothing below is evaluated on one.
		expect(list.map((e) => e.value),
			'both seeded dictionary terms must be detected before anything else is asserted')
			.toEqual(expect.arrayContaining([TERM_A, TERM_B]))

		for (const e of list) {
			for (const field of ['type', 'value', 'highestConfidence', 'fileCount', 'included']) {
				expect(Object.keys(e), `every entity entry must carry "${field}"`).toContain(field)
			}
		}

		// DEDUPLICATION: TERM_A appears in BOTH documents and must collapse to
		// ONE entry whose fileCount is 2. This is the assertion that fails if
		// the endpoint stops deduplicating — a per-file list would have three
		// rows, two of them identical.
		const a = list.filter((e) => e.value === TERM_A)
		expect(a, `"${TERM_A}" occurs in two files and must appear exactly once`).toHaveLength(1)
		expect(a[0].fileCount, `"${TERM_A}" is in two documents, so fileCount must be 2`).toBe(2)
		const b = list.filter((e) => e.value === TERM_B)
		expect(b[0].fileCount, `"${TERM_B}" is in one document, so fileCount must be 1`).toBe(1)

		// SORTED BY CONFIDENCE DESCENDING.
		const conf = list.map((e) => Number(e.highestConfidence))
		expect(conf, `confidences in response order: ${conf.join(', ')}`)
			.toEqual([...conf].sort((x, y) => y - x))
	})

	test('an entity no prohibition rule matches reports prohibitionMatch null', async ({ page, request }) => {
		// @e2e openspec/specs/anonymization-entity-review/spec.md#entity-with-no-prohibition-match-returns-null
		const f = await provision(page, request)
		const list = (await entities(request, f)).entities as Array<Record<string, unknown>>
		const unmatched = list.find((e) => e.value === TERM_B)
		expect(unmatched, `positive control — "${TERM_B}" must be in the list at all`).toBeDefined()
		expect(unmatched!.prohibitionMatch,
			`no prohibition rule names "${TERM_B}" (the only rule names "${TERM_A}"), so its match must be null`)
			.toBeNull()
	})

	test('a matching prohibition rule is reported with ruleId, ruleName and highConfidence', async ({ page, request }) => {
		// @e2e openspec/specs/anonymization-entity-review/spec.md#high-confidence-prohibition-match-is-reported
		//
		// NOT anchored to `#highest-confidence-reading-is-used-across-the-batch`,
		// though it looks like it should be. That scenario asserts the MAXIMUM
		// reading is the one used (0.62 / 0.78 / 0.91 against a 0.85 threshold).
		// Every dictionary hit here is 1.00, so max, min and first-seen all give
		// the same answer and the assertion could not fail if the code took the
		// wrong one. Anchoring it would claim a discrimination this test does
		// not make.
		const f = await provision(page, request)
		const list = (await entities(request, f)).entities as Array<Record<string, unknown>>
		const matched = list.find((e) => e.value === TERM_A)
		expect(matched, `positive control — "${TERM_A}" must be in the list at all`).toBeDefined()

		const pm = matched!.prohibitionMatch as Record<string, unknown> | null
		expect(pm, `an active prohibition rule names "${TERM_A}", so its match must NOT be null`).not.toBeNull()
		expect(pm!.ruleId, 'the match must carry the id of the rule that fired').toBe(f.ruleId)
		expect(pm!.ruleName, 'the match must carry the rule primaryName').toBe(TERM_A)
		// The entity is detected in TWO files; highConfidence is derived from the
		// HIGHEST reading across the batch, which is what makes this entity
		// high-confidence at all.
		expect(matched!.fileCount, 'this assertion is only meaningful across >1 file').toBe(2)
		expect(pm!.highConfidence,
			`highestConfidence across the batch is ${matched!.highestConfidence}, which is above threshold`)
			.toBe(true)
	})

	test('files that belong to no dossier yield an empty suggestedBases', async ({ page, request }) => {
		// @e2e openspec/specs/anonymization-entity-review/spec.md#files-not-in-a-dossier-yield-empty-suggestedbases
		const f = await provision(page, request)
		const list = (await entities(request, f)).entities as Array<Record<string, unknown>>
		expect(list.length, 'positive control — the list must be populated').toBeGreaterThan(0)
		for (const e of list) {
			expect(e.suggestedBases,
				`${FOLDER} is a plain Files folder bound to no dossier, so suggestedBases must be []`)
				.toEqual([])
		}
	})

	test('the response is a strict superset of the pre-change shape', async ({ page, request }) => {
		// @e2e openspec/specs/anonymization-entity-review/spec.md#pre-change-client-continues-to-work
		const f = await provision(page, request)
		const list = (await entities(request, f)).entities as Array<Record<string, unknown>>
		expect(list.length, 'positive control — the list must be populated').toBeGreaterThan(0)

		// A pre-change client read exactly these five fields and nothing else.
		// Reading them must still work, with their original TYPES — a field that
		// changed shape (say fileCount to a string) would break such a client
		// while still "being present".
		for (const e of list) {
			expect(typeof e.type, 'type must still be a string').toBe('string')
			expect(typeof e.value, 'value must still be a string').toBe('string')
			expect(typeof e.highestConfidence, 'highestConfidence must still be a number').toBe('number')
			expect(typeof e.fileCount, 'fileCount must still be a number').toBe('number')
			expect(typeof e.included, 'included must still be a boolean').toBe('boolean')
		}
	})

	test('omitting minConfidence applies no confidence filtering at all', async ({ page, request }) => {
		// @e2e openspec/specs/anonymization-entity-review/spec.md#default-threshold-includes-all-entities
		const f = await provision(page, request)
		const bare = await entities(request, f)
		const zero = await entities(request, f, 'minConfidence=0')

		// The documented default is 0.0, so omitting the parameter and passing
		// 0.0 must be the SAME request. If the default were anything else, the
		// `included` flags would differ between these two responses.
		expect(bare.entityCount, 'positive control — the list must be populated').toBeGreaterThan(0)
		expect(JSON.stringify(bare.entities),
			'omitting minConfidence must be identical to minConfidence=0 — that is what "default 0.0" means')
			.toBe(JSON.stringify(zero.entities))
	})
})

// ---------------------------------------------------------------------------
// UI layer — the entity review table on /folder-anonymization
// ---------------------------------------------------------------------------

/**
 * Drive the Folder Analysis page through to the review step.
 *
 * @param page The page.
 * @return Resolves once the review table has rendered.
 */
async function driveToReview(page: Page): Promise<void> {
	await page.goto(await appUrl(page, 'folder-anonymization'), { waitUntil: 'domcontentloaded' })
	await waitForAppReady(page)
	await dismissOverlays(page)

	const input = page.locator('input[placeholder*="Documents/contracts"]').first()
	await input.fill(FOLDER)
	const analyze = page.getByRole('button', { name: 'Analyze Folder' })
	await expect(analyze, 'the Analyze action must enable once a path is typed').toBeEnabled()
	await analyze.click()

	// The review step is what the excluded spec called unbuilt.
	await expect(page.getByRole('heading', { name: 'Review Entities' }),
		'the folder analysis must reach the review step').toBeVisible({ timeout: 120_000 })
	await expect(page.locator('.entity-review table.entity-table'),
		'the entity review table must render').toBeVisible()

	// ⚠️ The TABLE element renders before its ROWS do — `store.entities` is
	// still being fetched. Returning here made "Deselect All Visible" a silent
	// no-op in two tests, because the component emits
	// `filteredEntities.map(i => i.idx)` and that array was EMPTY at click
	// time; the rows then arrived pre-checked and the assertion read as a
	// broken bulk action. Wait for the content, not the container.
	await expect(page.locator('.entity-review table.entity-table tbody tr'),
		'both seeded entities must have rendered before anything is clicked')
		.toHaveCount(2)
}

/**
 * The row for one entity value.
 *
 * @param page  The page.
 * @param value The entity value.
 * @return A locator for that row.
 */
function row(page: Page, value: string) {
	return page.locator('.entity-review table.entity-table tbody tr').filter({ hasText: value })
}

/**
 * A bulk-action button, scoped to the review table's own action bar.
 *
 * ⚠️ `exact: true` is load-bearing, and this cost two runs to see.
 * `getByRole`'s `name` is a SUBSTRING match by default, and
 * **"Deselect All Visible" contains "Select All Visible"** — so the obvious
 * locator resolves to BOTH buttons and dies on strict mode. `.first()` would
 * have "fixed" it by silently clicking whichever came first in the DOM, i.e.
 * possibly the opposite action, and the test would still have gone green in
 * one of the two directions.
 *
 * @param page  The page.
 * @param label The exact button label.
 * @return A locator for exactly that action.
 */
function bulk(page: Page, label: string) {
	return page.locator('.entity-review .bulk-actions')
		.getByRole('button', { name: label, exact: true })
}

test.describe('anonymization-entity-review — review table UI', () => {
	test('the review table renders a row per entity plus the summary bar', async ({ page, request }) => {
		// @e2e openspec/specs/anonymization-entity-review/spec.md#display-entity-review-table
		await provision(page, request)
		await driveToReview(page)

		const rows = page.locator('.entity-review table.entity-table tbody tr')
		await expect(rows, 'both seeded terms must have a row').toHaveCount(2)

		// Column contract: checkbox, type badge, value, confidence %, file count.
		const r = row(page, TERM_A)
		await expect(r.locator('input[type="checkbox"]').first(), 'each row has an include checkbox').toBeVisible()
		// The requirement says "Type (badge)", not which words go in it. Assert
		// the badge carries THIS entity's type, whatever label the catalogue
		// resolves. ⚠️ It renders the raw token `CUSTOM_DICTIONARY` here, because
		// `src/services/entityTypes.js` ENTITY_TYPES does not list it (nor BSN,
		// DATE, NORP, …) even though the admin `enabled_entity_types` setting
		// does — so those badges show SCREAMING_SNAKE and take the fallback
		// colour. Pinning a prettified label here would have made this test fail
		// for a cosmetic reason unrelated to the scenario.
		await expect(r.locator('.badge'), 'each row has a type badge naming its entity type')
			.toHaveText(/CUSTOM_DICTIONARY|Custom dictionary/i)
		await expect(r, 'each row shows a confidence percentage').toContainText('100.0%')

		await expect(page.locator('.entity-review .summary-bar'),
			'the summary bar reports selected / total / file counts')
			.toContainText('of 2 entities selected')
	})

	test('the search box filters rows by value substring', async ({ page, request }) => {
		// @e2e openspec/specs/anonymization-entity-review/spec.md#search-entities-by-value
		await provision(page, request)
		await driveToReview(page)

		// Positive control first: assert the row that must SURVIVE the filter is
		// present, so the absence half below cannot pass on an empty table.
		await page.getByRole('textbox', { name: 'Search entities' }).fill(NEEDLE)
		await expect(row(page, TERM_A), `"${TERM_A}" contains "${NEEDLE}" and must remain listed`).toHaveCount(1)
		await expect(row(page, TERM_B), `"${TERM_B}" does not contain "${NEEDLE}" and must be filtered out`).toHaveCount(0)

		// Clearing restores both — a filter that never restores is a broken list,
		// not a working filter.
		await page.getByRole('textbox', { name: 'Search entities' }).fill('')
		await expect(page.locator('.entity-review table.entity-table tbody tr')).toHaveCount(2)
	})

	test('a previously excluded entity can be included from the table', async ({ page, request }) => {
		// @e2e openspec/specs/anonymization-entity-review/spec.md#user-includes-a-previously-excluded-entity
		await provision(page, request)
		await driveToReview(page)

		// ESTABLISH the precondition rather than assume it. ⚠️ The API returns
		// `included: false` for these entities (WOO profile), but
		// `folderAnonymization.js:159` overwrites it with a hardcoded
		// `included: true` for every row, so the table opens fully checked —
		// see the defect note at the bottom of this file. Asserting the
		// unchecked start would make this test fail for that defect instead of
		// for the behaviour the scenario is about.
		const box = row(page, TERM_B).locator('input[type="checkbox"]').first()
		await bulk(page, 'Deselect All Visible').click()
		await expect(box, 'precondition — the entity must be excluded before it can be re-included')
			.not.toBeChecked()
		await expect(page.locator('.entity-review .summary-bar')).toContainText('0 of 2 entities selected')

		await box.check()
		await expect(box, 'checking the box must set included=true in the store').toBeChecked()
		await expect(page.locator('.entity-review .summary-bar')).toContainText('1 of 2 entities selected')
	})

	test('unchecking an entity removes it from the payload the anonymise call sends', async ({ page, request }) => {
		// @e2e openspec/specs/anonymization-entity-review/spec.md#user-excludes-an-entity-from-anonymization
		await provision(page, request)
		await driveToReview(page)

		// Include BOTH, then exclude one. Asserting only the store state cannot
		// tell "the checkbox did not take" from "the payload dropped it"; the
		// scenario's second clause is about the REQUEST, so record it.
		for (const value of [TERM_A, TERM_B]) {
			await row(page, value).locator('input[type="checkbox"]').first().check()
		}
		await expect(page.locator('.entity-review .summary-bar')).toContainText('2 of 2 entities selected')

		const excluded = row(page, TERM_B).locator('input[type="checkbox"]').first()
		await excluded.uncheck()
		await expect(excluded).not.toBeChecked()
		await expect(page.locator('.entity-review .summary-bar')).toContainText('1 of 2 entities selected')

		// ⚠️ The FOLDER flow does not post to `/batch/{id}/anonymize`. Step 2 of
		// `folderAnonymization.anonymizeFolder()` fans the approved entity list
		// out over the SINGLE-FILE endpoint, one POST per extracted file, so the
		// batch route never appears and a recorder listening for it observes
		// nothing at all — which reads exactly like "the button does nothing".
		const payloads: string[] = []
		page.on('request', (r) => {
			if (/\/apps\/docudesk\/api\/anonymization\/anonymize\/\d+/.test(r.url())) {
				payloads.push(r.postData() ?? '')
			}
		})
		await page.getByRole('button', { name: /Anonymize 1 entit/ }).click()
		await expect.poll(() => payloads.length,
			{ message: 'the Anonymize action must actually POST the approved entity list', timeout: 60_000 })
			.toBeGreaterThan(0)

		const sent = payloads.join('\n')
		expect(sent, `positive control — the INCLUDED entity must be in the payload. Sent: ${sent.slice(0, 400)}`)
			.toContain(TERM_A)
		expect(sent, `the UNCHECKED entity must NOT be sent. Sent: ${sent.slice(0, 400)}`)
			.not.toContain(TERM_B)
	})

	test('Select All Visible affects only the filtered rows; Deselect All clears them', async ({ page, request }) => {
		// @e2e openspec/specs/anonymization-entity-review/spec.md#select-all-visible-entities
		// @e2e openspec/specs/anonymization-entity-review/spec.md#deselect-all-visible-entities
		await provision(page, request)
		await driveToReview(page)

		// Start from a known-empty selection. The table opens fully checked (see
		// the defect note at the bottom of this file), so selecting from that
		// state could not discriminate a working "Select All Visible" from a
		// no-op.
		await bulk(page, 'Deselect All Visible').click()
		await expect(page.locator('.entity-review .summary-bar')).toContainText('0 of 2 entities selected')

		// With a filter active, "Select All Visible" must leave the HIDDEN row
		// untouched — that clause is the whole point of the word "Visible", and
		// a test that selects with no filter cannot see it.
		await page.getByRole('textbox', { name: 'Search entities' }).fill(NEEDLE)
		await expect(row(page, TERM_A), 'positive control — the visible row must be there').toHaveCount(1)
		await bulk(page, 'Select All Visible').click()
		await expect(page.locator('.entity-review .summary-bar'),
			'only the one visible entity may be selected').toContainText('1 of 2 entities selected')

		await page.getByRole('textbox', { name: 'Search entities' }).fill('')
		await expect(row(page, TERM_B).locator('input[type="checkbox"]').first(),
			'the row hidden behind the filter must NOT have been selected').not.toBeChecked()

		// Deselect All Visible with no filter clears everything. Select both
		// first, so "0 of 2" is a state this click produced and not one it
		// inherited.
		await bulk(page, 'Select All Visible').click()
		await expect(page.locator('.entity-review .summary-bar')).toContainText('2 of 2 entities selected')
		await bulk(page, 'Deselect All Visible').click()
		await expect(page.locator('.entity-review .summary-bar')).toContainText('0 of 2 entities selected')
	})
})

/*
 * DEFECT FOUND WHILE WRITING THESE TESTS — filed as ConductionNL/docudesk#434.
 *
 * The consolidated-entities endpoint pre-sets `included` from the active WOO
 * profile; the spec's own scenario says "entities matching the WOO keep profile
 * have included=false". Measured on one batch, both layers:
 *
 *   GET .../entities            -> included: false   (for both entities)
 *   the review table            -> BOTH checkboxes CHECKED
 *
 * `src/store/modules/folderAnonymization.js:159` maps the response with a
 * hardcoded `included: true`, discarding the backend flag. So an entity the WOO
 * KEEP profile says must not be redacted arrives PRE-SELECTED for redaction,
 * and only an operator noticing and unchecking it prevents that. The API half
 * is correct; the frontend throws its answer away.
 *
 * Not fixed here on purpose: it changes which entities are redacted by default,
 * which is a product decision, and it needs its own spec-anchored test rather
 * than riding along in a coverage PR.
 */

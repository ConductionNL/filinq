/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage — openspec/specs/custom-dictionary-recognition/spec.md
 *
 * ⚠️ WHY THIS FILE WAS REWRITTEN, NOT JUST RE-ANCHORED
 * ----------------------------------------------------
 * Two independent problems, and fixing only the first would have bought a
 * green with a false statement.
 *
 * 1. EVERY ANCHOR WAS INERT. They pointed at
 *    `openspec/changes/custom-dictionary-recognition/specs/.../spec.md#…`.
 *    Gate-19's ref parser only matches `openspec/specs/<spec>/…#<slug>`, so a
 *    `changes/` path constructs no ref at all — not a dangling anchor that gets
 *    reported, just silently invisible. All anchors now point at the canonical
 *    archived spec.
 *
 * 2. THE TESTS COULD NOT FAIL. Every assertion sat behind
 *    `if (await x.isVisible().catch(() => false))` with an `else` branch that
 *    asserted `body` is visible, or with no `else` at all. A missing table, a
 *    missing Add button and a missing row all produced a PASS. Re-anchoring
 *    those would have credited five scenarios to tests that assert nothing —
 *    the same failure mode as the six self-skipping tests documented at length
 *    in orphaned-surface-restoration.spec.ts. The conditionals are gone; the
 *    fixtures each test needs are created up front so the data is a
 *    precondition rather than a hope.
 *
 * TWO SCENARIOS ARE DELIBERATELY NOT CLAIMED HERE — see the notes at the
 * bottom of this file. Their anchors have been removed rather than repointed.
 */

import { test, expect, type APIRequestContext, type Page } from '@playwright/test'
import { appUrl, go, waitForAppReady } from './_helpers'
import { harvestToken, jsonHeaders, API } from '../workflows/_fixtures'

const P = `g19cdr-${Date.now()}`
const SEEDED_LABEL = 'Projectnamen' // shipped by lib/Settings/docudesk_register.json
const FIXTURE_LABEL = `${P}-Straatnamen`

let fixtureId = ''

/**
 * The content region — never the page title / host chrome.
 *
 * A locator rather than a `innerText()` snapshot on purpose: an
 * `expect(locator).toContainText()` retries until the store's fetch has
 * resolved, whereas a one-shot string read races it. That race bit this suite
 * once already (see `expectListedNotListed` in
 * entity-publication-policies.spec.ts) and it is especially treacherous for
 * NEGATIVE assertions, which an unloaded page satisfies for free.
 *
 * @param page A page navigated to an in-app route.
 * @return A locator for the content region.
 */
const content = (page: Page) => page.locator('#content, .app-content, main').first()

async function createDictionary(
	req: APIRequestContext,
	token: string,
	data: Record<string, unknown>,
): Promise<string> {
	const res = await req.post(`${API}/custom-dictionaries`, {
		headers: jsonHeaders(token),
		data,
	})
	expect(res.status(), `create dictionary (${await res.text().catch(() => '')})`).toBe(201)
	return (await res.json()).id
}

test.describe('custom-dictionary-recognition — dictionaries admin UI', () => {
	test.beforeAll(async ({ browser }) => {
		const ctx = await browser.newContext()
		const page = await ctx.newPage()
		const token = await harvestToken(page)

		// The listing scenario says "GIVEN TWO dictionaries". One ships with the
		// register seed; this is the second. Created with an explicit
		// wordBoundary so the match-mode column has something to distinguish it
		// from the seeded caseInsensitive row — a column that renders the same
		// string for every row proves nothing about the column.
		fixtureId = await createDictionary(ctx.request, token, {
			label: FIXTURE_LABEL,
			description: 'gate-19 e2e fixture',
			matchMode: 'wordBoundary',
			active: true,
		})
		await ctx.close()
	})

	test.afterAll(async ({ browser }) => {
		const ctx = await browser.newContext()
		const page = await ctx.newPage()
		const token = await harvestToken(page)
		if (fixtureId) {
			const res = await ctx.request.delete(`${API}/custom-dictionaries/${fixtureId}`, {
				headers: jsonHeaders(token),
			}).catch(() => null)
			if (res && res.status() >= 400) {
				// eslint-disable-next-line no-console
				console.warn(`[teardown] dictionary ${fixtureId} -> ${res.status()} (leaked)`)
			}
		}
		await ctx.close()
	})

	test('both dictionaries are listed with label, term count, match mode and active state', async ({ page }) => {
		// @e2e openspec/specs/custom-dictionary-recognition/spec.md#the-dictionaries-page-lists-dictionaries-with-their-term-counts
		await go(page, 'custom-dictionaries')
		await expect(page).toHaveURL(/\/apps\/docudesk\/custom-dictionaries/)

		const table = page.locator('#content table, .app-content table').first()
		await expect(table, 'the index must render a table, not an empty state').toBeVisible()

		// The seeded dictionary, and the four columns the scenario names. Its
		// term count is 2 in the shipped register seed — asserting the NUMBER
		// (not merely that a cell exists) is what makes this a term-count test.
		const seededRow = table.locator('tr').filter({ hasText: SEEDED_LABEL }).first()
		await expect(seededRow, `the seeded "${SEEDED_LABEL}" dictionary must be listed`).toBeVisible()
		await expect(seededRow, 'term count column').toContainText('2')
		await expect(seededRow, 'match mode column').toContainText('Case-insensitive')
		await expect(seededRow, 'active state column').toContainText(/Active/i)

		// The second dictionary, with a DIFFERENT match mode — so a column that
		// renders a constant cannot satisfy both rows.
		const fixtureRow = table.locator('tr').filter({ hasText: FIXTURE_LABEL }).first()
		await expect(fixtureRow, 'the second dictionary must be listed too').toBeVisible()
		await expect(fixtureRow, 'match mode column must reflect THIS row, not a constant')
			.toContainText('Word boundary')
		await expect(fixtureRow, 'a dictionary with no terms shows 0').toContainText('0')
	})

	test('a manager creates a word-boundary dictionary through the Add dialog and it is listed', async ({ page }) => {
		// @e2e openspec/specs/custom-dictionary-recognition/spec.md#a-permitted-manager-creates-a-dictionary
		const uiLabel = `${P}-ui-Straatnamen`
		await go(page, 'custom-dictionaries')

		// CnActionsBar resolves the CTA label to "Add <schema title>", never the
		// bare "Add", so target the stable testid with a name-prefix fallback.
		const addCta = page.locator('[data-testid="cn-cta-primary"]')
			.or(page.getByRole('button', { name: /^Add\b/ }))
			.first()
		await expect(addCta, 'CustomDictionaryIndex declares :show-add="true"').toBeVisible()
		await addCta.click()

		const dialog = page.getByRole('dialog').first()
		await expect(dialog).toBeVisible()
		await dialog.getByLabel('Label').first().fill(uiLabel)

		// Choosing a match mode, and PROVING the choice landed before submitting.
		//
		// Two earlier attempts failed differently and both were informative:
		// clicking `getByRole('option', …)` timed out (with `{value, label}`
		// options this nc-vue build does not put `role="option"` on the node,
		// unlike the plain-string options in entity-publication-policies.spec.ts),
		// and a looser text-matched click "succeeded" while leaving the select on
		// its `caseInsensitive` default — the dictionary was created, listed, and
		// carried the wrong mode. A create-then-assert test cannot tell those two
		// apart, so the selection is asserted here, separately: a failure now
		// means the FORM did not take the choice, a failure at the listing
		// assertion below means it did not PERSIST.
		const matchMode = dialog.getByLabel('Match mode')
		await matchMode.click()
		await matchMode.fill('Word boundary')
		await page.keyboard.press('Enter')
		await expect(
			dialog.locator('.vs__selected').filter({ hasText: 'Word boundary' }),
			'the form must show the chosen match mode before submit',
		).toBeVisible()
		await dialog.getByRole('button', { name: /^(Create|Save)$/ }).first().click()

		// "THEN it is persisted under organisation A and listed for them" — the
		// listing is the observable half, and it must survive a RELOAD, which is
		// what separates "persisted" from "optimistically added to a local array".
		await expect(page.getByText(uiLabel).first()).toBeVisible()
		await go(page, 'custom-dictionaries')
		const row = page.locator('#content table tr, .app-content table tr').filter({ hasText: uiLabel }).first()
		await expect(row, 'the new dictionary must still be listed after a full reload').toBeVisible()
		await expect(row, 'the chosen match mode must round-trip').toContainText('Word boundary')

		// Clean up through the UI's own data path so a rerun's negative
		// assertions are not poisoned by a leftover row.
		const ctx = page.context()
		const token = await harvestToken(page)
		const list = await ctx.request.get(`${API}/custom-dictionaries`, { headers: jsonHeaders(token) })
		const created = (await list.json()).find(
			(d: { label?: unknown, id?: string }) => JSON.stringify(d.label ?? '').includes(uiLabel),
		)
		if (created?.id) {
			await ctx.request.delete(`${API}/custom-dictionaries/${created.id}`, { headers: jsonHeaders(token) })
		}
	})

	test('a CSV upload on the detail page adds the terms and reports the added count', async ({ page }) => {
		// @e2e openspec/specs/custom-dictionary-recognition/spec.md#import-through-the-admin-page
		// `appUrl`, not a hardcoded `/index.php/...` — the router base differs
		// between a rewriting Apache and CI's `php -S`. See `resolveAppBase`.
		await page.goto(await appUrl(page, `custom-dictionaries/${fixtureId}`), {
			waitUntil: 'domcontentloaded',
		})
		await waitForAppReady(page)
		await expect(page).toHaveURL(new RegExp(`/custom-dictionaries/${fixtureId}`))

		// Precondition, asserted rather than assumed: this dictionary starts
		// empty, so "the new terms appear" cannot be satisfied by pre-existing
		// rows. Ordered positive-then-negative for the reason spelled out in
		// entity-publication-policies.spec.ts — wait for something that proves
		// the page has rendered before asserting anything is absent from it,
		// or an unloaded page satisfies the absence for free.
		await expect(content(page), 'the detail page must have rendered')
			.toContainText(FIXTURE_LABEL)
		await expect(content(page), 'fixture dictionary must start with no terms')
			.not.toContainText(`${P}-alpha`)

		await page.getByRole('button', { name: /Import/i }).first().click()
		const dialog = page.getByRole('dialog').filter({ hasText: 'Import terms' }).first()
		await expect(dialog).toBeVisible()

		// Parsing MUST NOT be delegated to the browser (REQ-DDCDR-005), so this
		// hands the server a real file rather than pasting into the textarea.
		await dialog.locator('#custom-dictionary-import-file').setInputFiles({
			name: 'terms.csv',
			mimeType: 'text/csv',
			// EXACTLY three data rows and no blank line. The third row is a
			// case-differing duplicate of the first, so the expected result
			// isolates ONE behaviour: case-insensitive de-duplication.
			//
			// The first version of this test also put a blank line in the file
			// and expected "1 skipped". The server answered "2 added, 2 skipped,
			// 4 total" — it counts a blank line in BOTH `skipped` and `total`.
			// That is arguably a separate question about what "total terms"
			// means; it is not this scenario's question, so the blank line is
			// gone rather than the assertion loosened.
			buffer: Buffer.from(`${P}-alpha,Alpha label\n${P}-beta,Beta label\n${P}-ALPHA,duplicate\n`),
		})
		await dialog.getByRole('button', { name: /^Import$/ }).click()

		// "THEN the new terms appear in the term table with the reported added
		// count". Both halves, and the exact triple — a bare "some terms were
		// added" would pass even if de-duplication did nothing.
		await expect(dialog.getByText('2 added, 1 skipped, 3 total.')).toBeVisible()

		await dialog.getByRole('button', { name: /Close|Cancel/i }).first().click().catch(() => {})
		await expect(content(page), 'imported term must appear in the term table')
			.toContainText(`${P}-alpha`)
		await expect(content(page), 'the second imported term too')
			.toContainText(`${P}-beta`)
	})
})

/*
 * NOT CLAIMED BY THIS FILE — and why an `@e2e exclude` is right for one and
 * wrong for the other.
 *
 * `match-mode-defaults-to-case-insensitive`
 *   EXCLUDED in the spec. A browser genuinely cannot observe this: BOTH
 *   rendering paths substitute the default themselves. `matchModeLabel()` in
 *   CustomDictionaryIndex.vue:204 and in CustomDictionaryDetail.vue:222 are
 *   identical and both end `|| t('docudesk', 'Case-insensitive')`. So a
 *   dictionary persisted with a NULL match mode renders exactly the same string
 *   as one persisted with `caseInsensitive`, and any browser assertion here
 *   would pass whether or not the backend defaulted anything. The persisted
 *   value is asserted where it is visible — at the service boundary, in
 *   tests/unit/Service/CustomDictionaryServiceTest.php::testCreateDictionaryDefaultsMatchMode,
 *   which asserts `saveObject` received `matchMode === 'caseInsensitive'`.
 *
 * `a-dictionary-hit-is-detected-reviewable-and-redacted`
 *   NOT excluded, and NOT claimed. It is browser-observable in principle — the
 *   review workbench is a real surface — but it needs a document containing the
 *   seeded term to be extracted and detected end to end, and on a CI instance
 *   entity recognition runs in regex-only mode with no anonymiser backend
 *   installed. That is a MISSING TEST, not an unobservable scenario, so it stays
 *   in gate-19's finding list where it is visible. The previous version of this
 *   file claimed it from a file-level anchor while its own closing comment
 *   admitted no such test existed.
 */

/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage — the "Three separate admin surfaces MUST exist"
 * requirement of openspec/specs/entity-publication-policies/spec.md.
 *
 * This spec had ZERO `@e2e` anchors and ZERO `@e2e exclude` markers before this
 * file: all 34 of its scenarios were raw gate-19 findings. Most of the other 30
 * are match-rule semantics, cache behaviour and retroactive resolution — none
 * browser-observable. This requirement is the part that is defined as *what an
 * administrator sees*, so it is the part an e2e test can honestly prove.
 *
 * WHAT MAKES THESE FALSIFIABLE
 * ----------------------------
 * Each scenario is a NEGATIVE as much as a positive: "only records with
 * scope=entity are listed" is worthless without a scope=document record in the
 * store to be excluded. So `beforeAll` seeds one record of EACH kind through
 * the real controllers, and every test asserts both halves — the record that
 * must appear AND the record that must not. Drop the filter at
 * `src/views/consent/ConsentIndex.vue:194` (`workflowConsents`) or at
 * `lib/Service/PolicyCrudService.php:153` and these go red immediately; that
 * is the check.
 *
 * A NOTE ON WHERE THE FILTER LIVES (do not "fix" this by moving the assertion)
 * ---------------------------------------------------------------------------
 * The two surfaces filter in DIFFERENT layers, and it matters for reading a
 * failure:
 *   - Standing consents filter SERVER-side. `GET /api/policy/standing-consents`
 *     returns only `scope: "entity"` (PolicyCrudService::listStandingConsents).
 *   - The consent workflow filters CLIENT-side. `GET /api/consents` returns
 *     BOTH scopes — measured on a seeded instance: 13 entity + 1 document —
 *     and `ConsentIndex.vue` narrows to `scope === 'document'` in a computed.
 * So a regression on the workflow page shows up ONLY in the browser. An API
 * assertion would pass straight through it, which is precisely why this
 * scenario belongs in gate-19 and not in Newman.
 *
 * Fixtures are prefixed and removed in `afterAll` so a crashed run cannot
 * poison the next one's negative assertions.
 */

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { API, harvestToken, jsonHeaders } from '../workflows/_fixtures.ts'
import { go, waitForAppReady } from './_helpers.ts'

/** Unique per run — the negative assertions depend on no stale twin existing. */
const P = `g19epp-${Date.now()}`

const STANDING_NAME = `${P}-standing-acme`
const DOCUMENT_NAME = `${P}-workflow-pietersen`
const PROHIBITION_NAME = `${P}-prohibited-jansen`

const created = { standing: '', document: '', prohibition: '' }

async function seed(req: APIRequestContext, token: string): Promise<void> {
	// scope=entity — belongs on "Publish always" only.
	const standing = await req.post(`${API}/policy/standing-consents`, {
		headers: jsonHeaders(token),
		data: {
			entityText: STANDING_NAME,
			entityType: 'ORGANIZATION',
			consentMethod: 'paper',
			matchRules: [{ type: 'exact', value: STANDING_NAME }],
		},
	})
	expect(
		standing.status(),
		`seed standing consent (${await standing.text().catch(() => '')})`,
	).toBe(201)
	created.standing = (await standing.json()).id

	// scope=document — belongs on the Consent Workflow page only.
	const document = await req.post(`${API}/consents`, {
		headers: jsonHeaders(token),
		data: {
			documentId: `${P}-doc`,
			entityKey: `${P}-key`,
			entityText: DOCUMENT_NAME,
			entityType: 'PERSON',
			scope: 'document',
		},
	})
	expect(
		document.status(),
		`seed document consent (${await document.text().catch(() => '')})`,
	).toBe(201)
	created.document = (await document.json()).id

	// publicationProhibition — a DIFFERENT schema, and belongs on
	// "Publish never" only. `primaryName` and `reason` are schema-required;
	// omitting them answers 500, not 422 (worth knowing when this seed breaks).
	const prohibition = await req.post(`${API}/policy/prohibitions`, {
		headers: jsonHeaders(token),
		data: {
			primaryName: PROHIBITION_NAME,
			reason: 'gate-19 e2e fixture',
			entityType: 'PERSON',
			matchRules: [{ type: 'exact', value: PROHIBITION_NAME }],
			active: true,
		},
	})
	expect(
		prohibition.status(),
		`seed prohibition (${await prohibition.text().catch(() => '')})`,
	).toBe(201)
	created.prohibition = (await prohibition.json()).id
}

/**
 * The content region — never the page title or the stats block.
 *
 * A page heading is host chrome: it renders from the manifest even when the
 * body never does, so asserting on it tests the manifest rather than the
 * render.
 *
 * @param page A page navigated to an index route.
 * @return A locator for the content region.
 */
const content = (page: Page) => page.locator('#content, .app-content, main').first()

/**
 * Assert that a list page shows `present` and does NOT show `absent`.
 *
 * ⚠️ THE ORDER IS THE WHOLE POINT, and it is not stylistic.
 *
 * An absence assertion is satisfied for free by a page that has not finished
 * loading — an empty table contains nothing, so `not.toContainText(absent)`
 * passes instantly and the test proves precisely zero. The first version of
 * this file read `innerText()` once into a string and asserted against it; the
 * snapshot was taken before the consent store's fetch resolved, and the run
 * failed on the POSITIVE half with a content region holding only the page
 * heading and a "Refresh" button. That is the same race that would have made
 * the negative half a silent no-op on any run where it happened to win.
 *
 * So: assert `present` FIRST with a retrying expectation (which is what
 * establishes that the list is populated at all), and only then assert
 * `absent`. A dead selector cannot produce that difference.
 *
 * @param page    A page navigated to an index route.
 * @param present Text that MUST appear (the positive control).
 * @param absent  Text that must NOT appear once the list has rendered.
 * @param why     Message for the absence assertion.
 */
async function expectListedNotListed(
	page: Page,
	present: string,
	absent: string,
	why: string,
): Promise<void> {
	await expect(
		content(page),
		`positive control — ${present} must be listed`,
	).toContainText(present)
	await expect(content(page), why).not.toContainText(absent)
}

test.describe('entity-publication-policies — three separate admin surfaces', () => {
	test.beforeAll(async ({ browser }) => {
		const ctx = await browser.newContext()
		const page = await ctx.newPage()
		const token = await harvestToken(page)
		await seed(ctx.request, token)
		await ctx.close()
	})

	/*
	 * TEARDOWN — and why it only removes ONE of the three fixtures.
	 *
	 * Measured, not assumed (first version of this file tried all three):
	 *   DELETE api/policy/standing-consents/{id}  -> 200. Route exists.
	 *   DELETE api/consents/{id}                  -> 405. There IS no delete
	 *       route: appinfo/routes.php declares consent# index/create/show/
	 *       update/byDocument and nothing else. A workflow consent record is
	 *       not deletable over HTTP by design.
	 *   DELETE api/policy/prohibitions/{id}       -> 409. PolicyController::
	 *       deleteProhibition maps ArchivalImmutableException to 409 —
	 *       prohibitions are retention-protected.
	 *
	 * So two of the three fixtures are PERMANENT, and that is correct app
	 * behaviour rather than a teardown bug. It is safe here only because every
	 * fixture name is stamped with `Date.now()`: the negative assertions
	 * ("must NOT contain X") name THIS run's strings, so yesterday's leftovers
	 * cannot satisfy or break them. Do not "fix" this by reusing a fixed name.
	 */
	test.afterAll(async ({ browser }) => {
		if (!created.standing) return
		const ctx = await browser.newContext()
		const page = await ctx.newPage()
		const token = await harvestToken(page)
		const res = await ctx.request
			.delete(`${API}/policy/standing-consents/${created.standing}`, {
				headers: jsonHeaders(token),
			})
			.catch(() => null)
		if (res && res.status() >= 400) {
			console.warn(
				`[teardown] standing consent ${created.standing} -> ${res.status()} (leaked)`,
			)
		}
		await ctx.close()
	})

	test('"Publish always" lists the scope=entity record and NOT the scope=document one', async ({
		page,
	}) => {
		// @e2e openspec/specs/entity-publication-policies/spec.md#standing-publication-consents-page-filters-by-scope
		await go(page, 'policy/standing-consents')
		await expect(page).toHaveURL(/\/apps\/filinq\/policy\/standing-consents/)
		await expect(
			page.getByRole('heading', { name: 'Publish always' }),
		).toBeVisible()

		await expectListedNotListed(
			page,
			STANDING_NAME,
			DOCUMENT_NAME,
			'a scope=document workflow record must NOT appear here',
		)
	})

	test('the Consent Workflow page lists the scope=document record and NOT the scope=entity one', async ({
		page,
	}) => {
		// @e2e openspec/specs/entity-publication-policies/spec.md#consent-workflow-page-filters-by-scope
		//
		// This is the surface whose filter is CLIENT-side: `GET /api/consents`
		// hands the page both scopes and `ConsentIndex.vue`'s `workflowConsents`
		// computed narrows them. An API-level test of this scenario would pass
		// over a broken page, so it has to be a browser assertion.
		await go(page, 'consent')
		await expect(page).toHaveURL(/\/apps\/filinq\/consent/)

		await expectListedNotListed(
			page,
			DOCUMENT_NAME,
			STANDING_NAME,
			'a scope=entity standing consent must NOT leak onto the workflow page',
		)
	})

	test('a prohibition appears on "Publish never" and on neither consent surface', async ({
		page,
	}) => {
		// @e2e openspec/specs/entity-publication-policies/spec.md#publication-prohibitions-page-is-the-only-surface-for-prohibition-records
		await go(page, 'policy/prohibitions')
		await expect(page).toHaveURL(/\/apps\/filinq\/policy\/prohibitions/)
		await expect(
			page.getByRole('heading', { name: 'Publish never' }),
		).toBeVisible()
		await expect(
			content(page),
			'the seeded prohibition must be listed on its own page',
		).toContainText(PROHIBITION_NAME)

		// "AND they do not appear on either of the consent pages" — the whole
		// point of the scenario, and the half a single-page test would miss.
		// Each absence is paired with the positive control for THAT page, so an
		// unloaded list cannot satisfy it for free (see expectListedNotListed).
		await go(page, 'policy/standing-consents')
		await expectListedNotListed(
			page,
			STANDING_NAME,
			PROHIBITION_NAME,
			'a prohibition must not appear on "Publish always"',
		)

		await go(page, 'consent')
		await expectListedNotListed(
			page,
			DOCUMENT_NAME,
			PROHIBITION_NAME,
			'a prohibition must not appear on the Consent Workflow page',
		)
	})

	test('the standing-consent create form blocks submit until consentMethod is set, and warns on a blank validUntil', async ({
		page,
	}) => {
		// @e2e openspec/specs/entity-publication-policies/spec.md#standing-consent-create-form-requires-consentmethod
		await go(page, 'policy/standing-consents')

		// CnActionsBar renders the primary CTA with a resolved label of
		// "Add <schema title>" — never the bare string "Add" — so target the
		// stable testid, with a name-prefix fallback for older shells.
		const addCta = page
			.locator('[data-testid="cn-cta-primary"]')
			.or(page.getByRole('button', { name: /^Add\b/ }))
			.first()
		await expect(
			addCta,
			'StandingConsentIndex declares :show-add="true"',
		).toBeVisible()
		await addCta.click()

		const dialog = page
			.getByRole('dialog')
			.filter({ hasText: 'Add standing consent' })
			.first()
		await expect(dialog).toBeVisible()

		const submit = dialog
			.getByRole('button', { name: /^(Create|Save)$/ })
			.first()

		// Satisfy EVERY other precondition of `canSubmit` (entityText + one
		// complete match rule) so the only thing still missing is
		// consentMethod. Without this the assertion below would pass for the
		// wrong reason — the button is disabled on a blank form regardless.
		await dialog.getByLabel('Entity text (display name)').fill(`${P}-form-probe`)
		await dialog.getByRole('button', { name: 'Add match rule' }).click()
		await dialog.getByLabel('Match value').first().fill(`${P}-form-probe`)

		await expect(
			submit,
			'consentMethod is still unset, so submission must remain blocked',
		).toBeDisabled()

		// "AND when validUntil is left blank, the form surfaces a non-blocking
		// warning recommending an explicit term" — non-blocking, so it must be
		// present while the form is still unsubmittable.
		await expect(
			dialog.locator('.form-warning').filter({ hasText: 'No expiry set' }),
			'blank validUntil must surface the non-blocking expiry warning',
		).toBeVisible()

		// The positive control for the assertion above: setting consentMethod —
		// and nothing else — must unblock submission. If it does not, the
		// "blocked" assertion was passing for some other missing field.
		await dialog.getByLabel('Consent method').click()
		await page.getByRole('option', { name: 'paper' }).first().click()
		await expect(
			submit,
			'with consentMethod set and every other requirement satisfied, submit must unblock',
		).toBeEnabled()

		// Leave no record behind: this form is never submitted.
		await dialog.getByRole('button', { name: 'Cancel' }).click()
		await waitForAppReady(page)
	})
})

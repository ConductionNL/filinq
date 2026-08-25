/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Coverage for the `entity-publication-policies` policy-match derivation —
 * the data contract the publication-prep anonymisation toggle is built on.
 *
 * WHY THIS IS A SEPARATE FILE FROM `entity-review.spec.ts`
 * -------------------------------------------------------
 * The work item asked for these tests to be appended to `entity-review.spec.ts`
 * and driven through its `driveToReview()` helper. That helper opens the
 * FOLDER-ANALYSIS review table (`/folder-anonymization`,
 * `src/views/anonymization/EntityReviewTable.vue`), and the toggle that table
 * carries is the anonymisation-gate "Skip" switch — gated on
 * `prohibitionMatch.highConfidence`, a different mechanism with a different
 * default (OFF) and no prohibition-list wording.
 *
 * The toggle the `entity-publication-policies` scenarios describe is the
 * "Anonymise this entity in the published document" switch in
 * `src/views/consent/ConsentDetail.vue`, driven by the `policyMatch`
 * REFERENT TYPE, reached from the Consent Management list — a different page,
 * a different store and a different fixture. Putting it in the entity-review
 * file would have filed it under a spec it does not belong to.
 *
 * ⚠️ THE TWO TOGGLE SCENARIOS ARE UNREACHABLE AND ARE DELIBERATELY UNTAGGED
 * -------------------------------------------------------------------------
 * Neither `#toggle-is-locked-when-policymatch-references-a-prohibition` nor
 * `#toggle-is-overridable-when-policymatch-references-a-standing-consent` is
 * anchored anywhere in this suite, because the toggle they describe cannot
 * render. Measured on a freshly `ci-seed.sh`-provisioned instance, 2026-08-25.
 * Two independent blockers, either of which alone is sufficient:
 *
 *   1. `ConsentDetail.vue` puts the whole anonymisation section — toggle,
 *      locked note, standing-consent note — in `CnDetailPage`'s DEFAULT slot,
 *      while also supplying a `#stats-rows` slot. `CnDetailPage` renders those
 *      two branches as `v-if` / `v-else`:
 *
 *          <div v-if="hasStats" class="cn-detail-page__stats"> … </div>
 *          <div v-else class="cn-detail-page__content"><slot /></div>
 *
 *          hasStats() { return this.statsColumns.length > 0
 *              && (this.statsRows.length > 0 || !!this.$slots['stats-rows']) }
 *
 *      `statsColumns` and `#stats-rows` are both present whenever
 *      `consentItem` is set — and `consentItem` must be set for the toggle to
 *      exist at all. So the stats table always wins and the default slot NEVER
 *      renders. The rendered page stops after the Entity Information table:
 *      no toggle, no consent-status form, no Save Changes button.
 *
 *   2. `/consent/:id` does not deep-link. The manifest route declares the
 *      param as `:id`, `ConsentDetail` declares the prop as `consentId`, and
 *      its `created()` hook fetches only `if (this.consentId)`. The names
 *      never meet, so a direct navigation renders the error state
 *      "No consent record selected." The only hydrating path is a row click
 *      on `/consent` (`ConsentIndex::viewConsent` → `setConsentItem`).
 *
 * A test tagged against either scenario would be green over a UI that does
 * not exist — the `.github#345` defect. So the UI halves are left UNCOVERED.
 *
 * WHAT IS COVERED HERE INSTEAD
 * ----------------------------
 * The DATA contract both toggle scenarios sit on: that a `scope: "document"`
 * `publicationConsent` really does end up carrying a `policyMatch` pointing at
 * a prohibition (retroactively) or at a `scope: "entity"` standing consent (at
 * detection time). That half works end-to-end and is measured below, so the
 * report on the toggle can say "the blocker is the render path, not the data"
 * as an executable fact rather than a claim.
 *
 * ⚠️ NOTE ON THE ORIGINAL REACHABILITY WARNING. The work item flagged the
 * standing-consent scenario as possibly unreachable because
 * `prohibition-override-audit-schema.spec.ts` records that the regex-only
 * backend emits `CUSTOM_DICTIONARY` at confidence 1.00 and the 0.85 override
 * threshold is not in `SettingsService::WRITABLE_KEYS`. That constraint is
 * real but belongs to a DIFFERENT mechanism — the anonymisation prohibition
 * GATE (`ProhibitionOverrideCommitter`,
 * `filinq.prohibition.high_confidence_threshold`). `PolicyMatchService::match()`
 * is rule-based (`exact` / `normalized` / `bsn` / `kvk`) and never reads a
 * detection confidence, so the policy-match path below is fully reachable and
 * is exercised for real here.
 *
 * Cleanup: `publicationConsent` exposes no DELETE (measured: 405) and
 * prohibitions are retention-protected (409), so every fixture is stamped with
 * `TEST_PREFIX` and every assertion names only this run's strings.
 */

import { test, expect, type APIRequestContext } from '@playwright/test'
import { harvestToken, jsonHeaders, API, TEST_PREFIX } from './_fixtures'

test.describe.configure({ mode: 'serial' })

/**
 * Read back the `scope: "document"` consent record for one document id.
 *
 * ⚠️ Keyed on `documentId`, NOT on `entityText`, and that is load-bearing.
 * `GET /api/consents` returns EVERY publicationConsent object — the
 * `scope: "entity"` standing consents included — and a standing consent
 * carries the SAME `entityText` as the per-document record it matched. A
 * lookup by entity text therefore returns whichever of the two the store
 * happens to list first, and the standing consent legitimately has
 * `policyMatch: null` (a scope=entity row must not carry one — see
 * `ConsentScopeValidator::assertEntityHasNoPolicyMatch()`). That read the
 * first time round as "the reference was never persisted".
 *
 * `scope` is asserted too, so a future change that starts stamping
 * `documentId` on standing consents fails here rather than silently
 * reintroducing the same ambiguity.
 *
 * The list endpoint answers with a bare array (not `{results}`), and the
 * Consent Management table pages it CLIENT-side, so reading the API directly
 * is the only way to see a record that is not on the table's first page.
 *
 * @param req   The request context.
 * @param token The CSRF request-token.
 * @param doc   The exact `documentId` to look for.
 * @return The matching scope=document record, or undefined.
 */
async function consentFor(
	req: APIRequestContext,
	token: string,
	doc: string,
): Promise<Record<string, unknown> | undefined> {
	const res = await req.get(`${API}/consents`, { headers: jsonHeaders(token) })
	expect(res.status(), `GET ${API}/consents must answer 200`).toBe(200)
	const body = await res.json()
	const rows = (Array.isArray(body) ? body : (body.results ?? [])) as Array<
		Record<string, unknown>
	>
	const matches = rows.filter(
		(r) => r.documentId === doc && r.scope === 'document',
	)
	expect(
		matches.length,
		`exactly one scope=document record must carry documentId "${doc}" `
			+ `(found ${matches.length})`,
	).toBeLessThanOrEqual(1)
	return matches[0]
}

/**
 * Create a `scope: "document"` publicationConsent for one entity.
 *
 * @param req   The request context.
 * @param token The CSRF request-token.
 * @param text  The entity text.
 * @param doc   The document id to attach it to.
 * @return The HTTP status and parsed body.
 */
async function createDocumentConsent(
	req: APIRequestContext,
	token: string,
	text: string,
	doc: string,
) {
	const res = await req.post(`${API}/consents`, {
		headers: jsonHeaders(token),
		data: { documentId: doc, entityType: 'PERSON', entityText: text },
	})
	return { status: res.status(), body: await res.json().catch(() => ({})) }
}

// ---------------------------------------------------------------------------
// Standing-consent match at detection time
// ---------------------------------------------------------------------------

// @e2e openspec/specs/entity-publication-policies/spec.md#standing-consent-match-short-circuits-when-no-prohibition-match
//
// Every clause of the scenario has an assertion below:
//
//   GIVEN "an entity matching an active scope:'entity' publicationConsent with
//          UUID R-STANDING-1 and matching no publicationProhibition rule"
//       → the standing consent is created here and its uuid is captured; the
//         entity text is `TEST_PREFIX`-stamped so no prohibition seeded by any
//         other spec in this suite can match it. The "no prohibition" half is
//         asserted positively rather than assumed: a prohibition match would
//         make POST /api/consents answer 403 (PolicyRejectedException), so the
//         201 below IS the proof that the prohibition pass found nothing.
//
//   THEN scope:'document', consentStatus:'consent_given',
//        notificationStatus:'skipped', publicationDecision:'publish_with_consent',
//        policyMatch referencing R-STANDING-1
//       → asserted field by field, and re-asserted against a fresh list read so
//         the claim is about what was PERSISTED, not about the create echo.
//
//   AND "no notification is sent"
//       → `notificationSentAt` must be empty. `notificationStatus: 'skipped'`
//         is asserted separately above; on its own it is the workflow's own
//         label for its intent, not evidence that nothing went out.
test('A standing consent short-circuits detection: the per-document record is consent_given and points at the standing consent', async ({
	page,
}) => {
	const token = await harvestToken(page)
	const req = page.request

	const entity = `${TEST_PREFIX} Standing Subject`
	const documentId = `${TEST_PREFIX}-doc-standing`

	const standing = await req.post(`${API}/policy/standing-consents`, {
		headers: jsonHeaders(token),
		data: {
			entityText: entity,
			entityType: 'PERSON',
			consentMethod: 'opt_in_form',
			consentScope: `${TEST_PREFIX} publication-policy fixture`,
			matchRules: [{ type: 'exact', value: entity }],
			active: true,
			consentStatus: 'consent_given',
			publicationDecision: 'publish_with_consent',
			notificationStatus: 'skipped',
		},
	})
	expect(
		standing.status(),
		`POST ${API}/policy/standing-consents must answer 201 (body: ${await standing
			.text()
			.catch(() => '')})`,
	).toBe(201)
	const standingId = (await standing.json()).id as string
	expect(standingId, 'the standing consent must carry a persisted uuid').toBeTruthy()

	const created = await createDocumentConsent(req, token, entity, documentId)
	// A prohibition match would have thrown PolicyRejectedException → 403. The
	// 201 is therefore the positive control for "matching no prohibition rule".
	expect(
		created.status,
		'POST /api/consents must answer 201. A 403 here would mean a prohibition '
			+ 'matched first, which is a DIFFERENT scenario (#prohibition-match-short-circuits) '
			+ `and would invalidate every assertion below. Body: ${JSON.stringify(created.body).slice(0, 300)}`,
	).toBe(201)

	expect(created.body.scope, 'the created record is scope=document').toBe(
		'document',
	)
	expect(
		created.body.policyMatch,
		'policyMatch must reference the standing consent that matched',
	).toBe(standingId)
	expect(
		created.body.consentStatus,
		'a standing-consent match resolves the record to consent_given',
	).toBe('consent_given')
	expect(
		created.body.notificationStatus,
		'the WOO notification workflow must be skipped, not started',
	).toBe('skipped')
	expect(
		created.body.publicationDecision,
		'the decision defaults to publish_with_consent under a standing consent',
	).toBe('publish_with_consent')
	expect(
		created.body.notificationSentAt ?? null,
		'"no notification is sent" — nothing may have been stamped as sent',
	).toBeFalsy()

	// Persistence, not the create echo.
	const persisted = await consentFor(req, token, documentId)
	expect(
		persisted,
		`the seeded scope=document record for "${documentId}" must be readable back from the list`,
	).toBeDefined()
	expect(
		persisted!.policyMatch,
		'the policyMatch reference must have PERSISTED, not just been echoed',
	).toBe(standingId)
	expect(persisted!.consentStatus).toBe('consent_given')
})

// ---------------------------------------------------------------------------
// Retroactive prohibition resolution
// ---------------------------------------------------------------------------

// DELIBERATELY CARRIES NO `@e2e` ANCHOR.
//
// The nearest scenario is
// `#new-prohibition-retroactively-resolves-matching-in-flight-records`, and
// this test does assert three of its four THEN clauses (consentStatus,
// notificationStatus, publicationDecision, policyMatch). It does NOT assert
// the remaining two:
//
//   "AND any pending notification for that record is canceled"
//   "AND the audit log records the retroactive update with timestamp and
//    triggering rule UUID"
//
// Filinq exposes no read surface for either from an e2e context — there is no
// notification-queue endpoint and no retroactive-audit endpoint in
// `appinfo/routes.php`. Anchoring here would credit those two clauses to a
// test that cannot evaluate them, which is exactly the false-green this suite
// is trying not to add to. The scenario stays uncovered until a read surface
// exists; this test still earns its place as the executable proof that the
// DATA precondition for the locked-toggle UI is satisfied — i.e. that the
// toggle's blocker is the render path, not the policy engine.
test('Creating a prohibition retroactively resolves an in-flight consent record and points it at the new rule', async ({
	page,
}) => {
	const token = await harvestToken(page)
	const req = page.request

	const entity = `${TEST_PREFIX} Prohibited Subject`
	const documentId = `${TEST_PREFIX}-doc-prohibited`

	// In-flight FIRST, prohibition second: that ordering is the whole point.
	// Creating the prohibition first would make POST /api/consents answer 403
	// and there would be no in-flight record to resolve.
	const created = await createDocumentConsent(req, token, entity, documentId)
	expect(
		created.status,
		`POST ${API}/consents must answer 201 while no rule matches (body: ${JSON.stringify(created.body).slice(0, 300)})`,
	).toBe(201)
	expect(
		created.body.consentStatus,
		'the record must start in-flight (pending) for the retroactive pass to have anything to do',
	).toBe('pending')
	expect(
		created.body.policyMatch ?? null,
		'no rule matches yet, so policyMatch must start null',
	).toBeNull()

	const rule = await req.post(`${API}/policy/prohibitions`, {
		headers: jsonHeaders(token),
		data: {
			primaryName: entity,
			entityType: 'PERSON',
			matchRules: [{ type: 'exact', value: entity }],
			reason: `${TEST_PREFIX} publication-policy fixture`,
			active: true,
		},
	})
	expect(
		rule.status(),
		`POST ${API}/policy/prohibitions must answer 201 (body: ${await rule
			.text()
			.catch(() => '')})`,
	).toBe(201)
	const ruleId = (await rule.json()).id as string
	expect(ruleId, 'the prohibition must carry a persisted uuid').toBeTruthy()

	const resolved = await consentFor(req, token, documentId)
	expect(
		resolved,
		`the in-flight record for "${documentId}" must still be readable after the retroactive pass`,
	).toBeDefined()

	expect(
		resolved!.policyMatch,
		'the in-flight record must now reference the prohibition that was just created. '
			+ 'A null here means the retroactive listener did not fire — the record would '
			+ 'stay published-eligible while an active prohibition names its entity.',
	).toBe(ruleId)
	expect(
		resolved!.consentStatus,
		'a retroactively prohibited record is force-resolved to anonymized',
	).toBe('anonymized')
	expect(
		resolved!.notificationStatus,
		'the WOO notification workflow must be marked skipped once the rule pre-empts it',
	).toBe('skipped')

	// `publicationDecision` is declared `"translatable": true` in
	// lib/Settings/filinq_register.json, so OpenRegister's i18n layer reads it
	// back as `{ "<lang>": "<value>" }` rather than as the bare string the
	// service wrote. Assert the VALUE through either shape: pinning the string
	// alone would fail on a translated instance, and pinning the object alone
	// would fail on an untranslated one — and neither failure would be about
	// the policy engine this test is measuring.
	const decision = resolved!.publicationDecision
	const decisionValues =
		decision !== null && typeof decision === 'object'
			? Object.values(decision as Record<string, unknown>)
			: [decision]
	expect(
		decisionValues,
		`the retroactive pass must set publicationDecision to "anonymize" (read back as ${JSON.stringify(decision)})`,
	).toContain('anonymize')
})

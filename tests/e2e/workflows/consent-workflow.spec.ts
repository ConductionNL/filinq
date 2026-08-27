/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The publication-consent workflow journey — policy resolution, idempotency,
 * the pre-emption lock, operator-driven status advancement, and retroactive
 * rule application.
 *
 * Every test here seeds REAL records through the documented controllers and
 * then asserts the resulting state through a follow-up read (or through the
 * ConsentDetail UI), so a regression in `ConsentService`,
 * `ConsentUpdateHandler`, `PolicyMatchService` or `PolicyRetroactiveService`
 * turns one of these red rather than sliding past a page-renders check.
 *
 * ⚠️ THIS SUITE LEAKS RECORDS PERMANENTLY, BY DESIGN OF THE APP.
 * `DELETE api/policy/prohibitions/{id}` answers 409 (archival-immutable) and
 * there is no `DELETE api/consents/{id}` route at all (405). Everything is
 * therefore stamped with `POLICY_PREFIX` (embeds `Date.now()`) so leaked rows
 * are identifiable and — far more important — so a leaked exact-match
 * PROHIBITION can never pre-empt a later run's consent records. See the long
 * note above `POLICY_PREFIX` in ./_fixtures.ts before adding a fixture.
 *
 * ------------------------------------------------------------------
 * TWO SCENARIOS ARE DELIBERATELY LEFT UNTAGGED — see the `test.describe`
 * blocks at the bottom. Both are cases where the spec describes behaviour the
 * shipped code does not have, and tagging them would make gate-19 report
 * coverage for UI/API that does not exist. That is the exact failure this
 * suite's sibling (`#view-consent-statistics`) was written to undo.
 * ------------------------------------------------------------------
 */

import { test, expect, type APIRequestContext } from '@playwright/test'
import { go, waitForAppReady } from '../spec-coverage/_helpers'
import {
	API,
	POLICY_PREFIX,
	createProhibition,
	createStandingConsent,
	getConsent,
	harvestToken,
	jsonHeaders,
	seedPolicyMatchedConsent,
} from './_fixtures'

/** Harvested once in `beforeAll`; every request helper needs it. */
let token = ''
/** A request context carrying the admin session. */
let req: APIRequestContext

test.beforeAll(async ({ browser }) => {
	const ctx = await browser.newContext()
	const page = await ctx.newPage()
	token = await harvestToken(page)
	req = ctx.request
})

// ---------------------------------------------------------------------------
// Policy resolution at detection time
// ---------------------------------------------------------------------------

test.describe('consent workflow — policy resolution', () => {
	test('a standing-consent match resolves the new record to consent_given', async () => {
		// @e2e consent-management::standing-consent-match-resolves-to-existing-consentgiven-status
		const f = await seedPolicyMatchedConsent(
			req,
			token,
			'standing_consent',
			'sc-resolve',
		)

		expect(f.consentStatusCode, 'the consent POST must succeed').toBe(201)

		// Every THEN/AND of the scenario that is observable on the record.
		expect(f.consent.consentStatus).toBe('consent_given')
		expect(f.consent.notificationStatus).toBe('skipped')
		expect(f.consent.publicationDecision).toBe('publish_with_consent')
		expect(
			f.consent.policyMatch,
			'policyMatch must reference the matching standing consent',
		).toBe(f.ruleId)

		// AND notificationSentAt / objectionDeadline are null — asserted on a
		// re-read rather than on the create response, because the create
		// response is the service's own return value while the re-read is what
		// actually landed in OpenRegister.
		const stored = await getConsent(req, token, f.consentId)
		expect(stored.notificationSentAt).toBeNull()
		expect(stored.objectionDeadline).toBeNull()
		expect(stored.consentStatus).toBe('consent_given')
	})

	test('detection creates exactly one record per (document, entity)', async () => {
		// @e2e consent-management::detection-creates-exactly-one-publicationconsent-record-per-document-entity
		const f = await seedPolicyMatchedConsent(
			req,
			token,
			'standing_consent',
			'idempotent',
		)
		expect(f.consentStatusCode).toBe(201)

		// Re-submit the SAME (documentId, entityKey). The idempotency contract
		// says this updates the existing record instead of creating a second
		// one — the status code drops to 200 and `wasUpdated` flips.
		const second = await req.post(`${API}/consents`, {
			headers: jsonHeaders(token),
			data: {
				documentId: f.documentId,
				entityKey: f.entityKey,
				entityText: f.entityText,
				entityType: 'PERSON',
				scope: 'document',
			},
		})
		expect(second.status(), 're-POST must update, not create').toBe(200)
		const body = await second.json()
		expect(body.id, 'the same record must be returned').toBe(f.consentId)
		expect(body.wasUpdated).toBe(true)

		// THEN exactly one scope=document record exists for this pair. This is
		// the assertion that actually falsifies a duplicate-creation
		// regression — the status code alone would not.
		const listed = await req.get(`${API}/consents/document/${f.documentId}`, {
			headers: jsonHeaders(token),
		})
		expect(listed.status()).toBe(200)
		const rows = (await listed.json()) as Array<{
			id: string
			policyMatch?: string
		}>
		expect(rows).toHaveLength(1)
		expect(rows[0].id).toBe(f.consentId)

		// AND the record reflects the highest-priority policy match.
		expect(rows[0].policyMatch).toBe(f.ruleId)
	})
})

// ---------------------------------------------------------------------------
// The pre-emption lock
// ---------------------------------------------------------------------------

test.describe('consent workflow — pre-emption lock', () => {
	test('workflow transitions are rejected on a policy-pre-empted record', async () => {
		// @e2e consent-management::workflow-transitions-are-rejected-on-policy-pre-empted-records
		const f = await seedPolicyMatchedConsent(
			req,
			token,
			'standing_consent',
			'locked',
		)
		expect(f.consentStatusCode).toBe(201)

		for (const target of ['objection_received', 'no_response', 'pending']) {
			const res = await req.put(`${API}/consents/${f.consentId}`, {
				headers: jsonHeaders(token),
				data: { consentStatus: target },
			})
			expect(
				res.ok(),
				`transition to "${target}" must be rejected on a pre-empted record`,
			).toBe(false)
		}

		// The load-bearing half: the record did not move. A rejection that
		// still mutated the record would satisfy the status-code check above
		// and be a far worse bug, so the re-read is what makes this test mean
		// what its name says.
		const stored = await getConsent(req, token, f.consentId)
		expect(stored.consentStatus).toBe('consent_given')
		expect(stored.policyMatch).toBe(f.ruleId)

		// NOT ASSERTED — "the rejection error cites the policy-pre-empted state
		// and references the matching rule UUID". The service DOES build that
		// message (ConsentUpdateHandler::guardPolicyPreemptedTransition
		// formats `... rejected on policy-pre-empted record (policyMatch=<uuid>,
		// current=<status>)`), but the controller swallows it: the HTTP
		// response is 500 with a flat `{"error":"Failed to update consent"}`
		// and no rule identity. The citation is observable only in
		// nextcloud.log. Reported as a defect; asserting the flat body here
		// would pin the swallow in place.
	})
})

// ---------------------------------------------------------------------------
// Operator-driven advancement (ConsentDetail UI)
// ---------------------------------------------------------------------------

test.describe('consent workflow — operator advancement', () => {
	test('operator advances notification status manually via ConsentDetail', async ({
		page,
	}) => {
		// @e2e consent-management::operator-advances-notification-status-manually
		const f = await seedPolicyMatchedConsent(req, token, 'none', 'advance')
		expect(f.consentStatusCode).toBe(201)
		// Precondition: an unmatched entity follows the WOO flow.
		expect(f.consent.notificationStatus).toBe('pending')

		await go(page, `consent/${f.consentId}`)
		await waitForAppReady(page)

		// The scenario is about the operator advancing the status by hand, so
		// this drives the real ConsentDetail controls rather than PUTting
		// behind the UI's back: the NcSelect labelled "Notification Status",
		// then "Save Changes".
		const select = page
			.locator('.select', { hasText: 'Notification Status' })
			.first()
		await expect(
			select,
			'the Notification Status dropdown must render',
		).toBeVisible()
		await select.click()
		await page.getByRole('option', { name: 'Sent', exact: true }).click()
		await page.getByRole('button', { name: 'Save Changes' }).click()

		// THEN the record is updated. Asserted on a server re-read, not on the
		// dropdown's own value — the dropdown shows local component state and
		// would still read "Sent" if the save had failed outright.
		await expect
			.poll(
				async () =>
					(await getConsent(req, token, f.consentId)).notificationStatus,
				{ message: 'notificationStatus must persist as "sent"' },
			)
			.toBe('sent')

		// NOT ASSERTED — the scenario's WHEN also carries
		// `notificationSentAt: "<ISO timestamp>"`. That field is silently
		// dropped on update: `ConsentUpdateHandler`'s `$mutableFields`
		// whitelist is exactly [notificationStatus, consentStatus,
		// publicationDecision, objectionReason, objectionDeadline], and
		// `notificationSentAt` / `objectionReceivedAt` are stripped by
		// `array_intersect_key` with no error. So an operator can record THAT
		// a notification went out but never WHEN. Reported as a defect;
		// asserting `notificationSentAt === null` here would pin it.
	})
})

// ---------------------------------------------------------------------------
// Retroactive rule application (entity-publication-policies)
// ---------------------------------------------------------------------------

test.describe('consent workflow — retroactive policy application', () => {
	test('a new prohibition retroactively resolves in-flight records', async () => {
		// @e2e entity-publication-policies::new-prohibition-retroactively-resolves-matching-in-flight-records
		const f = await seedPolicyMatchedConsent(req, token, 'none', 'retro-pending')
		expect(f.consentStatusCode).toBe(201)

		// GIVEN an in-flight record with consentStatus "pending" and no
		// prohibition currently matching.
		expect(f.consent.consentStatus).toBe('pending')
		expect(f.consent.policyMatch ?? null).toBeNull()
		expect(
			f.consent.objectionDeadline,
			'an unmatched record must carry a computed objection deadline',
		).toBeTruthy()

		// WHEN an admin creates a matching prohibition.
		const ruleId = await createProhibition(req, token, f.entityText)

		// THEN the existing record is force-resolved.
		await expect
			.poll(
				async () =>
					(await getConsent(req, token, f.consentId)).consentStatus,
				{ message: 'the in-flight record must be force-resolved' },
			)
			.toBe('anonymized')

		const stored = await getConsent(req, token, f.consentId)
		expect(stored.notificationStatus).toBe('skipped')
		expect(stored.publicationDecision).toBe('anonymize')
		expect(stored.policyMatch, 'policyMatch must reference the NEW rule').toBe(
			ruleId,
		)
	})

	test('a new prohibition retroactively resolves a record in objection_received', async () => {
		// @e2e entity-publication-policies::new-prohibition-retroactively-resolves-a-record-in-objectionreceived-state
		const f = await seedPolicyMatchedConsent(req, token, 'none', 'retro-obj')
		expect(f.consentStatusCode).toBe(201)

		// GIVEN the record is in objection_received. `objectionReason` is set
		// alongside it and used below as the audit-preservation probe.
		const put = await req.put(`${API}/consents/${f.consentId}`, {
			headers: jsonHeaders(token),
			data: {
				consentStatus: 'objection_received',
				objectionReason: `${POLICY_PREFIX}-objection-note`,
			},
		})
		expect(put.status(), 'moving an UNMATCHED record is allowed').toBe(200)
		expect((await getConsent(req, token, f.consentId)).consentStatus).toBe(
			'objection_received',
		)

		// WHEN a matching prohibition is created.
		const ruleId = await createProhibition(req, token, f.entityText)

		// THEN it transitions to anonymized with policyMatch populated.
		await expect
			.poll(
				async () =>
					(await getConsent(req, token, f.consentId)).consentStatus,
				{ message: 'an objected record must still be force-resolved' },
			)
			.toBe('anonymized')

		const stored = await getConsent(req, token, f.consentId)
		expect(stored.policyMatch).toBe(ruleId)
		// AND objectionDeadline is cleared.
		expect(stored.objectionDeadline).toBeNull()
		// AND the objection audit trail survives the retroactive rewrite.
		expect(
			stored.objectionReason,
			'the objection detail must be preserved for audit',
		).toBe(`${POLICY_PREFIX}-objection-note`)

		// NOT ASSERTED — the scenario also names `notificationSentAt` and
		// `objectionReceivedAt` among the timestamps "preserved for audit".
		// Neither can be set through the API at all: both are in
		// `SERVER_CONTROLLED_CREATE_FIELDS` on create and absent from
		// `$mutableFields` on update, so they are null on every
		// API-constructed record and "preserved" would be vacuously true.
	})

	test('a new standing consent does not override an existing objection', async () => {
		// @e2e entity-publication-policies::new-standing-consent-does-not-override-existing-objection
		const f = await seedPolicyMatchedConsent(req, token, 'none', 'no-override')
		expect(f.consentStatusCode).toBe(201)

		const put = await req.put(`${API}/consents/${f.consentId}`, {
			headers: jsonHeaders(token),
			data: { consentStatus: 'objection_received' },
		})
		expect(put.status()).toBe(200)
		const before = await getConsent(req, token, f.consentId)
		expect(before.consentStatus).toBe('objection_received')

		// WHEN an admin creates a matching scope=entity standing consent.
		await createStandingConsent(req, token, f.entityText)

		// THEN the existing per-document record is unchanged. The positive
		// control for the negative — the sibling test above proves a
		// PROHIBITION does move a record on this same code path, so an
		// unchanged record here is a real "standing consents are not
		// retroactive" result rather than a retroactive engine that never ran.
		const after = await getConsent(req, token, f.consentId)
		expect(after.consentStatus).toBe('objection_received')
		expect(after.policyMatch ?? null).toBeNull()
		expect(after.publicationDecision).toBe(before.publicationDecision)
		expect(after.notificationStatus).toBe(before.notificationStatus)
	})
})

// ---------------------------------------------------------------------------
// UNTAGGED — the spec describes behaviour the shipped code does not have.
// These run and assert the ACTUAL contract, so they document the gap and go
// red the day it is closed. They carry no @e2e tag on purpose.
// ---------------------------------------------------------------------------

test.describe('consent workflow — documented gaps (intentionally untagged)', () => {
	test('GAP: a prohibition match answers 403 and creates NO record', async () => {
		// NO @e2e TAG. The spec scenario "Prohibition match resolves to
		// existing 'anonymized' status" claims detection CREATES a record with
		// consentStatus "anonymized" / publicationDecision "anonymize" /
		// policyMatch → the prohibition. The shipped code does the opposite:
		// `ConsentService::createConsentRequest()` throws
		// `PolicyRejectedException` on a prohibition match BEFORE any record is
		// written, and the controller maps that to 403. There is no API or UI
		// path that produces an `anonymized` record at detection time, so no
		// e2e test can honestly claim that scenario.
		//
		// (An `anonymized` record IS reachable — but only RETROACTIVELY, when a
		// prohibition is added after the record exists. That is a different
		// scenario, and it is tagged above.)
		const f = await seedPolicyMatchedConsent(
			req,
			token,
			'prohibition',
			'gap-403',
		)

		expect(f.consentStatusCode, 'prohibition match rejects the write').toBe(403)
		// The rejection carries the rule identity, which is the part the spec
		// and the Newman collection agree on.
		expect(f.consent.matchKind).toBe('prohibition')
		expect(f.consent.ruleUuid).toBe(f.ruleId)
		expect(f.consent.ruleName).toBe(f.entityText)

		// AND no record was created for the pair.
		const listed = await req.get(`${API}/consents/document/${f.documentId}`, {
			headers: jsonHeaders(token),
		})
		expect(listed.status()).toBe(200)
		expect(await listed.json()).toHaveLength(0)
	})

	test('GAP: override-up on a standing-consent match is rejected, not allowed', async () => {
		// NO @e2e TAG. The spec scenario "Override-up on a standing-consent
		// match is allowed" requires that a publication-decision override
		// (anonymize anyway) SUCCEEDS on a standing-consent-matched record.
		// It cannot: `ConsentUpdateHandler::isStandingConsentOverride()` gates
		// the escape hatch on `$existing['matchKind'] === 'standing_consent'`,
		// but `matchKind` is never stored — it is not declared in the
		// "Publication Consent" schema in lib/Settings/filinq_register.json, so
		// OpenRegister's MagicMapper discards it on write ("Discarding 1
		// property the schema does not declare: matchKind"). The create
		// RESPONSE echoes `matchKind` back, which is what makes this look
		// wired; the stored record has no such field. The guard therefore reads
		// '' on every record and the hatch is permanently closed.
		const f = await seedPolicyMatchedConsent(
			req,
			token,
			'standing_consent',
			'gap-override',
		)
		expect(f.consentStatusCode).toBe(201)

		// The stored record has no matchKind — the root cause, asserted
		// directly so this test names the defect rather than just its symptom.
		const stored = await getConsent(req, token, f.consentId)
		expect(stored.policyMatch).toBe(f.ruleId)
		expect(stored.matchKind ?? null).toBeNull()

		const res = await req.put(`${API}/consents/${f.consentId}`, {
			headers: jsonHeaders(token),
			data: { publicationDecision: 'anonymize' },
		})
		expect(
			res.ok(),
			'the spec says this MUST be allowed; today it is rejected',
		).toBe(false)
		expect((await getConsent(req, token, f.consentId)).publicationDecision).toBe(
			'publish_with_consent',
		)
	})
})

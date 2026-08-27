/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Seeded-fixture helpers for the DEEP, data-dependent Filinq workflow
 * suite (`tests/e2e/workflows/`).
 *
 * Unlike the Gate-19 spec-coverage tests (which only assert that pages
 * render), these tests create REAL data through Filinq's REST API
 * (OpenRegister-backed) and then assert the data surfaces through the UI
 * and through follow-up reads — proving the document features work
 * end-to-end, not just that shells mount.
 *
 * The Filinq frontend is a manifest-driven shell whose Templates and
 * Signing-Requests views are READ-ONLY tables (no inline create/edit/
 * delete forms — see the bug list in the run report). So the create /
 * update / delete legs of every journey are driven via the documented
 * controller endpoints discovered from `lib/Controller/*` and
 * `src/store/modules/*`, and PERSISTENCE is asserted by reloading the
 * relevant UI list (or re-reading the entity) and checking the seeded
 * row / content is present (or gone after delete).
 *
 * Auth: every request reuses the Playwright `storageState` session
 * (admin/admin, established by `tests/e2e/global-setup.ts`). Filinq's
 * write endpoints are session-cookie + CSRF (`requesttoken`) protected,
 * so each helper takes the live request-token harvested from the running
 * page via `harvestToken(page)`.
 *
 * Every seeded entity carries the unique `TEST_PREFIX` so `afterAll`
 * cleanup can find and purge anything a crashing test left behind, and so
 * concurrent runs never collide.
 */

import { type APIRequestContext, type Page, expect } from '@playwright/test'

/** Shared family prefix for ALL workflow-test artefacts (every run). */
export const TEST_FAMILY = 'e2eflow-'

/** Unique-per-run prefix stamped on every seeded entity name. */
export const TEST_PREFIX = `${TEST_FAMILY}${Date.now()}`

/** App API root (index.php-prefixed so it works with NC pretty-URLs off). */
export const API = '/index.php/apps/filinq/api'

/**
 * Harvest the live CSRF request-token from a loaded Filinq page.
 *
 * Filinq's POST/PUT/DELETE routes are CSRF-guarded; `OC.requestToken`
 * is the canonical token the in-app axios client sends. We read it from
 * the running app rather than re-deriving it so the token always matches
 * the active session cookie jar Playwright restored from storageState.
 *
 * @param page A page that has already navigated to /apps/filinq.
 * @return The request-token string (empty if the app hasn't mounted).
 */
export async function harvestToken(page: Page): Promise<string> {
	// `index.php`-prefixed for the same reason `API` above is: nothing rewrites
	// `/apps/...` on a `php -S` runner, so the unprefixed form returns PHP's own
	// 404 page — which carries neither the head `data-requesttoken` attribute
	// nor `window.OC`, making this helper fail with "CSRF request-token must be
	// harvestable" and accusing the session instead of the URL.
	await page.goto('/index.php/apps/filinq', { waitUntil: 'domcontentloaded' })
	// Wait for exactly what the evaluate below reads: a non-empty CSRF
	// request-token.
	//
	// Nextcloud does NOT publish the token as `<meta name="requesttoken">` —
	// that element does not exist on any authenticated page. `layout.user.php`
	// renders it as an attribute on the head element
	// (`<head … data-requesttoken="…">`), and NC core's own accessor
	// `getRequestToken()` (core/src/OC/requesttoken.ts) reads exactly
	// `document.head.dataset.requesttoken`; `window.OC.requestToken` is that
	// same value re-exported once the core bundle has evaluated. So the two
	// readable sources are `window.OC.requestToken` and the head attribute,
	// and this waits for whichever appears first.
	//
	// This replaces `await page.waitForLoadState('networkidle').catch(() => {})`
	// (ADR-074 rule 4 / gate-58). Nextcloud holds long-lived connections open,
	// so networkidle never fires: that wait always ran to its own timeout and
	// the `.catch` turned the timeout into a pass — meaning it gave the token
	// no guarantee whatsoever, and the "CSRF request-token must be harvestable"
	// expectation below was left to race the page.
	//
	// The wait is not swallowed: on PHP's built-in-server 404 page (see the
	// `page.goto` note above) neither source ever appears, and this fails here
	// naming the token, instead of failing later as a mysterious 412 on the
	// first write request.
	await page.waitForFunction(
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		() =>
			Boolean(
				(window as any).OC?.requestToken
				|| document.head.dataset.requesttoken,
			),
		undefined,
		{ timeout: 30_000 },
	)
	const token = await page.evaluate(
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		() =>
			(window as any).OC?.requestToken
			|| document.head.dataset.requesttoken
			|| '',
	)
	expect(
		token,
		'CSRF request-token must be harvestable from the running app',
	).not.toEqual('')
	return token
}

/** Standard JSON headers carrying the CSRF token for a write request. */
export function jsonHeaders(token: string): Record<string, string> {
	return { requesttoken: token, 'Content-Type': 'application/json' }
}

/* --------------------------------------------------------------------- *
 *  Templates
 * --------------------------------------------------------------------- */

export interface TemplateSeed {
	id: string
	name: string
	content: string
	namespace: string
}

/**
 * Create a template via the REST API and return the persisted entity.
 *
 * Mirrors `templateStore.createTemplate()` →
 * `TemplatesController::create` → `TemplateService::createTemplate`,
 * which requires name + content + namespace and persists through
 * OpenRegister (register/schema configured in app config).
 *
 * @param req       The Playwright request context (session-scoped).
 * @param token     The CSRF request-token.
 * @param overrides Optional name / content / namespace overrides.
 * @return The persisted template seed (id + the fields sent).
 */
export async function createTemplate(
	req: APIRequestContext,
	token: string,
	overrides: Partial<TemplateSeed> = {},
): Promise<TemplateSeed> {
	const name =
		overrides.name
		?? `${TEST_PREFIX}-tmpl-${Math.random().toString(36).slice(2, 8)}`
	const content = overrides.content ?? 'Dear {{name}}, welcome to {{org}}.'
	const namespace = overrides.namespace ?? 'filinq'
	const res = await req.post(`${API}/templates`, {
		headers: jsonHeaders(token),
		data: { name, content, namespace },
	})
	expect(
		res.status(),
		`create template HTTP (body: ${await res.text().catch(() => '')})`,
	).toBe(200)
	const body = await res.json()
	expect(body.id, 'created template must carry a persisted id').toBeTruthy()
	expect(body.name).toBe(name)
	return { id: body.id, name, content, namespace }
}

/**
 * Read a single template back by id (proves persistence).
 *
 * @param req   The Playwright request context.
 * @param token The CSRF request-token.
 * @param id    The template id.
 * @return The HTTP status and parsed body.
 */
export async function getTemplate(
	req: APIRequestContext,
	token: string,
	id: string,
) {
	const res = await req.get(`${API}/templates/${id}`, {
		headers: jsonHeaders(token),
	})
	return { status: res.status(), body: await res.json().catch(() => ({})) }
}

/**
 * List all templates.
 *
 * @param req   The Playwright request context.
 * @param token The CSRF request-token.
 * @return The HTTP status and the results array.
 */
export async function listTemplates(req: APIRequestContext, token: string) {
	const res = await req.get(`${API}/templates`, { headers: jsonHeaders(token) })
	const body = await res.json().catch(() => ({ results: [] }))
	return {
		status: res.status(),
		results: (body.results ?? []) as Array<Record<string, unknown>>,
	}
}

/**
 * Delete a template by id.
 *
 * @param req   The Playwright request context.
 * @param token The CSRF request-token.
 * @param id    The template id.
 * @return The HTTP status code.
 */
export async function deleteTemplate(
	req: APIRequestContext,
	token: string,
	id: string,
): Promise<number> {
	const res = await req.delete(`${API}/templates/${id}`, {
		headers: jsonHeaders(token),
	})
	return res.status()
}

/**
 * Purge every template whose name starts with the run prefix family.
 *
 * Defensive afterAll cleanup so a crashed create/update test never leaves
 * orphaned rows that would pollute the next run's list assertions.
 *
 * @param req   The Playwright request context.
 * @param token The CSRF request-token.
 * @return Resolves once all family-prefixed templates are deleted.
 */
export async function cleanupTemplates(
	req: APIRequestContext,
	token: string,
): Promise<void> {
	const { results } = await listTemplates(req, token)
	for (const t of results) {
		const name = String(t.name ?? '')
		const id = String(t.id ?? '')
		// Purge the whole TEST_FAMILY (incl. orphans from crashed prior runs),
		// not just this run's unique prefix.
		if (name.startsWith(TEST_FAMILY) && id !== '') {
			await deleteTemplate(req, token, id).catch(() => 0)
		}
	}
}

/* --------------------------------------------------------------------- *
 *  Nextcloud files (real file nodes for file-backed workflows)
 * --------------------------------------------------------------------- */

/**
 * Create a real file in the admin user's Files via WebDAV and return its
 * dav path + numeric fileId. The Filinq signing / anonymization flows
 * are file-backed, so we need a genuine Nextcloud file node.
 *
 * @param req      The Playwright request context.
 * @param token    The CSRF request-token.
 * @param relPath  Path under the admin Files root (e.g. "folder/file.txt").
 * @param contents The file contents to write.
 * @return The PUT status and the resolved numeric fileId ("" if absent).
 */
export async function createDavFile(
	req: APIRequestContext,
	token: string,
	relPath: string,
	contents: string,
): Promise<{ status: number; fileId: string }> {
	const url = `/remote.php/dav/files/admin/${relPath}`
	const put = await req.put(url, {
		headers: { requesttoken: token },
		data: contents,
	})
	let fileId = ''
	if (put.status() === 201 || put.status() === 204) {
		// PROPFIND the file to read its oc:fileid.
		const pf = await req.fetch(url, {
			method: 'PROPFIND',
			headers: {
				requesttoken: token,
				Depth: '0',
				'Content-Type': 'application/xml',
			},
			data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
		})
		const xml = await pf.text()
		fileId = xml.match(/<oc:fileid>(\d+)<\/oc:fileid>/)?.[1] ?? ''
	}
	return { status: put.status(), fileId }
}

/**
 * MKCOL a folder under the admin user's Files.
 *
 * @param req     The Playwright request context.
 * @param token   The CSRF request-token.
 * @param relPath The folder path under the admin Files root.
 * @return The MKCOL HTTP status code.
 */
export async function createDavFolder(
	req: APIRequestContext,
	token: string,
	relPath: string,
): Promise<number> {
	const res = await req.fetch(`/remote.php/dav/files/admin/${relPath}`, {
		method: 'MKCOL',
		headers: { requesttoken: token },
	})
	return res.status()
}

/**
 * Delete a dav path (folder or file) — afterAll cleanup helper.
 *
 * @param req     The Playwright request context.
 * @param token   The CSRF request-token.
 * @param relPath The path under the admin Files root to delete.
 * @return Resolves once the DELETE has been attempted (errors swallowed).
 */
export async function deleteDavPath(
	req: APIRequestContext,
	token: string,
	relPath: string,
): Promise<void> {
	await req
		.fetch(`/remote.php/dav/files/admin/${relPath}`, {
			method: 'DELETE',
			headers: { requesttoken: token },
		})
		.catch(() => {})
}

// ---------------------------------------------------------------------------
// Publication-policy fixtures (consent workflow suite)
// ---------------------------------------------------------------------------

/**
 * ⚠️ EVERYTHING SEEDED BELOW LEAKS PERMANENTLY. READ THIS BEFORE ADDING MORE.
 *
 * Two of the three record kinds this file creates CANNOT be deleted over HTTP,
 * and that is correct app behaviour rather than a teardown bug (measured, see
 * the teardown note in ../spec-coverage/entity-publication-policies.spec.ts):
 *
 *   DELETE api/policy/standing-consents/{id} -> 200. Removable.
 *   DELETE api/consents/{id}                 -> 405. No delete route exists.
 *   DELETE api/policy/prohibitions/{id}      -> 409. ArchivalImmutable —
 *       prohibitions are retention-protected by design.
 *
 * So every prohibition and every workflow consent a run creates stays in the
 * instance forever. The ONLY thing that keeps that survivable is the naming
 * discipline: every fixture name is stamped with `POLICY_PREFIX` below, which
 * embeds `Date.now()`. That makes leaked rows (a) identifiable as test debris
 * and (b) incapable of colliding with a later run's exact-match rules — which
 * matters more than tidiness here, because a prohibition is a MATCH RULE: a
 * reused name would silently pre-empt a future run's consent records and turn
 * unrelated tests red for reasons that look like product bugs.
 *
 * Never hardcode a policy fixture name. Never reuse one across runs.
 */
export const POLICY_PREFIX = `e2epol-${Date.now()}`

/** One seeded (prohibition | standing-consent) + consent pair. */
export interface PolicyMatchedConsent {
	/** The unique entity text both the rule and the consent record carry. */
	entityText: string
	/** The `documentId` of the seeded per-document consent record. */
	documentId: string
	/** The `entityKey` of the seeded per-document consent record. */
	entityKey: string
	/** UUID of the seeded policy rule. */
	ruleId: string
	/**
	 * UUID of the seeded `scope: "document"` consent record, or `''` when the
	 * policy layer refused to create one (the prohibition path answers 403 and
	 * deliberately creates NO record — see `kind: 'prohibition'` below).
	 */
	consentId: string
	/** The create response body for the consent POST, for direct assertions. */
	consent: Record<string, unknown>
	/** HTTP status the consent POST answered with (201, 200 on re-POST, 403). */
	consentStatusCode: number
}

/**
 * Seed a policy rule and then a `scope: "document"` consent for a matching
 * entity, exercising the real policy-resolution path.
 *
 * `kind` selects which layer is under test, and the two behave DIFFERENTLY on
 * purpose — this is the part worth knowing before writing assertions:
 *
 *   'standing_consent' — the consent POST succeeds (201) and comes back
 *       pre-resolved: `consentStatus: "consent_given"`,
 *       `notificationStatus: "skipped"`,
 *       `publicationDecision: "publish_with_consent"`, `policyMatch` = rule id.
 *
 *   'prohibition' — the consent POST is REJECTED with 403 and NO record is
 *       created. `ConsentService::createConsentRequest()` throws
 *       `PolicyRejectedException` on a prohibition match, and the controller
 *       maps it to 403 carrying `matchKind` / `ruleUuid` / `ruleName`. Callers
 *       must not expect an `anonymized` record from this path.
 *
 *   'none' — no rule is created; the consent falls through to the WOO flow
 *       (`pending` / `pending` / `pending` with a computed objectionDeadline).
 *
 * @param req   The Playwright request context (carries the admin session).
 * @param token The CSRF request-token from {@see harvestToken}.
 * @param kind  Which policy layer to seed ahead of the consent.
 * @param label Short discriminator so several fixtures in one spec stay unique.
 * @return The seeded identifiers plus the consent POST's body and status.
 */
export async function seedPolicyMatchedConsent(
	req: APIRequestContext,
	token: string,
	kind: 'prohibition' | 'standing_consent' | 'none',
	label: string,
): Promise<PolicyMatchedConsent> {
	const entityText = `${POLICY_PREFIX}-${label}`
	const documentId = `${entityText}-doc`
	const entityKey = `${entityText}-key`
	let ruleId = ''

	if (kind === 'prohibition') {
		const res = await req.post(`${API}/policy/prohibitions`, {
			headers: jsonHeaders(token),
			// `primaryName` and `reason` are schema-required; omitting either
			// answers 500 rather than 422.
			data: {
				primaryName: entityText,
				reason: 'e2e consent-workflow fixture',
				entityType: 'PERSON',
				matchRules: [{ type: 'exact', value: entityText }],
				active: true,
			},
		})
		expect(
			res.status(),
			`seed prohibition (${await res.text().catch(() => '')})`,
		).toBe(201)
		ruleId = (await res.json()).id
	} else if (kind === 'standing_consent') {
		const res = await req.post(`${API}/policy/standing-consents`, {
			headers: jsonHeaders(token),
			data: {
				entityText,
				entityType: 'PERSON',
				consentMethod: 'paper',
				matchRules: [{ type: 'exact', value: entityText }],
			},
		})
		expect(
			res.status(),
			`seed standing consent (${await res.text().catch(() => '')})`,
		).toBe(201)
		ruleId = (await res.json()).id
	}

	const res = await req.post(`${API}/consents`, {
		headers: jsonHeaders(token),
		data: {
			documentId,
			entityKey,
			entityText,
			entityType: 'PERSON',
			scope: 'document',
		},
	})
	const consentStatusCode = res.status()
	const consent = (await res.json().catch(() => ({}))) as Record<string, unknown>

	return {
		entityText,
		documentId,
		entityKey,
		ruleId,
		consentId: (consent.id as string) ?? '',
		consent,
		consentStatusCode,
	}
}

/**
 * Read one consent record back through the API.
 *
 * @param req   The Playwright request context.
 * @param token The CSRF request-token.
 * @param id    The consent record UUID.
 * @return The consent record body.
 */
export async function getConsent(
	req: APIRequestContext,
	token: string,
	id: string,
): Promise<Record<string, unknown>> {
	const res = await req.get(`${API}/consents/${id}`, {
		headers: jsonHeaders(token),
	})
	expect(res.status(), `GET /api/consents/${id}`).toBe(200)
	return (await res.json()) as Record<string, unknown>
}

/**
 * Create a `publicationProhibition` rule matching `entityText`.
 *
 * Separate from {@see seedPolicyMatchedConsent} because the retroactive
 * scenarios need the rule created AFTER the consent record already exists.
 *
 * ⚠️ Permanent: prohibitions answer 409 on DELETE. Always pass a
 * `POLICY_PREFIX`-stamped `entityText`.
 *
 * @param req        The Playwright request context.
 * @param token      The CSRF request-token.
 * @param entityText The exact entity text the rule must match.
 * @return The created rule's UUID.
 */
export async function createProhibition(
	req: APIRequestContext,
	token: string,
	entityText: string,
): Promise<string> {
	const res = await req.post(`${API}/policy/prohibitions`, {
		headers: jsonHeaders(token),
		data: {
			primaryName: entityText,
			reason: 'e2e consent-workflow fixture',
			entityType: 'PERSON',
			matchRules: [{ type: 'exact', value: entityText }],
			active: true,
		},
	})
	expect(
		res.status(),
		`create prohibition (${await res.text().catch(() => '')})`,
	).toBe(201)
	return (await res.json()).id
}

/**
 * Create a `scope: "entity"` standing consent matching `entityText`.
 *
 * @param req        The Playwright request context.
 * @param token      The CSRF request-token.
 * @param entityText The exact entity text the rule must match.
 * @return The created standing consent's UUID.
 */
export async function createStandingConsent(
	req: APIRequestContext,
	token: string,
	entityText: string,
): Promise<string> {
	const res = await req.post(`${API}/policy/standing-consents`, {
		headers: jsonHeaders(token),
		data: {
			entityText,
			entityType: 'PERSON',
			consentMethod: 'paper',
			matchRules: [{ type: 'exact', value: entityText }],
		},
	})
	expect(
		res.status(),
		`create standing consent (${await res.text().catch(() => '')})`,
	).toBe(201)
	return (await res.json()).id
}

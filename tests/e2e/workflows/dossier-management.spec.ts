/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * DEEP, data-dependent workflow tests — dossier membership and lifecycle.
 *
 * The data-layer counterpart to `spec-coverage/dossier-management.spec.ts`
 * (which asserts the surfaces render). Here the behaviours that only show up
 * against a real OpenRegister are proven end to end:
 *
 *   - a rename carries every other field forward (OR saves are PUT-semantic,
 *     so a name-only write nulls bases, checkedOn, status and documents)
 *   - one document belongs to two dossiers, and unlinking from one leaves it
 *     in the other AND leaves the file on disk
 *   - the lifecycle guard refuses an out-of-order status write
 *
 * ⚠️ EVERY WRITE NEEDS THE HARVESTED CSRF TOKEN. Filinq's POST/PUT/DELETE
 * routes are CSRF-guarded, and Playwright's bare `request` fixture carries the
 * session cookie but no request-token — so an unauthenticated-looking write
 * comes back as an HTML error page and `response.json()` yields `undefined`
 * rather than anything that names the cause. `harvestToken` + `jsonHeaders`
 * from `./_fixtures.ts` is the pattern the rest of this suite already uses.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from '../spec-coverage/_helpers.ts'
import { API, harvestToken, jsonHeaders } from './_fixtures.ts'

const DOSSIERS = `${API}/dossiers`

/**
 * Harvest a CSRF token bound to the SAME session the request fixture uses.
 *
 * ⚠️ THE TOKEN AND THE COOKIE JAR MUST COME FROM ONE CONTEXT. Harvesting from
 * a `browser.newContext()` page and then writing through the test's `request`
 * fixture pairs a token from one session with the cookies of another: the write
 * is rejected, the response is an error page, and `response.json()` yields an
 * object with none of the fields asserted — a failure that names the assertion
 * rather than the mismatch. Playwright's test-level `page` and `request`
 * fixtures share one context, so harvesting from `page` here is what makes the
 * token valid for the requests below.
 *
 * The requests themselves go through `page.request`, NOT the bare `request`
 * fixture. `request` did not carry the restored admin session here, so every
 * read came back as `{ error: "Not authenticated" }` — valid JSON with none of
 * the asserted fields, which fails as `Received: undefined` and points at the
 * assertion instead of at the missing session. `page.request` shares the page's
 * cookie jar by construction.
 *
 * ⚠️ THE READS NEED THE TOKEN TOO. Filinq's GET routes are CSRF-guarded, so a
 * read without `requesttoken` answers 412 `{"message":"CSRF check failed"}` —
 * valid JSON, so `.json()` succeeds and every asserted field is `undefined`.
 * That reads as a data bug in the endpoint rather than a missing header on the
 * request, which is exactly the wrong place to look.
 *
 * @param page The test's page fixture.
 * @return The request-token.
 */
async function tokenFor(page: Page): Promise<string> {
	return harvestToken(page)
}

/**
 * Create a dossier through the API and return its id.
 *
 * @param req The test's request fixture.
 * @param token A token harvested from the same context.
 * @param name The dossier name.
 * @param body Any extra fields.
 * @return The created dossier's uuid.
 */
async function createDossier(
	req: APIRequestContext,
	token: string,
	name: string,
	body: Record<string, unknown> = {},
): Promise<string> {
	const response = await req.post(DOSSIERS, {
		data: { name, ...body },
		headers: jsonHeaders(token),
	})
	expect(response.status(), await response.text()).toBe(201)
	const dossier = await response.json()
	expect(dossier.id, 'a created dossier must carry its uuid').toBeTruthy()
	return dossier.id
}

test.describe('dossier-management — membership and lifecycle', () => {
	test('a rename preserves every other field', async ({ page }) => {
		const token = await tokenFor(page)
		const req = page.request
		// @e2e openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md#inline-rename-preserves-the-other-fields
		const id = await createDossier(req, token, `Handhaving ${Date.now()}`, {
			description: 'Bezwaren handhaving',
			bases: ['persoonsgegevens'],
		})

		const renamed = await req.put(`${DOSSIERS}/${id}/name`, {
			data: { name: 'Handhaving 2025' },
			headers: jsonHeaders(token),
		})
		expect(renamed.status(), await renamed.text()).toBe(200)

		const after = await (
			await req.get(`${DOSSIERS}/${id}`, { headers: jsonHeaders(token) })
		).json()
		expect(after.name).toBe('Handhaving 2025')
		// The whole point: a name-only PUT would have nulled these.
		expect(after.description).toBe('Bezwaren handhaving')
		expect(after.bases.map((b: { slug: string }) => b.slug)).toEqual([
			'persoonsgegevens',
		])
	})

	test('the lifecycle refuses an out-of-order transition', async ({ page }) => {
		const token = await tokenFor(page)
		const req = page.request
		// @e2e openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md#only-legal-transitions-are-offered
		const id = await createDossier(req, token, `Lifecycle ${Date.now()}`)

		const refused = await req.put(`${DOSSIERS}/${id}/status`, {
			data: { status: 'published' },
			headers: jsonHeaders(token),
		})
		// 409 with a readable message, not a 500 and not a silent success.
		expect(refused.status()).toBe(409)
		expect((await refused.json()).error).toMatch(/cannot move from "open"/)

		const still = await (
			await req.get(`${DOSSIERS}/${id}`, { headers: jsonHeaders(token) })
		).json()
		expect(still.status).toBe('open')

		const allowed = await req.put(`${DOSSIERS}/${id}/status`, {
			data: { status: 'in-review' },
			headers: jsonHeaders(token),
		})
		expect(allowed.status()).toBe(200)
		expect((await allowed.json()).status).toBe('in-review')
	})

	test('one document belongs to two dossiers, and unlinking keeps the file', async ({
		page,
	}) => {
		const token = await tokenFor(page)
		const req = page.request
		// @e2e openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md#one-document-is-a-member-of-two-dossiers
		// @e2e openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md#unlinking-a-referenced-member-keeps-the-file
		const stamp = Date.now()
		const dossierA = await createDossier(req, token, `Dossier A ${stamp}`)
		const dossierB = await createDossier(req, token, `Dossier B ${stamp}`)

		// Put a real file in A's home folder through WebDAV, then read its id
		// back from the dossier's own document list — that is the id the
		// membership relation stores.
		// WebDAV writes are CSRF-guarded too — same header as the API calls
		// above. Without it the PUT answers 412 and the assertion below reports
		// the accepted-status array rather than what actually came back.
		const upload = await req.put(
			`/remote.php/dav/files/admin/Filinq/Dossier A ${stamp}/bijlage.txt`,
			{
				data: 'e2e membership fixture',
				headers: {
					requesttoken: token,
					'Content-Type': 'application/octet-stream',
				},
			},
		)
		expect(
			[201, 204],
			`the fixture file must upload (HTTP ${upload.status()})`,
		).toContain(upload.status())

		const withFile = await (
			await req.get(`${DOSSIERS}/${dossierA}`, { headers: jsonHeaders(token) })
		).json()
		const fileId = withFile.documents[0]?.id
		expect(
			fileId,
			'the uploaded file must appear in A via its home folder',
		).toBeTruthy()

		// Link the SAME file into B. Link semantics: nothing is copied or moved.
		const linked = await req.post(`${DOSSIERS}/${dossierB}/documents`, {
			data: { fileId },
			headers: jsonHeaders(token),
		})
		expect(linked.status(), await linked.text()).toBe(200)

		const bWithLink = await linked.json()
		expect(bWithLink.documents.map((d: { id: number }) => d.id)).toContain(
			fileId,
		)

		// Still in A: linking must not have moved it out of A's home folder.
		const aStill = await (
			await req.get(`${DOSSIERS}/${dossierA}`, { headers: jsonHeaders(token) })
		).json()
		expect(aStill.documents.map((d: { id: number }) => d.id)).toContain(fileId)

		// Removing from B must drop only B's reference. Because A also holds
		// the file, the removal mode is `unlink`, never `trash`.
		const mode = await (
			await req.get(
				`${DOSSIERS}/${dossierB}/documents/${fileId}/removal-mode`,
				{ headers: jsonHeaders(token) },
			)
		).json()
		expect(mode.mode).toBe('unlink')

		const removed = await req.delete(
			`${DOSSIERS}/${dossierB}/documents/${fileId}`,
			{
				headers: jsonHeaders(token),
			},
		)
		expect(removed.status()).toBe(200)

		const aAfter = await (
			await req.get(`${DOSSIERS}/${dossierA}`, { headers: jsonHeaders(token) })
		).json()
		expect(
			aAfter.documents.map((d: { id: number }) => d.id),
			'unlinking from B must leave the file in A',
		).toContain(fileId)

		const bAfter = await (
			await req.get(`${DOSSIERS}/${dossierB}`, { headers: jsonHeaders(token) })
		).json()
		expect(bAfter.documents.map((d: { id: number }) => d.id)).not.toContain(
			fileId,
		)
	})

	test('a dossier created through the API is listed in the UI', async ({
		page,
	}) => {
		// @e2e openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md#index-lists-dossiers-with-live-context
		const token = await tokenFor(page)
		const req = page.request
		const name = `Persisted ${Date.now()}`
		await createDossier(req, token, name, { description: 'Written via the API' })

		await go(page, 'dossiers')
		// Asserted through the rendered list, not only the API, so the read
		// path the operator actually uses is the one under test.
		await expect(page.getByText(name)).toBeVisible()
	})
})

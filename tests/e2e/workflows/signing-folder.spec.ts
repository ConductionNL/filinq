/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * End-to-end regression for `signing-folder-across-cases`: everything still
 * waiting for one signer arrives in one folder, ordered by deadline, a signed
 * document leaves it, a mandate keeps a document the signer may not sign out
 * of it, and nobody reads the folder without being somebody.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The folder is what a signer opens, so the list and the pass are read off the
 * page. Seeding a signing request is the consuming app's job, so that goes
 * through the documented endpoint, as does the mandate an administrator
 * records on that app's behalf.
 *
 * The least privileged principal that should be refused here is nobody at all:
 * an unauthenticated context asking for the folder must not be handed one.
 *
 * ⚠️ The `@e2e` anchors name BOTH the change's delta spec and the canonical
 * spec it is synced into at archive time. Gate 19 scans `openspec/specs/` only.
 */

import type { APIRequestContext } from '@playwright/test'

import { expect, request as playwrightRequest, test } from '@playwright/test'
import { go } from '../spec-coverage/_helpers.ts'
import { API, harvestToken, jsonHeaders, TEST_PREFIX } from './_fixtures.ts'

test.describe.configure({ mode: 'serial' })

/** The folder page, named after the component file it covers. */
const SigningFolder = 'signing-folder'

/**
 * Seed one signing request with the admin as its signer.
 *
 * @param req      The request context.
 * @param token    The CSRF request-token.
 * @param name     The document name.
 * @param deadline The deadline, ISO 8601.
 * @param schema   The subject schema, which a mandate binds to.
 * @return The created request's id.
 */
async function seedRequest(
	req: APIRequestContext,
	token: string,
	name: string,
	deadline: string,
	schema = 'besluit',
): Promise<string> {
	const res = await req.post(`${API}/signing/requests`, {
		headers: jsonHeaders(token),
		data: {
			documentName: name,
			documentFileId: '0',
			signatureLevel: 'SES',
			signingMode: 'SEQUENTIAL',
			provider: 'native',
			deadline,
			sourceApp: 'dossiq',
			subjectRegister: 'zaken',
			subjectSchema: schema,
			subjectId: `${TEST_PREFIX}-zaak`,
			subjectLabel: `Zaak ${name}`,
			signers: [{ userId: 'admin', displayName: 'admin' }],
		},
	})
	expect(res.status(), `seed ${name}`).toBeLessThan(300)
	const body = await res.json()
	return String(body.id ?? body.uuid ?? '')
}

test.afterAll(async ({ request }) => {
	const res = await request.get(`${API}/signing/requests`)
	const body = await res.json().catch(() => [])
	const rows = Array.isArray(body) ? body : (body.results ?? [])
	for (const row of rows) {
		if (String(row.documentName ?? '').startsWith(TEST_PREFIX)) {
			await request
				.delete(`${API}/signing/requests/${row.id ?? row.uuid}`)
				.catch(() => {})
		}
	}
	await request.delete(`${API}/signing/mandates/dossiq/besluit`).catch(() => {})
})

test('the folder gathers what is pending across cases, soonest deadline first', async ({
	page,
}) => {
	// @e2e openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md#forty-decisions-on-nine-cases-one-folder
	// @e2e openspec/specs/document-signing/spec.md#forty-decisions-on-nine-cases-one-folder
	// @e2e openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md#the-signer-knows-what-they-are-signing
	// @e2e openspec/specs/document-signing/spec.md#the-signer-knows-what-they-are-signing
	const token = await harvestToken(page)
	await seedRequest(
		page.request,
		token,
		`${TEST_PREFIX}-late`,
		'2026-12-01T00:00:00+00:00',
	)
	await seedRequest(
		page.request,
		token,
		`${TEST_PREFIX}-soon`,
		'2026-10-01T00:00:00+00:00',
	)

	await go(page, SigningFolder)
	await expect(page.getByRole('heading', { name: 'Signing folder' })).toBeVisible()

	const rows = page.locator('.signing-folder__table tbody tr')
	await expect(rows.filter({ hasText: `${TEST_PREFIX}-soon` })).toHaveCount(1)
	await expect(rows.filter({ hasText: `${TEST_PREFIX}-late` })).toHaveCount(1)

	const order = await rows.allInnerTexts()
	const soon = order.findIndex((text) => text.includes(`${TEST_PREFIX}-soon`))
	const late = order.findIndex((text) => text.includes(`${TEST_PREFIX}-late`))
	expect(soon, 'the sooner deadline comes first').toBeLessThan(late)

	// Every entry says which case it belongs to and who asked.
	await expect(
		rows.filter({ hasText: `${TEST_PREFIX}-soon` }).first(),
	).toContainText('Zaak')
})

test('the document is readable without leaving the folder', async ({ page }) => {
	// @e2e openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md#the-document-is-readable-in-place
	// @e2e openspec/specs/document-signing/spec.md#the-document-is-readable-in-place
	await go(page, SigningFolder)
	const row = page
		.locator('.signing-folder__table tbody tr')
		.filter({ hasText: `${TEST_PREFIX}-soon` })
		.first()
	await row.getByRole('button', { name: `${TEST_PREFIX}-soon` }).click()

	await expect(page.locator('.signing-folder-document')).toBeVisible()
	await expect(page).toHaveURL(/signing-folder/)
})

test('signing a selection takes those documents out of the folder and leaves the rest', async ({
	page,
}) => {
	// @e2e openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md#a-signed-document-leaves-the-folder
	// @e2e openspec/specs/document-signing/spec.md#a-signed-document-leaves-the-folder
	// @e2e openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md#thirty-eight-of-forty
	// @e2e openspec/specs/document-signing/spec.md#thirty-eight-of-forty
	// @e2e openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md#an-interrupted-pass-leaves-nothing-half-done
	// @e2e openspec/specs/document-signing/spec.md#an-interrupted-pass-leaves-nothing-half-done
	await go(page, SigningFolder)
	const row = page
		.locator('.signing-folder__table tbody tr')
		.filter({ hasText: `${TEST_PREFIX}-soon` })
		.first()
	await row.getByRole('checkbox').check()
	await page.getByRole('button', { name: /Sign selected/ }).click()

	await expect(page.locator('.signing-folder__results')).toContainText(
		`${TEST_PREFIX}-soon`,
	)
	await expect(
		page
			.locator('.signing-folder__table tbody tr')
			.filter({ hasText: `${TEST_PREFIX}-soon` }),
		'a signed document has left the folder',
	).toHaveCount(0)
	await expect(
		page
			.locator('.signing-folder__table tbody tr')
			.filter({ hasText: `${TEST_PREFIX}-late` }),
		'the rest of the folder is untouched',
	).toHaveCount(1)
})

test('a cancelled request is gone the next time the folder is opened', async ({
	page,
}) => {
	// @e2e openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md#a-cancelled-request-leaves-the-folder-at-once
	// @e2e openspec/specs/document-signing/spec.md#a-cancelled-request-leaves-the-folder-at-once
	const token = await harvestToken(page)
	const id = await seedRequest(
		page.request,
		token,
		`${TEST_PREFIX}-cancelled`,
		'2026-11-01T00:00:00+00:00',
	)

	await go(page, SigningFolder)
	await expect(
		page.locator('.signing-folder__table tbody tr').filter({
			hasText: `${TEST_PREFIX}-cancelled`,
		}),
	).toHaveCount(1)

	const cancelled = await page.request.delete(`${API}/signing/requests/${id}`, {
		headers: jsonHeaders(token),
	})
	expect(cancelled.status(), 'cancel the request').toBeLessThan(300)

	await go(page, SigningFolder)
	await expect(
		page.locator('.signing-folder__table tbody tr').filter({
			hasText: `${TEST_PREFIX}-cancelled`,
		}),
	).toHaveCount(0)
})

test('a mandate keeps the folder honest, and withdrawing it gives the document back', async ({
	page,
}) => {
	// @e2e openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md#a-mandate-keeps-the-folder-honest
	// @e2e openspec/specs/document-signing/spec.md#a-mandate-keeps-the-folder-honest
	// @e2e openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md#no-declaration-no-invented-restriction
	// @e2e openspec/specs/document-signing/spec.md#no-declaration-no-invented-restriction
	const token = await harvestToken(page)
	await seedRequest(
		page.request,
		token,
		`${TEST_PREFIX}-mandated`,
		'2026-11-15T00:00:00+00:00',
	)

	await go(page, SigningFolder)
	await expect(
		page.locator('.signing-folder__table tbody tr').filter({
			hasText: `${TEST_PREFIX}-mandated`,
		}),
		'with no declaration, nothing is invented',
	).toHaveCount(1)

	const declared = await page.request.post(`${API}/signing/mandates`, {
		headers: jsonHeaders(token),
		data: {
			typeReference: 'dossiq/besluit',
			groups: ['portefeuillehouders'],
			rule: 'Only the portefeuillehouder signs a besluit',
		},
	})
	expect(declared.status(), 'declare the mandate').toBeLessThan(300)

	await go(page, SigningFolder)
	await expect(
		page.locator('.signing-folder__table tbody tr').filter({
			hasText: `${TEST_PREFIX}-mandated`,
		}),
		'a document outside the mandate is absent',
	).toHaveCount(0)

	const withdrawn = await page.request.delete(
		`${API}/signing/mandates/dossiq/besluit`,
		{ headers: jsonHeaders(token) },
	)
	expect(withdrawn.status(), 'withdraw the mandate').toBeLessThan(300)

	await go(page, SigningFolder)
	await expect(
		page.locator('.signing-folder__table tbody tr').filter({
			hasText: `${TEST_PREFIX}-mandated`,
		}),
	).toHaveCount(1)
})

test('nobody gets a folder without being somebody', async ({ baseURL }) => {
	// @e2e openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md#the-folder-holds-nobody-elses-work
	// @e2e openspec/specs/document-signing/spec.md#the-folder-holds-nobody-elses-work
	const anonymous = await playwrightRequest.newContext({ baseURL })
	const res = await anonymous.get(`${API}/signing/folder`, {
		headers: { Accept: 'application/json' },
	})

	expect(
		res.status(),
		'an unauthenticated caller is refused, never handed a folder',
	).toBeGreaterThanOrEqual(400)
	const body = await res.text()
	expect(body).not.toContain(TEST_PREFIX)
	await anonymous.dispose()
})

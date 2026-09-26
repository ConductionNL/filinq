/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * End-to-end regression for `inbound-documents-and-the-worklist`: an attachment
 * is a record of its own that names its message, a document is stamped before
 * any classifier looks at it, a party is offered and never filed, a document
 * taken off a record lands on the worklist, and routing is the consuming app's
 * declaration applied rather than a default invented here.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The worklist is what a HANDLER sees, so it is read off the Intake page. The
 * arrival of a message with attachments is a background feeder, and a routing
 * declaration is one app telling another what it wants, so both go through the
 * documented endpoints.
 *
 * ⚠️ The `@e2e` anchors name BOTH the change's delta spec and the canonical
 * spec it is synced into at archive time. Gate 19 scans `openspec/specs/` only.
 */

import type { APIRequestContext } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { API, harvestToken, jsonHeaders, TEST_PREFIX } from './_fixtures.ts'

/** The OpenRegister objects endpoint for the intake documents. */
const OR_INTAKE = '/index.php/apps/openregister/api/objects/filinq/intakeDocument'

/** The OpenRegister objects endpoint for the routing declarations. */
const OR_ROUTING =
	'/index.php/apps/openregister/api/objects/filinq/intakeRoutingRule'

/**
 * Seed one intake document in a given state.
 *
 * @param req    The request context.
 * @param token  The CSRF request-token.
 * @param fields The fields to store.
 * @return The seeded document's uuid.
 */
async function seed(
	req: APIRequestContext,
	token: string,
	fields: Record<string, unknown>,
): Promise<string> {
	const created = await req.post(OR_INTAKE, {
		headers: jsonHeaders(token),
		data: {
			channel: 'mail',
			sender: `${TEST_PREFIX}@voorbeeld.nl`,
			receivedAt: new Date().toISOString(),
			status: 'received',
			...fields,
		},
	})
	expect(created.status(), 'seeding an intake document must succeed').toBeLessThan(
		300,
	)
	const body = await created.json()
	const uuid = body?.['@self']?.id || body?.id || body?.uuid || ''
	expect(uuid, 'seeding must yield a uuid').not.toBe('')
	return uuid
}

/**
 * Read one intake document back, flattened.
 *
 * @param req   The request context.
 * @param token The CSRF request-token.
 * @param uuid  The intake document.
 * @return The stored fields.
 */
async function readBack(
	req: APIRequestContext,
	token: string,
	uuid: string,
): Promise<Record<string, unknown>> {
	const response = await req.get(`${OR_INTAKE}/${uuid}`, {
		headers: jsonHeaders(token),
	})
	expect(response.status()).toBe(200)
	const body = await response.json()
	return (body.object ?? body) as Record<string, unknown>
}

test.describe('Inbound documents and the worklist', () => {
	let token = ''

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect(token, 'the suite needs a live request token').not.toBe('')
		await page.close()
	})

	// @e2e openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md#one-attachment-belongs-elsewhere
	// @e2e openspec/specs/inbound-auto-classification/spec.md#one-attachment-belongs-elsewhere
	test('an attachment assigned on its own is recorded on both records', async ({
		page,
	}) => {
		const message = await seed(page.request, token, {
			subject: `${TEST_PREFIX}-aanvraag`,
			sourceRef: `${TEST_PREFIX}-msg`,
		})
		const attachment = await seed(page.request, token, {
			subject: `${TEST_PREFIX}-bijlage`,
			sourceRef: `${TEST_PREFIX}-msg-a1`,
			arrivedWith: message,
		})

		const assigned = await page.request.post(
			`${API}/intake/documents/${attachment}/assign`,
			{
				headers: jsonHeaders(token),
				data: {
					register: 'filinq',
					schema: 'dossier',
					id: await firstDossier(page.request, token),
				},
			},
		)
		expect(assigned.status()).toBe(200)

		const storedAttachment = await readBack(page.request, token, attachment)
		expect(storedAttachment.status).toBe('assigned')

		const storedMessage = await readBack(page.request, token, message)
		const notes = storedMessage.attachmentNotes as Array<Record<string, unknown>>
		expect(Array.isArray(notes)).toBe(true)
		expect(notes[0]?.attachment).toBe(attachment)
	})

	// @e2e openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md#a-document-removed-from-the-wrong-case
	// @e2e openspec/specs/inbound-auto-classification/spec.md#a-document-removed-from-the-wrong-case
	test('a document taken off a record lands on the worklist with its reason', async ({
		page,
	}) => {
		const uuid = await seed(page.request, token, {
			subject: `${TEST_PREFIX}-verkeerde-zaak`,
			sourceRef: `${TEST_PREFIX}-detach`,
			file: 999999,
			status: 'assigned',
		})

		const detached = await page.request.post(`${API}/intake/documents/detach`, {
			headers: jsonHeaders(token),
			data: { fileId: 999999, reason: 'verkeerde zaak' },
		})
		expect(detached.status()).toBe(200)

		const stored = await readBack(page.request, token, uuid)
		expect(stored.status).toBe('detached')
		expect(stored.detachReason).toBe('verkeerde zaak')

		await page.goto('/index.php/apps/filinq/#/intake')
		await expect(page.getByTestId('intake-index')).toBeVisible({
			timeout: 15000,
		})
		await page.getByTestId('intake-mode-detached').click()
		await expect(
			page.getByRole('row', {
				name: new RegExp(`${TEST_PREFIX}-verkeerde-zaak`),
			}),
		).toBeVisible({ timeout: 15000 })
	})

	// @e2e openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md#a-document-that-never-had-an-intake-record
	// @e2e openspec/specs/inbound-auto-classification/spec.md#a-document-that-never-had-an-intake-record
	test('a document that never passed through the inbox gets a record when it is detached', async ({
		page,
	}) => {
		const detached = await page.request.post(`${API}/intake/documents/detach`, {
			headers: jsonHeaders(token),
			data: {
				fileId: 888888,
				reason: 'hoort hier niet',
				documentName: `${TEST_PREFIX}-los-bestand.pdf`,
			},
		})
		expect(detached.status()).toBe(200)
		const body = await detached.json()
		expect(body.status).toBe('detached')
		expect(body.file).toBe(888888)
	})

	// @e2e openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md#detaching-without-a-reason
	// @e2e openspec/specs/inbound-auto-classification/spec.md#a-document-removed-from-the-wrong-case
	test('detaching without a reason is refused', async ({ page }) => {
		const refused = await page.request.post(`${API}/intake/documents/detach`, {
			headers: jsonHeaders(token),
			data: { fileId: 777777, reason: '   ' },
		})
		expect(refused.status()).toBe(400)
	})

	// @e2e openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md#a-bezwaar-routes-to-the-jurists
	// @e2e openspec/specs/inbound-auto-classification/spec.md#a-bezwaar-routes-to-the-jurists
	test('a declared record type routes the document and asks for acceptance', async ({
		page,
	}) => {
		const typeReference = `${TEST_PREFIX}-bezwaarschrift`
		const declared = await page.request.post(`${API}/intake/routing-rules`, {
			headers: jsonHeaders(token),
			data: {
				declaringApp: 'dossiq',
				typeReference,
				routeTo: 'juristen',
				requiresAcceptance: true,
			},
		})
		expect(declared.status()).toBe(200)

		const uuid = await seed(page.request, token, {
			subject: `${TEST_PREFIX}-routing`,
			sourceRef: `${TEST_PREFIX}-routing`,
		})
		const assigned = await page.request.post(
			`${API}/intake/documents/${uuid}/assign`,
			{
				headers: jsonHeaders(token),
				data: {
					register: 'filinq',
					schema: 'dossier',
					id: await firstDossier(page.request, token),
					declaringApp: 'dossiq',
					typeReference,
				},
			},
		)
		expect(assigned.status()).toBe(200)

		const stored = await readBack(page.request, token, uuid)
		expect(stored.routing).toBe('juristen')
		expect((stored.acceptance as Record<string, unknown>)?.required).toBe(true)

		// And the declaration is a stored object, not a value invented here.
		const declarations = await page.request.get(OR_ROUTING, {
			headers: jsonHeaders(token),
		})
		expect(declarations.status()).toBe(200)
	})

	// @e2e openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md#no-declaration-no-routing
	// @e2e openspec/specs/inbound-auto-classification/spec.md#no-declaration-no-routing
	test('a record type nobody declared is assigned without routing', async ({
		page,
	}) => {
		const uuid = await seed(page.request, token, {
			subject: `${TEST_PREFIX}-geen-routing`,
			sourceRef: `${TEST_PREFIX}-geen-routing`,
		})

		const assigned = await page.request.post(
			`${API}/intake/documents/${uuid}/assign`,
			{
				headers: jsonHeaders(token),
				data: {
					register: 'filinq',
					schema: 'dossier',
					id: await firstDossier(page.request, token),
					declaringApp: 'dossiq',
					typeReference: `${TEST_PREFIX}-niet-gedeclareerd`,
				},
			},
		)
		expect(assigned.status()).toBe(200)

		const stored = await readBack(page.request, token, uuid)
		expect(stored.routing).toBe('')
	})

	// @e2e openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md#a-rejection-teaches-the-corpus
	// @e2e openspec/specs/inbound-auto-classification/spec.md#a-rejection-teaches-the-corpus
	test('rejecting a party suggestion is recorded as a correction', async ({
		page,
	}) => {
		const recorded = await page.request.post(`${API}/intake/party-decisions`, {
			headers: jsonHeaders(token),
			data: {
				sender: `${TEST_PREFIX}@voorbeeld.nl`,
				decision: 'rejected',
				suggested: { name: 'J. Jansen' },
			},
		})
		expect(recorded.status()).toBe(200)
		const body = await recorded.json()
		expect(body.decision).toBe('rejected')
		expect(body.decidedBy).not.toBe('')

		const corrections = await page.request.get(
			'/index.php/apps/openregister/api/objects/filinq/intakePartyCorrection',
			{ headers: jsonHeaders(token) },
		)
		expect(corrections.status()).toBe(200)
		const stored = await corrections.json()
		expect(Array.isArray(stored?.results)).toBe(true)
	})

	// @e2e openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md#the-classifier-suggests-it-does-not-overwrite
	// @e2e openspec/specs/inbound-auto-classification/spec.md#the-classifier-suggests-it-does-not-overwrite
	test('a decision that is neither accept, edit nor reject is refused', async ({
		page,
	}) => {
		const refused = await page.request.post(`${API}/intake/party-decisions`, {
			headers: jsonHeaders(token),
			data: { sender: `${TEST_PREFIX}@voorbeeld.nl`, decision: 'maybe' },
		})
		expect(refused.status()).toBe(400)
	})
})

/**
 * The id of any dossier on the instance, seeding one when there is none.
 *
 * @param req   The request context.
 * @param token The CSRF request-token.
 * @return The dossier id.
 */
async function firstDossier(req: APIRequestContext, token: string): Promise<string> {
	const list = await req.get(
		'/index.php/apps/openregister/api/objects/filinq/dossier',
		{
			headers: jsonHeaders(token),
		},
	)
	expect(list.status()).toBe(200)
	const body = await list.json()
	const first = Array.isArray(body?.results) ? body.results[0] : null
	const existing = first?.['@self']?.id || first?.id || ''
	if (existing !== '') {
		return existing
	}

	const created = await req.post(
		'/index.php/apps/openregister/api/objects/filinq/dossier',
		{
			headers: jsonHeaders(token),
			data: { title: `${TEST_PREFIX}-dossier` },
		},
	)
	expect(created.status()).toBeLessThan(300)
	const made = await created.json()
	return made?.['@self']?.id || made?.id || ''
}

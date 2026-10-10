/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * End-to-end regression for `document-intake-inbox`: a document that arrived
 * through a channel waits in one inbox, a clerk assigns it to a record or
 * rejects it with a reason, and a document that has left the inbox stays left.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The inbox, the assign dialog and the reject dialog are what a CLERK sees, so
 * they are driven through the Intake page. Delivering a document is not a user
 * journey in Filinq at all: a scanner, a mailbox and digital post all reach the
 * inbox through a background feeder, so the seeding here goes through the
 * documented endpoints and OpenRegister's own object API.
 *
 * The refusal is probed with the LEAST privileged principal that should be
 * refused: an ordinary member of no group, against a schema whose rights name a
 * group they are not in. A superuser success would prove almost nothing.
 *
 * ⚠️ The `@e2e` anchors name BOTH the change's delta spec, which is where these
 * scenarios live today, and the canonical spec they are synced into when the
 * change is archived. Gate 19 scans `openspec/specs/` only.
 */

import type { APIRequestContext } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { API, harvestToken, jsonHeaders, TEST_PREFIX } from './_fixtures.ts'

/** The OpenRegister objects endpoint the intake documents are seeded through. */
const OR_OBJECTS = '/index.php/apps/openregister/api/objects/filinq/intakeDocument'

/**
 * Seed one waiting intake document straight into the register.
 *
 * @param req     The request context.
 * @param token   The CSRF request-token.
 * @param channel The channel that delivered it.
 * @param subject The subject line.
 * @return The seeded document's uuid.
 */
async function seedWaiting(
	req: APIRequestContext,
	token: string,
	channel: string,
	subject: string,
): Promise<string> {
	const created = await req.post(OR_OBJECTS, {
		headers: jsonHeaders(token),
		data: {
			channel,
			subject,
			sender: `${TEST_PREFIX}@voorbeeld.nl`,
			receivedAt: new Date().toISOString(),
			sourceRef: `${TEST_PREFIX}-${subject}`,
			status: 'received',
		},
	})
	expect(created.status(), `seeding ${subject} must succeed`).toBeLessThan(300)
	const body = await created.json()
	const uuid = body?.['@self']?.id || body?.id || body?.uuid || ''
	expect(uuid, `seeding ${subject} must yield a uuid`).not.toBe('')
	return uuid
}

/**
 * Read one intake document back out of the register.
 *
 * @param req   The request context.
 * @param token The CSRF request-token.
 * @param uuid  The intake document.
 * @return The stored document.
 */
async function readBack(
	req: APIRequestContext,
	token: string,
	uuid: string,
): Promise<Record<string, unknown>> {
	const response = await req.get(`${OR_OBJECTS}/${uuid}`, {
		headers: jsonHeaders(token),
	})
	expect(response.status()).toBe(200)
	return await response.json()
}

test.describe('The intake inbox', () => {
	let token = ''

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect(token, 'the suite needs a live request token').not.toBe('')
		await page.close()
	})

	// @e2e openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md#the-inbox-shows-waiting-documents
	// @e2e openspec/specs/document-intake-inbox/spec.md#the-inbox-shows-waiting-documents
	test('lists what is waiting, with channel, sender, subject and received date', async ({
		page,
	}) => {
		const subject = `${TEST_PREFIX}-bezwaar-scan`
		await seedWaiting(page.request, token, 'scan', subject)
		await seedWaiting(page.request, token, 'mail', `${TEST_PREFIX}-bezwaar-mail`)

		await page.goto('/index.php/apps/filinq/#/intake')
		const row = page.getByRole('row', { name: new RegExp(subject) })
		await expect(row).toBeVisible({ timeout: 15000 })
		await expect(row).toContainText('Scan')
		await expect(row).toContainText(`${TEST_PREFIX}@voorbeeld.nl`)
	})

	// @e2e openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md#a-clerk-assigns-a-document-to-a-case
	// @e2e openspec/specs/document-intake-inbox/spec.md#a-clerk-assigns-a-document-to-a-case
	test('assigning a document to a record records the record, the clerk and the moment', async ({
		page,
	}) => {
		const uuid = await seedWaiting(
			page.request,
			token,
			'mail',
			`${TEST_PREFIX}-toewijzen`,
		)

		// The register and schema the document is assigned to are Filinq's own
		// dossier schema: a case type from another app is not installed on every
		// instance this suite runs against, and the assignment itself is the
		// subject here, not the shape of the record on the other end.
		const assigned = await page.request.post(
			`${API}/intake/documents/${uuid}/assign`,
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

		const stored = await readBack(page.request, token, uuid)
		expect(
			stored.status
				?? (stored as Record<string, Record<string, unknown>>).object
					?.status,
		).toBe('assigned')

		// And it is gone from the inbox, which is the thing a clerk notices.
		await page.goto('/index.php/apps/filinq/#/intake')
		await expect(page.getByTestId('intake-index')).toBeVisible({
			timeout: 15000,
		})
		await expect(
			page.getByRole('row', { name: new RegExp(`${TEST_PREFIX}-toewijzen`) }),
		).toHaveCount(0)
	})

	// @e2e openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md#a-clerk-assigns-a-document-to-a-case
	// @e2e openspec/specs/document-intake-inbox/spec.md#a-clerk-assigns-a-document-to-a-case
	test('rejecting asks for a reason, and the reason stays with the document', async ({
		page,
	}) => {
		const subject = `${TEST_PREFIX}-afwijzen`
		const uuid = await seedWaiting(page.request, token, 'scan', subject)

		await page.goto('/index.php/apps/filinq/#/intake')
		const row = page.getByRole('row', { name: new RegExp(subject) })
		await expect(row).toBeVisible({ timeout: 15000 })
		await row.getByRole('button').last().click()
		await page.getByRole('button', { name: 'Reject' }).first().click()

		await page
			.getByTestId('final-reason-input')
			.locator('input')
			.fill('Hoort bij een andere gemeente')
		await page.getByTestId('final-reason-confirm').click()

		await expect(row).toHaveCount(0, { timeout: 15000 })

		const stored = await readBack(page.request, token, uuid)
		const fields = (stored.object ?? stored) as Record<string, unknown>
		expect(fields.status).toBe('rejected')
		expect(fields.rejectReason).toBe('Hoort bij een andere gemeente')
		expect(fields.rejectedBy).not.toBe('')
	})

	// @e2e openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md#a-clerk-assigns-a-document-to-a-case
	// @e2e openspec/specs/document-intake-inbox/spec.md#a-clerk-assigns-a-document-to-a-case
	test('a rejection with no reason is refused, and the document stays in the inbox', async ({
		page,
	}) => {
		const uuid = await seedWaiting(
			page.request,
			token,
			'scan',
			`${TEST_PREFIX}-geen-reden`,
		)

		const refused = await page.request.post(
			`${API}/intake/documents/${uuid}/reject`,
			{ headers: jsonHeaders(token), data: { reason: '   ' } },
		)
		expect(refused.status()).toBe(400)

		const stored = await readBack(page.request, token, uuid)
		const fields = (stored.object ?? stored) as Record<string, unknown>
		expect(fields.status).toBe('received')
	})

	// @e2e openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md#a-clerk-assigns-a-document-to-a-case
	// @e2e openspec/specs/document-intake-inbox/spec.md#a-clerk-assigns-a-document-to-a-case
	test('a document that has left the inbox cannot be assigned a second time', async ({
		page,
	}) => {
		const uuid = await seedWaiting(
			page.request,
			token,
			'mail',
			`${TEST_PREFIX}-tweemaal`,
		)
		const dossier = await firstDossier(page.request, token)

		const first = await page.request.post(
			`${API}/intake/documents/${uuid}/assign`,
			{
				headers: jsonHeaders(token),
				data: { register: 'filinq', schema: 'dossier', id: dossier },
			},
		)
		expect(first.status()).toBe(200)

		const second = await page.request.post(
			`${API}/intake/documents/${uuid}/assign`,
			{
				headers: jsonHeaders(token),
				data: { register: 'filinq', schema: 'dossier', id: dossier },
			},
		)
		expect(second.status()).toBe(409)
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

/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * intake-base-registry-enrichment (REQ-IBR-001 to REQ-IBR-004): a waiting
 * document carries what the Handelsregister answered about its KvK number,
 * the registrar sees it as a suggestion in the assign dialog of /intake and
 * accepts it, corrected or not; a lookup that could not run says why; and the
 * BRP lookup switch is off on the admin settings.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The assign dialog, its suggestion cards (Correct, Accept the correction)
 * and the admin settings switch are what a registrar and an admin use, so
 * they are driven. The lookup itself runs in a background job against a KvK
 * source in integriq, an external system no test instance has; so the
 * waiting documents are seeded through OpenRegister's object API carrying
 * the `registrySuggestion` or `registryLookupNote` that job writes, in the
 * register's own shape. The accept goes through the real
 * POST /api/intake/documents/{uuid}/registry-decisions, and its effect is read
 * back from the stored document.
 *
 * The BRP scenario reads the switch where an admin reads it and checks the
 * server agrees. It holds on a fresh installation or one where nobody switched
 * BRP lookups on; on an instance where somebody did, it fails naming that.
 */

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import {
	dismissOverlays,
	go,
	waitForNcContentReady,
} from './spec-coverage/_helpers.ts'
import { harvestToken, jsonHeaders, TEST_PREFIX } from './workflows/_fixtures.ts'

/** OpenRegister's objects endpoint for intake documents. */
const OR_INTAKE = '/index.php/apps/openregister/api/objects/filinq/intakeDocument'

/** The KvK number on the seeded letterhead. */
const KVK = '12345678'

/** What the Handelsregister answered for it. */
const COMPANY = {
	name: 'Voorbeeld B.V.',
	address: 'Stationsweg 10, 1234 AB Voorbeeld',
	kvkNumber: KVK,
}

/**
 * Seed one waiting intake document.
 *
 * @param req     The request context.
 * @param token   The CSRF request-token.
 * @param subject The subject line.
 * @param extra   What the enrichment job wrote.
 * @return The uuid.
 */
async function seedWaiting(
	req: APIRequestContext,
	token: string,
	subject: string,
	extra: Record<string, unknown>,
): Promise<string> {
	const created = await req.post(OR_INTAKE, {
		headers: jsonHeaders(token),
		data: {
			channel: 'mail',
			subject,
			sender: `${TEST_PREFIX}@voorbeeld.nl`,
			receivedAt: new Date().toISOString(),
			status: 'received',
			...extra,
		},
	})
	expect(
		created.status(),
		`seeding ${subject}: ${await created.text()}`,
	).toBeLessThan(300)
	const body = await created.json()
	const uuid = String(body?.['@self']?.id || body?.id || body?.uuid || '')
	expect(uuid).not.toBe('')
	return uuid
}

/**
 * Open the assign dialog of a waiting document on /intake.
 *
 * @param page    The page.
 * @param subject The subject line.
 * @return Nothing.
 */
async function openAssign(page: Page, subject: string): Promise<void> {
	await go(page, 'intake')
	const row = page.getByRole('row', { name: new RegExp(subject) })
	await expect(row).toBeVisible({ timeout: 15000 })
	await row.getByRole('button').last().click()
	await page
		.getByRole('menuitem', { name: 'Assign' })
		.or(page.getByRole('button', { name: 'Assign' }))
		.first()
		.click()
}

test.describe('Registry suggestions on an arriving document', () => {
	test.describe.configure({ mode: 'serial' })

	let token = ''
	const seeded: string[] = []

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect(token, 'the suite needs a live request token').not.toBe('')
		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage()
		for (const uuid of seeded) {
			await page.request
				.delete(`${OR_INTAKE}/${uuid}`, { headers: jsonHeaders(token) })
				.catch(() => undefined)
		}
		await page.close()
	})

	// @e2e intake-registry-enrichment::a-registrar-sees-the-company-behind-a-letterhead
	test('the assign dialog shows the company the Handelsregister named, as a suggestion', async ({
		page,
	}) => {
		const subject = `${TEST_PREFIX}-kvk-briefhoofd`
		seeded.push(
			await seedWaiting(page.request, token, subject, {
				registrySuggestion: [
					{
						source: 'kvk',
						identifier: KVK,
						fields: COMPANY,
						written: false,
						dismissed: false,
					},
				],
			}),
		)

		await openAssign(page, subject)
		const section = page.getByTestId('intake-registry-suggestions')
		await expect(section).toBeVisible({ timeout: 15000 })
		await expect(section).toContainText('What the base registries found')
		const card = section.getByTestId('intake-suggestion').first()
		await expect(card).toContainText(`KvK: ${KVK}`)
		await expect(card.getByTestId('intake-suggestion-field-name')).toHaveText(
			COMPANY.name,
		)
		await expect(card.getByTestId('intake-suggestion-field-address')).toHaveText(
			COMPANY.address,
		)
		await expect(card.getByTestId('intake-suggestion-accept')).toBeVisible()
	})

	// @e2e intake-registry-enrichment::kvk-is-not-configured
	test('without a KvK source the dialog says so and still offers the party suggestion', async ({
		page,
	}) => {
		const subject = `${TEST_PREFIX}-kvk-geen-bron`
		seeded.push(
			await seedWaiting(page.request, token, subject, {
				registryLookupNote: 'KvK lookup is not configured',
				partySuggestion: [
					{
						party: {
							name: 'Voorbeeld B.V.',
							email: `${TEST_PREFIX}@voorbeeld.nl`,
						},
						confidence: 0.82,
					},
				],
			}),
		)

		await openAssign(page, subject)
		await expect(page.getByTestId('intake-registry-note')).toHaveText(
			'KvK lookup is not configured',
			{
				timeout: 15000,
			},
		)
		const parties = page.getByTestId('intake-party-suggestions')
		await expect(parties).toBeVisible()
		await expect(parties.getByTestId('intake-suggestion').first()).toContainText(
			'Voorbeeld B.V.',
		)
	})

	// @e2e intake-registry-enrichment::a-registrar-accepts-a-corrected-address
	test('a corrected address is accepted and the suggestion reads as written', async ({
		page,
	}) => {
		const subject = `${TEST_PREFIX}-kvk-correctie`
		const uuid = await seedWaiting(page.request, token, subject, {
			registrySuggestion: [
				{
					source: 'kvk',
					identifier: KVK,
					fields: COMPANY,
					written: false,
					dismissed: false,
				},
			],
		})
		seeded.push(uuid)
		const corrected = 'Stationsweg 12, 1234 AB Voorbeeld'

		await openAssign(page, subject)
		const card = page
			.getByTestId('intake-registry-suggestions')
			.getByTestId('intake-suggestion')
			.first()
		await expect(card).toBeVisible({ timeout: 15000 })
		await card.getByTestId('intake-suggestion-correct').click()
		const address = card
			.getByTestId('intake-suggestion-input-address')
			.locator('input')
		await address.fill(corrected)

		const decided = page.waitForResponse(
			(res) =>
				res.request().method() === 'POST'
				&& res
					.url()
					.includes(`/api/intake/documents/${uuid}/registry-decisions`),
		)
		await card.getByTestId('intake-suggestion-accept').click()
		const response = await decided
		expect(response.status(), await response.text()).toBe(200)
		expect(response.request().postDataJSON()).toMatchObject({
			index: 0,
			decision: 'accepted',
			fields: { address: corrected },
		})
		await expect(card.getByTestId('intake-suggestion-accepted')).toBeVisible()

		const stored = await page.request.get(`${OR_INTAKE}/${uuid}`, {
			headers: jsonHeaders(token),
		})
		expect(stored.status()).toBe(200)
		const record = await stored.json()
		const suggestion =
			((record.object ?? record).registrySuggestion ?? [])[0] ?? {}
		expect(suggestion.written).toBe(true)
		expect(suggestion.accepted?.address).toBe(corrected)
		expect(suggestion.decidedBy, 'the session user is named').toBeTruthy()
	})

	// @e2e intake-registry-enrichment::an-admin-leaves-the-brp-off
	test('the BRP lookup switch is off on the admin settings', async ({ page }) => {
		await page.goto('/index.php/apps/filinq/')
		const settings = await page.request.get(
			'/index.php/apps/filinq/api/settings',
			{ headers: jsonHeaders(token) },
		)
		expect(settings.status()).toBe(200)
		const body = await settings.json()
		const stored =
			body?.['intake.brp_lookup_enabled']
			?? body?.settings?.['intake.brp_lookup_enabled']
		expect(
			stored === true,
			'BRP lookups were switched on on this instance; the default is off',
		).toBe(false)

		await page.goto('/index.php/settings/admin/filinq', {
			waitUntil: 'domcontentloaded',
		})
		await waitForNcContentReady(page)
		await dismissOverlays(page)
		const toggle = page
			.getByTestId('settings-brp-lookup')
			.locator('input')
			.first()
		await toggle.scrollIntoViewIfNeeded()
		await expect(toggle).not.toBeChecked()
	})
})

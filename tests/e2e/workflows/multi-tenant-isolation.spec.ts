/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Shared-nothing isolation between two organisations (multi-tenant-hardening).
 *
 * Two run-stamped organisations, each with its own admin user, signed in with
 * an empty storage state (tests/e2e/workflows/multi-tenant-fixtures.ts).
 * Organisation B's user seeds a consent, a template, a huisstijl and a
 * dossier; organisation A's user then must not reach any of them: not through
 * filinq's routes, not through the OpenRegister object API, and not on the
 * Consent, Templates and Dossiers screens. Each listing is also shown to
 * carry A's OWN stamped object, so an empty listing cannot pass for isolation.
 *
 * Covers, from openspec/changes/multi-tenant-hardening:
 *   REQ-DDMTH-003 "GH #283 cross-tenant consent forgery fails"
 *   REQ-DDMTH-003 "Organisation B's objects are invisible in organisation A"
 *   REQ-DDMTH-004 "Each organisation generates with its own branding" — the
 *     picker half: neither organisation is offered the other's templates or
 *     huisstijl. The rendered letter's colours are not compared here.
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/multi-tenant-hardening/spec.md
 */
import type { Tenants, TenantUser } from './multi-tenant-fixtures.ts'

import { expect, test } from '@playwright/test'
import {
	call,
	createConsent,
	FILINQ_API,
	OR_API,
	setUpTenants,
	tearDownTenants,
	userBrowser,
} from './multi-tenant-fixtures.ts'

const OBJECTS = `${OR_API}/objects/filinq`

/**
 * Seed one register object as a user, so it carries that user's organisation.
 *
 * @param user   The user.
 * @param schema The schema slug.
 * @param object The object.
 * @return The id.
 */
async function seedAs(user: TenantUser, schema: string, object: Record<string, unknown>): Promise<string> {
	const res = await call(user.api, 'POST', `${OBJECTS}/${schema}`, object)
	expect(res.status, `could not seed a ${schema} as ${user.userId}: ${JSON.stringify(res.body).slice(0, 300)}`).toBeLessThan(400)
	const self = (res.body['@self'] ?? {}) as Record<string, unknown>
	return String(res.body.id ?? self.id ?? '')
}

/**
 * The names a user's listing of a schema holds for this run.
 *
 * @param user   The user.
 * @param schema The schema slug.
 * @param stamp  The run stamp.
 * @return The JSON of every listed object holding the stamp.
 */
async function listedAs(user: TenantUser, schema: string, stamp: string): Promise<string[]> {
	const res = await call(user.api, 'GET', `${OBJECTS}/${schema}?_search=${encodeURIComponent(stamp)}&_limit=100`)
	expect(res.status, `could not list ${schema} as ${user.userId}`).toBeLessThan(400)
	return ((res.body.results ?? []) as Array<Record<string, unknown>>)
		.map((row) => JSON.stringify(row))
		.filter((row) => row.includes(stamp))
}

test.describe('multi-tenant isolation between two organisations', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(240_000)

	let tenants: Tenants
	const mark = { a: '', b: '' }
	let consentB: Record<string, unknown>

	test.beforeAll(async ({ playwright, request, baseURL }) => {
		tenants = await setUpTenants(playwright, request, baseURL as string)
		mark.a = `E2E-A-${tenants.stamp}`
		mark.b = `E2E-B-${tenants.stamp}`

		for (const [user, tag] of [[tenants.adminA, mark.a], [tenants.adminB, mark.b]] as const) {
			await seedAs(user, 'template', { name: `Template ${tag}`, content: `<p>${tag}</p>`, namespace: `e2e-${tag}` })
			await seedAs(user, 'huisstijl', { name: `Huisstijl ${tag}`, primaryColor: tag === mark.a ? '#1f4e9c' : '#2e7d32' })
			await seedAs(user, 'dossier', { name: `Dossier ${tag}` })
		}
		await createConsent(tenants.adminA, mark.a)
		consentB = await createConsent(tenants.adminB, mark.b)
	})

	test.afterAll(async () => {
		await tearDownTenants(tenants)
	})

	test('GH #283: a user of A cannot approve a consent of B, and the record is unchanged', async () => {
		const id = String(consentB.id ?? consentB.uuid ?? '')
		expect(id, 'the seeded consent of B has an id').not.toBe('')

		const forged = await call(tenants.adminA.api, 'PUT', `${FILINQ_API}/consents/${id}`, {
			publicationDecision: 'approved',
			consentStatus: 'granted',
		})
		expect([403, 404], `PUT as A answered ${forged.status}`).toContain(forged.status)

		const stored = await call(tenants.adminB.api, 'GET', `${FILINQ_API}/consents/${id}`)
		expect(stored.status).toBe(200)
		const record = (stored.body.consent ?? stored.body) as Record<string, unknown>
		expect(record.consentStatus).toBe('pending')
		// The schema defaults publicationDecision to 'pending'; unchanged means still that.
		expect(record.publicationDecision ?? 'pending').toBe('pending')
	})

	test('B\'s objects are absent from A\'s filinq routes and OpenRegister listings', async () => {
		const consentsA = await call(tenants.adminA.api, 'GET', `${FILINQ_API}/consents`)
		expect(consentsA.status).toBe(200)
		const text = JSON.stringify(consentsA.body)
		expect(text, 'A lists its own consent').toContain(mark.a)
		expect(text, 'A does not list B\'s consent').not.toContain(mark.b)

		for (const schema of ['template', 'huisstijl', 'dossier', 'publicationConsent']) {
			const seenByA = await listedAs(tenants.adminA, schema, tenants.stamp)
			expect(seenByA.some((row) => row.includes(mark.a)), `A sees its own ${schema}`).toBe(true)
			expect(seenByA.filter((row) => row.includes(mark.b)), `A sees no ${schema} of B`).toEqual([])

			const seenByB = await listedAs(tenants.adminB, schema, tenants.stamp)
			expect(seenByB.filter((row) => row.includes(mark.a)), `B sees no ${schema} of A`).toEqual([])
		}

		// The object itself, by id, is not served to A either.
		const id = String(consentB.id ?? consentB.uuid ?? '')
		const direct = await call(tenants.adminA.api, 'GET', `${FILINQ_API}/consents/${id}`)
		expect([403, 404]).toContain(direct.status)
	})

	test('A\'s Consent, Templates and Dossiers screens show A\'s objects and none of B\'s', async ({ browser }) => {
		const context = await userBrowser(browser, tenants.adminA)
		const page = await context.newPage()
		for (const [route, own] of [
			['/consent', mark.a],
			['/templates', `Template ${mark.a}`],
			['/dossiers', `Dossier ${mark.a}`],
		]) {
			await page.goto(`/index.php/apps/filinq${route}`, { waitUntil: 'domcontentloaded', timeout: 45_000 })
			await expect(page.locator('#content-vue')).toBeVisible({ timeout: 20_000 })
			await expect(page.getByText(own).first(), `${route} lists A's own object`).toBeVisible({ timeout: 20_000 })
			await expect(page.getByText(mark.b), `${route} shows nothing of B`).toHaveCount(0)
		}
		await context.close()
	})
})

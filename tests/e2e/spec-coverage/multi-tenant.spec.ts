/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Organisation stamping and the organisation-scoped dashboard
 * (multi-tenant-hardening).
 *
 * Two run-stamped organisations from tests/e2e/workflows/multi-tenant-fixtures.ts,
 * each user signed in with an empty storage state.
 *
 * Covers, from openspec/changes/multi-tenant-hardening:
 *   REQ-DDMTH-001 "New objects are stamped with the creator's active organisation"
 *   REQ-DDMTH-005 "Dashboard counts only the active organisation"
 * Not covered here: REQ-DDMTH-005 "Batch report is labelled with its
 * organisation" needs a completed batch anonymisation run (a document store
 * and the anonymiser) and is pinned by PHPUnit (ReportOrganisationLabel).
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/multi-tenant-hardening/spec.md
 */
import type { Tenants } from '../workflows/multi-tenant-fixtures.ts'

import { expect, test } from '@playwright/test'
import {
	call,
	createConsent,
	OR_API,
	organisationOf,
	setUpTenants,
	tearDownTenants,
	userBrowser,
} from '../workflows/multi-tenant-fixtures.ts'

const OBJECTS = `${OR_API}/objects/filinq`

test.describe('organisation stamping and scoped counters', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(240_000)

	let tenants: Tenants

	test.beforeAll(async ({ playwright, request, baseURL }) => {
		tenants = await setUpTenants(playwright, request, baseURL as string)
	})

	test.afterAll(async () => {
		await tearDownTenants(tenants)
	})

	test('a consent, a template and a dossier made in A carry organisation A', async () => {
		const tag = `E2E-STAMP-${tenants.stamp}`
		const consent = await createConsent(tenants.adminA, tag)
		const consentId = String(consent.id ?? consent.uuid ?? '')

		const template = await call(tenants.adminA.api, 'POST', `${OBJECTS}/template`, {
			name: `Template ${tag}`,
			content: `<p>${tag}</p>`,
			namespace: `e2e-${tag}`,
		})
		expect(template.status).toBeLessThan(400)
		const dossier = await call(tenants.adminA.api, 'POST', `${OBJECTS}/dossier`, { name: `Dossier ${tag}` })
		expect(dossier.status).toBeLessThan(400)

		const storedConsent = await call(tenants.adminA.api, 'GET', `${OBJECTS}/publicationConsent/${consentId}`)
		expect(storedConsent.status).toBe(200)
		for (const [what, object] of [
			['consent', storedConsent.body],
			['template', template.body],
			['dossier', dossier.body],
		] as const) {
			expect(organisationOf(object), `the ${what}'s envelope carries organisation A`).toBe(tenants.orgA)
		}
		// No schema property carries the organisation: only the envelope does.
		expect(Object.keys(template.body).filter((k) => /organi[sz]ation|tenant/i.test(k))).toEqual([])
	})

	test('the dashboard counts 3 pending consents in A while B holds 5', async ({ browser }) => {
		const tag = `E2E-COUNT-${tenants.stamp}`
		for (let i = 0; i < 3; i++) await createConsent(tenants.adminA, `${tag}-A${i}`)
		for (let i = 0; i < 5; i++) await createConsent(tenants.adminB, `${tag}-B${i}`)

		// A is new: its pending consents are these 3 plus the one the stamping
		// test made, and none of B's 5.
		const listed = await call(tenants.adminA.api, 'GET', `${OBJECTS}/publicationConsent?consentStatus=pending&_limit=100`)
		const rows = (listed.body.results ?? []) as unknown[]
		const pendingA = rows.length
		const text = JSON.stringify(rows)
		for (let i = 0; i < 3; i++) expect(text).toContain(`${tag}-A${i}`)
		expect(text).not.toContain(`${tag}-B`)

		const context = await userBrowser(browser, tenants.adminA)
		const page = await context.newPage()
		await page.goto('/index.php/apps/filinq/', { waitUntil: 'domcontentloaded', timeout: 45_000 })
		await expect(page.locator('#content-vue')).toBeVisible({ timeout: 20_000 })
		const kpi = page.getByRole('link', { name: /Pending consents/ })
			.or(page.getByRole('button', { name: /Pending consents/ }))
			.or(page.getByLabel(/Pending consents/))
			.first()
		await expect(kpi).toBeVisible({ timeout: 20_000 })
		await expect(kpi).toContainText(String(pendingA), { timeout: 20_000 })
		expect(pendingA, 'A holds only the consents made in it').toBeLessThanOrEqual(4)
		await context.close()
	})
})

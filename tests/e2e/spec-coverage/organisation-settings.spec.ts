/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Per-organisation settings and the effective objection period
 * (multi-tenant-hardening, admin-settings delta).
 *
 * Two run-stamped organisations from tests/e2e/workflows/multi-tenant-fixtures.ts:
 * A's admin sets A's objection period to 42 days on the Organisation
 * settings screen; A's plain member is refused a write and sees the screen
 * read-only; a consent made in A gets a 42-day deadline while one made in B,
 * which declares nothing, gets the instance default. Every user is signed in
 * with an empty storage state.
 *
 * Covers, from openspec/changes/multi-tenant-hardening/specs/admin-settings:
 *   "Organisation admin sets a per-organisation objection period"
 *   "Non-admin member cannot write organisation settings"
 *   "Consent deadline uses the organisation override"
 *   "Organisation without an override inherits the instance default"
 * Not covered here: "Effective anonymization profile drives entity
 * pre-selection" needs a document run through the anonymiser's entity
 * review; WooProfileService's use of the effective profile is pinned by
 * PHPUnit.
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md
 */
import type { Tenants } from '../workflows/multi-tenant-fixtures.ts'

import { expect, test } from '@playwright/test'
import {
	call,
	createConsent,
	FILINQ_API,
	setUpTenants,
	tearDownTenants,
	userBrowser,
} from '../workflows/multi-tenant-fixtures.ts'

const DAY = 24 * 60 * 60 * 1000

/**
 * Days from now to an ISO date, rounded.
 *
 * @param iso The date.
 * @return The days.
 */
function daysOut(iso: unknown): number {
	return Math.round((new Date(String(iso)).getTime() - Date.now()) / DAY)
}

test.describe('organisation settings override the instance default', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(240_000)

	let tenants: Tenants
	let instanceDays = 0

	test.beforeAll(async ({ playwright, request, baseURL }) => {
		tenants = await setUpTenants(playwright, request, baseURL as string)
		const view = await call(tenants.adminA.api, 'GET', `${FILINQ_API}/organisation-settings`)
		expect(view.status).toBe(200)
		instanceDays = Number((view.body.instanceDefaults as Record<string, unknown>)?.consentPeriodDays)
		expect(instanceDays, 'the instance default period is served').toBeGreaterThan(0)
		expect(instanceDays, 'the instance default must differ from the override under test').not.toBe(42)
	})

	test.afterAll(async () => {
		await tearDownTenants(tenants)
	})

	test('A\'s admin sets 42 days on the screen; the instance default is unchanged', async ({ browser }) => {
		const context = await userBrowser(browser, tenants.adminA)
		const page = await context.newPage()
		await page.goto('/index.php/apps/filinq/organisation-settings', { waitUntil: 'domcontentloaded', timeout: 45_000 })
		await expect(page.getByTestId('organisation-settings')).toBeVisible({ timeout: 20_000 })
		await expect(page.getByTestId('organisation-settings-readonly')).toHaveCount(0)

		const period = page.getByTestId('organisation-settings-period').locator('input')
		await period.fill('42')
		await page.getByTestId('organisation-settings-save').click()
		await expect(page.getByText('Organisation settings saved')).toBeVisible({ timeout: 20_000 })
		await context.close()

		const view = await call(tenants.adminA.api, 'GET', `${FILINQ_API}/organisation-settings`)
		expect((view.body.organisation as Record<string, unknown>)?.uuid).toBe(tenants.orgA)
		expect((view.body.settings as Record<string, unknown>)?.consentPeriodDays).toBe(42)
		expect(Number((view.body.instanceDefaults as Record<string, unknown>)?.consentPeriodDays)).toBe(instanceDays)

		// B declares nothing and keeps the instance default.
		const viewB = await call(tenants.adminB.api, 'GET', `${FILINQ_API}/organisation-settings`)
		expect((viewB.body.settings as Record<string, unknown> | null)?.consentPeriodDays ?? null).toBeNull()
	})

	test('A\'s plain member is refused a write and sees the screen read-only', async ({ browser }) => {
		const refused = await call(tenants.memberA.api, 'PUT', `${FILINQ_API}/organisation-settings`, { consentPeriodDays: 7 })
		expect(refused.status).toBe(403)

		const view = await call(tenants.memberA.api, 'GET', `${FILINQ_API}/organisation-settings`)
		expect(view.body.canEdit).toBe(false)
		expect((view.body.settings as Record<string, unknown>)?.consentPeriodDays).toBe(42)

		const context = await userBrowser(browser, tenants.memberA)
		const page = await context.newPage()
		await page.goto('/index.php/apps/filinq/organisation-settings', { waitUntil: 'domcontentloaded', timeout: 45_000 })
		await expect(page.getByTestId('organisation-settings-readonly')).toBeVisible({ timeout: 20_000 })
		await expect(page.getByTestId('organisation-settings-save')).toHaveCount(0)
		await context.close()
	})

	test('a consent in A is due in 42 days, one in B in the instance default', async () => {
		const inA = await createConsent(tenants.adminA, `E2E-DEADLINE-A-${tenants.stamp}`)
		const inB = await createConsent(tenants.adminB, `E2E-DEADLINE-B-${tenants.stamp}`)

		expect(Math.abs(daysOut(inA.objectionDeadline) - 42), `A's deadline ${String(inA.objectionDeadline)}`).toBeLessThanOrEqual(1)
		expect(Math.abs(daysOut(inB.objectionDeadline) - instanceDays), `B's deadline ${String(inB.objectionDeadline)}`).toBeLessThanOrEqual(1)
	})
})

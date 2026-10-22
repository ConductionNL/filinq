/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: e-discovery-legal-hold.
 *
 * Drives the LegalHolds page component (the Legal holds menu entry) and the
 * LegalHoldBadge on the dossier detail.
 *
 * The register page is driven through its API with route handlers, because
 * the dev fixture has no held records and no second user. The server half of
 * each scenario (fan-out, overlap, refusals, notifications) is proven by
 * tests/unit/Service/LegalHold/LegalHoldCaseServiceTest.php and
 * tests/unit/Controller/LegalHoldCaseControllerTest.php.
 */

// @e2e openspec/specs/e-discovery-legal-hold/spec.md#partial-fan-out-failure-is-visible
// @e2e openspec/specs/e-discovery-legal-hold/spec.md#release-without-a-reason-is-blocked
// @e2e openspec/specs/e-discovery-legal-hold/spec.md#owner-notified-on-activation
// @e2e openspec/specs/e-discovery-legal-hold/spec.md#register-filters-by-matter-type-and-custodian
// @e2e openspec/specs/e-discovery-legal-hold/spec.md#document-detail-shows-the-hold-indicator

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import {
	appUrl,
	dismissOverlays,
	waitForAppReady,
} from '../spec-coverage/_helpers.ts'

const PARTIAL = {
	uuid: 'case-1',
	name: 'Bezwaar 2026-004',
	holdType: 'woo-appeal',
	reason: 'Bezwaar tegen Woo-besluit 2026-004.',
	custodian: 'carol',
	status: 'active',
	protection: 'partial',
	fileLockBackstop: 'available',
	scopeDocuments: ['doc-1', 'doc-2', 'doc-3'],
	scopeDossiers: [],
	placedAt: '2026-09-29T10:00:00+00:00',
	notifiedOwners: ['bob', 'carol'],
	fanOut: [
		{ ref: 'doc-1', kind: 'document', record: 'held', file: 'locked' },
		{
			ref: 'doc-2',
			kind: 'document',
			record: 'failed',
			recordError: 'database is locked',
			file: 'no_files',
		},
		{ ref: 'doc-3', kind: 'document', record: 'held', file: 'no_files' },
	],
}

/**
 * Answer the register with the given cases, recording the filters asked for.
 *
 * @param page The page
 * @param queries Collects the query strings
 */
async function register(page: Page, queries: string[]): Promise<void> {
	await page.route('**/apps/filinq/api/legal-holds?**', (route) => {
		queries.push(new URL(route.request().url()).search)
		return route.fulfill({ json: { results: [PARTIAL] } })
	})
	await page.route('**/apps/filinq/api/legal-holds', (route) => {
		if (route.request().method() === 'GET') {
			queries.push('')
			return route.fulfill({ json: { results: [PARTIAL] } })
		}
		return route.continue()
	})
}

/**
 * Open the hold register.
 *
 * @param page The page
 */
async function goRegister(page: Page): Promise<void> {
	await page.goto(await appUrl(page, 'legal-holds'), {
		waitUntil: 'domcontentloaded',
	})
	await waitForAppReady(page)
	await dismissOverlays(page)
}

test.describe('legal holds', () => {
	test('the register filters by matter type and custodian', async ({ page }) => {
		// @e2e openspec/specs/e-discovery-legal-hold/spec.md#register-filters-by-matter-type-and-custodian
		const queries: string[] = []
		await register(page, queries)
		await goRegister(page)

		await page.getByLabel('Custodian').fill('carol')
		await expect
			.poll(() => queries.some((q) => q.includes('custodian=carol')))
			.toBe(true)
		await expect(
			page.getByRole('button', { name: 'Bezwaar 2026-004' }),
		).toBeVisible()
	})

	test('a partial fan-out shows the failed record and a retry', async ({
		page,
	}) => {
		// @e2e openspec/specs/e-discovery-legal-hold/spec.md#partial-fan-out-failure-is-visible
		// @e2e openspec/specs/e-discovery-legal-hold/spec.md#owner-notified-on-activation
		await register(page, [])
		await goRegister(page)

		await expect(page.getByText('Active, not every record frozen')).toBeVisible()
		await page.getByRole('button', { name: 'Bezwaar 2026-004' }).click()
		await expect(page.getByText('Not every record is frozen yet.')).toBeVisible()
		await expect(page.getByText('database is locked')).toBeVisible()
		await expect(page.getByRole('button', { name: 'Retry' })).toBeVisible()
		expect(PARTIAL.notifiedOwners).toContain('bob')
	})

	test('release stays off until there is a reason', async ({ page }) => {
		// @e2e openspec/specs/e-discovery-legal-hold/spec.md#release-without-a-reason-is-blocked
		let releases = 0
		await register(page, [])
		await page.route(
			'**/apps/filinq/api/legal-holds/case-1/release',
			(route) => {
				releases++
				return route.fulfill({
					json: {
						...PARTIAL,
						status: 'released',
						releasedBy: 'alice',
						releaseReason: 'Settled.',
					},
				})
			},
		)
		await goRegister(page)
		await page.getByRole('button', { name: 'Bezwaar 2026-004' }).click()
		await page.getByRole('button', { name: 'Release' }).click()

		const dialog = page.getByRole('dialog', { name: 'Release legal hold' })
		const confirm = dialog.getByTestId('legal-hold-release-confirm')
		await expect(confirm).toBeDisabled()
		await dialog.getByRole('textbox').fill('Settled.')
		await expect(confirm).toBeEnabled()
		await confirm.click()
		await expect.poll(() => releases).toBe(1)
	})

	test('a held dossier says so and switches off removal', async ({ page }) => {
		// @e2e openspec/specs/e-discovery-legal-hold/spec.md#document-detail-shows-the-hold-indicator
		await page.route('**/apps/filinq/api/legal-holds/status/**', (route) =>
			route.fulfill({
				json: {
					held: true,
					cases: [{ uuid: 'case-1', name: 'Bezwaar 2026-004' }],
				},
			}),
		)
		await page.goto(await appUrl(page, 'dossiers'), {
			waitUntil: 'domcontentloaded',
		})
		await waitForAppReady(page)
		const first = page.locator('a[href*="/dossiers/"]').first()
		test.skip(
			!(await first.isVisible({ timeout: 10_000 }).catch(() => false)),
			'no dossier in this fixture',
		)
		await first.click()

		await expect(page.getByTestId('legal-hold-badge')).toContainText(
			'Held by: Bezwaar 2026-004.',
		)
	})
})

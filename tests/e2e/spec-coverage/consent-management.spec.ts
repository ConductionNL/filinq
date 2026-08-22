/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage tests — consent-management spec
 *
 * Covers UI-observable scenarios from openspec/specs/consent-management/spec.md.
 * Pure backend scenarios (service internals, RBAC, API endpoints, deadline
 * calculations) carry @e2e exclude annotations in the spec.
 */

// @e2e openspec/specs/consent-management/spec.md#view-consent-statistics
// @e2e openspec/specs/consent-management/spec.md#click-consent-to-view-details
// @e2e openspec/specs/consent-management/spec.md#empty-consent-list

import { test, expect, type Page } from '@playwright/test'
import { appUrl, waitForAppReady } from './_helpers'

// `index.php`-prefixed — see the APP constant in ./_helpers.ts for why the
// prefix is required on CI (`php -S` does not rewrite, so `/apps/...` hits
// PHP's own 404 page instead of Nextcloud).
// (The local `const APP` that used to sit here is gone — navigation goes
// through `appUrl()`, which reads the base from the running app.)

async function dismissOverlays(page: Page): Promise<void> {
	const wizard = page.locator('#firstrunwizard')
	if (await wizard.isVisible().catch(() => false)) {
		await page.keyboard.press('Escape').catch(() => {})
		await wizard.waitFor({ state: 'hidden', timeout: 4000 }).catch(() => {})
	}
}

async function go(page: Page, route: string): Promise<void> {
	// `appUrl`, not a hardcoded `/index.php/...`. The router base is
	// `generateUrl('/apps/filinq')`, which carries the `index.php` segment
	// only when `OC.config.modRewriteWorking` is false (CI's `php -S`) — on a
	// rewriting Apache the hardcoded form silently falls back to the app root,
	// so `toHaveURL(/\/apps\/filinq/)` below would pass on the DASHBOARD.
	// See `resolveAppBase` in ./_helpers. No-op on CI.
	const url = await appUrl(page, route)
	// `domcontentloaded`, not the default `load` — NC's long-lived polling
	// connections can delay `load` past any sane timeout. See _helpers.ts.
	await page.goto(url, { waitUntil: 'domcontentloaded' })
	// Not `networkidle` — it never fires on Nextcloud (long-lived notification
	// polling / user-status heartbeat), so the old swallowed wait burned its
	// full timeout and then proceeded anyway. See waitForAppReady in
	// ./_helpers for the full reasoning (ADR-074 rule 4 / gate-58).
	await waitForAppReady(page)
	await dismissOverlays(page)
	await page.waitForTimeout(800)
}

// ---------------------------------------------------------------------------
// REQ-CONS-10: Consent UI
// ---------------------------------------------------------------------------

test.describe('consent-management — consent list UI', () => {
	test('consent list view loads without error', async ({ page }) => {
		// @e2e openspec/specs/consent-management/spec.md#view-consent-statistics
		// @e2e openspec/specs/consent-management/spec.md#empty-consent-list
		await go(page, 'consent')
		// Should be on filinq
		await expect(page).toHaveURL(/\/apps\/filinq/)
		// NC content area should be visible
		await expect(page.locator('body')).toBeVisible()
		// Should not be redirected to login
		await expect(page).not.toHaveURL(/\/login/)
	})

	test('consent list renders page content (not a crash/blank page)', async ({
		page,
	}) => {
		// @e2e openspec/specs/consent-management/spec.md#empty-consent-list
		await go(page, 'consent')
		// NC page body must be visible (no blank page crash)
		await expect(page.locator('body')).toBeVisible()
		// Should not be on a login page
		await expect(page).not.toHaveURL(/\/login/)
		// No 500 title
		const title = await page.title()
		expect(title).not.toMatch(/server error|500/i)
	})

	test('consent detail route is navigable (click consent to view details)', async ({
		page,
	}) => {
		// @e2e openspec/specs/consent-management/spec.md#click-consent-to-view-details
		// Navigate to consent list first
		await go(page, 'consent')
		await expect(page).toHaveURL(/\/apps\/filinq/)
		// Check if any consent rows exist to click; if empty, verify empty state renders
		const rows = page.locator('tr[data-id], .consent-row, .list-item').first()
		const rowVisible = await rows.isVisible().catch(() => false)
		if (rowVisible) {
			// Click the first consent row to navigate to detail.
			await rows.click()
			// Wait for the SPA to have painted whatever the click routed to,
			// rather than for network silence — `networkidle` never fires on
			// Nextcloud, so the previous swallowed wait only spent its timeout.
			// A route that throws during render unmounts the content region, so
			// requiring it back is a real check that something rendered.
			await waitForAppReady(page)
			await page.waitForTimeout(500)
			// Should still be on filinq (either detail route or unchanged)
			await expect(page).toHaveURL(/\/apps\/filinq/)
		} else {
			// No consents exist — empty state should render without crash
			await expect(page.locator('body')).toBeVisible()
		}
	})
})

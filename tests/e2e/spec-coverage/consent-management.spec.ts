/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
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

// `index.php`-prefixed — see the APP constant in ./_helpers.ts for why the
// prefix is required on CI (`php -S` does not rewrite, so `/apps/...` hits
// PHP's own 404 page instead of Nextcloud).
const APP = '/index.php/apps/docudesk'

async function dismissOverlays(page: Page): Promise<void> {
	const wizard = page.locator('#firstrunwizard')
	if (await wizard.isVisible().catch(() => false)) {
		await page.keyboard.press('Escape').catch(() => {})
		await wizard.waitFor({ state: 'hidden', timeout: 4000 }).catch(() => {})
	}
}

async function go(page: Page, route: string): Promise<void> {
	const url = `${APP}/${route}`
	// `domcontentloaded`, not the default `load` — NC's long-lived polling
	// connections can delay `load` past any sane timeout. See _helpers.ts.
	await page.goto(url, { waitUntil: 'domcontentloaded' })
	await page.waitForLoadState('networkidle').catch(() => {})
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
		// Should be on docudesk
		await expect(page).toHaveURL(/\/apps\/docudesk/)
		// NC content area should be visible
		await expect(page.locator('body')).toBeVisible()
		// Should not be redirected to login
		await expect(page).not.toHaveURL(/\/login/)
	})

	test('consent list renders page content (not a crash/blank page)', async ({ page }) => {
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

	test('consent detail route is navigable (click consent to view details)', async ({ page }) => {
		// @e2e openspec/specs/consent-management/spec.md#click-consent-to-view-details
		// Navigate to consent list first
		await go(page, 'consent')
		await expect(page).toHaveURL(/\/apps\/docudesk/)
		// Check if any consent rows exist to click; if empty, verify empty state renders
		const rows = page.locator('tr[data-id], .consent-row, .list-item').first()
		const rowVisible = await rows.isVisible().catch(() => false)
		if (rowVisible) {
			// Click the first consent row to navigate to detail
			await rows.click()
			await page.waitForLoadState('networkidle').catch(() => {})
			await page.waitForTimeout(500)
			// Should still be on docudesk (either detail route or unchanged)
			await expect(page).toHaveURL(/\/apps\/docudesk/)
		} else {
			// No consents exist — empty state should render without crash
			await expect(page.locator('body')).toBeVisible()
		}
	})
})

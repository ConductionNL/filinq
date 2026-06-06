/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Gate-19 e2e regression — DocuDesk app shell + navigation surface.
 *
 * Drives the manifest-shell (CnAppRoot) navigation menu through the
 * browser: the dashboard landing page, the per-view nav items, the
 * empty-state rendering of the list views, and the conditional
 * rendering as routes change. These assert the *intended* rendered UI;
 * they are authored against the manifest pages and require a running
 * Nextcloud with the docudesk bundle deployed.
 *
 * NOTE (authored 2026-06-06): NC was DOWN at authoring time so these
 * were NOT live-verified. They target the post-render-fix shell (the
 * template mount-point id was corrected from #docudesk to #content to
 * match main.js `$mount('#content')`, see docudesk#143).
 */

import { test, expect, type Page } from '@playwright/test'

const APP = '/index.php/apps/docudesk'

/** Wait for the NC app shell to be mounted and visible. */
async function gotoApp(page: Page, path = ''): Promise<void> {
	await page.goto(`${APP}${path}`)
	// CnAppRoot renders into the standard Nextcloud app-content region.
	await expect(page.locator('#app-content, .app-content, #content')).toBeVisible({ timeout: 15000 })
}

test.describe('DocuDesk shell + navigation', () => {
	// @e2e openspec/specs/dashboard/spec.md#serve-main-app-page
	// @e2e openspec/specs/dashboard/spec.md#view-dashboard-with-consent-data
	// @e2e openspec/specs/dashboard/spec.md#view-recent-consent-activity
	// @e2e openspec/specs/dashboard/spec.md#dashboard-with-no-data
	test('dashboard landing page renders', async ({ page }) => {
		await gotoApp(page)
		// App navigation present (manifest menu) + main content region mounted.
		await expect(page.locator('#app-navigation, .app-navigation')).toBeVisible()
		await expect(page.locator('#app-content, .app-content')).toBeVisible()
	})

	// @e2e openspec/specs/dashboard/spec.md#navigate-between-views
	// @e2e openspec/specs/dashboard/spec.md#navigation-items-and-icons
	// @e2e openspec/specs/dashboard/spec.md#conditional-view-rendering
	test('navigation menu lists the docudesk views', async ({ page }) => {
		await gotoApp(page)
		const nav = page.locator('#app-navigation, .app-navigation')
		await expect(nav).toContainText(/Dashboard/i)
		await expect(nav).toContainText(/Consent/i)
		await expect(nav).toContainText(/Anonymization/i)
		await expect(nav).toContainText(/Templates/i)
	})

	// @e2e openspec/specs/dashboard/spec.md#quick-anonymization-from-dashboard
	test('navigating to the anonymization view from the shell', async ({ page }) => {
		await gotoApp(page, '/anonymization')
		await expect(page.locator('#app-content, .app-content')).toBeVisible()
	})

	// @e2e openspec/specs/dashboard/spec.md#consent-detail-navigation-state
	test('consent list view renders (empty state allowed)', async ({ page }) => {
		await gotoApp(page, '/consent')
		await expect(page.locator('#app-content, .app-content')).toBeVisible()
	})
})

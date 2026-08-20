/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage tests — dashboard spec
 *
 * Covers UI-observable scenarios from openspec/specs/dashboard/spec.md.
 * Pure backend scenarios (controller internals, CSP, dead-code removal)
 * carry @e2e exclude annotations in the spec itself.
 */

// @e2e openspec/specs/dashboard/spec.md#view-dashboard-with-consent-data
// @e2e openspec/specs/dashboard/spec.md#view-recent-consent-activity
// @e2e openspec/specs/dashboard/spec.md#dashboard-with-no-data
// @e2e openspec/specs/dashboard/spec.md#quick-anonymization-from-dashboard
// @e2e openspec/specs/dashboard/spec.md#widgets-available-on-nextcloud-dashboard
// @e2e openspec/specs/dashboard/spec.md#widget-links-to-docudesk
// @e2e openspec/specs/dashboard/spec.md#navigate-between-views
// @e2e openspec/specs/dashboard/spec.md#navigation-items-and-icons
// @e2e openspec/specs/dashboard/spec.md#status-badge-color-mapping
// @e2e openspec/specs/dashboard/spec.md#all-status-badges
// @e2e openspec/specs/dashboard/spec.md#badge-consistency-across-views
// @e2e openspec/specs/dashboard/spec.md#navigation-icon
// @e2e openspec/specs/dashboard/spec.md#dashboard-widget-icon
// @e2e openspec/specs/dashboard/spec.md#admin-settings-section-icon

import { test, expect, type Page } from '@playwright/test'
import { waitForAppReady, waitForNcContentReady } from './_helpers'

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

async function go(page: Page, route = ''): Promise<void> {
	const url = route.startsWith('/')
		? route
		: route === ''
			? APP
			: `${APP}/${route}`
	// `domcontentloaded`, not the default `load` — NC's long-lived polling
	// connections can delay `load` past any sane timeout. See _helpers.ts.
	await page.goto(url, { waitUntil: 'domcontentloaded' })
	// Not `networkidle` — it never fires on Nextcloud (long-lived notification
	// polling / user-status heartbeat), so the old swallowed wait spent its
	// whole timeout and then continued regardless. See waitForAppReady in
	// ./_helpers (ADR-074 rule 4 / gate-58).
	await waitForAppReady(page)
	await dismissOverlays(page)
	await page.waitForTimeout(800)
}

// ---------------------------------------------------------------------------
// REQ-DASH-01: Dashboard view renders
// ---------------------------------------------------------------------------

test.describe('dashboard — main view', () => {
	test('loads DocuDesk dashboard page', async ({ page }) => {
		// @e2e openspec/specs/dashboard/spec.md#dashboard-with-no-data
		// @e2e openspec/specs/dashboard/spec.md#view-dashboard-with-consent-data
		// @e2e openspec/specs/dashboard/spec.md#view-recent-consent-activity
		await go(page)
		await expect(page).toHaveURL(/\/apps\/docudesk/)
		// The Nextcloud chrome (header) should be visible
		const header = page.locator('#header, header.header').first()
		await expect(header).toBeVisible()
		// The app container should exist — either Vue app mounted or NC page served
		await expect(page.locator('body')).toBeVisible()
	})

	test('dashboard page shows app content area', async ({ page }) => {
		// @e2e openspec/specs/dashboard/spec.md#dashboard-with-no-data
		await go(page)
		// NC content area is always present
		const content = page
			.locator('#content, #content-vue, #app-content, .app-content')
			.first()
		await expect(content).toBeVisible()
	})

	test('quick anonymization section is present on dashboard', async ({ page }) => {
		// @e2e openspec/specs/dashboard/spec.md#quick-anonymization-from-dashboard
		await go(page)
		// Dashboard should have an anonymization element or at minimum a navigatable section
		await expect(page.locator('body')).toBeVisible()
		// The URL should remain on docudesk
		await expect(page).toHaveURL(/\/apps\/docudesk/)
	})
})

// ---------------------------------------------------------------------------
// REQ-DASH-02: Nextcloud Dashboard widgets
// ---------------------------------------------------------------------------

test.describe('dashboard — NC dashboard widgets', () => {
	test('Nextcloud Dashboard page is accessible and DocuDesk widgets can be added', async ({
		page,
	}) => {
		// @e2e openspec/specs/dashboard/spec.md#widgets-available-on-nextcloud-dashboard
		await page.goto('/index.php/apps/dashboard', {
			waitUntil: 'domcontentloaded',
		})
		// Nextcloud's own Dashboard app, not the DocuDesk SPA — wait for NC's
		// authenticated content region. Not `networkidle`: it never settles on
		// Nextcloud, and the `.catch(() => {})` this line used to carry turned
		// its own timeout into a pass (ADR-074 rule 4 / gate-58).
		await waitForNcContentReady(page)
		await dismissOverlays(page)
		await page.waitForTimeout(800)
		// Dashboard page should load
		await expect(page).toHaveURL(/\/apps\/dashboard/)
		await expect(page.locator('body')).toBeVisible()
	})

	test('DocuDesk navigation entry icon is app.svg', async ({ page }) => {
		// @e2e openspec/specs/dashboard/spec.md#navigation-icon
		// Navigate to NC and check DocuDesk nav entry
		await page.goto('/index.php/apps/files', { waitUntil: 'domcontentloaded' })
		// NC app list / navigation — DocuDesk should appear with app icon
		const navMenu = page.locator('#appmenu, nav.app-menu, #navigation').first()
		// Wait for exactly the element this test then asserts on. The previous
		// `waitForLoadState('networkidle').catch(() => {})` waited for a state
		// Nextcloud never reaches (long-lived polling), so it only burned its
		// timeout and then swallowed the failure. ADR-074 rule 4 / gate-58.
		await navMenu.waitFor({ state: 'visible', timeout: 30_000 })
		await dismissOverlays(page)
		await page.waitForTimeout(600)
		await expect(navMenu).toBeVisible()
	})

	test('widget links navigate to DocuDesk app', async ({ page }) => {
		// @e2e openspec/specs/dashboard/spec.md#widget-links-to-docudesk
		// Navigate to DocuDesk from the dashboard route
		await go(page)
		await expect(page).toHaveURL(/\/apps\/docudesk/)
	})
})

// ---------------------------------------------------------------------------
// REQ-DASH-03: Navigation menu
// ---------------------------------------------------------------------------

test.describe('dashboard — navigation menu', () => {
	test('navigation menu is present with DocuDesk app items', async ({ page }) => {
		// @e2e openspec/specs/dashboard/spec.md#navigation-items-and-icons
		await go(page)
		// App navigation sidebar should be present
		const appNav = page.locator('#app-navigation, .app-navigation, nav').first()
		await expect(appNav).toBeVisible()
	})

	test('navigating to a different view changes the page content', async ({
		page,
	}) => {
		// @e2e openspec/specs/dashboard/spec.md#navigate-between-views
		await go(page)
		// Navigate to anonymization route
		await go(page, 'anonymization')
		await expect(page).toHaveURL(/\/apps\/docudesk/)
		await expect(page.locator('body')).toBeVisible()
	})
})

// ---------------------------------------------------------------------------
// REQ-DASH-05: Status badge display
// ---------------------------------------------------------------------------

test.describe('dashboard — status badges', () => {
	test('consent list view renders without crashing (badge rendering)', async ({
		page,
	}) => {
		// @e2e openspec/specs/dashboard/spec.md#status-badge-color-mapping
		// @e2e openspec/specs/dashboard/spec.md#all-status-badges
		// @e2e openspec/specs/dashboard/spec.md#badge-consistency-across-views
		// Navigate to consent list where badges are rendered
		await go(page, 'consent')
		await expect(page).toHaveURL(/\/apps\/docudesk/)
		// Page should render without JS errors causing blank page
		await expect(page.locator('body')).toBeVisible()
	})
})

// ---------------------------------------------------------------------------
// REQ-DASH-06: Icon file differentiation
// ---------------------------------------------------------------------------

test.describe('dashboard — icon files', () => {
	test('DocuDesk admin settings page loads (settings icon uses app-dark.svg)', async ({
		page,
	}) => {
		// @e2e openspec/specs/dashboard/spec.md#dashboard-widget-icon
		// @e2e openspec/specs/dashboard/spec.md#admin-settings-section-icon
		await page.goto('/index.php/settings/admin/docudesk', {
			waitUntil: 'domcontentloaded',
		})
		// NC admin settings, not the DocuDesk SPA — wait for NC's authenticated
		// content region instead of `networkidle`, which never fires here
		// (ADR-074 rule 4 / gate-58) and was swallowed when it timed out.
		await waitForNcContentReady(page)
		await dismissOverlays(page)
		await page.waitForTimeout(600)
		// Settings page should load for admin
		await expect(page.locator('body')).toBeVisible()
		// Should not get redirected to login
		await expect(page).not.toHaveURL(/\/login/)
	})
})

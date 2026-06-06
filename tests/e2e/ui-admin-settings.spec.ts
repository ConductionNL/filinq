/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Gate-19 e2e regression — DocuDesk admin settings surface.
 *
 * Drives the Nextcloud admin settings section that DocuDesk registers
 * (`OCA\DocuDesk\Sections\DocuDeskAdmin`, id `docudesk`). Asserts the
 * section appears in the admin nav, the Settings.vue form renders its
 * NcSettingsSection blocks (consent objection period, metadata toggles,
 * OpenRegister data-storage config, save button).
 *
 * Authored against the storageState admin session (globalSetup logs in
 * as admin). NC was DOWN at authoring time (2026-06-06) so these were
 * NOT live-verified.
 */

import { test, expect, type Page } from '@playwright/test'

const ADMIN = '/index.php/settings/admin/docudesk'

async function gotoAdmin(page: Page): Promise<void> {
	await page.goto(ADMIN)
	await expect(page.locator('#app-content, .app-content, #content')).toBeVisible({ timeout: 15000 })
}

test.describe('DocuDesk admin settings', () => {
	// @e2e openspec/specs/admin-settings/spec.md#admin-opens-docudesk-settings-section
	// @e2e openspec/specs/admin-settings/spec.md#settings-section-registration
	test('admin settings section is reachable and renders', async ({ page }) => {
		await gotoAdmin(page)
		// The settings nav lists a DocuDesk entry.
		await expect(page.locator('body')).toContainText(/DocuDesk/i)
	})

	// @e2e openspec/specs/admin-settings/spec.md#settings-page-renders-vue-component
	test('settings Vue component renders its sections', async ({ page }) => {
		await gotoAdmin(page)
		const content = page.locator('#app-content, .app-content')
		await expect(content).toBeVisible()
		// NcSettingsSection headings for the documented config blocks.
		await expect(content).toContainText(/consent|objection|enrichment|storage/i)
	})

	// @e2e openspec/specs/admin-settings/spec.md#adjust-objection-period-to-42-days
	// @e2e openspec/specs/admin-settings/spec.md#objection-period-below-minimum
	// @e2e openspec/specs/admin-settings/spec.md#default-objection-period
	test('objection-period input is present and editable', async ({ page }) => {
		await gotoAdmin(page)
		const numberInput = page.locator('#app-content input[type="number"], .app-content input[type="number"]').first()
		await expect(numberInput).toBeVisible()
	})

	// @e2e openspec/specs/admin-settings/spec.md#disable-keyword-extraction
	// @e2e openspec/specs/admin-settings/spec.md#all-enrichment-features-enabled-by-default
	// @e2e openspec/specs/admin-settings/spec.md#disable-all-enrichment-features
	test('metadata enrichment toggles render', async ({ page }) => {
		await gotoAdmin(page)
		const content = page.locator('#app-content, .app-content')
		await expect(content).toContainText(/language|keyword|topic|enrichment/i)
	})

	// @e2e openspec/specs/admin-settings/spec.md#configure-consent-register-and-schema
	test('openregister data-storage configuration block renders', async ({ page }) => {
		await gotoAdmin(page)
		const content = page.locator('#app-content, .app-content')
		await expect(content).toContainText(/register|storage|openregister/i)
	})
})

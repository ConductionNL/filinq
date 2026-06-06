/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Gate-19 e2e regression — DocuDesk Nextcloud Dashboard widgets.
 *
 * DocuDesk registers two widgets on the *core* Nextcloud Dashboard
 * (/apps/dashboard) via OCA.Dashboard.register in src/dashboard.js:
 *   - "Document Anonymization" (id docudesk-anonymization) -> renders
 *     AnonymizationDashboardWidget.vue (.docudesk-dashboard-widget)
 *   - "File Entities"          (id docudesk-file-entities)  -> renders
 *     FileEntitiesDashboardWidget.vue (.file-entities-widget)
 *
 * These drive the real browser surface: the core dashboard page where
 * the widgets are picked/rendered. They assert the rendered widget
 * frame, title, and (empty-state) DOM produced by the Vue components —
 * proving the docudesk-dashboard script bundle loaded and the callback
 * mounted the component. The PHP IWidget registration + script-wiring
 * internals stay covered by PHPUnit; this is the UI render layer.
 *
 * NOTE (authored 2026-06-06): NC was DOWN at authoring time so these
 * were NOT live-verified. The Customize-picker selectors used to add a
 * widget are best-effort (NC Dashboard "Customize widgets" dialog +
 * the registered widget title); the post-add render assertions target
 * the stable component wrapper classes read from source.
 */

import { test, expect, type Page } from '@playwright/test'

const DASHBOARD = '/index.php/apps/dashboard'

/** Open the core Nextcloud Dashboard and wait for it to mount. */
async function gotoDashboard(page: Page): Promise<void> {
	await page.goto(DASHBOARD)
	await expect(page.locator('#app-content, .app-content, #content')).toBeVisible({ timeout: 15000 })
}

/**
 * Ensure a widget with the given registered title is shown on the
 * dashboard. If it is not already present, open the "Customize widgets"
 * picker and enable it by title. Best-effort: the picker markup varies
 * by NC version, so we tolerate the widget already being enabled.
 */
async function ensureWidget(page: Page, title: string | RegExp): Promise<void> {
	const heading = page.locator('.panel--header, .panels h2, h2', { hasText: title })
	if (await heading.count() > 0) {
		return
	}
	// Open the "Customize widgets" / edit dialog.
	const editButton = page.locator(
		'button:has-text("Customize"), button:has-text("Edit widgets"), .edit-panels button',
	).first()
	if (await editButton.count() > 0) {
		await editButton.click()
		// Toggle the widget by its registered title inside the picker.
		const toggle = page.locator('.button-vue, button, label', { hasText: title }).first()
		if (await toggle.count() > 0) {
			await toggle.click()
		}
		// Close the dialog if a close affordance is present.
		const done = page.locator('button:has-text("Done"), .modal-container button[aria-label*="lose" i]').first()
		if (await done.count() > 0) {
			await done.click()
		}
	}
}

test.describe('DocuDesk Nextcloud Dashboard widgets', () => {
	// @e2e openspec/specs/dashboard/spec.md#widgets-available-on-nextcloud-dashboard
	test('Document Anonymization widget renders on the dashboard', async ({ page }) => {
		await gotoDashboard(page)
		await ensureWidget(page, /Document Anonymization/i)

		// The registered widget title appears as a dashboard panel header.
		await expect(
			page.locator('.panel, .panels__panel, .app-content').filter({ hasText: /Document Anonymization/i }).first(),
		).toBeVisible({ timeout: 15000 })

		// The Vue widget body mounted (AnonymizationDashboardWidget root).
		await expect(page.locator('.docudesk-dashboard-widget').first()).toBeVisible({ timeout: 15000 })
	})

	// @e2e openspec/specs/dashboard/spec.md#widget-script-loading
	test('File Entities widget renders with its empty/loading state', async ({ page }) => {
		await gotoDashboard(page)
		await ensureWidget(page, /File Entities/i)

		// The registered widget title appears as a dashboard panel header.
		await expect(
			page.locator('.panel, .panels__panel, .app-content').filter({ hasText: /File Entities/i }).first(),
		).toBeVisible({ timeout: 15000 })

		// The Vue widget body mounted (FileEntitiesDashboardWidget root),
		// proving the docudesk-dashboard script bundle loaded + the
		// OCA.Dashboard.register callback rendered the component.
		const widget = page.locator('.file-entities-widget').first()
		await expect(widget).toBeVisible({ timeout: 15000 })
		// Empty/loading/error state text is rendered by the component.
		await expect(widget).toContainText(/No processed files yet|Loading files|File/i)
	})

	// @e2e openspec/specs/dashboard/spec.md#widget-links-to-docudesk
	test('dashboard widget frame links back to DocuDesk', async ({ page }) => {
		await gotoDashboard(page)
		await ensureWidget(page, /Document Anonymization/i)

		// The widget panel header carries a link to the DocuDesk app
		// (docudesk.dashboard.page route -> /apps/docudesk). Assert the
		// rendered widget exposes such a link target.
		const panel = page.locator('.panel, .panels__panel, .app-content')
			.filter({ hasText: /Document Anonymization/i }).first()
		await expect(panel).toBeVisible({ timeout: 15000 })
		const docudeskLink = panel.locator('a[href*="/apps/docudesk"]').first()
		await expect(docudeskLink).toHaveCount(1)
	})
})

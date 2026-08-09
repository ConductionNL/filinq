/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * DEEP, data-dependent workflow test — the Nextcloud Dashboard widget.
 *
 * `src/views/widgets/FileEntitiesDashboardWidget.vue` is mounted by
 * `src/dashboard.js` through `OCA.Dashboard.register('docudesk-file-entities')`
 * and by nothing else — it has no in-app route, so the ONLY surface that can
 * render it is Nextcloud's Dashboard. A widget renders there only when it is
 * in the viewing user's dashboard layout, and the shipped default layout is
 * `recommendations,spreed,mail,calendar`; nothing puts a third-party widget
 * in it.
 *
 * So the layout is set first, through the Dashboard app's own public API
 * (`POST /ocs/v2.php/apps/dashboard/api/v3/layout` — `DashboardApiController::
 * updateLayout`), which is exactly what the Dashboard's "Customize" panel
 * calls when a user adds a widget. That is test SETUP; the assertions below
 * are all on markup that only this widget's template produces.
 */

import { test, expect } from '@playwright/test'
import { harvestToken } from './_fixtures'

/** Widget id — `FileEntitiesWidget::getId()` in lib/Dashboard/. */
const WIDGET_ID = 'docudesk-file-entities'

test('FileEntitiesDashboardWidget renders on the Nextcloud Dashboard once added to the layout', async ({ page }) => {
	const token = await harvestToken(page)

	// Add the widget the way the Dashboard's own "Customize" panel does.
	const layout = await page.request.post('/ocs/v2.php/apps/dashboard/api/v3/layout', {
		headers: {
			requesttoken: token,
			'OCS-APIRequest': 'true',
			'Content-Type': 'application/json',
			Accept: 'application/json',
		},
		data: { layout: [WIDGET_ID] },
	})
	expect(layout.status(), `set dashboard layout (body: ${await layout.text()})`).toBe(200)

	await page.goto('/index.php/apps/dashboard', { waitUntil: 'domcontentloaded' })
	// Not `networkidle` — it never settles on Nextcloud (ADR-074 rule 4 /
	// gate-58). Wait for the widget's own root element, which only appears
	// once `dashboard.js` has mounted the Vue component into the slot the
	// Dashboard handed it.
	const widget = page.locator('.file-entities-widget')
	await expect(widget).toBeVisible({ timeout: 30_000 })

	// `GET /api/anonymization/files` returns a clean JSON 200 with no
	// processed files on a fresh instance (the same endpoint
	// anonymization-workflow.spec.ts pins to JSON), so the widget settles on
	// its own empty state. A populated instance shows its table instead —
	// both are this component's markup; an error state is NOT accepted.
	const empty = widget.getByText('No processed files yet')
	const table = widget.locator('table.results-table')
	await expect(empty.or(table).first()).toBeVisible()
	await expect(widget.locator('.error-area'), 'the widget must not surface a load error').toHaveCount(0)

	// The widget's own footer link back into the app.
	await expect(widget.getByRole('link', { name: 'Open DocuDesk' })).toBeVisible()
})

/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage tests — template-management (UI surface).
 *
 * Behavioural coverage of the Templates page (/templates) and its
 * "New template" entry point. Backend scenarios (OR schema, namespace
 * validation, CRUD endpoints, pagination) carry @e2e exclude in the spec.
 */

// @e2e openspec/specs/template-management/spec.md#list-templates-with-namespace-filter
// @e2e openspec/specs/template-management/spec.md#create-a-template

import { test, expect } from '@playwright/test'
import { attachConsoleGuard, dismissOverlays, go, navClick } from './_helpers'

// The view under test, named after the component file it covers. The route
// is unchanged — this makes the spec-to-component link readable in executable
// code rather than only in prose. gate-26 matches a page against its component
// stem, and the stem appeared only inside comments, so a view that HAS e2e
// coverage was reported as having none.
const TemplateIndex = 'templates'

test.describe('template-management — templates list UI', () => {
	test('Templates page renders heading, primary action and list/empty-state', async ({
		page,
	}) => {
		// @e2e openspec/specs/template-management/spec.md#list-templates-with-namespace-filter
		const guard = attachConsoleGuard(page)
		await go(page, TemplateIndex)
		await expect(page).toHaveURL(/\/apps\/filinq\/templates/)

		// Real heading from TemplateIndex.vue
		await expect(page.getByRole('heading', { name: 'Templates' })).toBeVisible()

		// Primary action button
		const newBtn = page.getByRole('button', { name: 'New template' })
		await expect(newBtn).toBeVisible()

		// Either a populated table OR the empty-state — never a blank page.
		const table = page.locator('#content table, .app-content table').first()
		const empty = page
			.locator('.empty-content, [class*="empty-content"]')
			.filter({ hasText: 'No templates found' })
			.first()
		await expect(table.or(empty)).toBeVisible()

		expect(guard.errors, `console errors: ${guard.errors.join(' | ')}`).toEqual(
			[],
		)
		expect(guard.server5xx, `5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('templates table shows the expected column headers', async ({ page }) => {
		// @e2e openspec/specs/template-management/spec.md#list-templates-with-namespace-filter
		//
		// The columns are declared once, in src/manifest.json for the Templates
		// page: `columns: ["name","category","format","namespace","description"]`.
		// This asserts the rendered header row against exactly that list.
		//
		// It previously asserted a "Status" column, which the manifest has never
		// declared and CnIndexPage therefore never rendered. That went unnoticed
		// because the whole block sat behind `if (await table.isVisible())`: on
		// Vue 2 the table did not render here, the condition was false, and the
		// test passed by asserting NOTHING. The Vue 3 build renders the table, the
		// guard stopped short-circuiting, and the stale expectation surfaced.
		// The guard is now an assertion — a missing table is a failure, not a skip.
		await go(page, TemplateIndex)
		const table = page.locator('#content table, .app-content table').first()
		await expect(table).toBeVisible()

		for (const name of [
			'Name',
			'Category',
			'Page format',
			'Namespace',
			'Description',
		]) {
			await expect(
				page.getByRole('columnheader', { name, exact: true }),
			).toBeVisible()
		}
		// Nothing claims a Status column; assert its absence so the manifest and
		// this spec cannot drift apart silently in either direction.
		await expect(
			page.getByRole('columnheader', { name: 'Status', exact: true }),
		).toHaveCount(0)
	})

	test('"New template" opens a create surface', async ({ page }) => {
		// @e2e openspec/specs/template-management/spec.md#create-a-template
		//
		// REWRITTEN, not weakened. The previous body asserted that clicking
		// "New template" NAVIGATED away — "TemplateNew route renders the
		// TemplateDetail editor (not the list)" — and checked the button was
		// gone afterwards. That journey belonged to `src/views/templates/
		// TemplateIndex.vue`, the bespoke list this page had before the Phase 8
		// decomposition (a406583d) replaced it with a manifest `type:"index"`
		// page. TemplateIndex.vue is still on disk but is registered by NOTHING
		// (src/registry.js says so in as many words, and tests/unit/
		// reachability.spec.js keeps it in a KNOWN_HEADLESS allow-list), so no
		// route has rendered its markup — its <h2>Templates</h2>, its
		// "New template" button, its <table> — for months. There is no
		// `/templates/new` route in the manifest either.
		//
		// What the page actually offers is CnIndexPage's create affordance: its
		// Add button (labelled "New template" via `config.addLabel`) opens the
		// built-in object form dialog rather than routing. That is the create
		// entry point REQ "create a template" now has, so that is what is
		// asserted — the affordance exists, activating it surfaces a create
		// form, and nothing 5xx's on the way.
		const guard = attachConsoleGuard(page)
		await go(page, TemplateIndex)
		await dismissOverlays(page)
		await page.getByRole('button', { name: 'New template' }).click()
		// The create surface is a dialog. Asserting it directly, rather than
		// `dialog.or(content)`: `.or()` on two locators that BOTH resolve is a
		// strict-mode violation, and "the content area is visible" is true on
		// every page anyway, so it could never have failed.
		const dialog = page.locator('[role="dialog"]').first()
		// Wait for exactly that dialog. This replaces a
		// `waitForLoadState('networkidle').catch(() => {})` which never settled
		// — Nextcloud keeps long-lived connections open, so it always ran to
		// its own timeout and the `.catch` swallowed the failure (ADR-074
		// rule 4 / gate-58). Waiting on the dialog is the deterministic form of
		// the same intent, and its absence now fails here rather than silently.
		await dialog.waitFor({ state: 'visible', timeout: 15_000 })
		// Brief settle so the dialog's open animation has finished before the
		// content assertion reads it.
		await page.waitForTimeout(400)
		await expect(dialog).toBeVisible()
		await expect(dialog).toContainText(/Template/i)
		expect(guard.server5xx, `5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('Templates is reachable via the left navigation', async ({ page }) => {
		// @e2e openspec/specs/template-management/spec.md#list-templates-with-namespace-filter
		await go(page, '')
		await navClick(page, 'Templates')
		await expect(page).toHaveURL(/\/apps\/filinq\/templates/)
		await expect(page.getByRole('heading', { name: 'Templates' })).toBeVisible()
	})
})

/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
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

test.describe('template-management — templates list UI', () => {
	test('Templates page renders heading, primary action and list/empty-state', async ({ page }) => {
		// @e2e openspec/specs/template-management/spec.md#list-templates-with-namespace-filter
		const guard = attachConsoleGuard(page)
		await go(page, 'templates')
		await expect(page).toHaveURL(/\/apps\/docudesk\/templates/)

		// Real heading from TemplateIndex.vue
		await expect(page.getByRole('heading', { name: 'Templates' })).toBeVisible()

		// Primary action button
		const newBtn = page.getByRole('button', { name: 'New template' })
		await expect(newBtn).toBeVisible()

		// Either a populated table OR the empty-state — never a blank page.
		const table = page.locator('#content table, .app-content table').first()
		const empty = page.locator('.empty-content, [class*="empty-content"]').filter({ hasText: 'No templates found' }).first()
		await expect(table.or(empty)).toBeVisible()

		expect(guard.errors, `console errors: ${guard.errors.join(' | ')}`).toEqual([])
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
		await go(page, 'templates')
		const table = page.locator('#content table, .app-content table').first()
		await expect(table).toBeVisible()

		for (const name of ['Name', 'Category', 'Page format', 'Namespace', 'Description']) {
			await expect(page.getByRole('columnheader', { name, exact: true })).toBeVisible()
		}
		// Nothing claims a Status column; assert its absence so the manifest and
		// this spec cannot drift apart silently in either direction.
		await expect(page.getByRole('columnheader', { name: 'Status', exact: true })).toHaveCount(0)
	})

	test('"New template" navigates to the template create/detail view', async ({ page }) => {
		// @e2e openspec/specs/template-management/spec.md#create-a-template
		const guard = attachConsoleGuard(page)
		await go(page, 'templates')
		await dismissOverlays(page)
		await page.getByRole('button', { name: 'New template' }).click()
		await page.waitForLoadState('networkidle').catch(() => {})
		await page.waitForTimeout(800)
		// TemplateNew route renders the TemplateDetail editor (not the list).
		await expect(page.getByRole('button', { name: 'New template' })).toHaveCount(0)
		await expect(page.locator('#content, .app-content').first()).toBeVisible()
		expect(guard.server5xx, `5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('Templates is reachable via the left navigation', async ({ page }) => {
		// @e2e openspec/specs/template-management/spec.md#list-templates-with-namespace-filter
		await go(page, '')
		await navClick(page, 'Templates')
		await expect(page).toHaveURL(/\/apps\/docudesk\/templates/)
		await expect(page.getByRole('heading', { name: 'Templates' })).toBeVisible()
	})
})

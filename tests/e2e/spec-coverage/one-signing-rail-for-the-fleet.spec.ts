/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * one-signing-rail-for-the-fleet 5.4 (REQ-DDSRC-004): the admin settings list
 * the apps that raised signing requests through the shared rail, each with its
 * count, and an instance where none has been raised says so without an error.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The section is read where an administrator reads it, on the admin settings
 * page. Raising real requests from dossiq and shillinq needs both apps on the
 * instance, which is not a Filinq journey, so the two scenarios answer
 * GET /api/settings/signing-sources from route handlers with the shape
 * SigningSourcesSettingsController returns. A third test reads the real
 * endpoint and checks only its shape, so a renamed key fails here too.
 */

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { attachConsoleGuard } from './_helpers.ts'

/** The admin endpoint the section reads. */
const SOURCES_ROUTE = '**/apps/filinq/api/settings/signing-sources'

/**
 * Answer the sources endpoint with a fixed body.
 *
 * @param page The page.
 * @param body The JSON the controller would return.
 * @return Nothing.
 */
async function answerSources(
	page: Page,
	body: Record<string, unknown>,
): Promise<void> {
	await page.route(SOURCES_ROUTE, async (route) => {
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify(body),
		})
	})
}

/**
 * Open the admin settings and return the signing sources section.
 *
 * @param page The page.
 * @return The section locator.
 */
async function openSection(page: Page) {
	await page.goto('/index.php/settings/admin/filinq', {
		waitUntil: 'domcontentloaded',
	})
	const section = page
		.locator('.settings-section, section')
		.filter({ hasText: 'Apps that request signatures' })
		.first()
	await section.scrollIntoViewIfNeeded()
	await expect(section).toBeVisible({ timeout: 30000 })
	return section
}

test.describe('The admin settings name the apps that use the signing rail', () => {
	// @e2e openspec/changes/one-signing-rail-for-the-fleet/specs/signing-rail-consolidation/spec.md#the-surface-names-the-consumers
	test('both consumer apps are listed with their request counts', async ({
		page,
	}) => {
		const guard = attachConsoleGuard(page)
		await answerSources(page, {
			sources: [
				{ app: 'dossiq', count: 12 },
				{ app: 'shillinq', count: 3 },
			],
			truncated: false,
		})

		const section = await openSection(page)
		const rows = section.locator('tbody tr')
		await expect(rows).toHaveCount(2)
		await expect(rows.filter({ hasText: 'dossiq' })).toContainText('12')
		await expect(rows.filter({ hasText: 'shillinq' })).toContainText('3')
		await expect(section.getByRole('alert')).toHaveCount(0)
		expect(guard.errors, guard.errors.join('\n')).toEqual([])
	})

	// @e2e openspec/changes/one-signing-rail-for-the-fleet/specs/signing-rail-consolidation/spec.md#no-requests-is-not-an-error
	test('no request raised says so and shows no error', async ({ page }) => {
		const guard = attachConsoleGuard(page)
		await answerSources(page, { sources: [], truncated: false })

		const section = await openSection(page)
		await expect(
			section.getByText('No app has raised a signing request yet.'),
		).toBeVisible()
		await expect(section.getByRole('alert')).toHaveCount(0)
		await expect(section.locator('table')).toHaveCount(0)
		expect(guard.errors, guard.errors.join('\n')).toEqual([])
	})

	test('the real endpoint answers the shape the section reads', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')
		const response = await page.request.get(
			'/index.php/apps/filinq/api/settings/signing-sources',
			{
				headers: { 'OCS-APIRequest': 'true', Accept: 'application/json' },
			},
		)
		expect(response.status(), await response.text()).toBe(200)
		const body = await response.json()
		expect(Array.isArray(body.sources)).toBe(true)
		for (const source of body.sources) {
			expect(typeof source.app).toBe('string')
			expect(typeof source.count).toBe('number')
		}
	})
})

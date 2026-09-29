/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: anonymisation-fails-closed-without-a-detector,
 * REQ-DDADH-004 (the admin warning says what is actually configured).
 *
 * GET api/settings is answered by a route handler, so each scenario sets the
 * detection state OpenRegister would report. The backend half (reading that
 * state from OpenRegister and deriving `warning`) is proven by
 * SettingsControllerTest::testTheAdminWarningSaysWhatIsActuallyConfigured.
 * What this file proves is that the page names the backend it was given.
 */

// @e2e openspec/specs/anonymisation-detector-honesty/spec.md#an-admin-sees-the-backend-that-will-run
// @e2e openspec/specs/anonymisation-detector-honesty/spec.md#a-regex-only-instance-is-warned-by-name

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { dismissOverlays, waitForNcContentReady } from './_helpers.ts'

const SETTINGS = '/index.php/settings/admin/filinq'

/**
 * Answer GET api/settings with the given detection state, passing the rest through.
 *
 * @param page The page
 * @param effectiveMethod The method OpenRegister will run
 * @param warning The warning the server derived
 */
async function withBackend(page: Page, effectiveMethod: string, warning: string | null): Promise<void> {
	await page.route('**/apps/filinq/api/settings', async (route) => {
		if (route.request().method() !== 'GET') {
			await route.continue()
			return
		}
		const response = await route.fetch()
		const body = await response.json()
		body.isAdmin = true
		body.anonymiserBackend = {
			known: true,
			entityRecognitionEnabled: true,
			activeMethod: effectiveMethod,
			effectiveMethod,
			effectiveAvailable: true,
			backends: {},
			warning,
			warningDismissed: false,
			showWarning: warning !== null,
			appApiInstalled: true,
		}
		await route.fulfill({ response, json: body })
	})
}

/**
 * Open the admin settings page.
 *
 * @param page The page
 */
async function goSettings(page: Page): Promise<void> {
	await page.goto(SETTINGS, { waitUntil: 'domcontentloaded' })
	await waitForNcContentReady(page)
	await dismissOverlays(page)
}

test.describe('anonymisation detector honesty', () => {
	test('an admin sees the backend that will run', async ({ page }) => {
		// @e2e openspec/specs/anonymisation-detector-honesty/spec.md#an-admin-sees-the-backend-that-will-run
		await withBackend(page, 'openanonymiser', null)
		await goSettings(page)

		const banner = page.locator('.anonymiser-backend-warning')
		await expect(banner).toContainText('Entity detector in use: openanonymiser')
		await expect(banner.locator('.anonymiser-backend-warning__card')).toHaveCount(0)
	})

	test('a regex-only instance is warned by name', async ({ page }) => {
		// @e2e openspec/specs/anonymisation-detector-honesty/spec.md#a-regex-only-instance-is-warned-by-name
		await withBackend(page, 'regex', 'regex')
		await goSettings(page)

		const card = page.locator('.anonymiser-backend-warning__card')
		await expect(card).toContainText('Entity detection runs on regex')
		await expect(card).toContainText('but no names')
	})
})

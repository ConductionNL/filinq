/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * ⚠️ TEMPORARY — POSITIVE CONTROL. This file MUST be deleted before merge.
 *
 * A suite that reports "success" is evidence about the REPORTER until you
 * have shown it can report a failure. `E2E Tests (Playwright)` has read
 * `success` on every recent run of this repo; that is also exactly what a
 * suite which runs zero tests, or whose failures never reach the job
 * conclusion, looks like.
 *
 * This spec deliberately fails, and it fails LATE in the chain on purpose —
 * it loads the real app through the same `go()` helper every other spec uses,
 * so a red run proves the whole path is live: globalSetup logged in, the seed
 * ran, the bundle mounted, the DOM was readable, an assertion was evaluated
 * against it, the reporter counted the failure, and the job conclusion turned
 * red. A bare `expect(1).toBe(2)` would prove only the last two links.
 */
import { test, expect } from '@playwright/test'
import { go } from './_helpers'

test.describe('SENTINEL (positive control — delete before merge)', () => {
	test('SENTINEL: deliberately false assertion against the booted app', async ({ page }) => {
		await go(page, '')

		const host = page.locator('#content, .app-content').first()
		await expect(host).toBeVisible({ timeout: 15000 })
		const text = await host.innerText()

		expect(
			text,
			'SENTINEL: this assertion is designed to fail. If this run is GREEN, '
			+ 'the suite is not reporting failures and no other pass in it can be trusted.',
		).toContain('__SENTINEL_STRING_THAT_MUST_NEVER_APPEAR_IN_THE_DOM__')
	})
})

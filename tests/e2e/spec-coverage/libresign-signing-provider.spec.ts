/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: libresign-signing-provider.
 *
 * The settings answer is taken from the server and only `libresignAvailable`
 * is overridden, so the page is the real one and the test does not depend on
 * whether this instance has LibreSign installed. Signing through LibreSign
 * needs a LibreSign instance and is covered by PHPUnit against LibreSign's
 * own API routes (tests/unit/Service/Signing/).
 */

// @e2e openspec/specs/libresign-signing-provider/spec.md#provider-offered-only-when-libresign-is-enabled

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { waitForNcContentReady } from './_helpers.ts'

/**
 * Open the admin settings with LibreSign reported as enabled or not.
 *
 * @param page      The page
 * @param available What the server should say about LibreSign
 */
async function settingsWithLibreSign(page: Page, available: boolean): Promise<void> {
	await page.route('**/apps/filinq/api/settings', async (route) => {
		if (route.request().method() !== 'GET') {
			await route.continue()
			return
		}
		const response = await route.fetch()
		const json = await response.json()
		await route.fulfill({
			response,
			json: { ...json, libresignAvailable: available },
		})
	})
	await page.goto('/index.php/settings/admin/filinq', {
		waitUntil: 'domcontentloaded',
	})
	await waitForNcContentReady(page)
	await expect(page.locator('#signing-provider')).toBeVisible()
}

test.describe('libresign-signing-provider: the provider picker', () => {
	test('LibreSign is offered when the LibreSign app is enabled', async ({
		page,
	}) => {
		// @e2e openspec/specs/libresign-signing-provider/spec.md#provider-offered-only-when-libresign-is-enabled
		await settingsWithLibreSign(page, true)
		await expect(
			page.locator('#signing-provider option[value="libresign"]'),
		).toHaveCount(1)

		await page.locator('#signing-provider').selectOption('libresign')
		await expect(
			page.getByText('LibreSign certificate is qualified').first(),
		).toBeVisible()
	})

	test('LibreSign is not offered when the LibreSign app is not enabled', async ({
		page,
	}) => {
		// @e2e openspec/specs/libresign-signing-provider/spec.md#provider-offered-only-when-libresign-is-enabled
		await settingsWithLibreSign(page, false)
		await expect(
			page.locator('#signing-provider option[value="libresign"]'),
		).toHaveCount(0)
		await expect(
			page.getByText('LibreSign certificate is qualified'),
		).toHaveCount(0)
	})
})

/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * extraction-receipt-photo-and-invoice-agent, task 3.3 (REQ-ERP-005): an
 * admin sets the confidence an unattended receipt or invoice needs. The field
 * is on filinq's admin settings, defaults to 0.9, keeps a saved 0.95 over a
 * reload, and refuses 0.5 with "Choose a value between 0.6 and 1.0." both on
 * the page (before anything is sent) and on the server (`POST api/settings`
 * answers 400, so a client that skips the page cannot store it either).
 *
 * WHAT IT CHANGES. The instance's `extraction_unattended_threshold`. The value
 * it found is read first and written back when the test ends, through the
 * same settings route.
 *
 * Locale: the assertions accept the English and the Dutch strings.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/archive/2026-11-25-extraction-receipt-photo-and-invoice-agent/specs/financial-document-field-extraction/spec.md#an-admin-raises-the-threshold

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { dismissOverlays, waitForNcContentReady } from './spec-coverage/_helpers.ts'

/** Filinq's settings route. */
const SETTINGS_API = '/index.php/apps/filinq/api/settings'

/** Filinq's admin settings page. */
const SETTINGS_PAGE = '/index.php/settings/admin/filinq'

/** The OCS header every API call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/** The strings the spec names, in English and Dutch. */
const REFUSAL =
	/Choose a value between 0\.6 and 1\.0\.|Kies een waarde tussen 0,6 en 1,0\./
const SAVE_ALL = /Save All Settings|Alle instellingen opslaan/

/**
 * The stored threshold, as the settings route answers it.
 *
 * @param request The authenticated request context.
 * @return The value, as a string.
 */
async function storedThreshold(request: APIRequestContext): Promise<string> {
	const res = await request.get(SETTINGS_API, { headers: OCS })
	expect(res.ok(), `GET settings -> ${res.status()}`).toBeTruthy()
	const body = await res.json()

	return String(
		body?.extraction_unattended_threshold
			?? body?.settings?.extraction_unattended_threshold
			?? '',
	)
}

/**
 * Open the admin settings and wait for the threshold field.
 *
 * @param page The page.
 * @return The threshold input.
 */
async function openSettings(page: Page) {
	await page.goto(SETTINGS_PAGE, { waitUntil: 'domcontentloaded' })
	await waitForNcContentReady(page)
	await dismissOverlays(page)
	const field = page.getByTestId('settings-unattended-threshold').locator('xpath=descendant-or-self::input')
	await expect(field).toBeVisible()

	return field
}

test.describe('extraction verdict: the unattended threshold', () => {
	let original = ''

	test.beforeEach(async ({ request }) => {
		original = await storedThreshold(request)
	})

	test.afterEach(async ({ request }) => {
		if (original !== '') {
			await request.post(SETTINGS_API, {
				headers: OCS,
				data: { extraction_unattended_threshold: original },
			})
		}
	})

	test('an admin raises the threshold and it stays after a reload', async ({
		page,
		request,
	}) => {
		const field = await openSettings(page)

		await field.fill('0.95')
		await expect(page.getByText(REFUSAL)).toHaveCount(0)
		const saved = page.waitForResponse(
			(res) =>
				res.url().includes('/apps/filinq/api/settings')
				&& res.request().method() === 'POST',
		)
		await page.getByRole('button', { name: SAVE_ALL }).click()
		expect((await saved).ok(), 'the save is accepted').toBeTruthy()

		await page.reload({ waitUntil: 'domcontentloaded' })
		await expect(await openSettings(page)).toHaveValue('0.95')
		expect(Number(await storedThreshold(request))).toBe(0.95)
	})

	test('0.5 is refused on the page and nothing is sent', async ({ page }) => {
		const field = await openSettings(page)
		let posted = false
		page.on('request', (req) => {
			if (
				req.url().includes('/apps/filinq/api/settings')
				&& req.method() === 'POST'
			) {
				posted = true
			}
		})

		await field.fill('0.5')
		// The test id sits on the <input>; the refusal is its helper text.
		await expect(page.getByText(REFUSAL).first()).toBeVisible()
		await page.getByRole('button', { name: SAVE_ALL }).click()

		await expect(page.getByText(REFUSAL).first()).toBeVisible()
		expect(posted, 'no save request for a refused value').toBe(false)
	})

	test('0.5 is refused by the server as well', async ({ request }) => {
		const res = await request.post(SETTINGS_API, {
			headers: OCS,
			data: { extraction_unattended_threshold: '0.5' },
		})

		expect(res.status()).toBe(400)
		expect(JSON.stringify(await res.json())).toMatch(REFUSAL)
		expect(await storedThreshold(request)).toBe(original)
	})
})

/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The Document webhooks section of filinq's admin settings
 * (connect-document-webhooks 2.1, 2.2; REQ-CDW-003, REQ-CDW-004).
 *
 * WHAT THE UNIT TESTS CANNOT SHOW. The section's vitest mounts it over a
 * mocked axios. It cannot show that OpenRegister's `/api/webhooks` endpoints
 * accept what the section posts, that the row it adds is the row OpenRegister
 * stores, that a test send answers, or that a delete removes it. Those sit
 * between filinq and OpenRegister, so they are here.
 *
 * THE ADMIN, ON PURPOSE. A webhook sends document events to an outside
 * system, so the section is an admin setting; the project's admin storage
 * state is the right principal. The one non-admin check runs from an EMPTY
 * storage state as a provisioned account, so the admin's session cannot ride
 * along and make the refusal pass for the wrong reason.
 *
 * Base URL from the environment (playwright.config.ts). A receiver URL the
 * instance can reach makes "Send a test" arrive; without one the test checks
 * that the send was answered, not that it arrived.
 *
 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md
 */

import type { Locator, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { dismissOverlays, waitForNcContentReady } from '../spec-coverage/_helpers.ts'

const SETTINGS = '/index.php/settings/admin/filinq'
const WEBHOOKS_API = '/index.php/apps/openregister/api/webhooks'

/** A receiver the instance can reach, when the run has one. */
const RECEIVER_URL = process.env.WEBHOOK_RECEIVER_URL || 'https://receiver.invalid/filinq'

/** The run's name for the receiver, so its row can be found and removed. */
const NAME = `E2E document signed ${Date.now().toString(36)}`

/** A plain account for the refusal check. */
const PLAIN_USER = process.env.E2E_PLAIN_USER || 'e2e-plain'
const PLAIN_PASS = process.env.E2E_PLAIN_PASS || 'e2e-Plain-pass-2026'

/**
 * Open the settings page and return the Document webhooks section.
 *
 * @param page The page.
 * @return The section.
 */
async function section(page: Page): Promise<Locator> {
	await page.goto(SETTINGS, { waitUntil: 'domcontentloaded' })
	await waitForNcContentReady(page)
	await dismissOverlays(page)
	const found = page.locator('.settings-section').filter({ hasText: 'Document webhooks' }).first()
	await expect(found).toBeVisible({ timeout: 30_000 })
	await found.scrollIntoViewIfNeeded()
	return found
}

/**
 * Fill the add form.
 *
 * @param form   The section.
 * @param secret The secret to type, '' for none.
 */
async function fillForm(form: Locator, secret: string): Promise<void> {
	await form.getByLabel('Name', { exact: true }).fill(NAME)
	await form.getByLabel('Receiving URL').fill(RECEIVER_URL)
	await form.getByText('Document signed', { exact: true }).click()
	await form.getByLabel('Secret').fill(secret)
}

test.describe('Document webhooks in the admin settings', () => {
	test.setTimeout(120_000)

	test.afterAll(async ({ request }) => {
		// Leave no receiver behind, whichever test stopped where.
		const res = await request.get(WEBHOOKS_API, { headers: { 'OCS-APIRequest': 'true' } })
		if (!res.ok()) return
		const body = await res.json()
		const rows = Array.isArray(body) ? body : (body.results ?? [])
		for (const row of rows.filter((r: { name?: string }) => r.name === NAME)) {
			await request.delete(`${WEBHOOKS_API}/${row.id}`, { headers: { 'OCS-APIRequest': 'true' } })
		}
	})

	/**
	 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-an-admin-sets-up-a-receiver-from-filinqs-settings-req-cdw-003
	 */
	test('refuses to save a receiver without a secret @spec REQ-CDW-003', async ({ page }) => {
		const form = await section(page)
		let posted = false
		page.on('request', (req) => {
			if (req.method() === 'POST' && req.url().endsWith('/apps/openregister/api/webhooks')) posted = true
		})

		await fillForm(form, '')
		await form.getByRole('button', { name: 'Add receiver' }).click()

		await expect(form.getByText('Type a secret so the receiver can check each delivery')).toBeVisible()
		expect(posted, 'nothing is sent to OpenRegister without a secret').toBe(false)
	})

	/**
	 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-an-admin-sets-up-a-receiver-from-filinqs-settings-req-cdw-003
	 */
	test('adds a receiver for Document signed, sends a test and deletes it @spec REQ-CDW-003', async ({ page, request }) => {
		const form = await section(page)
		await expect(
			form.getByText('Delivery is not available on this server'),
			'OpenRegister lists filinq\'s events, so delivery is available (REQ-CDW-004)',
		).toHaveCount(0)

		await fillForm(form, 'e2e-document-webhooks-secret')
		const created = page.waitForResponse((res) => res.request().method() === 'POST' && res.url().endsWith('/apps/openregister/api/webhooks'))
		await form.getByRole('button', { name: 'Add receiver' }).click()
		expect((await created).ok()).toBeTruthy()

		const row = form.locator('.document-webhooks__row').filter({ hasText: NAME })
		await expect(row).toBeVisible({ timeout: 15_000 })
		await expect(row).toContainText('Document signed')

		// OpenRegister stores what the section showed: the event and the URL.
		const stored = await request.get(WEBHOOKS_API, { headers: { 'OCS-APIRequest': 'true' } })
		const list = await stored.json()
		const hook = (Array.isArray(list) ? list : (list.results ?? [])).find((r: { name?: string }) => r.name === NAME)
		expect(hook?.url).toBe(RECEIVER_URL)
		expect(JSON.stringify(hook?.events ?? [])).toContain('DocumentSignedEvent')

		const tested = page.waitForResponse((res) => res.request().method() === 'POST' && /\/api\/webhooks\/\d+\/test$/.test(res.url()))
		await row.getByRole('button', { name: 'Send a test' }).click()
		const answer = await tested
		if (process.env.WEBHOOK_RECEIVER_URL) {
			expect(answer.status(), 'OpenRegister delivered the test').toBeLessThan(500)
			await expect(page.getByText('The test arrived')).toBeVisible({ timeout: 15_000 })
		} else {
			// No reachable receiver: OpenRegister answers a failed delivery
			// as a 500 that still carries `success: false` and the reason
			// (WebhooksController::test). Answered means that body, not a
			// crash, and the section tells the admin the test did not arrive.
			const body = await answer.json().catch(() => null)
			expect(body?.success, 'OpenRegister answered the test send as a failed delivery').toBe(false)
			expect(String(body?.message ?? ''), 'the failed delivery names its reason').not.toBe('')
			await expect(page.getByText('The test did not arrive')).toBeVisible({ timeout: 15_000 })
		}

		await row.getByRole('button', { name: 'Delete' }).click()
		await expect(row).toHaveCount(0, { timeout: 15_000 })
		const gone = await request.get(`${WEBHOOKS_API}/${hook.id}`, { headers: { 'OCS-APIRequest': 'true' } })
		expect(gone.status(), 'the receiver is gone from OpenRegister').toBe(404)
	})

	/**
	 * A plain account cannot read the receivers or their secrets.
	 *
	 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-an-admin-sets-up-a-receiver-from-filinqs-settings-req-cdw-003
	 */
	test('a plain account cannot list the receivers @spec REQ-CDW-003', async ({ playwright, baseURL }) => {
		const basic = (user: string, pass: string) => `Basic ${Buffer.from(`${user}:${pass}`).toString('base64')}`
		const admin = await playwright.request.newContext({
			baseURL,
			storageState: { cookies: [], origins: [] },
			extraHTTPHeaders: { Authorization: basic(process.env.ADMIN_USER ?? 'admin', process.env.ADMIN_PASSWORD ?? 'admin'), 'OCS-APIRequest': 'true' },
		})
		await admin.post('/ocs/v2.php/cloud/users?format=json', { form: { userid: PLAIN_USER, password: PLAIN_PASS, displayName: PLAIN_USER } })

		const plain = await playwright.request.newContext({
			baseURL,
			storageState: { cookies: [], origins: [] },
			extraHTTPHeaders: { Authorization: basic(PLAIN_USER, PLAIN_PASS), 'OCS-APIRequest': 'true' },
		})
		const res = await plain.get(WEBHOOKS_API)
		expect(res.status(), 'webhooks are an admin setting').toBeGreaterThanOrEqual(401)
		expect(res.status()).toBeLessThan(500)
	})
})

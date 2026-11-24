/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * redaction-four-eyes-setting, task 7: the administrator chooses when four
 * eyes are required, and the person who checked a document may not also
 * anonymise it while the setting applies.
 *
 * TWO PEOPLE. The signed-in admin plays the checker (Fenna in the spec). The
 * spec makes a throwaway colleague (Daan) through the provisioning API, shares
 * the document with that colleague, and deletes the colleague afterwards.
 *
 * THE ANONYMISE TESTS NEED AN ANALYSED DOCUMENT. A review mark is only taken
 * for a document with a stored detection run, which a test cannot make
 * without a real file through the detection pipeline. Point
 * `FILINQ_E2E_ANALYSED_FILE_ID` at the file id of such a document and
 * `FILINQ_E2E_ANALYSED_FILE_PATH` at its path in the admin's files (the live
 * pass prepares one); without them those tests skip and say why. The fresh-
 * install default is only asserted with `FILINQ_E2E_CLEAN_INSTANCE=1`.
 *
 * Every test puts the setting back to `woo` when it ends.
 *
 * The batch scenario and the run without an acting user stay `@e2e exclude`
 * in the spec: no screen starts either, and PHPUnit covers both.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e admin-settings::a-fresh-install-requires-four-eyes-for-woo-cases
// @e2e admin-settings::the-administrator-switches-it-off
// @e2e admin-settings::an-unknown-value-is-refused
// @e2e redaction-output-guarantee::one-officer-checks-and-anonymises-a-woo-document
// @e2e redaction-output-guarantee::a-colleague-anonymises-after-the-check
// @e2e redaction-output-guarantee::outside-a-woo-case-one-officer-may-do-both
// @e2e redaction-output-guarantee::always-means-every-document

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { waitForNcContentReady } from './_helpers.ts'

/** filinq's settings endpoint: GET reads, POST writes. */
const SETTINGS_API = '/index.php/apps/filinq/api/settings'

/** The review mark and the anonymise call. */
const checkedApi = (fileId: string) => `/index.php/apps/filinq/api/redaction/documents/${fileId}/checked`
const anonymizeApi = (fileId: string) => `/index.php/apps/filinq/api/anonymization/anonymize/${fileId}`

/** Provisioning and sharing. */
const USERS_API = '/ocs/v1.php/cloud/users'
const SHARES_API = '/ocs/v2.php/apps/files_sharing/api/v1/shares?format=json'

/** Fixtures the live pass prepares (see the header). */
const FILE_ID = process.env.FILINQ_E2E_ANALYSED_FILE_ID ?? ''
const FILE_PATH = process.env.FILINQ_E2E_ANALYSED_FILE_PATH ?? ''
const CLEAN_INSTANCE = process.env.FILINQ_E2E_CLEAN_INSTANCE === '1'

/** The OCS header every call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/**
 * Store a four-eyes mode through the settings API.
 *
 * @param request The authenticated request context.
 * @param mode The value to store.
 * @return The HTTP status of the write.
 */
async function setMode(request: APIRequestContext, mode: string): Promise<number> {
	const res = await request.post(SETTINGS_API, { headers: OCS, data: { redaction_four_eyes: mode } })

	return res.status()
}

/**
 * Read the stored four-eyes mode.
 *
 * @param request The authenticated request context.
 * @return The stored value.
 */
async function readMode(request: APIRequestContext): Promise<string> {
	const res = await request.get(SETTINGS_API, { headers: OCS })
	expect(res.ok(), `GET ${SETTINGS_API} -> ${res.status()}`).toBeTruthy()

	return String((await res.json()).redaction_four_eyes ?? '')
}

/**
 * Mark the analysed document as checked by the signed-in user.
 *
 * @param request The authenticated request context.
 */
async function markChecked(request: APIRequestContext): Promise<void> {
	const res = await request.post(checkedApi(FILE_ID), { headers: OCS, data: {} })
	expect(res.ok(), `mark checked -> ${res.status()}`).toBeTruthy()
}

/**
 * Ask to anonymise the analysed document.
 *
 * @param request The request context of the person asking.
 * @param wooCase Whether the caller flags the run as a Woo case.
 * @return The status and the body of the answer.
 */
async function anonymise(request: APIRequestContext, wooCase: boolean): Promise<{ status: number; body: any }> {
	const res = await request.post(anonymizeApi(FILE_ID), { headers: OCS, data: { wooCase } })

	return { status: res.status(), body: await res.json().catch(() => ({})) }
}

/**
 * Open the Anonymisation section of filinq's admin page.
 *
 * @param page The page.
 */
async function openSettings(page: Page): Promise<void> {
	await page.goto('/index.php/settings/admin/filinq', { waitUntil: 'domcontentloaded' })
	await waitForNcContentReady(page)
	await expect(page.getByText(/Four eyes when anonymising|Vier ogen bij anonimiseren/)).toBeVisible({
		timeout: 30_000,
	})
}

test.describe('four eyes when anonymising', () => {
	test.setTimeout(180_000)

	test.afterEach(async ({ request }) => {
		await setMode(request, 'woo')
	})

	test('a fresh install selects "Only for Woo cases"', async ({ page }) => {
		test.skip(!CLEAN_INSTANCE, 'FILINQ_E2E_CLEAN_INSTANCE is not 1: the setting may have been stored before')

		await openSettings(page)
		await expect(page.getByRole('radio', { name: /Only for Woo cases|Alleen bij Woo-zaken/ })).toBeChecked()
	})

	test('the administrator switches it off and the stored value is off', async ({ page, request }) => {
		expect(await setMode(request, 'woo')).toBe(200)

		await openSettings(page)
		await page.getByText(/^(Off|Uit)$/).click()
		await page.getByRole('button', { name: /Save All Settings|Alle instellingen opslaan/ }).click()
		await expect(page.getByText(/All settings saved successfully|Alle instellingen/)).toBeVisible({ timeout: 30_000 })

		expect(await readMode(request)).toBe('off')
	})

	test('an unknown value is refused with 400 and the setting stays', async ({ request }) => {
		expect(await setMode(request, 'woo')).toBe(200)
		expect(await setMode(request, 'sometimes')).toBe(400)
		expect(await readMode(request)).toBe('woo')
	})

	test('the checker is refused on a Woo case with same_person', async ({ request }) => {
		test.skip(FILE_ID === '', 'FILINQ_E2E_ANALYSED_FILE_ID is not set: no analysed document on this instance')

		expect(await setMode(request, 'woo')).toBe(200)
		await markChecked(request)

		const answer = await anonymise(request, true)
		expect(answer.status).toBe(409)
		expect(answer.body.reviewRequired).toBe('same_person')
	})

	test('the checker may anonymise outside a Woo case while the setting is woo', async ({ request }) => {
		test.skip(FILE_ID === '', 'FILINQ_E2E_ANALYSED_FILE_ID is not set: no analysed document on this instance')

		expect(await setMode(request, 'woo')).toBe(200)
		await markChecked(request)

		const answer = await anonymise(request, false)
		expect(answer.status, JSON.stringify(answer.body)).toBe(200)
	})

	test('always refuses the checker outside a Woo case too', async ({ request }) => {
		test.skip(FILE_ID === '', 'FILINQ_E2E_ANALYSED_FILE_ID is not set: no analysed document on this instance')

		expect(await setMode(request, 'always')).toBe(200)
		await markChecked(request)

		const answer = await anonymise(request, false)
		expect(answer.status).toBe(409)
		expect(answer.body.reviewRequired).toBe('same_person')
	})

	test('a colleague anonymises the Woo document after the check', async ({ playwright, request, baseURL }) => {
		test.skip(FILE_ID === '' || FILE_PATH === '', 'FILINQ_E2E_ANALYSED_FILE_ID/PATH is not set: no analysed document on this instance')

		expect(await setMode(request, 'woo')).toBe(200)
		await markChecked(request)

		const userid = `four-eyes-e2e-${Date.now()}`
		const password = `Pw-${Date.now()}-colleague!`
		const made = await request.post(USERS_API, { headers: OCS, form: { userid, password } })
		expect(made.ok(), `create user -> ${made.status()}`).toBeTruthy()

		const shared = await request.post(SHARES_API, {
			headers: OCS,
			form: { path: FILE_PATH, shareType: '0', shareWith: userid, permissions: '19' },
		})
		expect(shared.ok(), `share -> ${shared.status()}`).toBeTruthy()

		const colleague = await playwright.request.newContext({
			baseURL,
			httpCredentials: { username: userid, password },
		})
		try {
			const answer = await anonymise(colleague, true)
			expect(answer.status, JSON.stringify(answer.body)).toBe(200)
		} finally {
			await colleague.dispose()
			await request.delete(`${USERS_API}/${userid}`, { headers: OCS })
		}
	})
})

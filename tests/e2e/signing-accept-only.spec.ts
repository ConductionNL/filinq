/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * signing-accept-only-recipient (REQ-SAO-001 to REQ-SAO-003): a recipient on
 * a signing request either signs or only accepts. The initiator sets the role
 * per recipient on the new request form, a request with nobody to sign is
 * refused, an accept-only recipient accepts from the signing folder without
 * signing, and the request completes on one signature and one acceptance.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The new request form, the detail page's recipient list, the signing folder's
 * Accept button and the verification page are what the employee, the officer
 * and the reader use, so they are clicked. The tenant's signature goes through
 * `POST api/signing/requests/{id}/sign`, because the scenario is about the
 * acceptance counting towards completion, not about the signing ceremony.
 *
 * The tenant and the housing officer are run-stamped users created through the
 * OCS provisioning API; each works in a browser context with an EMPTY storage
 * state and basic auth, so the admin's session never stands in for them. The
 * lease is a WebDAV file shared with both.
 *
 * "An accept-only recipient cannot sign through the API" is `@e2e exclude` in
 * the spec (PHPUnit and Newman cover it). The portal scenarios name no e2e file.
 *
 * Not run in the change that wrote it: the live pass runs it.
 */

import type { Browser, BrowserContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from './spec-coverage/_helpers.ts'
import {
	API,
	createDavFile,
	createDavFolder,
	deleteDavPath,
	harvestToken,
	jsonHeaders,
	TEST_PREFIX,
} from './workflows/_fixtures.ts'

/** Provisioning and sharing. */
const USERS_API = '/ocs/v1.php/cloud/users'
const SHARES_API = '/ocs/v2.php/apps/files_sharing/api/v1/shares?format=json'
const OCS = { 'OCS-APIRequest': 'true' }

/** Where the lease lives. */
const FOLDER = `${TEST_PREFIX}-accept-only`
const LEASE = `${TEST_PREFIX}-huurovereenkomst`

/** The two recipients. */
const TENANT = {
	userid: `${TEST_PREFIX}-tenant`,
	password: 'Pw-tenant-2026!Strong#e2e',
	name: 'Tenant',
}
const OFFICER = {
	userid: `${TEST_PREFIX}-officer`,
	password: 'Pw-officer-2026!Strong#e2e',
	name: 'Housing officer',
}

/**
 * A browser context for one recipient: basic auth on an empty storage state.
 *
 * @param browser The browser.
 * @param user    The recipient.
 * @param user.userid   Their user id.
 * @param user.password Their password.
 * @return The context.
 */
async function contextFor(
	browser: Browser,
	user: { userid: string; password: string },
): Promise<BrowserContext> {
	// A real session through the login form. With httpCredentials alone the
	// browser has no Nextcloud session, and the app shell never mounts
	// `#filinq-app` (seen on a live run, 25 Nov 2026).
	const context = await browser.newContext({
		storageState: { cookies: [], origins: [] },
	})
	const login = await context.newPage()
	await login.goto('/index.php/login', { waitUntil: 'domcontentloaded' })
	await login.locator('#user').fill(user.userid)
	await login.locator('#password').fill(user.password)
	await login.locator('button[type="submit"]').click()
	await login.waitForURL(/\/apps\//, { timeout: 60000 })
	await login.close()
	return context
}

/**
 * Fill one recipient row of the new request form.
 *
 * @param page  The page.
 * @param index The row.
 * @param name  The recipient's name.
 * @param user  The recipient's Nextcloud user.
 * @param role  The role label to choose.
 * @return Nothing.
 */
async function fillRecipient(
	page: Page,
	index: number,
	name: string,
	user: string,
	role: 'Sign' | 'Accept only',
): Promise<void> {
	await page.locator(`#signer-${index}-name`).fill(name)
	await page.locator(`#signer-${index}-user`).fill(user)
	const row = page.locator('.signers__row').nth(index)
	await row.getByRole('combobox').click()
	await page.getByRole('option', { name: role, exact: true }).click()
}

/**
 * Create a request on the form and return its id from the create answer.
 *
 * @param page   The page.
 * @param fileId The lease's file id.
 * @return The signing request id.
 */
async function createOnForm(page: Page, fileId: string): Promise<string> {
	await go(page, 'signing/new')
	await page.locator('#signing-request-document-file-id').fill(fileId)
	await page.locator('#signing-request-document-name').fill(`${LEASE}.txt`)
	await page
		.locator('.form-group')
		.filter({ hasText: 'Signing Mode' })
		.locator('select')
		.selectOption('parallel')

	await fillRecipient(page, 0, TENANT.name, TENANT.userid, 'Sign')
	await page.getByRole('button', { name: 'Add signer' }).click()
	await fillRecipient(page, 1, OFFICER.name, OFFICER.userid, 'Accept only')

	const created = page.waitForResponse(
		(res) =>
			res.url().endsWith('/api/signing/requests')
			&& res.request().method() === 'POST',
	)
	await page.getByRole('button', { name: 'Create Signing Request' }).click()
	const response = await created
	expect(response.status(), await response.text()).toBe(201)
	const id = (await response.json()).id
	expect(id, 'the create answer carries the request id').toBeTruthy()
	return String(id)
}

/**
 * The recipient row of one recipient on the detail page.
 *
 * @param page The page on the detail page.
 * @param name The recipient's name.
 * @return The row locator.
 */
function recipientRow(page: Page, name: string) {
	return page
		.locator('h3', { hasText: 'Recipients' })
		.locator('xpath=following-sibling::table[1]')
		.locator('tbody tr')
		.filter({ hasText: name })
		.first()
}

test.describe('A recipient signs or only accepts', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(180_000)

	let token = ''
	let leaseFileId = ''
	let requestId = ''
	let usersMade = false

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect(await createDavFolder(page.request, token, FOLDER)).toBeLessThan(300)
		const seeded = await createDavFile(
			page.request,
			token,
			`${FOLDER}/${LEASE}.txt`,
			'Huurovereenkomst, artikel 1.',
		)
		expect(seeded.status, 'seeding the lease').toBeLessThan(300)
		leaseFileId = seeded.fileId

		for (const user of [TENANT, OFFICER]) {
			const made = await page.request.post(USERS_API, {
				headers: OCS,
				form: { userid: user.userid, password: user.password },
			})
			expect(
				made.ok(),
				`create ${user.userid} -> ${made.status()}`,
			).toBeTruthy()
			const shared = await page.request.post(SHARES_API, {
				headers: OCS,
				form: {
					path: `/${FOLDER}/${LEASE}.txt`,
					shareType: '0',
					shareWith: user.userid,
					permissions: '19',
				},
			})
			expect(
				shared.ok(),
				`share the lease with ${user.userid} -> ${shared.status()}`,
			).toBeTruthy()
		}
		usersMade = true
		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		await deleteDavPath(page.request, await harvestToken(page), FOLDER)
		if (usersMade) {
			for (const user of [TENANT, OFFICER]) {
				await page.request.delete(`${USERS_API}/${user.userid}`, {
					headers: OCS,
				})
			}
		}
		await page.close()
	})

	// @e2e signing-accept-only::an-employee-asks-one-party-to-sign-and-another-to-accept
	test('the form sends one signer and one accept-only recipient, and the detail page lists both roles', async ({
		page,
	}) => {
		requestId = await createOnForm(page, leaseFileId)

		await go(page, `signing/${requestId}`)
		await expect(page.locator('h3', { hasText: 'Recipients' })).toBeVisible({
			timeout: 30000,
		})
		await expect(recipientRow(page, TENANT.name)).toContainText('Sign')
		await expect(recipientRow(page, TENANT.name)).not.toContainText(
			'Accept only',
		)
		await expect(recipientRow(page, OFFICER.name)).toContainText('Accept only')
		await expect(recipientRow(page, OFFICER.name)).toContainText('Waiting')
	})

	// @e2e signing-accept-only::nobody-to-sign
	test('a request with only accept-only recipients is refused', async ({
		page,
	}) => {
		await go(page, 'signing/new')
		await page.locator('#signing-request-document-file-id').fill(leaseFileId)
		await page.locator('#signing-request-document-name').fill(`${LEASE}.txt`)
		await fillRecipient(page, 0, OFFICER.name, OFFICER.userid, 'Accept only')

		await expect(
			page.getByText('A signing request needs at least one signer'),
		).toBeVisible()
		await expect(
			page.getByRole('button', { name: 'Create Signing Request' }),
		).toBeDisabled()

		// The server refuses it too, for a caller that skips the form.
		const refused = await page.request.post(`${API}/signing/requests`, {
			headers: jsonHeaders(token),
			data: {
				documentFileId: leaseFileId,
				documentName: `${LEASE}.txt`,
				signers: [
					{
						order: 0,
						displayName: OFFICER.name,
						userId: OFFICER.userid,
						role: 'accept',
					},
				],
			},
		})
		expect(refused.status(), await refused.text()).toBe(400)
	})

	// @e2e signing-accept-only::the-housing-officer-accepts-from-the-signing-folder
	test('the officer accepts from the signing folder and the audit trail reads Accepted', async ({
		browser,
		page,
	}) => {
		expect(requestId, 'the first test created the request').not.toBe('')
		const context = await contextFor(browser, OFFICER)
		try {
			const officer = await context.newPage()
			await go(officer, 'signing-folder')
			const entry = officer.locator('tr').filter({ hasText: LEASE }).first()
			await expect(entry).toBeVisible({ timeout: 30000 })

			const accepted = officer.waitForResponse(
				(res) =>
					res.url().includes(`/api/signing/requests/${requestId}/accept`)
					&& res.request().method() === 'POST',
			)
			await entry.getByRole('button', { name: `Accept ${LEASE}` }).click()
			const response = await accepted
			expect(response.status(), await response.text()).toBe(200)
			const record = await response.json()
			expect(record.status).toBe('ACCEPTED')
			expect(
				record.signatureData ?? null,
				'an acceptance is never a signature',
			).toBeNull()

			await expect(
				officer.locator('tr').filter({ hasText: LEASE }),
				'the lease leaves her folder',
			).toHaveCount(0, { timeout: 30000 })
		} finally {
			await context.close()
		}

		const audit = await page.request.get(
			`${API}/signing/requests/${requestId}/audit`,
			{ headers: jsonHeaders(token) },
		)
		expect(audit.status(), await audit.text()).toBe(200)
		const trail = JSON.stringify(await audit.json())
		expect(trail).toContain('ACCEPTED')
		expect(trail).toContain(OFFICER.userid)
	})

	// @e2e signing-accept-only::a-signature-and-an-acceptance-complete-the-lease
	test('one signature and one acceptance complete the lease', async ({
		browser,
		page,
	}) => {
		expect(requestId, 'the first test created the request').not.toBe('')
		const shown = await page.request.get(
			`${API}/signing/requests/${requestId}`,
			{ headers: jsonHeaders(token) },
		)
		expect(shown.status(), await shown.text()).toBe(200)
		const recipients: Array<{ id: string; role: string }> =
			(await shown.json()).recipients ?? []
		const tenantRecord = recipients.find(
			(recipient) => recipient.role !== 'accept',
		)
		expect(tenantRecord, 'the request has a signer').toBeTruthy()

		const context = await contextFor(browser, TENANT)
		try {
			const tenant = await context.newPage()
			await tenant.goto('/index.php/apps/filinq/')
			const tenantToken = await harvestToken(tenant)
			const signed = await tenant.request.post(
				`${API}/signing/requests/${requestId}/sign`,
				{
					headers: jsonHeaders(tenantToken),
					data: { signerId: tenantRecord?.id },
				},
			)
			expect(signed.status(), await signed.text()).toBe(200)
		} finally {
			await context.close()
		}

		await go(page, `signing/${requestId}`)
		await expect(page.getByText(/Status\s*:\s*completed/i)).toBeVisible({
			timeout: 30000,
		})
		await expect(recipientRow(page, TENANT.name)).toContainText('Signed')
		await expect(recipientRow(page, OFFICER.name)).toContainText('Accepted')

		await go(page, `signing/verify/${leaseFileId}`)
		const signatures = page.locator('.signature-list')
		await expect(signatures, 'the tenant signed').toBeVisible({ timeout: 30000 })
		await expect(signatures.locator('li')).toHaveCount(1)
		await expect(
			signatures,
			'the officer accepted; she is not a signer',
		).not.toContainText(OFFICER.name)
	})
})

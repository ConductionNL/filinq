/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * signing-field-validation (REQ-SFV-001, REQ-SFV-003, REQ-SFV-004): a signer
 * fills in a text field while signing, the server checks it (a Dutch IBAN)
 * before anything is written, a request with fields stays out of a signing
 * folder selection, and a valid value is stored on the signer record.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The placement editor on the new request form, the "Fill in and sign"
 * dialog and the signing folder's selection are what the employee uses, so
 * they are clicked. The requests the dialog and the folder scenarios start
 * from are created through `POST api/signing/requests` with the placement
 * shape `toRequestPlacements()` sends, because those scenarios are about the
 * signing act, not the editor. The stored value is read through
 * OpenRegister's objects API, as the scenario says.
 *
 * The employee is the signer on their own requests (the instance admin), so
 * no second account is needed. The folder signs through the signer's mandate:
 * on an instance where the admin has no signing mandate, the two other
 * documents are refused for that reason and the test says so.
 *
 * The check is OpenRegister's `iban` format (ConductionNL/openregister#10571).
 * On an OpenRegister without it every request with a check is refused with
 * "This check is not available on this server", and these tests fail on
 * that line rather than pass on a request that was never checked.
 *
 * Reading the value off the signed page, and an altered value, are PHPUnit's
 * (task 2.4); the older-OpenRegister and foreign-IBAN branches are `@e2e
 * exclude` in the spec.
 *
 * Not run in the change that wrote it: the live pass runs it.
 */

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { readFileSync } from 'node:fs'
import path from 'node:path'
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

/** Where the agreements live. */
const FOLDER = `${TEST_PREFIX}-field-validation`

/** A real one-page PDF, so the placement editor has a page to draw. */
const SAMPLE_PDF = path.resolve(
	__dirname,
	'../sample-documents/pdfa/pdfa3b-rendered.pdf',
)

/** The values the scenarios type. */
const WRONG_IBAN = 'NL91 ABNA 0417 1643 01'
const RIGHT_IBAN = 'NL91 ABNA 0417 1643 00'
const STORED_IBAN = 'NL91ABNA0417164300'
const IBAN_MESSAGE = 'Type a valid Dutch IBAN, for example NL91 ABNA 0417 1643 00'
const FIELD_LABEL = 'Account for the payment'

/** The OpenRegister objects endpoint of signer records. */
const SIGNER_RECORDS = '/index.php/apps/openregister/api/objects/filinq/signerRecord'

/**
 * Upload the sample PDF under a name and return its file id.
 *
 * @param req   The request context.
 * @param token The request token.
 * @param name  The file name.
 * @return The file id.
 */
async function uploadPdf(
	req: APIRequestContext,
	token: string,
	name: string,
): Promise<string> {
	const url = `/remote.php/dav/files/admin/${FOLDER}/${name}`
	const put = await req.put(url, {
		headers: { requesttoken: token, 'Content-Type': 'application/pdf' },
		data: readFileSync(SAMPLE_PDF),
	})
	expect([201, 204], `uploading ${name}`).toContain(put.status())
	const found = await req.fetch(url, {
		method: 'PROPFIND',
		headers: {
			requesttoken: token,
			Depth: '0',
			'Content-Type': 'application/xml',
		},
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
	})
	const fileId =
		(await found.text()).match(/<oc:fileid>(\d+)<\/oc:fileid>/)?.[1] ?? ''
	expect(fileId, `${name} has a file id`).not.toBe('')
	return fileId
}

/**
 * Create a request with the admin as its only signer, optionally with a
 * required Dutch IBAN field for them.
 *
 * @param req       The request context.
 * @param token     The request token.
 * @param fileId    The document.
 * @param name      The document name.
 * @param withField Whether the signer has the IBAN field.
 * @return The request id.
 */
async function createRequest(
	req: APIRequestContext,
	token: string,
	fileId: string,
	name: string,
	withField: boolean,
): Promise<string> {
	const created = await req.post(`${API}/signing/requests`, {
		headers: jsonHeaders(token),
		data: {
			documentFileId: fileId,
			documentName: name,
			signatureLevel: 'SES',
			signingMode: 'sequential',
			signers: [{ order: 0, displayName: 'Employee', userId: 'admin' }],
			...(withField
				? {
						fieldPlacements: [
							{
								signerIndex: 0,
								page: 1,
								x: 0.1,
								y: 0.7,
								width: 0.4,
								height: 0.05,
								type: 'text',
								label: FIELD_LABEL,
								required: true,
								validation: 'iban-nl',
							},
						],
					}
				: {}),
		},
	})
	expect(created.status(), await created.text()).toBe(201)
	return String((await created.json()).id)
}

/**
 * Read a request back.
 *
 * @param req   The request context.
 * @param token The request token.
 * @param id    The request id.
 * @return The request.
 */
async function readRequest(
	req: APIRequestContext,
	token: string,
	id: string,
): Promise<Record<string, unknown>> {
	const shown = await req.get(`${API}/signing/requests/${id}`, {
		headers: jsonHeaders(token),
	})
	expect(shown.status(), await shown.text()).toBe(200)
	return shown.json()
}

/**
 * The folder row of a document.
 *
 * @param page The page on the signing folder.
 * @param name The document name.
 * @return The row locator.
 */
function folderRow(page: Page, name: string) {
	return page.locator('tr').filter({ hasText: name }).first()
}

test.describe('A field filled in while signing is checked and sealed', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(180_000)

	let token = ''
	let agreementId = ''
	const agreement = `${TEST_PREFIX}-subsidieovereenkomst.pdf`

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect(await createDavFolder(page.request, token, FOLDER)).toBeLessThan(300)
		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		await deleteDavPath(page.request, await harvestToken(page), FOLDER)
		await page.close()
	})

	// @e2e openspec/changes/signing-field-validation/specs/signing-field-validation/spec.md#an-employee-asks-for-a-bank-account
	test('the placement editor saves a required Dutch IBAN field', async ({
		page,
	}) => {
		const fileId = await uploadPdf(
			page.request,
			token,
			`${TEST_PREFIX}-placement.pdf`,
		)

		await go(page, 'signing/new')
		await page.locator('#signing-request-document-file-id').fill(fileId)
		await page
			.locator('#signing-request-document-name')
			.fill(`${TEST_PREFIX}-placement.pdf`)
		await page.locator('#signer-0-name').fill('Applicant')
		await page.locator('#signer-0-user').fill('admin')
		await page.getByText('Place fields on the document').click()

		const editor = page.locator('fieldset.field-placement')
		await expect(editor).toBeVisible({ timeout: 30000 })
		await editor.locator('.field-placement__tools .v-select').nth(1).click()
		await page.getByRole('option', { name: 'Name', exact: true }).click()
		const sheet = page.getByTestId('field-placement-sheet')
		await expect(sheet).toBeVisible({ timeout: 30000 })
		await sheet.focus()
		await sheet.press('Enter')

		const settings = page.getByTestId('field-settings-0')
		await expect(settings).toBeVisible()
		await settings.getByLabel('Label for the signer').fill(FIELD_LABEL)
		await settings.getByText('The signer must fill this in').click()
		await settings.getByRole('combobox').click()
		await page.getByRole('option', { name: 'Dutch IBAN', exact: true }).click()

		const created = page.waitForResponse(
			(res) =>
				res.url().endsWith('/api/signing/requests')
				&& res.request().method() === 'POST',
		)
		await page.getByRole('button', { name: 'Create Signing Request' }).click()
		const response = await created
		expect(response.status(), await response.text()).toBe(201)

		const saved = await readRequest(
			page.request,
			token,
			String((await response.json()).id),
		)
		const placements = (saved.fieldPlacements ?? []) as Array<
			Record<string, unknown>
		>
		const text = placements.find((placement) => placement.type === 'text')
		expect(text, 'the request carries the text placement').toBeTruthy()
		expect(text?.label).toBe(FIELD_LABEL)
		expect(text?.required).toBe(true)
		expect(text?.validation).toBe('iban-nl')
	})

	// @e2e openspec/changes/signing-field-validation/specs/signing-field-validation/spec.md#a-typing-error-is-caught
	test('a wrong check digit is named on the field and nothing is signed', async ({
		page,
	}) => {
		const fileId = await uploadPdf(page.request, token, agreement)
		agreementId = await createRequest(
			page.request,
			token,
			fileId,
			agreement,
			true,
		)

		await go(page, 'signing-folder')
		const row = folderRow(page, agreement)
		await expect(row).toBeVisible({ timeout: 30000 })
		await row.getByTestId(`fill-in-and-sign-${agreementId}`).click()

		const dialog = page.locator('.fill-in-and-sign')
		await expect(dialog).toBeVisible({ timeout: 15000 })
		await dialog.getByLabel(FIELD_LABEL).fill(WRONG_IBAN)
		const refused = page.waitForResponse(
			(res) =>
				res.url().includes(`/api/signing/requests/${agreementId}/sign`)
				&& res.request().method() === 'POST',
		)
		await dialog.getByRole('button', { name: 'Sign', exact: true }).click()
		expect((await refused).status()).toBe(422)
		await expect(dialog.getByText(IBAN_MESSAGE)).toBeVisible()

		const after = await readRequest(page.request, token, agreementId)
		const recipients = (after.recipients ?? []) as Array<{ status: string }>
		expect(
			recipients.map((recipient) => recipient.status),
			'the request still waits for the signature',
		).toEqual(['PENDING'])
	})

	// @e2e openspec/changes/signing-field-validation/specs/signing-field-validation/spec.md#not-in-a-folder-selection
	test('a request with fields stays out of a folder selection', async ({
		page,
	}) => {
		expect(agreementId, 'the previous test created the agreement').not.toBe('')
		const others: string[] = []
		for (const stem of ['brief-a', 'brief-b']) {
			const name = `${TEST_PREFIX}-${stem}.txt`
			const seeded = await createDavFile(
				page.request,
				token,
				`${FOLDER}/${name}`,
				`Contents of ${stem}.`,
			)
			expect(seeded.status).toBeLessThan(300)
			await createRequest(page.request, token, seeded.fileId, name, false)
			others.push(name)
		}

		await go(page, 'signing-folder')
		const withFields = folderRow(page, agreement)
		await expect(withFields).toBeVisible({ timeout: 30000 })
		await expect(
			withFields.getByText(
				'This document has fields to fill in. Open it to sign.',
			),
		).toBeVisible()
		await expect(
			withFields.getByRole('checkbox'),
			'it cannot be selected',
		).toBeDisabled()

		for (const name of others) {
			await folderRow(page, name).getByRole('checkbox').check()
		}
		await page.getByRole('button', { name: /Sign selected/ }).click()
		const results = page.locator('.signing-folder__results')
		await expect(results).toBeVisible({ timeout: 30000 })
		for (const name of others) {
			await expect(
				results.getByText(`Signed: ${name}`),
				`${name} is signed (needs the admin's signing mandate)`,
			).toBeVisible()
		}

		// A caller that puts it in a selection anyway is told why on the server.
		const forced = await page.request.post(`${API}/signing/folder/sign`, {
			headers: jsonHeaders(token),
			data: { requestIds: [agreementId] },
		})
		expect(forced.status(), await forced.text()).toBeLessThan(500)
		expect(await forced.text()).toContain(
			'This document has fields to fill in. Open it to sign.',
		)
	})

	// @e2e openspec/changes/signing-field-validation/specs/signing-field-validation/spec.md#the-account-is-on-the-signed-agreement
	test('a valid IBAN is signed and stored normalised on the signer record', async ({
		page,
	}) => {
		expect(agreementId, 'the earlier test created the agreement').not.toBe('')
		await go(page, 'signing-folder')
		await folderRow(page, agreement)
			.getByTestId(`fill-in-and-sign-${agreementId}`)
			.click()
		const dialog = page.locator('.fill-in-and-sign')
		await expect(dialog).toBeVisible({ timeout: 15000 })
		await dialog.getByLabel(FIELD_LABEL).fill(RIGHT_IBAN)
		const signed = page.waitForResponse(
			(res) =>
				res.url().includes(`/api/signing/requests/${agreementId}/sign`)
				&& res.request().method() === 'POST',
		)
		await dialog.getByRole('button', { name: 'Sign', exact: true }).click()
		expect((await signed).status()).toBe(200)

		const after = await readRequest(page.request, token, agreementId)
		const recipient = (
			(after.recipients ?? []) as Array<{ id: string; status: string }>
		)[0]
		expect(recipient?.status).toBe('SIGNED')

		const record = await page.request.get(`${SIGNER_RECORDS}/${recipient.id}`, {
			headers: jsonHeaders(token),
		})
		expect(record.status(), await record.text()).toBe(200)
		expect(
			JSON.stringify((await record.json()).fieldValues ?? {}),
			'the value is stored in capitals without spaces',
		).toContain(STORED_IBAN)
	})
})

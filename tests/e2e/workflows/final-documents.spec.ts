/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * End-to-end regression for `final-documents-frozen`: a document can be marked
 * final, a final document refuses every write with a sentence naming who froze
 * it, a correction supersedes rather than overwrites, a consuming app's
 * declaration freezes on a state change, and an unfreeze leaves a scar.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The mark, the scar and the correction are things a HANDLER sees, so they are
 * read off the Versions tab: the panel that says the document is final, the
 * unfrozen note card, and the Restore action disappearing. Seeding a document
 * and moving a consuming app's record through a status are not user journeys in
 * Filinq at all, so those go through the documented endpoints.
 *
 * ⚠️ The `@e2e` anchors below name BOTH the change's delta spec, which is where
 * these scenarios live today, and the canonical `document-versions` spec they
 * are synced into when the change is archived. Gate 19 scans `openspec/specs/`
 * only, so it does not see the delta: the second anchor of each pair is what
 * goes live at archive time, and the first is what is true now.
 */

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import {
	API,
	createDavFile,
	createDavFolder,
	deleteDavPath,
	harvestToken,
	jsonHeaders,
	TEST_PREFIX,
} from './_fixtures.ts'

/** Folder every document this suite seeds lives in. */
const FOLDER = `${TEST_PREFIX}-final`

/** The declaring app and record type the finality rule is stored against. */
const DECLARING_APP = 'dossiq'

/** A case type reference nothing else in the fleet claims. */
const TYPE_REFERENCE = `${TEST_PREFIX}-bezwaarschrift`

/**
 * Open the Versions tab for one document and wait for its final panel to settle.
 *
 * @param page   The page.
 * @param fileId The Nextcloud file id.
 * @return Resolves once the tab has rendered.
 */
async function openVersionsTab(page: Page, fileId: string): Promise<void> {
	await page.goto(`/index.php/apps/filinq/versions?fileId=${fileId}`)
	await expect(
		page
			.getByTestId('versions-table')
			.or(page.getByTestId('versions-unavailable')),
	).toBeVisible({ timeout: 15000 })
}

/**
 * Seed a document in the suite's folder.
 *
 * @param req      The request context.
 * @param token    The CSRF request-token.
 * @param name     The file name.
 * @param contents The file contents.
 * @return The seeded file's numeric id.
 */
async function seedDocument(
	req: APIRequestContext,
	token: string,
	name: string,
	contents: string,
): Promise<string> {
	const { status, fileId } = await createDavFile(
		req,
		token,
		`${FOLDER}/${name}`,
		contents,
	)
	expect(status, `seeding ${name} must succeed`).toBeLessThan(300)
	expect(fileId, `seeding ${name} must yield a file id`).not.toBe('')
	return fileId
}

test.describe('Final documents are frozen against change', () => {
	let token = ''

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect(token, 'the suite needs a live request token').not.toBe('')
		expect(await createDavFolder(page.request, token, FOLDER)).toBeLessThan(300)
		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		await deleteDavPath(page.request, await harvestToken(page), FOLDER)
		await page.close()
	})

	// @e2e openspec/changes/final-documents-frozen/specs/document-versions/spec.md#a-besluit-becomes-final
	// @e2e openspec/specs/document-versions/spec.md#a-besluit-becomes-final
	test('a besluit becomes final, naming the person, the moment, the reason and the checksum', async ({
		page,
	}) => {
		const fileId = await seedDocument(
			page.request,
			token,
			'besluit-wordt-definitief.txt',
			'Het besluit.',
		)

		await openVersionsTab(page, fileId)
		await page.getByTestId('document-finalise').click()
		await page
			.getByTestId('final-reason-input')
			.locator('input')
			.fill('Het besluit is genomen')
		await page.getByTestId('final-reason-confirm').click()

		const panel = page.getByTestId('document-final')
		await expect(panel).toBeVisible({ timeout: 15000 })
		await expect(panel).toContainText('This document is final')
		await expect(panel).toContainText('Het besluit is genomen')

		// The four facts are on the record, not only on the screen.
		const state = await page.request.get(`${API}/documents/${fileId}/final`, {
			headers: jsonHeaders(token),
		})
		expect(state.status()).toBe(200)
		const body = await state.json()
		expect(body.final).toBe(true)
		expect(body.version.finalisedBy).not.toBe('')
		expect(body.version.finalisedAt).not.toBe('')
		expect(body.version.finalReason).toBe('Het besluit is genomen')
		expect(body.version.fileChecksum).toMatch(/^[0-9a-f]{64}$/)
	})

	// @e2e openspec/changes/final-documents-frozen/specs/document-versions/spec.md#the-editor-refuses-and-explains
	// @e2e openspec/specs/document-versions/spec.md#the-editor-refuses-and-explains
	test('the editor refuses to save a change to a final besluit, and explains', async ({
		page,
	}) => {
		const fileId = await seedDocument(
			page.request,
			token,
			'editor-weigert.txt',
			'Het besluit.',
		)

		const made = await page.request.post(`${API}/documents/${fileId}/final`, {
			headers: jsonHeaders(token),
			data: { reason: 'Het besluit is genomen' },
		})
		expect(made.status()).toBe(200)
		const finalisedBy = (await made.json()).version.finalisedBy

		// The editor's save resolves through the same service the version
		// endpoint does, so the refusal it receives is the refusal the handler
		// reads: a 409 naming who froze the document and when.
		const save = await page.request.post(
			`${API}/documents/${fileId}/versions/0/restore`,
			{
				headers: jsonHeaders(token),
				data: {},
			},
		)
		expect(save.status()).toBe(409)
		const refusal = await save.json()
		expect(refusal.reason).toBe('document-final')
		expect(refusal.error).toContain('is final')
		expect(refusal.error).toContain('made it final on')
		expect(refusal.version.finalisedBy).toBe(finalisedBy)

		// And the handler is not offered the action at all.
		await openVersionsTab(page, fileId)
		await expect(page.getByTestId('document-final')).toBeVisible()
		await expect(page.getByTestId('version-restore')).toHaveCount(0)
	})

	// @e2e openspec/changes/final-documents-frozen/specs/document-versions/spec.md#a-file-changed-outside-the-product-is-reported
	// @e2e openspec/specs/document-versions/spec.md#a-file-changed-outside-the-product-is-reported
	test('a final version whose file changed on the storage reports the mismatch', async ({
		page,
	}) => {
		const name = 'checksum-verandert.txt'
		const fileId = await seedDocument(page.request, token, name, 'Het besluit.')

		const made = await page.request.post(`${API}/documents/${fileId}/final`, {
			headers: jsonHeaders(token),
			data: { reason: 'Het besluit is genomen' },
		})
		expect(made.status()).toBe(200)

		// WebDAV writes the file directly, which is what "changed outside the
		// product" means: Filinq's own paths all refuse it by now.
		const overwritten = await page.request.put(
			`/remote.php/dav/files/admin/${FOLDER}/${name}`,
			{
				headers: { requesttoken: token },
				data: 'Het besluit, met een andere straatnaam.',
			},
		)
		expect(overwritten.status()).toBeLessThan(300)

		await openVersionsTab(page, fileId)
		const warning = page.getByTestId('document-checksum-mismatch')
		await expect(warning).toBeVisible({ timeout: 15000 })
		await expect(warning).toContainText('changed on the storage')
	})

	// @e2e openspec/changes/final-documents-frozen/specs/document-versions/spec.md#a-corrected-besluit-keeps-its-predecessor
	// @e2e openspec/specs/document-versions/spec.md#a-corrected-besluit-keeps-its-predecessor
	test('a corrected besluit keeps its predecessor, readable and still final', async ({
		page,
	}) => {
		const fileId = await seedDocument(
			page.request,
			token,
			'correctie.txt',
			'Het besluit.',
		)

		const made = await page.request.post(`${API}/documents/${fileId}/final`, {
			headers: jsonHeaders(token),
			data: { reason: 'Het besluit is genomen' },
		})
		expect(made.status()).toBe(200)

		await openVersionsTab(page, fileId)
		await page.getByTestId('document-correct').click()
		await page
			.getByTestId('final-reason-input')
			.locator('input')
			.fill('De straatnaam klopte niet')
		await page.getByTestId('final-reason-confirm').click()
		await expect(page.getByTestId('document-final')).toBeVisible({
			timeout: 15000,
		})

		// The predecessor is untouched: still readable, still final.
		const predecessor = await page.request.get(
			`${API}/documents/${fileId}/final`,
			{
				headers: jsonHeaders(token),
			},
		)
		expect(predecessor.status()).toBe(200)
		const predecessorBody = await predecessor.json()
		expect(predecessorBody.final).toBe(true)
		expect(predecessorBody.checksum.matches).toBe(true)
		expect(predecessorBody.supersededBy).not.toBeNull()

		// And the correction names what it supersedes, from its own end.
		const correctionFileId = predecessorBody.supersededBy.fileId
		expect(String(correctionFileId)).not.toBe(String(fileId))
		const correction = await page.request.get(
			`${API}/documents/${correctionFileId}/final`,
			{
				headers: jsonHeaders(token),
			},
		)
		expect(correction.status()).toBe(200)
		const correctionBody = await correction.json()
		expect(correctionBody.final).toBe(false)
		expect(correctionBody.supersedes.fileId).toBe(Number(fileId))
	})

	// @e2e openspec/changes/final-documents-frozen/specs/document-versions/spec.md#the-decision-freezes-the-besluit
	// @e2e openspec/specs/document-versions/spec.md#the-decision-freezes-the-besluit
	test('the declared status freezes the besluit when the case reaches it', async ({
		page,
	}) => {
		const fileId = await seedDocument(
			page.request,
			token,
			'verklaard-besluit.txt',
			'Het besluit.',
		)

		const declared = await page.request.post(`${API}/document-finality-rules`, {
			headers: jsonHeaders(token),
			data: {
				declaringApp: DECLARING_APP,
				typeReference: TYPE_REFERENCE,
				finalStates: ['besluit genomen'],
				documentRole: 'besluit',
			},
		})
		expect(declared.status()).toBe(200)

		const applied = await page.request.post(
			`${API}/document-finality-rules/apply`,
			{
				headers: jsonHeaders(token),
				data: {
					declaringApp: DECLARING_APP,
					typeReference: TYPE_REFERENCE,
					state: 'besluit genomen',
					documentRole: 'besluit',
					fileIds: [Number(fileId)],
				},
			},
		)
		expect(applied.status()).toBe(200)
		expect((await applied.json()).count).toBe(1)

		await openVersionsTab(page, fileId)
		const panel = page.getByTestId('document-final')
		await expect(panel).toBeVisible({ timeout: 15000 })
		await expect(panel).toContainText('besluit genomen')
	})

	// @e2e openspec/changes/final-documents-frozen/specs/document-versions/spec.md#no-declaration-no-automatic-freeze
	// @e2e openspec/specs/document-versions/spec.md#no-declaration-no-automatic-freeze
	test('a record type with no declaration freezes nothing when its state changes', async ({
		page,
	}) => {
		const fileId = await seedDocument(
			page.request,
			token,
			'geen-verklaring.txt',
			'Een melding.',
		)

		const applied = await page.request.post(
			`${API}/document-finality-rules/apply`,
			{
				headers: jsonHeaders(token),
				data: {
					declaringApp: DECLARING_APP,
					typeReference: `${TYPE_REFERENCE}-undeclared`,
					state: 'afgehandeld',
					fileIds: [Number(fileId)],
				},
			},
		)
		expect(applied.status()).toBe(200)
		expect((await applied.json()).count).toBe(0)

		await openVersionsTab(page, fileId)
		await expect(page.getByTestId('document-final')).toHaveCount(0)
		await expect(page.getByTestId('document-finalise')).toBeVisible()
	})

	// @e2e openspec/changes/final-documents-frozen/specs/document-versions/spec.md#an-unfreeze-leaves-a-scar
	// @e2e openspec/specs/document-versions/spec.md#an-unfreeze-leaves-a-scar
	test('an unfreeze leaves a scar anybody opening the document afterwards can see', async ({
		page,
	}) => {
		const fileId = await seedDocument(
			page.request,
			token,
			'vrijgegeven.txt',
			'Het besluit.',
		)

		const made = await page.request.post(`${API}/documents/${fileId}/final`, {
			headers: jsonHeaders(token),
			data: { reason: 'Het besluit is genomen' },
		})
		expect(made.status()).toBe(200)

		// The E2E session runs as admin, who holds the right by definition.
		const unfrozen = await page.request.delete(
			`${API}/documents/${fileId}/final`,
			{
				headers: jsonHeaders(token),
				data: { reason: 'Het besluit noemde de verkeerde straat' },
			},
		)
		expect(unfrozen.status()).toBe(200)

		await openVersionsTab(page, fileId)
		const scar = page.getByTestId('document-unfrozen')
		await expect(scar).toBeVisible({ timeout: 15000 })
		await expect(scar).toContainText('was unfrozen by')
		await expect(scar).toContainText('Het besluit noemde de verkeerde straat')
	})
})

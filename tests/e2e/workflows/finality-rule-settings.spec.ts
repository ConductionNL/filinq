/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * finish-half-built-capabilities 3.2: an administrator declares a finality
 * rule on the admin settings page, and a status change of the owning app's
 * record then makes the document final.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The rule is declared through the "Final documents by record state" section,
 * because that section is the caller this task added for
 * POST /api/document-finality-rules; final-documents.spec.ts already covers
 * the same endpoint called directly. The status change is the owning app's
 * report (POST /api/document-finality-rules/apply), which is not a Filinq user
 * journey, so it goes through the endpoint. The result is read where a handler
 * sees it: the final panel on the document's Versions tab.
 */

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

/** Folder the seeded document lives in. */
const FOLDER = `${TEST_PREFIX}-finality-rule`

/** The owning app the rule is declared for. */
const DECLARING_APP = 'dossiq'

/** A record type nothing else in the fleet claims. */
const TYPE_REFERENCE = `${TEST_PREFIX}-vergunning`

/** The state that makes the document final. */
const FINAL_STATE = 'besluit verzonden'

test.describe('A finality rule declared on the admin settings freezes a document', () => {
	test.describe.configure({ mode: 'serial' })

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

	// @e2e openspec/changes/archive/2026-11-20-finish-half-built-capabilities/tasks.md#3.2
	test('the administrator declares the rule in the settings section', async ({ page }) => {
		await page.goto('/index.php/settings/admin/filinq', { waitUntil: 'domcontentloaded' })

		const section = page
			.locator('.settings-section, section')
			.filter({ hasText: 'Final documents by record state' })
			.first()
		await section.scrollIntoViewIfNeeded()
		await expect(section).toBeVisible({ timeout: 30000 })

		const declare = section.getByRole('button', { name: 'Declare rule' })
		await expect(declare, 'an incomplete rule cannot be declared').toBeDisabled()

		await section.getByLabel('App that owns the record').fill(DECLARING_APP)
		await section.getByLabel('Record type').fill(TYPE_REFERENCE)
		await section.getByLabel('States that make a document final').fill(` ${FINAL_STATE}, ${FINAL_STATE} `)
		await section.getByLabel('Document role').fill('besluit')

		const stored = page.waitForResponse(
			(res) => res.url().endsWith('/api/document-finality-rules') && res.request().method() === 'POST',
		)
		await expect(declare).toBeEnabled()
		await declare.click()
		const response = await stored
		expect(response.status(), await response.text()).toBe(200)

		const sent = response.request().postDataJSON()
		expect(sent.finalStates, 'the states are trimmed and deduplicated').toEqual([FINAL_STATE])

		await expect(section.getByRole('status')).toContainText(
			`Stored: ${DECLARING_APP} ${TYPE_REFERENCE} becomes final in ${FINAL_STATE}.`,
		)
	})

	// @e2e openspec/changes/archive/2026-11-20-finish-half-built-capabilities/tasks.md#3.2
	test('a status change of the record then makes the document final', async ({ page }) => {
		const { status, fileId } = await createDavFile(
			page.request,
			token,
			`${FOLDER}/vergunningsbesluit.txt`,
			'Het besluit op de aanvraag.',
		)
		expect(status, 'seeding the document must succeed').toBeLessThan(300)
		expect(fileId).not.toBe('')

		const applied = await page.request.post(`${API}/document-finality-rules/apply`, {
			headers: jsonHeaders(token),
			data: {
				declaringApp: DECLARING_APP,
				typeReference: TYPE_REFERENCE,
				state: FINAL_STATE,
				documentRole: 'besluit',
				fileIds: [Number(fileId)],
			},
		})
		expect(applied.status(), await applied.text()).toBe(200)
		expect((await applied.json()).count, 'the rule from the settings froze one document').toBe(1)

		await page.goto(`/index.php/apps/filinq/versions?fileId=${fileId}`)
		const panel = page.getByTestId('document-final')
		await expect(panel).toBeVisible({ timeout: 15000 })
		await expect(panel).toContainText(FINAL_STATE)
	})
})

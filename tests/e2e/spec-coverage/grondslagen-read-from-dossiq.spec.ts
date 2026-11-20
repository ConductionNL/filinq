/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * grondslagen-read-from-dossiq, task 5: the Woo refusal grounds read from
 * dossiq, or from the read-only snapshot, on the screens a reviewer and an
 * administrator use.
 *
 * BOTH STATES, and each test asserts the one the instance is in. `GET
 * /api/grounds` answers `source: dossiq` when dossiq is installed and enabled,
 * `source: snapshot` otherwise; the dossiq-absent state is the same instance
 * with dossiq disabled. A test for the other state skips and says why.
 *
 * THE REVIEW TABLE NEEDS ANALYSED DOCUMENTS. Its picker is only enabled on a
 * detected entity with a stored relation, which a run cannot make without a
 * real file through the detection pipeline. Point
 * `FILINQ_E2E_REVIEW_FOLDER` at a folder of the signed-in user that holds
 * such a document (the live pass does); without it the picker test skips.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#dossiq-installed
// @e2e openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#no-editing-without-dossiq

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go, waitForNcContentReady } from './_helpers.ts'

/** The grounds endpoint every picker reads. */
const GROUNDS_API = '/index.php/apps/filinq/api/grounds'

/** The ground the scenario picks. */
const CODE = '5.1.2.e'

/** A folder with analysed documents, for the review table (see above). */
const REVIEW_FOLDER = process.env.FILINQ_E2E_REVIEW_FOLDER ?? ''

/**
 * What the grounds endpoint answers.
 *
 * @param request The authenticated request context.
 * @return The answer: source, version and grounds.
 */
async function groundsAnswer(
	request: APIRequestContext,
): Promise<{ source: string; version: string; grounds: any[] }> {
	const res = await request.get(GROUNDS_API, {
		headers: { 'OCS-APIRequest': 'true' },
	})
	expect(res.ok(), `GET /api/grounds -> ${res.status()}`).toBeTruthy()
	const body = await res.json()
	expect(['dossiq', 'snapshot']).toContain(body.source)
	expect(Array.isArray(body.grounds) && body.grounds.length > 0).toBe(true)

	return body
}

/**
 * The picker label of a ground, as `services/bases.js` builds it.
 *
 * @param ground The ground.
 * @return The label.
 */
function optionLabel(ground: any): string {
	const label = typeof ground.label === 'string' ? ground.label : ''

	return label !== '' ? `${ground.code} — ${label}` : String(ground.code)
}

/**
 * Open filinq's admin settings and wait for the grounds section.
 *
 * @param page The page.
 */
async function openGroundsSettings(page: Page): Promise<void> {
	await page.goto('/index.php/settings/admin/filinq', {
		waitUntil: 'domcontentloaded',
	})
	await waitForNcContentReady(page)
	await expect(page.getByTestId('woo-grounds-source')).toBeVisible({
		timeout: 30_000,
	})
}

test.describe('grondslagen read from dossiq', () => {
	test.setTimeout(180_000)

	test('the grounds endpoint answers one list, never an empty one', async ({
		request,
	}) => {
		const answer = await groundsAnswer(request)
		for (const ground of answer.grounds) {
			expect(typeof ground.code).toBe('string')
			expect(String(ground.code)).not.toMatch(/^art-/)
		}
		expect(answer.grounds.map((ground: any) => ground.code)).toContain(CODE)
	})

	test('without dossiq the settings show the read-only snapshot, its version, and no editor', async ({
		page,
		request,
	}) => {
		const answer = await groundsAnswer(request)
		test.skip(answer.source !== 'snapshot', 'dossiq answers on this instance; the dossiq test covers it')

		await openGroundsSettings(page)
		const section = page.getByTestId('woo-grounds-source')
		await expect(section).toContainText(answer.version)
		await expect(section).toContainText(/dossiq/)
		await expect(section.locator('li').filter({ hasText: CODE }).first()).toBeVisible()

		// Read-only: nothing in the section adds, edits or deletes.
		await expect(section.locator('button')).toHaveCount(0)
		await expect(section.locator('input, textarea, select')).toHaveCount(0)
	})

	test('with dossiq the settings link to dossiq\'s grounds page and offer no editor', async ({
		page,
		request,
	}) => {
		const answer = await groundsAnswer(request)
		test.skip(answer.source !== 'dossiq', 'dossiq is absent or disabled; the snapshot test covers it')

		await openGroundsSettings(page)
		const section = page.getByTestId('woo-grounds-source')
		await expect(
			section.locator('a[href*="/apps/dossiq/settings/woo-refusal-grounds"]'),
		).toBeVisible()
		await expect(section.locator('button')).toHaveCount(0)
		await expect(section.locator('input, textarea, select')).toHaveCount(0)
	})

	test('the review table picker lists the grounds and stores the code', async ({
		page,
		request,
	}) => {
		test.skip(REVIEW_FOLDER === '', 'FILINQ_E2E_REVIEW_FOLDER is not set: no analysed documents to review')

		const answer = await groundsAnswer(request)
		const ground = answer.grounds.find((row: any) => row.code === CODE)
		expect(ground, `${CODE} in /api/grounds`).toBeTruthy()

		await go(page, '/folder-anonymization')
		await page.locator('.folder-path-input').fill(REVIEW_FOLDER)
		await page.getByRole('button', { name: /Analy[sz]e Folder|Map analyseren/i }).click()

		const table = page.locator('.entity-table')
		await expect(table).toBeVisible({ timeout: 120_000 })
		const cell = table.locator('td.bases-cell').filter({
			has: page.locator('.vs__dropdown-toggle:not([aria-disabled="true"])'),
		}).first()
		test.skip((await cell.count()) === 0, 'no detected entity with a stored relation in that folder')

		await cell.locator('.vs__dropdown-toggle').click()
		const option = page.getByRole('option', { name: optionLabel(ground) })
		await expect(option).toBeVisible({ timeout: 10_000 })

		const saved = page.waitForRequest(
			(sent) =>
				sent.method() === 'PATCH'
				&& sent.url().includes('/api/anonymization/relations/'),
		)
		await option.click()
		const body = (await saved).postDataJSON()
		expect(body.bases).toContain(CODE)
	})
})

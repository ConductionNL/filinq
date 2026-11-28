/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * woo-metadata-worklist (REQ-WMW-001, -002, -003): a coordinator finds the
 * publication records that miss mandatory metadata, fills one value on a
 * selection, and an admin's default publisher prefills a new record.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The "Metadata incomplete" filter, the missing-metadata column, the row
 * selection, the "Set metadata" dialog, a record's History and the admin
 * "Publisher (TOOI identifier)" field are what the coordinator and the admin
 * use, so they are clicked and read. Seeding is not: the documents go in over
 * WebDAV, a publication is started with POST api/publications (the Publish
 * action on a document belongs to the publication pipeline, not to this
 * change), and the records' starting metadata is written with
 * PUT api/publications/{id}/metadata. Everything runs against the real server.
 *
 * The Woo category must come from OpenCatalogi's TOOI list. The first test
 * reads it from GET api/publications/categories and skips, naming why, when
 * the instance has no list to choose from.
 */

import type { APIRequestContext, Page } from '@playwright/test'

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

/** This run's folder of documents. */
const FOLDER = `${TEST_PREFIX}-woo-worklist`

/** A publisher identifier from the TOOI register. */
const PUBLISHER = 'https://identifier.overheid.nl/tooi/id/gemeente/gm0363'

/** How many records the bulk test fills at once. */
const BULK_COUNT = 40

/**
 * Seed a document and start a publication for it.
 *
 * @param request The request context.
 * @param token   The request token.
 * @param name    The document's file name, without extension.
 * @return The publication record.
 */
async function startPublication(
	request: APIRequestContext,
	token: string,
	name: string,
): Promise<Record<string, unknown>> {
	const seeded = await createDavFile(
		request,
		token,
		`${FOLDER}/${name}.txt`,
		`Besluit ${name}.`,
	)
	expect(seeded.status, `seeding ${name}`).toBeLessThan(300)
	const created = await request.post(`${API}/publications`, {
		headers: jsonHeaders(token),
		data: { documentFileRef: seeded.fileId },
	})
	expect(
		created.status(),
		`POST /api/publications -> ${await created.text()}`,
	).toBe(201)
	return await created.json()
}

/**
 * Write metadata on one publication record.
 *
 * @param request The request context.
 * @param token   The request token.
 * @param uuid    The record.
 * @param values  The fields to write; an empty string clears one.
 * @return The record as saved.
 */
async function writeMetadata(
	request: APIRequestContext,
	token: string,
	uuid: string,
	values: Record<string, string>,
): Promise<Record<string, unknown>> {
	const saved = await request.put(`${API}/publications/${uuid}/metadata`, {
		headers: jsonHeaders(token),
		data: values,
	})
	expect(saved.status(), `PUT metadata ${uuid} -> ${await saved.text()}`).toBe(200)
	return await saved.json()
}

/**
 * Every mandatory field filled, under a given title.
 *
 * @param title    The official title.
 * @param category A known Woo category code.
 * @return The metadata.
 */
function completeMetadata(title: string, category: string): Record<string, string> {
	return {
		officieleTitel: title,
		wooCategory: category,
		documentsoort: 'besluit',
		publisher: PUBLISHER,
		creatiedatum: '2026-01-15',
		publicatiedatum: '2026-02-01',
	}
}

/**
 * The list row of a record, by its official title.
 *
 * @param page  The page.
 * @param title The title.
 * @return The row locator.
 */
function rowOf(page: Page, title: string) {
	return page.locator('tbody tr').filter({ hasText: title }).first()
}

/**
 * Open the publications list, optionally filtered to the incomplete records.
 *
 * @param page         The page.
 * @param onlyIncomplete Whether to tick "Metadata incomplete".
 * @return Nothing.
 */
async function openList(page: Page, onlyIncomplete: boolean): Promise<void> {
	await go(page, 'publications')
	if (onlyIncomplete) {
		const loaded = page.waitForResponse(
			(res) =>
				res.url().includes('/api/publications?')
				|| res.url().includes('incomplete'),
		)
		await page.getByTestId('publications-incomplete').check()
		await loaded.catch(() => undefined)
	}
}

test.describe('The Woo metadata worklist', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(300_000)

	let token = ''
	let category = ''

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect(await createDavFolder(page.request, token, FOLDER)).toBeLessThan(300)
		const categories = await page.request.get(`${API}/publications/categories`, {
			headers: jsonHeaders(token),
		})
		category = String((await categories.json()).results?.[0]?.code ?? '')
		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		await deleteDavPath(page.request, await harvestToken(page), FOLDER)
		await page.close()
	})

	// @e2e woo-metadata-worklist::a-woo-coordinator-finds-the-incomplete-records
	test('the incomplete filter lists the two records that miss fields and names them', async ({
		page,
	}) => {
		test.skip(
			category === '',
			'no Woo category list on this instance (OpenCatalogi absent)',
		)
		await page.goto('/index.php/apps/filinq/')
		const titles = {
			complete: `${TEST_PREFIX} volledig`,
			noCategory: `${TEST_PREFIX} zonder categorie`,
			noPublisher: `${TEST_PREFIX} zonder publisher`,
		}
		const complete = await startPublication(page.request, token, 'volledig')
		await writeMetadata(
			page.request,
			token,
			String(complete.uuid),
			completeMetadata(titles.complete, category),
		)
		const noCategory = await startPublication(
			page.request,
			token,
			'zonder-categorie',
		)
		await writeMetadata(page.request, token, String(noCategory.uuid), {
			...completeMetadata(titles.noCategory, category),
			wooCategory: '',
		})
		const noPublisher = await startPublication(
			page.request,
			token,
			'zonder-publisher',
		)
		await writeMetadata(page.request, token, String(noPublisher.uuid), {
			...completeMetadata(titles.noPublisher, category),
			publisher: '',
			documentsoort: '',
		})

		await openList(page, true)
		await expect(rowOf(page, titles.noCategory)).toBeVisible({ timeout: 30000 })
		await expect(rowOf(page, titles.noPublisher)).toBeVisible()
		await expect(rowOf(page, titles.complete)).toHaveCount(0)

		await expect(
			rowOf(page, titles.noCategory).locator('td[data-missing]'),
		).toHaveText('wooCategory')
		const missing = rowOf(page, titles.noPublisher).locator('td[data-missing]')
		await expect(missing).toHaveAttribute('data-missing', '2')
		await expect(missing).toContainText('publisher')
		await expect(missing).toContainText('documentsoort')
	})

	// @e2e woo-metadata-worklist::one-publisher-for-forty-records
	test('one publisher set on forty selected records fills them all and logs each', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')
		const titles: string[] = []
		const uuids: string[] = []
		for (let n = 1; n <= BULK_COUNT; n++) {
			const title = `${TEST_PREFIX} bulk ${String(n).padStart(2, '0')}`
			const record = await startPublication(page.request, token, `bulk-${n}`)
			await writeMetadata(page.request, token, String(record.uuid), {
				officieleTitel: title,
				publisher: '',
			})
			titles.push(title)
			uuids.push(String(record.uuid))
		}

		await openList(page, true)
		for (const title of titles) {
			await rowOf(page, title)
				.getByRole('checkbox', { name: 'Select' })
				.check()
		}
		await page.getByTestId('publications-set-metadata').click()

		const dialog = page.getByRole('dialog').filter({ hasText: 'Set metadata' })
		await expect(dialog).toBeVisible({ timeout: 15000 })
		await dialog.getByTestId('bulk-metadata-field').selectOption('publisher')
		await dialog
			.getByTestId('bulk-metadata-value')
			.locator('xpath=descendant-or-self::input')
			.fill(PUBLISHER)
		const bulk = page.waitForResponse(
			(res) =>
				res.url().endsWith('/api/publications/metadata')
				&& res.request().method() === 'PUT',
		)
		await dialog.getByRole('button', { name: 'Set metadata' }).click()
		const response = await bulk
		expect(response.status(), await response.text()).toBe(200)
		const outcome = await response.json()
		expect(outcome.updated).toHaveLength(BULK_COUNT)
		expect(outcome.skipped).toEqual([])

		await expect(page.getByTestId('publications-bulk-result')).toContainText(
			`${BULK_COUNT}`,
		)
		await openList(page, true)
		for (const title of titles) {
			const row = rowOf(page, title)
			if ((await row.count()) > 0) {
				await expect(row.locator('td[data-missing]')).not.toContainText(
					'publisher',
				)
			}
		}

		await go(page, `publications/${uuids[0]}`)
		const history = page.locator('.publications__log')
		await expect(history).toContainText('metadata_assembled', { timeout: 30000 })
		await expect(history).toContainText(process.env.NC_ADMIN_USER ?? 'admin')
	})

	// @e2e woo-metadata-worklist::an-admin-sets-the-default-publisher
	test('a default publisher set by the admin fills a new record', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')
		const before = await page.request.get(`${API}/settings`, {
			headers: jsonHeaders(token),
		})
		const previous = String((await before.json())['woo.default_publisher'] ?? '')

		try {
			await page.goto('/index.php/settings/admin/filinq', {
				waitUntil: 'domcontentloaded',
			})
			const field = page
				.getByTestId('settings-woo-default-publisher')
				.locator('xpath=descendant-or-self::input')
			await field.scrollIntoViewIfNeeded()
			await expect(field).toBeVisible({ timeout: 30000 })
			await field.fill(PUBLISHER)
			const saved = page.waitForResponse(
				(res) =>
					res.url().endsWith('/apps/filinq/api/settings')
					&& res.request().method() === 'POST',
			)
			await page.getByRole('button', { name: 'Save All Settings' }).click()
			expect((await saved).status()).toBe(200)

			await page.goto('/index.php/apps/filinq/')
			const record = await startPublication(
				page.request,
				token,
				'met-standaard-publisher',
			)
			expect(record.publisher).toBe(PUBLISHER)
			const title = `${TEST_PREFIX} standaard publisher`
			await writeMetadata(page.request, token, String(record.uuid), {
				officieleTitel: title,
			})

			await openList(page, false)
			const row = rowOf(page, title)
			await expect(row).toBeVisible({ timeout: 30000 })
			await expect(row.locator('td[data-missing]')).not.toContainText(
				'publisher',
			)
		} finally {
			await page.request.post(`${API}/settings`, {
				headers: jsonHeaders(token),
				data: { 'woo.default_publisher': previous },
			})
		}
	})
})

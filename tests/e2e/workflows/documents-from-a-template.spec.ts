/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * End-to-end regression for `documents-from-a-template`: the page layout is
 * versioned so an edit today does not rewrite a besluit sent in March, a case
 * leaves as one bundle with a manifest that names what was left out and why,
 * a periodic document renders from a saved view and fails loudly when the view
 * is gone, and a released document comes back for review on its date.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * None of this has a surface of its own yet: the layout authoring page and the
 * download-all leaf are still to come. So everything goes through the
 * documented endpoints, which is also where the guarantees live.
 *
 * ⚠️ The `@e2e` anchors name BOTH the change's delta spec and the canonical
 * spec it is synced into at archive time. Gate 19 scans `openspec/specs/` only.
 */

import { expect, test } from '@playwright/test'
import { API, harvestToken, jsonHeaders, TEST_PREFIX } from './_fixtures.ts'

/** The OpenRegister objects endpoint for the page layouts. */
const OR_LAYOUTS = '/index.php/apps/openregister/api/objects/filinq/pageLayout'

/** The OpenRegister objects endpoint for the generated documents. */
const OR_DOCUMENTS =
	'/index.php/apps/openregister/api/objects/filinq/generatedDocument'

test.describe('Documents from a template', () => {
	let token = ''
	const layoutName = `${TEST_PREFIX}-briefpapier`

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect(token, 'the suite needs a live request token').not.toBe('')

		const created = await page.request.post(OR_LAYOUTS, {
			headers: jsonHeaders(token),
			data: {
				name: layoutName,
				layoutVersion: 1,
				paperSize: 'A4',
				orientation: 'portrait',
				margins: { top: 35, right: 20, bottom: 25, left: 25 },
				header: 'Gemeente',
				footer: 'Pagina 1',
				firstPageDiffers: true,
				firstPageFooter: 'Postbus 1',
				active: true,
			},
		})
		expect(created.status(), 'the suite needs a layout to edit').toBeLessThan(
			300,
		)
		await page.close()
	})

	// @e2e openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md#a-layout-change-does-not-rewrite-history
	// @e2e openspec/specs/document-creatie-sjablonen/spec.md#a-layout-change-does-not-rewrite-history
	test('editing a layout writes a new version and leaves the old one readable', async ({
		page,
	}) => {
		const edited = await page.request.post(`${API}/page-layouts`, {
			headers: jsonHeaders(token),
			data: {
				name: layoutName,
				changes: { footer: 'Pagina {{page}} van {{pages}}' },
			},
		})
		expect(edited.status()).toBe(200)
		const next = await edited.json()
		expect(next.layoutVersion).toBe(2)

		const versions = await page.request.get(`${API}/page-layouts`, {
			headers: jsonHeaders(token),
			params: { name: layoutName },
		})
		expect(versions.status()).toBe(200)
		const body = await versions.json()
		expect(body.results.length).toBeGreaterThanOrEqual(2)

		// Version 1 is still there, still version 1, and no longer active.
		const one = body.results.find(
			(row: Record<string, unknown>) => row.layoutVersion === 1,
		)
		expect(one, 'the earlier version must stay readable').toBeTruthy()
		expect(one.footer).toBe('Pagina 1')
		expect(one.active).toBe(false)
	})

	// @e2e openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md#a-besluit-on-the-right-briefpapier
	// @e2e openspec/specs/document-creatie-sjablonen/spec.md#a-besluit-on-the-right-briefpapier
	test('editing a layout nobody declared is refused', async ({ page }) => {
		const refused = await page.request.post(`${API}/page-layouts`, {
			headers: jsonHeaders(token),
			data: { name: `${TEST_PREFIX}-bestaat-niet`, changes: { header: 'x' } },
		})
		expect(refused.status()).toBe(400)
	})

	// @e2e openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md#nothing-is-dropped-silently
	// @e2e openspec/specs/document-creatie-sjablonen/spec.md#nothing-is-dropped-silently
	test('a bundle says before it starts whether it fits, and its manifest says what did not', async ({
		page,
	}) => {
		const dossier = await page.request.post(
			'/index.php/apps/openregister/api/objects/filinq/dossier',
			{
				headers: jsonHeaders(token),
				data: { title: `${TEST_PREFIX}-bundel` },
			},
		)
		expect(dossier.status()).toBeLessThan(300)
		const created = await dossier.json()
		const id = created?.['@self']?.id || created?.id || ''
		expect(id).not.toBe('')

		const preflight = await page.request.get(`${API}/case-archive/preflight`, {
			headers: jsonHeaders(token),
			params: { register: 'filinq', schema: 'dossier', id, ceiling: 1 },
		})
		expect(preflight.status()).toBe(200)
		const flight = await preflight.json()
		expect(flight).toHaveProperty('exceedsCeiling')
		expect(flight).toHaveProperty('ceilingBytes')

		const manifest = await page.request.post(`${API}/case-archive/manifest`, {
			headers: jsonHeaders(token),
			data: { register: 'filinq', schema: 'dossier', id, ceiling: 1 },
		})
		expect(manifest.status()).toBe(200)
		const body = await manifest.json()
		expect(Array.isArray(body.included)).toBe(true)
		expect(Array.isArray(body.excluded)).toBe(true)
		for (const row of body.excluded) {
			expect(row.reason, 'every exclusion carries its reason').not.toBe('')
		}
	})

	// @e2e openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md#a-deleted-view-fails-loudly
	// @e2e openspec/specs/document-creatie-sjablonen/spec.md#a-deleted-view-fails-loudly
	test('a run against a view nobody has fails, naming the view', async ({
		page,
	}) => {
		const failed = await page.request.post(`${API}/periodic-documents/run`, {
			headers: jsonHeaders(token),
			data: {
				schedule: {
					name: `${TEST_PREFIX}-besluitenlijst`,
					viewSlug: `${TEST_PREFIX}-verwijderde-view`,
					templateId: 'geen',
				},
			},
		})
		expect(failed.status()).toBe(400)
		const body = await failed.json()
		expect(body.error).toContain(`${TEST_PREFIX}-verwijderde-view`)
	})

	// @e2e openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md#a-beleidsregel-comes-back
	// @e2e openspec/specs/document-creatie-sjablonen/spec.md#a-beleidsregel-comes-back
	test('a document past its review date is listed as due, and stays due until it is reviewed', async ({
		page,
	}) => {
		const created = await page.request.post(OR_DOCUMENTS, {
			headers: jsonHeaders(token),
			data: {
				templateName: `${TEST_PREFIX}-beleidsregel`,
				format: 'pdf',
				status: 'generated',
				generatedAt: '2025-01-01T10:00:00+00:00',
				reviewInterval: 'P12M',
				reviewDate: '2026-01-01T10:00:00+00:00',
			},
		})
		expect(created.status()).toBeLessThan(300)
		const body = await created.json()
		const uuid = body?.['@self']?.id || body?.id || ''
		expect(uuid).not.toBe('')

		const due = await page.request.get(`${API}/documents/due-for-review`, {
			headers: jsonHeaders(token),
		})
		expect(due.status()).toBe(200)
		const list = await due.json()
		expect(
			list.results.some((row: Record<string, unknown>) => row.uuid === uuid),
			'a document past its date is on the list',
		).toBe(true)

		const reviewed = await page.request.post(
			`${API}/documents/${uuid}/reviewed`,
			{
				headers: jsonHeaders(token),
			},
		)
		expect(reviewed.status()).toBe(200)
		const after = await reviewed.json()
		expect(after.reviewedAt).not.toBe('')

		const again = await page.request.get(`${API}/documents/due-for-review`, {
			headers: jsonHeaders(token),
		})
		const second = await again.json()
		expect(
			second.results.some((row: Record<string, unknown>) => row.uuid === uuid),
			'a reviewed document leaves the list',
		).toBe(false)
	})
})

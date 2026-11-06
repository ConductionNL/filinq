/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: entity-search.
 *
 * Drives the EntitySearch page (the Entity search menu entry) against route
 * handlers for api/entity-search, because the dev fixture's entity catalogue
 * is empty until documents are extracted. The server half (gate, tenant
 * scope, digest-only log, refusal when the log fails, unreadable files as a
 * count) is proven by tests/unit/Service/EntitySearch/EntitySearchServiceTest.php
 * and tests/unit/Controller/EntitySearchControllerTest.php.
 */

// @e2e openspec/specs/entity-search/spec.md#search-by-value-returns-matching-entities-with-counts
// @e2e openspec/specs/entity-search/spec.md#detail-shows-documents-dossiers-and-anonymisation-state
// @e2e openspec/specs/entity-search/spec.md#non-member-is-refused
// @e2e openspec/specs/entity-search/spec.md#search-produces-a-digest-only-log-entry
// @e2e openspec/specs/entity-search/spec.md#index-renders-results-with-filters

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { appUrl, dismissOverlays, waitForAppReady } from './_helpers.ts'

const PERSON = {
	uuid: 'e-1',
	type: 'PERSON',
	value: 'Jan de Vries',
	category: 'personal_data',
	occurrences: 3,
}
const IBAN = {
	uuid: 'e-2',
	type: 'IBAN',
	value: 'NL91ABNA0417164300',
	category: 'financial',
	occurrences: 1,
}

const DETAIL = {
	...PERSON,
	occurrenceCount: 3,
	noAccess: 1,
	other: [],
	documents: [
		{
			fileId: 11,
			name: 'aanvraag.pdf',
			path: '/Zaken/aanvraag.pdf',
			dossier: { uuid: 'd-1', name: 'Bezwaar 2026-14' },
			anonymisation: { state: 'anonymised', counterpartFileId: 21 },
			riskLevel: 'high',
			occurrences: [
				{ confidence: 0.97, anonymized: false, detectionMethod: 'presidio' },
			],
		},
		{
			fileId: 12,
			name: 'besluit.docx',
			path: '/Zaken/besluit.docx',
			dossier: null,
			anonymisation: { state: 'none', counterpartFileId: null },
			riskLevel: 'low',
			occurrences: [
				{ confidence: 0.91, anonymized: false, detectionMethod: 'presidio' },
			],
		},
	],
}

/**
 * Answer the entity search API, recording the query strings the page sent.
 *
 * @param page The page
 * @param sent Collects the request URLs
 * @param allowed Whether the access route says yes
 */
async function api(page: Page, sent: string[], allowed = true): Promise<void> {
	await page.route('**/apps/filinq/api/entity-search**', (route) => {
		const url = new URL(route.request().url())
		sent.push(url.pathname + url.search)
		if (!allowed) {
			return route.fulfill({
				status: 403,
				json: {
					error: 'You are not allowed to use the entity search.',
					reason: 'not_allowed',
				},
			})
		}
		if (url.pathname.endsWith('/access')) {
			return route.fulfill({ json: { allowed: true } })
		}
		if (url.pathname.endsWith('/e-1')) {
			return route.fulfill({ json: DETAIL })
		}
		const type = url.searchParams.get('type')
		const results = [PERSON, IBAN].filter((row) => !type || row.type === type)
		return route.fulfill({
			json: { results, total: results.length, limit: 25, offset: 0 },
		})
	})
}

/**
 * Open the entity search page.
 *
 * @param page The page
 */
async function open(page: Page): Promise<void> {
	await page.goto(await appUrl(page, 'entity-search'), {
		waitUntil: 'domcontentloaded',
	})
	await waitForAppReady(page)
	await dismissOverlays(page)
}

test.describe('entity search', () => {
	test('a search lists the value with its occurrence count, and sends the query only to the gated route', async ({
		page,
	}) => {
		// @e2e openspec/specs/entity-search/spec.md#search-by-value-returns-matching-entities-with-counts
		// @e2e openspec/specs/entity-search/spec.md#search-produces-a-digest-only-log-entry
		const sent: string[] = []
		await api(page, sent)
		await open(page)
		await page.getByLabel('Value to look for').fill('de vries')
		await page.getByRole('button', { name: 'Search' }).click()
		await expect(page.getByTestId('entity-search-results')).toContainText(
			'Jan de Vries',
		)
		await expect(page.getByTestId('entity-search-results')).toContainText('3')
		expect(
			sent.some(
				(u) =>
					u.includes('query=de+vries') || u.includes('query=de%20vries'),
			),
		).toBe(true)
	})

	test('the type filter narrows the results', async ({ page }) => {
		// @e2e openspec/specs/entity-search/spec.md#index-renders-results-with-filters
		const sent: string[] = []
		await api(page, sent)
		await open(page)
		await page.getByLabel('Type').click()
		await page.getByRole('option', { name: 'IBAN' }).click()
		await page.getByRole('button', { name: 'Search' }).click()
		await expect(page.getByTestId('entity-search-results')).toContainText(
			'NL91ABNA0417164300',
		)
		await expect(page.getByTestId('entity-search-results')).not.toContainText(
			'Jan de Vries',
		)
	})

	test('the detail lists documents with dossier and anonymisation state, and counts the unreadable one', async ({
		page,
	}) => {
		// @e2e openspec/specs/entity-search/spec.md#detail-shows-documents-dossiers-and-anonymisation-state
		const sent: string[] = []
		await api(page, sent)
		await open(page)
		await page.getByLabel('Value to look for').fill('vries')
		await page.getByRole('button', { name: 'Search' }).click()
		await page.getByText('Jan de Vries').click()
		const documents = page.getByTestId('entity-search-documents')
		await expect(documents).toContainText('aanvraag.pdf')
		await expect(documents).toContainText('Bezwaar 2026-14')
		await expect(documents).toContainText('Anonymised copy exists')
		await expect(page.getByTestId('entity-search-no-access')).toContainText('1')
	})

	test('a non-member sees the refusal and no menu entry', async ({ page }) => {
		// @e2e openspec/specs/entity-search/spec.md#non-member-is-refused
		const sent: string[] = []
		await api(page, sent, false)
		await open(page)
		await expect(page.getByRole('link', { name: 'Entity search' })).toHaveCount(
			0,
		)
		await page.getByLabel('Value to look for').fill('vries')
		await page.getByRole('button', { name: 'Search' }).click()
		await expect(
			page.getByText('You are not allowed to use the entity search.'),
		).toBeVisible()
	})
})

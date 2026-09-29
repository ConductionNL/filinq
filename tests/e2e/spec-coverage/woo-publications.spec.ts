/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: the Publications page (woo-publicatie-pipeline).
 *
 * The publication API is answered by route handlers, so the page is tested
 * without OpenCatalogi, consent records or a redacted document on the
 * instance. The server rules behind each answer are covered by PHPUnit
 * (tests/unit/Service/Publication/, tests/unit/Controller/PublicationControllerTest.php).
 */

// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#missing-woo-category-blocks-handoff
// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#category-is-selected-from-the-tooi-list
// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#endpoint-absent
// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#empty-reason-is-refused
// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#log-timeline-shows-the-full-trail

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from './_helpers.ts'

// The view under test, named after its component file (gate-26 matches on the stem).
const PublicationsPage = 'publications/pub-1'

const CATEGORIES = [
	{ code: 'c_139c6280', label: 'Wetten en algemeen verbindende voorschriften' },
	{ code: 'c_8c840238', label: 'Adviezen' },
]

/**
 * Answer the publication API with one record.
 *
 * @param page     The page
 * @param record   The record GET returns
 * @param platform Whether OpenCatalogi is installed
 */
async function withRecord(
	page: Page,
	record: Record<string, unknown>,
	platform = true,
): Promise<void> {
	await page.route('**/apps/filinq/api/publications/categories', (route) =>
		route.fulfill({ json: { results: platform ? CATEGORIES : [] } }),
	)
	await page.route('**/apps/filinq/api/publications/pub-1', (route) =>
		route.fulfill({
			json: { uuid: 'pub-1', platformAvailable: platform, ...record },
		}),
	)
}

const ready = {
	documentFileRef: '42',
	status: 'ready',
	entitiesReviewed: true,
	consentClear: true,
	prohibitionsClear: true,
	readinessReasons: [],
	officieleTitel: 'Besluit 2025-017',
	publicatiedatum: '2026-10-01',
	log: [],
}

test.describe('woo publications page', () => {
	test('a missing category blocks the hand-off and says so', async ({ page }) => {
		// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#missing-woo-category-blocks-handoff
		await withRecord(page, ready)
		await go(page, PublicationsPage)
		await expect(page.getByText('Still missing: wooCategory')).toBeVisible()
		await expect(
			page.getByRole('button', { name: 'Hand off for publication' }),
		).toBeDisabled()
	})

	test('the category is chosen from the TOOI list, not typed', async ({
		page,
	}) => {
		// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#category-is-selected-from-the-tooi-list
		await withRecord(page, ready)
		await go(page, PublicationsPage)
		const select = page.getByLabel('Information category')
		await expect(select).toHaveJSProperty('tagName', 'SELECT')
		await expect(select.locator('option')).toHaveCount(CATEGORIES.length + 1)
		await select.selectOption('c_8c840238')
		await expect(
			page.getByRole('button', { name: 'Hand off for publication' }),
		).toBeEnabled()
	})

	test('without OpenCatalogi the hand-off is disabled and the page says why', async ({
		page,
	}) => {
		// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#endpoint-absent
		await withRecord(page, { ...ready, wooCategory: 'c_8c840238' }, false)
		await go(page, PublicationsPage)
		await expect(page.getByText('OpenCatalogi is not installed')).toBeVisible()
		await expect(
			page.getByRole('button', { name: 'Hand off for publication' }),
		).toBeDisabled()
	})

	test('a withdrawal without a reason cannot be sent', async ({ page }) => {
		// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#empty-reason-is-refused
		await withRecord(page, {
			...ready,
			wooCategory: 'c_8c840238',
			status: 'published',
		})
		await go(page, PublicationsPage)
		const withdraw = page.getByRole('button', { name: 'Withdraw publication' })
		await expect(withdraw).toBeDisabled()
		await page.getByLabel('Why is it withdrawn?').fill('Wrong version')
		await expect(withdraw).toBeEnabled()
	})

	test('the history shows every step', async ({ page }) => {
		// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#log-timeline-shows-the-full-trail
		const log = [
			'created',
			'readiness_evaluated',
			'handed_off',
			'published',
		].map((action, i) => ({
			uuid: 'log-' + i,
			action,
			actor: 'anna',
			timestamp: '2026-09-29T10:0' + i + ':00+00:00',
		}))
		await withRecord(page, { ...ready, status: 'published', log })
		await go(page, PublicationsPage)
		const history = page.locator('.publications__log li')
		await expect(history).toHaveCount(4)
		await expect(history.nth(2)).toContainText('handed_off')
	})
})

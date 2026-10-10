/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: accessible-redaction-output, the publication side.
 *
 * The publication record is answered by a route handler, so the page shows
 * what the server would record for a copy that lost its accessibility. The
 * server half (the gate, the recorded flag, the block mode and its override)
 * is proven by PublicationPipelineServiceTest.
 */

// @e2e openspec/specs/accessible-redaction-output/spec.md#degraded-redaction-warns-and-flags-at-clearance-by-default

import { expect, test } from '@playwright/test'
import { go } from './_helpers.ts'

test.describe('accessible-redaction-output', () => {
	test('a degraded copy is flagged on the publication and still ready by default', async ({
		page,
	}) => {
		// @e2e openspec/specs/accessible-redaction-output/spec.md#degraded-redaction-warns-and-flags-at-clearance-by-default
		let saved: Record<string, unknown> = {}
		const record = {
			uuid: 'pub-a11y',
			platformAvailable: true,
			log: [],
			status: 'ready',
			documentFileRef: '42',
			entitiesReviewed: true,
			consentClear: true,
			prohibitionsClear: true,
			readinessReasons: [],
			accessibilityState: 'degraded',
			officieleTitel: 'Besluit 2025-017',
			wooCategory: 'c_8c840238',
			publicatiedatum: '2026-09-29',
		}
		await page.route('**/apps/filinq/api/publications/categories', (route) =>
			route.fulfill({
				json: { results: [{ code: 'c_8c840238', label: 'Adviezen' }] },
			}),
		)
		await page.route(
			'**/apps/filinq/api/publications/pub-a11y**',
			async (route) => {
				if (route.request().method() === 'GET') {
					await route.fulfill({ json: { ...record, ...saved } })
					return
				}
				saved = { ...saved, ...(route.request().postDataJSON() || {}) }
				await route.fulfill({ json: {} })
			},
		)

		await go(page, 'publications/pub-a11y')

		await expect(page.locator('.publications__accessibility')).toContainText(
			'Accessibility lost',
		)
		const override = page.locator('#publication-accessibility-override')
		await expect(override).toBeVisible()
		await override.fill(
			'Origineel was al ontoegankelijk; toegankelijke versie volgt.',
		)
		await page.getByRole('button', { name: 'Save metadata' }).click()
		await expect
			.poll(() => saved.accessibilityOverrideReason)
			.toBe('Origineel was al ontoegankelijk; toegankelijke versie volgt.')
	})
})

/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The bottom-left app chrome, in a browser (ADR-114).
 *
 * gate-107 reads the manifest and can prove the entries are DECLARED. It
 * cannot prove they RENDER, and this programme has already produced three
 * defects of exactly that shape: an icon name that is not registered renders
 * NO glyph (not a fallback, not a console error), an entry whose `route` names
 * a page the app does not host renders a row that goes nowhere, and
 * `nav.includePersonalSettings: false` silently removed the entry that reaches
 * the user's notification preferences.
 *
 * The three reports are declarative `type: "dashboard"` pages over filinq's own
 * register, which adds a fourth failure mode no manifest gate can see: a widget
 * whose `source` names a schema, field or filter value that does not match
 * renders its card, its title and no value, silently. In THIS app the live risk
 * is CASE: signingRequest and signerRecord spell their statuses in upper case
 * (PENDING, COMPLETED) while anonymizationBatch and generatedDocument use lower
 * case. Scalar equality does not fold case, so a filter of "completed" against
 * COMPLETED counts zero and reports zero as if it were the answer.
 *
 * ⚠️ SCOPE EVERY SELECTOR TO `[data-testid="cn-nav"]`. An unscoped selector
 * also matches Nextcloud's own user menu, which is attached-but-hidden:
 * `waitFor({state:'attached'})` passes on it and the click never becomes
 * actionable, so the spec fails with "Target page has been closed" — a timeout
 * wearing a crash's clothes.
 *
 * ⚠️ SETTINGS ENTRIES ARE ATTACHED, NOT VISIBLE, inside a collapsed foldout.
 */

import { expect, test } from '@playwright/test'

const APP_BASE = '/apps/filinq'

test.describe('app chrome (ADR-114)', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto(`${APP_BASE}/`, { waitUntil: 'domcontentloaded' })
		await expect(page.locator('[data-testid="cn-nav"]')).toBeVisible({
			timeout: 30_000,
		})
	})

	test('the footer reads Documentation, Reports, Features & roadmap, each with a glyph', async ({
		page,
	}) => {
		const footer = page.locator(
			'[data-testid="cn-nav"] .cn-app-nav__footer-list',
		)
		await expect(footer).toBeAttached({ timeout: 15_000 })

		const rows = footer.locator('li')
		const texts = (await rows.allInnerTexts())
			.map((t) => t.trim())
			.filter(Boolean)

		// ORDER is the rule, not the numbers. This app ran Documentation at 90
		// and Features & roadmap at 91, which left no room between them, so the
		// roadmap moved to 100 rather than Reports being squeezed in.
		const seen = texts.filter((t) => /Documentation|Reports|roadmap/i.test(t))
		expect(seen.length).toBe(3)
		expect(seen[0]).toMatch(/Documentation/i)
		expect(seen[1]).toMatch(/Reports/i)
		expect(seen[2]).toMatch(/roadmap/i)

		// A glyph on every row. ChartBoxOutline had to be added to src/icons.js
		// for the Reports entry; without it the row renders a blank space where
		// the icon belongs and nothing complains.
		for (const row of await rows.all()) {
			await expect(
				row.locator('svg, .material-design-icon').first(),
			).toBeAttached()
		}
	})

	test('Reports lists the three reports', async ({ page }) => {
		const nav = page.locator('[data-testid="cn-nav"]')
		await nav.locator('[data-testid="cn-nav-entry-ReportsMenu"]').click()
		await expect(page).toHaveURL(/\/apps\/filinq\/reports(\?|$)/, {
			timeout: 15_000,
		})

		for (const label of ['Signing', 'Anonymisation', 'Documents produced']) {
			await expect(
				page.getByText(label, { exact: false }).first(),
			).toBeVisible({ timeout: 15_000 })
		}
	})

	test('the signing report renders real numbers, not empty cards', async ({
		page,
	}) => {
		// The point of this test. The three stats filter on PENDING,
		// IN_PROGRESS and COMPLETED — upper case, because that is how
		// signingRequest spells them. A lower-case filter would count zero and
		// the card would render a confident 0.
		await page.goto(`${APP_BASE}/reports/signing`)
		await expect(page.locator('[data-testid="cn-nav"]')).toBeVisible({
			timeout: 30_000,
		})
		await expect(
			page.getByText('Awaiting signature', { exact: false }).first(),
		).toBeVisible({ timeout: 30_000 })
		await expect(page.locator('main, .app-content').first()).toContainText(
			/\d/,
			{ timeout: 30_000 },
		)
	})

	test('the anonymisation report renders real numbers, not empty cards', async ({
		page,
	}) => {
		// Same shape, opposite case: anonymizationBatch spells its statuses in
		// lower case (review, completed, error).
		await page.goto(`${APP_BASE}/reports/anonymization`)
		await expect(page.locator('[data-testid="cn-nav"]')).toBeVisible({
			timeout: 30_000,
		})
		await expect(
			page.getByText('Waiting for review', { exact: false }).first(),
		).toBeVisible({ timeout: 30_000 })
		await expect(page.locator('main, .app-content').first()).toContainText(
			/\d/,
			{ timeout: 30_000 },
		)
	})

	test('the produced-documents report is reachable and titled', async ({
		page,
	}) => {
		await page.goto(`${APP_BASE}/reports/documents`)
		await expect(page).toHaveURL(/\/reports\/documents(\?|$)/, {
			timeout: 15_000,
		})
		await expect(
			page.getByText('Documents generated', { exact: false }).first(),
		).toBeVisible({ timeout: 30_000 })
	})

	test('the settings foldout carries Personal settings, Admin settings and Flows', async ({
		page,
	}) => {
		const nav = page.locator('[data-testid="cn-nav"]')

		await expect(nav.locator('[data-testid="cn-nav-settings"]')).toBeAttached({
			timeout: 15_000,
		})
		await expect(
			nav.locator('[data-testid="cn-nav-personal-settings"]'),
		).toBeAttached()
		await expect(
			nav.locator('[data-testid="cn-nav-entry-FlowsMenu"]'),
		).toBeAttached()

		const admin = nav.locator('[data-testid="cn-nav-admin-settings"]')
		await expect(admin).toBeAttached()
		await expect(admin).toHaveAttribute('href', /\/settings\/admin\/filinq$/)
	})
})

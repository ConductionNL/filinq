/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage tests — print-preview (Vue component surface).
 *
 * Behavioural coverage of the Print Preview page (/print-preview/:templateId?):
 * the "Print Preview: <title>" header and the Print / Download PDF/A / Close
 * actions. The render/PDF-A generation APIs are backend and carry @e2e
 * exclude in the spec.
 */

// @e2e openspec/specs/print-preview/spec.md#preview-with-inline-template

import { test, expect } from '@playwright/test'
import { attachConsoleGuard, go } from './_helpers'

// The view under test, named after the component file it covers. The route
// is unchanged — this makes the spec-to-component link readable in executable
// code rather than only in prose. gate-26 matches a page against its component
// stem, and the stem appeared only inside comments, so a view that HAS e2e
// coverage was reported as having none.
const PrintPreview = 'print-preview'

test.describe('print-preview — preview component UI', () => {
	test('Print Preview page renders its header and action buttons', async ({
		page,
	}) => {
		// @e2e openspec/specs/print-preview/spec.md#preview-with-inline-template
		const guard = attachConsoleGuard(page)
		await go(page, PrintPreview)
		await expect(page).toHaveURL(/\/apps\/docudesk\/print-preview/)

		// Without a templateId the inline path renders the default title "document".
		await expect(
			page.getByRole('heading', { name: /Print Preview/ }),
		).toBeVisible()

		// Action buttons from PrintPreview.vue. Scope "Close" to the page
		// body so it doesn't collide with the nav-toggle / modal close
		// buttons ("Close navigation", modal "Close").
		const pageBody = page.locator('[data-testid="cn-page"], #content').first()
		await expect(page.getByRole('button', { name: 'Print' })).toBeVisible()
		await expect(
			page.getByRole('button', { name: 'Download PDF/A' }),
		).toBeVisible()
		await expect(
			pageBody.getByRole('button', { name: 'Close', exact: true }),
		).toBeVisible()

		expect(guard.errors, `console errors: ${guard.errors.join(' | ')}`).toEqual(
			[],
		)
	})

	test('Print Preview "Close" button is interactive', async ({ page }) => {
		// @e2e openspec/specs/print-preview/spec.md#preview-with-inline-template
		await go(page, PrintPreview)
		const pageBody = page.locator('[data-testid="cn-page"], #content').first()
		const close = pageBody.getByRole('button', { name: 'Close', exact: true })
		await expect(close).toBeEnabled()
	})
})

/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage tests — document-signing (UI surface).
 *
 * Behavioural coverage of the Signing Requests page (/signing). The
 * create/sign/verify/audit pipeline is backend and carries @e2e exclude
 * in the spec; here we assert the list view renders its real content
 * (table with status/level/mode columns OR the empty-state) and that the
 * page can be reached via the left navigation.
 */

// @e2e openspec/specs/document-signing/spec.md#list-all-signing-requests
// @e2e openspec/specs/document-signing/spec.md#view-signing-request-status

import { test, expect } from '@playwright/test'
import { attachConsoleGuard, go, navClick } from './_helpers'

test.describe('document-signing — signing requests list UI', () => {
	test('Signing Requests page renders heading and a list or empty-state', async ({
		page,
	}) => {
		// @e2e openspec/specs/document-signing/spec.md#list-all-signing-requests
		const guard = attachConsoleGuard(page)
		await go(page, 'signing')
		await expect(page).toHaveURL(/\/apps\/filinq\/signing/)

		await expect(
			page.getByRole('heading', { name: 'Signing Requests' }),
		).toBeVisible()

		const table = page.locator('#content table, .app-content table').first()
		const empty = page
			.locator('.empty-content, [class*="empty-content"]')
			.filter({ hasText: 'No signing requests' })
			.first()
		await expect(table.or(empty)).toBeVisible()

		expect(guard.errors, `console errors: ${guard.errors.join(' | ')}`).toEqual(
			[],
		)
		expect(guard.server5xx, `5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('signing requests table exposes status / level / mode columns when populated', async ({
		page,
	}) => {
		// @e2e openspec/specs/document-signing/spec.md#view-signing-request-status
		await go(page, 'signing')
		const table = page.locator('#content table, .app-content table').first()
		if (await table.isVisible().catch(() => false)) {
			await expect(
				page.getByRole('columnheader', { name: 'Document' }),
			).toBeVisible()
			await expect(
				page.getByRole('columnheader', { name: 'Status' }),
			).toBeVisible()
			await expect(
				page.getByRole('columnheader', { name: 'Level' }),
			).toBeVisible()
			await expect(
				page.getByRole('columnheader', { name: 'Mode' }),
			).toBeVisible()
		} else {
			// Empty-state path: assert the explicit empty message is shown.
			await expect(
				page
					.locator('.empty-content, [class*="empty-content"]')
					.filter({ hasText: 'No signing requests' })
					.first(),
			).toBeVisible()
		}
	})

	test('Signing Requests is reachable via the left navigation', async ({
		page,
	}) => {
		// @e2e openspec/specs/document-signing/spec.md#list-all-signing-requests
		await go(page, '')
		await navClick(page, 'Signing Requests')
		await expect(page).toHaveURL(/\/apps\/filinq\/signing/)
		await expect(
			page.getByRole('heading', { name: 'Signing Requests' }),
		).toBeVisible()
	})
})

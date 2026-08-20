/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage tests — Features & roadmap page (UI).
 *
 * The Features & roadmap page is a manifest-declared, library-rendered
 * page (CnPageRenderer built-in features page-type). It is a navigable
 * DocuDesk view per the dashboard navigation requirement, so it is
 * covered here as part of the navigation surface.
 */

// @e2e openspec/specs/dashboard/spec.md#navigation-items-and-icons

import { test, expect } from '@playwright/test'
import { attachConsoleGuard, go, navClick } from './_helpers'

test.describe('dashboard — features & roadmap page', () => {
	test('Features & roadmap page renders its heading and actions', async ({
		page,
	}) => {
		// @e2e openspec/specs/dashboard/spec.md#navigation-items-and-icons
		const guard = attachConsoleGuard(page)
		await go(page, 'features-roadmap')
		await expect(page).toHaveURL(/\/apps\/docudesk\/features-roadmap/)

		await expect(page.getByRole('heading', { name: 'Features' })).toBeVisible()
		await expect(
			page.getByRole('button', { name: 'Show roadmap' }),
		).toBeVisible()
		await expect(
			page.getByRole('button', { name: 'Suggest feature' }),
		).toBeVisible()

		expect(guard.errors, `console errors: ${guard.errors.join(' | ')}`).toEqual(
			[],
		)
		expect(guard.server5xx, `5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('Features & roadmap is reachable via the left navigation', async ({
		page,
	}) => {
		// @e2e openspec/specs/dashboard/spec.md#navigation-items-and-icons
		await go(page, '')
		await navClick(page, 'Features & roadmap')
		await expect(page).toHaveURL(/\/apps\/docudesk\/features-roadmap/)
		await expect(page.getByRole('heading', { name: 'Features' })).toBeVisible()
	})
})

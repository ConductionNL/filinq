/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage tests — Features & roadmap page (UI).
 *
 * The Features & roadmap page is a manifest-declared, library-rendered
 * page (CnPageRenderer built-in features page-type). It is a navigable
 * Filinq view per the dashboard navigation requirement, so it is
 * covered here as part of the navigation surface.
 */

// @e2e openspec/specs/dashboard/spec.md#navigation-items-and-icons

import { expect, test } from '@playwright/test'
import { attachConsoleGuard, go, navClick } from './_helpers.ts'

test.describe('dashboard — features & roadmap page', () => {
	test('Features & roadmap page renders its heading and actions', async ({
		page,
	}) => {
		// @e2e openspec/specs/dashboard/spec.md#navigation-items-and-icons
		const guard = attachConsoleGuard(page)
		await go(page, 'features-roadmap')
		await expect(page).toHaveURL(/\/apps\/filinq\/features-roadmap/)

		await expect(page.getByRole('heading', { name: 'Features' })).toBeVisible()
		await expect(
			page.getByRole('button', { name: 'Show roadmap' }),
		).toBeVisible()
		await expect(
			// A LINK, not a button. nextcloud-vue 2.36.4 removed the in-product
			// suggestion modal (team decision 2026-09-04: the forge is where the
			// conversation happens), and the CTA is an anchor to the forge's
			// feature-request issue form now. An `<a href>` has role `link`.
			page.getByRole('link', { name: 'Suggest feature' }),
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
		await expect(page).toHaveURL(/\/apps\/filinq\/features-roadmap/)
		await expect(page.getByRole('heading', { name: 'Features' })).toBeVisible()
	})
})

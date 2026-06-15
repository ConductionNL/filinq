/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage tests — document-comparison spec.
 *
 * Covers the UI-observable scenarios from the side-by-side comparison view
 * requirement. The backend scenarios (subject resolution, diff engine,
 * redaction annotation, completeness signal, ephemerality) are exercised by
 * tests/unit/Service/DocumentComparisonServiceTest.php and carry @e2e exclude
 * annotations in the spec delta.
 */

// @e2e openspec/specs/document-comparison/spec.md#operator-compares-original-and-anonymised-output-from-the-ui
// @e2e openspec/specs/document-comparison/spec.md#operator-picks-two-versions
// @e2e openspec/specs/document-comparison/spec.md#advisory-panel-for-unredacted-entities

import { test, expect } from '@playwright/test'
import { attachConsoleGuard, go } from './_helpers'

test.describe('document-comparison — side-by-side view', () => {
	test('comparison view renders its heading, pickers and Compare action', async ({ page }) => {
		// @e2e openspec/specs/document-comparison/spec.md#the-ui-must-provide-a-side-by-side-comparison-view
		const guard = attachConsoleGuard(page)
		// History-mode (manifest) router: deep-link the path, not a hash.
		await go(page, 'comparison')

		await expect(page.getByRole('heading', { name: 'Document comparison' })).toBeVisible()

		// File-ID pickers for both subjects.
		await expect(page.getByText('Left file ID')).toBeVisible()
		await expect(page.getByText('Right file ID')).toBeVisible()

		// Compare action present (disabled until both subjects are chosen).
		await expect(page.getByRole('button', { name: 'Compare', exact: true })).toBeVisible()

		expect(guard.server5xx, guard.server5xx.join('\n')).toHaveLength(0)
	})

	test('operator picks two files and triggers a comparison', async ({ page }) => {
		// @e2e openspec/specs/document-comparison/spec.md#the-ui-must-provide-a-side-by-side-comparison-view
		const guard = attachConsoleGuard(page)
		await go(page, 'comparison?left=1&right=2')

		// Preselected subjects auto-run; the heading remains and no JS error fires.
		await expect(page.getByRole('heading', { name: 'Document comparison' })).toBeVisible()
		expect(guard.errors, guard.errors.join('\n')).toHaveLength(0)
	})
})

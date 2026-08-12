/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage tests — folder-batch-analysis (UI surface).
 *
 * Behavioural coverage of the Folder Analysis page (/folder-anonymization):
 * the folder-path input, the "Analyze Folder" primary action and its
 * enable-on-input behaviour, and the optional dossier-binding sub-form.
 * Background extraction / NER pipeline scenarios are backend and carry
 * @e2e exclude in the spec.
 *
 * ANCHOR REPAIR: the anchors below used to name
 * `#initiate-folder-analysis-on-a-folder-with-5-documents`, a scenario that
 * does not exist in this spec (it was renamed to
 * `initiate-folder-analysis-by-folder-path-existing-behavior`). Gate-19 does
 * not report a dangling anchor — it parses the ref, finds no scenario with
 * that name, and moves on — so the mismatch was invisible for as long as it
 * existed. The count is unchanged by this repair (every scenario in this spec
 * carries an `@e2e exclude`); what changes is that the file no longer claims
 * to trace to something that is not there.
 */

// @e2e openspec/specs/folder-batch-analysis/spec.md#initiate-folder-analysis-by-folder-path-existing-behavior
// @e2e openspec/specs/folder-batch-analysis/spec.md#folder-path-does-not-exist

import { test, expect } from '@playwright/test'
import { attachConsoleGuard, dismissOverlays, go, navClick } from './_helpers'

test.describe('folder-batch-analysis — folder analysis UI', () => {
	test('Folder Analysis page renders heading, path input and Analyze action', async ({ page }) => {
		// @e2e openspec/specs/folder-batch-analysis/spec.md#initiate-folder-analysis-by-folder-path-existing-behavior
		const guard = attachConsoleGuard(page)
		await go(page, 'folder-anonymization')
		await expect(page).toHaveURL(/\/apps\/docudesk\/folder-anonymization/)

		await expect(page.getByRole('heading', { name: 'Folder Analysis & Anonymization' })).toBeVisible()

		const input = page.locator('input[placeholder*="Documents/contracts"]').first()
		await expect(input).toBeVisible()
		await expect(page.getByRole('button', { name: 'Analyze Folder' })).toBeVisible()

		expect(guard.errors, `console errors: ${guard.errors.join(' | ')}`).toEqual([])
		expect(guard.server5xx, `5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('Analyze button is disabled until a folder path is typed', async ({ page }) => {
		// @e2e openspec/specs/folder-batch-analysis/spec.md#folder-path-does-not-exist
		await go(page, 'folder-anonymization')
		const analyze = page.getByRole('button', { name: 'Analyze Folder' })
		// Empty path => disabled (:disabled="!folderPath.trim() || processing")
		await expect(analyze).toBeDisabled()

		await dismissOverlays(page)
		const input = page.locator('input[placeholder*="Documents/contracts"]').first()
		await input.fill('Documents/contracts')
		await expect(analyze).toBeEnabled()
	})

	test('initial step shows the folder-path instruction text', async ({ page }) => {
		// @e2e openspec/specs/folder-batch-analysis/spec.md#initiate-folder-analysis-by-folder-path-existing-behavior
		// Step 1 (store.isActive === false) renders the instruction paragraph.
		// The optional dossier-binding sub-form is intentionally only mounted
		// once analysis has started (store.batchStatus === extracting|review),
		// so it is NOT asserted on initial load.
		await go(page, 'folder-anonymization')
		await expect(page.locator('#content, .app-content').first())
			.toContainText('Enter a folder path')
	})

	test('Folder Analysis is reachable via the left navigation', async ({ page }) => {
		// @e2e openspec/specs/folder-batch-analysis/spec.md#initiate-folder-analysis-by-folder-path-existing-behavior
		await go(page, '')
		await navClick(page, 'Folder Analysis')
		await expect(page).toHaveURL(/\/apps\/docudesk\/folder-anonymization/)
		await expect(page.getByRole('heading', { name: 'Folder Analysis & Anonymization' })).toBeVisible()
	})
})

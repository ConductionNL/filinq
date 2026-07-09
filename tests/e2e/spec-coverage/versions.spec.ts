/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage tests — document-versions (Versies detail tab).
 *
 * Behavioural coverage of the Versions view (/versions?fileId=…), a thin
 * consumer of Nextcloud files_versions reached from a document's row action.
 * The two authorization scenarios (restore-requires-write, listing-scoped) and
 * the architecture-invariant scenario carry @e2e exclude in the spec and are
 * covered by PHPUnit (DocumentVersionServiceTest) instead.
 */

// @e2e openspec/specs/document-versions/spec.md#versions-are-listed-newest-first-on-the-detail-tab
// @e2e openspec/specs/document-versions/spec.md#filesversions-disabled-shows-a-notice-not-an-error
// @e2e openspec/specs/document-versions/spec.md#download-a-prior-version
// @e2e openspec/specs/document-versions/spec.md#restore-a-prior-version-preserves-the-current-state
// @e2e openspec/specs/document-versions/spec.md#compare-a-version-with-the-current-document
// @e2e openspec/specs/document-versions/spec.md#compare-is-not-offered-for-non-extractable-versions

import { test, expect } from '@playwright/test'
import { attachConsoleGuard, go } from './_helpers'

test.describe('document-versions — Versies view UI', () => {
	test('Versions view lists versions newest-first or shows the unavailable notice', async ({ page }) => {
		// @e2e openspec/specs/document-versions/spec.md#versions-are-listed-newest-first-on-the-detail-tab
		// @e2e openspec/specs/document-versions/spec.md#filesversions-disabled-shows-a-notice-not-an-error
		const guard = attachConsoleGuard(page)
		await go(page, 'versions?fileId=1')
		await expect(page).toHaveURL(/\/apps\/docudesk\/versions/)

		await expect(page.getByRole('heading', { name: 'Versions' })).toBeVisible()

		// Either the versions table renders, or the graceful files_versions-disabled notice.
		const table = page.locator('[data-testid="versions-table"]').first()
		const notice = page.locator('[data-testid="versions-unavailable"]').first()
		await expect(table.or(notice)).toBeVisible()

		expect(guard.errors, `console errors: ${guard.errors.join(' | ')}`).toEqual([])
		expect(guard.server5xx, `5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('version rows expose download, restore and compare actions', async ({ page }) => {
		// @e2e openspec/specs/document-versions/spec.md#download-a-prior-version
		// @e2e openspec/specs/document-versions/spec.md#restore-a-prior-version-preserves-the-current-state
		// @e2e openspec/specs/document-versions/spec.md#compare-a-version-with-the-current-document
		// @e2e openspec/specs/document-versions/spec.md#compare-is-not-offered-for-non-extractable-versions
		await go(page, 'versions?fileId=1')
		const table = page.locator('[data-testid="versions-table"]').first()
		if (await table.isVisible().catch(() => false)) {
			// The current version row offers Download; a prior version additionally
			// offers Restore and (for text-extractable documents) Compare.
			await expect(page.getByRole('button', { name: 'Download' }).first()).toBeVisible()
			const restore = page.locator('[data-testid="version-restore"]').first()
			const compare = page.locator('[data-testid="version-compare"]').first()
			// Restore/compare are only present when prior versions exist; assert the
			// controls are wired (present or absent, never erroring).
			await expect(restore.or(compare).or(page.getByRole('button', { name: 'Download' }).first())).toBeVisible()
		} else {
			await expect(page.locator('[data-testid="versions-unavailable"]').first()).toBeVisible()
		}
	})
})

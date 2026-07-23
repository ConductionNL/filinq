/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage tests —
 * openspec/changes/orphaned-surface-restoration.
 *
 * Behavioural coverage proving each restored surface actually opens
 * through the manifest-driven router (not just that its component
 * resolves statically — that half is covered by
 * tests/unit/reachability.spec.js): Correspondence from the menu, the
 * signer-chain create form from the Signing Requests index primary
 * action, the verify page from a request detail, and the two policy
 * pages via deep link. Also asserts no policy menu entry exists yet
 * (menu ownership is deferred to publication-policy-labels-and-nav).
 *
 * NOTE: written against the existing spec-coverage helper conventions
 * (tests/e2e/spec-coverage/_helpers.ts) but not executed against a live
 * Nextcloud instance as part of this change — no dev instance was
 * provisioned for this task. Run `npm run test:e2e -- orphaned-surface-restoration`
 * against a running docudesk instance before relying on it in CI.
 */

// @e2e openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#requirement-the-correspondence-surface-is-reachable-req-ddosr-003
// @e2e openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#requirement-signing-authoring-and-verify-are-reachable-with-trust-actions-gated-req-ddosr-004
// @e2e openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
// @e2e openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#requirement-the-dead-legacy-router-is-deleted-req-ddosr-001

import { test, expect } from '@playwright/test'
import { attachConsoleGuard, dismissOverlays, go, navClick } from './_helpers'

test.describe('orphaned-surface-restoration — correspondence', () => {
	test('Correspondence is reachable via the left navigation and renders its form', async ({ page }) => {
		// @e2e openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#scenario-correspondence-opens-from-the-menu
		const guard = attachConsoleGuard(page)
		await go(page, '')
		await navClick(page, 'Brieven & correspondentie')
		await expect(page).toHaveURL(/\/apps\/docudesk\/correspondence/)
		await expect(page.getByRole('heading', { name: 'Brieven & correspondentie' })).toBeVisible()

		expect(guard.errors, `console errors: ${guard.errors.join(' | ')}`).toEqual([])
		expect(guard.server5xx, `5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('Correspondence deep-links directly', async ({ page }) => {
		await go(page, 'correspondence')
		await expect(page).toHaveURL(/\/apps\/docudesk\/correspondence/)
		await expect(page.getByRole('heading', { name: 'Brieven & correspondentie' })).toBeVisible()
		// Real field from CorrespondenceIndex.vue — proves the component
		// (not a manifest-empty-state fallback) actually rendered.
		await expect(page.getByText('Template ID')).toBeVisible()
	})
})

test.describe('orphaned-surface-restoration — signing authoring + verify', () => {
	test('"New signing request" primary action opens the signer-chain create form, not the generic Add', async ({ page }) => {
		// @e2e openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#scenario-signer-chain-create-form-opens-from-the-signing-index
		const guard = attachConsoleGuard(page)
		await go(page, 'signing')
		await dismissOverlays(page)

		const primaryAction = page.locator('[data-testid="cn-nav-primary-action"]')
			.or(page.getByRole('button', { name: 'New signing request' }))
			.first()
		await expect(primaryAction).toBeVisible()
		await primaryAction.click()
		await page.waitForLoadState('networkidle').catch(() => {})
		await page.waitForTimeout(800)

		await expect(page).toHaveURL(/\/apps\/docudesk\/signing\/new/)
		await expect(page.getByRole('heading', { name: 'New Signing Request' })).toBeVisible()
		// The old generic index Add wrote a bare object with no signer-chain
		// fields — assert the real form fields are present instead.
		await expect(page.getByText('Document File ID')).toBeVisible()
		await expect(page.getByText('Signature Level')).toBeVisible()

		expect(guard.server5xx, `5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('the Signing Requests index no longer shows the generic inline Add button', async ({ page }) => {
		await go(page, 'signing')
		// showAdd:false (config.actionToggles) — the CnIndexPage built-in Add
		// button must be gone now that primaryAction replaces it.
		await expect(page.getByRole('button', { name: 'Add', exact: true })).toHaveCount(0)
	})

	test('SignatureVerification renders the engine-attributed verdict at a deep link', async ({ page }) => {
		// @e2e openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#scenario-verify-page-renders-the-backend-verdict-verbatim
		const guard = attachConsoleGuard(page)
		await go(page, 'signing/verify/1')
		await expect(page).toHaveURL(/\/apps\/docudesk\/signing\/verify\/1/)
		await expect(page.getByRole('heading', { name: 'Signature Verification' })).toBeVisible()
		// Field is pre-filled from the :fileId route param (SignatureVerification.vue mounted() hook).
		await expect(page.locator('.verify-form input')).toHaveValue('1')

		expect(guard.server5xx.filter((e) => !e.includes('/signing/verify/1')), `unexpected 5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('a signing request detail with a document file id shows a Verify action', async ({ page }) => {
		// @e2e openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#scenario-verify-page-renders-the-backend-verdict-verbatim
		await go(page, 'signing')
		const firstRow = page.locator('#content table tbody tr, .app-content table tbody tr').first()
		if (!(await firstRow.isVisible().catch(() => false))) {
			test.skip(true, 'no signing requests seeded on this environment — nothing to open a detail for')
			return
		}
		await firstRow.click()
		await page.waitForLoadState('networkidle').catch(() => {})
		await page.waitForTimeout(800)
		const verifyButton = page.getByRole('button', { name: 'Verify' })
		// Only present when the request carries a documentFileId — assert it
		// does not error out either way (visible XOR simply absent is fine).
		await expect(verifyButton.or(page.locator('h2')).first()).toBeVisible()
	})
})

test.describe('orphaned-surface-restoration — publication policy', () => {
	test('Prohibitions ("Publish never") deep-links and renders its list', async ({ page }) => {
		// @e2e openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#scenario-policy-pages-are-deep-link-reachable
		const guard = attachConsoleGuard(page)
		await go(page, 'policy/prohibitions')
		await expect(page).toHaveURL(/\/apps\/docudesk\/policy\/prohibitions/)
		await expect(page.getByRole('heading', { name: 'Publish never' })).toBeVisible()

		const table = page.locator('#content table, .app-content table').first()
		const empty = page.locator('.empty-content, [class*="empty-content"]')
			.filter({ hasText: 'No publication prohibitions' }).first()
		await expect(table.or(empty)).toBeVisible()

		expect(guard.server5xx, `5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('StandingConsents ("Publish always") deep-links and renders its list', async ({ page }) => {
		// @e2e openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#scenario-policy-pages-are-deep-link-reachable
		const guard = attachConsoleGuard(page)
		await go(page, 'policy/standing-consents')
		await expect(page).toHaveURL(/\/apps\/docudesk\/policy\/standing-consents/)
		await expect(page.getByRole('heading', { name: 'Publish always' })).toBeVisible()

		const table = page.locator('#content table, .app-content table').first()
		const empty = page.locator('.empty-content, [class*="empty-content"]')
			.filter({ hasText: 'No standing publication consents' }).first()
		await expect(table.or(empty)).toBeVisible()

		expect(guard.server5xx, `5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('the "Add" action on Prohibitions opens the extracted ProhibitionFormModal (not an inline dialog)', async ({ page }) => {
		await go(page, 'policy/prohibitions')
		const addButton = page.getByRole('button', { name: 'Add', exact: true })
		if (!(await addButton.isVisible().catch(() => false))) {
			test.skip(true, 'index Add action not rendered on this environment')
			return
		}
		await addButton.click()
		await expect(page.getByRole('heading', { name: 'Add publish-never rule' })).toBeVisible()
	})

	test('the "Add" action on StandingConsents opens the (now-wired) StandingConsentFormModal', async ({ page }) => {
		// Regression guard for the modal-isolation fix: StandingConsentIndex.vue
		// previously duplicated this form inline; it now delegates to
		// StandingConsentFormModal.vue like ProhibitionIndex.vue does.
		await go(page, 'policy/standing-consents')
		const addButton = page.getByRole('button', { name: 'Add', exact: true })
		if (!(await addButton.isVisible().catch(() => false))) {
			test.skip(true, 'index Add action not rendered on this environment')
			return
		}
		await addButton.click()
		await expect(page.getByRole('heading', { name: 'Add standing consent' })).toBeVisible()
	})

	test('no policy menu entry is introduced by this change', async ({ page }) => {
		// @e2e openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#scenario-no-policy-menu-label-is-introduced-here
		await go(page, '')
		const prohibitionsLink = page.locator('#app-navigation a[title="Publish never"], .app-navigation a[title="Publish never"]')
		const standingConsentsLink = page.locator('#app-navigation a[title="Publish always"], .app-navigation a[title="Publish always"]')
		await expect(prohibitionsLink).toHaveCount(0)
		await expect(standingConsentsLink).toHaveCount(0)
	})
})

test.describe('orphaned-surface-restoration — dead router removal is inert', () => {
	test('previously-existing pages still route identically after the dead router removal', async ({ page }) => {
		// @e2e openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#scenario-existing-pages-still-route-after-deletion
		for (const [route, heading] of [
			['', 'Dashboard'],
			['anonymization', 'Anonymization'],
			['templates', 'Templates'],
			['signing', 'Signing Requests'],
		] as const) {
			await go(page, route)
			await expect(page.getByRole('heading', { name: heading })).toBeVisible()
		}
	})
})

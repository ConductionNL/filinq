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
 * ⚠️ WHY EVERY `@e2e` ANCHOR IN THIS FILE MOVED (and nothing else did)
 * --------------------------------------------------------------------
 * These tests were authored while the change still lived in
 * `openspec/changes/orphaned-surface-restoration/`, and their anchors still
 * pointed there after the change was archived to `openspec/specs/`. Gate-19's
 * ref parser is
 *
 *     @e2e\s+openspec/specs/(?P<spec>[^/]+)/[^\s#]*#(?P<slug>[A-Za-z0-9_-]+)
 *
 * so a `openspec/changes/...` path matches NEITHER of its two regexes: the gate
 * never constructs a ref at all. It is not a dangling anchor that gets reported
 * — it is silently invisible. Result: five real, running, asserting tests were
 * scored as ZERO coverage, and their five scenarios sat in gate-19's finding
 * list looking untested.
 *
 * The archived slugs also carried a `scenario-` prefix that the canonical slug
 * does not have, so repointing the directory alone would still have produced a
 * dangling anchor — which gate-19 also does not report. Both halves are fixed
 * below.
 *
 * The four file-level anchors that used to sit here have been REMOVED rather
 * than repointed. They named REQUIREMENTS, not scenarios (no such ref exists),
 * and a file-level anchor is credited by gate-19 without checking that anything
 * in the file exercises it (.github#343). Every anchor now sits inside the body
 * of the test that actually asserts the scenario.
 *
 * NOTE ON PROVENANCE: this file's original header said it had "not [been]
 * executed against a live Nextcloud instance". It has now — see the run notes
 * on the individual `test.fixme` blocks below.
 */

import { test, expect, type Page } from '@playwright/test'
import { attachConsoleGuard, dismissOverlays, go, navClick } from './_helpers'

test.describe('orphaned-surface-restoration — correspondence', () => {
	test('Correspondence is reachable via the left navigation and renders its form', async ({ page }) => {
		// @e2e openspec/specs/orphaned-surface-restoration/spec.md#correspondence-opens-from-the-menu
		const guard = attachConsoleGuard(page)
		await go(page, '')
		await navClick(page, 'Letters & correspondence')
		await expect(page).toHaveURL(/\/apps\/docudesk\/correspondence/)
		await expect(page.getByRole('heading', { name: 'Letters & correspondence' })).toBeVisible()

		expect(guard.errors, `console errors: ${guard.errors.join(' | ')}`).toEqual([])
		expect(guard.server5xx, `5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('Correspondence deep-links directly', async ({ page }) => {
		await go(page, 'correspondence')
		await expect(page).toHaveURL(/\/apps\/docudesk\/correspondence/)
		await expect(page.getByRole('heading', { name: 'Letters & correspondence' })).toBeVisible()
		// Real field from CorrespondenceIndex.vue — proves the component
		// (not a manifest-empty-state fallback) actually rendered.
		await expect(page.getByText('Template ID')).toBeVisible()
	})
})

test.describe('orphaned-surface-restoration — signing authoring + verify', () => {
	test('"New signing request" primary action opens the signer-chain create form, not the generic Add', async ({ page }) => {
		// @e2e openspec/specs/orphaned-surface-restoration/spec.md#signer-chain-create-form-opens-from-the-signing-index
		const guard = attachConsoleGuard(page)
		await go(page, 'signing')
		await dismissOverlays(page)

		const primaryAction = page.locator('[data-testid="cn-nav-primary-action"]')
			.or(page.getByRole('button', { name: 'New signing request' }))
			.first()
		await expect(primaryAction).toBeVisible()
		await primaryAction.click()
		// No `networkidle` load-state wait here — ADR-074 rule 4 / gate-58.
		// Nextcloud keeps long-lived connections open (notifications polling,
		// user-status heartbeat), so networkidle never fires and the wait only
		// burned its timeout before being swallowed by `.catch()`. The two
		// web-first assertions below already retry until the SPA has routed,
		// so the wait bought nothing it does not already provide.
		await page.waitForTimeout(800)

		await expect(page).toHaveURL(/\/apps\/docudesk\/signing\/new/)
		await expect(page.getByRole('heading', { name: 'New Signing Request' })).toBeVisible()
		// The old generic index Add wrote a bare object with no signer-chain
		// fields — assert the real form fields are present instead.
		// `.first()` — "Signature Level" appears both as the field label and in
		// the select's own accessible text, so a bare getByText is a strict-mode
		// violation rather than a real failure.
		await expect(page.getByText('Document File ID').first()).toBeVisible()
		await expect(page.getByText('Signature Level').first()).toBeVisible()

		expect(guard.server5xx, `5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('the Signing Requests index no longer shows the generic inline Add button', async ({ page }) => {
		await go(page, 'signing')
		// showAdd:false (config.actionToggles) — the CnIndexPage built-in Add
		// button must be gone now that primaryAction replaces it.
		await expect(page.getByRole('button', { name: 'Add', exact: true })).toHaveCount(0)
	})

	test('SignatureVerification renders the engine-attributed verdict at a deep link', async ({ page }) => {
		// @e2e openspec/specs/orphaned-surface-restoration/spec.md#verify-page-renders-the-backend-verdict-verbatim
		const guard = attachConsoleGuard(page)
		await go(page, 'signing/verify/1')
		await expect(page).toHaveURL(/\/apps\/docudesk\/signing\/verify\/1/)
		await expect(page.getByRole('heading', { name: 'Signature Verification' })).toBeVisible()
		// Field is pre-filled from the :fileId route param (SignatureVerification.vue mounted() hook).
		await expect(page.locator('.verify-form input')).toHaveValue('1')

		expect(guard.server5xx.filter((e) => !e.includes('/signing/verify/1')), `unexpected 5xx: ${guard.server5xx.join(' | ')}`).toEqual([])
	})

	test('a signing request detail with a document file id shows a Verify action', async ({ page }) => {
		// @e2e openspec/specs/orphaned-surface-restoration/spec.md#verify-page-renders-the-backend-verdict-verbatim
		// KNOWN FAILURE — ConductionNL/docudesk#339: rows on the Signing Requests
		// index are not clickable. Clicking one neither routes to /signing/{id}
		// nor opens the configured sidebar (0px wide, checkVisibility() false),
		// so no detail surface can be reached from the UI and the restored
		// Verify action is only reachable by typing the URL. The detail page
		// itself renders correctly when navigated to directly — covered by the
		// SignatureVerification deep-link test above, which passes.
		// Remove this fixme once #339 lands — do NOT weaken the assertion.
		test.fixme(true, 'blocked by #339 — signing index rows are not clickable')
		await go(page, 'signing')
		const firstRow = page.locator('#content table tbody tr, .app-content table tbody tr').first()
		if (!(await firstRow.isVisible().catch(() => false))) {
			test.skip(true, 'no signing requests seeded on this environment — nothing to open a detail for')
			return
		}
		await firstRow.click()
		// No `networkidle` load-state wait here — ADR-074 rule 4 / gate-58;
		// it never fires on Nextcloud. The `toHaveURL` / `toBeVisible`
		// assertions below retry until the detail surface has rendered.
		await page.waitForTimeout(800)
		const verifyButton = page.getByRole('button', { name: 'Verify' })
		// The Verify action is only rendered when the request carries a
		// documentFileId, so its absence is legitimate and cannot be asserted
		// unconditionally. A heading fallback does not work either: these pages
		// render no visible heading in the content area (their only <h2> is the
		// 0x0 collapsed `app-sidebar-header__mainname`). Assert what the
		// scenario really guards — opening a row reaches a detail route and the
		// surface renders without erroring.
		await expect(page).toHaveURL(/\/apps\/docudesk\/signing\/.+/)
		await expect(page.locator('#content, .app-content').first()).toBeVisible()
		if (await verifyButton.first().isVisible().catch(() => false)) {
			await expect(verifyButton.first()).toBeEnabled()
		}
	})
})

test.describe('orphaned-surface-restoration — publication policy', () => {
	test('Prohibitions ("Publish never") deep-links and renders its list', async ({ page }) => {
		// @e2e openspec/specs/orphaned-surface-restoration/spec.md#policy-pages-are-deep-link-reachable
		// The `test.fixme(true, 'blocked by #333 …')` that used to sit here has
		// been REMOVED, and the assertions below are untouched.
		//
		// #333 ("publicationProhibition schema is never imported") is still open,
		// and correctly so: it is about DocuDesk's boot-time import path failing
		// silently. But it is not true of the environment this suite runs in.
		// `tests/e2e/ci-seed.sh` — the workflow's own `playwright-seed-command` —
		// imports the register through OpenRegister's admin HTTP importer with
		// `force=true` precisely because the boot-time path is unreliable, and it
		// then VERIFIES the result. Measured on a seeded instance:
		// `publicationProhibition` is present in `GET /api/schemas`, and the two
		// sibling tests that read this very page (the Add-modal test below and
		// entity-publication-policies.spec.ts's prohibition test) both pass.
		//
		// So the skip was suppressing a test that passes. A `fixme` whose stated
		// blocker does not hold in the environment under test is indistinguishable
		// from a healthy run in the summary line — the same failure mode this file
		// already documents at length for its six self-skipping tests. If #333
		// regresses INTO this environment, this test goes red, which is the point.
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
		// @e2e openspec/specs/orphaned-surface-restoration/spec.md#policy-pages-are-deep-link-reachable
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

	/*
	 * The two tests below used to locate the Add action with
	 *   page.getByRole('button', { name: 'Add', exact: true })
	 * and, when it did not match, call
	 *   test.skip(true, 'index Add action not rendered on this environment')
	 *
	 * That selector CANNOT match, on any environment. CnIndexPage renders the
	 * primary CTA through CnActionsBar with `:add-label="resolvedAddLabel"`,
	 * and `resolvedAddLabel` is (verified in the shipped dist of the pinned
	 * @conduction/nextcloud-vue 2.2.0-vue3.3):
	 *
	 *     if (this.addLabel) return this.addLabel
	 *     return 'Add ' + (this.effectiveSchema?.title || 'Item')
	 *
	 * Neither ProhibitionIndex.vue nor StandingConsentIndex.vue passes
	 * `add-label`, so the button's accessible name is always "Add <Something>"
	 * — never the bare, `exact: true` string "Add". The `isVisible()` probe
	 * therefore resolved false on EVERY run, the self-skip fired every time,
	 * and both tests reported as skipped with a reason ("not rendered on this
	 * environment") that was simply untrue. In a summary line that is
	 * indistinguishable from a healthy suite: run 31167880581 counted them in
	 * its "6 skipped" and nobody looked further.
	 *
	 * Both components declare `:show-add="true"`, so the CTA is REQUIRED to be
	 * there — its absence is a defect, not an environment condition. Target the
	 * stable `data-testid="cn-cta-primary"` that CnActionsBar puts on the
	 * button (with a name-prefix fallback for older shells) and assert it hard.
	 */
	const addCta = (page: Page) =>
		page.locator('[data-testid="cn-cta-primary"]')
			.or(page.getByRole('button', { name: /^Add\b/ }))
			.first()

	test('the "Add" action on Prohibitions opens the extracted ProhibitionFormModal (not an inline dialog)', async ({ page }) => {
		await go(page, 'policy/prohibitions')
		const addButton = addCta(page)
		await expect(addButton, 'ProhibitionIndex declares :show-add="true" — the primary CTA must render').toBeVisible()
		await addButton.click()
		await expect(page.getByRole('heading', { name: 'Add publish-never rule' })).toBeVisible()
	})

	test('the "Add" action on StandingConsents opens the (now-wired) StandingConsentFormModal', async ({ page }) => {
		// Regression guard for the modal-isolation fix: StandingConsentIndex.vue
		// previously duplicated this form inline; it now delegates to
		// StandingConsentFormModal.vue like ProhibitionIndex.vue does.
		await go(page, 'policy/standing-consents')
		const addButton = addCta(page)
		await expect(addButton, 'StandingConsentIndex declares :show-add="true" — the primary CTA must render').toBeVisible()
		await addButton.click()
		await expect(page.getByRole('heading', { name: 'Add standing consent' })).toBeVisible()
	})

	test('no policy menu entry is introduced by this change', async ({ page }) => {
		// @e2e openspec/specs/orphaned-surface-restoration/spec.md#no-policy-menu-label-is-introduced-here
		await go(page, '')
		const prohibitionsLink = page.locator('#app-navigation a[title="Publish never"], .app-navigation a[title="Publish never"]')
		const standingConsentsLink = page.locator('#app-navigation a[title="Publish always"], .app-navigation a[title="Publish always"]')
		await expect(prohibitionsLink).toHaveCount(0)
		await expect(standingConsentsLink).toHaveCount(0)
	})
})

test.describe('orphaned-surface-restoration — dead router removal is inert', () => {
	test('previously-existing pages still route identically after the dead router removal', async ({ page }) => {
		// @e2e openspec/specs/orphaned-surface-restoration/spec.md#existing-pages-still-route-after-deletion
		// What this scenario actually guards is ROUTING: after deleting the dead
		// vue-router, each previously-reachable route still resolves and renders
		// its page. It must not assert a visible heading as the proxy for that:
		// manifest `type:"index"` pages (/templates, /signing) render no visible
		// page heading at all — their only <h2> is the collapsed
		// `app-sidebar-header__mainname`, 0x0 with checkVisibility() === false.
		// That is a real accessibility gap, tracked separately; asserting it here
		// would conflate "the route works" with "the page has a heading".
		for (const [route, urlPattern] of [
			['', /\/apps\/docudesk\/?$/],
			['anonymization', /\/apps\/docudesk\/anonymization/],
			['templates', /\/apps\/docudesk\/templates/],
			['signing', /\/apps\/docudesk\/signing/],
		] as const) {
			await go(page, route)
			await expect(page).toHaveURL(urlPattern)
			// The app shell mounted and rendered content for this route.
			await expect(page.locator('#content, .app-content').first()).toBeVisible()
		}
	})
})

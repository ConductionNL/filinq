/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Documentation screenshot capture suite — docudesk.
 *
 * This spec is *not* a regression test — it drives the DocuDesk UI
 * through the flows documented under `docs/tutorials/{user,admin}/*.md`
 * and writes a fresh PNG into `docs/static/screenshots/tutorials/<track>/`
 * for each step the markdown references.
 *
 * Run manually whenever the UI changes and tutorial screenshots need
 * to be refreshed:
 *
 *     NEXTCLOUD_URL=http://localhost:8080 \
 *       npx playwright test --project docs-capture
 *
 * Excluded from the default regression run via the `docs-capture`
 * project flag in `playwright.config.ts` so PR pipelines don't
 * reshoot screenshots on every push.
 *
 * Authentication: `playwright.config.ts` wires `globalSetup` (a one-time
 * Nextcloud login → storage state) and `use.storageState`, so the
 * `page` fixture here arrives already signed in.
 *
 * Routing note: DocuDesk's dashboard route is `/apps/docudesk` (no
 * trailing slash); adding the slash returns 404 because the Symfony
 * route registers `url: '/'` and the controller signature uses
 * `?string $getParameter`. Tracked separately — see docudesk#143.
 *
 * Mount-status note: at the time of bootstrap (2026-05-13) the Vue
 * mount point `<div id="docudesk"></div>` stayed empty on the dev
 * container (#143). Until that's fixed, the structural screenshots
 * below capture only the Nextcloud chrome — the markdown pages
 * reference them as expected, and the docs build's
 * `onBrokenMarkdownImages: 'warn'` keeps the build green.
 *
 * Data dependency: DocuDesk's list views render even with zero
 * objects (Documents / Templates / Consents / Signing all show an
 * empty state). The flow-detail screenshots (a signed PDF, a
 * fully-anonymised output, a consent past its earliest-publish date)
 * need real objects; until seed data lands those steps fall back to
 * the relevant list/empty-state view.
 *
 * Pattern reference: ADR-030 (hydra/openspec/architecture/).
 */

import { test, expect, type Page } from '@playwright/test'
import * as path from 'path'
import * as fs from 'fs'

const SHOT_ROOT = path.resolve(__dirname, '..', '..', 'docs', 'static', 'screenshots', 'tutorials')
const APP = '/apps/docudesk'

async function shoot(page: Page, track: 'user' | 'admin', file: string): Promise<void> {
	const dir = path.join(SHOT_ROOT, track)
	if (!fs.existsSync(dir)) {
		fs.mkdirSync(dir, { recursive: true })
	}
	await page.screenshot({ path: path.join(dir, file), fullPage: false, type: 'png' })
}

async function dismissOverlays(page: Page): Promise<void> {
	const wizard = page.locator('#firstrunwizard')
	if (await wizard.isVisible().catch(() => false)) {
		const close = wizard.getByRole('button', { name: /close|got it|finish|skip/i }).first()
		if (await close.isVisible().catch(() => false)) {
			await close.click().catch(() => {})
		} else {
			await page.keyboard.press('Escape').catch(() => {})
		}
		await wizard.waitFor({ state: 'hidden', timeout: 4000 }).catch(() => {})
	}
	const stray = page.locator('[role="dialog"]:not(#firstrunwizard)')
	if (await stray.first().isVisible().catch(() => false)) {
		await page.keyboard.press('Escape').catch(() => {})
		await page.waitForTimeout(300)
	}
}

/** Navigate to a DocuDesk (or absolute) route and settle. */
async function go(page: Page, route: string): Promise<void> {
	// Strip leading slash on the route so we never produce `//`.
	const cleaned = route.startsWith('/') ? route.slice(1) : route
	const url = route.startsWith('/apps/') || route.startsWith('/settings/')
		? route
		: cleaned === '' ? APP : `${APP}/${cleaned}`
	await page.goto(url).catch(() => { /* tolerate a 404 — caller decides */ })
	await page.waitForLoadState('networkidle').catch(() => { /* idle never fires on some pages */ })
	await dismissOverlays(page)
	await page.waitForTimeout(900)
}

async function captureCreateDialog(page: Page, track: 'user' | 'admin', file: string, label: RegExp): Promise<boolean> {
	const addBtn = page.getByRole('button', { name: label }).first()
	if (!(await addBtn.isVisible().catch(() => false))) {
		return false
	}
	await addBtn.click().catch(() => {})
	const dialog = page.locator('[role="dialog"]:not(#firstrunwizard)').first()
	await dialog.waitFor({ state: 'visible', timeout: 5000 }).catch(() => { /* no dialog */ })
	await page.waitForTimeout(400)
	await shoot(page, track, file)
	const cancel = dialog.getByRole('button', { name: /Cancel|Close/i }).first()
	if (await cancel.isVisible().catch(() => false)) {
		await cancel.click().catch(() => {})
	} else {
		await page.keyboard.press('Escape').catch(() => {})
	}
	await page.waitForTimeout(300)
	return true
}

test.beforeEach(async ({ page }) => {
	page.setViewportSize({ width: 1280, height: 800 })
})

// ---------------------------------------------------------------------------
// USER TRACK — see docs/tutorials/user/
// ---------------------------------------------------------------------------

test.describe('docs: user track', () => {
	test('U1 first-launch', async ({ page }) => {
		// docs/tutorials/user/01-first-launch.md
		await go(page, '')
		await shoot(page, 'user', '01-first-launch-01.png')
		await shoot(page, 'user', '01-first-launch-02.png')
		await shoot(page, 'user', '01-first-launch-03.png')
		await go(page, 'documents')
		await shoot(page, 'user', '01-first-launch-04.png')
		expect(page.url()).toContain('/apps/docudesk')
	})

	test('U2 upload-document', async ({ page }) => {
		// docs/tutorials/user/02-upload-document.md
		await go(page, 'documents')
		await shoot(page, 'user', '02-upload-document-01.png')
		const had = await captureCreateDialog(page, 'user', '02-upload-document-02.png', /Upload|Add/i)
		if (!had) {
			await shoot(page, 'user', '02-upload-document-02.png')
		}
		await go(page, 'documents')
		await shoot(page, 'user', '02-upload-document-03.png')
		await shoot(page, 'user', '02-upload-document-04.png')
	})

	test('U3 anonymise-document', async ({ page }) => {
		// docs/tutorials/user/03-anonymise-document.md
		await go(page, 'documents')
		await shoot(page, 'user', '03-anonymise-document-01.png')
		await go(page, 'anonymization')
		await shoot(page, 'user', '03-anonymise-document-02.png')
		await shoot(page, 'user', '03-anonymise-document-03.png')
		await shoot(page, 'user', '03-anonymise-document-04.png')
	})

	test('U4 create-template', async ({ page }) => {
		// docs/tutorials/user/04-create-template.md
		await go(page, 'templates')
		await shoot(page, 'user', '04-create-template-01.png')
		const had = await captureCreateDialog(page, 'user', '04-create-template-02.png', /Add Template|Add template|Add/i)
		if (!had) {
			await shoot(page, 'user', '04-create-template-02.png')
		}
		await go(page, 'templates')
		await shoot(page, 'user', '04-create-template-03.png')
		await shoot(page, 'user', '04-create-template-04.png')
	})

	test('U5 render-template', async ({ page }) => {
		// docs/tutorials/user/05-render-template.md
		await go(page, 'templates')
		await shoot(page, 'user', '05-render-template-01.png')
		await shoot(page, 'user', '05-render-template-02.png')
		await shoot(page, 'user', '05-render-template-03.png')
		await go(page, 'documents')
		await shoot(page, 'user', '05-render-template-04.png')
	})

	test('U6 request-consent', async ({ page }) => {
		// docs/tutorials/user/06-request-consent.md
		await go(page, 'documents')
		await shoot(page, 'user', '06-request-consent-01.png')
		await go(page, 'consents')
		await shoot(page, 'user', '06-request-consent-02.png')
		const had = await captureCreateDialog(page, 'user', '06-request-consent-03.png', /Add Consent|Request consent|Add/i)
		if (!had) {
			await shoot(page, 'user', '06-request-consent-03.png')
		}
		await go(page, 'consents')
		await shoot(page, 'user', '06-request-consent-04.png')
	})

	test('U7 signing-flow', async ({ page }) => {
		// docs/tutorials/user/07-signing-flow.md
		await go(page, 'documents')
		await shoot(page, 'user', '07-signing-flow-01.png')
		await go(page, 'signing')
		await shoot(page, 'user', '07-signing-flow-02.png')
		await shoot(page, 'user', '07-signing-flow-03.png')
		await shoot(page, 'user', '07-signing-flow-04.png')
	})

	test('U8 retention-policy', async ({ page }) => {
		// docs/tutorials/user/08-retention-policy.md
		await go(page, 'documents')
		await shoot(page, 'user', '08-retention-policy-01.png')
		await shoot(page, 'user', '08-retention-policy-02.png')
		await shoot(page, 'user', '08-retention-policy-03.png')
		await go(page, '')
		await shoot(page, 'user', '08-retention-policy-04.png')
	})
})

// ---------------------------------------------------------------------------
// ADMIN TRACK — see docs/tutorials/admin/
// ---------------------------------------------------------------------------

test.describe('docs: admin track', () => {
	test('A1 templates-library', async ({ page }) => {
		// docs/tutorials/admin/01-templates-library.md
		await go(page, 'templates')
		await shoot(page, 'admin', '01-templates-library-01.png')
		await shoot(page, 'admin', '01-templates-library-02.png')
		await shoot(page, 'admin', '01-templates-library-03.png')
		await shoot(page, 'admin', '01-templates-library-04.png')
	})

	test('A2 anonymisation-rules', async ({ page }) => {
		// docs/tutorials/admin/02-anonymisation-rules.md
		await go(page, '/settings/admin/docudesk')
		await shoot(page, 'admin', '02-anonymisation-rules-01.png')
		await shoot(page, 'admin', '02-anonymisation-rules-02.png')
		await shoot(page, 'admin', '02-anonymisation-rules-03.png')
		await shoot(page, 'admin', '02-anonymisation-rules-04.png')
	})

	test('A3 admin-settings', async ({ page }) => {
		// docs/tutorials/admin/03-admin-settings.md
		await go(page, '/settings/admin/docudesk')
		await shoot(page, 'admin', '03-admin-settings-01.png')
		await shoot(page, 'admin', '03-admin-settings-02.png')
		await shoot(page, 'admin', '03-admin-settings-03.png')
		await shoot(page, 'admin', '03-admin-settings-04.png')
		await shoot(page, 'admin', '03-admin-settings-05.png')
	})
})

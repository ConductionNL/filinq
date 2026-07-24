/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Shared helpers for the Gate-19 behavioural spec-coverage suite.
 *
 * `attachConsoleGuard` records genuine app-level console errors and 5xx
 * responses so individual specs can assert the page rendered cleanly. It
 * deliberately ignores Nextcloud-environment noise that is unrelated to
 * DocuDesk and present fleet-wide on this dev container:
 *   - user_status / heartbeat OCS endpoints (NC core, returning 500 here)
 *   - WebDAV (`remote.php/dav`) — the dev container's DAV stack is 500ing
 *     at the root, so DocuDesk's recent-documents fetch fails downstream.
 * Those are tracked as environment bugs in the run report, not DocuDesk
 * defects, and must not make a page-render assertion flap.
 */

import { type Page } from '@playwright/test'

const IGNORE = [
	'user_status',
	'/heartbeat',
	'remote.php/dav',
	'Failed to load user status',
	'Failed to load recent documents', // downstream of the DAV 500 above
	'Failed to fetch documents', // my-documents DAV-backed fetch, same root cause
	'favicon',
	// The browser emits a bare, URL-less "Failed to load resource: …" console
	// error for every non-2xx network response. The URL-bearing version is
	// captured separately by the `response` listener into `server5xx`, where
	// it CAN be attributed and filtered. Keeping this generic echo in
	// `errors` would make every env 5xx (user_status / DAV) indistinguishable
	// from an app error, so it is dropped here and assertions rely on
	// `server5xx` for HTTP failures and on real JS errors for the rest.
	'Failed to load resource',
]

export interface ConsoleGuard {
	errors: string[]
	server5xx: string[]
}

export function attachConsoleGuard(page: Page): ConsoleGuard {
	const guard: ConsoleGuard = { errors: [], server5xx: [] }
	page.on('console', (m) => {
		if (m.type() !== 'error') return
		const text = m.text()
		if (IGNORE.some((s) => text.includes(s))) return
		guard.errors.push(text.slice(0, 300))
	})
	page.on('pageerror', (e) => {
		const text = String(e)
		if (IGNORE.some((s) => text.includes(s))) return
		guard.errors.push(`pageerror: ${text.slice(0, 300)}`)
	})
	page.on('response', (r) => {
		if (r.status() < 500) return
		const url = r.url()
		if (IGNORE.some((s) => url.includes(s))) return
		guard.server5xx.push(`${r.status()} ${url}`)
	})
	return guard
}

export async function dismissOverlays(page: Page): Promise<void> {
	const wizard = page.locator('#firstrunwizard')
	if (await wizard.isVisible().catch(() => false)) {
		await page.keyboard.press('Escape').catch(() => {})
		await wizard.waitFor({ state: 'hidden', timeout: 4000 }).catch(() => {})
	}
	// The manifest shell auto-mounts a library "support" dialog
	// (data-testid-modal="cn-support-dialog") whose modal-mask overlays the
	// whole viewport and intercepts pointer events — it swallows nav clicks.
	// Close it via its own close button, falling back to Escape.
	const support = page.locator('[data-testid-modal="cn-support-dialog"]').first()
	for (let i = 0; i < 3 && await support.isVisible().catch(() => false); i++) {
		const close = support.locator('.modal-container__close, [aria-label="Close"]').first()
		if (await close.isVisible().catch(() => false)) {
			await close.click().catch(() => {})
		} else {
			await page.keyboard.press('Escape').catch(() => {})
		}
		await support.waitFor({ state: 'hidden', timeout: 3000 }).catch(() => {})
	}

	// The onboarding walkthrough ("Welcome to DocuDesk") renders a full-viewport
	// dim layer (.cn-walkthrough__dim--full) that intercepts pointer events, so
	// every left-navigation click fails with "subtree intercepts pointer events"
	// even though the target link is visible and enabled. Close it via its own
	// button; Escape alone does not always dismiss it.
	const walkthrough = page.locator('.cn-walkthrough, [aria-label="Welcome to DocuDesk"]').first()
	for (let i = 0; i < 3 && await walkthrough.isVisible().catch(() => false); i++) {
		const closeTour = page.getByRole('button', { name: /close tour/i }).first()
		if (await closeTour.isVisible().catch(() => false)) {
			await closeTour.click().catch(() => {})
		} else {
			await page.keyboard.press('Escape').catch(() => {})
		}
		await walkthrough.waitFor({ state: 'hidden', timeout: 3000 }).catch(() => {})
	}

	// Belt and braces: if any full-viewport dim layer survived the loops above,
	// a click would still be swallowed. Wait for it to detach rather than
	// letting the next action fail with a confusing interception error.
	await page.locator('.cn-walkthrough__dim--full')
		.waitFor({ state: 'detached', timeout: 3000 })
		.catch(() => {})
}

export async function go(page: Page, route: string): Promise<void> {
	const url = route ? `/apps/docudesk/${route}` : '/apps/docudesk'
	await page.goto(url)
	await page.waitForLoadState('networkidle').catch(() => {})
	await dismissOverlays(page)
	await page.waitForTimeout(800)
}

/** Click a left-hand app-navigation entry by its `title` attribute. */
export async function navClick(page: Page, label: string): Promise<void> {
	await dismissOverlays(page)
	// Match on the entry's title attribute — exact, so "Documentation" can't
	// collide with longer labels and "Dashboard" stays unambiguous.
	const link = page.locator(`#app-navigation a[title="${label}"], .app-navigation a[title="${label}"]`).first()
	await link.click()
	await page.waitForLoadState('networkidle').catch(() => {})
	await page.waitForTimeout(800)
}

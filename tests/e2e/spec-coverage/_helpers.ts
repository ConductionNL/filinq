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

/**
 * App root, `index.php`-prefixed.
 *
 * ⚠️ The prefix is load-bearing on CI and must not be dropped. The shared
 * `E2E Tests (Playwright)` workflow serves Nextcloud with `php -S 0.0.0.0:8080`
 * and NO router script, so nothing rewrites `/apps/docudesk` onto `index.php`
 * the way Nextcloud's `.htaccess` does under Apache. PHP's built-in server
 * resolves the path against the document root itself, finds no matching file,
 * and answers its OWN 404 page — "The requested resource /apps/docudesk was not
 * found on this server". That page has a `<body>`, is not `/login`, and carries
 * no `#header` and no `requesttoken` meta, so it fails every spec at a
 * *selector* while looking like an app that would not mount. (Measured: 37 of
 * 66 specs failed this way on run 30797589151.)
 *
 * `/index.php/apps/docudesk` is served correctly BOTH with and without URL
 * rewriting, so it is the portable form — the same reasoning that already put
 * `index.php` in `workflows/_fixtures.ts`'s API constant.
 */
export const APP = '/index.php/apps/docudesk'

/**
 * Wait until the DocuDesk SPA has actually mounted and painted its content
 * region.
 *
 * ⚠️ This replaces the `await page.waitForLoadState('networkidle').catch(() => {})`
 * that used to follow every navigation in this suite (ADR-074 rule 4 /
 * gate-58 e2e-networkidle). That call could never do what it looked like it
 * did: Nextcloud holds long-lived connections open — notifications polling,
 * the user-status heartbeat — so the network NEVER goes idle. The wait
 * therefore always ran to its own timeout and was then swallowed by
 * `.catch(() => {})`. It did not "let the app settle"; it burned ~30s per
 * navigation and then continued at exactly the moment it would have anyway,
 * leaving the assertions to race a possibly-unmounted app. Worse, the swallow
 * turned that timeout into a pass, so nothing ever reported it.
 *
 * The deterministic form of the same intent is two explicit waits:
 *  1. `#docudesk-app` — the mount point `templates/index.php` server-renders.
 *     Its presence also separates a real Nextcloud response from PHP's
 *     built-in-server 404 page (see the `APP` docblock above), which has a
 *     `<body>` and a non-`/login` URL and so satisfied several assertions.
 *  2. the content region the manifest shell renders inside it — present only
 *     once Vue has mounted and the active route has painted.
 *
 * Neither wait is swallowed. A page that never mounts must fail here, loudly
 * and at the navigation, instead of failing later at an assertion where it
 * reads like an application defect.
 *
 * @param page    The Playwright page.
 * @param timeout Budget for each of the two waits, in ms.
 * @return Resolves once the SPA has mounted and painted.
 */
export async function waitForAppReady(page: Page, timeout = 30_000): Promise<void> {
	await page.locator('#docudesk-app').waitFor({ state: 'attached', timeout })
	await page.locator('main, #app-content, .app-content, #content-vue').first()
		.waitFor({ state: 'visible', timeout })
}

/**
 * Wait until a *Nextcloud core* page (admin settings, Dashboard, Files) has
 * painted its authenticated content region.
 *
 * Same rationale as `waitForAppReady` — `networkidle` never fires on
 * Nextcloud — but these routes are not the DocuDesk SPA, so there is no
 * `#docudesk-app` to key on. `#content` is rendered by NC's *authenticated*
 * layout only; the guest/login layout does not have it, so this wait also
 * fails fast on a session that silently expired instead of letting a
 * `not.toHaveURL(/\/login/)` assertion decide it much later.
 *
 * @param page    The Playwright page.
 * @param timeout Wait budget in ms.
 * @return Resolves once the NC content region is visible.
 */
export async function waitForNcContentReady(page: Page, timeout = 30_000): Promise<void> {
	await page.locator('#content, #app-content, .app-content, main').first()
		.waitFor({ state: 'visible', timeout })
}

/**
 * The app base the SPA ROUTER actually uses, read from the running instance.
 *
 * ⚠️ `APP` above is the right entry point but the wrong ROUTER BASE, and the
 * difference is invisible until a test deep-links.
 *
 * `src/main.js:306` builds the router as
 * `createWebHistory(generateUrl('/apps/docudesk'))`, and Nextcloud's
 * `generateUrl` includes the `index.php` segment only when
 * `OC.config.modRewriteWorking` is FALSE:
 *
 *   - CI (`php -S`, no rewrite)  modRewriteWorking = false
 *                                -> router base `/index.php/apps/docudesk`
 *   - Apache with mod_rewrite    modRewriteWorking = true   (measured on a
 *     (any normal dev/prod NC)   `nextcloud:34-apache` rig)
 *                                -> router base `/apps/docudesk`
 *
 * So on Apache a navigation to `/index.php/apps/docudesk/custom-dictionaries`
 * lands on a path the router does not recognise and it falls back to the app
 * root. The failure is quiet and very easy to misread: the page renders fine,
 * the URL is still under `/apps/docudesk`, and only an assertion that names
 * the ROUTE catches it. Every assertion in this suite of the shape
 * `expect(page).toHaveURL(/\/apps\/docudesk/)` passes on the WRONG PAGE.
 * (Measured: three deep-link specs failed on Apache with
 *  `Received string: "http://localhost:8097/apps/docudesk/"`.)
 *
 * Hardcoding either form is therefore wrong on one of the two environments.
 * Ask the app instead: land once on `APP` — which is served correctly with and
 * without rewriting — and read the same `generateUrl` the router itself used.
 * On CI this resolves to exactly the previous hardcoded value, so the change is
 * a no-op there and a repair everywhere else.
 *
 * Cached per worker: one extra navigation per run, not per test.
 */
let cachedAppBase: string | null = null

async function resolveAppBase(page: Page): Promise<string> {
	if (cachedAppBase !== null) return cachedAppBase
	await page.goto(APP, { waitUntil: 'domcontentloaded' })
	await waitForAppReady(page)
	cachedAppBase = await page.evaluate(() => {
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		const oc = (window as any).OC
		return (oc?.generateUrl?.('/apps/docudesk') as string) || ''
	})
	// An empty answer means `OC` was not on the page — that is a broken load,
	// not a base to guess around. Fail loudly rather than silently reinstating
	// the hardcoded value and producing the exact bug this function exists to
	// remove.
	if (!cachedAppBase) {
		throw new Error(
			'Could not read OC.generateUrl("/apps/docudesk") from the running app. '
			+ `The page at ${APP} did not expose window.OC, so the SPA router base is unknown.`,
		)
	}
	return cachedAppBase
}

export async function go(page: Page, route: string): Promise<void> {
	const base = await resolveAppBase(page)
	const url = route ? `${base}/${route}` : base
	// Wait for `domcontentloaded`, not the default `load`. Nextcloud keeps
	// long-lived connections open (notifications polling, user-status
	// heartbeat), so on a busy instance the `load` event can be minutes late
	// or never fire at all — every navigation then failed with a 60s timeout
	// even though the page was interactive. `waitForAppReady` below then waits
	// on the app itself rather than on the network going quiet.
	await page.goto(url, { waitUntil: 'domcontentloaded' })
	await waitForAppReady(page)
	await dismissOverlays(page)
	await page.waitForTimeout(800)
}

/**
 * Build an absolute in-app URL for a route, using the router's real base.
 *
 * Use this instead of `${APP}/${route}` whenever a spec needs `page.goto`
 * directly rather than `go()` — see `resolveAppBase` for why the hardcoded
 * form is wrong on a rewriting server.
 *
 * @param page  A page in the same context (used once to read the base).
 * @param route The in-app route, without a leading slash.
 * @return The absolute path to navigate to.
 */
export async function appUrl(page: Page, route: string): Promise<string> {
	const base = await resolveAppBase(page)
	return route ? `${base}/${route}` : base
}

/** Click a left-hand app-navigation entry by its `title` attribute. */
export async function navClick(page: Page, label: string): Promise<void> {
	await dismissOverlays(page)
	// Match on the entry's title attribute — exact, so "Documentation" can't
	// collide with longer labels and "Dashboard" stays unambiguous.
	const link = page.locator(`#app-navigation a[title="${label}"], .app-navigation a[title="${label}"]`).first()
	await link.click()
	// The click routes in-app. Re-assert the shell rather than waiting for
	// network silence (which never arrives): a manifest route that throws
	// during render unmounts the content region, so requiring it back is a
	// real check that the destination painted. Callers' `toHaveURL` /
	// `toBeVisible` expectations then retry over the route-specific markup.
	await waitForAppReady(page)
	await page.waitForTimeout(800)
}

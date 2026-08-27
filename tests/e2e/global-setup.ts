/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Playwright globalSetup — logs into Nextcloud once and persists the
 * resulting cookie jar / localStorage to `tests/e2e/.auth/admin.json`.
 * Every spec then reuses that storage state via the `use.storageState`
 * setting in playwright.config.ts, so individual tests start from an
 * authenticated session without each one paying the login cost.
 *
 * Pattern reference: ADR-030 (hydra/openspec/architecture/), mirrored
 * from decidesk's journeydoc setup.
 */

import { chromium, request, type FullConfig } from '@playwright/test'
import { execSync } from 'child_process'
import * as path from 'path'
import * as fs from 'fs'
import { resolveBaseUrl } from './base-url'

const AUTH_DIR = path.resolve(__dirname, '.auth')
const STORAGE_STATE = path.join(AUTH_DIR, 'admin.json')
const APP_ROOT = path.resolve(__dirname, '..', '..')
const BUNDLE_PATH = path.join(APP_ROOT, 'js', 'filinq-main.js')

/**
 * Ensure the webpack bundle exists before specs hit `/apps/filinq/`.
 *
 * On CI this is a HARD ERROR, not something to repair. The shared
 * `ConductionNL/.github/quality.yml` Playwright job now has a dedicated
 * "Build app frontend" step (`npm run build`) that runs before the specs,
 * so by the time we get here a missing `js/filinq-main.js` means that
 * step did not produce one. Silently rebuilding here would turn a broken
 * build into a green run with nothing to show for it.
 *
 * It also makes the bundle genuinely untestable: a positive control that
 * REMOVES the bundle to prove the specs depend on it gets healed right back
 * before the first spec runs, and the suite passes. (Observed on opencatalogi:
 * run 30791459241 passed 82/82 with the bundle deleted, because this function
 * rebuilt it — the control proved nothing until it was changed to truncate the
 * file instead.)
 *
 * Locally the rebuild stays, because there it is a genuine convenience: the
 * dev container typically mounts a *separate* checkout into
 * `custom_apps/filinq` and serves that build, and a fresh checkout has no
 * `js/` at all with nothing else to build it.
 */
function ensureBundleBuilt(): void {
	if (fs.existsSync(BUNDLE_PATH)) {
		return
	}
	if (process.env.CI === 'true' || process.env.GITHUB_ACTIONS === 'true') {
		throw new Error(
			`[playwright globalSetup] bundle missing at ${BUNDLE_PATH} on CI. `
				+ 'The workflow\'s "Build app frontend" step should already have produced it — '
				+ 'check that step rather than rebuilding here, because a rebuild would hide it.',
		)
	}
	// eslint-disable-next-line no-console
	console.log(
		`[playwright globalSetup] bundle missing at ${BUNDLE_PATH}; running 'npm run build' once…`,
	)
	execSync('npm run build', { cwd: APP_ROOT, stdio: 'inherit' })
}

/**
 * Wait until Nextcloud is actually serving requests.
 *
 * A shared dev instance is routinely mid-flight: another deploy flips it into
 * maintenance mode, an app version bump sets needsDbUpgrade (which makes NC
 * answer 503 on every route), or Postgres is still finishing crash recovery.
 * All three are transient and clear within minutes, but a single-shot check
 * turns them into a hard suite failure — observed three times on 2026-07-24.
 *
 * Poll until the instance reports installed, out of maintenance and not
 * awaiting a DB upgrade. Tune with E2E_HEALTH_TIMEOUT_MS (default 10 min).
 *
 * @param {string} baseURL Instance base URL.
 * @return {Promise<void>} Resolves once healthy; rejects on timeout.
 */
async function ensureNextcloudReachable(baseURL: string): Promise<void> {
	const deadline =
		Date.now() + Number(process.env.E2E_HEALTH_TIMEOUT_MS || 600_000)
	const ctx = await request.newContext()
	let last = 'no response yet'
	try {
		while (Date.now() < deadline) {
			try {
				const res = await ctx.get(`${baseURL}/status.php`, {
					failOnStatusCode: false,
				})
				if (res.ok()) {
					const body = await res.json().catch(() => ({}))
					if (
						body
						&& body.installed === true
						&& body.maintenance === false
						&& body.needsDbUpgrade === false
					) {
						return
					}
					last = `status.php = ${JSON.stringify(body)}`
				} else {
					// 503 while an app upgrade is pending, 500 while the DB recovers.
					last = `status.php returned ${res.status()}`
				}
			} catch (err) {
				last = `request failed: ${(err as Error).message}`
			}
			// eslint-disable-next-line no-await-in-loop
			await new Promise((resolve) => setTimeout(resolve, 5_000))
		}
		throw new Error(
			`Nextcloud at ${baseURL} did not become healthy in time — last seen: ${last}. `
				+ 'Check for a concurrent deploy (occ upgrade), maintenance mode, or a recovering database.',
		)
	} finally {
		await ctx.dispose()
	}
}

export default async function globalSetup(config: FullConfig): Promise<void> {
	// Take the resolved config value when Playwright supplies one, otherwise
	// resolve it the same way playwright.config.ts does. No `localhost:8080`
	// fallback — this used to disagree with the config's own resolver, so the
	// login went to one instance and the specs to another (see base-url.ts).
	const baseURL =
		(config.projects[0]?.use?.baseURL as string | undefined) ?? resolveBaseUrl()
	const username = process.env.NC_ADMIN_USER ?? 'admin'
	const password = process.env.NC_ADMIN_PASS ?? 'admin'

	ensureBundleBuilt()
	await ensureNextcloudReachable(baseURL)
	fs.mkdirSync(AUTH_DIR, { recursive: true })

	const browser = await chromium.launch()
	const context = await browser.newContext({ baseURL })
	const page = await context.newPage()

	// The instance can flip back into maintenance between the health check and
	// this navigation; re-check health and retry rather than failing the suite.
	for (let attempt = 1; ; attempt++) {
		try {
			await page.goto('/index.php/login')
			break
		} catch (err) {
			if (attempt >= 3) {
				throw err
			}
			await ensureNextcloudReachable(baseURL)
		}
	}
	// Nextcloud's login form is client-rendered and its markup has drifted
	// between releases: on NC 34 the fields carry `id="user"` / `id="password"`
	// but no `name` attribute, so a `input[name="user"]` selector never resolves
	// and globalSetup times out — which is why this suite could not run at all.
	// Match either shape, and wait for the field to be attached first.
	const userField = page.locator('input#user, input[name="user"]').first()
	const passwordField = page
		.locator('input#password, input[name="password"]')
		.first()
	await userField.waitFor({ state: 'visible', timeout: 30_000 })
	// The login form is a Vue app: the markup exists before its submit handler
	// is attached, so clicking too early silently does nothing and the page
	// simply stays on /login. Wait for the bundle to have mounted the form.
	//
	// This used to be a swallowed `networkidle` load-state wait, which
	// ADR-074 rule 4 forbids (gate-58 e2e-networkidle) for a good reason:
	// Nextcloud holds long-lived connections open (notifications polling,
	// user-status heartbeat), so `networkidle` NEVER fires. The wait therefore
	// always ran to its own timeout and was swallowed by `.catch()` — it did
	// not "let the bundle settle", it just burned the budget and then
	// proceeded at exactly the moment it would have anyway. Waiting on the
	// submit control instead is the deterministic form of the same intent:
	// the button is rendered by the same bundle that attaches the handler.
	await page
		.locator('button[type="submit"]')
		.first()
		.waitFor({ state: 'visible', timeout: 30_000 })
	await userField.fill(username)
	await passwordField.fill(password)
	// Bind the navigation wait BEFORE clicking, so a fast redirect cannot be
	// missed between the click returning and the wait starting.
	await Promise.all([
		page
			.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60_000 })
			.catch(() => {}),
		page.locator('button[type="submit"]').first().click(),
	])
	// Wait for the authenticated shell. NC 34 no longer guarantees the legacy
	// `#header` / `header.header` markup, so accept any banner-role header and
	// give the (slow, shared) instance room to finish the post-login redirect.
	await page.waitForURL((url) => /\/login(\?|$|\/)/.test(url.pathname) === false, {
		timeout: 60_000,
	})
	await page.waitForSelector('#header, header.header, header, [role="banner"]', {
		timeout: 60_000,
	})
	const currentUrl = page.url()
	if (/\/login(\?|$|\/)/.test(currentUrl)) {
		throw new Error(
			`Login appears to have failed — still on ${currentUrl}. `
				+ `Check NC_ADMIN_USER / NC_ADMIN_PASS (defaults admin/admin).`,
		)
	}

	// Bake the nc-vue support-dialog "seen" flag into the persisted storage
	// state so CnSupportDialog never auto-opens during a spec.
	//
	// `_helpers.ts:dismissOverlays()` already CLOSES this dialog, and that is
	// why Filinq's suite is currently clean — but closing it is inherently
	// racy and only reactive. CnAppRoot calls
	// `useSupportDialog(appId, { persistence: 'server' })`
	// (nextcloud-vue CnAppRoot.vue:1297); in server mode `visible` starts FALSE
	// and only flips true once
	// `GET /apps/filinq/api/preferences/support-dialog-seen` RESOLVES — i.e.
	// asynchronously, at an arbitrary point that can land AFTER dismissOverlays
	// has run and a spec has started clicking. The modal then mounts a
	// full-viewport `.modal-mask` that swallows pointer events and the click
	// retries until the test times out, which reads as a broken nav entry
	// rather than an overlay.
	//
	// That is not hypothetical: sibling repo opencatalogi hit exactly this on
	// run 31167878145 (`1 flaky`, call log naming
	// `data-testid-modal="cn-support-dialog" … subtree intercepts pointer
	// events`). globalSetup here never visits an app page, so the flag was
	// never in admin.json and the dialog was armed on every spec — Filinq has
	// simply been winning the race so far.
	//
	// `resolveServerVisibility()` checks the local flag FIRST and returns early
	// when it reads exactly '1' (useSupportDialog.js `hasRealFlag`), so seeding
	// it means the dialog is never scheduled at all. dismissOverlays() stays as
	// the fallback. No spec asserts on CnSupportDialog — it is an nc-vue
	// one-time nag unrelated to any Filinq scenario — so nothing is weakened.
	await page.evaluate(() => {
		try {
			window.localStorage.setItem('cn-support-dialog-shown:filinq', '1')
		} catch (e) {
			/* private mode / quota — the dismissOverlays fallback still applies */
		}
	})

	await context.storageState({ path: STORAGE_STATE })
	await browser.close()
}

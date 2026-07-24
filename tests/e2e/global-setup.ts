/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

const AUTH_DIR = path.resolve(__dirname, '.auth')
const STORAGE_STATE = path.join(AUTH_DIR, 'admin.json')
const APP_ROOT = path.resolve(__dirname, '..', '..')
const BUNDLE_PATH = path.join(APP_ROOT, 'js', 'docudesk-main.js')

/**
 * Ensure the webpack bundle exists before specs hit `/apps/docudesk/`.
 *
 * The shared `ConductionNL/.github/quality.yml` Playwright job runs
 * `npm ci` + `npx playwright install` before the spec run, but never
 * `npm run build`. On a fresh CI VM the `js/docudesk-main.js` artefact
 * doesn't exist, so the rendered page loads a 404 script tag and the
 * Vue app never mounts — every selector wait then times out.
 *
 * Locally, the dev container typically mounts a *separate* checkout
 * into `custom_apps/docudesk` and serves that build, so this step is
 * a no-op when the bundle is already present.
 */
function ensureBundleBuilt(): void {
	if (fs.existsSync(BUNDLE_PATH)) {
		return
	}
	// eslint-disable-next-line no-console
	console.log(`[playwright globalSetup] bundle missing at ${BUNDLE_PATH}; running 'npm run build' once…`)
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
	const deadline = Date.now() + Number(process.env.E2E_HEALTH_TIMEOUT_MS || 600_000)
	const ctx = await request.newContext()
	let last = 'no response yet'
	try {
		while (Date.now() < deadline) {
			try {
				const res = await ctx.get(`${baseURL}/status.php`, { failOnStatusCode: false })
				if (res.ok()) {
					const body = await res.json().catch(() => ({}))
					if (body && body.installed === true
						&& body.maintenance === false
						&& body.needsDbUpgrade === false) {
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
	const baseURL = (config.projects[0]?.use?.baseURL as string | undefined)
		?? process.env.NEXTCLOUD_URL
		?? process.env.NC_BASE_URL
		?? 'http://localhost:8080'
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
	const passwordField = page.locator('input#password, input[name="password"]').first()
	await userField.waitFor({ state: 'visible', timeout: 30_000 })
	// The login form is a Vue app: the markup exists before its submit handler
	// is attached, so clicking too early silently does nothing and the page
	// simply stays on /login. Let the login bundle settle before interacting.
	await page.waitForLoadState('networkidle').catch(() => {})
	await userField.fill(username)
	await passwordField.fill(password)
	// Bind the navigation wait BEFORE clicking, so a fast redirect cannot be
	// missed between the click returning and the wait starting.
	await Promise.all([
		page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60_000 }).catch(() => {}),
		page.locator('button[type="submit"]').first().click(),
	])
	// Wait for the authenticated shell. NC 34 no longer guarantees the legacy
	// `#header` / `header.header` markup, so accept any banner-role header and
	// give the (slow, shared) instance room to finish the post-login redirect.
	await page.waitForURL((url) => /\/login(\?|$|\/)/.test(url.pathname) === false, { timeout: 60_000 })
	await page.waitForSelector('#header, header.header, header, [role="banner"]', { timeout: 60_000 })
	const currentUrl = page.url()
	if (/\/login(\?|$|\/)/.test(currentUrl)) {
		throw new Error(
			`Login appears to have failed — still on ${currentUrl}. ` +
			`Check NC_ADMIN_USER / NC_ADMIN_PASS (defaults admin/admin).`,
		)
	}

	await context.storageState({ path: STORAGE_STATE })
	await browser.close()
}

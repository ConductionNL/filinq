/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage — the "Admin Warning When No Anonymiser Backend
 * Is Available" requirement of openspec/specs/anonymization/spec.md.
 *
 * WHY THESE SCENARIOS AND NOT THE OTHER 45
 * ----------------------------------------
 * `anonymization` is the app's largest spec (78 scenarios) and most of what is
 * uncovered in it is service-layer behaviour — `outputFormat` cascades,
 * conversion-backend fallbacks, the skip-marked-relation gate — none of which
 * a browser can observe. This requirement is the exception: it is defined
 * ENTIRELY in terms of what an admin sees on a page, so every assertion below
 * is the scenario's own wording rather than a proxy for it.
 *
 * PRECONDITION, AND WHY IT IS ASSERTED RATHER THAN ASSUMED
 * -------------------------------------------------------
 * Every scenario here is conditioned on `method = 'regex'` — i.e. NO anonymiser
 * backend installed. On a seeded CI instance that is the state (`GET
 * /api/settings` returns `anonymiserBackend.method = "regex"`,
 * `showWarning = true`), but it is a property of the ENVIRONMENT, not of the
 * app. If a backend were ever installed on the runner, the banner would
 * correctly disappear and these tests would go red for a reason that is not a
 * defect. So the precondition is read from the API and asserted first, in
 * `test.beforeAll`: a wrong environment fails ONCE, loudly, naming itself —
 * instead of six selector timeouts that read like a broken banner.
 *
 * Deliberately NOT a `test.skip()` on that precondition. A self-skip here would
 * be indistinguishable from a healthy run in the summary line, which is exactly
 * how six tests in this suite went green-by-skipping for months
 * (see the long comment in orphaned-surface-restoration.spec.ts).
 */

import { test, expect, type Page, type APIRequestContext } from '@playwright/test'
import { waitForNcContentReady, dismissOverlays, go } from './_helpers'

const SETTINGS = '/index.php/settings/admin/filinq'
const API_SETTINGS = '/index.php/apps/filinq/api/settings'

interface BackendState {
	method: string
	appApiInstalled: boolean
	warningDismissed: boolean
	showWarning: boolean
}

async function readBackendState(request: APIRequestContext): Promise<BackendState> {
	const res = await request.get(API_SETTINGS, {
		headers: { 'OCS-APIRequest': 'true' },
	})
	// Print the STATUS CODE, always. A 403 body parses as an empty object and
	// then reads as "the field is absent", which is a different finding.
	expect(res.status(), `GET ${API_SETTINGS} must answer 200`).toBe(200)
	const body = await res.json()
	return body.anonymiserBackend as BackendState
}

async function goSettings(page: Page): Promise<void> {
	await page.goto(SETTINGS, { waitUntil: 'domcontentloaded' })
	// Not `networkidle` — Nextcloud holds notification-polling and
	// user-status connections open, so it never fires (ADR-074 rule 4 /
	// gate-58). Wait for the region the assertions actually read.
	await waitForNcContentReady(page)
	await dismissOverlays(page)
}

/** The banner root. Scoped to its own class so nothing else can satisfy it. */
const banner = (page: Page) => page.locator('.anonymiser-backend-warning')

test.describe('anonymization — admin warning when no anonymiser backend is available', () => {
	test.beforeAll(async ({ request }) => {
		const state = await readBackendState(request)
		expect(
			state.method,
			'PRECONDITION: these scenarios are all conditioned on regex-only mode. '
				+ `This instance reports method="${state.method}", so an anonymiser backend IS `
				+ 'configured and the banner is CORRECTLY hidden. Nothing below is a defect — '
				+ 'remove the backend, or run this suite on a clean instance.',
		).toBe('regex')
	})

	test('the banner renders on the admin settings page with all four required elements', async ({
		page,
		request,
	}) => {
		// @e2e openspec/specs/anonymization/spec.md#admin-opens-filinq-admin-settings-with-no-backend-configured
		const state = await readBackendState(request)
		expect(
			state.warningDismissed,
			'admin must not have dismissed the warning yet',
		).toBe(false)

		await goSettings(page)
		await expect(banner(page)).toBeVisible()

		// The scenario names four things the banner MUST contain. Each is a
		// separate assertion so a partial regression names which half broke.
		await expect(
			banner(page).locator(
				'a[href="/settings/apps/discover/openanonymiser_light"]',
			),
			'deep link to the App Store entry for openanonymiser_light',
		).toHaveCount(1)
		await expect(
			banner(page).locator('a[href="/settings/apps/discover/openanonymiser"]'),
			'deep link to the App Store entry for openanonymiser',
		).toHaveCount(1)
		await expect(
			banner(page).locator('a[href="/settings/admin/openregister"]'),
			'link to OpenRegister settings for a custom endpoint',
		).toHaveCount(1)
		await expect(
			banner(page).getByRole('button', { name: 'Dismiss' }),
			'"Dismiss" action',
		).toBeVisible()
	})

	test('the banner renders at the top of the Filinq dashboard', async ({
		page,
	}) => {
		// @e2e openspec/specs/anonymization/spec.md#admin-opens-filinq-dashboard-with-no-backend-configured
		await go(page, '')
		await expect(banner(page)).toBeVisible()

		// "at the top of the dashboard" is the load-bearing half of this
		// scenario — a banner rendered below the fold satisfies "is shown" and
		// still fails the requirement. Assert the ordering against the
		// dashboard body rather than a pixel coordinate: the banner's box must
		// start above the dashboard page component's box.
		const bannerBox = await banner(page).boundingBox()
		const dashboardBox = await page
			.locator('.cn-dashboard-page, [class*="dashboard-page"]')
			.first()
			.boundingBox()
		expect(bannerBox, 'banner must have a rendered box').not.toBeNull()
		expect(
			dashboardBox,
			'dashboard body must have a rendered box',
		).not.toBeNull()
		expect(
			bannerBox!.y,
			'the banner must render ABOVE the dashboard body, not below it',
		).toBeLessThan(dashboardBox!.y)
	})

	test('the banner states that AppAPI must be installed first, without hiding the ExApp CTAs', async ({
		page,
		request,
	}) => {
		// @e2e openspec/specs/anonymization/spec.md#appapi-is-not-installed
		const state = await readBackendState(request)
		expect(
			state.appApiInstalled,
			'PRECONDITION: this scenario needs app_api absent. It is installed on this instance.',
		).toBe(false)

		await goSettings(page)
		await expect(banner(page)).toBeVisible()
		await expect(
			banner(page).locator('.anonymiser-backend-warning__appapi-line'),
			'the AppAPI notice line',
		).toBeVisible()
		await expect(
			banner(page).locator('.anonymiser-backend-warning__appapi-line'),
		).toContainText('AppAPI is not installed')
		// "AND the deep-link CTAs to the ExApp entries remain visible" — the
		// half of this scenario a naive implementation breaks by replacing the
		// body with the AppAPI notice.
		await expect(
			banner(page).locator(
				'a[href="/settings/apps/discover/openanonymiser_light"]',
			),
		).toBeVisible()
		await expect(
			banner(page).locator('a[href="/settings/apps/discover/openanonymiser"]'),
		).toBeVisible()
	})

	test('the OpenAnonymiser Light CTA navigates to the App Store discover page and installs nothing', async ({
		page,
	}) => {
		// @e2e openspec/specs/anonymization/spec.md#click-on-install-openanonymiser-light
		await goSettings(page)
		const cta = banner(page).locator(
			'a[href="/settings/apps/discover/openanonymiser_light"]',
		)
		await expect(cta).toBeVisible()

		// "no install action is triggered automatically" — record every request
		// the click produces and assert none of them is an app-install call.
		// A plain assertion on the destination URL cannot see a side effect.
		const installCalls: string[] = []
		page.on('request', (r) => {
			const u = r.url()
			if (
				/\/settings\/apps\/enable|\/apps\/[^/]+\/enable|app_api\/apps\/install/.test(
					u,
				)
			) {
				installCalls.push(`${r.method()} ${u}`)
			}
		})

		await cta.click()
		await page.waitForURL(/\/settings\/apps\/discover\/openanonymiser_light/, {
			timeout: 30_000,
		})
		await waitForNcContentReady(page)

		expect(
			installCalls,
			`the CTA must not trigger an install; observed: ${installCalls.join(' | ')}`,
		).toEqual([])
	})
})

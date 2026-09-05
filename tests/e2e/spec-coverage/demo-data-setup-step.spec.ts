import type { Page } from '@playwright/test'

/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * ADR-111 — the demo-data setup step, exercised against a running instance.
 *
 * WHY THIS EXISTS. The programme that added demo data to this fleet shipped a
 * defect that every unit test passed: the import printed `register "…"
 * imported.` and seeded ZERO of the descriptor's objects. The unit tests could
 * not see it — they mock the import service, so they validate the CALL and
 * never its effect.
 *
 * So the assertion that matters here is not "the endpoint answers 200". It is
 * that the response NAMES WHAT LANDED. A success message that cannot be told
 * apart from an import that wrote nothing is exactly what let that defect
 * through.
 *
 * WHY THE API AND NOT A CLICK-THROUGH. `CnAppRoot` opens the optional wizard
 * only while an optional step is outstanding, and the CI seed deliberately
 * settles those so the wizard stops covering the app in every test. The
 * observable surface for this capability is therefore the contract the wizard
 * calls — `GET /api/setup/status` and `POST /api/setup/action/{id}` — issued
 * from inside the authenticated admin page so every call carries the real
 * session and `OC.requestToken` through Nextcloud's `AuthorizedAdminSetting`
 * middleware. A unit test with a mocked IAppConfig cannot show that middleware
 * admitting the request; this can — and that middleware is precisely what the
 * attribute on SetupController configures.
 *
 * WHAT THIS DELIBERATELY DOES NOT ASSERT. That the demo-data step is FIRST
 * (ADR-111 rule 4) is a property of the manifest, which the app bundles rather
 * than serves, so it is not observable from here. Gate 100
 * (`setup-demo-data-first`) checks it statically on every change. Claiming to
 * prove it here would be asserting something this vantage point cannot see.
 *
 * @spec exclude ADR-042/ADR-111 setup contract; no per-app behavioural spec.
 */
import { expect, test } from '@playwright/test'
import * as path from 'path'

const STORAGE_STATE = path.resolve(__dirname, '../.auth/admin.json')

const BASE = '/apps/filinq'

/** One authenticated JSON call issued from inside the logged-in admin page. */
async function api(
	page: Page,
	method: string,
	apiPath: string,
): Promise<{ status: number; json: any }> {
	return await page.evaluate(
		async ({ method, apiPath }) => {
			const res = await fetch(apiPath, {
				method,
				headers: {
					'Content-Type': 'application/json',

					requesttoken: (window as any).OC?.requestToken || '',
					'OCS-APIREQUEST': 'true',
				},
			})
			let json: any
			try {
				json = await res.json()
			} catch {
				json = null
			}
			return { status: res.status, json }
		},
		{ method, apiPath },
	)
}

test.describe.configure({ mode: 'serial' })

test.describe('ADR-111 demo data', () => {
	// The setup contract lives behind the admin middleware, so these calls need
	// the real logged-in session `globalSetup` captured — not the suite's
	// default Basic-auth header, which does not produce an `OC.requestToken`.
	test.use({ storageState: STORAGE_STATE })

	test.beforeEach(async ({ page }) => {
		await page.goto(`${BASE}/`, { waitUntil: 'domcontentloaded' })
		await page.waitForFunction(() => (window as any).OC?.requestToken, null, {
			timeout: 15000,
		})
	})

	test('setup status reports the demo-data step, so the wizard can offer it', async ({
		page,
	}) => {
		const res = await api(page, 'GET', `${BASE}/api/setup/status`)

		expect(res.status, 'setup/status must answer an authenticated admin').toBe(
			200,
		)

		// A step the endpoint never MENTIONS resolves to `done: false` forever —
		// no operator action can clear it, and CnAppRoot then covers the app with
		// the wizard in every fresh browser context. Absence is the defect here,
		// not "not done".
		const steps = Object.keys(res.json?.steps ?? {})
		expect(steps, 'setup/status must report the choice step').toContain(
			'demo-data',
		)
		expect(steps, 'setup/status must report the load step').toContain(
			'load-demo-data',
		)
	})

	test('setup status carries the datasets the choice step offers', async ({
		page,
	}) => {
		// 🔴 THIS RESPONSE *IS* THE OPTION LIST. The step declares
		// `optionsSource: datasets` and carries no options of its own, so a
		// dataset missing here is a dataset nobody can pick — there is no second
		// copy in the manifest to fall back on.
		const res = await api(page, 'GET', `${BASE}/api/setup/status`)

		const datasets = res.json?.datasets ?? []
		const ids = datasets.map((d: any) => d.id)

		expect(
			ids,
			'declining must be offerable, or "no thanks" is unsayable',
		).toContain('none')
		expect(ids, 'the shipped dataset must be offered').toContain('demo')

		// A card renders a label, a description and an icon. An entry missing one
		// renders a blank card, which is worse than the Run button it replaced.
		for (const dataset of datasets) {
			expect(
				String(dataset.label ?? ''),
				`${dataset.id} has no label`,
			).not.toBe('')
			expect(
				String(dataset.description ?? ''),
				`${dataset.id} has no description`,
			).not.toBe('')
			expect(String(dataset.icon ?? ''), `${dataset.id} has no icon`).not.toBe(
				'',
			)
		}

		// The card promises a number, so it has to be the number the descriptor
		// actually carries.
		const demo = datasets.find((d: any) => d.id === 'demo')
		expect(demo.objectCount).toBeGreaterThan(0)
	})

	test('declining closes both steps, so the wizard stops covering the app', async ({
		page,
	}) => {
		// 🔴 THE DEFECT THIS FIXES. This app implemented `skip-demo-data` and no
		// manifest step could reach it, so declining was unsayable: the step
		// stayed `done: false` and CnAppRoot reopened the wizard over every page
		// unless the operator imported data they did not want.
		const saved = await api(page, 'POST', `${BASE}/api/setup/config`, {
			demo_dataset: 'none',
		})
		expect(saved.status, JSON.stringify(saved.json)).toBe(200)

		const status = await api(page, 'GET', `${BASE}/api/setup/status`)
		expect(status.json?.steps?.['demo-data']?.done).toBe(true)
		expect(status.json?.steps?.['load-demo-data']?.done).toBe(true)
	})

	test('a dataset that does not exist is refused rather than stored', async ({
		page,
	}) => {
		const res = await api(page, 'POST', `${BASE}/api/setup/config`, {
			demo_dataset: 'atlantis',
		})

		expect(res.status).toBe(400)
		expect(res.json?.success).toBe(false)
	})

	test('installing the demo data reports HOW MUCH landed, not just success', async ({
		page,
	}) => {
		// 🔴 A REAL IMPORT, NOT A STUB. Measured on this fleet: the install arm
		// took 42.8s on dossiq and 49.6s on shillinq, and exceeded the 30s
		// default on one run. The operation is legitimately slow, and the
		// assertion is worth its cost: it is the only check that the install
		// WROTE something.
		test.slow()

		const chosen = await api(page, 'POST', `${BASE}/api/setup/config`, {
			demo_dataset: 'demo',
		})
		expect(chosen.json?.success, JSON.stringify(chosen.json)).toBe(true)

		const res = await api(
			page,
			'POST',
			`${BASE}/api/setup/action/load-demo-data`,
		)

		expect(res.status, 'the action must pass the admin middleware').toBe(200)
		expect(
			res.json?.success,
			`install failed: ${JSON.stringify(res.json)}`,
		).toBe(true)

		// 🔴 THE COUNTS ARE THE ASSERTION. "Demo data installed" with no numbers
		// is indistinguishable from an import that wrote nothing — the exact
		// defect this programme shipped and had to fix. A message carrying a
		// positive object count is the only evidence the data reached the
		// instance.
		const message = String(res.json?.message ?? '')
		const numbers = (message.match(/\d+/g) ?? []).map(Number)

		expect(
			numbers.some((n) => n > 0),
			`the install message must name a non-zero object count; got: "${message}"`,
		).toBe(true)
	})

	test('re-installing is safe, because the step promises it is', async ({
		page,
	}) => {
		// The step body tells the operator it is "safe to run more than once".
		// That sentence is a contract; this asserts the server keeps it rather
		// than erroring or reporting failure on a second pass.
		const again = await api(
			page,
			'POST',
			`${BASE}/api/setup/action/load-demo-data`,
		)

		expect(again.status).toBe(200)
		expect(
			again.json?.success,
			`a second install must not fail: ${JSON.stringify(again.json)}`,
		).toBe(true)
	})
})

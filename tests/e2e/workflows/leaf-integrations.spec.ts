/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * End-to-end regression for `leaf-integrations`: six Filinq schemas opt into
 * the standard OpenRegister leaves, and Filinq contributes one leaf of its own
 * so the documents it holds for an object are visible where that object is.
 *
 * WHAT THIS FILE ASSERTS AND WHY IT IS THIS AND NOT MORE
 * -----------------------------------------------------
 * A leaf declaration is only real once OpenRegister has imported it. The import
 * runs at boot, answers HTTP 200 even when a schema was refused and skipped, and
 * writes nothing anyone looks at. So the assertion that matters here is the one
 * the unit tests cannot make: that the LIVE schema rows carry the declarations,
 * on a running instance, after the import.
 *
 * The second half is the one that has bitten this fleet before. A leaf whose
 * server half is registered and whose client bundle is missing is DARK, and the
 * cross-layer parity check compares the two halves without asking whether either
 * reached a page. So this file asks the page: it fetches the bundle's URL and
 * requires JavaScript back, not Nextcloud's error page wearing a 200.
 *
 * NC Mail, Calendar, Contacts and Deck are not installed on the shared test
 * instance, so the scenarios that need one carry an `@e2e exclude` in the spec
 * naming the app they wait on, rather than a test that would be skipped in
 * silence.
 */

import { expect, test } from '@playwright/test'

/** Every schema that opts into a leaf, and the leaves it declares. */
const DECLARED: Array<[string, string[]]> = [
	['signingRequest', ['mail', 'calendar']],
	['signerRecord', ['contacts']],
	['publicationConsent', ['mail', 'calendar', 'deck']],
	['correspondence', ['mail']],
	['generatedDocument', ['files']],
	['dossier', ['files', 'deck']],
]

test.describe('Leaf integrations', () => {
	// @e2e openspec/changes/leaf-integrations/specs/document-register/spec.md#register-imports-cleanly-with-the-leaf-declarations
	// @e2e openspec/specs/document-register/spec.md#register-imports-cleanly-with-the-leaf-declarations
	test('every declared leaf survives the register import', async ({ page }) => {
		await page.goto('/index.php/apps/filinq/')

		const response = await page.request.get(
			'/index.php/apps/openregister/api/schemas?limit=200',
			{ headers: { Accept: 'application/json' } },
		)
		expect(response.status(), 'the schema list must be readable').toBeLessThan(300)

		const body = await response.json()
		const rows: Array<Record<string, unknown>> = body?.results || body?.schemas || []
		expect(rows.length, 'the instance must have imported schemas').toBeGreaterThan(0)

		for (const [slug, leaves] of DECLARED) {
			const row = rows.find((candidate) => candidate.slug === slug)
			expect(row, `${slug} must exist after the import`).toBeTruthy()

			const configuration = (row?.configuration || {}) as Record<string, unknown>
			expect(
				configuration.linkedTypes,
				`${slug} must carry its leaves after the import, not only in the descriptor`,
			).toEqual(leaves)
		}
	})

	// @e2e openspec/changes/leaf-integrations/specs/publication-consent/spec.md#objection-email-becomes-a-consent-record
	// @e2e openspec/specs/publication-consent/spec.md#objection-email-becomes-a-consent-record
	test('the create-from-email map names real properties of its own schema', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')

		for (const [slug, fields] of [
			['publicationConsent', ['contactEmail', 'notes']],
			['correspondence', ['caseReference']],
		] as Array<[string, string[]]>) {
			const response = await page.request.get(
				`/index.php/apps/openregister/api/schemas?slug=${slug}`,
				{ headers: { Accept: 'application/json' } },
			)
			expect(response.status()).toBeLessThan(300)

			const body = await response.json()
			const row = (body?.results || [])[0]
			expect(row, `${slug} must exist`).toBeTruthy()

			const template = (row.configuration?.mailObjectTemplate || {}) as Record<string, unknown>
			expect(
				Object.keys(template).sort(),
				`${slug} must offer create-from-email`,
			).toEqual([...fields].sort())

			// An unknown key would have failed the whole import, so the live row
			// is where the cross-check is worth anything.
			for (const field of Object.keys(template)) {
				expect(
					Object.keys(row.properties || {}),
					`${slug}.${field} must be a property of ${slug}`,
				).toContain(field)
			}
		}
	})

	// @e2e openspec/changes/leaf-integrations/specs/document-register/spec.md#a-generated-documents-file-is-reachable-from-its-record
	// @e2e openspec/specs/document-register/spec.md#a-generated-documents-file-is-reachable-from-its-record
	test("the documents leaf's client half is on a page, not merely registered", async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')

		const bundle = await page.request.get('/custom_apps/filinq/js/filinq-leaves.js')
		expect(bundle.status(), 'the leaf bundle must be served').toBe(200)

		// 🔴 A missing app path answers 200 with Nextcloud's error page, so the
		// STATUS says nothing. The content type is the assertion.
		expect(
			bundle.headers()['content-type'] || '',
			'the leaf bundle must be JavaScript, not the SPA shell wearing a 200',
		).toContain('javascript')

		expect(await bundle.text()).toContain('filinq-documents')
	})

	// @e2e openspec/changes/leaf-integrations/specs/document-signing/spec.md#mail-app-absent
	// @e2e openspec/specs/document-signing/spec.md#mail-app-absent
	test('a record surface renders when the leaf apps are absent', async ({ page }) => {
		const errors: string[] = []
		page.on('pageerror', (error) => errors.push(error.message))

		await page.goto('/index.php/apps/filinq/signing-requests')
		await expect(page.locator('#filinq-app')).toBeVisible()

		await page.goto('/index.php/apps/filinq/consent')
		await expect(page.locator('#filinq-app')).toBeVisible()

		expect(errors, 'an absent leaf app must hide its leaf, never break the page').toEqual([])
	})

	// @e2e openspec/changes/leaf-integrations/specs/publication-consent/spec.md#agent-still-cannot-enumerate-consent-records
	// @e2e openspec/specs/publication-consent/spec.md#agent-still-cannot-enumerate-consent-records
	// @e2e openspec/changes/leaf-integrations/specs/document-signing/spec.md#contacts-leaf-does-not-widen-the-agent-surface
	// @e2e openspec/specs/document-signing/spec.md#contacts-leaf-does-not-widen-the-agent-surface
	test('the leaves do not put a signer or a consent record in front of an agent', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')

		const response = await page.request.get(
			'/index.php/apps/openregister/api/mcp/tools',
			{ headers: { Accept: 'application/json' } },
		)
		// An instance whose OpenRegister predates the MCP surface answers 404,
		// and then there is no agent surface to widen.
		test.skip(response.status() === 404, 'this OpenRegister exposes no MCP tool list')
		expect(response.status()).toBeLessThan(300)

		const listed = JSON.stringify(await response.json())
		expect(listed, 'signerRecord stays off the agent surface').not.toContain('signerRecord')
		expect(listed, 'publicationConsent stays off the agent surface').not.toContain(
			'publicationConsent',
		)
	})

	// The least privileged principal that should be refused: nobody at all. The
	// leaf reads the flat list through Filinq's own route, so an anonymous read
	// of that route is the probe worth making. A leaf that renders for a caller
	// with no session would be a disclosure surface, not a convenience.
	//
	// @e2e openspec/changes/leaf-integrations/specs/document-register/spec.md#dossier-files-are-linked-on-the-dossier-record
	// @e2e openspec/specs/document-register/spec.md#dossier-files-are-linked-on-the-dossier-record
	test('the list the leaf reads refuses a caller with no session', async ({ browser }) => {
		const anonymous = await browser.newContext({ storageState: { cookies: [], origins: [] } })
		const response = await anonymous.request.get(
			'/index.php/apps/filinq/api/case-documents/files?register=filinq&schema=dossier&id=1',
			{ headers: { Accept: 'application/json' }, maxRedirects: 0 },
		)

		expect(
			[302, 401, 403],
			`an anonymous read answered ${response.status()}`,
		).toContain(response.status())

		await anonymous.close()
	})
})

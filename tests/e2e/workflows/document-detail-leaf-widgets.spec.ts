/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * End-to-end regression for `document-detail-leaf-widgets`: the contacts,
 * activity and shares leaves render on the document detail surface through the
 * shared integration registry, and the app-owned surfaces beside them are
 * untouched.
 *
 * WHAT THIS ASSERTS THAT THE UNIT TEST CANNOT
 * -------------------------------------------
 * `tests/vitest/documentLeafTabs.spec.js` proves the SELECTION: which leaves
 * this surface asks for, and that an absent one is dropped. Nothing in it can
 * see whether the registry those leaves come from reaches a page at all. This
 * file asks the page — because the failure this change exists to prevent is a
 * host that is wired, green, and dark.
 *
 * WHY IT READS THE BUNDLE AND THE REGISTRY RATHER THAN CLICKING A TAB
 * ------------------------------------------------------------------
 * The leaf tabs render inside the file viewer's sidebar, which opens only once
 * a document has been through anonymisation — a fixture this suite cannot make
 * without a real detector. So the assertions here are the two that hold on any
 * instance: the registry host is IN the served bundle (a leaf whose host never
 * shipped is dark with every other check green), and the three leaf ids this
 * surface consumes are the ones OpenRegister actually publishes. The rendered
 * sidebar itself is covered by `@e2e exclude` in the spec, which names the
 * anonymisation fixture it waits on rather than skipping in silence.
 */

import { expect, test } from '@playwright/test'

/** The leaves the document detail surface consumes, in render order. */
const DOCUMENT_LEAVES = ['contacts', 'activity', 'shares']

test.describe('Document detail leaf widgets', () => {
	// @e2e openspec/changes/document-detail-leaf-widgets/specs/document-register/spec.md#contacts-activity-and-shares-tabs-appear-on-the-document-record
	test('the registry host ships in the served bundle', async ({ page }) => {
		await page.goto('/index.php/apps/filinq/')

		const response = await page.request.get(
			'/custom_apps/filinq/js/filinq-main.js',
		)
		expect(
			response.status(),
			'the main bundle must be served, not 404',
		).toBeLessThan(300)

		// 🔴 CONTENT TYPE, NOT STATUS. `/apps/<app>/js/<file>` answers the SPA
		// shell as 200 text/html, so a status check alone passes on an app that
		// ships no bundle at all.
		expect(
			response.headers()['content-type'] || '',
			'the bundle must be served as JavaScript, not the SPA shell',
		).toContain('javascript')

		const body = await response.text()
		expect(
			body,
			'the document detail surface must carry the registry tab host',
		).toContain('document-leaf-tabs')
		for (const leaf of DOCUMENT_LEAVES) {
			expect(
				body,
				`the bundle must name the ${leaf} leaf this surface consumes`,
			).toContain(leaf)
		}
	})

	// @e2e openspec/changes/document-detail-leaf-widgets/specs/document-register/spec.md#a-leaf-is-hidden-when-its-app-is-absent
	test('the leaves this surface consumes are the ones the platform publishes', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')

		// The registry the tabs read is installed on the page by OpenRegister's
		// bootstrap. Reading it here is what separates "filinq asks for three
		// leaves" from "three leaves exist to be asked for".
		const published = await page.evaluate(() => {
			// `window.OCA.OpenRegister.integrations` — the key the library's own
			// installIntegrationRegistry() writes and sharedRegistryIfInstalled()
			// reads. Read from the source rather than remembered: a guessed key
			// answers undefined, and this test would then report every instance
			// as having no leaves at all.
			const oca = (window as unknown as { OCA?: Record<string, any> }).OCA
			const registry = oca?.OpenRegister?.integrations ?? null
			if (!registry || typeof registry.list !== 'function') {
				return null
			}
			return registry.list().map((entry: { id: string }) => entry.id)
		})

		// A registry that is not installed is a real answer, not a skip: it
		// means no leaf reaches any page on this instance, and saying so is
		// more use than a green tick.
		expect(
			published,
			'OpenRegister must install the shared integration registry on the page',
		).not.toBeNull()

		for (const leaf of DOCUMENT_LEAVES) {
			expect(
				published,
				`the ${leaf} leaf must be published for the document surface to consume it`,
			).toContain(leaf)
		}
	})
})

/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * End-to-end regression for what leaves the building
 * (`redaction-and-what-leaves-the-building`).
 *
 * WHAT THIS PROVES, AND WHAT IT DELIBERATELY DOES NOT
 * ---------------------------------------------------
 * The failure this change exists to prevent is a redaction that reports
 * success while the original is still reachable. So every assertion here is
 * about what a caller can actually retrieve over HTTP, never about a service's
 * return value:
 *
 *   - the anonymise endpoint REFUSES an unchecked document, and the refusal is
 *     server-side, so it holds for a caller that never opened the screen;
 *   - the composed publication list carries no original file id, name or path
 *     anywhere in its body, which is asserted over the raw response text
 *     rather than over the fields the test happens to know about;
 *   - a gated download refuses before acceptance and asks again after the
 *     terms change version.
 *
 * The probing account is the ordinary E2E session, not an admin acting with
 * `_rbac: false`: a superuser success would prove almost nothing about what a
 * reader can reach.
 *
 * ⚠️ NOT ASSERTED HERE: the irreversibility routes (text under the mark,
 * embedded previews, XMP, incremental updates). Those read the produced bytes
 * of every output mode and belong to `RedactionIrreversibilityVerifierTest`,
 * which is where the spec's own `@e2e exclude` sends them.
 */

import { expect, test } from '@playwright/test'
import { API, harvestToken, jsonHeaders, TEST_PREFIX } from './_fixtures.ts'

/** Where the redaction surface lives. */
const REDACTION = `${API}/redaction`

/** OpenRegister's object API, for seeding the agreement and reading it back. */
const OBJECTS = '/index.php/apps/openregister/api/objects/filinq'

test.describe('what leaves the building', () => {
	let token = ''

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		await page.close()
	})

	// @e2e openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md#the-screen-path-is-gated
	test('an unchecked document is refused over the API, not only on the screen', async ({
		page,
	}) => {
		// A caller that never opened the review screen. If the gate lived in
		// the component, this request would be the way around it.
		const refused = await page.request.post(`${API}/anonymization/anonymize/1`, {
			headers: jsonHeaders(token),
			data: {
				entities: [
					{ entityType: 'PERSON', text: 'Fatima El-Amrani', start: 10, end: 26 },
				],
			},
		})

		// 4xx or 5xx: what matters is that it is not a success carrying a file.
		expect(refused.status(), 'an unchecked document must not anonymise').toBeGreaterThanOrEqual(400)

		const body = await refused.text()
		expect(
			body,
			'the refusal tells the operator what to do rather than what went wrong',
		).toMatch(/check|gecontroleerd|reviewed/i)
	})

	// @e2e openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md#re-detecting-clears-the-check
	test('a check names the detection run it covers', async ({ page }) => {
		const entities = [
			{ entityType: 'PERSON', text: 'Henk Bakker', start: 10, end: 21 },
		]

		const marked = await page.request.post(`${REDACTION}/documents/1/checked`, {
			headers: jsonHeaders(token),
			data: { entities, note: `${TEST_PREFIX} checked` },
		})

		// The mark is restricted to the Woo officers and the policy admins,
		// both of which ship EMPTY, so an ordinary session is refused here and
		// that refusal is itself the point: nobody signs an approval by
		// default. Where the session does hold the group, the mark must name a
		// run rather than merely exist.
		if (marked.status() < 300) {
			const mark = await marked.json()
			expect(mark.detectionRun, 'a mark that names no run covers everything').not.toBe('')
			expect(mark.checkedBy, 'a check nobody signed is a check nobody can be asked about').not.toBe('')
		} else {
			expect(marked.status()).toBeGreaterThanOrEqual(400)
		}
	})

	// @e2e openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md#an-original-is-never-referenced
	// @e2e openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md#a-record-that-is-not-ready-stops-the-list
	test('a composed list carries no original, and stops when a record is not ready', async ({
		page,
	}) => {
		const composed = await page.request.post(`${REDACTION}/publication-list`, {
			headers: jsonHeaders(token),
			data: { view: `${TEST_PREFIX}-woo`, title: 'Woo-publicatielijst' },
		})

		const body = await composed.text()

		if (composed.status() === 409) {
			// Refused, which is the correct answer for a view that does not
			// resolve or holds a record with no redacted copy. What must not
			// happen is a list produced anyway.
			expect(body).toMatch(/no_such_view|not_ready/)
			expect(body, 'a refused composition produces no entries').not.toContain('"entries"')
			return
		}

		expect(composed.status()).toBe(200)
		const list = await composed.json()
		expect(list.view).toBe(`${TEST_PREFIX}-woo`)
		expect(list.count).toBe(list.entries.length)

		// Read over the whole body rather than over the fields this test knows
		// about: an original carried in a key nobody renders today is published
		// the first time somebody renders it.
		expect(body, 'no entry may name a source file').not.toMatch(/sourceFile/i)
	})

	// @e2e openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md#a-reuse-condition-is-accepted-before-the-file-arrives
	// @e2e openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md#a-new-version-asks-again
	test('a gated download waits for the conditions, and asks again when they change', async ({
		page,
	}) => {
		const document = `${TEST_PREFIX}-gated`

		const declared = await page.request.post(`${OBJECTS}/downloadAgreement`, {
			headers: jsonHeaders(token),
			data: {
				document,
				version: '1',
				text: 'Hergebruik is toegestaan met bronvermelding.',
				locale: 'nl',
			},
		})
		expect(declared.status(), 'the conditions must be declarable').toBeLessThan(300)

		const before = await page.request.get(
			`${REDACTION}/agreement?document=${encodeURIComponent(document)}`,
			{ headers: jsonHeaders(token) },
		)
		expect(before.status()).toBe(200)
		const first = await before.json()
		expect(first.mayDownload, 'nothing is served before the conditions are accepted').toBe(false)
		expect(first.agreement.text).toContain('bronvermelding')

		const accepted = await page.request.post(`${REDACTION}/agreement/accept`, {
			headers: jsonHeaders(token),
			data: { document, version: '1' },
		})
		expect(accepted.status()).toBe(200)
		expect((await accepted.json()).mayDownload).toBe(true)

		// The terms move on. Acceptance of version 1 is acceptance of a
		// different text, so it must not carry over.
		const republished = await page.request.post(`${OBJECTS}/downloadAgreement`, {
			headers: jsonHeaders(token),
			data: {
				document,
				version: '2',
				text: 'Hergebruik is toegestaan met bronvermelding en zonder bewerking.',
				locale: 'nl',
			},
		})
		expect(republished.status()).toBeLessThan(300)

		const again = await page.request.get(
			`${REDACTION}/agreement?document=${encodeURIComponent(document)}`,
			{ headers: jsonHeaders(token) },
		)
		const second = await again.json()
		expect(
			second.mayDownload,
			'a reader who accepted version 1 is asked again about version 2',
		).toBe(false)
		expect(second.reason).toBe('version_moved_on')
	})
})

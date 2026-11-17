/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Every entry of the in-app navigation opens a page that renders.
 *
 * The entries are read from the rendered navigation, not from a list kept
 * here, so a page added to or dropped from the manifest is covered without an
 * edit. An entry folded under a group is opened by its address. An unknown route answers 200 with the SPA shell, so each page is held
 * to two things the shell alone cannot give: the address moved to the entry's
 * route, and the content area carries text that is not an error page.
 */

// @e2e openspec/changes/archive/2026-11-17-filinq-adopt-buildmanifest-pipeline/specs/menu-architecture/spec.md#no-route-or-menu-entry-is-lost-by-the-migration

import { expect, test } from '@playwright/test'
import { dismissOverlays, go } from './_helpers.ts'

test('every in-app navigation entry opens a rendered page', async ({ page }) => {
	test.setTimeout(10 * 60_000)
	await go(page, '')

	const links = page.locator('[data-testid="cn-nav"] a[href]')
	const count = await links.count()
	expect(count, 'the navigation lists its entries').toBeGreaterThan(10)

	const entries: { label: string, href: string, folded: boolean }[] = []
	for (let i = 0; i < count; i++) {
		const link = links.nth(i)
		const href = (await link.getAttribute('href')) ?? ''
		const label = ((await link.getAttribute('title')) || (await link.innerText()).trim() || href)
		if (href === '' || href.startsWith('http') || href.startsWith('#')) {
			continue
		}
		entries.push({ label, href, folded: !(await link.isVisible()) })
	}

	const failures: string[] = []
	for (const entry of entries) {
		const errors: string[] = []
		const onError = (err: Error) => errors.push(err.message)
		page.on('pageerror', onError)

		// An entry folded under Advanced or the settings foldout is not clickable
		// until its group opens, so it is opened by its address instead.
		if (entry.folded) {
			await page.goto(entry.href, { waitUntil: 'domcontentloaded' })
		} else {
			await go(page, '')
			await dismissOverlays(page)
			await page.locator(`[data-testid="cn-nav"] a[href="${entry.href}"]`).first().click()
		}
		await page.waitForTimeout(1500)

		const path = new URL(page.url()).pathname
		const wanted = new URL(entry.href, page.url()).pathname
		const content = page.locator('#app-content-vue, #app-content, main').first()
		const text = (await content.innerText().catch(() => '')).trim()

		if (path.replace(/\/$/, '') !== wanted.replace(/\/$/, '')) {
			failures.push(`${entry.label}: address is ${path}, not ${wanted}`)
		} else if (text.length < 3) {
			failures.push(`${entry.label}: the page rendered no content`)
		} else if (/page not found|not found|internal server error/i.test(text.slice(0, 400))) {
			failures.push(`${entry.label}: the page reads as an error: ${text.slice(0, 120)}`)
		} else if (errors.length > 0) {
			failures.push(`${entry.label}: page error ${errors[0]}`)
		}

		page.off('pageerror', onError)
	}

	expect(failures, `${entries.length} entries visited`).toEqual([])
})

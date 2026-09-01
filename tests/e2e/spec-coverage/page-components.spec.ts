/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-26 visual-coverage — per-page-component rendering tests.
 *
 * WHAT THIS FILE IS FOR
 * ---------------------
 * Every Filinq page component under `src/views/` needs a visual proof:
 * either a pixel baseline under `tests/e2e/visual/**` or an e2e test that
 * drives the component in a browser (hydra gate-26). Pixel baselines are not
 * an option here — `tests/e2e/playwright.config.ts` deliberately excludes
 * `**\/visual/**` from the CI project because the PNGs are host-font/GPU
 * specific and a dev-container baseline cannot byte-match a CI Linux runner.
 * So the proof is behavioural: navigate to the component's real route and
 * assert on markup that ONLY that component renders.
 *
 * THE RULE EVERY TEST HERE FOLLOWS
 * --------------------------------
 * An unknown subpath under a Nextcloud app answers **200 with the SPA HTML
 * shell**, so `toHaveURL(...)`, a status check, or `expect(body).toBeVisible()`
 * all pass for a route that does not exist. None of those prove a component
 * rendered. Each test below therefore asserts a string or a class that is
 * written in that component's own template and nowhere else — a heading, a
 * form control, an empty state. Where two components render near-identical
 * markup (the two anonymisation drop zones), the assertion is pinned to the
 * text that differs between them, so neither test can be satisfied by the
 * other component.
 *
 * No `.catch(() => {})` anywhere: a swallowed timeout is a false pass. No
 * `networkidle` either — it never settles on Nextcloud (ADR-074 rule 4 /
 * gate-58); `go()` waits for the app shell and then each assertion retries
 * over the route-specific markup.
 */

import { expect, test } from '@playwright/test'
import { dismissOverlays, go, waitForNcContentReady } from './_helpers.ts'

/**
 * A syntactically valid UUID that no Filinq object can have.
 *
 * Used by the two detail-page tests. Both detail components catch their own
 * fetch failure (`customDictionaryStore.fetchDictionary` / the consent store
 * both `catch` and return null), so the route still mounts the component and
 * paints its own not-found / empty state — which is exactly the state under
 * test. Seeding a real object would test the happy path of a different
 * component (the index that created it); this tests the detail page itself.
 */
const ABSENT_ID = '00000000-0000-4000-8000-000000000000'

// ---------------------------------------------------------------------------
// Anonymisation surfaces
// ---------------------------------------------------------------------------

test.describe('page components — anonymization', () => {
	test('AnonymizationIndex paints its own page heading at /anonymization', async ({
		page,
	}) => {
		await go(page, 'anonymization')
		// `.anonymization-page__title` exists in exactly one template in the
		// app. The SPA shell renders no such element, so this cannot pass on
		// the bare shell.
		await expect(page.locator('.anonymization-page__title')).toHaveText(
			'Anonymization',
		)
	})

	test('AnonymizationWidget paints its upload drop zone inside /anonymization', async ({
		page,
	}) => {
		await go(page, 'anonymization')
		const widget = page.locator('.anonymization-page .anonymization-widget')
		await expect(widget).toBeVisible()
		await expect(widget.locator('.drop-title')).toHaveText(
			'Drag and drop one or more documents',
		)
		// ODT appears in THIS widget's subtitle and NOT in the dashboard
		// widget's (which lists only .docx/PDF/TXT), so this assertion pins the
		// test to the component it names.
		await expect(widget.locator('.drop-subtitle')).toHaveText(
			'Only Word (.docx), ODT, PDF or TXT files are supported. Maximum file size 500 MB.',
		)
		await expect(
			widget.getByRole('button', { name: '+ Select files' }),
		).toBeVisible()
		await expect(widget.locator('input[type="file"]')).toHaveAttribute(
			'aria-label',
			'Select files to anonymise',
		)
	})

	test('FolderAnonymizationView paints its folder-path form at /folder-anonymization', async ({
		page,
	}) => {
		await go(page, 'folder-anonymization')
		const view = page.locator('.folder-anonymization')
		await expect(
			view.getByRole('heading', { name: 'Folder Analysis & Anonymization' }),
		).toBeVisible()
		await expect(
			view.getByText(
				'Enter a folder path from your Nextcloud files to analyze all documents in it.',
			),
		).toBeVisible()
		// Step 1 of the view — the only step reachable without a completed
		// extraction run.
		await expect(view.locator('input.folder-path-input')).toHaveAttribute(
			'aria-label',
			'Folder path to analyse',
		)
		await expect(
			view.getByRole('button', { name: 'Analyze Folder' }),
		).toBeVisible()
	})
})

// ---------------------------------------------------------------------------
// Dashboard
// ---------------------------------------------------------------------------

test.describe('page components — dashboard', () => {
	test('DashboardIndex paints its consent KPI links at the app root', async ({
		page,
	}) => {
		await go(page, '')
		// The KPI tiles are <RouterLink>s with explicit aria-labels written in
		// DashboardIndex's template — the shell has no such links.
		await expect(
			page.getByRole('link', { name: /^Total Consents/ }),
		).toBeVisible()
		await expect(
			page.getByRole('link', { name: /^Pending consents/ }),
		).toBeVisible()
		await expect(
			page.getByRole('link', { name: /^Approved consents/ }),
		).toBeVisible()
		await expect(
			page.getByRole('link', { name: /^Objected consents/ }),
		).toBeVisible()
	})

	test('AnonymizationDashboardWidget paints its quick-anonymisation panel on the dashboard', async ({
		page,
	}) => {
		await go(page, '')
		const widget = page.locator('.filinq-anon-widget')
		await expect(widget).toBeVisible()
		// The dashboard widget's supported-format line omits ODT; the in-app
		// AnonymizationWidget's includes it. Asserting the exact string keeps
		// the two components' tests non-interchangeable.
		await expect(widget.locator('.drop-subtitle')).toHaveText(
			'Only Word (.docx), PDF or TXT files are supported. Maximum file size 500 MB.',
		)
		await expect(
			widget.getByRole('button', { name: '+ Select files' }),
		).toBeVisible()
	})
})

// ---------------------------------------------------------------------------
// Consent
// ---------------------------------------------------------------------------

test.describe('page components — consent', () => {
	test('ConsentIndex paints its workflow description and record list at /consent', async ({
		page,
	}) => {
		await go(page, 'consent')
		// The description is ConsentIndex's own `description` prop on
		// CnIndexPage; no other page and no shell chrome carries this string.
		await expect(
			page.getByText(
				'Per-document consent records produced by the publication-clearance workflow.',
			),
		).toBeVisible()
		// The list itself: real rows, or this page's own `empty-text`.
		const table = page.locator('#content table, .app-content table').first()
		const empty = page.getByText('No consent records found')
		await expect(
			table.or(empty).first(),
			'the consent list must render rows or its empty state',
		).toBeVisible()

		// TWO THINGS THIS PAGE DECLARED AND DID NOT RENDER — measured from the
		// accessibility snapshot of run 31335736716, where `<main>` was exactly:
		//     heading "Consent Management" [level=1]
		//     paragraph: Per-document consent records produced by the …
		//     button "Refresh" / note "No consent records found" / button "Actions"
		//
		// 1. FIXED. `<template #above-table>` (`.consent-stats`, four
		//    CnStatsBlocks) was dropped on the floor: CnIndexPage has no
		//    `above-table` slot — the slot between the page header and the
		//    actions bar is `below-header`, and Vue drops an unmatched named
		//    slot silently. Renamed in views/consent/ConsentIndex,
		//    views/policy/ProhibitionIndex and views/policy/StandingConsentIndex;
		//    all three verified rendering. The cards are now asserted by
		//    `consent statistics render four colour-coded cards matching the
		//    API payload` in ./consent-management.spec.ts, which is what
		//    carries the `#view-consent-statistics` tag.
		//
		//    A FOURTH view, views/consent/StandingConsentIndex, still carries
		//    the dead `#above-table` name and was deliberately left alone: it
		//    is an orphaned legacy duplicate that no registry entry mounts (see
		//    the `orphaned-view` record for it in tests/unit/reachability.spec.js),
		//    so there is no route on which the rename could be verified.
		//
		// 2. STILL OPEN. The h1 reads "Consent Management" (the manifest page
		//    title), not the "Consent Workflow" this component binds to
		//    CnIndexPage's `title`: CnPageRenderer forwards the manifest's
		//    top-level `title` to the page component, ConsentIndex declares no
		//    `title` prop, so it falls through onto CnIndexPage and wins over
		//    the explicit binding. Recorded here and not asserted.
	})

	test('ConsentDetail paints its no-record state at /consent/<absent-id>', async ({
		page,
	}) => {
		await go(page, `consent/${ABSENT_ID}`)
		// CnDetailPage's error slot, filled by ConsentDetail with its own copy.
		await expect(page.getByText('No consent record selected.')).toBeVisible()
		await expect(
			page.getByRole('button', { name: 'Back to Consents' }).first(),
		).toBeVisible()
	})
})

// ---------------------------------------------------------------------------
// Custom dictionaries
// ---------------------------------------------------------------------------

test.describe('page components — custom dictionaries', () => {
	test('CustomDictionaryIndex paints its list description at /custom-dictionaries', async ({
		page,
	}) => {
		await go(page, 'custom-dictionaries')
		await expect(page.getByText(/Organisation-managed term lists/)).toBeVisible()
		await expect(
			page.getByText(/add an extra recognizer alongside Presidio and regex/),
		).toBeVisible()
	})

	test('CustomDictionaryDetail paints its term-management panel at /custom-dictionaries/<absent-id>', async ({
		page,
	}) => {
		await go(page, `custom-dictionaries/${ABSENT_ID}`)
		const detail = page.locator('.custom-dictionary-detail')
		await expect(
			detail.getByRole('button', { name: 'Back to custom dictionaries' }),
		).toBeVisible()
		await expect(detail.getByRole('heading', { name: 'Terms' })).toBeVisible()
		await expect(detail.getByRole('button', { name: 'Add term' })).toBeVisible()
		// No dictionary resolved, so the terms panel shows its empty state.
		await expect(detail.getByText('No terms yet')).toBeVisible()
	})
})

// ---------------------------------------------------------------------------
// Comparison, gallery, documents
// ---------------------------------------------------------------------------

test.describe('page components — standalone pages', () => {
	test('ComparisonView paints its two file pickers at /comparison', async ({
		page,
	}) => {
		await go(page, 'comparison')
		const view = page.locator('.comparison-view')
		await expect(
			view.getByRole('heading', { name: 'Document comparison' }),
		).toBeVisible()
		await expect(view.locator('.comparison-view__subtitle')).toHaveText(
			'Compare two versions of a file or two distinct files side by side.',
		)
		await expect(view.locator('.comparison-view__pickers')).toBeVisible()
		await expect(
			view.getByRole('button', { name: 'Compare', exact: true }),
		).toBeVisible()
	})

	test('ComponentGallery paints its masthead and component sections at /gallery', async ({
		page,
	}) => {
		await go(page, 'gallery')
		const gallery = page.locator('.dd-gallery')
		await expect(
			gallery.getByRole('heading', {
				name: 'Filinq component gallery',
				level: 1,
			}),
		).toBeVisible()
		await expect(
			gallery.getByRole('navigation', { name: 'Components' }),
		).toBeVisible()
		// A section heading, not the table-of-contents link of the same name.
		await expect(
			gallery.getByRole('heading', { name: 'DdPageHeader' }),
		).toBeVisible()
	})

	test('MyDocumentsIndex paints its documents header and search bar at /my-documents', async ({
		page,
	}) => {
		await go(page, 'my-documents')
		const view = page.locator('.my-documents-wrapper')
		await expect(view.locator('.dd-page-header__title')).toHaveText('Documents')
		await expect(
			view.locator('.my-documents-search input').first(),
		).toHaveAttribute('placeholder', 'Search by name')
	})
})

// ---------------------------------------------------------------------------
// Admin settings
// ---------------------------------------------------------------------------

test.describe('page components — admin settings', () => {
	test('EntityTypeSelector paints its detection hint on the Filinq admin settings page', async ({
		page,
	}) => {
		await page.goto('/index.php/settings/admin/filinq', {
			waitUntil: 'domcontentloaded',
		})
		await waitForNcContentReady(page)
		await dismissOverlays(page)
		const selector = page.locator('.entity-type-selector')
		await expect(selector).toBeVisible()
		// The component computes one of three hints; all three name entity
		// types, and none of them is rendered by the settings shell around it.
		await expect(selector.locator('.entity-type-selector__hint')).toHaveText(
			/entity types (are|available)|Only the enabled types are detected/i,
		)
	})
})

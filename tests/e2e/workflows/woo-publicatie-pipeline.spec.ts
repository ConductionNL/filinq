/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Workflow: a document goes through the Woo publication pipeline.
 *
 * The API is answered by route handlers that keep one record in memory, so
 * the flow runs without OpenCatalogi or consent data on the instance. The
 * rules the server applies at each step are PHPUnit's
 * (tests/unit/Service/Publication/PublicationPipelineServiceTest.php).
 */

// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#ready-when-all-three-gates-are-clear
// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#open-objection-window-blocks-readiness
// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#regressed-readiness-demotes-the-record-at-handoff
// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#successful-handoff-creates-the-endpoint-publication
// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#withdraw-a-publication
// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#wizard-reflects-gate-state
// @e2e openspec/changes/woo-hand-off-files-its-category/specs/woo-publicatie-pipeline/spec.md#a-handed-off-decision-appears-in-its-category-sitemap

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from '../spec-coverage/_helpers.ts'

/**
 * Serve one in-memory record and apply the steps the page sends.
 *
 * @param page    The page
 * @param initial The record to start from
 * @param onStep  What a step does to the record, or an error answer
 */
async function pipeline(
	page: Page,
	initial: Record<string, unknown>,
	onStep: (
		step: string,
		record: Record<string, unknown>,
		body: Record<string, unknown>,
	) => { status?: number; json: Record<string, unknown> },
): Promise<void> {
	let record = { uuid: 'pub-1', platformAvailable: true, log: [], ...initial }
	await page.route('**/apps/filinq/api/publications/categories', (route) =>
		route.fulfill({
			json: { results: [{ code: 'infocat009', label: 'Adviezen' }] },
		}),
	)
	await page.route('**/apps/filinq/api/publications/pub-1**', async (route) => {
		const url = route.request().url()
		if (route.request().method() === 'GET') {
			await route.fulfill({ json: record })
			return
		}
		const step = url.split('/pub-1/')[1] || ''
		const answer = onStep(step, record, route.request().postDataJSON() || {})
		if (!answer.status || answer.status < 300) {
			record = { ...record, ...answer.json }
		}
		await route.fulfill({ status: answer.status || 200, json: answer.json })
	})
}

const clear = {
	documentFileRef: '42',
	entitiesReviewed: true,
	consentClear: true,
	prohibitionsClear: true,
	readinessReasons: [],
	officieleTitel: 'Besluit 2025-017',
	wooCategory: 'infocat009',
	publicatiedatum: '2026-09-29',
}

test.describe('woo publication pipeline', () => {
	test('a document with all three checks clear is handed off and published', async ({
		page,
	}) => {
		// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#ready-when-all-three-gates-are-clear
		// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#successful-handoff-creates-the-endpoint-publication
		await pipeline(page, { ...clear, status: 'ready' }, (step) =>
			step === 'handoff'
				? { json: { status: 'published', endpointPublicationRef: 'oc-1' } }
				: { json: {} },
		)
		await go(page, 'publications/pub-1')
		await expect(page.getByText('Ready to hand off')).toBeVisible()
		await page.getByRole('button', { name: 'Hand off for publication' }).click()
		await expect(page.getByText('Published', { exact: true })).toBeVisible()
	})

	test('an open objection window keeps it from being ready, and says until when', async ({
		page,
	}) => {
		// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#open-objection-window-blocks-readiness
		await pipeline(
			page,
			{
				...clear,
				status: 'draft',
				consentClear: false,
				readinessReasons: [
					'Consent request c-1: waiting for a reaction until 2026-10-27',
				],
			},
			() => ({ json: {} }),
		)
		await go(page, 'publications/pub-1')
		await expect(page.getByText('Not ready')).toBeVisible()
		await expect(page.getByText('until 2026-10-27')).toBeVisible()
		await expect(
			page.getByRole('button', { name: 'Hand off for publication' }),
		).toBeDisabled()
	})

	test('a hand-off that finds a new objection is refused with the reason', async ({
		page,
	}) => {
		// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#regressed-readiness-demotes-the-record-at-handoff
		await pipeline(page, { ...clear, status: 'ready' }, (step) =>
			step === 'handoff'
				? {
						status: 409,
						json: {
							error: 'The publication is not ready to hand off',
							reasons: [
								'Consent request c-9: an objection was received',
							],
						},
					}
				: { json: {} },
		)
		await go(page, 'publications/pub-1')
		await page.getByRole('button', { name: 'Hand off for publication' }).click()
		await expect(page.getByText('an objection was received')).toBeVisible()
	})

	test('a published document is withdrawn with a reason', async ({ page }) => {
		// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#withdraw-a-publication
		let sent: Record<string, unknown> = {}
		await pipeline(page, { ...clear, status: 'published' }, (step, _r, body) => {
			sent = body
			return step === 'withdraw'
				? {
						json: {
							status: 'depublished',
							depublicationReason: body.reason,
						},
					}
				: { json: {} }
		})
		await go(page, 'publications/pub-1')
		await page.getByLabel('Why is it withdrawn?').fill('Wrong version published')
		await page.getByRole('button', { name: 'Withdraw publication' }).click()
		await expect(page.getByText('Withdrawn', { exact: true })).toBeVisible()
		expect(sent.reason).toBe('Wrong version published')
	})

	test('an unchecked document shows the blocking step with a link to the review', async ({
		page,
	}) => {
		// @e2e openspec/specs/woo-publicatie-pipeline/spec.md#wizard-reflects-gate-state
		await pipeline(
			page,
			{ ...clear, status: 'draft', entitiesReviewed: false },
			() => ({ json: {} }),
		)
		await go(page, 'publications/pub-1')
		const check = page.locator('.publications__checks li[data-ok="no"]')
		await expect(check).toContainText('Detected entities checked by a person')
		await check.getByRole('link', { name: 'Resolve this' }).click()
		await expect(page).toHaveURL(/\/anonymization/)
	})
})

/*
 * THE REAL HAND-OFF. With `E2E_WOO_READY_RECORD` (the uuid of a publication
 * record that is ready, with category "Besluiten op Woo-verzoeken" and complete
 * metadata) and `E2E_OPENCATALOGI_CATALOG` (the catalog slug or id the
 * sitemaps are served under), the operator hands the record off on the page
 * and the category sitemap of OpenCatalogi then lists the publication. Without
 * both it is skipped: it needs a live instance with OpenCatalogi.
 */
test.describe('woo hand-off files its category', () => {
	const recordId = process.env.E2E_WOO_READY_RECORD ?? ''
	const catalog = process.env.E2E_OPENCATALOGI_CATALOG ?? ''

	test('a handed-off decision appears in its category sitemap', async ({ page }) => {
		// @e2e openspec/changes/woo-hand-off-files-its-category/specs/woo-publicatie-pipeline/spec.md#a-handed-off-decision-appears-in-its-category-sitemap
		test.skip(
			recordId === '' || catalog === '',
			'needs E2E_WOO_READY_RECORD and E2E_OPENCATALOGI_CATALOG on a live instance',
		)

		await go(page, `publications/${recordId}`)
		await expect(page.getByText('Ready to hand off')).toBeVisible()
		const handedOff = page.waitForResponse(
			(response) =>
				response.url().includes(`/apps/filinq/api/publications/${recordId}/handoff`)
				&& response.request().method() === 'POST',
		)
		await page.getByRole('button', { name: 'Hand off for publication' }).click()
		const answer = await handedOff
		expect(answer.status(), await answer.text()).toBeLessThan(300)
		const publication = String((await answer.json()).endpointPublicationRef ?? '')
		expect(publication).not.toBe('')

		const platform = await page.request.get(
			`/index.php/apps/openregister/api/objects/publication/publication/${publication}`,
			{ headers: { 'OCS-APIRequest': 'true' } },
		)
		expect(platform.ok(), await platform.text()).toBeTruthy()
		expect((await platform.json()).wooCategory).toBe('infocat001')

		await page.goto(
			`/index.php/apps/opencatalogi/api/${encodeURIComponent(catalog)}/sitemaps/sitemapindex-diwoo-infocat001.xml`,
		)
		await expect(page.locator('body')).toContainText(publication)
	})
})
